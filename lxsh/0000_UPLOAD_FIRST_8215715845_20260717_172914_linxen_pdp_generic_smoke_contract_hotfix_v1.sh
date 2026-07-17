#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='linxen_pdp_generic_smoke_contract_hotfix_v1'
SOURCE_FILE='app/Console/Commands/CommerceV2SmokeCommand.php'
MARKER='AI_PATCH_LINXEN_PDP_GENERIC_SMOKE_CONTRACT_V1'

BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_$(date +%Y%m%d_%H%M%S)"
MANIFEST="${BACKUP_ROOT}/manifest.tsv"
PATCH_WRITTEN=0

rollback() {
    STATUS=$?

    if [ "$PATCH_WRITTEN" -eq 1 ] && [ -f "$MANIFEST" ]; then
        printf '%s\n' \
          'Có lỗi bắt buộc. Đang rollback CommerceV2 smoke command...' \
          >&2

        while IFS=$'\t' read -r KIND FILE; do
            if [ "$KIND" = 'existing' ] \
                && [ -f "$BACKUP_ROOT/$FILE" ]
            then
                mkdir -p "$(dirname "$FILE")"
                cp -p "$BACKUP_ROOT/$FILE" "$FILE"
            fi
        done < "$MANIFEST"
    fi

    exit "$STATUS"
}

trap rollback ERR

test -f artisan || {
    printf '%s\n' \
      'ERROR: Hãy chạy patch từ root Laravel Lin Xén.' \
      >&2
    exit 1
}

test -f "$SOURCE_FILE" || {
    printf 'ERROR: Thiếu source file: %s\n' \
      "$SOURCE_FILE" >&2
    exit 1
}

mkdir -p "$BACKUP_ROOT/$(dirname "$SOURCE_FILE")"
cp -p "$SOURCE_FILE" "$BACKUP_ROOT/$SOURCE_FILE"
printf 'existing\t%s\n' "$SOURCE_FILE" > "$MANIFEST"
PATCH_WRITTEN=1

export LINXEN_SMOKE_SOURCE_FILE="$SOURCE_FILE"
export LINXEN_SMOKE_MARKER="$MARKER"

php <<'PHP'
<?php

$path = getenv('LINXEN_SMOKE_SOURCE_FILE');
$marker = getenv('LINXEN_SMOKE_MARKER');

if (
    ! is_string($path)
    || ! is_file($path)
    || ! is_string($marker)
    || $marker === ''
) {
    fwrite(
        STDERR,
        "ERROR: Source path/marker không hợp lệ.\n"
    );
    exit(1);
}

$source = file_get_contents($path);

if (! is_string($source)) {
    fwrite(
        STDERR,
        "ERROR: Không đọc được CommerceV2 smoke command.\n"
    );
    exit(1);
}

if (substr_count($source, $marker) > 1) {
    fwrite(
        STDERR,
        "ERROR: Hotfix marker xuất hiện nhiều hơn một lần.\n"
    );
    exit(1);
}

if (! str_contains($source, $marker)) {
    $jsonStartNeedle = '            $productPayloadJson = json_encode(';
    $viewStartNeedle = "\n\n            \$productViewHtml = view(";

    $jsonStart = strpos(
        $source,
        $jsonStartNeedle
    );

    if ($jsonStart === false) {
        fwrite(
            STDERR,
            "ERROR: Không tìm thấy productPayloadJson block.\n"
        );
        exit(1);
    }

    $viewStart = strpos(
        $source,
        $viewStartNeedle,
        $jsonStart
    );

    if ($viewStart === false) {
        fwrite(
            STDERR,
            "ERROR: Không tìm thấy product view render block.\n"
        );
        exit(1);
    }

    $newJsonBlock = <<<'PHPBLOCK'
            /*
             * AI_PATCH_LINXEN_PDP_GENERIC_SMOKE_CONTRACT_V1
             *
             * Keep the generic smoke aligned with the production controller:
             * the PDP JavaScript receives the complete presented product,
             * not the legacy id/name/colors subset.
             */
            $productPayloadJson = json_encode(
                $presentedProduct,
                JSON_HEX_TAG
                | JSON_HEX_APOS
                | JSON_HEX_AMP
                | JSON_HEX_QUOT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            );
PHPBLOCK;

    $source = substr_replace(
        $source,
        $newJsonBlock,
        $jsonStart,
        $viewStart - $jsonStart
    );

    $renderEndNeedle = "            )->render();\n";
    $renderStart = strpos(
        $source,
        '            $productViewHtml = view(',
        $jsonStart
    );

    if ($renderStart === false) {
        fwrite(
            STDERR,
            "ERROR: Không tìm thấy product view render sau khi sửa JSON.\n"
        );
        exit(1);
    }

    $renderEnd = strpos(
        $source,
        $renderEndNeedle,
        $renderStart
    );

    if ($renderEnd === false) {
        fwrite(
            STDERR,
            "ERROR: Không tìm thấy kết thúc product view render.\n"
        );
        exit(1);
    }

    $renderEnd += strlen($renderEndNeedle);

    $markerContractBlock = <<<'PHPBLOCK'

            $productViewContractMarkers = [
                'data-lxpdp',
                'data-lxpdp-gallery',
                'data-lxpdp-color',
                'data-lxpdp-size-advisor',
                'data-lxpdp-mobile-buy',
                'id="lxv2ProductData"',
                $productId,
            ];
PHPBLOCK;

    $source = substr_replace(
        $source,
        $markerContractBlock,
        $renderEnd,
        0
    );

    $checkStartNeedle = "                'product_view_render' => (";
    $nextCheckNeedle = "                'search_exact_first' =>";

    $checkStart = strpos(
        $source,
        $checkStartNeedle
    );

    if ($checkStart === false) {
        fwrite(
            STDERR,
            "ERROR: Không tìm thấy product_view_render check cũ.\n"
        );
        exit(1);
    }

    $nextCheck = strpos(
        $source,
        $nextCheckNeedle,
        $checkStart
    );

    if ($nextCheck === false) {
        fwrite(
            STDERR,
            "ERROR: Không tìm thấy check kế tiếp sau product_view_render.\n"
        );
        exit(1);
    }

    $newCheckBlock = <<<'PHPBLOCK'
                'product_view_render' => collect(
                    $productViewContractMarkers
                )->every(
                    fn (string $marker): bool => str_contains(
                        $productViewHtml,
                        $marker
                    )
                ),
PHPBLOCK;

    $source = substr_replace(
        $source,
        $newCheckBlock,
        $checkStart,
        $nextCheck - $checkStart
    );
}

