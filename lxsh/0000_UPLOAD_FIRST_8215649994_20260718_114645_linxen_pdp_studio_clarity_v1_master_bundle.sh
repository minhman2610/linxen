#!/usr/bin/env bash
set -Eeuo pipefail

TARGET=''

for ARG in "$@"; do
    case "$ARG" in
        --target=erp)
            TARGET='erp'
            ;;
        --target=storefront)
            TARGET='storefront'
            ;;
        *)
            printf 'ERROR: Unknown argument: %s\n' "$ARG" >&2
            exit 1
            ;;
    esac
done

test -n "$TARGET" || {
    printf '%s\n' \
      'Usage: bash <patch>.sh --target=erp|--target=storefront' \
      >&2
    exit 1
}

test -f artisan || {
    printf '%s\n' \
      'ERROR: Run from the intended Laravel project root.' \
      >&2
    exit 1
}

PATCH_NAME="linxen_pdp_studio_clarity_v1_${TARGET}"
BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_$(date +%Y%m%d_%H%M%S)"
MANIFEST="${BACKUP_ROOT}/manifest.tsv"
PATCH_WRITTEN=0

backup_file() {
    FILE="$1"
    mkdir -p "$BACKUP_ROOT/$(dirname "$FILE")"

    if [ -e "$FILE" ]; then
        cp -p "$FILE" "$BACKUP_ROOT/$FILE"
        printf 'existing\t%s\n' "$FILE" >> "$MANIFEST"
    else
        printf 'new\t%s\n' "$FILE" >> "$MANIFEST"
    fi
}

rollback() {
    STATUS=$?

    if [ "$PATCH_WRITTEN" -eq 1 ] && [ -f "$MANIFEST" ]; then
        printf 'Rollback target=%s\n' "$TARGET" >&2

        while IFS=$'\t' read -r KIND FILE; do
            case "$KIND" in
                existing)
                    if [ -f "$BACKUP_ROOT/$FILE" ]; then
                        mkdir -p "$(dirname "$FILE")"
                        cp -p "$BACKUP_ROOT/$FILE" "$FILE"
                    fi
                    ;;
                new)
                    rm -f "$FILE"
                    ;;
            esac
        done < "$MANIFEST"
    fi

    exit "$STATUS"
}

trap rollback ERR

decode_to_file() {
    FILE="$1"
    TMP_FILE="$(mktemp "${TMPDIR:-/tmp}/linxen_pdp_clarity.XXXXXX")"

    mkdir -p "$(dirname "$FILE")"

    if printf 'Zg==' | base64 --decode >/dev/null 2>&1; then
        base64 --decode > "$TMP_FILE"
    else
        base64 -D > "$TMP_FILE"
    fi

    mv "$TMP_FILE" "$FILE"
    chmod 0644 "$FILE"
}

artisan_safe() {
    if [ "$(id -u)" -eq 0 ] \
        && command -v sudo >/dev/null 2>&1 \
        && id www-data >/dev/null 2>&1
    then
        sudo -u www-data env \
          HOME="$(pwd)" \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan "$@"
    else
        env \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan "$@"
    fi
}

if [ "$TARGET" = 'erp' ]; then
    SERVICE='app/Services/Commerce/CommercePdpExperienceService.php'
    COMMAND='app/Console/Commands/Commerce/AuditPdpProductClarityCommand.php'

    for FILE in \
      "$SERVICE" \
      app/Services/Commerce/CommerceCatalogProjectionService.php \
      app/Services/Commerce/StorefrontScopeResolver.php \
      app/Http/Resources/Commerce/V2/CommerceProductResource.php
    do
        test -f "$FILE" || {
            printf 'ERROR: Missing ERP dependency: %s\n' "$FILE" >&2
            exit 1
        }
    done

    backup_file "$SERVICE"
    backup_file "$COMMAND"
    PATCH_WRITTEN=1

    export PDP_CLARITY_ERP_SERVICE="$SERVICE"

    php <<'PHP'
<?php

$path = getenv('PDP_CLARITY_ERP_SERVICE');
$marker = 'AI_PATCH_LINXEN_PDP_PRODUCT_CLARITY_MEDIA_V1';

if (! is_string($path) || ! is_file($path)) {
    fwrite(STDERR, "ERROR: CommercePdpExperienceService path không hợp lệ.\n");
    exit(1);
}

$source = file_get_contents($path);

if (! is_string($source)) {
    fwrite(STDERR, "ERROR: Không đọc được CommercePdpExperienceService.\n");
    exit(1);
}

if (substr_count($source, $marker) > 1) {
    fwrite(STDERR, "ERROR: PDP clarity marker xuất hiện nhiều hơn một lần.\n");
    exit(1);
}

if (! str_contains($source, $marker)) {
    $defaultAnchor = "        \$defaultColor = \$this->defaultColor(\n";

    if (substr_count($source, $defaultAnchor) !== 1) {
        fwrite(STDERR, "ERROR: ERP PDP default-color anchor drift.\n");
        exit(1);
    }

    $clarityBuild = <<<'BLOCK'
        /* AI_PATCH_LINXEN_PDP_PRODUCT_CLARITY_MEDIA_V1 */
        $clarityLimit = max(
            4,
            min(8, $limit + 2)
        );
        $claritySets = $colors
            ->map(function (array $color) use (
                $allMedia,
                $clarityLimit,
                $priority
            ) {
                return $this->productClarityMediaSet(
                    $color,
                    $allMedia,
                    $clarityLimit,
                    $priority
                );
            })
            ->values();

BLOCK;

    $source = str_replace(
        $defaultAnchor,
        $clarityBuild . $defaultAnchor,
        $source,
        $count
    );

    if ($count !== 1) {
        fwrite(STDERR, "ERROR: Không chèn được clarity set builder.\n");
        exit(1);
    }

    $mediaAnchor = "                'media_sets_by_color' => \$sets->all(),\n";

    if (substr_count($source, $mediaAnchor) !== 1) {
        fwrite(STDERR, "ERROR: ERP PDP media return anchor drift.\n");
        exit(1);
    }

    $source = str_replace(
        $mediaAnchor,
        $mediaAnchor
            . "                'clarity_sets_by_color' => \$claritySets->all(),\n",
        $source,
        $count
    );

    if ($count !== 1) {
        fwrite(STDERR, "ERROR: Không public clarity sets trong PDP contract.\n");
        exit(1);
    }

    $methodAnchor = "    protected function colorMediaSet(\n";

    if (substr_count($source, $methodAnchor) !== 1) {
        fwrite(STDERR, "ERROR: ERP PDP colorMediaSet anchor drift.\n");
        exit(1);
    }

    $helpers = <<<'BLOCK'
    protected function productClarityMediaSet(
        array $color,
        Collection $allMedia,
        int $limit,
        array $priority
    ): array {
        $colorKey = $this->colorKey(
            (string) data_get($color, 'key')
                ?: (string) data_get($color, 'code')
                ?: (string) data_get($color, 'label')
        );
        $exact = $allMedia
            ->filter(function ($item) use ($colorKey) {
                $category = Str::upper(trim(
                    (string) data_get($item, 'category')
                ));
                $itemColorKey = $this->colorKey(
                    (string) data_get(
                        $item,
                        'color.key',
                        data_get($item, 'color.code')
                    )
                );

                return $itemColorKey === $colorKey
                    && ! $this->isSizeChart($item)
                    && trim((string) data_get($item, 'url')) !== ''
                    && (
                        $category === 'OPENING_PRODUCT_CLARITY_SINGLE'
                        || Str::contains(
                            $category,
                            'PRODUCT_CLARITY'
                        )
                    );
            })
            ->unique(fn ($item) => (string) data_get($item, 'url'))
            ->sortBy(function ($item) {
                $primary = (bool) data_get(
                    $item,
                    'is_primary_for_scope',
                    false
                ) || (bool) data_get(
                    $item,
                    'is_primary',
                    false
                );

                return sprintf(
                    '%01d-%04d-%020d',
                    $primary ? 0 : 1,
                    $this->roleRank($this->inferRole($item)),
                    999999999999 - (int) data_get(
                        $item,
                        'database_id',
                        0
                    )
                );
            })
            ->values();

        $selected = collect();
        $angleKeys = [];

        foreach ($exact as $item) {
            if ($selected->count() >= $limit) {
                break;
            }

            $angleKey = Str::lower(trim(
                (string) $this->shotAngle($item)
                . '|'
                . (string) $this->inferRole($item)
            ));

            if (
                $angleKey !== '|'
                && array_key_exists($angleKey, $angleKeys)
                && $exact->count() > $limit
            ) {
                continue;
            }

            $selected->push(
                $this->publicClarityItem(
                    $item,
                    $priority,
                    $selected->count()
                )
            );
            $angleKeys[$angleKey] = true;
        }

        if ($selected->count() < min($limit, $exact->count())) {
            foreach ($exact as $item) {
                if ($selected->count() >= $limit) {
                    break;
                }

                $url = (string) data_get($item, 'url');

                if ($selected->contains(
                    fn ($selectedItem) => (string) data_get(
                        $selectedItem,
                        'url'
                    ) === $url
                )) {
                    continue;
                }

                $selected->push(
                    $this->publicClarityItem(
                        $item,
                        $priority,
                        $selected->count()
                    )
                );
            }
        }

        return [
            'color_id' => (string) data_get($color, 'id'),
            'code' => (string) data_get($color, 'code'),
            'label' => (string) data_get($color, 'label'),
            'key' => $colorKey,
            'exact_color_only' => true,
            'category_code' => 'OPENING_PRODUCT_CLARITY_SINGLE',
            'source_count' => $exact->count(),
            'count' => $selected->count(),
            'items' => $selected->all(),
        ];
    }

    protected function publicClarityItem(
        mixed $item,
        array $priority,
        int $index
    ): array {
        $public = $this->publicItem(
            $item,
            $priority,
            $index
        );

        $public['role'] = $this->inferRole($item);
        $public['role_source'] = 'opening_product_clarity_single';
        $public['category_code'] = (string) data_get(
            $item,
            'category'
        );
        $public['shot_angle'] = $this->shotAngle($item);
        $public['fallback_reason'] = null;

        return $public;
    }

BLOCK;

    $source = str_replace(
        $methodAnchor,
        $helpers . $methodAnchor,
        $source,
        $count
    );

    if ($count !== 1) {
        fwrite(STDERR, "ERROR: Không chèn được ERP clarity helpers.\n");
        exit(1);
    }
}

foreach ([
    $marker,
    "'clarity_sets_by_color' => \$claritySets->all()",
    'protected function productClarityMediaSet(',
    "'OPENING_PRODUCT_CLARITY_SINGLE'",
    'protected function publicClarityItem(',
    "'role_source'] = 'opening_product_clarity_single'",
] as $required) {
    if (! str_contains($source, $required)) {
        fwrite(STDERR, "ERROR: Thiếu ERP clarity contract: {$required}\n");
        exit(1);
    }
}

$written = file_put_contents($path, $source);

if ($written === false || $written !== strlen($source)) {
    fwrite(STDERR, "ERROR: Không ghi đầy đủ CommercePdpExperienceService.\n");
    exit(1);
}

echo "ERP_PDP_PRODUCT_CLARITY_SOURCE=APPLIED\n";

PHP

    decode_to_file "$COMMAND" <<'ERP_CLARITY_AUDIT_B64'
PD9waHAKCm5hbWVzcGFjZSBBcHBcQ29uc29sZVxDb21tYW5kc1xDb21tZXJjZTsKCnVzZSBBcHBc
SHR0cFxSZXNvdXJjZXNcQ29tbWVyY2VcVjJcQ29tbWVyY2VQcm9kdWN0UmVzb3VyY2U7CnVzZSBB
cHBcU2VydmljZXNcQ29tbWVyY2VcQ29tbWVyY2VDYXRhbG9nUHJvamVjdGlvblNlcnZpY2U7CnVz
ZSBBcHBcU2VydmljZXNcQ29tbWVyY2VcU3RvcmVmcm9udFNjb3BlUmVzb2x2ZXI7CnVzZSBJbGx1
bWluYXRlXENvbnNvbGVcQ29tbWFuZDsKdXNlIElsbHVtaW5hdGVcSHR0cFxSZXF1ZXN0Owp1c2Ug
SWxsdW1pbmF0ZVxTdXBwb3J0XENvbGxlY3Rpb247CnVzZSBJbGx1bWluYXRlXFN1cHBvcnRcU3Ry
Owp1c2UgVGhyb3dhYmxlOwoKY2xhc3MgQXVkaXRQZHBQcm9kdWN0Q2xhcml0eUNvbW1hbmQgZXh0
ZW5kcyBDb21tYW5kCnsKICAgIHByb3RlY3RlZCAkc2lnbmF0dXJlID0gJ2NvbW1lcmNlOmF1ZGl0
LXBkcC1wcm9kdWN0LWNsYXJpdHkKICAgICAgICB7c2l0ZT1saW54ZW59CiAgICAgICAge3Byb2R1
Y3Q9NDQ3N30KICAgICAgICB7LS1leHBlY3QtZGVmYXVsdC1taW49MX0KICAgICAgICB7LS1qc29u
fSc7CgogICAgcHJvdGVjdGVkICRkZXNjcmlwdGlvbiA9ICdSZWFkLW9ubHkgYXVkaXQgZXhhY3Qt
Y29sb3IgT1BFTklOR19QUk9EVUNUX0NMQVJJVFlfU0lOR0xFIG1lZGlhIGZvciBQRFAuJzsKCiAg
ICBwdWJsaWMgZnVuY3Rpb24gaGFuZGxlKAogICAgICAgIFN0b3JlZnJvbnRTY29wZVJlc29sdmVy
ICRzY29wZVJlc29sdmVyLAogICAgICAgIENvbW1lcmNlQ2F0YWxvZ1Byb2plY3Rpb25TZXJ2aWNl
ICRwcm9qZWN0aW9uU2VydmljZQogICAgKTogaW50IHsKICAgICAgICB0cnkgewogICAgICAgICAg
ICAkY29udGV4dCA9ICR0aGlzLT5yZXNvbHZlQ29udGV4dCgKICAgICAgICAgICAgICAgICRzY29w
ZVJlc29sdmVyLAogICAgICAgICAgICAgICAgKHN0cmluZykgJHRoaXMtPmFyZ3VtZW50KCdzaXRl
JykKICAgICAgICAgICAgKTsKICAgICAgICAgICAgJHByb2plY3Rpb24gPSAkcHJvamVjdGlvblNl
cnZpY2UtPnByb2plY3QoCiAgICAgICAgICAgICAgICAkY29udGV4dCwKICAgICAgICAgICAgICAg
IChzdHJpbmcpICR0aGlzLT5hcmd1bWVudCgncHJvZHVjdCcpCiAgICAgICAgICAgICk7CiAgICAg
ICAgICAgICRwdWJsaWMgPSAobmV3IENvbW1lcmNlUHJvZHVjdFJlc291cmNlKAogICAgICAgICAg
ICAgICAgJHByb2plY3Rpb24KICAgICAgICAgICAgKSktPnRvQXJyYXkoCiAgICAgICAgICAgICAg
ICBSZXF1ZXN0OjpjcmVhdGUoCiAgICAgICAgICAgICAgICAgICAgJy9hcGkvY29tbWVyY2UvdjIv
Y2F0YWxvZy9wcm9kdWN0JywKICAgICAgICAgICAgICAgICAgICAnR0VUJwogICAgICAgICAgICAg
ICAgKQogICAgICAgICAgICApOwoKICAgICAgICAgICAgJHNldHMgPSBjb2xsZWN0KChhcnJheSkg
ZGF0YV9nZXQoCiAgICAgICAgICAgICAgICAkcHVibGljLAogICAgICAgICAgICAgICAgJ21lZGlh
LnBkcC5jbGFyaXR5X3NldHNfYnlfY29sb3InLAogICAgICAgICAgICAgICAgW10KICAgICAgICAg
ICAgKSktPnZhbHVlcygpOwogICAgICAgICAgICAkZGVmYXVsdENvbG9ySWQgPSAoc3RyaW5nKSBk
YXRhX2dldCgKICAgICAgICAgICAgICAgICRwdWJsaWMsCiAgICAgICAgICAgICAgICAnbWVkaWEu
cGRwLmRlZmF1bHRfY29sb3IuaWQnCiAgICAgICAgICAgICk7CiAgICAgICAgICAgICRkZWZhdWx0
U2V0ID0gKGFycmF5KSAoCiAgICAgICAgICAgICAgICAkc2V0cy0+Zmlyc3RXaGVyZSgKICAgICAg
ICAgICAgICAgICAgICAnY29sb3JfaWQnLAogICAgICAgICAgICAgICAgICAgICRkZWZhdWx0Q29s
b3JJZAogICAgICAgICAgICAgICAgKSA/OiBbXQogICAgICAgICAgICApOwogICAgICAgICAgICAk
ZXhwZWN0RGVmYXVsdE1pbiA9IG1heCgKICAgICAgICAgICAgICAgIDAsCiAgICAgICAgICAgICAg
ICAoaW50KSAkdGhpcy0+b3B0aW9uKAogICAgICAgICAgICAgICAgICAgICdleHBlY3QtZGVmYXVs
dC1taW4nCiAgICAgICAgICAgICAgICApCiAgICAgICAgICAgICk7CiAgICAgICAgICAgICRpdGVt
cyA9ICRzZXRzLT5mbGF0TWFwKAogICAgICAgICAgICAgICAgZm4gKCRzZXQpID0+IChhcnJheSkg
ZGF0YV9nZXQoCiAgICAgICAgICAgICAgICAgICAgJHNldCwKICAgICAgICAgICAgICAgICAgICAn
aXRlbXMnLAogICAgICAgICAgICAgICAgICAgIFtdCiAgICAgICAgICAgICAgICApCiAgICAgICAg
ICAgICktPnZhbHVlcygpOwoKICAgICAgICAgICAgJGNoZWNrcyA9IFsKICAgICAgICAgICAgICAg
ICdjbGFyaXR5X2NvbnRyYWN0X3ByZXNlbnQnID0+IGRhdGFfZ2V0KAogICAgICAgICAgICAgICAg
ICAgICRwdWJsaWMsCiAgICAgICAgICAgICAgICAgICAgJ21lZGlhLnBkcC5jbGFyaXR5X3NldHNf
YnlfY29sb3InCiAgICAgICAgICAgICAgICApICE9PSBudWxsLAogICAgICAgICAgICAgICAgJ2Ns
YXJpdHlfc2V0c19ub25fZW1wdHknID0+ICRzZXRzLT5pc05vdEVtcHR5KCksCiAgICAgICAgICAg
ICAgICAnZGVmYXVsdF9jb2xvcl9pZGVudGlmaWVkJyA9PiAkZGVmYXVsdENvbG9ySWQgIT09ICcn
LAogICAgICAgICAgICAgICAgJ2RlZmF1bHRfY2xhcml0eV9taW4nID0+IGNvdW50KAogICAgICAg
ICAgICAgICAgICAgIChhcnJheSkgZGF0YV9nZXQoCiAgICAgICAgICAgICAgICAgICAgICAgICRk
ZWZhdWx0U2V0LAogICAgICAgICAgICAgICAgICAgICAgICAnaXRlbXMnLAogICAgICAgICAgICAg
ICAgICAgICAgICBbXQogICAgICAgICAgICAgICAgICAgICkKICAgICAgICAgICAgICAgICkgPj0g
JGV4cGVjdERlZmF1bHRNaW4sCiAgICAgICAgICAgICAgICAnZXhhY3RfY29sb3Jfb25seScgPT4g
JHNldHMtPmV2ZXJ5KAogICAgICAgICAgICAgICAgICAgIGZuICgkc2V0KSA9PiAoYm9vbCkgZGF0
YV9nZXQoCiAgICAgICAgICAgICAgICAgICAgICAgICRzZXQsCiAgICAgICAgICAgICAgICAgICAg
ICAgICdleGFjdF9jb2xvcl9vbmx5JywKICAgICAgICAgICAgICAgICAgICAgICAgZmFsc2UKICAg
ICAgICAgICAgICAgICAgICApCiAgICAgICAgICAgICAgICApLAogICAgICAgICAgICAgICAgJ2dh
bGxlcnlfbWF4X2VpZ2h0JyA9PiAkc2V0cy0+ZXZlcnkoCiAgICAgICAgICAgICAgICAgICAgZm4g
KCRzZXQpID0+IGNvdW50KAogICAgICAgICAgICAgICAgICAgICAgICAoYXJyYXkpIGRhdGFfZ2V0
KAogICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNldCwKICAgICAgICAgICAgICAgICAgICAg
ICAgICAgICdpdGVtcycsCiAgICAgICAgICAgICAgICAgICAgICAgICAgICBbXQogICAgICAgICAg
ICAgICAgICAgICAgICApCiAgICAgICAgICAgICAgICAgICAgKSA8PSA4CiAgICAgICAgICAgICAg
ICApLAogICAgICAgICAgICAgICAgJ2NhdGVnb3J5X3Byb2R1Y3RfY2xhcml0eV9vbmx5JyA9PiAk
aXRlbXMtPmV2ZXJ5KAogICAgICAgICAgICAgICAgICAgIGZ1bmN0aW9uICgkaXRlbSk6IGJvb2wg
ewogICAgICAgICAgICAgICAgICAgICAgICAkY2F0ZWdvcnkgPSBTdHI6OnVwcGVyKHRyaW0oCiAg
ICAgICAgICAgICAgICAgICAgICAgICAgICAoc3RyaW5nKSBkYXRhX2dldCgKICAgICAgICAgICAg
ICAgICAgICAgICAgICAgICAgICAkaXRlbSwKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg
ICAnY2F0ZWdvcnlfY29kZScKICAgICAgICAgICAgICAgICAgICAgICAgICAgICkKICAgICAgICAg
ICAgICAgICAgICAgICAgKSk7CgogICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gU3RyOjpj
b250YWlucygKICAgICAgICAgICAgICAgICAgICAgICAgICAgICRjYXRlZ29yeSwKICAgICAgICAg
ICAgICAgICAgICAgICAgICAgICdQUk9EVUNUX0NMQVJJVFknCiAgICAgICAgICAgICAgICAgICAg
ICAgICk7CiAgICAgICAgICAgICAgICAgICAgfQogICAgICAgICAgICAgICAgKSwKICAgICAgICAg
ICAgICAgICdhbmdsZV9vcl9yb2xlX3ByZXNlbnQnID0+ICRpdGVtcy0+ZXZlcnkoCiAgICAgICAg
ICAgICAgICAgICAgZm4gKCRpdGVtKSA9PiB0cmltKAogICAgICAgICAgICAgICAgICAgICAgICAo
c3RyaW5nKSBkYXRhX2dldCgKICAgICAgICAgICAgICAgICAgICAgICAgICAgICRpdGVtLAogICAg
ICAgICAgICAgICAgICAgICAgICAgICAgJ3Nob3RfYW5nbGUnCiAgICAgICAgICAgICAgICAgICAg
ICAgICkKICAgICAgICAgICAgICAgICAgICAgICAgLiAoc3RyaW5nKSBkYXRhX2dldCgKICAgICAg
ICAgICAgICAgICAgICAgICAgICAgICRpdGVtLAogICAgICAgICAgICAgICAgICAgICAgICAgICAg
J3JvbGUnCiAgICAgICAgICAgICAgICAgICAgICAgICkKICAgICAgICAgICAgICAgICAgICApICE9
PSAnJwogICAgICAgICAgICAgICAgKSwKICAgICAgICAgICAgICAgICdwdWJsaWNfcmVzb3VyY2Vf
cHJlc2VydmVkJyA9PiBkYXRhX2dldCgKICAgICAgICAgICAgICAgICAgICAkcHVibGljLAogICAg
ICAgICAgICAgICAgICAgICdtZWRpYS5wZHAuY2xhcml0eV9zZXRzX2J5X2NvbG9yJwogICAgICAg
ICAgICAgICAgKSAhPT0gbnVsbCwKICAgICAgICAgICAgICAgICdwcm92aWRlcl9jYWxsX25vbmUn
ID0+IHRydWUsCiAgICAgICAgICAgICAgICAnZGJfd3JpdGVfbm9uZScgPT4gdHJ1ZSwKICAgICAg
ICAgICAgXTsKCiAgICAgICAgICAgICRwYXlsb2FkID0gWwogICAgICAgICAgICAgICAgJ29rJyA9
PiAhIGluX2FycmF5KAogICAgICAgICAgICAgICAgICAgIGZhbHNlLAogICAgICAgICAgICAgICAg
ICAgICRjaGVja3MsCiAgICAgICAgICAgICAgICAgICAgdHJ1ZQogICAgICAgICAgICAgICAgKSwK
ICAgICAgICAgICAgICAgICdwcm9kdWN0X2lkJyA9PiBkYXRhX2dldCgKICAgICAgICAgICAgICAg
ICAgICAkcHVibGljLAogICAgICAgICAgICAgICAgICAgICdpZCcKICAgICAgICAgICAgICAgICks
CiAgICAgICAgICAgICAgICAncHJvZHVjdF9jb2RlJyA9PiBkYXRhX2dldCgKICAgICAgICAgICAg
ICAgICAgICAkcHVibGljLAogICAgICAgICAgICAgICAgICAgICdjb2RlJwogICAgICAgICAgICAg
ICAgKSwKICAgICAgICAgICAgICAgICdkZWZhdWx0X2NvbG9yX2lkJyA9PiAkZGVmYXVsdENvbG9y
SWQsCiAgICAgICAgICAgICAgICAnZGVmYXVsdF9jbGFyaXR5X2NvdW50JyA9PiBjb3VudCgKICAg
ICAgICAgICAgICAgICAgICAoYXJyYXkpIGRhdGFfZ2V0KAogICAgICAgICAgICAgICAgICAgICAg
ICAkZGVmYXVsdFNldCwKICAgICAgICAgICAgICAgICAgICAgICAgJ2l0ZW1zJywKICAgICAgICAg
ICAgICAgICAgICAgICAgW10KICAgICAgICAgICAgICAgICAgICApCiAgICAgICAgICAgICAgICAp
LAogICAgICAgICAgICAgICAgJ3NldF9jb3VudCcgPT4gJHNldHMtPmNvdW50KCksCiAgICAgICAg
ICAgICAgICAnc2V0cycgPT4gJHNldHMtPm1hcCgKICAgICAgICAgICAgICAgICAgICBmbiAoJHNl
dCkgPT4gWwogICAgICAgICAgICAgICAgICAgICAgICAnY29sb3JfaWQnID0+IGRhdGFfZ2V0KAog
ICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNldCwKICAgICAgICAgICAgICAgICAgICAgICAg
ICAgICdjb2xvcl9pZCcKICAgICAgICAgICAgICAgICAgICAgICAgKSwKICAgICAgICAgICAgICAg
ICAgICAgICAgJ2xhYmVsJyA9PiBkYXRhX2dldCgKICAgICAgICAgICAgICAgICAgICAgICAgICAg
ICRzZXQsCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnbGFiZWwnCiAgICAgICAgICAgICAg
ICAgICAgICAgICksCiAgICAgICAgICAgICAgICAgICAgICAgICdzb3VyY2VfY291bnQnID0+IChp
bnQpIGRhdGFfZ2V0KAogICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNldCwKICAgICAgICAg
ICAgICAgICAgICAgICAgICAgICdzb3VyY2VfY291bnQnLAogICAgICAgICAgICAgICAgICAgICAg
ICAgICAgMAogICAgICAgICAgICAgICAgICAgICAgICApLAogICAgICAgICAgICAgICAgICAgICAg
ICAnY291bnQnID0+IGNvdW50KAogICAgICAgICAgICAgICAgICAgICAgICAgICAgKGFycmF5KSBk
YXRhX2dldCgKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2V0LAogICAgICAgICAg
ICAgICAgICAgICAgICAgICAgICAgICdpdGVtcycsCiAgICAgICAgICAgICAgICAgICAgICAgICAg
ICAgICAgW10KICAgICAgICAgICAgICAgICAgICAgICAgICAgICkKICAgICAgICAgICAgICAgICAg
ICAgICAgKSwKICAgICAgICAgICAgICAgICAgICAgICAgJ2FuZ2xlcycgPT4gY29sbGVjdCgKICAg
ICAgICAgICAgICAgICAgICAgICAgICAgIChhcnJheSkgZGF0YV9nZXQoCiAgICAgICAgICAgICAg
ICAgICAgICAgICAgICAgICAgJHNldCwKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAn
aXRlbXMnLAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIFtdCiAgICAgICAgICAgICAg
ICAgICAgICAgICAgICApCiAgICAgICAgICAgICAgICAgICAgICAgICkKICAgICAgICAgICAgICAg
ICAgICAgICAgICAgIC0+bWFwKGZuICgkaXRlbSkgPT4gWwogICAgICAgICAgICAgICAgICAgICAg
ICAgICAgICAgICdyb2xlJyA9PiBkYXRhX2dldCgKICAgICAgICAgICAgICAgICAgICAgICAgICAg
ICAgICAgICAgJGl0ZW0sCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdyb2xl
JwogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICksCiAgICAgICAgICAgICAgICAgICAg
ICAgICAgICAgICAgJ3Nob3RfYW5nbGUnID0+IGRhdGFfZ2V0KAogICAgICAgICAgICAgICAgICAg
ICAgICAgICAgICAgICAgICAkaXRlbSwKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg
ICAgJ3Nob3RfYW5nbGUnCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgKSwKICAgICAg
ICAgICAgICAgICAgICAgICAgICAgICAgICAnY2F0ZWdvcnlfY29kZScgPT4gZGF0YV9nZXQoCiAg
ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRpdGVtLAogICAgICAgICAgICAgICAg
ICAgICAgICAgICAgICAgICAgICAnY2F0ZWdvcnlfY29kZScKICAgICAgICAgICAgICAgICAgICAg
ICAgICAgICAgICApLAogICAgICAgICAgICAgICAgICAgICAgICAgICAgXSkKICAgICAgICAgICAg
ICAgICAgICAgICAgICAgIC0+dmFsdWVzKCkKICAgICAgICAgICAgICAgICAgICAgICAgICAgIC0+
YWxsKCksCiAgICAgICAgICAgICAgICAgICAgXQogICAgICAgICAgICAgICAgKS0+dmFsdWVzKCkt
PmFsbCgpLAogICAgICAgICAgICAgICAgJ2NoZWNrcycgPT4gJGNoZWNrcywKICAgICAgICAgICAg
XTsKCiAgICAgICAgICAgIGlmICgkdGhpcy0+b3B0aW9uKCdqc29uJykpIHsKICAgICAgICAgICAg
ICAgICR0aGlzLT5saW5lKGpzb25fZW5jb2RlKAogICAgICAgICAgICAgICAgICAgICRwYXlsb2Fk
LAogICAgICAgICAgICAgICAgICAgIEpTT05fUFJFVFRZX1BSSU5UCiAgICAgICAgICAgICAgICAg
ICAgfCBKU09OX1VORVNDQVBFRF9VTklDT0RFCiAgICAgICAgICAgICAgICAgICAgfCBKU09OX1VO
RVNDQVBFRF9TTEFTSEVTCiAgICAgICAgICAgICAgICApKTsKICAgICAgICAgICAgfSBlbHNlIHsK
ICAgICAgICAgICAgICAgIGZvcmVhY2ggKCRjaGVja3MgYXMgJGNvZGUgPT4gJHBhc3NlZCkgewog
ICAgICAgICAgICAgICAgICAgICR0aGlzLT5saW5lKAogICAgICAgICAgICAgICAgICAgICAgICBT
dHI6OnVwcGVyKCRjb2RlKQogICAgICAgICAgICAgICAgICAgICAgICAuICc9JwogICAgICAgICAg
ICAgICAgICAgICAgICAuICgkcGFzc2VkID8gJ1BBU1MnIDogJ0ZBSUwnKQogICAgICAgICAgICAg
ICAgICAgICk7CiAgICAgICAgICAgICAgICB9CgogICAgICAgICAgICAgICAgJHRoaXMtPmxpbmUo
CiAgICAgICAgICAgICAgICAgICAgJ1BEUF9QUk9EVUNUX0NMQVJJVFlfQVVESVQ9JwogICAgICAg
ICAgICAgICAgICAgIC4gKCRwYXlsb2FkWydvayddID8gJ1BBU1MnIDogJ0ZBSUwnKQogICAgICAg
ICAgICAgICAgKTsKICAgICAgICAgICAgfQoKICAgICAgICAgICAgcmV0dXJuICRwYXlsb2FkWydv
ayddCiAgICAgICAgICAgICAgICA/IHNlbGY6OlNVQ0NFU1MKICAgICAgICAgICAgICAgIDogc2Vs
Zjo6RkFJTFVSRTsKICAgICAgICB9IGNhdGNoIChUaHJvd2FibGUgJGUpIHsKICAgICAgICAgICAg
cmVwb3J0KCRlKTsKCiAgICAgICAgICAgICR0aGlzLT5lcnJvcigKICAgICAgICAgICAgICAgICdQ
RFBfUFJPRFVDVF9DTEFSSVRZX0FVRElUX0VSUk9SPScKICAgICAgICAgICAgICAgIC4gJGUtPmdl
dE1lc3NhZ2UoKQogICAgICAgICAgICApOwoKICAgICAgICAgICAgcmV0dXJuIHNlbGY6OkZBSUxV
UkU7CiAgICAgICAgfQogICAgfQoKICAgIHByb3RlY3RlZCBmdW5jdGlvbiByZXNvbHZlQ29udGV4
dCgKICAgICAgICBTdG9yZWZyb250U2NvcGVSZXNvbHZlciAkcmVzb2x2ZXIsCiAgICAgICAgc3Ry
aW5nICRzaXRlCiAgICApOiBvYmplY3QgewogICAgICAgIGZvcmVhY2ggKFsKICAgICAgICAgICAg
J3Jlc29sdmUnLAogICAgICAgICAgICAncmVzb2x2ZUJ5Q29kZScsCiAgICAgICAgICAgICdmb3JT
aXRlJywKICAgICAgICBdIGFzICRtZXRob2QpIHsKICAgICAgICAgICAgaWYgKG1ldGhvZF9leGlz
dHMoJHJlc29sdmVyLCAkbWV0aG9kKSkgewogICAgICAgICAgICAgICAgcmV0dXJuICRyZXNvbHZl
ci0+eyRtZXRob2R9KCRzaXRlKTsKICAgICAgICAgICAgfQogICAgICAgIH0KCiAgICAgICAgdGhy
b3cgbmV3IFxSdW50aW1lRXhjZXB0aW9uKAogICAgICAgICAgICAnU3RvcmVmcm9udFNjb3BlUmVz
b2x2ZXIgbWV0aG9kIGlzIHVuYXZhaWxhYmxlLicKICAgICAgICApOwogICAgfQp9Cg==
ERP_CLARITY_AUDIT_B64

    php -l "$SERVICE"
    php -l "$COMMAND"

    grep -Fq -- \
      'AI_PATCH_LINXEN_PDP_PRODUCT_CLARITY_MEDIA_V1' \
      "$SERVICE"

    grep -Fq -- \
      "'clarity_sets_by_color' => \$claritySets->all()" \
      "$SERVICE"

    grep -Fq -- \
      "'OPENING_PRODUCT_CLARITY_SINGLE'" \
      "$SERVICE"

    grep -Fq -- \
      'commerce:audit-pdp-product-clarity' \
      "$COMMAND"

    artisan_safe optimize:clear

    artisan_safe list --raw \
      | grep -Fq -- \
        'commerce:audit-pdp-product-clarity'

    trap - ERR

    printf '%s\n' \
      'LINXEN_PDP_STUDIO_CLARITY_ERP_SOURCE_PATCH=PASS'
    printf '%s\n' \
      'PRODUCT_CLARITY_CATEGORY=OPENING_PRODUCT_CLARITY_SINGLE'
    printf '%s\n' \
      'PRODUCT_CLARITY_MAX_PER_COLOR=8'
    printf '%s\n' \
      'EXACT_COLOR_ONLY=YES'
    printf '%s\n' \
      'CROSS_COLOR_FALLBACK=BLOCKED'
    printf '%s\n' \
      'MIGRATION=NONE'
    printf '%s\n' \
      'DB_MUTATION=NONE'
    printf '%s\n' \
      'PROVIDER_CALL=NONE'
    printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"

    exit 0
fi

if [ "$TARGET" = 'storefront' ]; then
    PRESENTER='app/Services/CommerceV2/CommerceV2Presenter.php'
    VIEW_MODEL='app/Services/CommerceV2/Pdp/PdpViewModelBuilder.php'
    REGISTRY='app/Services/CommerceV2/Pdp/PdpVariantRegistry.php'
    SECTIONS='app/Services/CommerceV2/Pdp/PdpSectionRegistry.php'
    HERO='resources/views/commerce_v2/pdp/clarity/hero-purchase.blade.php'
    ANGLES='resources/views/commerce_v2/pdp/clarity/product-angles.blade.php'
    CSS='public/commerce-v2/pdp/v1/variants/studio-clarity-v1.css'
    JS='public/commerce-v2/pdp/v1/variants/studio-clarity-v1.js'

    for FILE in \
      "$PRESENTER" \
      "$VIEW_MODEL" \
      "$REGISTRY" \
      "$SECTIONS" \
      resources/views/commerce_v2/pdp/page.blade.php \
      public/commerce-v2/pdp/v1/core.js \
      public/commerce-v2/pdp-sales-experience.js \
      public/commerce-v2/pdp-sales-experience.css
    do
        test -f "$FILE" || {
            printf 'ERROR: Missing Storefront dependency: %s\n' "$FILE" >&2
            exit 1
        }
    done

    for FILE in \
      "$PRESENTER" \
      "$VIEW_MODEL" \
      "$REGISTRY" \
      "$SECTIONS" \
      "$HERO" \
      "$ANGLES" \
      "$CSS" \
      "$JS"
    do
        backup_file "$FILE"
    done

    PATCH_WRITTEN=1

    export PDP_CLARITY_PRESENTER="$PRESENTER"
    export PDP_CLARITY_VIEW_MODEL="$VIEW_MODEL"
    export PDP_CLARITY_VARIANT_REGISTRY="$REGISTRY"
    export PDP_CLARITY_SECTION_REGISTRY="$SECTIONS"

    php <<'PHP'
<?php

$presenterPath = getenv('PDP_CLARITY_PRESENTER');
$builderPath = getenv('PDP_CLARITY_VIEW_MODEL');
$registryPath = getenv('PDP_CLARITY_VARIANT_REGISTRY');
$sectionsPath = getenv('PDP_CLARITY_SECTION_REGISTRY');

$presenter = file_get_contents($presenterPath);
$builder = file_get_contents($builderPath);
$registry = file_get_contents($registryPath);
$sections = file_get_contents($sectionsPath);

if (
    ! is_string($presenter)
    || ! is_string($builder)
    || ! is_string($registry)
    || ! is_string($sections)
) {
    fwrite(STDERR, "ERROR: Không đọc được Storefront PDP source.\n");
    exit(1);
}

$presenterMarker = 'AI_PATCH_LINXEN_PDP_CLARITY_MEDIA_PRESENTER_V1';
$builderMarker = 'AI_PATCH_LINXEN_PDP_CLARITY_VIEW_MODEL_V1';