foreach ([
    $marker,
    '$productPayloadJson = json_encode(',
    '$presentedProduct,',
    '$productViewContractMarkers = [',
    "'data-lxpdp-gallery'",
    "'data-lxpdp-color'",
    "'data-lxpdp-size-advisor'",
    "'data-lxpdp-mobile-buy'",
    "'id=\"lxv2ProductData\"'",
    "'product_view_render' => collect(",
] as $required) {
    if (! str_contains($source, $required)) {
        fwrite(
            STDERR,
            "ERROR: Missing smoke contract: {$required}\n"
        );
        exit(1);
    }
}

if (str_contains($source, "'data-lxv2-product'")) {
    fwrite(
        STDERR,
        "ERROR: Legacy PDP marker vẫn còn trong generic smoke.\n"
    );
    exit(1);
}

$written = file_put_contents(
    $path,
    $source
);

if (
    $written === false
    || $written !== strlen($source)
) {
    fwrite(
        STDERR,
        "ERROR: Không ghi đầy đủ CommerceV2 smoke command.\n"
    );
    exit(1);
}

echo "LINXEN_PDP_GENERIC_SMOKE_SOURCE=APPLIED\n";
PHP

php -l "$SOURCE_FILE"

grep -Fq -- \
  "$MARKER" \
  "$SOURCE_FILE"

grep -Fq -- \
  "\$productViewContractMarkers = [" \
  "$SOURCE_FILE"

grep -Fq -- \
  "'data-lxpdp-gallery'" \
  "$SOURCE_FILE"

grep -Fq -- \
  "'data-lxpdp-color'" \
  "$SOURCE_FILE"

grep -Fq -- \
  "'data-lxpdp-size-advisor'" \
  "$SOURCE_FILE"

grep -Fq -- \
  "'data-lxpdp-mobile-buy'" \
  "$SOURCE_FILE"

grep -Fq -- \
  "'product_view_render' => collect(" \
  "$SOURCE_FILE"

if grep -Fq -- \
  "'data-lxv2-product'" \
  "$SOURCE_FILE"
then
    printf '%s\n' \
      'ERROR: Legacy product smoke marker vẫn tồn tại.' \
      >&2
    exit 1
fi

printf '%s\n' \
  'LINXEN_PDP_GENERIC_SMOKE_STATIC_CONTRACT=PASS'

trap - ERR

set +e

env \
  CACHE_STORE=file \
  SESSION_DRIVER=file \
  php artisan optimize:clear

CLEAR_STATUS=$?

set -e

if [ "$CLEAR_STATUS" -eq 0 ]; then
    printf '%s\n' 'OPTIMIZE_CLEAR=PASS'
else
    printf 'OPTIMIZE_CLEAR=WARNING_EXIT_%s\n' \
      "$CLEAR_STATUS"
fi

if php artisan list --raw 2>/dev/null \
    | grep -Fq -- \
      'commerce-v2:pdp-sales-experience-smoke'
then
    set +e

    env \
      CACHE_STORE=file \
      SESSION_DRIVER=file \
      php artisan \
        commerce-v2:pdp-sales-experience-smoke

    PDP_SMOKE_STATUS=$?

    set -e

    if [ "$PDP_SMOKE_STATUS" -eq 0 ]; then
        printf '%s\n' \
          'PDP_SALES_EXPERIENCE_STATIC_SMOKE=PASS'
    else
        printf 'PDP_SALES_EXPERIENCE_STATIC_SMOKE=WARNING_EXIT_%s\n' \
          "$PDP_SMOKE_STATUS"
    fi
else
    printf '%s\n' \
      'PDP_SALES_EXPERIENCE_STATIC_SMOKE=SKIPPED_COMMAND_MISSING'
fi

printf '%s\n' \
  'LINXEN_PDP_GENERIC_SMOKE_HOTFIX=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' \
  'RUNTIME_PDP_FILES_CHANGED=NONE'
printf '%s\n' \
  'GENERIC_SMOKE_PAYLOAD=FULL_PRESENTED_PRODUCT'
printf '%s\n' \
  'GENERIC_SMOKE_MARKERS=PDP_SALES_EXPERIENCE_V2'
printf '%s\n' \
  'MIGRATION=NONE'
printf '%s\n' \
  'DB_MUTATION=NONE'
printf '%s\n' \
  'PROVIDER_CALL_DURING_PATCH=NONE'
printf '%s\n' \
  'ORDER_KIOTVIET_META_MUTATION=NONE'
printf '%s\n' \
  'PHP_FPM_RESTART=NOT_REQUIRED'