if (! str_contains($presenter, $presenterMarker)) {
    $setsAnchor = <<<'ANCHOR'
        $sets = collect((array) data_get(
            $pdp,
            'media_sets_by_color',
            []
        ))->keyBy(
            fn ($set) => (string) data_get(
                $set,
                'color_id'
            )
        );
ANCHOR;

    if (substr_count($presenter, $setsAnchor) !== 1) {
        fwrite(STDERR, "ERROR: Presenter media sets anchor drift.\n");
        exit(1);
    }

    $claritySets = <<<'BLOCK'
        /* AI_PATCH_LINXEN_PDP_CLARITY_MEDIA_PRESENTER_V1 */
        $claritySets = collect((array) data_get(
            $pdp,
            'clarity_sets_by_color',
            []
        ))->keyBy(
            fn ($set) => (string) data_get(
                $set,
                'color_id'
            )
        );
BLOCK;

    $presenter = str_replace(
        $setsAnchor,
        $setsAnchor . $claritySets,
        $presenter,
        $count
    );

    if ($count !== 1) {
        fwrite(STDERR, "ERROR: Không chèn được Presenter clarity sets.\n");
        exit(1);
    }

    $useAnchor = <<<'ANCHOR'
            ->map(function ($color) use (
                $sets,
                $fallbackMedia
            ) {
ANCHOR;

    if (substr_count($presenter, $useAnchor) !== 1) {
        fwrite(STDERR, "ERROR: Presenter color closure anchor drift.\n");
        exit(1);
    }

    $presenter = str_replace(
        $useAnchor,
        <<<'BLOCK'
            ->map(function ($color) use (
                $sets,
                $claritySets,
                $fallbackMedia
            ) {
BLOCK,
        $presenter,
        $count
    );

    $setAnchor = <<<'ANCHOR'
                $colorId = (string) data_get($color, 'id');
                $set = (array) $sets->get($colorId, []);
ANCHOR;

    if (substr_count($presenter, $setAnchor) !== 1) {
        fwrite(STDERR, "ERROR: Presenter color set anchor drift.\n");
        exit(1);
    }

    $clarityMedia = <<<'BLOCK'
                $claritySet = (array) $claritySets->get(
                    $colorId,
                    []
                );
                $clarityMedia = collect((array) data_get(
                    $claritySet,
                    'items',
                    []
                ))
                    ->map(fn ($item) => $this->presentMedia(
                        $item
                    ))
                    ->filter(fn ($item) => $item['url'] !== '')
                    ->take(8)
                    ->values()
                    ->all();
BLOCK;

    $presenter = str_replace(
        $setAnchor,
        $setAnchor . $clarityMedia,
        $presenter,
        $count
    );

    $mediaAnchor = "                    'media' => \$media,\n";

    if (substr_count($presenter, $mediaAnchor) !== 1) {
        fwrite(STDERR, "ERROR: Presenter media return anchor drift.\n");
        exit(1);
    }

    $presenter = str_replace(
        $mediaAnchor,
        $mediaAnchor
            . "                    'clarity_media' => \$clarityMedia,\n"
            . "                    'clarity_media_count' => count(\n"
            . "                        \$clarityMedia\n"
            . "                    ),\n"
            . "                    'clarity_media_source_count' => (int) data_get(\n"
            . "                        \$claritySet,\n"
            . "                        'source_count',\n"
            . "                        count(\$clarityMedia)\n"
            . "                    ),\n"
            . "                    'clarity_media_exact_color' => (bool) data_get(\n"
            . "                        \$claritySet,\n"
            . "                        'exact_color_only',\n"
            . "                        true\n"
            . "                    ),\n",
        $presenter,
        $count
    );
}

if (! str_contains($builder, $builderMarker)) {
    $anchor = "                'production_truth' => \$productionTruth,\n";

    if (substr_count($builder, $anchor) !== 1) {
        fwrite(STDERR, "ERROR: PDP view-model media anchor drift.\n");
        exit(1);
    }

    $addition = <<<'BLOCK'
                /* AI_PATCH_LINXEN_PDP_CLARITY_VIEW_MODEL_V1 */
                'product_clarity_by_color' => $colors
                    ->map(fn ($color) => [
                        'color_id' => (string) data_get(
                            $color,
                            'id'
                        ),
                        'color_code' => (string) data_get(
                            $color,
                            'code'
                        ),
                        'color_label' => (string) data_get(
                            $color,
                            'label'
                        ),
                        'items' => array_values((array) data_get(
                            $color,
                            'clarity_media',
                            []
                        )),
                    ])
                    ->values()
                    ->all(),
BLOCK;

    $builder = str_replace(
        $anchor,
        $addition . $anchor,
        $builder,
        $count
    );
}

if (! str_contains($registry, "'studio_clarity_v1' => [")) {
    $entry = <<<'ENTRY'
            /* AI_PATCH_LINXEN_PDP_STUDIO_CLARITY_V1 */
            'studio_clarity_v1' => [
                'key' => 'studio_clarity_v1',
                'label' => 'Studio Clarity V1',
                'version' => '1.0.0',
                'renderer' => 'sectioned',
                'view' => 'commerce_v2.pdp.page',
                'layout' => 'studio_clarity_v1',
                'view_model_version' => PdpViewModelBuilder::VERSION,
                'sections' => [
                    'clarity_hero_purchase',
                    'clarity_product_angles',
                ],
                'assets' => [
                    'styles' => [
                        'commerce-v2/pdp-sales-experience.css?v=3',
                        'commerce-v2/pdp/v1/core.css?v=1',
                        'commerce-v2/pdp/v1/variants/studio-clarity-v1.css?v=1',
                    ],
                    'scripts' => [
                        'commerce-v2/pdp/v1/variants/studio-clarity-v1.js?v=1',
                    ],
                ],
                'art_direction' => [
                    'concept' => 'product_clarity_commerce',
                    'journey' => 'gallery_purchase_then_exact_angle_story',
                    'mobile_navigation' => 'fixed_graphite_commerce_dock',
                    'hidden_sections' => 'all_non_product_clarity_sections',
                ],
                'enabled' => true,
            ],
ENTRY;

    $anchor = "\n        ];\n    }\n\n    public function get";

    if (substr_count($registry, $anchor) !== 1) {
        fwrite(STDERR, "ERROR: PDP variant registry anchor drift.\n");
        exit(1);
    }

    $registry = str_replace(
        $anchor,
        "\n" . rtrim($entry) . $anchor,
        $registry,
        $count
    );
}

if (! str_contains($sections, "'clarity_hero_purchase' => [")) {
    $entry = <<<'ENTRY'
            /* AI_PATCH_LINXEN_PDP_STUDIO_CLARITY_SECTIONS_V1 */
            'clarity_hero_purchase' => [
                'view' => 'commerce_v2.pdp.clarity.hero-purchase',
                'required' => ['identity.id', 'commerce.colors'],
                'empty_behavior' => 'render',
            ],
            'clarity_product_angles' => [
                'view' => 'commerce_v2.pdp.clarity.product-angles',
                'required' => ['identity.id'],
                'empty_behavior' => 'render',
            ],
ENTRY;

    $anchor = "\n        ];\n    }\n\n    public function compose";

    if (substr_count($sections, $anchor) !== 1) {
        fwrite(STDERR, "ERROR: PDP section registry anchor drift.\n");
        exit(1);
    }

    $sections = str_replace(
        $anchor,
        "\n" . rtrim($entry) . $anchor,
        $sections,
        $count
    );
}

foreach ([
    $presenterMarker,
    "'clarity_media' => \$clarityMedia",
    $builderMarker,
    "'product_clarity_by_color'",
] as $required) {
    if (
        ! str_contains($presenter . $builder, $required)
    ) {
        fwrite(STDERR, "ERROR: Thiếu Storefront clarity contract: {$required}\n");
        exit(1);
    }
}

foreach ([
    "'studio_clarity_v1' => [",
    "'clarity_hero_purchase'",
    "'clarity_product_angles'",
    'studio-clarity-v1.css?v=1',
    'studio-clarity-v1.js?v=1',
] as $required) {
    if (! str_contains($registry, $required)) {
        fwrite(STDERR, "ERROR: Thiếu Studio Clarity variant contract: {$required}\n");
        exit(1);
    }
}

foreach ([
    "'clarity_hero_purchase' => [",
    "'clarity_product_angles' => [",
] as $required) {
    if (! str_contains($sections, $required)) {
        fwrite(STDERR, "ERROR: Thiếu Studio Clarity section contract: {$required}\n");
        exit(1);
    }
}

foreach ([
    $presenterPath => $presenter,
    $builderPath => $builder,
    $registryPath => $registry,
    $sectionsPath => $sections,
] as $path => $source) {
    $written = file_put_contents($path, $source);

    if ($written === false || $written !== strlen($source)) {
        fwrite(STDERR, "ERROR: Không ghi đầy đủ source: {$path}\n");
        exit(1);
    }
}

echo "STOREFRONT_PDP_STUDIO_CLARITY_SOURCE=APPLIED\n";

PHP

    decode_to_file "$HERO" <<'SF_CLARITY_HERO_B64'
QHBocAogICAgJGlkZW50aXR5ID0gKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnaWRlbnRpdHknLCBb
XSk7CiAgICAkY29tbWVyY2UgPSAoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdjb21tZXJjZScsIFtd
KTsKICAgICRhbGxDb2xvcnMgPSBjb2xsZWN0KChhcnJheSkgZGF0YV9nZXQoJGNvbW1lcmNlLCAn
Y29sb3JzJywgW10pKS0+dmFsdWVzKCk7CiAgICAkYXZhaWxhYmxlQ29sb3JzID0gJGFsbENvbG9y
cwogICAgICAgIC0+ZmlsdGVyKGZuICgkY29sb3IpID0+IChib29sKSBkYXRhX2dldCgkY29sb3Is
ICdzZWxsYWJsZScpICYmIChmbG9hdCkgZGF0YV9nZXQoJGNvbG9yLCAnYXZhaWxhYmxlJywgMCkg
PiAwKQogICAgICAgIC0+dmFsdWVzKCk7CiAgICAkZGVmYXVsdENvbG9yID0gKGFycmF5KSBkYXRh
X2dldCgkY29tbWVyY2UsICdkZWZhdWx0X2NvbG9yJywgW10pOwogICAgaWYgKCEgZGF0YV9nZXQo
JGRlZmF1bHRDb2xvciwgJ3NlbGxhYmxlJykgfHwgKGZsb2F0KSBkYXRhX2dldCgkZGVmYXVsdENv
bG9yLCAnYXZhaWxhYmxlJywgMCkgPD0gMCkgewogICAgICAgICRkZWZhdWx0Q29sb3IgPSAoYXJy
YXkpICgkYXZhaWxhYmxlQ29sb3JzLT5maXJzdCgpID86ICRkZWZhdWx0Q29sb3IpOwogICAgfQog
ICAgJGRlZmF1bHRNZWRpYSA9IGNvbGxlY3QoKGFycmF5KSBkYXRhX2dldCgkZGVmYXVsdENvbG9y
LCAnbWVkaWEnLCBbXSkpLT50YWtlKDYpLT52YWx1ZXMoKTsKICAgICRoZXJvTWVkaWEgPSAoYXJy
YXkpICgkZGVmYXVsdE1lZGlhLT5maXJzdCgpID86IFtdKTsKICAgICRhZHZpc29yID0gKGFycmF5
KSBkYXRhX2dldCgkcGRwLCAnZml0LmFkdmlzb3InLCBbXSk7CiAgICAkc2hvcnROYW1lID0gdHJp
bSgoc3RyaW5nKSAoZGF0YV9nZXQoJGlkZW50aXR5LCAnc2hvcnRfbmFtZScpID86IGRhdGFfZ2V0
KCRpZGVudGl0eSwgJ25hbWUnKSkpOwogICAgJGZ1bGxOYW1lID0gdHJpbSgoc3RyaW5nKSBkYXRh
X2dldCgkaWRlbnRpdHksICduYW1lJykpOwogICAgJGRlc2NyaXB0b3IgPSB0cmltKChzdHJpbmcp
IHByZWdfcmVwbGFjZSgKICAgICAgICAnL14nLnByZWdfcXVvdGUoJHNob3J0TmFtZSwgJy8nKS4n
XHMqW+KAk+KAlFwtOl0/XHMqL3UnLAogICAgICAgICcnLAogICAgICAgICRmdWxsTmFtZQogICAg
KSk7CiAgICAkZGVzY3JpcHRpb24gPSBcSWxsdW1pbmF0ZVxTdXBwb3J0XFN0cjo6bGltaXQoCiAg
ICAgICAgXElsbHVtaW5hdGVcU3VwcG9ydFxTdHI6OnNxdWlzaCgoc3RyaW5nKSBkYXRhX2dldCgk
aWRlbnRpdHksICdkZXNjcmlwdGlvbicpKSwKICAgICAgICAxODAsCiAgICAgICAgJ+KApicKICAg
ICk7CiAgICAkcmVxdWVzdGVkQ29sb3IgPSBcSWxsdW1pbmF0ZVxTdXBwb3J0XFN0cjo6bG93ZXIo
dHJpbSgoc3RyaW5nKSByZXF1ZXN0KCdjb2xvcicsICcnKSkpOwogICAgJHJlcXVlc3RlZFVuYXZh
aWxhYmxlID0gJHJlcXVlc3RlZENvbG9yICE9PSAnJwogICAgICAgID8gJGFsbENvbG9ycy0+Zmly
c3QoZnVuY3Rpb24gKCRjb2xvcikgdXNlICgkcmVxdWVzdGVkQ29sb3IpIHsKICAgICAgICAgICAg
JGtleXMgPSBjb2xsZWN0KFsKICAgICAgICAgICAgICAgIGRhdGFfZ2V0KCRjb2xvciwgJ2lkJyks
CiAgICAgICAgICAgICAgICBkYXRhX2dldCgkY29sb3IsICdjb2RlJyksCiAgICAgICAgICAgICAg
ICBkYXRhX2dldCgkY29sb3IsICdrZXknKSwKICAgICAgICAgICAgXSktPm1hcChmbiAoJHZhbHVl
KSA9PiBcSWxsdW1pbmF0ZVxTdXBwb3J0XFN0cjo6bG93ZXIodHJpbSgoc3RyaW5nKSAkdmFsdWUp
KSk7CgogICAgICAgICAgICByZXR1cm4gJGtleXMtPmNvbnRhaW5zKCRyZXF1ZXN0ZWRDb2xvcikK
ICAgICAgICAgICAgICAgICYmICghIGRhdGFfZ2V0KCRjb2xvciwgJ3NlbGxhYmxlJykgfHwgKGZs
b2F0KSBkYXRhX2dldCgkY29sb3IsICdhdmFpbGFibGUnLCAwKSA8PSAwKTsKICAgICAgICB9KQog
ICAgICAgIDogbnVsbDsKQGVuZHBocAoKPGRpdiBjbGFzcz0ibHhjLXNoZWxsIGx4Yy1oZXJvIiBk
YXRhLWx4Yy1yZXZlYWw+CiAgICA8ZGl2IGNsYXNzPSJseGMtaGVyb19fZ2FsbGVyeS1jb2x1bW4i
PgogICAgICAgIDxkaXYgY2xhc3M9Imx4Yy1oZXJvX190b3BsaW5lIiBhcmlhLWhpZGRlbj0idHJ1
ZSI+CiAgICAgICAgICAgIDxzcGFuPkxJTiBYw4lOIC8gUFJPRFVDVCBGT0NVUzwvc3Bhbj4KICAg
ICAgICAgICAgPHNwYW4+e3sgbm93KCktPmZvcm1hdCgnWScpIH19PC9zcGFuPgogICAgICAgIDwv
ZGl2PgoKICAgICAgICA8ZGl2IGNsYXNzPSJseHBkcC1nYWxsZXJ5IGx4Yy1nYWxsZXJ5IiBkYXRh
LWx4cGRwLWdhbGxlcnkgYXJpYS1sYWJlbD0iSMOsbmgg4bqjbmggc+G6o24gcGjhuqltIj4KICAg
ICAgICAgICAgPGRpdiBjbGFzcz0ibHhwZHAtZ2FsbGVyeV9fc3RhZ2UgbHhjLWdhbGxlcnlfX3N0
YWdlIj4KICAgICAgICAgICAgICAgIDxidXR0b24KICAgICAgICAgICAgICAgICAgICB0eXBlPSJi
dXR0b24iCiAgICAgICAgICAgICAgICAgICAgY2xhc3M9Imx4cGRwLWdhbGxlcnlfX25hdiBseHBk
cC1nYWxsZXJ5X19uYXYtLXByZXYgbHhjLWdhbGxlcnlfX25hdiBseGMtZ2FsbGVyeV9fbmF2LS1w
cmV2IgogICAgICAgICAgICAgICAgICAgIGRhdGEtbHhwZHAtZ2FsbGVyeS1wcmV2CiAgICAgICAg
ICAgICAgICAgICAgYXJpYS1sYWJlbD0i4bqibmggdHLGsOG7m2MiCiAgICAgICAgICAgICAgICA+
CiAgICAgICAgICAgICAgICAgICAgPHN2ZyB2aWV3Qm94PSIwIDAgMjQgMjQiIGFyaWEtaGlkZGVu
PSJ0cnVlIj48cGF0aCBkPSJtMTUgMTgtNi02IDYtNiIvPjwvc3ZnPgogICAgICAgICAgICAgICAg
PC9idXR0b24+CgogICAgICAgICAgICAgICAgPGZpZ3VyZSBjbGFzcz0ibHhwZHAtZ2FsbGVyeV9f
ZmlndXJlIGx4Yy1nYWxsZXJ5X19maWd1cmUiPgogICAgICAgICAgICAgICAgICAgIDxpbWcKICAg
ICAgICAgICAgICAgICAgICAgICAgZGF0YS1seHBkcC1tYWluLWltYWdlCiAgICAgICAgICAgICAg
ICAgICAgICAgIHNyYz0ie3sgZGF0YV9nZXQoJGhlcm9NZWRpYSwgJ3VybCcsIGRhdGFfZ2V0KCRw
ZHAsICdtZWRpYS5jb3Zlcl91cmwnKSkgfX0iCiAgICAgICAgICAgICAgICAgICAgICAgIGFsdD0i
e3sgJGZ1bGxOYW1lIH19IC0ge3sgZGF0YV9nZXQoJGRlZmF1bHRDb2xvciwgJ2xhYmVsJykgfX0i
CiAgICAgICAgICAgICAgICAgICAgICAgIHdpZHRoPSIxMTIwIgogICAgICAgICAgICAgICAgICAg
ICAgICBoZWlnaHQ9IjE0MDAiCiAgICAgICAgICAgICAgICAgICAgICAgIGZldGNocHJpb3JpdHk9
ImhpZ2giCiAgICAgICAgICAgICAgICAgICAgICAgIGRlY29kaW5nPSJhc3luYyIKICAgICAgICAg
ICAgICAgICAgICA+CiAgICAgICAgICAgICAgICAgICAgPGZpZ2NhcHRpb24gY2xhc3M9Imx4Yy1n
YWxsZXJ5X19tZXRhIj4KICAgICAgICAgICAgICAgICAgICAgICAgPHNwYW4gZGF0YS1seHBkcC1p
bWFnZS1yb2xlPnt7IGRhdGFfZ2V0KCRoZXJvTWVkaWEsICdyb2xlJykgPT09ICdoZXJvJyA/ICdU
4buVbmcgdGjhu4MnIDogJ0jDrG5oIOG6o25oIHPhuqNuIHBo4bqpbScgfX08L3NwYW4+CiAgICAg
ICAgICAgICAgICAgICAgICAgIDxzcGFuIGRhdGEtbHhwZHAtaW1hZ2UtY291bnRlcj57eyAkZGVm
YXVsdE1lZGlhLT5pc05vdEVtcHR5KCkgPyAnMDEgLyAnLnN0cl9wYWQoKHN0cmluZykgJGRlZmF1
bHRNZWRpYS0+Y291bnQoKSwgMiwgJzAnLCBTVFJfUEFEX0xFRlQpIDogJycgfX08L3NwYW4+CiAg
ICAgICAgICAgICAgICAgICAgPC9maWdjYXB0aW9uPgogICAgICAgICAgICAgICAgPC9maWd1cmU+
CgogICAgICAgICAgICAgICAgPGJ1dHRvbgogICAgICAgICAgICAgICAgICAgIHR5cGU9ImJ1dHRv
biIKICAgICAgICAgICAgICAgICAgICBjbGFzcz0ibHhwZHAtZ2FsbGVyeV9fbmF2IGx4cGRwLWdh
bGxlcnlfX25hdi0tbmV4dCBseGMtZ2FsbGVyeV9fbmF2IGx4Yy1nYWxsZXJ5X19uYXYtLW5leHQi
CiAgICAgICAgICAgICAgICAgICAgZGF0YS1seHBkcC1nYWxsZXJ5LW5leHQKICAgICAgICAgICAg
ICAgICAgICBhcmlhLWxhYmVsPSLhuqJuaCB0aeG6v3AgdGhlbyIKICAgICAgICAgICAgICAgID4K
ICAgICAgICAgICAgICAgICAgICA8c3ZnIHZpZXdCb3g9IjAgMCAyNCAyNCIgYXJpYS1oaWRkZW49
InRydWUiPjxwYXRoIGQ9Im05IDE4IDYtNi02LTYiLz48L3N2Zz4KICAgICAgICAgICAgICAgIDwv
YnV0dG9uPgogICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4cGRw
LWdhbGxlcnlfX3RodW1icyBseGMtZ2FsbGVyeV9fdGh1bWJzIiBkYXRhLWx4cGRwLXRodW1icyBy
b2xlPSJsaXN0IiBhcmlhLWxhYmVsPSJDaOG7jW4g4bqjbmggc+G6o24gcGjhuqltIj4KICAgICAg
ICAgICAgICAgIEBmb3JlYWNoKCRkZWZhdWx0TWVkaWEgYXMgJGluZGV4ID0+ICRtZWRpYSkKICAg
ICAgICAgICAgICAgICAgICA8YnV0dG9uCiAgICAgICAgICAgICAgICAgICAgICAgIHR5cGU9ImJ1
dHRvbiIKICAgICAgICAgICAgICAgICAgICAgICAgY2xhc3M9Imx4cGRwLWdhbGxlcnlfX3RodW1i
IGx4Yy1nYWxsZXJ5X190aHVtYiB7eyAkaW5kZXggPT09IDAgPyAnaXMtYWN0aXZlJyA6ICcnIH19
IgogICAgICAgICAgICAgICAgICAgICAgICBkYXRhLWx4cGRwLXRodW1iCiAgICAgICAgICAgICAg
ICAgICAgICAgIGRhdGEtaW5kZXg9Int7ICRpbmRleCB9fSIKICAgICAgICAgICAgICAgICAgICAg
ICAgYXJpYS1sYWJlbD0iWGVtIOG6o25oIHt7ICRpbmRleCArIDEgfX0iCiAgICAgICAgICAgICAg
ICAgICAgICAgIGFyaWEtY3VycmVudD0ie3sgJGluZGV4ID09PSAwID8gJ3RydWUnIDogJ2ZhbHNl
JyB9fSIKICAgICAgICAgICAgICAgICAgICA+CiAgICAgICAgICAgICAgICAgICAgICAgIDxpbWcK
ICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNyYz0ie3sgZGF0YV9nZXQoJG1lZGlhLCAndGh1
bWJfdXJsJywgZGF0YV9nZXQoJG1lZGlhLCAndXJsJykpIH19IgogICAgICAgICAgICAgICAgICAg
ICAgICAgICAgYWx0PSIiCiAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aWR0aD0iOTYiCiAg
ICAgICAgICAgICAgICAgICAgICAgICAgICBoZWlnaHQ9IjEyMCIKICAgICAgICAgICAgICAgICAg
ICAgICAgICAgIGxvYWRpbmc9ImxhenkiCiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkZWNv
ZGluZz0iYXN5bmMiCiAgICAgICAgICAgICAgICAgICAgICAgID4KICAgICAgICAgICAgICAgICAg
ICA8L2J1dHRvbj4KICAgICAgICAgICAgICAgIEBlbmRmb3JlYWNoCiAgICAgICAgICAgIDwvZGl2
PgoKICAgICAgICAgICAgPHAgY2xhc3M9Imx4cGRwLWdhbGxlcnlfX25vdGljZSBseGMtZ2FsbGVy
eV9fbm90aWNlIiBkYXRhLWx4cGRwLWdhbGxlcnktbm90aWNlIEBpZigkZGVmYXVsdE1lZGlhLT5p
c05vdEVtcHR5KCkpIGhpZGRlbiBAZW5kaWY+CiAgICAgICAgICAgICAgICBNw6B1IG7DoHkgxJFh
bmcgY2jhu50gYuG7mSDhuqNuaCDEkcaw4bujYyBkdXnhu4d0LiBMSU4gWMOJTiBraMO0bmcgZMO5
bmcg4bqjbmggY+G7p2EgbcOgdSBraMOhYyDEkeG7gyBtaW5oIGjhu41hLgogICAgICAgICAgICA8
L3A+CiAgICAgICAgPC9kaXY+CiAgICA8L2Rpdj4KCiAgICA8YXNpZGUgY2xhc3M9Imx4cGRwLWJ1
eS1wYW5lbCBseGMtYnV5IiBhcmlhLWxhYmVsPSJUaMO0bmcgdGluIG11YSBow6BuZyIgZGF0YS1s
eGMtcHVyY2hhc2U+CiAgICAgICAgPGRpdiBjbGFzcz0ibHhjLWJ1eV9faGVhZCI+CiAgICAgICAg
ICAgIDxwIGNsYXNzPSJseGMta2lja2VyIj5UaGnhur90IGvhur8gbeG7m2kgwrcgUmVhZHkgdG8g
d2VhcjwvcD4KICAgICAgICAgICAgPGgxPnt7ICRzaG9ydE5hbWUgfX08L2gxPgogICAgICAgICAg
ICBAaWYoJGRlc2NyaXB0b3IgIT09ICcnKQogICAgICAgICAgICAgICAgPHAgY2xhc3M9Imx4Yy1i
dXlfX2Rlc2NyaXB0b3IiPnt7ICRkZXNjcmlwdG9yIH19PC9wPgogICAgICAgICAgICBAZW5kaWYK
ICAgICAgICAgICAgQGlmKCRkZXNjcmlwdGlvbiAhPT0gJycpCiAgICAgICAgICAgICAgICA8cCBj
bGFzcz0ibHhjLWJ1eV9fZGVzY3JpcHRpb24iPnt7ICRkZXNjcmlwdGlvbiB9fTwvcD4KICAgICAg
ICAgICAgQGVuZGlmCiAgICAgICAgPC9kaXY+CgogICAgICAgIDxkaXYgY2xhc3M9Imx4Yy1wcmlj
ZS1saW5lIj4KICAgICAgICAgICAgPGRpdiBjbGFzcz0ibHhwZHBfX3ByaWNlIGx4Yy1wcmljZSIg
ZGF0YS1seHBkcC1wcmljZT4KICAgICAgICAgICAgICAgIDxzdHJvbmc+e3sgbnVtYmVyX2Zvcm1h
dCgoZmxvYXQpIGRhdGFfZ2V0KCRjb21tZXJjZSwgJ3ByaWNlLm1pbicpLCAwLCAnLCcsICcuJykg
fX3igqs8L3N0cm9uZz4KICAgICAgICAgICAgICAgIEBpZihkYXRhX2dldCgkY29tbWVyY2UsICdw
cmljZS5oYXNfc2FsZScpICYmIGRhdGFfZ2V0KCRjb21tZXJjZSwgJ3ByaWNlLm9yaWdpbmFsX21p
bicpID4gZGF0YV9nZXQoJGNvbW1lcmNlLCAncHJpY2UubWluJykpCiAgICAgICAgICAgICAgICAg
ICAgPGRlbD57eyBudW1iZXJfZm9ybWF0KChmbG9hdCkgZGF0YV9nZXQoJGNvbW1lcmNlLCAncHJp
Y2Uub3JpZ2luYWxfbWluJyksIDAsICcsJywgJy4nKSB9feKCqzwvZGVsPgogICAgICAgICAgICAg
ICAgQGVuZGlmCiAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8c3BhbiBjbGFzcz0ibHhj
LXN0b2NrIHt7IGRhdGFfZ2V0KCRjb21tZXJjZSwgJ2F2YWlsYWJpbGl0eS5pbl9zdG9jaycpID8g
J2lzLWluJyA6ICdpcy1vdXQnIH19Ij4KICAgICAgICAgICAgICAgIDxpIGFyaWEtaGlkZGVuPSJ0
cnVlIj48L2k+CiAgICAgICAgICAgICAgICB7eyBkYXRhX2dldCgkY29tbWVyY2UsICdhdmFpbGFi
aWxpdHkuaW5fc3RvY2snKSA/ICdT4bq1biBzw6BuZyBnaWFvJyA6ICdU4bqhbSBo4bq/dCBow6Bu
ZycgfX0KICAgICAgICAgICAgPC9zcGFuPgogICAgICAgIDwvZGl2PgoKICAgICAgICA8c2VjdGlv
biBjbGFzcz0ibHhwZHAtc2VsZWN0b3IgbHhjLXNlbGVjdG9yIiBhcmlhLWxhYmVsbGVkYnk9Imx4
c0NvbG9yVGl0bGUiPgogICAgICAgICAgICA8ZGl2IGNsYXNzPSJseGMtc2VsZWN0b3JfX2hlYWQi
PgogICAgICAgICAgICAgICAgPGgyIGlkPSJseHNDb2xvclRpdGxlIj5Nw6B1IHPhuq9jPC9oMj4K
ICAgICAgICAgICAgICAgIDxzcGFuIGRhdGEtbHhwZHAtY29sb3ItbGFiZWw+e3sgZGF0YV9nZXQo
JGRlZmF1bHRDb2xvciwgJ2xhYmVsJywgJ0No4buNbiBtw6B1JykgfX08L3NwYW4+CiAgICAgICAg
ICAgIDwvZGl2PgoKICAgICAgICAgICAgQGlmKCRhdmFpbGFibGVDb2xvcnMtPmlzTm90RW1wdHko
KSkKICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4Yy1jb2xvci1saXN0IiByb2xlPSJsaXN0
Ij4KICAgICAgICAgICAgICAgICAgICBAZm9yZWFjaCgkYXZhaWxhYmxlQ29sb3JzIGFzICRjb2xv
cikKICAgICAgICAgICAgICAgICAgICAgICAgQHBocAogICAgICAgICAgICAgICAgICAgICAgICAg
ICAgJGNvdmVyID0gZGF0YV9nZXQoJGNvbG9yLCAnbWVkaWEuMC50aHVtYl91cmwnKQogICAgICAg
ICAgICAgICAgICAgICAgICAgICAgICAgID86IGRhdGFfZ2V0KCRjb2xvciwgJ21lZGlhLjAudXJs
JykKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA/OiBkYXRhX2dldCgkY29sb3IsICdj
b3Zlcl91cmwnKTsKICAgICAgICAgICAgICAgICAgICAgICAgICAgICRhY3RpdmUgPSAoc3RyaW5n
KSBkYXRhX2dldCgkY29sb3IsICdpZCcpID09PSAoc3RyaW5nKSBkYXRhX2dldCgkZGVmYXVsdENv
bG9yLCAnaWQnKTsKICAgICAgICAgICAgICAgICAgICAgICAgQGVuZHBocAogICAgICAgICAgICAg
ICAgICAgICAgICA8YnV0dG9uCiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0eXBlPSJidXR0
b24iCiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjbGFzcz0ibHhwZHAtY29sb3ItY2FyZCBs
eGMtY29sb3Ige3sgJGFjdGl2ZSA/ICdpcy1hY3RpdmUnIDogJycgfX0iCiAgICAgICAgICAgICAg
ICAgICAgICAgICAgICBkYXRhLWx4cGRwLWNvbG9yCiAgICAgICAgICAgICAgICAgICAgICAgICAg
ICBkYXRhLWNvbG9yLWlkPSJ7eyBkYXRhX2dldCgkY29sb3IsICdpZCcpIH19IgogICAgICAgICAg
ICAgICAgICAgICAgICAgICAgZGF0YS1jb2xvci1jb2RlPSJ7eyBkYXRhX2dldCgkY29sb3IsICdj
b2RlJykgfX0iCiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRhLWNvbG9yLXNlbGxhYmxl
PSIxIgogICAgICAgICAgICAgICAgICAgICAgICAgICAgYXJpYS1wcmVzc2VkPSJ7eyAkYWN0aXZl
ID8gJ3RydWUnIDogJ2ZhbHNlJyB9fSIKICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFyaWEt
bGFiZWw9Ik3DoHUge3sgZGF0YV9nZXQoJGNvbG9yLCAnbGFiZWwnKSB9fSIKICAgICAgICAgICAg
ICAgICAgICAgICAgPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHNwYW4gY2xhc3M9Imx4
Yy1jb2xvcl9fdmlzdWFsIiBzdHlsZT0iLS1seGMtc3dhdGNoOnt7IGRhdGFfZ2V0KCRjb2xvciwg
J2hleCcpID86ICcjZGZlM2VmJyB9fSI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg
QGlmKCRjb3ZlcikKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGltZyBzcmM9
Int7ICRjb3ZlciB9fSIgYWx0PSIiIHdpZHRoPSI3MiIgaGVpZ2h0PSI5MCIgbG9hZGluZz0ibGF6
eSIgZGVjb2Rpbmc9ImFzeW5jIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBAZWxz
ZQogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aSBzdHlsZT0iLS1seGMtc3dh
dGNoOnt7IGRhdGFfZ2V0KCRjb2xvciwgJ2hleCcpID86ICcjZGZlM2VmJyB9fSI+PC9pPgogICAg
ICAgICAgICAgICAgICAgICAgICAgICAgICAgIEBlbmRpZgogICAgICAgICAgICAgICAgICAgICAg
ICAgICAgPC9zcGFuPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHN0cm9uZz57eyBkYXRh
X2dldCgkY29sb3IsICdsYWJlbCcpIH19PC9zdHJvbmc+CiAgICAgICAgICAgICAgICAgICAgICAg
IDwvYnV0dG9uPgogICAgICAgICAgICAgICAgICAgIEBlbmRmb3JlYWNoCiAgICAgICAgICAgICAg
ICA8L2Rpdj4KICAgICAgICAgICAgQGVsc2UKICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4
Yy1hbGwtc29sZG91dCIgcm9sZT0ic3RhdHVzIj4KICAgICAgICAgICAgICAgICAgICBU4bqldCBj
4bqjIG3DoHUgaGnhu4duIMSRYW5nIHThuqFtIGjhur90IGjDoG5nLgogICAgICAgICAgICAgICAg
PC9kaXY+CiAgICAgICAgICAgIEBlbmRpZgoKICAgICAgICAgICAgQGlmKCRyZXF1ZXN0ZWRVbmF2
YWlsYWJsZSkKICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4Yy1jb2xvci11bmF2YWlsYWJs
ZSIgcm9sZT0ic3RhdHVzIj4KICAgICAgICAgICAgICAgICAgICA8c3BhbiBzdHlsZT0iLS1seGMt
c3dhdGNoOnt7IGRhdGFfZ2V0KCRyZXF1ZXN0ZWRVbmF2YWlsYWJsZSwgJ2hleCcpID86ICcjY2Jk
NWUxJyB9fSI+PC9zcGFuPgogICAgICAgICAgICAgICAgICAgIDxkaXY+CiAgICAgICAgICAgICAg
ICAgICAgICAgIDxzdHJvbmc+e3sgZGF0YV9nZXQoJHJlcXVlc3RlZFVuYXZhaWxhYmxlLCAnbGFi
ZWwnKSB9fTwvc3Ryb25nPgogICAgICAgICAgICAgICAgICAgICAgICA8c21hbGw+TcOgdSBuw6B5
IMSRYW5nIHThuqFtIGjhur90IGjDoG5nPC9zbWFsbD4KICAgICAgICAgICAgICAgICAgICA8L2Rp
dj4KICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICBAZW5kaWYKICAgICAgICA8L3Nl
Y3Rpb24+CgogICAgICAgIDxzZWN0aW9uIGNsYXNzPSJseHBkcC1zZWxlY3RvciBseGMtc2VsZWN0
b3IgbHhjLXNlbGVjdG9yLS1zaXplIiBhcmlhLWxhYmVsbGVkYnk9Imx4c1NpemVUaXRsZSI+CiAg
ICAgICAgICAgIDxkaXYgY2xhc3M9Imx4Yy1zZWxlY3Rvcl9faGVhZCI+CiAgICAgICAgICAgICAg
ICA8aDIgaWQ9Imx4c1NpemVUaXRsZSI+S8OtY2ggdGjGsOG7m2M8L2gyPgogICAgICAgICAgICAg
ICAgPGJ1dHRvbgogICAgICAgICAgICAgICAgICAgIHR5cGU9ImJ1dHRvbiIKICAgICAgICAgICAg
ICAgICAgICBjbGFzcz0ibHhwZHAtc2l6ZS1hZHZpc29yLWxpbmsgbHhjLXNpemUtZ3VpZGUiCiAg
ICAgICAgICAgICAgICAgICAgZGF0YS1seHBkcC1zaXplLWFkdmlzb3Itb3BlbgogICAgICAgICAg
ICAgICAgICAgIEBpZighZGF0YV9nZXQoJGFkdmlzb3IsICdlbmFibGVkJykpIGRpc2FibGVkIEBl
bmRpZgogICAgICAgICAgICAgICAgPgogICAgICAgICAgICAgICAgICAgIFTDrG0gc2l6ZSBj4bun
YSBi4bqhbgogICAgICAgICAgICAgICAgICAgIDxzdmcgdmlld0JveD0iMCAwIDI0IDI0IiBhcmlh
LWhpZGRlbj0idHJ1ZSI+PHBhdGggZD0iTTUgMTJoMTRNMTQgN2w1IDUtNSA1Ii8+PC9zdmc+CiAg
ICAgICAgICAgICAgICA8L2J1dHRvbj4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgIDxk
aXYgY2xhc3M9Imx4cGRwLXNpemUtbGlzdCBseGMtc2l6ZS1saXN0IiBkYXRhLWx4cGRwLXNpemVz
IHJvbGU9Imxpc3QiIGFyaWEtbGl2ZT0icG9saXRlIj48L2Rpdj4KICAgICAgICAgICAgPGRpdiBj
bGFzcz0ibHhwZHAtc2VsZWN0aW9uIGx4Yy1zZWxlY3Rpb24iIGRhdGEtbHhwZHAtc2VsZWN0aW9u
IGhpZGRlbj4KICAgICAgICAgICAgICAgIDxzdHJvbmcgZGF0YS1seHBkcC1zZWxlY3RlZC10ZXh0
Pjwvc3Ryb25nPgogICAgICAgICAgICAgICAgPHNwYW4gZGF0YS1seHBkcC1zZWxlY3RlZC1zdG9j
az48L3NwYW4+CiAgICAgICAgICAgIDwvZGl2PgogICAgICAgIDwvc2VjdGlvbj4KCiAgICAgICAg
PGZvcm0gbWV0aG9kPSJwb3N0IiBhY3Rpb249Int7IGRhdGFfZ2V0KCRjb21tZXJjZSwgJ2NhcnRf
YWN0aW9uJykgfX0iIGNsYXNzPSJseHBkcC1jYXJ0LWZvcm0gbHhjLWNhcnQiIGRhdGEtbHhwZHAt
Y2FydC1mb3JtPgogICAgICAgICAgICBAY3NyZgogICAgICAgICAgICA8aW5wdXQgdHlwZT0iaGlk
ZGVuIiBuYW1lPSJzZWxsYWJsZV9za3VfaWQiIHZhbHVlPSIiIGRhdGEtbHhwZHAtc2t1LWlucHV0
PgogICAgICAgICAgICA8aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJxdWFudGl0eSIgdmFsdWU9
IjEiPgogICAgICAgICAgICA8YnV0dG9uIGNsYXNzPSJseHBkcC1wcmltYXJ5LWJ1dHRvbiBseGMt
YnV5LWJ1dHRvbiIgdHlwZT0ic3VibWl0IiBkaXNhYmxlZCBkYXRhLWx4cGRwLWJ1eT4KICAgICAg
ICAgICAgICAgIENo4buNbiBtw6B1IHbDoCBrw61jaCB0aMaw4bubYwogICAgICAgICAgICA8L2J1
dHRvbj4KICAgICAgICA8L2Zvcm0+CiAgICA8L2FzaWRlPgo8L2Rpdj4KCjxuYXYgY2xhc3M9Imx4
Yy1kb2NrIiBkYXRhLWx4Yy1kb2NrIGFyaWEtbGFiZWw9IlRoYW5oIG11YSBow6BuZyBuaGFuaCI+
CiAgICA8ZGl2IGNsYXNzPSJseGMtZG9ja19faW5uZXIiPgogICAgICAgIDxhIGNsYXNzPSJseGMt
ZG9ja19faWNvbiIgaHJlZj0ie3sgcm91dGUoJ2NvbW1lcmNlLnYyLmhvbWUnKSB9fSIgYXJpYS1s
YWJlbD0iVHJhbmcgY2jhu6ciPgogICAgICAgICAgICA8c3ZnIHZpZXdCb3g9IjAgMCAyNCAyNCIg
YXJpYS1oaWRkZW49InRydWUiPjxwYXRoIGQ9Im0zIDExIDktNyA5IDd2OWgtNnYtNkg5djZIM3oi
Lz48L3N2Zz4KICAgICAgICA8L2E+CiAgICAgICAgPGEgY2xhc3M9Imx4Yy1kb2NrX19pY29uIiBo
cmVmPSJ7eyByb3V0ZSgnY29tbWVyY2UudjIuc2VhcmNoJykgfX0iIGFyaWEtbGFiZWw9IlTDrG0g
a2nhur9tIj4KICAgICAgICAgICAgPHN2ZyB2aWV3Qm94PSIwIDAgMjQgMjQiIGFyaWEtaGlkZGVu
PSJ0cnVlIj48Y2lyY2xlIGN4PSIxMSIgY3k9IjExIiByPSI2Ii8+PHBhdGggZD0ibTE2IDE2IDQg
NCIvPjwvc3ZnPgogICAgICAgIDwvYT4KICAgICAgICA8YSBjbGFzcz0ibHhjLWRvY2tfX2ljb24i
IGhyZWY9Int7IHJvdXRlKCdjb21tZXJjZS52Mi5jYXJ0LmluZGV4JykgfX0iIGFyaWEtbGFiZWw9
Ikdp4buPIGjDoG5nIj4KICAgICAgICAgICAgPHN2ZyB2aWV3Qm94PSIwIDAgMjQgMjQiIGFyaWEt
aGlkZGVuPSJ0cnVlIj48cGF0aCBkPSJNNSA3aDE0bC0xIDEzSDZMNSA3WiIvPjxwYXRoIGQ9Ik05
IDdhMyAzIDAgMCAxIDYgMCIvPjwvc3ZnPgogICAgICAgIDwvYT4KCiAgICAgICAgPGRpdiBjbGFz
cz0ibHhjLWRvY2tfX3N1bW1hcnkiIGFyaWEtbGl2ZT0icG9saXRlIj4KICAgICAgICAgICAgPHN0
cm9uZyBkYXRhLWx4Yy1kb2NrLXByaWNlPnt7IG51bWJlcl9mb3JtYXQoKGZsb2F0KSBkYXRhX2dl
dCgkY29tbWVyY2UsICdwcmljZS5taW4nKSwgMCwgJywnLCAnLicpIH194oKrPC9zdHJvbmc+CiAg
ICAgICAgICAgIDxzcGFuIGRhdGEtbHhjLWRvY2stc2VsZWN0aW9uPkNo4buNbiBtw6B1ICZhbXA7
IHNpemU8L3NwYW4+CiAgICAgICAgPC9kaXY+CgogICAgICAgIDxidXR0b24gdHlwZT0iYnV0dG9u
IiBjbGFzcz0ibHhjLWRvY2tfX2N0YSIgZGF0YS1seGMtZG9jay1zdWJtaXQgZGlzYWJsZWQ+CiAg
ICAgICAgICAgIDxzcGFuIGRhdGEtbHhjLWRvY2stbGFiZWw+Q2jhu41uIHNpemU8L3NwYW4+CiAg
ICAgICAgICAgIDxzdmcgdmlld0JveD0iMCAwIDI0IDI0IiBhcmlhLWhpZGRlbj0idHJ1ZSI+PHBh
dGggZD0iTTUgMTJoMTRNMTQgN2w1IDUtNSA1Ii8+PC9zdmc+CiAgICAgICAgPC9idXR0b24+CiAg
ICA8L2Rpdj4KPC9uYXY+Cg==
SF_CLARITY_HERO_B64

    decode_to_file "$ANGLES" <<'SF_CLARITY_ANGLES_B64'
QHBocAogICAgJGlkZW50aXR5ID0gKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnaWRlbnRpdHknLCBb
XSk7CiAgICAkY29tbWVyY2UgPSAoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdjb21tZXJjZScsIFtd
KTsKICAgICRkZWZhdWx0Q29sb3IgPSAoYXJyYXkpIGRhdGFfZ2V0KCRjb21tZXJjZSwgJ2RlZmF1
bHRfY29sb3InLCBbXSk7CiAgICAkY2xhcml0eUl0ZW1zID0gY29sbGVjdCgoYXJyYXkpIGRhdGFf
Z2V0KCRkZWZhdWx0Q29sb3IsICdjbGFyaXR5X21lZGlhJywgW10pKQogICAgICAgIC0+ZmlsdGVy
KGZuICgkaXRlbSkgPT4gdHJpbSgoc3RyaW5nKSBkYXRhX2dldCgkaXRlbSwgJ3VybCcpKSAhPT0g
JycpCiAgICAgICAgLT50YWtlKDgpCiAgICAgICAgLT52YWx1ZXMoKTsKCiAgICBpZiAoJGNsYXJp
dHlJdGVtcy0+aXNFbXB0eSgpKSB7CiAgICAgICAgJGNsYXJpdHlJdGVtcyA9IGNvbGxlY3QoKGFy
cmF5KSBkYXRhX2dldCgkZGVmYXVsdENvbG9yLCAnbWVkaWEnLCBbXSkpCiAgICAgICAgICAgIC0+
ZmlsdGVyKGZ1bmN0aW9uICgkaXRlbSkgewogICAgICAgICAgICAgICAgJGNhdGVnb3J5ID0gXEls
bHVtaW5hdGVcU3VwcG9ydFxTdHI6OnVwcGVyKAogICAgICAgICAgICAgICAgICAgIChzdHJpbmcp
IGRhdGFfZ2V0KCRpdGVtLCAnY2F0ZWdvcnlfY29kZScpCiAgICAgICAgICAgICAgICApOwoKICAg
ICAgICAgICAgICAgIHJldHVybiBcSWxsdW1pbmF0ZVxTdXBwb3J0XFN0cjo6Y29udGFpbnMoCiAg
ICAgICAgICAgICAgICAgICAgJGNhdGVnb3J5LAogICAgICAgICAgICAgICAgICAgICdQUk9EVUNU
X0NMQVJJVFknCiAgICAgICAgICAgICAgICApICYmIHRyaW0oKHN0cmluZykgZGF0YV9nZXQoJGl0
ZW0sICd1cmwnKSkgIT09ICcnOwogICAgICAgICAgICB9KQogICAgICAgICAgICAtPnRha2UoOCkK
ICAgICAgICAgICAgLT52YWx1ZXMoKTsKICAgIH0KCiAgICAkYW5nbGVMYWJlbCA9IGZ1bmN0aW9u
IChhcnJheSAkaXRlbSk6IHN0cmluZyB7CiAgICAgICAgJGJsb2IgPSBcSWxsdW1pbmF0ZVxTdXBw
b3J0XFN0cjo6dXBwZXIodHJpbSgKICAgICAgICAgICAgKHN0cmluZykgZGF0YV9nZXQoJGl0ZW0s
ICdzaG90X2FuZ2xlJykKICAgICAgICAgICAgLicgJwogICAgICAgICAgICAuKHN0cmluZykgZGF0
YV9nZXQoJGl0ZW0sICdyb2xlJykKICAgICAgICApKTsKCiAgICAgICAgcmV0dXJuIG1hdGNoICh0
cnVlKSB7CiAgICAgICAgICAgIFxJbGx1bWluYXRlXFN1cHBvcnRcU3RyOjpjb250YWlucygkYmxv
YiwgWydGUk9OVF8zUScsICdGUk9OVCAzUScsICdGUk9OVCBUSFJFRScsICczLzQgRlJPTlQnXSkK
ICAgICAgICAgICAgICAgID0+ICdHw7NjIHRyxrDhu5tjIDMvNCcsCiAgICAgICAgICAgIFxJbGx1
bWluYXRlXFN1cHBvcnRcU3RyOjpjb250YWlucygkYmxvYiwgWydCQUNLXzNRJywgJ0JBQ0sgM1En
LCAnMy80IEJBQ0snXSkKICAgICAgICAgICAgICAgID0+ICdHw7NjIHNhdSAzLzQnLAogICAgICAg
ICAgICBcSWxsdW1pbmF0ZVxTdXBwb3J0XFN0cjo6Y29udGFpbnMoJGJsb2IsIFsnTEVGVF9TSURF
JywgJ1NJREVfTEVGVCcsICdMRUZUIFBST0ZJTEUnXSkKICAgICAgICAgICAgICAgID0+ICdHw7Nj
IG5naGnDqm5nIHRyw6FpJywKICAgICAgICAgICAgXElsbHVtaW5hdGVcU3VwcG9ydFxTdHI6OmNv
bnRhaW5zKCRibG9iLCBbJ1JJR0hUX1NJREUnLCAnU0lERV9SSUdIVCcsICdSSUdIVCBQUk9GSUxF
J10pCiAgICAgICAgICAgICAgICA9PiAnR8OzYyBuZ2hpw6puZyBwaOG6o2knLAogICAgICAgICAg
ICBcSWxsdW1pbmF0ZVxTdXBwb3J0XFN0cjo6Y29udGFpbnMoJGJsb2IsIFsnRlVMTF9GUk9OVCcs
ICdQUk9EVUNUX0ZST05UJywgJ0ZST05UJ10pCiAgICAgICAgICAgICAgICA9PiAnTeG6t3QgdHLG
sOG7m2MnLAogICAgICAgICAgICBcSWxsdW1pbmF0ZVxTdXBwb3J0XFN0cjo6Y29udGFpbnMoJGJs
b2IsIFsnRlVMTF9CQUNLJywgJ1BST0RVQ1RfQkFDSycsICdCQUNLJ10pCiAgICAgICAgICAgICAg
ICA9PiAnTeG6t3Qgc2F1JywKICAgICAgICAgICAgXElsbHVtaW5hdGVcU3VwcG9ydFxTdHI6OmNv
bnRhaW5zKCRibG9iLCBbJ1NJREUnLCAnUFJPRklMRSddKQogICAgICAgICAgICAgICAgPT4gJ0fD
s2MgbmdoacOqbmcnLAogICAgICAgICAgICBcSWxsdW1pbmF0ZVxTdXBwb3J0XFN0cjo6Y29udGFp
bnMoJGJsb2IsIFsnREVUQUlMJywgJ0NMT1NFJywgJ01BQ1JPJ10pCiAgICAgICAgICAgICAgICA9
PiAnQ2hpIHRp4bq/dCBz4bqjbiBwaOG6qW0nLAogICAgICAgICAgICBcSWxsdW1pbmF0ZVxTdXBw
b3J0XFN0cjo6Y29udGFpbnMoJGJsb2IsIFsnTElGRVNUWUxFJywgJ01PREVMJ10pCiAgICAgICAg
ICAgICAgICA9PiAnVHLDqm4gbmfGsOG7nWkgbeG6q3UnLAogICAgICAgICAgICBkZWZhdWx0ID0+
IG1hdGNoICgoc3RyaW5nKSBkYXRhX2dldCgkaXRlbSwgJ3JvbGUnKSkgewogICAgICAgICAgICAg
ICAgJ2Zyb250JyA9PiAnTeG6t3QgdHLGsOG7m2MnLAogICAgICAgICAgICAgICAgJ2JhY2snID0+
ICdN4bq3dCBzYXUnLAogICAgICAgICAgICAgICAgJ3NpZGUnID0+ICdHw7NjIG5naGnDqm5nJywK
ICAgICAgICAgICAgICAgICdkZXRhaWwnID0+ICdDaGkgdGnhur90IHPhuqNuIHBo4bqpbScsCiAg
ICAgICAgICAgICAgICAnbGlmZXN0eWxlJyA9PiAnVHLDqm4gbmfGsOG7nWkgbeG6q3UnLAogICAg
ICAgICAgICAgICAgZGVmYXVsdCA9PiAnR8OzYyBuaMOsbiBz4bqjbiBwaOG6qW0nLAogICAgICAg
ICAgICB9LAogICAgICAgIH07CiAgICB9OwoKICAgICRhbmdsZURlc2NyaXB0aW9uID0gZnVuY3Rp
b24gKHN0cmluZyAkbGFiZWwpOiBzdHJpbmcgewogICAgICAgIHJldHVybiBtYXRjaCAoJGxhYmVs
KSB7CiAgICAgICAgICAgICdN4bq3dCB0csaw4bubYycgPT4gJ1F1YW4gc8OhdCB0b8OgbiBi4buZ
IMSRxrDhu51uZyBuw6l0IHbDoCB04bu3IGzhu4cgcGjDrWEgdHLGsOG7m2MuJywKICAgICAgICAg
ICAgJ03hurd0IHNhdScgPT4gJ0tp4buDbSB0cmEgcGhvbSBsxrBuZywga2jDs2EgdsOgIMSR4buZ
IHLGoWkgY+G7p2Egc+G6o24gcGjhuqltLicsCiAgICAgICAgICAgICdHw7NjIHRyxrDhu5tjIDMv
NCcgPT4gJ0PhuqNtIG5o4bqtbiDEkeG7mSBu4buVaSBraOG7kWkgdsOgIGPDoWNoIHBob20gw7Rt
IGPGoSB0aOG7gy4nLAogICAgICAgICAgICAnR8OzYyBzYXUgMy80JyA9PiAnWGVtIHLDtSBjaHV5
4buDbiB0aeG6v3AgdOG7qyBsxrBuZyBzYW5nIGjDtG5nIHbDoCBn4bqldS4nLAogICAgICAgICAg
ICAnR8OzYyBuZ2hpw6puZyB0csOhaScsICdHw7NjIG5naGnDqm5nIHBo4bqjaScsICdHw7NjIG5n
aGnDqm5nJwogICAgICAgICAgICAgICAgPT4gJ8SQw6FuaCBnacOhIMSR4buZIGTDoHksIGNoaeG7
gXUgc8OidSB2w6AgxJHGsOG7nW5nIGNvbmcgY+G7p2EgcGhvbS4nLAogICAgICAgICAgICAnQ2hp
IHRp4bq/dCBz4bqjbiBwaOG6qW0nID0+ICdOaMOsbiBn4bqnbiBjaOG6pXQgbGnhu4d1IHbDoCDE
kWnhu4NtIG5o4bqlbiB0aGnhur90IGvhur8uJywKICAgICAgICAgICAgJ1Ryw6puIG5nxrDhu51p
IG3huqt1JyA9PiAnSMOsbmggZHVuZyB04bu3IGzhu4cgc+G6o24gcGjhuqltIGtoaSBt4bq3YyB0
aOG7sWMgdOG6vy4nLAogICAgICAgICAgICBkZWZhdWx0ID0+ICdN4buZdCBnw7NjIG5ow6xuIMSR
w6MgxJHGsOG7o2MgY2jhu41uIMSR4buDIGzDoG0gcsO1IHPhuqNuIHBo4bqpbS4nLAogICAgICAg
IH07CiAgICB9OwpAZW5kcGhwCgo8ZGl2CiAgICBjbGFzcz0ibHhjLWFuZ2xlcyIKICAgIGRhdGEt
bHhjLWNsYXJpdHktc2VjdGlvbgogICAgZGF0YS1seGMtcmV2ZWFsCj4KICAgIDxkaXYgY2xhc3M9
Imx4Yy1zaGVsbCI+CiAgICAgICAgPGhlYWRlciBjbGFzcz0ibHhjLWFuZ2xlc19faGVhZGVyIj4K
ICAgICAgICAgICAgPGRpdj4KICAgICAgICAgICAgICAgIDxwIGNsYXNzPSJseGMta2lja2VyIj5D
aGkgdGnhur90IHPhuqNuIHBo4bqpbTwvcD4KICAgICAgICAgICAgICAgIDxoMj5YZW0gcsO1IHTh
u6tuZyBnw7NjIGPhu6dhIHt7IGRhdGFfZ2V0KCRpZGVudGl0eSwgJ3Nob3J0X25hbWUnKSA/OiBk
YXRhX2dldCgkaWRlbnRpdHksICduYW1lJykgfX08L2gyPgogICAgICAgICAgICA8L2Rpdj4KICAg
ICAgICAgICAgPGRpdiBjbGFzcz0ibHhjLWFuZ2xlc19faW50cm8iPgogICAgICAgICAgICAgICAg
PHNwYW4gZGF0YS1seGMtY2xhcml0eS1jb2xvcj57eyBkYXRhX2dldCgkZGVmYXVsdENvbG9yLCAn
bGFiZWwnLCAnTcOgdSDEkWFuZyBjaOG7jW4nKSB9fTwvc3Bhbj4KICAgICAgICAgICAgICAgIDxw
PkLhu5kg4bqjbmggcsO1IHPhuqNuIHBo4bqpbSDEkcOjIMSRxrDhu6NjIGR1eeG7h3QsIGhp4buD
biB0aOG7iyDEkcO6bmcgbcOgdSBi4bqhbiDEkWFuZyB4ZW0gdsOgIGtow7RuZyBtxrDhu6NuIOG6
o25oIHThu6sgbcOgdSBraMOhYy48L3A+CiAgICAgICAgICAgIDwvZGl2PgogICAgICAgIDwvaGVh
ZGVyPgoKICAgICAgICA8bmF2CiAgICAgICAgICAgIGNsYXNzPSJseGMtYW5nbGUtbmF2IgogICAg
ICAgICAgICBkYXRhLWx4Yy1hbmdsZS1uYXYKICAgICAgICAgICAgYXJpYS1sYWJlbD0iQ8OhYyBn
w7NjIOG6o25oIHPhuqNuIHBo4bqpbSIKICAgICAgICAgICAgQGlmKCRjbGFyaXR5SXRlbXMtPmlz
RW1wdHkoKSkgaGlkZGVuIEBlbmRpZgogICAgICAgID4KICAgICAgICAgICAgQGZvcmVhY2goJGNs
YXJpdHlJdGVtcyBhcyAkaW5kZXggPT4gJGl0ZW0pCiAgICAgICAgICAgICAgICBAcGhwICRsYWJl
bCA9ICRhbmdsZUxhYmVsKChhcnJheSkgJGl0ZW0pOyBAZW5kcGhwCiAgICAgICAgICAgICAgICA8
YnV0dG9uCiAgICAgICAgICAgICAgICAgICAgdHlwZT0iYnV0dG9uIgogICAgICAgICAgICAgICAg
ICAgIGRhdGEtbHhjLWFuZ2xlLWp1bXA9Int7ICRpbmRleCB9fSIKICAgICAgICAgICAgICAgICAg
ICBhcmlhLWxhYmVsPSLEkGkgdOG7m2kg4bqjbmgge3sgJGxhYmVsIH19IgogICAgICAgICAgICAg
ICAgPgogICAgICAgICAgICAgICAgICAgIDxzcGFuPnt7IHN0cl9wYWQoKHN0cmluZykgKCRpbmRl
eCArIDEpLCAyLCAnMCcsIFNUUl9QQURfTEVGVCkgfX08L3NwYW4+CiAgICAgICAgICAgICAgICAg
ICAge3sgJGxhYmVsIH19CiAgICAgICAgICAgICAgICA8L2J1dHRvbj4KICAgICAgICAgICAgQGVu
ZGZvcmVhY2gKICAgICAgICA8L25hdj4KCiAgICAgICAgPGRpdgogICAgICAgICAgICBjbGFzcz0i
bHhjLWFuZ2xlLWdyaWQiCiAgICAgICAgICAgIGRhdGEtbHhjLWNsYXJpdHktZ3JpZAogICAgICAg
ICAgICBAaWYoJGNsYXJpdHlJdGVtcy0+aXNFbXB0eSgpKSBoaWRkZW4gQGVuZGlmCiAgICAgICAg
PgogICAgICAgICAgICBAZm9yZWFjaCgkY2xhcml0eUl0ZW1zIGFzICRpbmRleCA9PiAkaXRlbSkK
ICAgICAgICAgICAgICAgIEBwaHAKICAgICAgICAgICAgICAgICAgICAkbGFiZWwgPSAkYW5nbGVM
YWJlbCgoYXJyYXkpICRpdGVtKTsKICAgICAgICAgICAgICAgICAgICAkZGVzY3JpcHRpb24gPSAk
YW5nbGVEZXNjcmlwdGlvbigkbGFiZWwpOwogICAgICAgICAgICAgICAgQGVuZHBocAogICAgICAg
ICAgICAgICAgPGZpZ3VyZQogICAgICAgICAgICAgICAgICAgIGNsYXNzPSJseGMtYW5nbGUtY2Fy
ZCBseGMtYW5nbGUtY2FyZC0te3sgbWluKCRpbmRleCArIDEsIDgpIH19IgogICAgICAgICAgICAg
ICAgICAgIGRhdGEtbHhjLWNsYXJpdHktaXRlbT0ie3sgJGluZGV4IH19IgogICAgICAgICAgICAg
ICAgPgogICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4Yy1hbmdsZS1jYXJkX19tZWRp
YSI+CiAgICAgICAgICAgICAgICAgICAgICAgIDxpbWcKICAgICAgICAgICAgICAgICAgICAgICAg
ICAgIHNyYz0ie3sgZGF0YV9nZXQoJGl0ZW0sICd1cmwnKSB9fSIKICAgICAgICAgICAgICAgICAg
ICAgICAgICAgIGFsdD0ie3sgZGF0YV9nZXQoJGlkZW50aXR5LCAnbmFtZScpIH19IOKAlCB7eyBk
YXRhX2dldCgkZGVmYXVsdENvbG9yLCAnbGFiZWwnKSB9fSDigJQge3sgJGxhYmVsIH19IgogICAg
ICAgICAgICAgICAgICAgICAgICAgICAgbG9hZGluZz0ie3sgJGluZGV4ID09PSAwID8gJ2VhZ2Vy
JyA6ICdsYXp5JyB9fSIKICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRlY29kaW5nPSJhc3lu
YyIKICAgICAgICAgICAgICAgICAgICAgICAgPgogICAgICAgICAgICAgICAgICAgICAgICA8c3Bh
bj57eyBzdHJfcGFkKChzdHJpbmcpICgkaW5kZXggKyAxKSwgMiwgJzAnLCBTVFJfUEFEX0xFRlQp
IH19PC9zcGFuPgogICAgICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICAgICAgICAg
IDxmaWdjYXB0aW9uPgogICAgICAgICAgICAgICAgICAgICAgICA8c21hbGw+R8OzYyBuaMOsbjwv
c21hbGw+CiAgICAgICAgICAgICAgICAgICAgICAgIDxoMz57eyAkbGFiZWwgfX08L2gzPgogICAg
ICAgICAgICAgICAgICAgICAgICA8cD57eyAkZGVzY3JpcHRpb24gfX08L3A+CiAgICAgICAgICAg
ICAgICAgICAgPC9maWdjYXB0aW9uPgogICAgICAgICAgICAgICAgPC9maWd1cmU+CiAgICAgICAg
ICAgIEBlbmRmb3JlYWNoCiAgICAgICAgPC9kaXY+CgogICAgICAgIDxkaXYKICAgICAgICAgICAg
Y2xhc3M9Imx4Yy1hbmdsZXNfX2VtcHR5IgogICAgICAgICAgICBkYXRhLWx4Yy1jbGFyaXR5LWVt
cHR5CiAgICAgICAgICAgIHJvbGU9InN0YXR1cyIKICAgICAgICAgICAgQGlmKCRjbGFyaXR5SXRl
bXMtPmlzTm90RW1wdHkoKSkgaGlkZGVuIEBlbmRpZgogICAgICAgID4KICAgICAgICAgICAgPHNw
YW4gYXJpYS1oaWRkZW49InRydWUiPgogICAgICAgICAgICAgICAgPHN2ZyB2aWV3Qm94PSIwIDAg
NDggNDgiPjxwYXRoIGQ9Ik04IDEyaDMydjI0SDh6Ii8+PGNpcmNsZSBjeD0iMTgiIGN5PSIyMSIg
cj0iNCIvPjxwYXRoIGQ9Im0xMiAzMiA4LTcgNiA1IDUtNCA1IDYiLz48L3N2Zz4KICAgICAgICAg
ICAgPC9zcGFuPgogICAgICAgICAgICA8ZGl2PgogICAgICAgICAgICAgICAgPHN0cm9uZz7EkGFu
ZyBj4bqtcCBuaOG6rXQgYuG7mSDhuqNuaCByw7Ugc+G6o24gcGjhuqltPC9zdHJvbmc+CiAgICAg
ICAgICAgICAgICA8cD5Nw6B1IG7DoHkgY2jGsGEgY8OzIMSR4bunIOG6o25oIGfDs2MgxJHDoyBk
dXnhu4d0LiBMSU4gWMOJTiBraMO0bmcgZMO5bmcg4bqjbmggY+G7p2EgbcOgdSBraMOhYyDEkeG7
gyB0aGF5IHRo4bq/LjwvcD4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgPC9kaXY+CiAgICA8
L2Rpdj4KPC9kaXY+Cg==
SF_CLARITY_ANGLES_B64

    decode_to_file "$CSS" <<'SF_CLARITY_CSS_B64'
LyoKICogTElOIFjDiU4g4oCUIFN0dWRpbyBDbGFyaXR5IFBEUCBWMQogKiBGb2N1c2VkIHByb2R1
Y3Qgam91cm5leToKICogZ2FsbGVyeSDihpIgY29sb3Ivc2l6ZS9jYXJ0IOKGkiBhcHByb3ZlZCBw
cm9kdWN0LWNsYXJpdHkgYW5nbGVzLgogKiBWYXJpYW50LXNjb3BlZCBhbmQgaW5kZXBlbmRlbnQg
ZnJvbSBTdHVkaW8gU2lnbmFsLgogKi8KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9f
Y2xhcml0eV92MSJdIHsKICAgIC0tbHhjLWJnOiAjZjVmN2ZiOwogICAgLS1seGMtc3VyZmFjZTog
I2ZmZmZmZjsKICAgIC0tbHhjLXN1cmZhY2Utc29mdDogI2VlZjJmODsKICAgIC0tbHhjLWluazog
IzEwMTMxYjsKICAgIC0tbHhjLW11dGVkOiAjNjk3Mzg2OwogICAgLS1seGMtbGluZTogI2RjZTJl
YzsKICAgIC0tbHhjLXByaW1hcnk6ICM1YjVmZjI7CiAgICAtLWx4Yy1wcmltYXJ5LWRhcms6ICMz
ZjQzY2Y7CiAgICAtLWx4Yy1zaWduYWw6ICNmZjQxNmM7CiAgICAtLWx4Yy1zaWduYWwtZGFyazog
I2U0MmQ1ODsKICAgIC0tbHhjLXN1Y2Nlc3M6ICMxNDc2NTc7CiAgICAtLWx4Yy1kYW5nZXI6ICNi
ZDMxNTA7CiAgICAtLWx4Yy1kYXJrOiAjMTExNDFkOwogICAgLS1seGMtZGFyay1zb2Z0OiAjMWQy
MjMwOwogICAgLS1seGMtc2hhZG93LXNtOiAwIDE0cHggMzhweCByZ2JhKDI0LCAzNCwgNjAsIC4w
OCk7CiAgICAtLWx4Yy1zaGFkb3ctbGc6IDAgMzRweCAxMDBweCByZ2JhKDIwLCAyOSwgNTIsIC4x
Nik7CiAgICAtLWx4Yy1yYWRpdXM6IDI2cHg7CiAgICAtLWx4Yy1yYWRpdXMtbGc6IDM2cHg7CiAg
ICAtLWx4Yy1tYXg6IDEzODBweDsKICAgIGNvbG9yOiB2YXIoLS1seGMtaW5rKTsKICAgIGZvbnQt
ZmFtaWx5OiBJbnRlciwgdWktc2Fucy1zZXJpZiwgc3lzdGVtLXVpLCAtYXBwbGUtc3lzdGVtLCBC
bGlua01hY1N5c3RlbUZvbnQsICJTZWdvZSBVSSIsIHNhbnMtc2VyaWY7CiAgICBpc29sYXRpb246
IGlzb2xhdGU7Cn0KCmJvZHk6aGFzKC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xh
cml0eV92MSJdKSwKYm9keS5seC1wZHAtc3R1ZGlvLWNsYXJpdHkgewogICAgLS1seHYyLWJnOiAj
ZjVmN2ZiOwogICAgLS1seHYyLXN1cmZhY2U6ICNmZmY7CiAgICAtLWx4djItdGV4dDogIzEwMTMx
YjsKICAgIC0tbHh2Mi1tdXRlZDogIzY5NzM4NjsKICAgIC0tbHh2Mi1saW5lOiAjZGNlMmVjOwog
ICAgLS1seHYyLWFjY2VudDogIzViNWZmMjsKICAgIC0tbHh2Mi1hY2NlbnQtZGFyazogIzNmNDNj
ZjsKICAgIC0tbHh2Mi1zb2Z0OiAjZWVmMWZmOwogICAgYmFja2dyb3VuZDoKICAgICAgICByYWRp
YWwtZ3JhZGllbnQoY2lyY2xlIGF0IDUlIDAlLCByZ2JhKDkxLCA5NSwgMjQyLCAuMTApLCB0cmFu
c3BhcmVudCAzMXJlbSksCiAgICAgICAgcmFkaWFsLWdyYWRpZW50KGNpcmNsZSBhdCA5OCUgMTIl
LCByZ2JhKDI1NSwgNjUsIDEwOCwgLjA3KSwgdHJhbnNwYXJlbnQgMjZyZW0pLAogICAgICAgIGxp
bmVhci1ncmFkaWVudCgxODBkZWcsICNmYWZiZmUgMCUsICNmNWY3ZmIgNDglLCAjZmZmIDEwMCUp
Owp9Cgpib2R5OmhhcygubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEi
XSkgLmx4djItaGVhZGVyLApib2R5Lmx4LXBkcC1zdHVkaW8tY2xhcml0eSAubHh2Mi1oZWFkZXIg
ewogICAgYm9yZGVyLWJvdHRvbS1jb2xvcjogcmdiYSgyMjAsIDIyNiwgMjM2LCAuODYpOwogICAg
YmFja2dyb3VuZDogcmdiYSgyNTAsIDI1MSwgMjU0LCAuODgpOwogICAgYm94LXNoYWRvdzogMCA4
cHggMzBweCByZ2JhKDI1LCAzNSwgNjAsIC4wNCk7CiAgICBiYWNrZHJvcC1maWx0ZXI6IGJsdXIo
MjBweCkgc2F0dXJhdGUoMTUwJSk7Cn0KCmJvZHk6aGFzKC5seHBkcFtkYXRhLXBkcC12YXJpYW50
PSJzdHVkaW9fY2xhcml0eV92MSJdKSAubHh2Mi1icmFuZF9fbWFyaywKYm9keS5seC1wZHAtc3R1
ZGlvLWNsYXJpdHkgLmx4djItYnJhbmRfX21hcmsgewogICAgYm9yZGVyLXJhZGl1czogMTRweDsK
ICAgIGJhY2tncm91bmQ6IGxpbmVhci1ncmFkaWVudCgxMzVkZWcsIHZhcigtLWx4Yy1wcmltYXJ5
KSwgdmFyKC0tbHhjLXNpZ25hbCkpOwogICAgYm94LXNoYWRvdzogMCAxMHB4IDI2cHggcmdiYSg5
MSwgOTUsIDI0MiwgLjI0KTsKfQoKYm9keTpoYXMoLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19jbGFyaXR5X3YxIl0pIC5seHYyLW1haW4sCmJvZHkubHgtcGRwLXN0dWRpby1jbGFyaXR5
IC5seHYyLW1haW4gewogICAgcGFkZGluZy10b3A6IDE2cHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12
YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seHBkcC1wcmV2aWV3LWJhbm5lciB7CiAgICBi
b3JkZXI6IDFweCBzb2xpZCByZ2JhKDkxLCA5NSwgMjQyLCAuMTgpOwogICAgYm9yZGVyLXJhZGl1
czogMThweDsKICAgIGNvbG9yOiB2YXIoLS1seGMtaW5rKTsKICAgIGJhY2tncm91bmQ6IHJnYmEo
MjM4LCAyNDEsIDI1NSwgLjk1KTsKICAgIGJveC1zaGFkb3c6IHZhcigtLWx4Yy1zaGFkb3ctc20p
Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhwZHAt
cHJldmlldy1iYW5uZXIgYSB7CiAgICBjb2xvcjogdmFyKC0tbHhjLXByaW1hcnkpOwp9CgoubHhw
ZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhwZHBfX2JyZWFkY3J1
bWIgewogICAgd2lkdGg6IG1pbih2YXIoLS1seGMtbWF4KSwgY2FsYygxMDAlIC0gNDhweCkpOwog
ICAgbWFyZ2luOiA3cHggYXV0byAxOHB4OwogICAgY29sb3I6IHZhcigtLWx4Yy1tdXRlZCk7CiAg
ICBmb250LXNpemU6IDEycHg7CiAgICBmb250LXdlaWdodDogNzUwOwp9CgoubHhwZHBbZGF0YS1w
ZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhwZHAtZW5naW5lLXNlY3Rpb24gewog
ICAgd2lkdGg6IDEwMHZ3OwogICAgbWFyZ2luLWxlZnQ6IGNhbGMoNTAlIC0gNTB2dyk7Cn0KCi5s
eHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtc2hlbGwgewog
ICAgd2lkdGg6IG1pbih2YXIoLS1seGMtbWF4KSwgY2FsYygxMDAlIC0gNDhweCkpOwogICAgbWFy
Z2luLWlubGluZTogYXV0bzsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFy
aXR5X3YxIl0gc3ZnIHsKICAgIGZpbGw6IG5vbmU7CiAgICBzdHJva2U6IGN1cnJlbnRDb2xvcjsK
ICAgIHN0cm9rZS13aWR0aDogMS44OwogICAgc3Ryb2tlLWxpbmVjYXA6IHJvdW5kOwogICAgc3Ry
b2tlLWxpbmVqb2luOiByb3VuZDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19j
bGFyaXR5X3YxIl0gYnV0dG9uLAoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJp
dHlfdjEiXSBhIHsKICAgIC13ZWJraXQtdGFwLWhpZ2hsaWdodC1jb2xvcjogdHJhbnNwYXJlbnQ7
Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIGJ1dHRvbjpm
b2N1cy12aXNpYmxlLAoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEi
XSBhOmZvY3VzLXZpc2libGUgewogICAgb3V0bGluZTogM3B4IHNvbGlkIHJnYmEoOTEsIDk1LCAy
NDIsIC4yNik7CiAgICBvdXRsaW5lLW9mZnNldDogM3B4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFy
aWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWtpY2tlciB7CiAgICBtYXJnaW46IDAgMCAx
MHB4OwogICAgY29sb3I6IHZhcigtLWx4Yy1wcmltYXJ5KTsKICAgIGZvbnQtc2l6ZTogMTFweDsK
ICAgIGZvbnQtd2VpZ2h0OiA5MDA7CiAgICBsZXR0ZXItc3BhY2luZzogLjE3ZW07CiAgICB0ZXh0
LXRyYW5zZm9ybTogdXBwZXJjYXNlOwp9CgovKiBQcm9kdWN0IGhlcm8gKi8KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seHBkcC1lbmdpbmUtc2VjdGlvbi0t
Y2xhcml0eV9oZXJvX3B1cmNoYXNlIHsKICAgIHBhZGRpbmc6IDAgMCA4MnB4OwogICAgYmFja2dy
b3VuZDoKICAgICAgICBsaW5lYXItZ3JhZGllbnQoMTgwZGVnLCByZ2JhKDI1NSwgMjU1LCAyNTUs
IC44MiksIHJnYmEoMjQ1LCAyNDcsIDI1MSwgLjkyKSksCiAgICAgICAgcmFkaWFsLWdyYWRpZW50
KGNpcmNsZSBhdCAxNyUgNSUsIHJnYmEoOTEsIDk1LCAyNDIsIC4xMSksIHRyYW5zcGFyZW50IDM0
cmVtKTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4
Yy1oZXJvIHsKICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IG1p
bm1heCgwLCAxLjE1ZnIpIG1pbm1heCg0MDBweCwgLjcyZnIpOwogICAgZ2FwOiBjbGFtcCgzMHB4
LCA0dncsIDY4cHgpOwogICAgYWxpZ24taXRlbXM6IHN0YXJ0Owp9CgoubHhwZHBbZGF0YS1wZHAt
dmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWhlcm9fX2dhbGxlcnktY29sdW1uIHsK
ICAgIG1pbi13aWR0aDogMDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFy
aXR5X3YxIl0gLmx4Yy1oZXJvX190b3BsaW5lIHsKICAgIGRpc3BsYXk6IGZsZXg7CiAgICBqdXN0
aWZ5LWNvbnRlbnQ6IHNwYWNlLWJldHdlZW47CiAgICBnYXA6IDE2cHg7CiAgICBtYXJnaW4tYm90
dG9tOiAxMnB4OwogICAgY29sb3I6IHZhcigtLWx4Yy1tdXRlZCk7CiAgICBmb250LXNpemU6IDEw
cHg7CiAgICBmb250LXdlaWdodDogOTAwOwogICAgbGV0dGVyLXNwYWNpbmc6IC4xNWVtOwp9Cgou
bHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWdhbGxlcnlf
X3N0YWdlIHsKICAgIHBvc2l0aW9uOiByZWxhdGl2ZTsKICAgIGhlaWdodDogbWluKDc0dmgsIDg1
MHB4KTsKICAgIG1pbi1oZWlnaHQ6IDYxMHB4OwogICAgb3ZlcmZsb3c6IGhpZGRlbjsKICAgIGJv
cmRlcjogMXB4IHNvbGlkIHJnYmEoMjE5LCAyMjUsIDIzNiwgLjkpOwogICAgYm9yZGVyLXJhZGl1
czogMzRweDsKICAgIGJhY2tncm91bmQ6CiAgICAgICAgbGluZWFyLWdyYWRpZW50KDEzNWRlZywg
cmdiYSgyMzgsIDI0MSwgMjU1LCAuNjUpLCByZ2JhKDI1NSwgMjU1LCAyNTUsIC45MikpOwogICAg
Ym94LXNoYWRvdzogdmFyKC0tbHhjLXNoYWRvdy1sZyk7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJp
YW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZ2FsbGVyeV9fZmlndXJlIHsKICAgIHBvc2l0
aW9uOiByZWxhdGl2ZTsKICAgIHdpZHRoOiAxMDAlOwogICAgaGVpZ2h0OiAxMDAlOwogICAgbWFy
Z2luOiAwOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAu
bHhjLWdhbGxlcnlfX2ZpZ3VyZSA+IGltZyB7CiAgICB3aWR0aDogMTAwJTsKICAgIGhlaWdodDog
MTAwJTsKICAgIGRpc3BsYXk6IGJsb2NrOwogICAgb2JqZWN0LWZpdDogY292ZXI7CiAgICBvYmpl
Y3QtcG9zaXRpb246IGNlbnRlciB0b3A7CiAgICB0cmFuc2l0aW9uOiBvcGFjaXR5IC4yOHMgZWFz
ZSwgdHJhbnNmb3JtIC42NXMgY3ViaWMtYmV6aWVyKC4yLCAuOCwgLjIsIDEpOwp9CgoubHhwZHBb
ZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWdhbGxlcnlfX3N0YWdl
OmhvdmVyIC5seGMtZ2FsbGVyeV9fZmlndXJlID4gaW1nIHsKICAgIHRyYW5zZm9ybTogc2NhbGUo
MS4wMTIpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAu
bHhjLWdhbGxlcnlfX21ldGEgewogICAgcG9zaXRpb246IGFic29sdXRlOwogICAgbGVmdDogMThw
eDsKICAgIHJpZ2h0OiAxOHB4OwogICAgYm90dG9tOiAxOHB4OwogICAgZGlzcGxheTogZmxleDsK
ICAgIGp1c3RpZnktY29udGVudDogc3BhY2UtYmV0d2VlbjsKICAgIGdhcDogMTZweDsKICAgIHBh
ZGRpbmc6IDEycHggMTRweDsKICAgIGJvcmRlcjogMXB4IHNvbGlkIHJnYmEoMjU1LCAyNTUsIDI1
NSwgLjI4KTsKICAgIGJvcmRlci1yYWRpdXM6IDE2cHg7CiAgICBjb2xvcjogI2ZmZjsKICAgIGJh
Y2tncm91bmQ6IHJnYmEoMTQsIDE3LCAyNSwgLjU0KTsKICAgIGJhY2tkcm9wLWZpbHRlcjogYmx1
cigxNHB4KTsKICAgIGZvbnQtc2l6ZTogMTJweDsKICAgIGZvbnQtd2VpZ2h0OiA4NTA7CiAgICBs
ZXR0ZXItc3BhY2luZzogLjA0ZW07Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9f
Y2xhcml0eV92MSJdIC5seGMtZ2FsbGVyeV9fbmF2IHsKICAgIHBvc2l0aW9uOiBhYnNvbHV0ZTsK
ICAgIHotaW5kZXg6IDQ7CiAgICB0b3A6IDUwJTsKICAgIHdpZHRoOiA0NnB4OwogICAgaGVpZ2h0
OiA0NnB4OwogICAgZGlzcGxheTogZ3JpZDsKICAgIHBsYWNlLWl0ZW1zOiBjZW50ZXI7CiAgICBw
YWRkaW5nOiAwOwogICAgYm9yZGVyOiAxcHggc29saWQgcmdiYSgyNTUsIDI1NSwgMjU1LCAuNjIp
OwogICAgYm9yZGVyLXJhZGl1czogNTAlOwogICAgY29sb3I6IHZhcigtLWx4Yy1pbmspOwogICAg
YmFja2dyb3VuZDogcmdiYSgyNTUsIDI1NSwgMjU1LCAuODYpOwogICAgYm94LXNoYWRvdzogMCAx
MnB4IDMwcHggcmdiYSgyMCwgMjgsIDQ5LCAuMTUpOwogICAgYmFja2Ryb3AtZmlsdGVyOiBibHVy
KDEycHgpOwogICAgdHJhbnNmb3JtOiB0cmFuc2xhdGVZKC01MCUpOwogICAgY3Vyc29yOiBwb2lu
dGVyOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhj
LWdhbGxlcnlfX25hdiBzdmcgewogICAgd2lkdGg6IDIxcHg7CiAgICBoZWlnaHQ6IDIxcHg7Cn0K
Ci5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZ2FsbGVy
eV9fbmF2LS1wcmV2IHsKICAgIGxlZnQ6IDE2cHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50
PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZ2FsbGVyeV9fbmF2LS1uZXh0IHsKICAgIHJpZ2h0
OiAxNnB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAu
bHhjLWdhbGxlcnlfX3RodW1icyB7CiAgICBkaXNwbGF5OiBmbGV4OwogICAgZ2FwOiAxMHB4Owog
ICAgbWFyZ2luLXRvcDogMTJweDsKICAgIG92ZXJmbG93LXg6IGF1dG87CiAgICBwYWRkaW5nOiAy
cHggMnB4IDdweDsKICAgIHNjcm9sbGJhci13aWR0aDogbm9uZTsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1nYWxsZXJ5X190aHVtYnM6Oi13ZWJr
aXQtc2Nyb2xsYmFyIHsKICAgIGRpc3BsYXk6IG5vbmU7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJp
YW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZ2FsbGVyeV9fdGh1bWIgewogICAgZmxleDog
MCAwIDc2cHg7CiAgICBoZWlnaHQ6IDk0cHg7CiAgICBwYWRkaW5nOiAwOwogICAgb3ZlcmZsb3c6
IGhpZGRlbjsKICAgIGJvcmRlcjogMnB4IHNvbGlkIHRyYW5zcGFyZW50OwogICAgYm9yZGVyLXJh
ZGl1czogMTVweDsKICAgIGJhY2tncm91bmQ6ICNlOWVkZjQ7CiAgICBvcGFjaXR5OiAuNjg7CiAg
ICBjdXJzb3I6IHBvaW50ZXI7CiAgICB0cmFuc2l0aW9uOiBib3JkZXItY29sb3IgLjJzIGVhc2Us
IG9wYWNpdHkgLjJzIGVhc2UsIHRyYW5zZm9ybSAuMnMgZWFzZTsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1nYWxsZXJ5X190aHVtYiBpbWcgewog
ICAgd2lkdGg6IDEwMCU7CiAgICBoZWlnaHQ6IDEwMCU7CiAgICBvYmplY3QtZml0OiBjb3ZlcjsK
fQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1nYWxs
ZXJ5X190aHVtYi5pcy1hY3RpdmUgewogICAgYm9yZGVyLWNvbG9yOiB2YXIoLS1seGMtcHJpbWFy
eSk7CiAgICBvcGFjaXR5OiAxOwogICAgdHJhbnNmb3JtOiB0cmFuc2xhdGVZKC0ycHgpOwp9Cgou
bHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWdhbGxlcnlf
X25vdGljZSB7CiAgICBtYXJnaW46IDEycHggMCAwOwogICAgcGFkZGluZzogMTJweCAxNHB4Owog
ICAgYm9yZGVyLXJhZGl1czogMTRweDsKICAgIGNvbG9yOiAjNmU1ZDI4OwogICAgYmFja2dyb3Vu
ZDogI2ZmZjhkYzsKICAgIGZvbnQtc2l6ZTogMTNweDsKICAgIGxpbmUtaGVpZ2h0OiAxLjU7Cn0K
Ci5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYnV5IHsK
ICAgIHBvc2l0aW9uOiBzdGlja3k7CiAgICB0b3A6IDk2cHg7CiAgICBkaXNwbGF5OiBncmlkOwog
ICAgZ2FwOiAyMnB4OwogICAgcGFkZGluZzogMjhweDsKICAgIGJvcmRlcjogMXB4IHNvbGlkIHJn
YmEoMjIwLCAyMjYsIDIzNiwgLjkyKTsKICAgIGJvcmRlci1yYWRpdXM6IDMwcHg7CiAgICBiYWNr
Z3JvdW5kOiByZ2JhKDI1NSwgMjU1LCAyNTUsIC45NCk7CiAgICBib3gtc2hhZG93OiB2YXIoLS1s
eGMtc2hhZG93LXNtKTsKICAgIGJhY2tkcm9wLWZpbHRlcjogYmx1cigxOHB4KTsKfQoKLmx4cGRw
W2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1idXlfX2hlYWQgaDEg
ewogICAgbWFyZ2luOiAwOwogICAgZm9udC1zaXplOiBjbGFtcCg0MnB4LCA0LjR2dywgNjZweCk7
CiAgICBmb250LXdlaWdodDogOTAwOwogICAgbGluZS1oZWlnaHQ6IC45NTsKICAgIGxldHRlci1z
cGFjaW5nOiAtLjA2ZW07Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0
eV92MSJdIC5seGMtYnV5X19kZXNjcmlwdG9yIHsKICAgIG1hcmdpbjogMTJweCAwIDA7CiAgICBj
b2xvcjogdmFyKC0tbHhjLWluayk7CiAgICBmb250LXNpemU6IDE3cHg7CiAgICBmb250LXdlaWdo
dDogNzgwOwogICAgbGluZS1oZWlnaHQ6IDEuNDI7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50
PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYnV5X19kZXNjcmlwdGlvbiB7CiAgICBtYXJnaW46
IDEycHggMCAwOwogICAgY29sb3I6IHZhcigtLWx4Yy1tdXRlZCk7CiAgICBmb250LXNpemU6IDE0
cHg7CiAgICBsaW5lLWhlaWdodDogMS42Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1
ZGlvX2NsYXJpdHlfdjEiXSAubHhjLXByaWNlLWxpbmUgewogICAgZGlzcGxheTogZmxleDsKICAg
IGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAgICBqdXN0aWZ5LWNvbnRlbnQ6IHNwYWNlLWJldHdlZW47
CiAgICBnYXA6IDE4cHg7CiAgICBwYWRkaW5nLWJsb2NrOiAxN3B4OwogICAgYm9yZGVyLWJsb2Nr
OiAxcHggc29saWQgdmFyKC0tbHhjLWxpbmUpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0i
c3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLXByaWNlIHsKICAgIGRpc3BsYXk6IGZsZXg7CiAgICBh
bGlnbi1pdGVtczogYmFzZWxpbmU7CiAgICBnYXA6IDEwcHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12
YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtcHJpY2Ugc3Ryb25nIHsKICAgIGZvbnQt
c2l6ZTogMjVweDsKICAgIGZvbnQtd2VpZ2h0OiA5MDA7CiAgICBsZXR0ZXItc3BhY2luZzogLS4w
MzVlbTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4
Yy1wcmljZSBkZWwgewogICAgY29sb3I6ICM5YWEzYjQ7CiAgICBmb250LXNpemU6IDE0cHg7Cn0K
Ci5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtc3RvY2sg
ewogICAgZGlzcGxheTogaW5saW5lLWZsZXg7CiAgICBhbGlnbi1pdGVtczogY2VudGVyOwogICAg
Z2FwOiA3cHg7CiAgICBjb2xvcjogdmFyKC0tbHhjLXN1Y2Nlc3MpOwogICAgZm9udC1zaXplOiAx
MnB4OwogICAgZm9udC13ZWlnaHQ6IDg1MDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1zdG9jayBpIHsKICAgIHdpZHRoOiA4cHg7CiAgICBoZWln
aHQ6IDhweDsKICAgIGJvcmRlci1yYWRpdXM6IDUwJTsKICAgIGJhY2tncm91bmQ6IGN1cnJlbnRD
b2xvcjsKICAgIGJveC1zaGFkb3c6IDAgMCAwIDVweCByZ2JhKDIwLCAxMTgsIDg3LCAuMSk7Cn0K
Ci5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtc3RvY2su
aXMtb3V0IHsKICAgIGNvbG9yOiB2YXIoLS1seGMtZGFuZ2VyKTsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1zZWxlY3RvciB7CiAgICBkaXNwbGF5
OiBncmlkOwogICAgZ2FwOiAxMnB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlv
X2NsYXJpdHlfdjEiXSAubHhjLXNlbGVjdG9yX19oZWFkIHsKICAgIGRpc3BsYXk6IGZsZXg7CiAg
ICBhbGlnbi1pdGVtczogY2VudGVyOwogICAganVzdGlmeS1jb250ZW50OiBzcGFjZS1iZXR3ZWVu
OwogICAgZ2FwOiAxNHB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJp
dHlfdjEiXSAubHhjLXNlbGVjdG9yX19oZWFkIGgyIHsKICAgIG1hcmdpbjogMDsKICAgIGZvbnQt
c2l6ZTogMTRweDsKICAgIGZvbnQtd2VpZ2h0OiA5MDA7CiAgICBsZXR0ZXItc3BhY2luZzogLS4w
MWVtOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhj
LXNlbGVjdG9yX19oZWFkID4gc3BhbiB7CiAgICBjb2xvcjogdmFyKC0tbHhjLW11dGVkKTsKICAg
IGZvbnQtc2l6ZTogMTJweDsKICAgIGZvbnQtd2VpZ2h0OiA4MDA7Cn0KCi5seHBkcFtkYXRhLXBk
cC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtY29sb3ItbGlzdCB7CiAgICBkaXNw
bGF5OiBmbGV4OwogICAgZ2FwOiAxMHB4OwogICAgb3ZlcmZsb3cteDogYXV0bzsKICAgIHBhZGRp
bmc6IDJweCAycHggNnB4OwogICAgc2Nyb2xsYmFyLXdpZHRoOiBub25lOwp9CgoubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWNvbG9yLWxpc3Q6Oi13ZWJr
aXQtc2Nyb2xsYmFyIHsKICAgIGRpc3BsYXk6IG5vbmU7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJp
YW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtY29sb3IgewogICAgZmxleDogMCAwIDcycHg7
CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ2FwOiA3cHg7CiAgICBqdXN0aWZ5LWl0ZW1zOiBjZW50
ZXI7CiAgICBwYWRkaW5nOiAwOwogICAgYm9yZGVyOiAwOwogICAgY29sb3I6IHZhcigtLWx4Yy1t
dXRlZCk7CiAgICBiYWNrZ3JvdW5kOiB0cmFuc3BhcmVudDsKICAgIGN1cnNvcjogcG9pbnRlcjsK
fQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1jb2xv
cl9fdmlzdWFsIHsKICAgIHBvc2l0aW9uOiByZWxhdGl2ZTsKICAgIHdpZHRoOiA3MHB4OwogICAg
aGVpZ2h0OiA4MnB4OwogICAgb3ZlcmZsb3c6IGhpZGRlbjsKICAgIGJvcmRlcjogMnB4IHNvbGlk
IHRyYW5zcGFyZW50OwogICAgYm9yZGVyLXJhZGl1czogMTdweDsKICAgIGJhY2tncm91bmQ6IHZh
cigtLWx4Yy1zd2F0Y2gsICNlNWU5ZjEpOwogICAgYm94LXNoYWRvdzogaW5zZXQgMCAwIDAgMXB4
IHJnYmEoMTYsIDE5LCAyNywgLjA4KTsKICAgIHRyYW5zaXRpb246IGJvcmRlci1jb2xvciAuMnMg
ZWFzZSwgdHJhbnNmb3JtIC4ycyBlYXNlLCBib3gtc2hhZG93IC4ycyBlYXNlOwp9CgoubHhwZHBb
ZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWNvbG9yX192aXN1YWwg
aW1nIHsKICAgIHdpZHRoOiAxMDAlOwogICAgaGVpZ2h0OiAxMDAlOwogICAgb2JqZWN0LWZpdDog
Y292ZXI7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5s
eGMtY29sb3JfX3Zpc3VhbCA+IGkgewogICAgcG9zaXRpb246IGFic29sdXRlOwogICAgaW5zZXQ6
IDA7CiAgICBiYWNrZ3JvdW5kOiB2YXIoLS1seGMtc3dhdGNoLCAjZTVlOWYxKTsKfQoKLmx4cGRw
W2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1jb2xvciBzdHJvbmcg
ewogICAgbWF4LXdpZHRoOiA3OHB4OwogICAgb3ZlcmZsb3c6IGhpZGRlbjsKICAgIGNvbG9yOiBp
bmhlcml0OwogICAgZm9udC1zaXplOiAxMHB4OwogICAgZm9udC13ZWlnaHQ6IDg1MDsKICAgIHRl
eHQtb3ZlcmZsb3c6IGVsbGlwc2lzOwogICAgd2hpdGUtc3BhY2U6IG5vd3JhcDsKfQoKLmx4cGRw
W2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1jb2xvci5pcy1hY3Rp
dmUgewogICAgY29sb3I6IHZhcigtLWx4Yy1wcmltYXJ5KTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1jb2xvci5pcy1hY3RpdmUgLmx4Yy1jb2xv
cl9fdmlzdWFsIHsKICAgIGJvcmRlci1jb2xvcjogdmFyKC0tbHhjLXByaW1hcnkpOwogICAgYm94
LXNoYWRvdzogMCAxMHB4IDI0cHggcmdiYSg5MSwgOTUsIDI0MiwgLjE4KTsKICAgIHRyYW5zZm9y
bTogdHJhbnNsYXRlWSgtMnB4KTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19j
bGFyaXR5X3YxIl0gLmx4Yy1jb2xvci11bmF2YWlsYWJsZSwKLmx4cGRwW2RhdGEtcGRwLXZhcmlh
bnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbGwtc29sZG91dCB7CiAgICBkaXNwbGF5OiBm
bGV4OwogICAgYWxpZ24taXRlbXM6IGNlbnRlcjsKICAgIGdhcDogMTBweDsKICAgIHBhZGRpbmc6
IDExcHggMTJweDsKICAgIGJvcmRlci1yYWRpdXM6IDE0cHg7CiAgICBjb2xvcjogIzdkNDg1NDsK
ICAgIGJhY2tncm91bmQ6ICNmZmYwZjQ7CiAgICBmb250LXNpemU6IDEycHg7Cn0KCi5seHBkcFtk
YXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtY29sb3ItdW5hdmFpbGFi
bGUgPiBzcGFuIHsKICAgIHdpZHRoOiAzMHB4OwogICAgaGVpZ2h0OiAzMHB4OwogICAgZmxleDog
MCAwIGF1dG87CiAgICBib3JkZXItcmFkaXVzOiA5cHg7CiAgICBiYWNrZ3JvdW5kOiB2YXIoLS1s
eGMtc3dhdGNoLCAjY2NkM2RmKTsKICAgIG9wYWNpdHk6IC41NTsKICAgIHBvc2l0aW9uOiByZWxh
dGl2ZTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4
Yy1jb2xvci11bmF2YWlsYWJsZSA+IHNwYW46OmFmdGVyIHsKICAgIGNvbnRlbnQ6ICIiOwogICAg
cG9zaXRpb246IGFic29sdXRlOwogICAgaW5zZXQ6IDE0cHggMnB4IGF1dG87CiAgICBoZWlnaHQ6
IDJweDsKICAgIGJhY2tncm91bmQ6IHZhcigtLWx4Yy1kYW5nZXIpOwogICAgdHJhbnNmb3JtOiBy
b3RhdGUoLTQyZGVnKTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5
X3YxIl0gLmx4Yy1jb2xvci11bmF2YWlsYWJsZSBzdHJvbmcsCi5seHBkcFtkYXRhLXBkcC12YXJp
YW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtY29sb3ItdW5hdmFpbGFibGUgc21hbGwgewog
ICAgZGlzcGxheTogYmxvY2s7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xh
cml0eV92MSJdIC5seGMtc2l6ZS1ndWlkZSB7CiAgICBkaXNwbGF5OiBpbmxpbmUtZmxleDsKICAg
IGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAgICBnYXA6IDZweDsKICAgIHBhZGRpbmc6IDA7CiAgICBi
b3JkZXI6IDA7CiAgICBjb2xvcjogdmFyKC0tbHhjLXByaW1hcnkpOwogICAgYmFja2dyb3VuZDog
dHJhbnNwYXJlbnQ7CiAgICBmb250LXNpemU6IDEycHg7CiAgICBmb250LXdlaWdodDogODUwOwog
ICAgY3Vyc29yOiBwb2ludGVyOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2Ns
YXJpdHlfdjEiXSAubHhjLXNpemUtZ3VpZGUgc3ZnIHsKICAgIHdpZHRoOiAxNnB4OwogICAgaGVp
Z2h0OiAxNnB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEi
XSAubHhjLXNpemUtbGlzdCB7CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ3JpZC10ZW1wbGF0ZS1j
b2x1bW5zOiByZXBlYXQoNCwgbWlubWF4KDAsIDFmcikpOwogICAgZ2FwOiA5cHg7Cn0KCi5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtc2l6ZS1saXN0IC5s
eHBkcC1zaXplLWJ1dHRvbiB7CiAgICBwb3NpdGlvbjogcmVsYXRpdmU7CiAgICBtaW4taGVpZ2h0
OiA0OHB4OwogICAgcGFkZGluZzogMCA4cHg7CiAgICBib3JkZXI6IDFweCBzb2xpZCB2YXIoLS1s
eGMtbGluZSk7CiAgICBib3JkZXItcmFkaXVzOiAxM3B4OwogICAgY29sb3I6IHZhcigtLWx4Yy1p
bmspOwogICAgYmFja2dyb3VuZDogI2ZmZjsKICAgIGZvbnQtc2l6ZTogMTNweDsKICAgIGZvbnQt
d2VpZ2h0OiA5MDA7CiAgICBjdXJzb3I6IHBvaW50ZXI7CiAgICB0cmFuc2l0aW9uOiBib3JkZXIt
Y29sb3IgLjJzIGVhc2UsIGJhY2tncm91bmQgLjJzIGVhc2UsIGNvbG9yIC4ycyBlYXNlLCB0cmFu
c2Zvcm0gLjJzIGVhc2U7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0
eV92MSJdIC5seGMtc2l6ZS1saXN0IC5seHBkcC1zaXplLWJ1dHRvbjpob3Zlcjpub3QoOmRpc2Fi
bGVkKSB7CiAgICBib3JkZXItY29sb3I6IHZhcigtLWx4Yy1wcmltYXJ5KTsKICAgIHRyYW5zZm9y
bTogdHJhbnNsYXRlWSgtMXB4KTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19j
bGFyaXR5X3YxIl0gLmx4Yy1zaXplLWxpc3QgLmx4cGRwLXNpemUtYnV0dG9uLmlzLWFjdGl2ZSB7
CiAgICBib3JkZXItY29sb3I6IHZhcigtLWx4Yy1wcmltYXJ5KTsKICAgIGNvbG9yOiAjZmZmOwog
ICAgYmFja2dyb3VuZDogdmFyKC0tbHhjLXByaW1hcnkpOwogICAgYm94LXNoYWRvdzogMCA5cHgg
MjBweCByZ2JhKDkxLCA5NSwgMjQyLCAuMjIpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0i
c3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLXNpemUtbGlzdCAubHhwZHAtc2l6ZS1idXR0b246ZGlz
YWJsZWQgewogICAgY29sb3I6ICM5YmEzYjI7CiAgICBiYWNrZ3JvdW5kOgogICAgICAgIGxpbmVh
ci1ncmFkaWVudCgKICAgICAgICAgICAgdG8gYm90dG9tIHJpZ2h0LAogICAgICAgICAgICB0cmFu
c3BhcmVudCBjYWxjKDUwJSAtIDFweCksCiAgICAgICAgICAgIHJnYmEoMTg5LCA0OSwgODAsIC43
KSA1MCUsCiAgICAgICAgICAgIHRyYW5zcGFyZW50IGNhbGMoNTAlICsgMXB4KQogICAgICAgICks
CiAgICAgICAgI2YzZjVmODsKICAgIGN1cnNvcjogbm90LWFsbG93ZWQ7Cn0KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtc2VsZWN0aW9uIHsKICAgIGRp
c3BsYXk6IGZsZXg7CiAgICBqdXN0aWZ5LWNvbnRlbnQ6IHNwYWNlLWJldHdlZW47CiAgICBnYXA6
IDEwcHg7CiAgICBjb2xvcjogdmFyKC0tbHhjLW11dGVkKTsKICAgIGZvbnQtc2l6ZTogMTJweDsK
fQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gW2RhdGEtbHhw
ZHAtc2VsZWN0ZWQtc3RvY2tdIHsKICAgIGRpc3BsYXk6IG5vbmUgIWltcG9ydGFudDsKfQoKLmx4
cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1jYXJ0IHsKICAg
IG1hcmdpbjogMDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3Yx
Il0gLmx4Yy1idXktYnV0dG9uIHsKICAgIHdpZHRoOiAxMDAlOwogICAgbWluLWhlaWdodDogNTZw
eDsKICAgIGJvcmRlcjogMDsKICAgIGJvcmRlci1yYWRpdXM6IDE2cHg7CiAgICBjb2xvcjogI2Zm
ZjsKICAgIGJhY2tncm91bmQ6IGxpbmVhci1ncmFkaWVudCgxMzVkZWcsIHZhcigtLWx4Yy1zaWdu
YWwpLCAjZmY2ZjkwKTsKICAgIGJveC1zaGFkb3c6IDAgMTVweCAzNHB4IHJnYmEoMjU1LCA2NSwg
MTA4LCAuMjQpOwogICAgZm9udC1zaXplOiAxNHB4OwogICAgZm9udC13ZWlnaHQ6IDkwMDsKICAg
IGxldHRlci1zcGFjaW5nOiAuMDE1ZW07CiAgICBjdXJzb3I6IHBvaW50ZXI7CiAgICB0cmFuc2l0
aW9uOiB0cmFuc2Zvcm0gLjJzIGVhc2UsIGJveC1zaGFkb3cgLjJzIGVhc2UsIGZpbHRlciAuMnMg
ZWFzZTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4
Yy1idXktYnV0dG9uOmhvdmVyOm5vdCg6ZGlzYWJsZWQpIHsKICAgIHRyYW5zZm9ybTogdHJhbnNs
YXRlWSgtMnB4KTsKICAgIGJveC1zaGFkb3c6IDAgMjBweCA0MnB4IHJnYmEoMjU1LCA2NSwgMTA4
LCAuMzIpOwogICAgZmlsdGVyOiBzYXR1cmF0ZSgxLjA1KTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1idXktYnV0dG9uOmRpc2FibGVkIHsKICAg
IGNvbG9yOiAjOGY5OGE4OwogICAgYmFja2dyb3VuZDogI2U5ZWRmMzsKICAgIGJveC1zaGFkb3c6
IG5vbmU7CiAgICBjdXJzb3I6IG5vdC1hbGxvd2VkOwp9CgovKiBQcm9kdWN0IGNsYXJpdHkgYW5n
bGVzICovCgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhw
ZHAtZW5naW5lLXNlY3Rpb24tLWNsYXJpdHlfcHJvZHVjdF9hbmdsZXMgewogICAgYmFja2dyb3Vu
ZDogdmFyKC0tbHhjLWRhcmspOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2Ns
YXJpdHlfdjEiXSAubHhjLWFuZ2xlcyB7CiAgICBwb3NpdGlvbjogcmVsYXRpdmU7CiAgICBwYWRk
aW5nOiBjbGFtcCg3MnB4LCA4dncsIDExOHB4KSAwIGNsYW1wKDg2cHgsIDEwdncsIDE0MnB4KTsK
ICAgIGNvbG9yOiAjZmZmOwogICAgb3ZlcmZsb3c6IGhpZGRlbjsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZXM6OmJlZm9yZSB7CiAgICBj
b250ZW50OiAiIjsKICAgIHBvc2l0aW9uOiBhYnNvbHV0ZTsKICAgIHdpZHRoOiAzOHJlbTsKICAg
IGhlaWdodDogMzhyZW07CiAgICB0b3A6IC0xOHJlbTsKICAgIHJpZ2h0OiAtMTJyZW07CiAgICBi
b3JkZXItcmFkaXVzOiA1MCU7CiAgICBiYWNrZ3JvdW5kOiByYWRpYWwtZ3JhZGllbnQoY2lyY2xl
LCByZ2JhKDkxLCA5NSwgMjQyLCAuMzQpLCB0cmFuc3BhcmVudCA2OCUpOwogICAgcG9pbnRlci1l
dmVudHM6IG5vbmU7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92
MSJdIC5seGMtYW5nbGVzOjphZnRlciB7CiAgICBjb250ZW50OiAiIjsKICAgIHBvc2l0aW9uOiBh
YnNvbHV0ZTsKICAgIHdpZHRoOiAyOHJlbTsKICAgIGhlaWdodDogMjhyZW07CiAgICBib3R0b206
IC0xNnJlbTsKICAgIGxlZnQ6IC0xMHJlbTsKICAgIGJvcmRlci1yYWRpdXM6IDUwJTsKICAgIGJh
Y2tncm91bmQ6IHJhZGlhbC1ncmFkaWVudChjaXJjbGUsIHJnYmEoMjU1LCA2NSwgMTA4LCAuMTkp
LCB0cmFuc3BhcmVudCA3MCUpOwogICAgcG9pbnRlci1ldmVudHM6IG5vbmU7Cn0KCi5seHBkcFtk
YXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGVzIC5seGMtc2hl
bGwgewogICAgcG9zaXRpb246IHJlbGF0aXZlOwogICAgei1pbmRleDogMTsKfQoKLmx4cGRwW2Rh
dGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZXNfX2hlYWRlciB7
CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiBtaW5tYXgoMCwg
MS4xNWZyKSBtaW5tYXgoMzAwcHgsIC41NWZyKTsKICAgIGdhcDogY2xhbXAoMzJweCwgNnZ3LCAx
MDBweCk7CiAgICBhbGlnbi1pdGVtczogZW5kOwogICAgbWFyZ2luLWJvdHRvbTogMzRweDsKfQoK
Lmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZXNf
X2hlYWRlciBoMiB7CiAgICBtYXJnaW46IDA7CiAgICBtYXgtd2lkdGg6IDg1MHB4OwogICAgZm9u
dC1zaXplOiBjbGFtcCgzNnB4LCA0Ljd2dywgNjZweCk7CiAgICBmb250LXdlaWdodDogODgwOwog
ICAgbGluZS1oZWlnaHQ6IC45ODsKICAgIGxldHRlci1zcGFjaW5nOiAtLjA1NWVtOwp9CgoubHhw
ZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xlc19faW50
cm8gewogICAgZGlzcGxheTogZ3JpZDsKICAgIGdhcDogMTBweDsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZXNfX2ludHJvID4gc3BhbiB7
CiAgICB3aWR0aDogbWF4LWNvbnRlbnQ7CiAgICBwYWRkaW5nOiA3cHggMTFweDsKICAgIGJvcmRl
cjogMXB4IHNvbGlkIHJnYmEoMjU1LCAyNTUsIDI1NSwgLjE0KTsKICAgIGJvcmRlci1yYWRpdXM6
IDk5OXB4OwogICAgY29sb3I6ICNmZmY7CiAgICBiYWNrZ3JvdW5kOiByZ2JhKDI1NSwgMjU1LCAy
NTUsIC4wOCk7CiAgICBmb250LXNpemU6IDExcHg7CiAgICBmb250LXdlaWdodDogOTAwOwogICAg
bGV0dGVyLXNwYWNpbmc6IC4wNmVtOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlv
X2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xlc19faW50cm8gcCB7CiAgICBtYXJnaW46IDA7CiAgICBj
b2xvcjogI2FlYjdjODsKICAgIGZvbnQtc2l6ZTogMTVweDsKICAgIGxpbmUtaGVpZ2h0OiAxLjY1
Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFu
Z2xlLW5hdiB7CiAgICBkaXNwbGF5OiBmbGV4OwogICAgZ2FwOiA4cHg7CiAgICBtYXJnaW4tYm90
dG9tOiAyMnB4OwogICAgb3ZlcmZsb3cteDogYXV0bzsKICAgIHBhZGRpbmc6IDJweCAycHggOHB4
OwogICAgc2Nyb2xsYmFyLXdpZHRoOiBub25lOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0i
c3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xlLW5hdjo6LXdlYmtpdC1zY3JvbGxiYXIgewog
ICAgZGlzcGxheTogbm9uZTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFy
aXR5X3YxIl0gLmx4Yy1hbmdsZS1uYXYgYnV0dG9uIHsKICAgIGZsZXg6IDAgMCBhdXRvOwogICAg
bWluLWhlaWdodDogMzhweDsKICAgIGRpc3BsYXk6IGlubGluZS1mbGV4OwogICAgYWxpZ24taXRl
bXM6IGNlbnRlcjsKICAgIGdhcDogOHB4OwogICAgcGFkZGluZzogMCAxM3B4OwogICAgYm9yZGVy
OiAxcHggc29saWQgcmdiYSgyNTUsIDI1NSwgMjU1LCAuMTIpOwogICAgYm9yZGVyLXJhZGl1czog
OTk5cHg7CiAgICBjb2xvcjogI2RjZTJlZTsKICAgIGJhY2tncm91bmQ6IHJnYmEoMjU1LCAyNTUs
IDI1NSwgLjA2KTsKICAgIGZvbnQtc2l6ZTogMTFweDsKICAgIGZvbnQtd2VpZ2h0OiA4MDA7CiAg
ICBjdXJzb3I6IHBvaW50ZXI7CiAgICB0cmFuc2l0aW9uOiBib3JkZXItY29sb3IgLjJzIGVhc2Us
IGJhY2tncm91bmQgLjJzIGVhc2UsIGNvbG9yIC4ycyBlYXNlOwp9CgoubHhwZHBbZGF0YS1wZHAt
dmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xlLW5hdiBidXR0b246aG92ZXIg
ewogICAgYm9yZGVyLWNvbG9yOiByZ2JhKDExOSwgMTI0LCAyNTUsIC43Mik7CiAgICBjb2xvcjog
I2ZmZjsKICAgIGJhY2tncm91bmQ6IHJnYmEoOTEsIDk1LCAyNDIsIC4xOCk7Cn0KCi5seHBkcFtk
YXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGUtbmF2IGJ1dHRv
biBzcGFuIHsKICAgIGNvbG9yOiAjODg4ZGZjOwogICAgZm9udC1zaXplOiA5cHg7CiAgICBsZXR0
ZXItc3BhY2luZzogLjA4ZW07Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xh
cml0eV92MSJdIC5seGMtYW5nbGUtZ3JpZCB7CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ3JpZC10
ZW1wbGF0ZS1jb2x1bW5zOiByZXBlYXQoMTIsIG1pbm1heCgwLCAxZnIpKTsKICAgIGdyaWQtYXV0
by1mbG93OiBkZW5zZTsKICAgIGdhcDogMThweDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9
InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZS1jYXJkIHsKICAgIG1pbi13aWR0aDogMDsK
ICAgIG1hcmdpbjogMDsKICAgIG92ZXJmbG93OiBoaWRkZW47CiAgICBib3JkZXI6IDFweCBzb2xp
ZCByZ2JhKDI1NSwgMjU1LCAyNTUsIC4wOSk7CiAgICBib3JkZXItcmFkaXVzOiAyNnB4OwogICAg
YmFja2dyb3VuZDogdmFyKC0tbHhjLWRhcmstc29mdCk7CiAgICBib3gtc2hhZG93OiAwIDI0cHgg
NzBweCByZ2JhKDAsIDAsIDAsIC4yMik7CiAgICBvcGFjaXR5OiAwOwogICAgdHJhbnNmb3JtOiB0
cmFuc2xhdGVZKDE4cHgpOwogICAgdHJhbnNpdGlvbjogb3BhY2l0eSAuNTVzIGVhc2UsIHRyYW5z
Zm9ybSAuNTVzIGVhc2UsIGJvcmRlci1jb2xvciAuMjVzIGVhc2U7Cn0KCi5seHBkcFtkYXRhLXBk
cC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGUtY2FyZC5pcy12aXNpYmxl
LAoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xl
LWdyaWQuaXMtdmlzaWJsZSAubHhjLWFuZ2xlLWNhcmQgewogICAgb3BhY2l0eTogMTsKICAgIHRy
YW5zZm9ybTogdHJhbnNsYXRlWSgwKTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRp
b19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZS1jYXJkOmhvdmVyIHsKICAgIGJvcmRlci1jb2xvcjog
cmdiYSg5MSwgOTUsIDI0MiwgLjU1KTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRp
b19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZS1jYXJkLS0xIHsKICAgIGdyaWQtY29sdW1uOiBzcGFu
IDc7CiAgICBncmlkLXJvdzogc3BhbiAyOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1
ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xlLWNhcmQtLTIgewogICAgZ3JpZC1jb2x1bW46IHNw
YW4gNTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4
Yy1hbmdsZS1jYXJkLS0zIHsKICAgIGdyaWQtY29sdW1uOiBzcGFuIDU7Cn0KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGUtY2FyZC0tNCwKLmx4
cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZS1jYXJk
LS01LAoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFu
Z2xlLWNhcmQtLTYgewogICAgZ3JpZC1jb2x1bW46IHNwYW4gNDsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZS1jYXJkLS03LAoubHhwZHBb
ZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xlLWNhcmQtLTgg
ewogICAgZ3JpZC1jb2x1bW46IHNwYW4gNjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZS1jYXJkX19tZWRpYSB7CiAgICBwb3NpdGlvbjog
cmVsYXRpdmU7CiAgICBtaW4taGVpZ2h0OiA0NzBweDsKICAgIG92ZXJmbG93OiBoaWRkZW47CiAg
ICBiYWNrZ3JvdW5kOiAjMmEyZjNkOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlv
X2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xlLWNhcmQtLTEgLmx4Yy1hbmdsZS1jYXJkX19tZWRpYSB7
CiAgICBtaW4taGVpZ2h0OiA3ODBweDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRp
b19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZS1jYXJkX19tZWRpYSBpbWcgewogICAgd2lkdGg6IDEw
MCU7CiAgICBoZWlnaHQ6IDEwMCU7CiAgICBkaXNwbGF5OiBibG9jazsKICAgIG9iamVjdC1maXQ6
IGNvdmVyOwogICAgb2JqZWN0LXBvc2l0aW9uOiBjZW50ZXIgdG9wOwogICAgdHJhbnNpdGlvbjog
dHJhbnNmb3JtIC43cyBjdWJpYy1iZXppZXIoLjIsIC44LCAuMiwgMSk7Cn0KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGUtY2FyZDpob3ZlciAu
bHhjLWFuZ2xlLWNhcmRfX21lZGlhIGltZyB7CiAgICB0cmFuc2Zvcm06IHNjYWxlKDEuMDI1KTsK
fQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbmds
ZS1jYXJkX19tZWRpYSA+IHNwYW4gewogICAgcG9zaXRpb246IGFic29sdXRlOwogICAgdG9wOiAx
NHB4OwogICAgbGVmdDogMTRweDsKICAgIG1pbi13aWR0aDogMzhweDsKICAgIGhlaWdodDogMzJw
eDsKICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBwbGFjZS1pdGVtczogY2VudGVyOwogICAgcGFkZGlu
Zy1pbmxpbmU6IDhweDsKICAgIGJvcmRlcjogMXB4IHNvbGlkIHJnYmEoMjU1LCAyNTUsIDI1NSwg
LjIyKTsKICAgIGJvcmRlci1yYWRpdXM6IDk5OXB4OwogICAgY29sb3I6ICNmZmY7CiAgICBiYWNr
Z3JvdW5kOiByZ2JhKDE0LCAxNywgMjUsIC41Mik7CiAgICBiYWNrZHJvcC1maWx0ZXI6IGJsdXIo
MTJweCk7CiAgICBmb250LXNpemU6IDEwcHg7CiAgICBmb250LXdlaWdodDogOTAwOwogICAgbGV0
dGVyLXNwYWNpbmc6IC4xZW07Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xh
cml0eV92MSJdIC5seGMtYW5nbGUtY2FyZCBmaWdjYXB0aW9uIHsKICAgIGRpc3BsYXk6IGdyaWQ7
CiAgICBnYXA6IDVweDsKICAgIHBhZGRpbmc6IDE4cHggMjBweCAyMXB4Owp9CgoubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xlLWNhcmQgZmlnY2Fw
dGlvbiBzbWFsbCB7CiAgICBjb2xvcjogIzg0OGJmZDsKICAgIGZvbnQtc2l6ZTogOXB4OwogICAg
Zm9udC13ZWlnaHQ6IDkwMDsKICAgIGxldHRlci1zcGFjaW5nOiAuMTVlbTsKICAgIHRleHQtdHJh
bnNmb3JtOiB1cHBlcmNhc2U7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xh
cml0eV92MSJdIC5seGMtYW5nbGUtY2FyZCBmaWdjYXB0aW9uIGgzIHsKICAgIG1hcmdpbjogMDsK
ICAgIGNvbG9yOiAjZmZmOwogICAgZm9udC1zaXplOiAyMHB4OwogICAgZm9udC13ZWlnaHQ6IDg1
MDsKICAgIGxldHRlci1zcGFjaW5nOiAtLjAyNWVtOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFu
dD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xlLWNhcmQgZmlnY2FwdGlvbiBwIHsKICAg
IG1hcmdpbjogMDsKICAgIGNvbG9yOiAjOWRhN2I5OwogICAgZm9udC1zaXplOiAxM3B4OwogICAg
bGluZS1oZWlnaHQ6IDEuNTU7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xh
cml0eV92MSJdIC5seGMtYW5nbGVzX19lbXB0eSB7CiAgICBkaXNwbGF5OiBmbGV4OwogICAgYWxp
Z24taXRlbXM6IGNlbnRlcjsKICAgIGdhcDogMThweDsKICAgIG1heC13aWR0aDogNzYwcHg7CiAg
ICBwYWRkaW5nOiAyNnB4OwogICAgYm9yZGVyOiAxcHggc29saWQgcmdiYSgyNTUsIDI1NSwgMjU1
LCAuMSk7CiAgICBib3JkZXItcmFkaXVzOiAyNHB4OwogICAgYmFja2dyb3VuZDogcmdiYSgyNTUs
IDI1NSwgMjU1LCAuMDYpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJp
dHlfdjEiXSAubHhjLWFuZ2xlc19fZW1wdHkgPiBzcGFuIHsKICAgIHdpZHRoOiA1OHB4OwogICAg
aGVpZ2h0OiA1OHB4OwogICAgZmxleDogMCAwIGF1dG87CiAgICBkaXNwbGF5OiBncmlkOwogICAg
cGxhY2UtaXRlbXM6IGNlbnRlcjsKICAgIGJvcmRlci1yYWRpdXM6IDE4cHg7CiAgICBjb2xvcjog
IzhkOTJmZjsKICAgIGJhY2tncm91bmQ6IHJnYmEoOTEsIDk1LCAyNDIsIC4xNik7Cn0KCi5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGVzX19lbXB0
eSBzdmcgewogICAgd2lkdGg6IDMwcHg7CiAgICBoZWlnaHQ6IDMwcHg7Cn0KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGVzX19lbXB0eSBzdHJv
bmcgewogICAgZGlzcGxheTogYmxvY2s7CiAgICBmb250LXNpemU6IDE3cHg7Cn0KCi5seHBkcFtk
YXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGVzX19lbXB0eSBw
IHsKICAgIG1hcmdpbjogNnB4IDAgMDsKICAgIGNvbG9yOiAjYWViN2M4OwogICAgZm9udC1zaXpl
OiAxM3B4OwogICAgbGluZS1oZWlnaHQ6IDEuNTU7Cn0KCi8qIEZpeGVkIGNvbW1lcmNlIGRvY2sg
Ki8KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZG9j
ayB7CiAgICBkaXNwbGF5OiBub25lOwp9CgovKiBTaXplIGFkdmlzb3IgcmVtYWlucyBhdmFpbGFi
bGUgZnJvbSB0aGUgc2l6ZSBzZWxlY3RvciwgYnV0IG5vIG90aGVyIFBEUCBzZWN0aW9uIGlzIHJl
bmRlcmVkLiAqLwoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0g
Lmx4cGRwLWFkdmlzb3IgewogICAgYm9yZGVyOiAwOwogICAgYm9yZGVyLXJhZGl1czogMjZweDsK
ICAgIGNvbG9yOiB2YXIoLS1seGMtaW5rKTsKICAgIGJhY2tncm91bmQ6ICNmZmY7CiAgICBib3gt
c2hhZG93OiAwIDM0cHggMTAwcHggcmdiYSgxMywgMTgsIDM0LCAuMjQpOwp9CgoubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhwZHAtYWR2aXNvcl9faGVhZGVy
IHsKICAgIGJvcmRlci1ib3R0b206IDFweCBzb2xpZCB2YXIoLS1seGMtbGluZSk7Cn0KCi5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seHBkcC1hZHZpc29yX19z
dWJtaXQgewogICAgYm9yZGVyOiAwOwogICAgY29sb3I6ICNmZmY7CiAgICBiYWNrZ3JvdW5kOiB2
YXIoLS1seGMtcHJpbWFyeSk7Cn0KCkBtZWRpYSAobWF4LXdpZHRoOiAxMTIwcHgpIHsKICAgIC5s
eHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtaGVybyB7CiAg
ICAgICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiBtaW5tYXgoMCwgMWZyKSBtaW5tYXgoMzYwcHgs
IC43MmZyKTsKICAgICAgICBnYXA6IDI4cHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1idXkgewogICAgICAgIHBhZGRpbmc6IDIy
cHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3Yx
Il0gLmx4Yy1hbmdsZS1jYXJkX19tZWRpYSB7CiAgICAgICAgbWluLWhlaWdodDogMzkwcHg7CiAg
ICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4
Yy1hbmdsZS1jYXJkLS0xIC5seGMtYW5nbGUtY2FyZF9fbWVkaWEgewogICAgICAgIG1pbi1oZWln
aHQ6IDY2MHB4OwogICAgfQp9CgpAbWVkaWEgKG1heC13aWR0aDogOTAwcHgpIHsKICAgIC5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtaGVybyB7CiAgICAg
ICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiAxZnI7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1idXkgewogICAgICAgIHBvc2l0aW9u
OiBzdGF0aWM7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFy
aXR5X3YxIl0gLmx4Yy1nYWxsZXJ5X19zdGFnZSB7CiAgICAgICAgaGVpZ2h0OiBhdXRvOwogICAg
ICAgIG1pbi1oZWlnaHQ6IDA7CiAgICAgICAgYXNwZWN0LXJhdGlvOiA0IC8gNTsKICAgIH0KCiAg
ICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xl
c19faGVhZGVyIHsKICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IDFmcjsKICAgICAgICBn
YXA6IDIwcHg7CiAgICB9Cn0KCkBtZWRpYSAobWF4LXdpZHRoOiA3ODBweCkgewogICAgYm9keTpo
YXMoLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0pIC5seHYyLW1h
aW4sCiAgICBib2R5Lmx4LXBkcC1zdHVkaW8tY2xhcml0eSAubHh2Mi1tYWluIHsKICAgICAgICB3
aWR0aDogMTAwJTsKICAgICAgICBwYWRkaW5nLWJvdHRvbTogY2FsYyg5MnB4ICsgZW52KHNhZmUt
YXJlYS1pbnNldC1ib3R0b20pKTsKICAgIH0KCiAgICBib2R5OmhhcygubHhwZHBbZGF0YS1wZHAt
dmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSkgLmx4djItZm9vdGVyLAogICAgYm9keS5seC1w
ZHAtc3R1ZGlvLWNsYXJpdHkgLmx4djItZm9vdGVyIHsKICAgICAgICBtYXJnaW4tYm90dG9tOiBj
YWxjKDc4cHggKyBlbnYoc2FmZS1hcmVhLWluc2V0LWJvdHRvbSkpOwogICAgfQoKICAgIGJvZHk6
aGFzKC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdKSAubHh2Mi1i
b3R0b20tbmF2LAogICAgYm9keS5seC1wZHAtc3R1ZGlvLWNsYXJpdHkgLmx4djItYm90dG9tLW5h
diwKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seHBk
cC1tb2JpbGUtYnV5IHsKICAgICAgICBkaXNwbGF5OiBub25lICFpbXBvcnRhbnQ7CiAgICB9Cgog
ICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4cGRwLXBy
ZXZpZXctYmFubmVyIHsKICAgICAgICBtYXJnaW4taW5saW5lOiAxNHB4OwogICAgfQoKICAgIC5s
eHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seHBkcF9fYnJlYWRj
cnVtYiwKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5s
eGMtc2hlbGwgewogICAgICAgIHdpZHRoOiBjYWxjKDEwMCUgLSAyOHB4KTsKICAgIH0KCiAgICAu
bHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhwZHAtZW5naW5l
LXNlY3Rpb24tLWNsYXJpdHlfaGVyb19wdXJjaGFzZSB7CiAgICAgICAgcGFkZGluZy1ib3R0b206
IDU2cHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5
X3YxIl0gLmx4Yy1nYWxsZXJ5X19zdGFnZSB7CiAgICAgICAgYm9yZGVyLXJhZGl1czogMjRweDsK
ICAgICAgICBib3gtc2hhZG93OiAwIDIwcHggNTZweCByZ2JhKDIwLCAyOSwgNTIsIC4xMyk7CiAg
ICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4
Yy1nYWxsZXJ5X19uYXYgewogICAgICAgIHdpZHRoOiA0MHB4OwogICAgICAgIGhlaWdodDogNDBw
eDsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEi
XSAubHhjLWdhbGxlcnlfX3RodW1iIHsKICAgICAgICBmbGV4LWJhc2lzOiA2NHB4OwogICAgICAg
IGhlaWdodDogODBweDsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlv
X2NsYXJpdHlfdjEiXSAubHhjLWJ1eSB7CiAgICAgICAgZ2FwOiAxOXB4OwogICAgICAgIHBhZGRp
bmc6IDIwcHggMThweDsKICAgICAgICBib3JkZXItcmFkaXVzOiAyNHB4OwogICAgfQoKICAgIC5s
eHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYnV5X19oZWFk
IGgxIHsKICAgICAgICBmb250LXNpemU6IGNsYW1wKDM4cHgsIDEydncsIDU0cHgpOwogICAgfQoK
ICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtY29s
b3IgewogICAgICAgIGZsZXgtYmFzaXM6IDY2cHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1jb2xvcl9fdmlzdWFsIHsKICAgICAg
ICB3aWR0aDogNjRweDsKICAgICAgICBoZWlnaHQ6IDc1cHg7CiAgICB9CgogICAgLmx4cGRwW2Rh
dGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1zaXplLWxpc3QgewogICAg
ICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogcmVwZWF0KDQsIDFmcik7CiAgICB9CgogICAgLmx4
cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZXMgewog
ICAgICAgIHBhZGRpbmctYmxvY2s6IDY0cHggOTBweDsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1w
ZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWFuZ2xlc19faGVhZGVyIGgyIHsK
ICAgICAgICBmb250LXNpemU6IGNsYW1wKDMycHgsIDEwdncsIDQ1cHgpOwogICAgICAgIGxpbmUt
aGVpZ2h0OiAxLjAyOwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9f
Y2xhcml0eV92MSJdIC5seGMtYW5nbGUtZ3JpZCB7CiAgICAgICAgZ3JpZC10ZW1wbGF0ZS1jb2x1
bW5zOiAxZnI7CiAgICAgICAgZ2FwOiAxNHB4OwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12
YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGUtY2FyZCwKICAgIC5seHBkcFtk
YXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGUtY2FyZC0tMSwK
ICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5n
bGUtY2FyZC0tMiwKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92
MSJdIC5seGMtYW5nbGUtY2FyZC0tMywKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVk
aW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGUtY2FyZC0tNCwKICAgIC5seHBkcFtkYXRhLXBkcC12
YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGUtY2FyZC0tNSwKICAgIC5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGUtY2FyZC0t
NiwKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMt
YW5nbGUtY2FyZC0tNywKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0
eV92MSJdIC5seGMtYW5nbGUtY2FyZC0tOCB7CiAgICAgICAgZ3JpZC1jb2x1bW46IGF1dG87CiAg
ICAgICAgZ3JpZC1yb3c6IGF1dG87CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9
InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1hbmdsZS1jYXJkX19tZWRpYSwKICAgIC5seHBkcFtk
YXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtYW5nbGUtY2FyZC0tMSAu
bHhjLWFuZ2xlLWNhcmRfX21lZGlhIHsKICAgICAgICBtaW4taGVpZ2h0OiAwOwogICAgICAgIGFz
cGVjdC1yYXRpbzogNCAvIDU7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1kb2NrIHsKICAgICAgICBwb3NpdGlvbjogZml4ZWQ7CiAg
ICAgICAgei1pbmRleDogNTAwOwogICAgICAgIGxlZnQ6IDA7CiAgICAgICAgcmlnaHQ6IDA7CiAg
ICAgICAgYm90dG9tOiAwOwogICAgICAgIGRpc3BsYXk6IGJsb2NrOwogICAgICAgIGNvbG9yOiAj
ZmZmOwogICAgICAgIGJhY2tncm91bmQ6CiAgICAgICAgICAgIGxpbmVhci1ncmFkaWVudCgxODBk
ZWcsIHJnYmEoMTcsIDIwLCAyOSwgLjk0KSwgcmdiYSgxMCwgMTIsIDE4LCAuOTkpKTsKICAgICAg
ICBib3gtc2hhZG93OiAwIC0yMHB4IDYwcHggcmdiYSg4LCAxMSwgMTgsIC4yOCk7CiAgICAgICAg
YmFja2Ryb3AtZmlsdGVyOiBibHVyKDIycHgpIHNhdHVyYXRlKDE1NSUpOwogICAgfQoKICAgIC5s
eHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZG9jazo6YmVm
b3JlIHsKICAgICAgICBjb250ZW50OiAiIjsKICAgICAgICBwb3NpdGlvbjogYWJzb2x1dGU7CiAg
ICAgICAgbGVmdDogMDsKICAgICAgICByaWdodDogMDsKICAgICAgICB0b3A6IDA7CiAgICAgICAg
aGVpZ2h0OiAycHg7CiAgICAgICAgYmFja2dyb3VuZDogbGluZWFyLWdyYWRpZW50KDkwZGVnLCB0
cmFuc3BhcmVudCwgIzZlNzJmZiAyMiUsICNmZjQxNmMgNzIlLCB0cmFuc3BhcmVudCk7CiAgICAg
ICAgYm94LXNoYWRvdzogMCAwIDIycHggcmdiYSg5MSwgOTUsIDI0MiwgLjYpOwogICAgfQoKICAg
IC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZG9ja19f
aW5uZXIgewogICAgICAgIHdpZHRoOiBtaW4oNzYwcHgsIDEwMCUpOwogICAgICAgIG1pbi1oZWln
aHQ6IDY4cHg7CiAgICAgICAgZGlzcGxheTogZ3JpZDsKICAgICAgICBncmlkLXRlbXBsYXRlLWNv
bHVtbnM6IHJlcGVhdCgzLCA0NHB4KSBtaW5tYXgoODRweCwgLjcyZnIpIG1pbm1heCgxMzJweCwg
MS4yZnIpOwogICAgICAgIGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAgICAgICAgZ2FwOiA2cHg7CiAg
ICAgICAgbWFyZ2luLWlubGluZTogYXV0bzsKICAgICAgICBwYWRkaW5nOiA4cHggMTBweCBjYWxj
KDhweCArIGVudihzYWZlLWFyZWEtaW5zZXQtYm90dG9tKSk7CiAgICB9CgogICAgLmx4cGRwW2Rh
dGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1kb2NrX19pY29uIHsKICAg
ICAgICB3aWR0aDogNDJweDsKICAgICAgICBoZWlnaHQ6IDQycHg7CiAgICAgICAgZGlzcGxheTog
Z3JpZDsKICAgICAgICBwbGFjZS1pdGVtczogY2VudGVyOwogICAgICAgIGJvcmRlcjogMXB4IHNv
bGlkIHJnYmEoMjU1LCAyNTUsIDI1NSwgLjEpOwogICAgICAgIGJvcmRlci1yYWRpdXM6IDE0cHg7
CiAgICAgICAgY29sb3I6ICNkN2RiZWE7CiAgICAgICAgYmFja2dyb3VuZDogcmdiYSgyNTUsIDI1
NSwgMjU1LCAuMDU1KTsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlv
X2NsYXJpdHlfdjEiXSAubHhjLWRvY2tfX2ljb246YWN0aXZlIHsKICAgICAgICBjb2xvcjogI2Zm
ZjsKICAgICAgICBiYWNrZ3JvdW5kOiByZ2JhKDkxLCA5NSwgMjQyLCAuMjgpOwogICAgfQoKICAg
IC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZG9ja19f
aWNvbiBzdmcgewogICAgICAgIHdpZHRoOiAyMXB4OwogICAgICAgIGhlaWdodDogMjFweDsKICAg
IH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhj
LWRvY2tfX3N1bW1hcnkgewogICAgICAgIG1pbi13aWR0aDogMDsKICAgICAgICBkaXNwbGF5OiBn
cmlkOwogICAgICAgIGdhcDogMnB4OwogICAgICAgIHBhZGRpbmctbGVmdDogNXB4OwogICAgfQoK
ICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZG9j
a19fc3VtbWFyeSBzdHJvbmcgewogICAgICAgIG92ZXJmbG93OiBoaWRkZW47CiAgICAgICAgZm9u
dC1zaXplOiAxNHB4OwogICAgICAgIGZvbnQtd2VpZ2h0OiA5MDA7CiAgICAgICAgdGV4dC1vdmVy
ZmxvdzogZWxsaXBzaXM7CiAgICAgICAgd2hpdGUtc3BhY2U6IG5vd3JhcDsKICAgIH0KCiAgICAu
bHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWRvY2tfX3N1
bW1hcnkgc3BhbiB7CiAgICAgICAgb3ZlcmZsb3c6IGhpZGRlbjsKICAgICAgICBjb2xvcjogIzll
YTdiYTsKICAgICAgICBmb250LXNpemU6IDlweDsKICAgICAgICBmb250LXdlaWdodDogNzUwOwog
ICAgICAgIHRleHQtb3ZlcmZsb3c6IGVsbGlwc2lzOwogICAgICAgIHdoaXRlLXNwYWNlOiBub3dy
YXA7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3Yx
Il0gLmx4Yy1kb2NrX19jdGEgewogICAgICAgIG1pbi13aWR0aDogMDsKICAgICAgICBoZWlnaHQ6
IDQ4cHg7CiAgICAgICAgZGlzcGxheTogZmxleDsKICAgICAgICBhbGlnbi1pdGVtczogY2VudGVy
OwogICAgICAgIGp1c3RpZnktY29udGVudDogY2VudGVyOwogICAgICAgIGdhcDogOHB4OwogICAg
ICAgIHBhZGRpbmc6IDAgMTNweDsKICAgICAgICBib3JkZXI6IDA7CiAgICAgICAgYm9yZGVyLXJh
ZGl1czogMTZweDsKICAgICAgICBjb2xvcjogI2ZmZjsKICAgICAgICBiYWNrZ3JvdW5kOiBsaW5l
YXItZ3JhZGllbnQoMTM1ZGVnLCB2YXIoLS1seGMtc2lnbmFsKSwgI2ZmNzE4Zik7CiAgICAgICAg
Ym94LXNoYWRvdzogMCAxMnB4IDI4cHggcmdiYSgyNTUsIDY1LCAxMDgsIC4yOCk7CiAgICAgICAg
Zm9udC1zaXplOiAxMXB4OwogICAgICAgIGZvbnQtd2VpZ2h0OiA5MDA7CiAgICAgICAgd2hpdGUt
c3BhY2U6IG5vd3JhcDsKICAgICAgICBjdXJzb3I6IHBvaW50ZXI7CiAgICB9CgogICAgLmx4cGRw
W2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1kb2NrX19jdGEgc3Zn
IHsKICAgICAgICB3aWR0aDogMTdweDsKICAgICAgICBoZWlnaHQ6IDE3cHg7CiAgICB9CgogICAg
Lmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1kb2NrX19j
dGE6ZGlzYWJsZWQgewogICAgICAgIGNvbG9yOiAjODc5MGEzOwogICAgICAgIGJhY2tncm91bmQ6
ICMyYjMwM2M7CiAgICAgICAgYm94LXNoYWRvdzogbm9uZTsKICAgICAgICBjdXJzb3I6IG5vdC1h
bGxvd2VkOwogICAgfQp9CgpAbWVkaWEgKG1heC13aWR0aDogNDMwcHgpIHsKICAgIC5seHBkcFtk
YXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZG9ja19faW5uZXIgewog
ICAgICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogcmVwZWF0KDMsIDQwcHgpIG1pbm1heCg2MnB4
LCAuNmZyKSBtaW5tYXgoMTIwcHgsIDEuMjVmcik7CiAgICAgICAgZ2FwOiA0cHg7CiAgICAgICAg
cGFkZGluZy1pbmxpbmU6IDdweDsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0i
c3R1ZGlvX2NsYXJpdHlfdjEiXSAubHhjLWRvY2tfX2ljb24gewogICAgICAgIHdpZHRoOiAzOXB4
OwogICAgICAgIGhlaWdodDogMzlweDsKICAgICAgICBib3JkZXItcmFkaXVzOiAxM3B4OwogICAg
fQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMt
ZG9ja19fc3VtbWFyeSB7CiAgICAgICAgcGFkZGluZy1sZWZ0OiAycHg7CiAgICB9CgogICAgLmx4
cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gLmx4Yy1kb2NrX19zdW1t
YXJ5IHN0cm9uZyB7CiAgICAgICAgZm9udC1zaXplOiAxMnB4OwogICAgfQoKICAgIC5seHBkcFtk
YXRhLXBkcC12YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZG9ja19fc3VtbWFyeSBz
cGFuIHsKICAgICAgICBkaXNwbGF5OiBub25lOwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12
YXJpYW50PSJzdHVkaW9fY2xhcml0eV92MSJdIC5seGMtZG9ja19fY3RhIHsKICAgICAgICBoZWln
aHQ6IDQ1cHg7CiAgICAgICAgcGFkZGluZy1pbmxpbmU6IDlweDsKICAgICAgICBmb250LXNpemU6
IDEwcHg7CiAgICB9Cn0KCkBtZWRpYSAocHJlZmVycy1yZWR1Y2VkLW1vdGlvbjogcmVkdWNlKSB7
CiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXSAqLAogICAg
Lmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gKjo6YmVmb3JlLAog
ICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19jbGFyaXR5X3YxIl0gKjo6YWZ0ZXIg
ewogICAgICAgIHNjcm9sbC1iZWhhdmlvcjogYXV0byAhaW1wb3J0YW50OwogICAgICAgIGFuaW1h
dGlvbi1kdXJhdGlvbjogLjAxbXMgIWltcG9ydGFudDsKICAgICAgICB0cmFuc2l0aW9uLWR1cmF0
aW9uOiAuMDFtcyAhaW1wb3J0YW50OwogICAgfQp9Cg==
SF_CLARITY_CSS_B64

    decode_to_file "$JS" <<'SF_CLARITY_JS_B64'
aW1wb3J0ICcuLi9jb3JlLmpzJzsKCmNvbnN0IHJvb3QgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9y
KCdbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX2NsYXJpdHlfdjEiXScpOwpjb25zdCBwcm9kdWN0
Tm9kZSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdseHYyUHJvZHVjdERhdGEnKTsKCmlmIChy
b290ICYmIHByb2R1Y3ROb2RlKSB7CiAgICBkb2N1bWVudC5ib2R5LmNsYXNzTGlzdC5hZGQoJ2x4
LXBkcC1zdHVkaW8tY2xhcml0eScpOwoKICAgIGxldCBwcm9kdWN0ID0ge307CgogICAgdHJ5IHsK
ICAgICAgICBwcm9kdWN0ID0gSlNPTi5wYXJzZShwcm9kdWN0Tm9kZS50ZXh0Q29udGVudCB8fCAn
e30nKTsKICAgIH0gY2F0Y2ggKGVycm9yKSB7CiAgICAgICAgY29uc29sZS5lcnJvcignS2jDtG5n
IMSR4buNYyDEkcaw4bujYyBQRFAgcGF5bG9hZCBjaG8gU3R1ZGlvIENsYXJpdHkuJywgZXJyb3Ip
OwogICAgfQoKICAgIGNvbnN0IHJlZHVjZWRNb3Rpb24gPSB3aW5kb3cubWF0Y2hNZWRpYSgnKHBy
ZWZlcnMtcmVkdWNlZC1tb3Rpb246IHJlZHVjZSknKS5tYXRjaGVzOwogICAgY29uc3QgY29sb3Jz
ID0gQXJyYXkuaXNBcnJheShwcm9kdWN0LmNvbG9ycykgPyBwcm9kdWN0LmNvbG9ycyA6IFtdOwoK
ICAgIGNvbnN0IG5vcm1hbGl6ZSA9ICh2YWx1ZSkgPT4gU3RyaW5nKHZhbHVlIHx8ICcnKQogICAg
ICAgIC50cmltKCkKICAgICAgICAudG9Mb2NhbGVMb3dlckNhc2UoJ3ZpJyk7CgogICAgY29uc3Qg
bWVkaWFVcmwgPSAoaXRlbSkgPT4gU3RyaW5nKGl0ZW0/LnVybCB8fCBpdGVtPy50aHVtYl91cmwg
fHwgJycpOwoKICAgIGNvbnN0IGFjdGl2ZUNvbG9yID0gKCkgPT4gewogICAgICAgIGNvbnN0IGJ1
dHRvbiA9IHJvb3QucXVlcnlTZWxlY3RvcignW2RhdGEtbHhwZHAtY29sb3JdLmlzLWFjdGl2ZScp
OwogICAgICAgIGNvbnN0IGlkID0gU3RyaW5nKGJ1dHRvbj8uZGF0YXNldC5jb2xvcklkIHx8ICcn
KTsKICAgICAgICBjb25zdCByZXF1ZXN0ZWQgPSBuZXcgVVJMKHdpbmRvdy5sb2NhdGlvbi5ocmVm
KS5zZWFyY2hQYXJhbXMuZ2V0KCdjb2xvcicpOwoKICAgICAgICByZXR1cm4gY29sb3JzLmZpbmQo
KGNvbG9yKSA9PiBTdHJpbmcoY29sb3IuaWQpID09PSBpZCkKICAgICAgICAgICAgfHwgY29sb3Jz
LmZpbmQoKGNvbG9yKSA9PiByZXF1ZXN0ZWQgJiYgWwogICAgICAgICAgICAgICAgY29sb3IuaWQs
CiAgICAgICAgICAgICAgICBjb2xvci5jb2RlLAogICAgICAgICAgICAgICAgY29sb3Iua2V5LAog
ICAgICAgICAgICBdLm1hcChub3JtYWxpemUpLmluY2x1ZGVzKG5vcm1hbGl6ZShyZXF1ZXN0ZWQp
KSkKICAgICAgICAgICAgfHwgY29sb3JzLmZpbmQoKGNvbG9yKSA9PiBTdHJpbmcoY29sb3IuaWQp
ID09PSBTdHJpbmcocHJvZHVjdC5kZWZhdWx0X2NvbG9yX2lkIHx8ICcnKSkKICAgICAgICAgICAg
fHwgY29sb3JzLmZpbmQoKGNvbG9yKSA9PiBjb2xvci5zZWxsYWJsZSAmJiBOdW1iZXIoY29sb3Iu
YXZhaWxhYmxlIHx8IDApID4gMCkKICAgICAgICAgICAgfHwgY29sb3JzWzBdCiAgICAgICAgICAg
IHx8IG51bGw7CiAgICB9OwoKICAgIGNvbnN0IGNsYXJpdHlJdGVtcyA9IChjb2xvcikgPT4gewog
ICAgICAgIGlmIChBcnJheS5pc0FycmF5KGNvbG9yPy5jbGFyaXR5X21lZGlhKSkgewogICAgICAg
ICAgICByZXR1cm4gY29sb3IuY2xhcml0eV9tZWRpYQogICAgICAgICAgICAgICAgLmZpbHRlcigo
aXRlbSkgPT4gbWVkaWFVcmwoaXRlbSkpCiAgICAgICAgICAgICAgICAuc2xpY2UoMCwgOCk7CiAg
ICAgICAgfQoKICAgICAgICByZXR1cm4gKEFycmF5LmlzQXJyYXkoY29sb3I/Lm1lZGlhKSA/IGNv
bG9yLm1lZGlhIDogW10pCiAgICAgICAgICAgIC5maWx0ZXIoKGl0ZW0pID0+IHsKICAgICAgICAg
ICAgICAgIGNvbnN0IGNhdGVnb3J5ID0gU3RyaW5nKGl0ZW0/LmNhdGVnb3J5X2NvZGUgfHwgJycp
LnRvVXBwZXJDYXNlKCk7CiAgICAgICAgICAgICAgICByZXR1cm4gY2F0ZWdvcnkuaW5jbHVkZXMo
J1BST0RVQ1RfQ0xBUklUWScpICYmIG1lZGlhVXJsKGl0ZW0pOwogICAgICAgICAgICB9KQogICAg
ICAgICAgICAuc2xpY2UoMCwgOCk7CiAgICB9OwoKICAgIGNvbnN0IGFuZ2xlTGFiZWwgPSAoaXRl
bSkgPT4gewogICAgICAgIGNvbnN0IGJsb2IgPSBgJHtpdGVtPy5zaG90X2FuZ2xlIHx8ICcnfSAk
e2l0ZW0/LnJvbGUgfHwgJyd9YC50b1VwcGVyQ2FzZSgpOwoKICAgICAgICBpZiAoCiAgICAgICAg
ICAgIGJsb2IuaW5jbHVkZXMoJ0ZST05UXzNRJykKICAgICAgICAgICAgfHwgYmxvYi5pbmNsdWRl
cygnRlJPTlQgM1EnKQogICAgICAgICAgICB8fCBibG9iLmluY2x1ZGVzKCczLzQgRlJPTlQnKQog
ICAgICAgICkgewogICAgICAgICAgICByZXR1cm4gJ0fDs2MgdHLGsOG7m2MgMy80JzsKICAgICAg
ICB9CgogICAgICAgIGlmICgKICAgICAgICAgICAgYmxvYi5pbmNsdWRlcygnQkFDS18zUScpCiAg
ICAgICAgICAgIHx8IGJsb2IuaW5jbHVkZXMoJ0JBQ0sgM1EnKQogICAgICAgICAgICB8fCBibG9i
LmluY2x1ZGVzKCczLzQgQkFDSycpCiAgICAgICAgKSB7CiAgICAgICAgICAgIHJldHVybiAnR8Oz
YyBzYXUgMy80JzsKICAgICAgICB9CgogICAgICAgIGlmICgKICAgICAgICAgICAgYmxvYi5pbmNs
dWRlcygnTEVGVF9TSURFJykKICAgICAgICAgICAgfHwgYmxvYi5pbmNsdWRlcygnU0lERV9MRUZU
JykKICAgICAgICAgICAgfHwgYmxvYi5pbmNsdWRlcygnTEVGVCBQUk9GSUxFJykKICAgICAgICAp
IHsKICAgICAgICAgICAgcmV0dXJuICdHw7NjIG5naGnDqm5nIHRyw6FpJzsKICAgICAgICB9Cgog
ICAgICAgIGlmICgKICAgICAgICAgICAgYmxvYi5pbmNsdWRlcygnUklHSFRfU0lERScpCiAgICAg
ICAgICAgIHx8IGJsb2IuaW5jbHVkZXMoJ1NJREVfUklHSFQnKQogICAgICAgICAgICB8fCBibG9i
LmluY2x1ZGVzKCdSSUdIVCBQUk9GSUxFJykKICAgICAgICApIHsKICAgICAgICAgICAgcmV0dXJu
ICdHw7NjIG5naGnDqm5nIHBo4bqjaSc7CiAgICAgICAgfQoKICAgICAgICBpZiAoCiAgICAgICAg
ICAgIGJsb2IuaW5jbHVkZXMoJ0ZVTExfRlJPTlQnKQogICAgICAgICAgICB8fCBibG9iLmluY2x1
ZGVzKCdQUk9EVUNUX0ZST05UJykKICAgICAgICAgICAgfHwgYmxvYi5pbmNsdWRlcygnRlJPTlQn
KQogICAgICAgICkgewogICAgICAgICAgICByZXR1cm4gJ03hurd0IHRyxrDhu5tjJzsKICAgICAg
ICB9CgogICAgICAgIGlmICgKICAgICAgICAgICAgYmxvYi5pbmNsdWRlcygnRlVMTF9CQUNLJykK
ICAgICAgICAgICAgfHwgYmxvYi5pbmNsdWRlcygnUFJPRFVDVF9CQUNLJykKICAgICAgICAgICAg
fHwgYmxvYi5pbmNsdWRlcygnQkFDSycpCiAgICAgICAgKSB7CiAgICAgICAgICAgIHJldHVybiAn
TeG6t3Qgc2F1JzsKICAgICAgICB9CgogICAgICAgIGlmIChibG9iLmluY2x1ZGVzKCdTSURFJykg
fHwgYmxvYi5pbmNsdWRlcygnUFJPRklMRScpKSB7CiAgICAgICAgICAgIHJldHVybiAnR8OzYyBu
Z2hpw6puZyc7CiAgICAgICAgfQoKICAgICAgICBpZiAoCiAgICAgICAgICAgIGJsb2IuaW5jbHVk
ZXMoJ0RFVEFJTCcpCiAgICAgICAgICAgIHx8IGJsb2IuaW5jbHVkZXMoJ0NMT1NFJykKICAgICAg
ICAgICAgfHwgYmxvYi5pbmNsdWRlcygnTUFDUk8nKQogICAgICAgICkgewogICAgICAgICAgICBy
ZXR1cm4gJ0NoaSB0aeG6v3Qgc+G6o24gcGjhuqltJzsKICAgICAgICB9CgogICAgICAgIGlmIChi
bG9iLmluY2x1ZGVzKCdMSUZFU1RZTEUnKSB8fCBibG9iLmluY2x1ZGVzKCdNT0RFTCcpKSB7CiAg
ICAgICAgICAgIHJldHVybiAnVHLDqm4gbmfGsOG7nWkgbeG6q3UnOwogICAgICAgIH0KCiAgICAg
ICAgcmV0dXJuIHsKICAgICAgICAgICAgZnJvbnQ6ICdN4bq3dCB0csaw4bubYycsCiAgICAgICAg
ICAgIGJhY2s6ICdN4bq3dCBzYXUnLAogICAgICAgICAgICBzaWRlOiAnR8OzYyBuZ2hpw6puZycs
CiAgICAgICAgICAgIGRldGFpbDogJ0NoaSB0aeG6v3Qgc+G6o24gcGjhuqltJywKICAgICAgICAg
ICAgbGlmZXN0eWxlOiAnVHLDqm4gbmfGsOG7nWkgbeG6q3UnLAogICAgICAgIH1bU3RyaW5nKGl0
ZW0/LnJvbGUgfHwgJycpXSB8fCAnR8OzYyBuaMOsbiBz4bqjbiBwaOG6qW0nOwogICAgfTsKCiAg
ICBjb25zdCBhbmdsZURlc2NyaXB0aW9uID0gKGxhYmVsKSA9PiAoewogICAgICAgICdN4bq3dCB0
csaw4bubYyc6ICdRdWFuIHPDoXQgdG/DoG4gYuG7mSDEkcaw4budbmcgbsOpdCB2w6AgdOG7tyBs
4buHIHBow61hIHRyxrDhu5tjLicsCiAgICAgICAgJ03hurd0IHNhdSc6ICdLaeG7g20gdHJhIHBo
b20gbMawbmcsIGtow7NhIHbDoCDEkeG7mSByxqFpIGPhu6dhIHPhuqNuIHBo4bqpbS4nLAogICAg
ICAgICdHw7NjIHRyxrDhu5tjIDMvNCc6ICdD4bqjbSBuaOG6rW4gxJHhu5kgbuG7lWkga2jhu5Fp
IHbDoCBjw6FjaCBwaG9tIMO0bSBjxqEgdGjhu4MuJywKICAgICAgICAnR8OzYyBzYXUgMy80Jzog
J1hlbSByw7UgY2h1eeG7g24gdGnhur9wIHThu6sgbMawbmcgc2FuZyBow7RuZyB2w6AgZ+G6pXUu
JywKICAgICAgICAnR8OzYyBuZ2hpw6puZyB0csOhaSc6ICfEkMOhbmggZ2nDoSDEkeG7mSBkw6B5
LCBjaGnhu4F1IHPDonUgdsOgIMSRxrDhu51uZyBjb25nIGPhu6dhIHBob20uJywKICAgICAgICAn
R8OzYyBuZ2hpw6puZyBwaOG6o2knOiAnxJDDoW5oIGdpw6EgxJHhu5kgZMOgeSwgY2hp4buBdSBz
w6J1IHbDoCDEkcaw4budbmcgY29uZyBj4bunYSBwaG9tLicsCiAgICAgICAgJ0fDs2MgbmdoacOq
bmcnOiAnxJDDoW5oIGdpw6EgxJHhu5kgZMOgeSwgY2hp4buBdSBzw6J1IHbDoCDEkcaw4budbmcg
Y29uZyBj4bunYSBwaG9tLicsCiAgICAgICAgJ0NoaSB0aeG6v3Qgc+G6o24gcGjhuqltJzogJ05o
w6xuIGfhuqduIGNo4bqldCBsaeG7h3UgdsOgIMSRaeG7g20gbmjhuqVuIHRoaeG6v3Qga+G6vy4n
LAogICAgICAgICdUcsOqbiBuZ8aw4budaSBt4bqrdSc6ICdIw6xuaCBkdW5nIHThu7cgbOG7hyBz
4bqjbiBwaOG6qW0ga2hpIG3hurdjIHRo4buxYyB04bq/LicsCiAgICB9W2xhYmVsXSB8fCAnTeG7
mXQgZ8OzYyBuaMOsbiDEkcOjIMSRxrDhu6NjIGNo4buNbiDEkeG7gyBsw6BtIHLDtSBz4bqjbiBw
aOG6qW0uJyk7CgogICAgY29uc3QgbWFrZUFuZ2xlQ2FyZCA9IChpdGVtLCBpbmRleCwgY29sb3Ip
ID0+IHsKICAgICAgICBjb25zdCBsYWJlbCA9IGFuZ2xlTGFiZWwoaXRlbSk7CiAgICAgICAgY29u
c3QgZmlndXJlID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZmlndXJlJyk7CiAgICAgICAgZmln
dXJlLmNsYXNzTmFtZSA9IGBseGMtYW5nbGUtY2FyZCBseGMtYW5nbGUtY2FyZC0tJHtNYXRoLm1p
bihpbmRleCArIDEsIDgpfWA7CiAgICAgICAgZmlndXJlLmRhdGFzZXQubHhjQ2xhcml0eUl0ZW0g
PSBTdHJpbmcoaW5kZXgpOwoKICAgICAgICBjb25zdCBtZWRpYSA9IGRvY3VtZW50LmNyZWF0ZUVs
ZW1lbnQoJ2RpdicpOwogICAgICAgIG1lZGlhLmNsYXNzTmFtZSA9ICdseGMtYW5nbGUtY2FyZF9f
bWVkaWEnOwoKICAgICAgICBjb25zdCBpbWFnZSA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2lt
ZycpOwogICAgICAgIGltYWdlLnNyYyA9IG1lZGlhVXJsKGl0ZW0pOwogICAgICAgIGltYWdlLmFs
dCA9IGAke3Byb2R1Y3QubmFtZSB8fCAnU+G6o24gcGjhuqltJ30g4oCUICR7Y29sb3I/LmxhYmVs
IHx8ICcnfSDigJQgJHtsYWJlbH1gOwogICAgICAgIGltYWdlLmxvYWRpbmcgPSBpbmRleCA9PT0g
MCA/ICdlYWdlcicgOiAnbGF6eSc7CiAgICAgICAgaW1hZ2UuZGVjb2RpbmcgPSAnYXN5bmMnOwoK
ICAgICAgICBjb25zdCBudW1iZXIgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdzcGFuJyk7CiAg
ICAgICAgbnVtYmVyLnRleHRDb250ZW50ID0gU3RyaW5nKGluZGV4ICsgMSkucGFkU3RhcnQoMiwg
JzAnKTsKCiAgICAgICAgbWVkaWEuYXBwZW5kKGltYWdlLCBudW1iZXIpOwoKICAgICAgICBjb25z
dCBjYXB0aW9uID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZmlnY2FwdGlvbicpOwogICAgICAg
IGNvbnN0IGtpY2tlciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ3NtYWxsJyk7CiAgICAgICAg
a2lja2VyLnRleHRDb250ZW50ID0gJ0fDs2MgbmjDrG4nOwogICAgICAgIGNvbnN0IHRpdGxlID0g
ZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnaDMnKTsKICAgICAgICB0aXRsZS50ZXh0Q29udGVudCA9
IGxhYmVsOwogICAgICAgIGNvbnN0IGRlc2NyaXB0aW9uID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVu
dCgncCcpOwogICAgICAgIGRlc2NyaXB0aW9uLnRleHRDb250ZW50ID0gYW5nbGVEZXNjcmlwdGlv
bihsYWJlbCk7CgogICAgICAgIGNhcHRpb24uYXBwZW5kKGtpY2tlciwgdGl0bGUsIGRlc2NyaXB0
aW9uKTsKICAgICAgICBmaWd1cmUuYXBwZW5kKG1lZGlhLCBjYXB0aW9uKTsKCiAgICAgICAgcmV0
dXJuIHsgZmlndXJlLCBsYWJlbCB9OwogICAgfTsKCiAgICBjb25zdCByZW5kZXJDbGFyaXR5ID0g
KGNvbG9yKSA9PiB7CiAgICAgICAgY29uc3QgZ3JpZCA9IHJvb3QucXVlcnlTZWxlY3RvcignW2Rh
dGEtbHhjLWNsYXJpdHktZ3JpZF0nKTsKICAgICAgICBjb25zdCBuYXYgPSByb290LnF1ZXJ5U2Vs
ZWN0b3IoJ1tkYXRhLWx4Yy1hbmdsZS1uYXZdJyk7CiAgICAgICAgY29uc3QgZW1wdHkgPSByb290
LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWx4Yy1jbGFyaXR5LWVtcHR5XScpOwogICAgICAgIGNvbnN0
IGNvbG9yTGFiZWwgPSByb290LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWx4Yy1jbGFyaXR5LWNvbG9y
XScpOwoKICAgICAgICBpZiAoY29sb3JMYWJlbCkgewogICAgICAgICAgICBjb2xvckxhYmVsLnRl
eHRDb250ZW50ID0gY29sb3I/LmxhYmVsIHx8ICdNw6B1IMSRYW5nIGNo4buNbic7CiAgICAgICAg
fQoKICAgICAgICBpZiAoIWdyaWQgfHwgIW5hdiB8fCAhZW1wdHkpIHsKICAgICAgICAgICAgcmV0
dXJuOwogICAgICAgIH0KCiAgICAgICAgY29uc3QgaXRlbXMgPSBjbGFyaXR5SXRlbXMoY29sb3Ip
OwoKICAgICAgICBpZiAoIWl0ZW1zLmxlbmd0aCkgewogICAgICAgICAgICBncmlkLnJlcGxhY2VD
aGlsZHJlbigpOwogICAgICAgICAgICBuYXYucmVwbGFjZUNoaWxkcmVuKCk7CiAgICAgICAgICAg
IGdyaWQuaGlkZGVuID0gdHJ1ZTsKICAgICAgICAgICAgbmF2LmhpZGRlbiA9IHRydWU7CiAgICAg
ICAgICAgIGVtcHR5LmhpZGRlbiA9IGZhbHNlOwogICAgICAgICAgICByZXR1cm47CiAgICAgICAg
fQoKICAgICAgICBjb25zdCBjYXJkcyA9IGRvY3VtZW50LmNyZWF0ZURvY3VtZW50RnJhZ21lbnQo
KTsKICAgICAgICBjb25zdCBjaGlwcyA9IGRvY3VtZW50LmNyZWF0ZURvY3VtZW50RnJhZ21lbnQo
KTsKCiAgICAgICAgaXRlbXMuZm9yRWFjaCgoaXRlbSwgaW5kZXgpID0+IHsKICAgICAgICAgICAg
Y29uc3QgeyBmaWd1cmUsIGxhYmVsIH0gPSBtYWtlQW5nbGVDYXJkKGl0ZW0sIGluZGV4LCBjb2xv
cik7CiAgICAgICAgICAgIGNhcmRzLmFwcGVuZChmaWd1cmUpOwoKICAgICAgICAgICAgY29uc3Qg
YnV0dG9uID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYnV0dG9uJyk7CiAgICAgICAgICAgIGJ1
dHRvbi50eXBlID0gJ2J1dHRvbic7CiAgICAgICAgICAgIGJ1dHRvbi5kYXRhc2V0Lmx4Y0FuZ2xl
SnVtcCA9IFN0cmluZyhpbmRleCk7CiAgICAgICAgICAgIGJ1dHRvbi5zZXRBdHRyaWJ1dGUoJ2Fy
aWEtbGFiZWwnLCBgxJBpIHThu5tpIOG6o25oICR7bGFiZWx9YCk7CgogICAgICAgICAgICBjb25z
dCBudW1iZXIgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdzcGFuJyk7CiAgICAgICAgICAgIG51
bWJlci50ZXh0Q29udGVudCA9IFN0cmluZyhpbmRleCArIDEpLnBhZFN0YXJ0KDIsICcwJyk7CiAg
ICAgICAgICAgIGJ1dHRvbi5hcHBlbmQobnVtYmVyLCBkb2N1bWVudC5jcmVhdGVUZXh0Tm9kZShs
YWJlbCkpOwogICAgICAgICAgICBidXR0b24uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKSA9
PiB7CiAgICAgICAgICAgICAgICBmaWd1cmUuc2Nyb2xsSW50b1ZpZXcoewogICAgICAgICAgICAg
ICAgICAgIGJlaGF2aW9yOiByZWR1Y2VkTW90aW9uID8gJ2F1dG8nIDogJ3Ntb290aCcsCiAgICAg
ICAgICAgICAgICAgICAgYmxvY2s6ICdjZW50ZXInLAogICAgICAgICAgICAgICAgfSk7CiAgICAg
ICAgICAgIH0pOwoKICAgICAgICAgICAgY2hpcHMuYXBwZW5kKGJ1dHRvbik7CiAgICAgICAgfSk7
CgogICAgICAgIGdyaWQucmVwbGFjZUNoaWxkcmVuKGNhcmRzKTsKICAgICAgICBuYXYucmVwbGFj
ZUNoaWxkcmVuKGNoaXBzKTsKICAgICAgICBncmlkLmhpZGRlbiA9IGZhbHNlOwogICAgICAgIG5h
di5oaWRkZW4gPSBmYWxzZTsKICAgICAgICBlbXB0eS5oaWRkZW4gPSB0cnVlOwoKICAgICAgICB3
aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lKCgpID0+IHsKICAgICAgICAgICAgZ3JpZC5jbGFz
c0xpc3QuYWRkKCdpcy12aXNpYmxlJyk7CiAgICAgICAgICAgIGdyaWQucXVlcnlTZWxlY3RvckFs
bCgnLmx4Yy1hbmdsZS1jYXJkJykuZm9yRWFjaCgoY2FyZCwgaW5kZXgpID0+IHsKICAgICAgICAg
ICAgICAgIGNhcmQuc3R5bGUudHJhbnNpdGlvbkRlbGF5ID0gcmVkdWNlZE1vdGlvbgogICAgICAg
ICAgICAgICAgICAgID8gJzBtcycKICAgICAgICAgICAgICAgICAgICA6IGAke01hdGgubWluKGlu
ZGV4ICogNTUsIDI4MCl9bXNgOwogICAgICAgICAgICAgICAgY2FyZC5jbGFzc0xpc3QuYWRkKCdp
cy12aXNpYmxlJyk7CiAgICAgICAgICAgIH0pOwogICAgICAgIH0pOwogICAgfTsKCiAgICBjb25z
dCBub3JtYWxpemVTaXplQnV0dG9ucyA9ICgpID0+IHsKICAgICAgICByb290LnF1ZXJ5U2VsZWN0
b3JBbGwoJ1tkYXRhLWx4cGRwLXNpemVdJykuZm9yRWFjaCgoYnV0dG9uKSA9PiB7CiAgICAgICAg
ICAgIGNvbnN0IGxhYmVsID0gU3RyaW5nKGJ1dHRvbi50ZXh0Q29udGVudCB8fCAnJykudHJpbSgp
OwoKICAgICAgICAgICAgaWYgKGJ1dHRvbi5kaXNhYmxlZCkgewogICAgICAgICAgICAgICAgYnV0
dG9uLnNldEF0dHJpYnV0ZSgKICAgICAgICAgICAgICAgICAgICAnYXJpYS1sYWJlbCcsCiAgICAg
ICAgICAgICAgICAgICAgYFNpemUgJHtsYWJlbH0g4oCUIGjhur90IGjDoG5nIOG7nyBtw6B1IMSR
YW5nIGNo4buNbmAKICAgICAgICAgICAgICAgICk7CiAgICAgICAgICAgICAgICBidXR0b24udGl0
bGUgPSBgU2l6ZSAke2xhYmVsfSDigJQgaOG6v3QgaMOgbmdgOwogICAgICAgICAgICB9IGVsc2Ug
ewogICAgICAgICAgICAgICAgYnV0dG9uLnNldEF0dHJpYnV0ZSgnYXJpYS1sYWJlbCcsIGBDaOG7
jW4gc2l6ZSAke2xhYmVsfWApOwogICAgICAgICAgICAgICAgYnV0dG9uLnRpdGxlID0gYENo4buN
biBzaXplICR7bGFiZWx9YDsKICAgICAgICAgICAgfQogICAgICAgIH0pOwogICAgfTsKCiAgICBj
b25zdCBidXlCdXR0b24gPSByb290LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWx4cGRwLWJ1eV0nKTsK
ICAgIGNvbnN0IGNhcnRGb3JtID0gcm9vdC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1seHBkcC1jYXJ0
LWZvcm1dJyk7CiAgICBjb25zdCBkb2NrQnV0dG9uID0gcm9vdC5xdWVyeVNlbGVjdG9yKCdbZGF0
YS1seGMtZG9jay1zdWJtaXRdJyk7CiAgICBjb25zdCBkb2NrTGFiZWwgPSByb290LnF1ZXJ5U2Vs
ZWN0b3IoJ1tkYXRhLWx4Yy1kb2NrLWxhYmVsXScpOwogICAgY29uc3QgZG9ja1NlbGVjdGlvbiA9
IHJvb3QucXVlcnlTZWxlY3RvcignW2RhdGEtbHhjLWRvY2stc2VsZWN0aW9uXScpOwogICAgY29u
c3Qgc2l6ZVNlbGVjdG9yID0gcm9vdC5xdWVyeVNlbGVjdG9yKCcubHhjLXNlbGVjdG9yLS1zaXpl
Jyk7CiAgICBjb25zdCBzZWxlY3RlZFRleHQgPSByb290LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWx4
cGRwLXNlbGVjdGVkLXRleHRdJyk7CiAgICBjb25zdCBjb2xvclRleHQgPSByb290LnF1ZXJ5U2Vs
ZWN0b3IoJ1tkYXRhLWx4cGRwLWNvbG9yLWxhYmVsXScpOwoKICAgIGNvbnN0IHN5bmNEb2NrID0g
KCkgPT4gewogICAgICAgIGlmICghZG9ja0J1dHRvbiB8fCAhZG9ja0xhYmVsKSB7CiAgICAgICAg
ICAgIHJldHVybjsKICAgICAgICB9CgogICAgICAgIGNvbnN0IHByb2R1Y3RJblN0b2NrID0gQm9v
bGVhbihwcm9kdWN0LmluX3N0b2NrKTsKICAgICAgICBjb25zdCByZWFkeSA9IEJvb2xlYW4oYnV5
QnV0dG9uICYmICFidXlCdXR0b24uZGlzYWJsZWQpOwogICAgICAgIGNvbnN0IGJ1eVRleHQgPSBT
dHJpbmcoYnV5QnV0dG9uPy50ZXh0Q29udGVudCB8fCAnJykudHJpbSgpOwogICAgICAgIGNvbnN0
IHNlbGVjdGlvblRleHQgPSBTdHJpbmcoc2VsZWN0ZWRUZXh0Py50ZXh0Q29udGVudCB8fCAnJyku
dHJpbSgpOwogICAgICAgIGNvbnN0IHNlbGVjdGVkQ29sb3JUZXh0ID0gU3RyaW5nKGNvbG9yVGV4
dD8udGV4dENvbnRlbnQgfHwgJycpLnRyaW0oKTsKCiAgICAgICAgaWYgKGRvY2tTZWxlY3Rpb24p
IHsKICAgICAgICAgICAgZG9ja1NlbGVjdGlvbi50ZXh0Q29udGVudCA9IHNlbGVjdGlvblRleHQK
ICAgICAgICAgICAgICAgIHx8IChzZWxlY3RlZENvbG9yVGV4dAogICAgICAgICAgICAgICAgICAg
ID8gYCR7c2VsZWN0ZWRDb2xvclRleHR9IMK3IENo4buNbiBzaXplYAogICAgICAgICAgICAgICAg
ICAgIDogJ0No4buNbiBtw6B1ICYgc2l6ZScpOwogICAgICAgIH0KCiAgICAgICAgaWYgKHJlYWR5
KSB7CiAgICAgICAgICAgIGRvY2tCdXR0b24uZGlzYWJsZWQgPSBmYWxzZTsKICAgICAgICAgICAg
ZG9ja0J1dHRvbi5kYXRhc2V0Lm1vZGUgPSAnc3VibWl0JzsKICAgICAgICAgICAgZG9ja0xhYmVs
LnRleHRDb250ZW50ID0gJ1Row6ptIHbDoG8gZ2nhu48nOwogICAgICAgICAgICByZXR1cm47CiAg
ICAgICAgfQoKICAgICAgICBpZiAoIXByb2R1Y3RJblN0b2NrIHx8IC9o4bq/dCBow6BuZy9pLnRl
c3QoYnV5VGV4dCkpIHsKICAgICAgICAgICAgZG9ja0J1dHRvbi5kaXNhYmxlZCA9IHRydWU7CiAg
ICAgICAgICAgIGRvY2tCdXR0b24uZGF0YXNldC5tb2RlID0gJ3NvbGRvdXQnOwogICAgICAgICAg
ICBkb2NrTGFiZWwudGV4dENvbnRlbnQgPSAnVOG6oW0gaOG6v3QgaMOgbmcnOwogICAgICAgICAg
ICByZXR1cm47CiAgICAgICAgfQoKICAgICAgICBkb2NrQnV0dG9uLmRpc2FibGVkID0gZmFsc2U7
CiAgICAgICAgZG9ja0J1dHRvbi5kYXRhc2V0Lm1vZGUgPSAnZ3VpZGUnOwogICAgICAgIGRvY2tM
YWJlbC50ZXh0Q29udGVudCA9IC9rw61jaCB0aMaw4bubY3xzaXplL2kudGVzdChidXlUZXh0KQog
ICAgICAgICAgICA/ICdDaOG7jW4gc2l6ZScKICAgICAgICAgICAgOiAnQ2jhu41uIG3DoHUgJiBz
aXplJzsKICAgIH07CgogICAgZG9ja0J1dHRvbj8uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAo
KSA9PiB7CiAgICAgICAgaWYgKAogICAgICAgICAgICBkb2NrQnV0dG9uLmRhdGFzZXQubW9kZSA9
PT0gJ3N1Ym1pdCcKICAgICAgICAgICAgJiYgYnV5QnV0dG9uCiAgICAgICAgICAgICYmICFidXlC
dXR0b24uZGlzYWJsZWQKICAgICAgICApIHsKICAgICAgICAgICAgY2FydEZvcm0/LnJlcXVlc3RT
dWJtaXQoKTsKICAgICAgICAgICAgcmV0dXJuOwogICAgICAgIH0KCiAgICAgICAgc2l6ZVNlbGVj
dG9yPy5zY3JvbGxJbnRvVmlldyh7CiAgICAgICAgICAgIGJlaGF2aW9yOiByZWR1Y2VkTW90aW9u
ID8gJ2F1dG8nIDogJ3Ntb290aCcsCiAgICAgICAgICAgIGJsb2NrOiAnY2VudGVyJywKICAgICAg
ICB9KTsKCiAgICAgICAgc2l6ZVNlbGVjdG9yPy5hbmltYXRlKAogICAgICAgICAgICBbCiAgICAg
ICAgICAgICAgICB7IGJveFNoYWRvdzogJzAgMCAwIDAgcmdiYSg5MSw5NSwyNDIsMCknIH0sCiAg
ICAgICAgICAgICAgICB7IGJveFNoYWRvdzogJzAgMCAwIDlweCByZ2JhKDkxLDk1LDI0MiwuMTYp
JyB9LAogICAgICAgICAgICAgICAgeyBib3hTaGFkb3c6ICcwIDAgMCAwIHJnYmEoOTEsOTUsMjQy
LDApJyB9LAogICAgICAgICAgICBdLAogICAgICAgICAgICB7CiAgICAgICAgICAgICAgICBkdXJh
dGlvbjogcmVkdWNlZE1vdGlvbiA/IDEgOiA4NTAsCiAgICAgICAgICAgICAgICBlYXNpbmc6ICdl
YXNlLW91dCcsCiAgICAgICAgICAgIH0KICAgICAgICApOwogICAgfSk7CgogICAgY29uc3QgYXBw
bHlDb2xvciA9IChjb2xvcikgPT4gewogICAgICAgIGNvbnN0IGhleCA9IC9eI1swLTlhLWZdezMs
OH0kL2kudGVzdChTdHJpbmcoY29sb3I/LmhleCB8fCAnJykpCiAgICAgICAgICAgID8gU3RyaW5n
KGNvbG9yLmhleCkKICAgICAgICAgICAgOiAnIzViNWZmMic7CgogICAgICAgIHJvb3Quc3R5bGUu
c2V0UHJvcGVydHkoJy0tbHhjLWN1cnJlbnQtY29sb3InLCBoZXgpOwogICAgICAgIHJlbmRlckNs
YXJpdHkoY29sb3IpOwoKICAgICAgICB3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lKCgpID0+
IHsKICAgICAgICAgICAgbm9ybWFsaXplU2l6ZUJ1dHRvbnMoKTsKICAgICAgICAgICAgc3luY0Rv
Y2soKTsKICAgICAgICB9KTsKICAgIH07CgogICAgcm9vdC5xdWVyeVNlbGVjdG9yQWxsKCdbZGF0
YS1seHBkcC1jb2xvcl0nKS5mb3JFYWNoKChidXR0b24pID0+IHsKICAgICAgICBidXR0b24uYWRk
RXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKSA9PiB7CiAgICAgICAgICAgIGNvbnN0IGNvbG9yID0g
Y29sb3JzLmZpbmQoCiAgICAgICAgICAgICAgICAoaXRlbSkgPT4gU3RyaW5nKGl0ZW0uaWQpID09
PSBTdHJpbmcoYnV0dG9uLmRhdGFzZXQuY29sb3JJZCkKICAgICAgICAgICAgKTsKCiAgICAgICAg
ICAgIGlmIChjb2xvcikgewogICAgICAgICAgICAgICAgd2luZG93LnJlcXVlc3RBbmltYXRpb25G
cmFtZSgoKSA9PiBhcHBseUNvbG9yKGNvbG9yKSk7CiAgICAgICAgICAgIH0KICAgICAgICB9KTsK
ICAgIH0pOwoKICAgIGNvbnN0IHNpemVMaXN0ID0gcm9vdC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1s
eHBkcC1zaXplc10nKTsKCiAgICBpZiAoc2l6ZUxpc3QgJiYgJ011dGF0aW9uT2JzZXJ2ZXInIGlu
IHdpbmRvdykgewogICAgICAgIG5ldyBNdXRhdGlvbk9ic2VydmVyKCgpID0+IHsKICAgICAgICAg
ICAgbm9ybWFsaXplU2l6ZUJ1dHRvbnMoKTsKICAgICAgICAgICAgc3luY0RvY2soKTsKICAgICAg
ICB9KS5vYnNlcnZlKHNpemVMaXN0LCB7CiAgICAgICAgICAgIGNoaWxkTGlzdDogdHJ1ZSwKICAg
ICAgICAgICAgc3VidHJlZTogdHJ1ZSwKICAgICAgICAgICAgYXR0cmlidXRlczogdHJ1ZSwKICAg
ICAgICAgICAgYXR0cmlidXRlRmlsdGVyOiBbJ2Rpc2FibGVkJywgJ2NsYXNzJ10sCiAgICAgICAg
fSk7CiAgICB9CgogICAgW2J1eUJ1dHRvbiwgc2VsZWN0ZWRUZXh0LCBjb2xvclRleHRdCiAgICAg
ICAgLmZpbHRlcihCb29sZWFuKQogICAgICAgIC5mb3JFYWNoKChub2RlKSA9PiB7CiAgICAgICAg
ICAgIGlmICghKCdNdXRhdGlvbk9ic2VydmVyJyBpbiB3aW5kb3cpKSB7CiAgICAgICAgICAgICAg
ICByZXR1cm47CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIG5ldyBNdXRhdGlvbk9ic2VydmVy
KHN5bmNEb2NrKS5vYnNlcnZlKG5vZGUsIHsKICAgICAgICAgICAgICAgIGNoaWxkTGlzdDogdHJ1
ZSwKICAgICAgICAgICAgICAgIHN1YnRyZWU6IHRydWUsCiAgICAgICAgICAgICAgICBhdHRyaWJ1
dGVzOiB0cnVlLAogICAgICAgICAgICAgICAgYXR0cmlidXRlRmlsdGVyOiBbJ2Rpc2FibGVkJywg
J2hpZGRlbiddLAogICAgICAgICAgICB9KTsKICAgICAgICB9KTsKCiAgICByb290LmFkZEV2ZW50
TGlzdGVuZXIoJ2NsaWNrJywgKGV2ZW50KSA9PiB7CiAgICAgICAgaWYgKAogICAgICAgICAgICBl
dmVudC50YXJnZXQuY2xvc2VzdCgKICAgICAgICAgICAgICAgICdbZGF0YS1seHBkcC1jb2xvcl0s
IFtkYXRhLWx4cGRwLXNpemVdJwogICAgICAgICAgICApCiAgICAgICAgKSB7CiAgICAgICAgICAg
IHdpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUoKCkgPT4gewogICAgICAgICAgICAgICAgbm9y
bWFsaXplU2l6ZUJ1dHRvbnMoKTsKICAgICAgICAgICAgICAgIHN5bmNEb2NrKCk7CiAgICAgICAg
ICAgIH0pOwogICAgICAgIH0KICAgIH0pOwoKICAgIGNvbnN0IHJldmVhbEl0ZW1zID0gQXJyYXku
ZnJvbSgKICAgICAgICByb290LnF1ZXJ5U2VsZWN0b3JBbGwoJ1tkYXRhLWx4Yy1yZXZlYWxdJykK
ICAgICk7CgogICAgaWYgKCEoJ0ludGVyc2VjdGlvbk9ic2VydmVyJyBpbiB3aW5kb3cpIHx8IHJl
ZHVjZWRNb3Rpb24pIHsKICAgICAgICByZXZlYWxJdGVtcy5mb3JFYWNoKChpdGVtKSA9PiBpdGVt
LmNsYXNzTGlzdC5hZGQoJ2lzLXZpc2libGUnKSk7CiAgICB9IGVsc2UgewogICAgICAgIGNvbnN0
IG9ic2VydmVyID0gbmV3IEludGVyc2VjdGlvbk9ic2VydmVyKChlbnRyaWVzLCBpbnN0YW5jZSkg
PT4gewogICAgICAgICAgICBlbnRyaWVzLmZvckVhY2goKGVudHJ5KSA9PiB7CiAgICAgICAgICAg
ICAgICBpZiAoIWVudHJ5LmlzSW50ZXJzZWN0aW5nKSB7CiAgICAgICAgICAgICAgICAgICAgcmV0
dXJuOwogICAgICAgICAgICAgICAgfQoKICAgICAgICAgICAgICAgIGVudHJ5LnRhcmdldC5jbGFz
c0xpc3QuYWRkKCdpcy12aXNpYmxlJyk7CiAgICAgICAgICAgICAgICBpbnN0YW5jZS51bm9ic2Vy
dmUoZW50cnkudGFyZ2V0KTsKICAgICAgICAgICAgfSk7CiAgICAgICAgfSwgewogICAgICAgICAg
ICB0aHJlc2hvbGQ6IC4wOCwKICAgICAgICAgICAgcm9vdE1hcmdpbjogJzBweCAwcHggLTUlIDBw
eCcsCiAgICAgICAgfSk7CgogICAgICAgIHJldmVhbEl0ZW1zLmZvckVhY2goKGl0ZW0pID0+IG9i
c2VydmVyLm9ic2VydmUoaXRlbSkpOwogICAgfQoKICAgIG5vcm1hbGl6ZVNpemVCdXR0b25zKCk7
CiAgICBzeW5jRG9jaygpOwogICAgYXBwbHlDb2xvcihhY3RpdmVDb2xvcigpKTsKCiAgICByb290
LmRpc3BhdGNoRXZlbnQobmV3IEN1c3RvbUV2ZW50KCdsaW54ZW46cGRwOnN0dWRpby1jbGFyaXR5
LXJlYWR5JywgewogICAgICAgIGJ1YmJsZXM6IHRydWUsCiAgICAgICAgZGV0YWlsOiB7CiAgICAg
ICAgICAgIHZhcmlhbnQ6ICdzdHVkaW9fY2xhcml0eV92MScsCiAgICAgICAgICAgIHByb2R1Y3Rf
aWQ6IHByb2R1Y3QuaWQgfHwgbnVsbCwKICAgICAgICB9LAogICAgfSkpOwp9Cg==
SF_CLARITY_JS_B64

    for FILE in \
      "$PRESENTER" \
      "$VIEW_MODEL" \
      "$REGISTRY" \
      "$SECTIONS"
    do
        php -l "$FILE"
    done

    node --check "$JS"

    grep -Fq -- \
      "'studio_clarity_v1' => [" \
      "$REGISTRY"

    grep -Fq -- \
      "'clarity_hero_purchase'" \
      "$REGISTRY"

    grep -Fq -- \
      "'clarity_product_angles'" \
      "$REGISTRY"

    grep -Fq -- \
      "'clarity_product_angles' => [" \
      "$SECTIONS"

    grep -Fq -- \
      'data-lxpdp-gallery' \
      "$HERO"

    grep -Fq -- \
      'name="sellable_sku_id"' \
      "$HERO"

    grep -Fq -- \
      'data-lxc-dock-submit' \
      "$HERO"

    grep -Fq -- \
      'data-lxc-clarity-grid' \
      "$ANGLES"

    grep -Fq -- \
      'Chi tiết sản phẩm' \
      "$ANGLES"

    grep -Fq -- \
      'position: fixed' \
      "$CSS"

    grep -Fq -- \
      'PRODUCT_CLARITY' \
      "$JS"

    artisan_safe optimize:clear
    artisan_safe view:cache
    artisan_safe view:clear

    artisan_safe commerce-v2:pdp-variant-smoke \
      --variant=studio_clarity_v1

    artisan_safe commerce-v2:pdp-variant-matrix-smoke

    trap - ERR

    printf '%s\n' \
      'LINXEN_PDP_STUDIO_CLARITY_STOREFRONT_SOURCE_PATCH=PASS'
    printf '%s\n' \
      'VARIANT=studio_clarity_v1'
    printf '%s\n' \
      'SECTION_ORDER=GALLERY_PURCHASE_THEN_PRODUCT_CLARITY'
    printf '%s\n' \
      'VISIBLE_SECTIONS=2'
    printf '%s\n' \
      'OTHER_PDP_SECTIONS=HIDDEN'
    printf '%s\n' \
      'BOTTOM_DOCK=FIXED_GRAPHITE_SINGLE_ROW'
    printf '%s\n' \
      'EXACT_SELLABLE_SKU_CONTRACT=PRESERVED'
    printf '%s\n' \
      'CROSS_COLOR_MEDIA_FALLBACK=BLOCKED'
    printf '%s\n' \
      'LIVE_VARIANT=UNCHANGED'
    printf '%s\n' \
      'MIGRATION=NONE'
    printf '%s\n' \
      'DB_MUTATION=NONE'
    printf '%s\n' \
      'ERP_API_CALL_DURING_LOCAL_PATCH=NONE'
    printf '%s\n' \
      'ORDER_PROVIDER_META_MUTATION=NONE'
    printf '%s\n' \
      'NPM_BUILD=NOT_REQUIRED'
    printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"

    exit 0
fi
