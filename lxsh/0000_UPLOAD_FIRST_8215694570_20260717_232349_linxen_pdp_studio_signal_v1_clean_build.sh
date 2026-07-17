#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='linxen_pdp_studio_signal_v1_clean_build'
REGISTRY='app/Services/CommerceV2/Pdp/PdpVariantRegistry.php'
SECTIONS='app/Services/CommerceV2/Pdp/PdpSectionRegistry.php'
VARIANT_MARKER='AI_PATCH_LINXEN_PDP_STUDIO_SIGNAL_V1'
SECTION_MARKER='AI_PATCH_LINXEN_PDP_STUDIO_SIGNAL_SECTIONS_V1'
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
        printf '%s\n' 'Có lỗi bắt buộc. Đang rollback Studio Signal variant...' >&2

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

test -f artisan || {
    printf '%s\n' 'ERROR: Hãy chạy patch từ Laravel root của Lin Xén.' >&2
    exit 1
}

for FILE in \
  "$REGISTRY" \
  "$SECTIONS" \
  resources/views/commerce_v2/pdp/page.blade.php \
  resources/views/commerce_v2/pdp/sections/shared-size-advisor.blade.php \
  public/commerce-v2/pdp-sales-experience.css \
  public/commerce-v2/pdp-sales-experience.js \
  public/commerce-v2/pdp/v1/core.css \
  public/commerce-v2/pdp/v1/core.js
do
    test -f "$FILE" || {
        printf 'ERROR: Thiếu PDP presentation source: %s\n' "$FILE" >&2
        exit 1
    }
done

FILES=(
    'public/commerce-v2/pdp/v1/variants/studio-signal-v1.css'
    'public/commerce-v2/pdp/v1/variants/studio-signal-v1.js'
    'resources/views/commerce_v2/pdp/studio/hero-purchase.blade.php'
    'resources/views/commerce_v2/pdp/studio/quick-read.blade.php'
    'resources/views/commerce_v2/pdp/studio/design-explorer.blade.php'
    'resources/views/commerce_v2/pdp/studio/benefit-grid.blade.php'
    'resources/views/commerce_v2/pdp/studio/media-lab.blade.php'
    'resources/views/commerce_v2/pdp/studio/size-studio.blade.php'
    'resources/views/commerce_v2/pdp/studio/material-feel.blade.php'
    'resources/views/commerce_v2/pdp/studio/confidence-strip.blade.php'
    'resources/views/commerce_v2/pdp/studio/complete-look.blade.php'
    'resources/views/commerce_v2/pdp/studio/recently-viewed.blade.php'
    'resources/views/commerce_v2/pdp/studio/final-cta.blade.php'
)

backup_file "$REGISTRY"
backup_file "$SECTIONS"

for FILE in "${FILES[@]}"; do
    backup_file "$FILE"
done

PATCH_WRITTEN=1

export PDP_VARIANT_REGISTRY_FILE="$REGISTRY"
export PDP_SECTION_REGISTRY_FILE="$SECTIONS"
export PDP_VARIANT_MARKER="$VARIANT_MARKER"
export PDP_SECTION_MARKER="$SECTION_MARKER"

php <<'PHP'
<?php

$registryPath = getenv('PDP_VARIANT_REGISTRY_FILE');
$sectionPath = getenv('PDP_SECTION_REGISTRY_FILE');
$variantMarker = getenv('PDP_VARIANT_MARKER');
$sectionMarker = getenv('PDP_SECTION_MARKER');

$registry = file_get_contents($registryPath);
$sections = file_get_contents($sectionPath);

if (! is_string($registry) || ! is_string($sections)) {
    fwrite(STDERR, "ERROR: Không đọc được PDP registry source.\n");
    exit(1);
}

$variantEntry = <<<'ENTRY'
            /* AI_PATCH_LINXEN_PDP_STUDIO_SIGNAL_V1 */
            'studio_signal_v1' => [
                'key' => 'studio_signal_v1',
                'label' => 'Studio Signal V1',
                'version' => '1.0.0',
                'renderer' => 'sectioned',
                'view' => 'commerce_v2.pdp.page',
                'layout' => 'studio_signal_v1',
                'view_model_version' => PdpViewModelBuilder::VERSION,
                'sections' => [
                    'studio_hero_purchase',
                    'studio_quick_read',
                    'studio_design_explorer',
                    'studio_benefit_grid',
                    'studio_media_lab',
                    'studio_size_studio',
                    'studio_material_feel',
                    'studio_confidence_strip',
                    'studio_complete_look',
                    'studio_recently_viewed',
                    'studio_final_cta',
                ],
                'assets' => [
                    'styles' => [
                        'commerce-v2/pdp-sales-experience.css?v=3',
                        'commerce-v2/pdp/v1/core.css?v=1',
                        'commerce-v2/pdp/v1/variants/studio-signal-v1.css?v=1',
                    ],
                    'scripts' => [
                        'commerce-v2/pdp/v1/variants/studio-signal-v1.js?v=1',
                    ],
                ],
                'art_direction' => [
                    'concept' => 'digital_fashion_studio',
                    'palette' => 'porcelain_graphite_signal_cherry',
                    'content_density' => 'visual_first_readable',
                    'mobile_navigation' => 'single_row_contextual_commerce_dock',
                    'empty_sections' => 'hide',
                ],
                'enabled' => true,
            ],
ENTRY;

$sectionEntries = <<<'ENTRY'
            /* AI_PATCH_LINXEN_PDP_STUDIO_SIGNAL_SECTIONS_V1 */
            'studio_hero_purchase' => [
                'view' => 'commerce_v2.pdp.studio.hero-purchase',
                'required' => ['identity.id', 'commerce.colors'],
                'empty_behavior' => 'render',
            ],
            'studio_quick_read' => [
                'view' => 'commerce_v2.pdp.studio.quick-read',
                'required_any' => [
                    'product_truth.highlights',
                    'product_truth.design.items',
                    'fit.fit_items',
                    'product_truth.materials.section.items',
                ],
                'empty_behavior' => 'hide',
            ],
            'studio_design_explorer' => [
                'view' => 'commerce_v2.pdp.studio.design-explorer',
                'required_any' => [
                    'product_truth.highlights',
                    'product_truth.design.items',
                ],
                'empty_behavior' => 'hide',
            ],
            'studio_benefit_grid' => [
                'view' => 'commerce_v2.pdp.studio.benefit-grid',
                'required_any' => [
                    'product_truth.highlights',
                    'product_truth.design.items',
                ],
                'empty_behavior' => 'hide',
            ],
            'studio_media_lab' => [
                'view' => 'commerce_v2.pdp.studio.media-lab',
                'required_any' => [
                    'commerce.default_color.media',
                    'media.production_truth',
                ],
                'empty_behavior' => 'hide',
            ],
            'studio_size_studio' => [
                'view' => 'commerce_v2.pdp.studio.size-studio',
                'required' => ['fit.garment_size_chart.points'],
                'empty_behavior' => 'hide',
            ],
            'studio_material_feel' => [
                'view' => 'commerce_v2.pdp.studio.material-feel',
                'required_any' => [
                    'product_truth.materials.main',
                    'product_truth.materials.lining',
                    'product_truth.materials.section.items',
                ],
                'empty_behavior' => 'hide',
            ],
            'studio_confidence_strip' => [
                'view' => 'commerce_v2.pdp.studio.confidence-strip',
                'required' => ['policies.cod.enabled'],
                'empty_behavior' => 'render',
            ],
            'studio_complete_look' => [
                'view' => 'commerce_v2.pdp.studio.complete-look',
                'required' => ['discovery.related_products'],
                'empty_behavior' => 'hide',
            ],
            'studio_recently_viewed' => [
                'view' => 'commerce_v2.pdp.studio.recently-viewed',
                'required' => ['discovery.recently_viewed_enabled'],
                'empty_behavior' => 'render',
            ],
            'studio_final_cta' => [
                'view' => 'commerce_v2.pdp.studio.final-cta',
                'required' => ['identity.id'],
                'empty_behavior' => 'render',
            ],
ENTRY;

$registryAnchor = "\n        ];\n    }\n\n    public function get";
$sectionAnchor = "\n        ];\n    }\n\n    public function compose";

if (! str_contains($registry, "'studio_signal_v1' => [")) {
    if (substr_count($registry, $registryAnchor) !== 1) {
        fwrite(STDERR, "ERROR: PDP variant registry anchor không duy nhất.\n");
        exit(1);
    }

    $registry = str_replace(
        $registryAnchor,
        "\n" . rtrim($variantEntry) . $registryAnchor,
        $registry,
        $count
    );

    if ($count !== 1) {
        fwrite(STDERR, "ERROR: Không chèn được Studio Signal variant.\n");
        exit(1);
    }
}

if (! str_contains($sections, "'studio_hero_purchase' => [")) {
    if (substr_count($sections, $sectionAnchor) !== 1) {
        fwrite(STDERR, "ERROR: PDP section registry anchor không duy nhất.\n");
        exit(1);
    }

    $sections = str_replace(
        $sectionAnchor,
        "\n" . rtrim($sectionEntries) . $sectionAnchor,
        $sections,
        $count
    );

    if ($count !== 1) {
        fwrite(STDERR, "ERROR: Không chèn được Studio Signal sections.\n");
        exit(1);
    }
}

foreach ([
    $variantMarker,
    "'studio_signal_v1' => [",
    "'studio_hero_purchase'",
    "'studio_quick_read'",
    "'studio_design_explorer'",
    "'studio_benefit_grid'",
    "'studio_media_lab'",
    "'studio_size_studio'",
    "'studio_material_feel'",
    "'studio_confidence_strip'",
    "'studio_complete_look'",
    "'studio_recently_viewed'",
    "'studio_final_cta'",
    'studio-signal-v1.css?v=1',
    'studio-signal-v1.js?v=1',
] as $required) {
    if (! str_contains($registry, $required)) {
        fwrite(STDERR, "ERROR: Thiếu Studio Signal variant contract: {$required}\n");
        exit(1);
    }
}

foreach ([
    $sectionMarker,
    "'studio_hero_purchase' => [",
    "'studio_quick_read' => [",
    "'studio_design_explorer' => [",
    "'studio_benefit_grid' => [",
    "'studio_media_lab' => [",
    "'studio_size_studio' => [",
    "'studio_material_feel' => [",
    "'studio_confidence_strip' => [",
    "'studio_complete_look' => [",
    "'studio_recently_viewed' => [",
    "'studio_final_cta' => [",
] as $required) {
    if (! str_contains($sections, $required)) {
        fwrite(STDERR, "ERROR: Thiếu Studio Signal section contract: {$required}\n");
        exit(1);
    }
}

foreach ([$registryPath => $registry, $sectionPath => $sections] as $path => $source) {
    $written = file_put_contents($path, $source);

    if ($written === false || $written !== strlen($source)) {
        fwrite(STDERR, "ERROR: Không ghi đầy đủ source: {$path}\n");
        exit(1);
    }
}

echo "PDP_STUDIO_SIGNAL_REGISTRY=APPLIED\n";
echo "PDP_STUDIO_SIGNAL_SECTION_REGISTRY=APPLIED\n";
PHP

decode_to_file() {
    FILE="$1"
    TMP_FILE="$(mktemp "${TMPDIR:-/tmp}/linxen_studio_signal.XXXXXX")"
    mkdir -p "$(dirname "$FILE")"

    if printf 'Zg==' | base64 --decode >/dev/null 2>&1; then
        base64 --decode > "$TMP_FILE"
    else
        base64 -D > "$TMP_FILE"
    fi

    mv "$TMP_FILE" "$FILE"
    chmod 0644 "$FILE"
}

decode_to_file 'public/commerce-v2/pdp/v1/variants/studio-signal-v1.css' <<'STUDIO_SIGNAL_PAYLOAD_1'
LyoKICogTElOIFjDiU4g4oCUIFN0dWRpbyBTaWduYWwgUERQIFYxCiAqIERpZ2l0YWwgZmFzaGlv
biBzdHVkaW8gYXJ0IGRpcmVjdGlvbi4KICogVmFyaWFudC1zY29wZWQ6IG5vIGNoYW5nZSB0byBD
bGFzc2ljLCBFZGl0b3JpYWwgR3VpZGVkIG9yIEF0ZWxpZXIgRWRpdG9yaWFsLgogKi8KCi5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gewogICAgLS1seHMtY2FudmFz
OiAjZjRmN2ZjOwogICAgLS1seHMtY2FudmFzLWRlZXA6ICNlOGVkZmE7CiAgICAtLWx4cy1zdXJm
YWNlOiAjZmZmZmZmOwogICAgLS1seHMtc3VyZmFjZS1zb2Z0OiAjZWVmMWZmOwogICAgLS1seHMt
c3VyZmFjZS1taW50OiAjZTdmOGYyOwogICAgLS1seHMtc3VyZmFjZS1yb3NlOiAjZmZmMGY0Owog
ICAgLS1seHMtaW5rOiAjMTExMzFhOwogICAgLS1seHMtaW5rLXNvZnQ6ICM2NjcwODU7CiAgICAt
LWx4cy1pbmstZmFpbnQ6ICM5OGEyYjM7CiAgICAtLWx4cy1saW5lOiAjZGNlM2VmOwogICAgLS1s
eHMtcHJpbWFyeTogIzViNWZmMjsKICAgIC0tbHhzLXByaW1hcnktZGFyazogIzQwNDRjZTsKICAg
IC0tbHhzLXByaW1hcnktc29mdDogI2U3ZThmZjsKICAgIC0tbHhzLXNpZ25hbDogI2ZmNDE2YzsK
ICAgIC0tbHhzLXNpZ25hbC1kYXJrOiAjZTkyZDU5OwogICAgLS1seHMtc3VjY2VzczogIzE3Nzk1
YjsKICAgIC0tbHhzLWRhbmdlcjogI2M5MzY1NTsKICAgIC0tbHhzLWdyYXBoaXRlOiAjMTYxOTIz
OwogICAgLS1seHMtc2hhZG93LXNtOiAwIDEycHggMzRweCByZ2JhKDI4LCAzOSwgNzEsIC4wOCk7
CiAgICAtLWx4cy1zaGFkb3ctbGc6IDAgMjhweCA5MHB4IHJnYmEoMjcsIDM3LCA2OCwgLjE0KTsK
ICAgIC0tbHhzLXJhZGl1cy1zbTogMTZweDsKICAgIC0tbHhzLXJhZGl1czogMjZweDsKICAgIC0t
bHhzLXJhZGl1cy1sZzogMzhweDsKICAgIC0tbHhzLW1heDogMTM4MHB4OwogICAgY29sb3I6IHZh
cigtLWx4cy1pbmspOwogICAgZm9udC1mYW1pbHk6IEludGVyLCB1aS1zYW5zLXNlcmlmLCBzeXN0
ZW0tdWksIC1hcHBsZS1zeXN0ZW0sIEJsaW5rTWFjU3lzdGVtRm9udCwgIlNlZ29lIFVJIiwgc2Fu
cy1zZXJpZjsKICAgIGlzb2xhdGlvbjogaXNvbGF0ZTsKfQoKYm9keTpoYXMoLmx4cGRwW2RhdGEt
cGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSksCmJvZHkubHgtcGRwLXN0dWRpby1zaWdu
YWwgewogICAgLS1seHYyLWJnOiAjZjRmN2ZjOwogICAgLS1seHYyLXN1cmZhY2U6ICNmZmZmZmY7
CiAgICAtLWx4djItdGV4dDogIzExMTMxYTsKICAgIC0tbHh2Mi1tdXRlZDogIzY2NzA4NTsKICAg
IC0tbHh2Mi1saW5lOiAjZGNlM2VmOwogICAgLS1seHYyLWFjY2VudDogIzViNWZmMjsKICAgIC0t
bHh2Mi1hY2NlbnQtZGFyazogIzQwNDRjZTsKICAgIC0tbHh2Mi1zb2Z0OiAjZWVmMWZmOwogICAg
LS1seHYyLXN1Y2Nlc3M6ICMxNzc5NWI7CiAgICBiYWNrZ3JvdW5kOgogICAgICAgIHJhZGlhbC1n
cmFkaWVudChjaXJjbGUgYXQgOCUgMCUsIHJnYmEoOTEsIDk1LCAyNDIsIC4xMyksIHRyYW5zcGFy
ZW50IDM0cmVtKSwKICAgICAgICByYWRpYWwtZ3JhZGllbnQoY2lyY2xlIGF0IDEwMCUgMTQlLCBy
Z2JhKDI1NSwgNjUsIDEwOCwgLjA5KSwgdHJhbnNwYXJlbnQgMjhyZW0pLAogICAgICAgIGxpbmVh
ci1ncmFkaWVudCgxODBkZWcsICNmOGZhZmYgMCUsICNmNGY3ZmMgNDYlLCAjZjdmOWZlIDEwMCUp
Owp9Cgpib2R5OmhhcygubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
KSAubHh2Mi1oZWFkZXIsCmJvZHkubHgtcGRwLXN0dWRpby1zaWduYWwgLmx4djItaGVhZGVyIHsK
ICAgIGJvcmRlci1ib3R0b20tY29sb3I6IHJnYmEoMjIwLCAyMjcsIDIzOSwgLjgyKTsKICAgIGJh
Y2tncm91bmQ6IHJnYmEoMjQ4LCAyNTAsIDI1NSwgLjg0KTsKICAgIGJveC1zaGFkb3c6IDAgOHB4
IDMwcHggcmdiYSgzMSwgNDMsIDc4LCAuMDQpOwogICAgYmFja2Ryb3AtZmlsdGVyOiBibHVyKDIy
cHgpIHNhdHVyYXRlKDE1MCUpOwp9Cgpib2R5OmhhcygubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0i
c3R1ZGlvX3NpZ25hbF92MSJdKSAubHh2Mi1icmFuZF9fbWFyaywKYm9keS5seC1wZHAtc3R1ZGlv
LXNpZ25hbCAubHh2Mi1icmFuZF9fbWFyayB7CiAgICBib3JkZXItcmFkaXVzOiAxNHB4OwogICAg
YmFja2dyb3VuZDogbGluZWFyLWdyYWRpZW50KDEzNWRlZywgdmFyKC0tbHhzLXByaW1hcnksICM1
YjVmZjIpLCB2YXIoLS1seHMtc2lnbmFsLCAjZmY0MTZjKSk7CiAgICBib3gtc2hhZG93OiAwIDlw
eCAyNHB4IHJnYmEoOTEsIDk1LCAyNDIsIC4yNSk7CiAgICBmb250LWZhbWlseTogaW5oZXJpdDsK
ICAgIGZvbnQtd2VpZ2h0OiA5MDA7Cn0KCmJvZHk6aGFzKC5seHBkcFtkYXRhLXBkcC12YXJpYW50
PSJzdHVkaW9fc2lnbmFsX3YxIl0pIC5seHYyLWJyYW5kX190ZXh0IHN0cm9uZywKYm9keS5seC1w
ZHAtc3R1ZGlvLXNpZ25hbCAubHh2Mi1icmFuZF9fdGV4dCBzdHJvbmcgewogICAgZm9udC1mYW1p
bHk6IGluaGVyaXQ7CiAgICBmb250LXdlaWdodDogOTAwOwogICAgbGV0dGVyLXNwYWNpbmc6IC0u
MDE1ZW07Cn0KCmJvZHk6aGFzKC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFs
X3YxIl0pIC5seHYyLW5hdiBhLApib2R5Lmx4LXBkcC1zdHVkaW8tc2lnbmFsIC5seHYyLW5hdiBh
IHsKICAgIGJvcmRlci1ib3R0b206IDA7CiAgICBib3JkZXItcmFkaXVzOiA5OTlweDsKICAgIHBh
ZGRpbmctaW5saW5lOiAxMXB4Owp9Cgpib2R5OmhhcygubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0i
c3R1ZGlvX3NpZ25hbF92MSJdKSAubHh2Mi1uYXYgYTpob3ZlciwKYm9keTpoYXMoLmx4cGRwW2Rh
dGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSkgLmx4djItbmF2IGEuYWN0aXZlLApi
b2R5Lmx4LXBkcC1zdHVkaW8tc2lnbmFsIC5seHYyLW5hdiBhOmhvdmVyLApib2R5Lmx4LXBkcC1z
dHVkaW8tc2lnbmFsIC5seHYyLW5hdiBhLmFjdGl2ZSB7CiAgICBjb2xvcjogdmFyKC0tbHhzLXBy
aW1hcnksICM1YjVmZjIpOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhzLXByaW1hcnktc29mdCwg
I2U3ZThmZik7Cn0KCmJvZHk6aGFzKC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2ln
bmFsX3YxIl0pIC5seHYyLWhlYWRlci1zZWFyY2gsCmJvZHkubHgtcGRwLXN0dWRpby1zaWduYWwg
Lmx4djItaGVhZGVyLXNlYXJjaCB7CiAgICBib3JkZXItY29sb3I6ICNkNWRjZWM7CiAgICBib3gt
c2hhZG93OiBpbnNldCAwIDAgMCAxcHggcmdiYSgyNTUsIDI1NSwgMjU1LCAuNjUpOwp9Cgpib2R5
OmhhcygubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdKSAubHh2Mi1t
YWluLApib2R5Lmx4LXBkcC1zdHVkaW8tc2lnbmFsIC5seHYyLW1haW4gewogICAgcGFkZGluZy10
b3A6IDE4cHg7Cn0KCmJvZHk6aGFzKC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2ln
bmFsX3YxIl0pIC5seHYyLWZvb3RlciwKYm9keS5seC1wZHAtc3R1ZGlvLXNpZ25hbCAubHh2Mi1m
b290ZXIgewogICAgYm9yZGVyOiAxcHggc29saWQgcmdiYSgyNTUsIDI1NSwgMjU1LCAuMDgpOwog
ICAgY29sb3I6ICNmZmY7CiAgICBiYWNrZ3JvdW5kOgogICAgICAgIHJhZGlhbC1ncmFkaWVudChj
aXJjbGUgYXQgMTAlIDEwJSwgcmdiYSg5MSwgOTUsIDI0MiwgLjI1KSwgdHJhbnNwYXJlbnQgMjZy
ZW0pLAogICAgICAgIHJhZGlhbC1ncmFkaWVudChjaXJjbGUgYXQgMTAwJSAxMDAlLCByZ2JhKDI1
NSwgNjUsIDEwOCwgLjE3KSwgdHJhbnNwYXJlbnQgMjRyZW0pLAogICAgICAgICMxNjE5MjM7CiAg
ICBib3gtc2hhZG93OiAwIDI2cHggODBweCByZ2JhKDE2LCAyMiwgNDIsIC4xOCk7Cn0KCmJvZHk6
aGFzKC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0pIC5seHYyLWZv
b3RlciBzdHJvbmcsCmJvZHkubHgtcGRwLXN0dWRpby1zaWduYWwgLmx4djItZm9vdGVyIHN0cm9u
ZyB7CiAgICBmb250LWZhbWlseTogaW5oZXJpdDsKICAgIGZvbnQtd2VpZ2h0OiA5MDA7Cn0KCmJv
ZHk6aGFzKC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0pIC5seHYy
LWZvb3RlciBwLApib2R5Lmx4LXBkcC1zdHVkaW8tc2lnbmFsIC5seHYyLWZvb3RlciBwIHsKICAg
IGNvbG9yOiAjYjhjMGQyOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25h
bF92MSJdIC5seHBkcC1wcmV2aWV3LWJhbm5lciB7CiAgICBib3JkZXI6IDFweCBzb2xpZCByZ2Jh
KDkxLCA5NSwgMjQyLCAuMik7CiAgICBib3JkZXItcmFkaXVzOiAxOHB4OwogICAgY29sb3I6IHZh
cigtLWx4cy1pbmspOwogICAgYmFja2dyb3VuZDogcmdiYSgyMzgsIDI0MSwgMjU1LCAuOTIpOwog
ICAgYm94LXNoYWRvdzogdmFyKC0tbHhzLXNoYWRvdy1zbSk7Cn0KCi5seHBkcFtkYXRhLXBkcC12
YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cGRwLXByZXZpZXctYmFubmVyIGEgewogICAg
Y29sb3I6IHZhcigtLWx4cy1wcmltYXJ5KTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19zaWduYWxfdjEiXSAubHhwZHBfX2JyZWFkY3J1bWIgewogICAgd2lkdGg6IG1pbih2YXIo
LS1seHMtbWF4KSwgY2FsYygxMDAlIC0gNDhweCkpOwogICAgbWFyZ2luOiA4cHggYXV0byAxOHB4
OwogICAgY29sb3I6IHZhcigtLWx4cy1pbmstc29mdCk7CiAgICBmb250LXNpemU6IDEycHg7CiAg
ICBmb250LXdlaWdodDogNzAwOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3Np
Z25hbF92MSJdIC5seHBkcC1lbmdpbmUtc2VjdGlvbiB7CiAgICB3aWR0aDogMTAwdnc7CiAgICBt
YXJnaW4tbGVmdDogY2FsYyg1MCUgLSA1MHZ3KTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9
InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNoZWxsIHsKICAgIHdpZHRoOiBtaW4odmFyKC0tbHhz
LW1heCksIGNhbGMoMTAwJSAtIDQ4cHgpKTsKICAgIG1hcmdpbi1pbmxpbmU6IGF1dG87Cn0KCi5s
eHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1raWNrZXIgewog
ICAgbWFyZ2luOiAwIDAgMTBweDsKICAgIGNvbG9yOiB2YXIoLS1seHMtcHJpbWFyeSk7CiAgICBm
b250LXNpemU6IDExcHg7CiAgICBmb250LXdlaWdodDogOTAwOwogICAgbGV0dGVyLXNwYWNpbmc6
IC4xOGVtOwogICAgdGV4dC10cmFuc2Zvcm06IHVwcGVyY2FzZTsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNlY3Rpb24taGVhZGluZyB7CiAgICBt
YXgtd2lkdGg6IDc2MHB4OwogICAgbWFyZ2luLWJvdHRvbTogMzJweDsKfQoKLmx4cGRwW2RhdGEt
cGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNlY3Rpb24taGVhZGluZy0tY29t
cGFjdCB7CiAgICBtYXgtd2lkdGg6IDYwMHB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0i
c3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2VjdGlvbi1oZWFkaW5nLS1zcGxpdCB7CiAgICBtYXgt
d2lkdGg6IG5vbmU7CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5z
OiBtaW5tYXgoMCwgMS4xNWZyKSBtaW5tYXgoMjgwcHgsIC42NWZyKTsKICAgIGdhcDogNzBweDsK
ICAgIGFsaWduLWl0ZW1zOiBlbmQ7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9f
c2lnbmFsX3YxIl0gLmx4cy1zZWN0aW9uLWhlYWRpbmcgaDIgewogICAgbWFyZ2luOiAwOwogICAg
bWF4LXdpZHRoOiA3ODBweDsKICAgIGZvbnQtc2l6ZTogY2xhbXAoMzBweCwgMy40dncsIDQ4cHgp
OwogICAgZm9udC13ZWlnaHQ6IDg1MDsKICAgIGxpbmUtaGVpZ2h0OiAxLjAzOwogICAgbGV0dGVy
LXNwYWNpbmc6IC0uMDQ1ZW07Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2ln
bmFsX3YxIl0gLmx4cy1zZWN0aW9uLWhlYWRpbmcgcDpub3QoLmx4cy1raWNrZXIpIHsKICAgIG1h
cmdpbjogMTRweCAwIDA7CiAgICBjb2xvcjogdmFyKC0tbHhzLWluay1zb2Z0KTsKICAgIGZvbnQt
c2l6ZTogMTZweDsKICAgIGxpbmUtaGVpZ2h0OiAxLjY1Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFy
aWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2VjdGlvbi1oZWFkaW5nLS1zcGxpdCA+IHAs
Ci5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zZWN0aW9u
LWhlYWRpbmctLXNwbGl0ID4gYSB7CiAgICBtYXJnaW46IDA7CiAgICBhbGlnbi1zZWxmOiBlbmQ7
Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zZWN0
aW9uLWhlYWRpbmctLXNwbGl0ID4gYSB7CiAgICBqdXN0aWZ5LXNlbGY6IGVuZDsKICAgIGNvbG9y
OiB2YXIoLS1seHMtcHJpbWFyeSk7CiAgICBmb250LXdlaWdodDogODUwOwp9CgoubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2VjdGlvbi1oZWFkaW5nLS1s
aWdodCBoMiwKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhz
LXNlY3Rpb24taGVhZGluZy0tbGlnaHQgcCB7CiAgICBjb2xvcjogdmFyKC0tbHhzLWluayk7Cn0K
Ci5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gc3ZnIHsKICAgIGZp
bGw6IG5vbmU7CiAgICBzdHJva2U6IGN1cnJlbnRDb2xvcjsKICAgIHN0cm9rZS13aWR0aDogMS44
OwogICAgc3Ryb2tlLWxpbmVjYXA6IHJvdW5kOwogICAgc3Ryb2tlLWxpbmVqb2luOiByb3VuZDsK
fQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSBidXR0b24sCi5s
eHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gYSB7CiAgICAtd2Via2l0
LXRhcC1oaWdobGlnaHQtY29sb3I6IHRyYW5zcGFyZW50Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFy
aWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIGJ1dHRvbjpmb2N1cy12aXNpYmxlLAoubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIGE6Zm9jdXMtdmlzaWJsZSB7CiAgICBv
dXRsaW5lOiAzcHggc29saWQgcmdiYSg5MSwgOTUsIDI0MiwgLjI4KTsKICAgIG91dGxpbmUtb2Zm
c2V0OiAzcHg7Cn0KCi8qIEhlcm8gKi8KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9f
c2lnbmFsX3YxIl0gLmx4cGRwLWVuZ2luZS1zZWN0aW9uLS1zdHVkaW9faGVyb19wdXJjaGFzZSB7
CiAgICBwYWRkaW5nOiAwIDAgNzRweDsKICAgIGJhY2tncm91bmQ6CiAgICAgICAgbGluZWFyLWdy
YWRpZW50KDE4MGRlZywgcmdiYSgyNTUsMjU1LDI1NSwuNzQpLCByZ2JhKDI0NCwyNDcsMjUyLC44
NikpLAogICAgICAgIHJhZGlhbC1ncmFkaWVudChjaXJjbGUgYXQgMTglIDYlLCByZ2JhKDkxLDk1
LDI0MiwuMTIpLCB0cmFuc3BhcmVudCAzNHJlbSk7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50
PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1oZXJvIHsKICAgIG1pbi1oZWlnaHQ6IG1pbig4MjBw
eCwgY2FsYygxMDB2aCAtIDExOHB4KSk7CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ3JpZC10ZW1w
bGF0ZS1jb2x1bW5zOiBtaW5tYXgoMCwgMS40MmZyKSBtaW5tYXgoMzkwcHgsIC43MmZyKTsKICAg
IGdhcDogMjhweDsKICAgIGFsaWduLWl0ZW1zOiBzdHJldGNoOwp9CgoubHhwZHBbZGF0YS1wZHAt
dmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtaGVyb19fZ2FsbGVyeS1jb2x1bW4gewog
ICAgbWluLXdpZHRoOiAwOwogICAgZGlzcGxheTogZ3JpZDsKICAgIGdyaWQtdGVtcGxhdGUtcm93
czogYXV0byAxZnI7CiAgICBnYXA6IDEycHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJz
dHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1oZXJvX190b3BsaW5lIHsKICAgIGRpc3BsYXk6IGZsZXg7
CiAgICBqdXN0aWZ5LWNvbnRlbnQ6IHNwYWNlLWJldHdlZW47CiAgICBjb2xvcjogdmFyKC0tbHhz
LWluay1zb2Z0KTsKICAgIGZvbnQtc2l6ZTogMTBweDsKICAgIGZvbnQtd2VpZ2h0OiA5MDA7CiAg
ICBsZXR0ZXItc3BhY2luZzogLjE2ZW07CiAgICB0ZXh0LXRyYW5zZm9ybTogdXBwZXJjYXNlOwp9
CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZ2FsbGVy
eSB7CiAgICBtaW4td2lkdGg6IDA7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9f
c2lnbmFsX3YxIl0gLmx4cy1nYWxsZXJ5X19zdGFnZSB7CiAgICBwb3NpdGlvbjogcmVsYXRpdmU7
CiAgICBoZWlnaHQ6IG1pbig3MjBweCwgY2FsYygxMDB2aCAtIDE3MHB4KSk7CiAgICBtaW4taGVp
Z2h0OiA2MjBweDsKICAgIG92ZXJmbG93OiBoaWRkZW47CiAgICBib3JkZXI6IDFweCBzb2xpZCBy
Z2JhKDIyMCwgMjI3LCAyMzksIC45KTsKICAgIGJvcmRlci1yYWRpdXM6IDM0cHg7CiAgICBiYWNr
Z3JvdW5kOgogICAgICAgIGxpbmVhci1ncmFkaWVudCgxNDVkZWcsICNlZGYxZmEsICNmZmYpLAog
ICAgICAgICNlZWYyZjg7CiAgICBib3gtc2hhZG93OiB2YXIoLS1seHMtc2hhZG93LWxnKTsKfQoK
Lmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWdhbGxlcnlf
X2ZpZ3VyZSB7CiAgICB3aWR0aDogMTAwJTsKICAgIGhlaWdodDogMTAwJTsKICAgIG1hcmdpbjog
MDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWdh
bGxlcnlfX2ZpZ3VyZTo6YmVmb3JlIHsKICAgIGNvbnRlbnQ6ICIiOwogICAgcG9zaXRpb246IGFi
c29sdXRlOwogICAgaW5zZXQ6IDA7CiAgICB6LWluZGV4OiAxOwogICAgcG9pbnRlci1ldmVudHM6
IG5vbmU7CiAgICBiYWNrZ3JvdW5kOgogICAgICAgIGxpbmVhci1ncmFkaWVudCgxODBkZWcsIHRy
YW5zcGFyZW50IDY4JSwgcmdiYSgxMCwgMTMsIDIzLCAuMikpLAogICAgICAgIGxpbmVhci1ncmFk
aWVudCg5MGRlZywgcmdiYSgyNTUsMjU1LDI1NSwuMTUpLCB0cmFuc3BhcmVudCAzMCUpOwp9Cgou
bHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZ2FsbGVyeV9f
ZmlndXJlIGltZyB7CiAgICB3aWR0aDogMTAwJTsKICAgIGhlaWdodDogMTAwJTsKICAgIG9iamVj
dC1maXQ6IGNvdmVyOwogICAgdHJhbnNpdGlvbjogb3BhY2l0eSAuMjhzIGVhc2UsIHRyYW5zZm9y
bSAuNnMgY3ViaWMtYmV6aWVyKC4yLC43LC4yLDEpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFu
dD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZ2FsbGVyeV9fZmlndXJlLmlzLWxvYWRpbmcgaW1n
IHsKICAgIG9wYWNpdHk6IC40NTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19z
aWduYWxfdjEiXSAubHhzLWdhbGxlcnlfX21ldGEgewogICAgcG9zaXRpb246IGFic29sdXRlOwog
ICAgei1pbmRleDogMjsKICAgIGxlZnQ6IDIycHg7CiAgICByaWdodDogMjJweDsKICAgIGJvdHRv
bTogMjBweDsKICAgIGRpc3BsYXk6IGZsZXg7CiAgICBqdXN0aWZ5LWNvbnRlbnQ6IHNwYWNlLWJl
dHdlZW47CiAgICBhbGlnbi1pdGVtczogY2VudGVyOwogICAgY29sb3I6ICNmZmY7CiAgICBmb250
LXNpemU6IDEycHg7CiAgICBmb250LXdlaWdodDogODUwOwogICAgbGV0dGVyLXNwYWNpbmc6IC4w
NmVtOwogICAgdGV4dC10cmFuc2Zvcm06IHVwcGVyY2FzZTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWdhbGxlcnlfX25hdiB7CiAgICBwb3NpdGlv
bjogYWJzb2x1dGU7CiAgICB6LWluZGV4OiA1OwogICAgdG9wOiA1MCU7CiAgICB3aWR0aDogNDZw
eDsKICAgIGhlaWdodDogNDZweDsKICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBwbGFjZS1pdGVtczog
Y2VudGVyOwogICAgdHJhbnNmb3JtOiB0cmFuc2xhdGVZKC01MCUpOwogICAgYm9yZGVyOiAxcHgg
c29saWQgcmdiYSgyNTUsMjU1LDI1NSwuNyk7CiAgICBib3JkZXItcmFkaXVzOiA1MCU7CiAgICBj
b2xvcjogdmFyKC0tbHhzLWluayk7CiAgICBiYWNrZ3JvdW5kOiByZ2JhKDI1NSwyNTUsMjU1LC44
Nik7CiAgICBib3gtc2hhZG93OiAwIDEycHggMzBweCByZ2JhKDEyLDE4LDM2LC4xMik7CiAgICBi
YWNrZHJvcC1maWx0ZXI6IGJsdXIoMTRweCk7CiAgICBjdXJzb3I6IHBvaW50ZXI7CiAgICB0cmFu
c2l0aW9uOiB0cmFuc2Zvcm0gLjJzIGVhc2UsIGJhY2tncm91bmQgLjJzIGVhc2U7Cn0KCi5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1nYWxsZXJ5X19uYXY6
aG92ZXIgewogICAgdHJhbnNmb3JtOiB0cmFuc2xhdGVZKC01MCUpIHNjYWxlKDEuMDYpOwogICAg
YmFja2dyb3VuZDogI2ZmZjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWdu
YWxfdjEiXSAubHhzLWdhbGxlcnlfX25hdiBzdmcgewogICAgd2lkdGg6IDIycHg7CiAgICBoZWln
aHQ6IDIycHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0g
Lmx4cy1nYWxsZXJ5X19uYXYtLXByZXYgewogICAgbGVmdDogMThweDsKfQoKLmx4cGRwW2RhdGEt
cGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWdhbGxlcnlfX25hdi0tbmV4dCB7
CiAgICByaWdodDogMThweDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWdu
YWxfdjEiXSAubHhzLWdhbGxlcnlfX3RodW1icyB7CiAgICBtYXJnaW4tdG9wOiAxMnB4OwogICAg
ZGlzcGxheTogZ3JpZDsKICAgIGdyaWQtYXV0by1mbG93OiBjb2x1bW47CiAgICBncmlkLWF1dG8t
Y29sdW1uczogNzJweDsKICAgIGp1c3RpZnktY29udGVudDogc3RhcnQ7CiAgICBnYXA6IDlweDsK
ICAgIG92ZXJmbG93LXg6IGF1dG87CiAgICBzY3JvbGxiYXItd2lkdGg6IG5vbmU7Cn0KCi5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1nYWxsZXJ5X190aHVt
YnM6Oi13ZWJraXQtc2Nyb2xsYmFyIHsKICAgIGRpc3BsYXk6IG5vbmU7Cn0KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1nYWxsZXJ5X190aHVtYiB7CiAg
ICBhc3BlY3QtcmF0aW86IDQgLyA1OwogICAgb3ZlcmZsb3c6IGhpZGRlbjsKICAgIHBhZGRpbmc6
IDA7CiAgICBib3JkZXI6IDJweCBzb2xpZCB0cmFuc3BhcmVudDsKICAgIGJvcmRlci1yYWRpdXM6
IDE0cHg7CiAgICBiYWNrZ3JvdW5kOiB2YXIoLS1seHMtY2FudmFzLWRlZXApOwogICAgb3BhY2l0
eTogLjYyOwogICAgY3Vyc29yOiBwb2ludGVyOwogICAgdHJhbnNpdGlvbjogb3BhY2l0eSAuMnMg
ZWFzZSwgYm9yZGVyLWNvbG9yIC4ycyBlYXNlLCB0cmFuc2Zvcm0gLjJzIGVhc2U7Cn0KCi5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1nYWxsZXJ5X190aHVt
Yjpob3ZlciwKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhz
LWdhbGxlcnlfX3RodW1iLmlzLWFjdGl2ZSB7CiAgICBvcGFjaXR5OiAxOwogICAgYm9yZGVyLWNv
bG9yOiB2YXIoLS1seHMtcHJpbWFyeSk7CiAgICB0cmFuc2Zvcm06IHRyYW5zbGF0ZVkoLTJweCk7
Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1nYWxs
ZXJ5X190aHVtYiBpbWcgewogICAgd2lkdGg6IDEwMCU7CiAgICBoZWlnaHQ6IDEwMCU7CiAgICBv
YmplY3QtZml0OiBjb3ZlcjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWdu
YWxfdjEiXSAubHhzLWdhbGxlcnlfX25vdGljZSB7CiAgICBtYXJnaW46IDEycHggMCAwOwogICAg
cGFkZGluZzogMTJweCAxNHB4OwogICAgYm9yZGVyLXJhZGl1czogMTRweDsKICAgIGNvbG9yOiAj
OGUzMDRhOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhzLXN1cmZhY2Utcm9zZSk7CiAgICBmb250
LXNpemU6IDEzcHg7CiAgICBsaW5lLWhlaWdodDogMS41Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFy
aWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtYnV5IHsKICAgIHBvc2l0aW9uOiBzdGlja3k7
CiAgICB0b3A6IDk2cHg7CiAgICBhbGlnbi1zZWxmOiBzdGFydDsKICAgIG1pbi13aWR0aDogMDsK
ICAgIHBhZGRpbmc6IGNsYW1wKDI4cHgsIDN2dywgNDJweCk7CiAgICBib3JkZXI6IDFweCBzb2xp
ZCByZ2JhKDIyMCwgMjI3LCAyMzksIC45NSk7CiAgICBib3JkZXItcmFkaXVzOiAzNHB4OwogICAg
YmFja2dyb3VuZDoKICAgICAgICByYWRpYWwtZ3JhZGllbnQoY2lyY2xlIGF0IDEwMCUgMCUsIHJn
YmEoOTEsOTUsMjQyLC4wOCksIHRyYW5zcGFyZW50IDE4cmVtKSwKICAgICAgICByZ2JhKDI1NSwy
NTUsMjU1LC45Nik7CiAgICBib3gtc2hhZG93OiB2YXIoLS1seHMtc2hhZG93LWxnKTsKfQoKLmx4
cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWJ1eV9faGVhZCBo
MSB7CiAgICBtYXJnaW46IDA7CiAgICBmb250LXNpemU6IGNsYW1wKDQ2cHgsIDUuMXZ3LCA3OHB4
KTsKICAgIGZvbnQtd2VpZ2h0OiA5MDA7CiAgICBsaW5lLWhlaWdodDogLjkyOwogICAgbGV0dGVy
LXNwYWNpbmc6IC0uMDY4ZW07Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2ln
bmFsX3YxIl0gLmx4cy1idXlfX2Rlc2NyaXB0b3IgewogICAgbWFyZ2luOiAxNHB4IDAgMDsKICAg
IGNvbG9yOiB2YXIoLS1seHMtaW5rKTsKICAgIGZvbnQtc2l6ZTogMThweDsKICAgIGZvbnQtd2Vp
Z2h0OiA3NTA7CiAgICBsaW5lLWhlaWdodDogMS4zNTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlh
bnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWJ1eV9fZGVzY3JpcHRpb24gewogICAgbWFyZ2lu
OiAxNXB4IDAgMDsKICAgIGNvbG9yOiB2YXIoLS1seHMtaW5rLXNvZnQpOwogICAgZm9udC1zaXpl
OiAxNHB4OwogICAgbGluZS1oZWlnaHQ6IDEuNjI7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50
PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1wcmljZS1saW5lIHsKICAgIG1hcmdpbi10b3A6IDI2
cHg7CiAgICBkaXNwbGF5OiBmbGV4OwogICAgYWxpZ24taXRlbXM6IGNlbnRlcjsKICAgIGp1c3Rp
ZnktY29udGVudDogc3BhY2UtYmV0d2VlbjsKICAgIGdhcDogMThweDsKfQoKLmx4cGRwW2RhdGEt
cGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXByaWNlIHsKICAgIGRpc3BsYXk6
IGZsZXg7CiAgICBhbGlnbi1pdGVtczogYmFzZWxpbmU7CiAgICBnYXA6IDEwcHg7Cn0KCi5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1wcmljZSBzdHJvbmcg
ewogICAgY29sb3I6IHZhcigtLWx4cy1pbmspOwogICAgZm9udC1zaXplOiAyN3B4OwogICAgZm9u
dC13ZWlnaHQ6IDkwMDsKICAgIGxldHRlci1zcGFjaW5nOiAtLjAzZW07Cn0KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1wcmljZSBkZWwgewogICAgY29s
b3I6IHZhcigtLWx4cy1pbmstZmFpbnQpOwogICAgZm9udC1zaXplOiAxNHB4Owp9CgoubHhwZHBb
ZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc3RvY2sgewogICAgZGlz
cGxheTogaW5saW5lLWZsZXg7CiAgICBhbGlnbi1pdGVtczogY2VudGVyOwogICAgZ2FwOiA3cHg7
CiAgICBjb2xvcjogdmFyKC0tbHhzLXN1Y2Nlc3MpOwogICAgZm9udC1zaXplOiAxMnB4OwogICAg
Zm9udC13ZWlnaHQ6IDg1MDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWdu
YWxfdjEiXSAubHhzLXN0b2NrIGkgewogICAgd2lkdGg6IDhweDsKICAgIGhlaWdodDogOHB4Owog
ICAgYm9yZGVyLXJhZGl1czogNTAlOwogICAgYmFja2dyb3VuZDogY3VycmVudENvbG9yOwogICAg
Ym94LXNoYWRvdzogMCAwIDAgNXB4IHJnYmEoMjMsMTIxLDkxLC4xKTsKfQoKLmx4cGRwW2RhdGEt
cGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXN0b2NrLmlzLW91dCB7CiAgICBj
b2xvcjogdmFyKC0tbHhzLWRhbmdlcik7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVk
aW9fc2lnbmFsX3YxIl0gLmx4cy1zZWxlY3RvciB7CiAgICBtYXJnaW4tdG9wOiAyOHB4Owp9Cgou
bHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2VsZWN0b3Jf
X2hlYWQgewogICAgbWFyZ2luLWJvdHRvbTogMTJweDsKICAgIGRpc3BsYXk6IGZsZXg7CiAgICBh
bGlnbi1pdGVtczogY2VudGVyOwogICAganVzdGlmeS1jb250ZW50OiBzcGFjZS1iZXR3ZWVuOwog
ICAgZ2FwOiAxNHB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92
MSJdIC5seHMtc2VsZWN0b3JfX2hlYWQgaDIgewogICAgbWFyZ2luOiAwOwogICAgZm9udC1zaXpl
OiAxM3B4OwogICAgZm9udC13ZWlnaHQ6IDkwMDsKICAgIGxldHRlci1zcGFjaW5nOiAuMDVlbTsK
ICAgIHRleHQtdHJhbnNmb3JtOiB1cHBlcmNhc2U7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50
PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zZWxlY3Rvcl9faGVhZCA+IHNwYW4gewogICAgY29s
b3I6IHZhcigtLWx4cy1pbmstc29mdCk7CiAgICBmb250LXNpemU6IDEzcHg7CiAgICBmb250LXdl
aWdodDogNzUwOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
IC5seHMtY29sb3ItbGlzdCB7CiAgICBkaXNwbGF5OiBmbGV4OwogICAgZ2FwOiAxMHB4OwogICAg
b3ZlcmZsb3cteDogYXV0bzsKICAgIHBhZGRpbmc6IDJweCAycHggOHB4OwogICAgc2Nyb2xsYmFy
LXdpZHRoOiBub25lOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92
MSJdIC5seHMtY29sb3ItbGlzdDo6LXdlYmtpdC1zY3JvbGxiYXIgewogICAgZGlzcGxheTogbm9u
ZTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWNv
bG9yIHsKICAgIGZsZXg6IDAgMCA3NnB4OwogICAgZGlzcGxheTogZ3JpZDsKICAgIGdhcDogN3B4
OwogICAganVzdGlmeS1pdGVtczogY2VudGVyOwogICAgcGFkZGluZzogMDsKICAgIGJvcmRlcjog
MDsKICAgIGNvbG9yOiB2YXIoLS1seHMtaW5rLXNvZnQpOwogICAgYmFja2dyb3VuZDogdHJhbnNw
YXJlbnQ7CiAgICBjdXJzb3I6IHBvaW50ZXI7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJz
dHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1jb2xvcl9fdmlzdWFsIHsKICAgIHdpZHRoOiA3MHB4Owog
ICAgYXNwZWN0LXJhdGlvOiAxIC8gMS4xODsKICAgIHBvc2l0aW9uOiByZWxhdGl2ZTsKICAgIG92
ZXJmbG93OiBoaWRkZW47CiAgICBkaXNwbGF5OiBncmlkOwogICAgcGxhY2UtaXRlbXM6IGNlbnRl
cjsKICAgIGJvcmRlcjogMnB4IHNvbGlkIHRyYW5zcGFyZW50OwogICAgYm9yZGVyLXJhZGl1czog
MTdweDsKICAgIGJhY2tncm91bmQ6IHZhcigtLWx4cy1jYW52YXMtZGVlcCk7CiAgICB0cmFuc2l0
aW9uOiB0cmFuc2Zvcm0gLjIycyBlYXNlLCBib3JkZXItY29sb3IgLjIycyBlYXNlLCBib3gtc2hh
ZG93IC4yMnMgZWFzZTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxf
djEiXSAubHhzLWNvbG9yX192aXN1YWw6OmFmdGVyIHsKICAgIGNvbnRlbnQ6ICIiOwogICAgcG9z
aXRpb246IGFic29sdXRlOwogICAgaW5zZXQ6IGF1dG8gN3B4IDdweCBhdXRvOwogICAgd2lkdGg6
IDEzcHg7CiAgICBoZWlnaHQ6IDEzcHg7CiAgICBib3JkZXI6IDJweCBzb2xpZCAjZmZmOwogICAg
Ym9yZGVyLXJhZGl1czogNTAlOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhzLXN3YXRjaCwgI2Rm
ZTNlZik7CiAgICBib3gtc2hhZG93OiAwIDAgMCAxcHggcmdiYSgxNywxOSwyNiwuMTgpOwp9Cgou
bHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtY29sb3JfX3Zp
c3VhbCBpbWcgewogICAgd2lkdGg6IDEwMCU7CiAgICBoZWlnaHQ6IDEwMCU7CiAgICBvYmplY3Qt
Zml0OiBjb3ZlcjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEi
XSAubHhzLWNvbG9yX192aXN1YWwgPiBpIHsKICAgIHdpZHRoOiAzMnB4OwogICAgaGVpZ2h0OiAz
MnB4OwogICAgYm9yZGVyLXJhZGl1czogNTAlOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhzLXN3
YXRjaCk7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4
cy1jb2xvciBzdHJvbmcgewogICAgbWF4LXdpZHRoOiA3OHB4OwogICAgb3ZlcmZsb3c6IGhpZGRl
bjsKICAgIGNvbG9yOiBpbmhlcml0OwogICAgZm9udC1zaXplOiAxMXB4OwogICAgZm9udC13ZWln
aHQ6IDg1MDsKICAgIHRleHQtb3ZlcmZsb3c6IGVsbGlwc2lzOwogICAgd2hpdGUtc3BhY2U6IG5v
d3JhcDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhz
LWNvbG9yOmhvdmVyIC5seHMtY29sb3JfX3Zpc3VhbCwKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9
InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWNvbG9yLmlzLWFjdGl2ZSAubHhzLWNvbG9yX192aXN1
YWwgewogICAgdHJhbnNmb3JtOiB0cmFuc2xhdGVZKC0zcHgpOwogICAgYm9yZGVyLWNvbG9yOiB2
YXIoLS1seHMtcHJpbWFyeSk7CiAgICBib3gtc2hhZG93OiAwIDEwcHggMjRweCByZ2JhKDkxLDk1
LDI0MiwuMTgpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
IC5seHMtY29sb3IuaXMtYWN0aXZlIHsKICAgIGNvbG9yOiB2YXIoLS1seHMtcHJpbWFyeSk7Cn0K
Ci5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1jb2xvci11
bmF2YWlsYWJsZSB7CiAgICBtYXJnaW4tdG9wOiA5cHg7CiAgICBkaXNwbGF5OiBpbmxpbmUtZmxl
eDsKICAgIGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAgICBnYXA6IDEwcHg7CiAgICBwYWRkaW5nOiAx
MHB4IDEycHg7CiAgICBib3JkZXI6IDFweCBkYXNoZWQgI2U4YTNiNTsKICAgIGJvcmRlci1yYWRp
dXM6IDE0cHg7CiAgICBjb2xvcjogIzkwMzA0YTsKICAgIGJhY2tncm91bmQ6IHZhcigtLWx4cy1z
dXJmYWNlLXJvc2UpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92
MSJdIC5seHMtY29sb3ItdW5hdmFpbGFibGUgPiBzcGFuIHsKICAgIHdpZHRoOiAyOHB4OwogICAg
aGVpZ2h0OiAyOHB4OwogICAgcG9zaXRpb246IHJlbGF0aXZlOwogICAgYm9yZGVyLXJhZGl1czog
MTBweDsKICAgIGJhY2tncm91bmQ6IHZhcigtLWx4cy1zd2F0Y2gpOwogICAgb3BhY2l0eTogLjU7
Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1jb2xv
ci11bmF2YWlsYWJsZSA+IHNwYW46OmFmdGVyIHsKICAgIGNvbnRlbnQ6ICIiOwogICAgcG9zaXRp
b246IGFic29sdXRlOwogICAgbGVmdDogMnB4OwogICAgcmlnaHQ6IDJweDsKICAgIHRvcDogMTNw
eDsKICAgIGhlaWdodDogMXB4OwogICAgdHJhbnNmb3JtOiByb3RhdGUoLTQyZGVnKTsKICAgIGJh
Y2tncm91bmQ6ICM5MDMwNGE7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2ln
bmFsX3YxIl0gLmx4cy1jb2xvci11bmF2YWlsYWJsZSBkaXYgewogICAgZGlzcGxheTogZ3JpZDsK
ICAgIGdhcDogMnB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92
MSJdIC5seHMtY29sb3ItdW5hdmFpbGFibGUgc21hbGwgewogICAgZm9udC1zaXplOiAxMXB4Owp9
CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtYWxsLXNv
bGRvdXQgewogICAgcGFkZGluZzogMTNweDsKICAgIGJvcmRlci1yYWRpdXM6IDE0cHg7CiAgICBj
b2xvcjogdmFyKC0tbHhzLWRhbmdlcik7CiAgICBiYWNrZ3JvdW5kOiB2YXIoLS1seHMtc3VyZmFj
ZS1yb3NlKTsKICAgIGZvbnQtc2l6ZTogMTNweDsKICAgIGZvbnQtd2VpZ2h0OiA4MDA7Cn0KCi5s
eHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zaXplLWd1aWRl
IHsKICAgIGRpc3BsYXk6IGlubGluZS1mbGV4OwogICAgYWxpZ24taXRlbXM6IGNlbnRlcjsKICAg
IGdhcDogNnB4OwogICAgYm9yZGVyOiAwOwogICAgY29sb3I6IHZhcigtLWx4cy1wcmltYXJ5KTsK
ICAgIGJhY2tncm91bmQ6IHRyYW5zcGFyZW50OwogICAgZm9udC1zaXplOiAxMnB4OwogICAgZm9u
dC13ZWlnaHQ6IDg1MDsKICAgIGN1cnNvcjogcG9pbnRlcjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtZ3VpZGUgc3ZnIHsKICAgIHdpZHRo
OiAxNXB4OwogICAgaGVpZ2h0OiAxNXB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1
ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS1saXN0IHsKICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBn
cmlkLXRlbXBsYXRlLWNvbHVtbnM6IHJlcGVhdChhdXRvLWZpdCwgbWlubWF4KDU4cHgsIDFmcikp
OwogICAgZ2FwOiA5cHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFs
X3YxIl0gLmx4cy1zaXplLWxpc3QgLmx4cGRwLXNpemUtYnV0dG9uIHsKICAgIG1pbi1oZWlnaHQ6
IDQ4cHg7CiAgICBwb3NpdGlvbjogcmVsYXRpdmU7CiAgICBvdmVyZmxvdzogaGlkZGVuOwogICAg
Ym9yZGVyOiAxcHggc29saWQgdmFyKC0tbHhzLWxpbmUpOwogICAgYm9yZGVyLXJhZGl1czogMTRw
eDsKICAgIGNvbG9yOiB2YXIoLS1seHMtaW5rKTsKICAgIGJhY2tncm91bmQ6ICNmZmY7CiAgICBm
b250LXNpemU6IDE0cHg7CiAgICBmb250LXdlaWdodDogOTAwOwogICAgY3Vyc29yOiBwb2ludGVy
OwogICAgdHJhbnNpdGlvbjogYm9yZGVyLWNvbG9yIC4ycyBlYXNlLCBiYWNrZ3JvdW5kIC4ycyBl
YXNlLCBjb2xvciAuMnMgZWFzZSwgdHJhbnNmb3JtIC4ycyBlYXNlOwp9CgoubHhwZHBbZGF0YS1w
ZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS1saXN0IC5seHBkcC1zaXpl
LWJ1dHRvbjpob3Zlcjpub3QoOmRpc2FibGVkKSB7CiAgICB0cmFuc2Zvcm06IHRyYW5zbGF0ZVko
LTJweCk7CiAgICBib3JkZXItY29sb3I6IHZhcigtLWx4cy1wcmltYXJ5KTsKfQoKLmx4cGRwW2Rh
dGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtbGlzdCAubHhwZHAt
c2l6ZS1idXR0b24uaXMtYWN0aXZlIHsKICAgIGJvcmRlci1jb2xvcjogdmFyKC0tbHhzLXByaW1h
cnkpOwogICAgY29sb3I6ICNmZmY7CiAgICBiYWNrZ3JvdW5kOiB2YXIoLS1seHMtcHJpbWFyeSk7
CiAgICBib3gtc2hhZG93OiAwIDlweCAyMnB4IHJnYmEoOTEsOTUsMjQyLC4yMik7Cn0KCi5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zaXplLWxpc3QgLmx4
cGRwLXNpemUtYnV0dG9uOmRpc2FibGVkIHsKICAgIGNvbG9yOiAjYTZhZGJhOwogICAgYmFja2dy
b3VuZDoKICAgICAgICBsaW5lYXItZ3JhZGllbnQoMTM1ZGVnLCB0cmFuc3BhcmVudCA0OCUsICNj
OTM2NTUgNDklLCAjYzkzNjU1IDUxJSwgdHJhbnNwYXJlbnQgNTIlKSwKICAgICAgICAjZjJmNGY4
OwogICAgY3Vyc29yOiBub3QtYWxsb3dlZDsKICAgIG9wYWNpdHk6IDE7Cn0KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zaXplLWxpc3QgLmx4cGRwLXNp
emUtYnV0dG9uOmRpc2FibGVkOjphZnRlciB7CiAgICBjb250ZW50OiAiSOG6v3QiOwogICAgcG9z
aXRpb246IGFic29sdXRlOwogICAgcmlnaHQ6IDRweDsKICAgIGJvdHRvbTogMnB4OwogICAgY29s
b3I6IHZhcigtLWx4cy1kYW5nZXIpOwogICAgZm9udC1zaXplOiA4cHg7CiAgICBmb250LXdlaWdo
dDogOTAwOwogICAgdGV4dC10cmFuc2Zvcm06IHVwcGVyY2FzZTsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNlbGVjdGlvbiB7CiAgICBtYXJnaW4t
dG9wOiAxMHB4OwogICAgZGlzcGxheTogZmxleDsKICAgIGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAg
ICBqdXN0aWZ5LWNvbnRlbnQ6IHNwYWNlLWJldHdlZW47CiAgICBnYXA6IDEycHg7CiAgICBwYWRk
aW5nOiAxMHB4IDEycHg7CiAgICBib3JkZXItcmFkaXVzOiAxMnB4OwogICAgY29sb3I6IHZhcigt
LWx4cy1wcmltYXJ5LWRhcmspOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhzLXByaW1hcnktc29m
dCk7CiAgICBmb250LXNpemU6IDEycHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVk
aW9fc2lnbmFsX3YxIl0gLmx4cy1zZWxlY3Rpb24gW2RhdGEtbHhwZHAtc2VsZWN0ZWQtc3RvY2td
IHsKICAgIGRpc3BsYXk6IG5vbmU7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9f
c2lnbmFsX3YxIl0gLmx4cy1jYXJ0IHsKICAgIG1hcmdpbi10b3A6IDE4cHg7Cn0KCi5seHBkcFtk
YXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1idXktYnV0dG9uLAoubHhw
ZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtYnV0dG9uIHsKICAg
IG1pbi1oZWlnaHQ6IDUycHg7CiAgICBkaXNwbGF5OiBpbmxpbmUtZmxleDsKICAgIGFsaWduLWl0
ZW1zOiBjZW50ZXI7CiAgICBqdXN0aWZ5LWNvbnRlbnQ6IGNlbnRlcjsKICAgIGJvcmRlcjogMDsK
ICAgIGJvcmRlci1yYWRpdXM6IDE2cHg7CiAgICBmb250LXdlaWdodDogOTAwOwogICAgY3Vyc29y
OiBwb2ludGVyOwogICAgdHJhbnNpdGlvbjogdHJhbnNmb3JtIC4ycyBlYXNlLCBib3gtc2hhZG93
IC4ycyBlYXNlLCBiYWNrZ3JvdW5kIC4ycyBlYXNlOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFu
dD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtYnV5LWJ1dHRvbiB7CiAgICB3aWR0aDogMTAwJTsK
ICAgIGNvbG9yOiAjZmZmOwogICAgYmFja2dyb3VuZDogbGluZWFyLWdyYWRpZW50KDEzNWRlZywg
dmFyKC0tbHhzLXNpZ25hbCksICNmZjZiOGUpOwogICAgYm94LXNoYWRvdzogMCAxNHB4IDM0cHgg
cmdiYSgyNTUsNjUsMTA4LC4yOCk7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9f
c2lnbmFsX3YxIl0gLmx4cy1idXktYnV0dG9uOmhvdmVyOm5vdCg6ZGlzYWJsZWQpLAoubHhwZHBb
ZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtYnV0dG9uOmhvdmVyOm5v
dCg6ZGlzYWJsZWQpIHsKICAgIHRyYW5zZm9ybTogdHJhbnNsYXRlWSgtMnB4KTsKfQoKLmx4cGRw
W2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWJ1eS1idXR0b246ZGlz
YWJsZWQgewogICAgY29sb3I6ICM4ZTk2YTY7CiAgICBiYWNrZ3JvdW5kOiAjZTllZGY0OwogICAg
Ym94LXNoYWRvdzogbm9uZTsKICAgIGN1cnNvcjogbm90LWFsbG93ZWQ7Cn0KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1idXR0b24gewogICAgcGFkZGlu
Zy1pbmxpbmU6IDIwcHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFs
X3YxIl0gLmx4cy1idXR0b24tLXByaW1hcnkgewogICAgY29sb3I6ICNmZmY7CiAgICBiYWNrZ3Jv
dW5kOiBsaW5lYXItZ3JhZGllbnQoMTM1ZGVnLCB2YXIoLS1seHMtcHJpbWFyeSksICM3NzdhZjgp
OwogICAgYm94LXNoYWRvdzogMCAxMnB4IDI4cHggcmdiYSg5MSw5NSwyNDIsLjIyKTsKfQoKLmx4
cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWJ1eS1jb25maWRl
bmNlIHsKICAgIG1hcmdpbi10b3A6IDE4cHg7CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ3JpZC10
ZW1wbGF0ZS1jb2x1bW5zOiByZXBlYXQoMywgMWZyKTsKICAgIGdhcDogOHB4Owp9CgoubHhwZHBb
ZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtYnV5LWNvbmZpZGVuY2Ug
c3BhbiB7CiAgICBtaW4td2lkdGg6IDA7CiAgICBkaXNwbGF5OiBncmlkOwogICAganVzdGlmeS1p
dGVtczogY2VudGVyOwogICAgZ2FwOiA2cHg7CiAgICBwYWRkaW5nOiAxMHB4IDdweDsKICAgIGJv
cmRlci1yYWRpdXM6IDEzcHg7CiAgICBjb2xvcjogdmFyKC0tbHhzLWluay1zb2Z0KTsKICAgIGJh
Y2tncm91bmQ6ICNmN2Y4ZmI7CiAgICB0ZXh0LWFsaWduOiBjZW50ZXI7CiAgICBmb250LXNpemU6
IDlweDsKICAgIGZvbnQtd2VpZ2h0OiA4MDA7CiAgICBsaW5lLWhlaWdodDogMS4zOwp9CgoubHhw
ZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtYnV5LWNvbmZpZGVu
Y2Ugc3ZnIHsKICAgIHdpZHRoOiAxOXB4OwogICAgaGVpZ2h0OiAxOXB4OwogICAgY29sb3I6IHZh
cigtLWx4cy1wcmltYXJ5KTsKfQoKLyogUXVpY2sgcmVhZCAqLwoKLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhwZHAtZW5naW5lLXNlY3Rpb24tLXN0dWRpb19x
dWlja19yZWFkIHsKICAgIHBhZGRpbmc6IDcwcHggMCA4MHB4OwogICAgYmFja2dyb3VuZDogdmFy
KC0tbHhzLWNhbnZhcyk7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFs
X3YxIl0gLmx4cy1xdWljay1yZWFkX19ncmlkIHsKICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBncmlk
LXRlbXBsYXRlLWNvbHVtbnM6IHJlcGVhdCg0LCAxZnIpOwogICAgZ2FwOiAxNHB4Owp9CgoubHhw
ZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZmFjdC1jYXJkIHsK
ICAgIG1pbi1oZWlnaHQ6IDE3MHB4OwogICAgZGlzcGxheTogZ3JpZDsKICAgIGFsaWduLWNvbnRl
bnQ6IHNwYWNlLWJldHdlZW47CiAgICBnYXA6IDI0cHg7CiAgICBwYWRkaW5nOiAyMnB4OwogICAg
Ym9yZGVyOiAxcHggc29saWQgcmdiYSgyMjAsMjI3LDIzOSwuOTIpOwogICAgYm9yZGVyLXJhZGl1
czogMjRweDsKICAgIGJhY2tncm91bmQ6IHJnYmEoMjU1LDI1NSwyNTUsLjg4KTsKICAgIGJveC1z
aGFkb3c6IHZhcigtLWx4cy1zaGFkb3ctc20pOwogICAgdHJhbnNpdGlvbjogdHJhbnNmb3JtIC4y
NHMgZWFzZSwgYm94LXNoYWRvdyAuMjRzIGVhc2U7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50
PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1mYWN0LWNhcmQ6aG92ZXIgewogICAgdHJhbnNmb3Jt
OiB0cmFuc2xhdGVZKC01cHgpOwogICAgYm94LXNoYWRvdzogMCAyMHB4IDQ0cHggcmdiYSgyOSw0
Miw4MiwuMTEpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
IC5seHMtZmFjdC1jYXJkX19pY29uIHsKICAgIHdpZHRoOiA0OHB4OwogICAgaGVpZ2h0OiA0OHB4
OwogICAgZGlzcGxheTogZ3JpZDsKICAgIHBsYWNlLWl0ZW1zOiBjZW50ZXI7CiAgICBib3JkZXIt
cmFkaXVzOiAxNnB4OwogICAgY29sb3I6IHZhcigtLWx4cy1wcmltYXJ5KTsKICAgIGJhY2tncm91
bmQ6IGxpbmVhci1ncmFkaWVudCgxMzVkZWcsIHZhcigtLWx4cy1wcmltYXJ5LXNvZnQpLCAjZmZm
KTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWZh
Y3QtY2FyZF9faWNvbiBzdmcgewogICAgd2lkdGg6IDI5cHg7CiAgICBoZWlnaHQ6IDI5cHg7Cn0K
Ci5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1mYWN0LWNh
cmQgZGl2IHsKICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBnYXA6IDZweDsKfQoKLmx4cGRwW2RhdGEt
cGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWZhY3QtY2FyZCBzbWFsbCB7CiAg
ICBjb2xvcjogdmFyKC0tbHhzLWluay1zb2Z0KTsKICAgIGZvbnQtc2l6ZTogMTFweDsKICAgIGZv
bnQtd2VpZ2h0OiA4NTA7CiAgICBsZXR0ZXItc3BhY2luZzogLjA1ZW07CiAgICB0ZXh0LXRyYW5z
Zm9ybTogdXBwZXJjYXNlOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25h
bF92MSJdIC5seHMtZmFjdC1jYXJkIHN0cm9uZyB7CiAgICBmb250LXNpemU6IDIwcHg7CiAgICBm
b250LXdlaWdodDogODUwOwogICAgbGluZS1oZWlnaHQ6IDEuMTY7Cn0KCi8qIERlc2lnbiBleHBs
b3JlciAqLwoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhw
ZHAtZW5naW5lLXNlY3Rpb24tLXN0dWRpb19kZXNpZ25fZXhwbG9yZXIgewogICAgcGFkZGluZzog
ODhweCAwOwogICAgY29sb3I6ICNmZmY7CiAgICBiYWNrZ3JvdW5kOgogICAgICAgIHJhZGlhbC1n
cmFkaWVudChjaXJjbGUgYXQgODglIDEyJSwgcmdiYSg5MSw5NSwyNDIsLjM2KSwgdHJhbnNwYXJl
bnQgMzByZW0pLAogICAgICAgIHJhZGlhbC1ncmFkaWVudChjaXJjbGUgYXQgNCUgMTAwJSwgcmdi
YSgyNTUsNjUsMTA4LC4xOCksIHRyYW5zcGFyZW50IDI2cmVtKSwKICAgICAgICB2YXIoLS1seHMt
Z3JhcGhpdGUpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
IC5seHBkcC1lbmdpbmUtc2VjdGlvbi0tc3R1ZGlvX2Rlc2lnbl9leHBsb3JlciAubHhzLWtpY2tl
ciB7CiAgICBjb2xvcjogIzllYTJmZjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRp
b19zaWduYWxfdjEiXSAubHhwZHAtZW5naW5lLXNlY3Rpb24tLXN0dWRpb19kZXNpZ25fZXhwbG9y
ZXIgLmx4cy1zZWN0aW9uLWhlYWRpbmcgcDpub3QoLmx4cy1raWNrZXIpIHsKICAgIGNvbG9yOiAj
YWViNWM2Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5s
eHMtZGVzaWduLWV4cGxvcmVyX19sYXlvdXQgewogICAgZGlzcGxheTogZ3JpZDsKICAgIGdyaWQt
dGVtcGxhdGUtY29sdW1uczogbWlubWF4KDAsIDEuMzVmcikgbWlubWF4KDMyMHB4LCAuNjVmcik7
CiAgICBnYXA6IDI2cHg7CiAgICBhbGlnbi1pdGVtczogc3RyZXRjaDsKfQoKLmx4cGRwW2RhdGEt
cGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWRlc2lnbi1leHBsb3Jlcl9fdmlz
dWFsIHsKICAgIG1pbi1oZWlnaHQ6IDcyMHB4OwogICAgcG9zaXRpb246IHJlbGF0aXZlOwogICAg
b3ZlcmZsb3c6IGhpZGRlbjsKICAgIG1hcmdpbjogMDsKICAgIGJvcmRlci1yYWRpdXM6IDMycHg7
CiAgICBiYWNrZ3JvdW5kOiAjMjYyYTM3Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1
ZGlvX3NpZ25hbF92MSJdIC5seHMtZGVzaWduLWV4cGxvcmVyX192aXN1YWw6OmFmdGVyIHsKICAg
IGNvbnRlbnQ6ICIiOwogICAgcG9zaXRpb246IGFic29sdXRlOwogICAgaW5zZXQ6IDA7CiAgICBw
b2ludGVyLWV2ZW50czogbm9uZTsKICAgIGJhY2tncm91bmQ6IGxpbmVhci1ncmFkaWVudCgxODBk
ZWcsIHRyYW5zcGFyZW50IDU4JSwgcmdiYSg4LDEwLDE3LC4zNikpOwp9CgoubHhwZHBbZGF0YS1w
ZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZGVzaWduLWV4cGxvcmVyX192aXN1
YWwgaW1nIHsKICAgIHdpZHRoOiAxMDAlOwogICAgaGVpZ2h0OiAxMDAlOwogICAgb2JqZWN0LWZp
dDogY292ZXI7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0g
Lmx4cy1ob3RzcG90IHsKICAgIHBvc2l0aW9uOiBhYnNvbHV0ZTsKICAgIHotaW5kZXg6IDM7CiAg
ICBsZWZ0OiB2YXIoLS1seHMtaG90c3BvdC14KTsKICAgIHRvcDogdmFyKC0tbHhzLWhvdHNwb3Qt
eSk7CiAgICB3aWR0aDogNDJweDsKICAgIGhlaWdodDogNDJweDsKICAgIGRpc3BsYXk6IGdyaWQ7
CiAgICBwbGFjZS1pdGVtczogY2VudGVyOwogICAgdHJhbnNmb3JtOiB0cmFuc2xhdGUoLTUwJSwg
LTUwJSk7CiAgICBib3JkZXI6IDFweCBzb2xpZCByZ2JhKDI1NSwyNTUsMjU1LC43NSk7CiAgICBi
b3JkZXItcmFkaXVzOiA1MCU7CiAgICBjb2xvcjogI2ZmZjsKICAgIGJhY2tncm91bmQ6IHJnYmEo
MTcsMTksMjYsLjU1KTsKICAgIGJveC1zaGFkb3c6IDAgMCAwIDlweCByZ2JhKDI1NSwyNTUsMjU1
LC4xMik7CiAgICBiYWNrZHJvcC1maWx0ZXI6IGJsdXIoMTBweCk7CiAgICBjdXJzb3I6IHBvaW50
ZXI7CiAgICB0cmFuc2l0aW9uOiB0cmFuc2Zvcm0gLjJzIGVhc2UsIGJhY2tncm91bmQgLjJzIGVh
c2UsIGJveC1zaGFkb3cgLjJzIGVhc2U7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVk
aW9fc2lnbmFsX3YxIl0gLmx4cy1ob3RzcG90OmhvdmVyLAoubHhwZHBbZGF0YS1wZHAtdmFyaWFu
dD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtaG90c3BvdC5pcy1hY3RpdmUgewogICAgdHJhbnNm
b3JtOiB0cmFuc2xhdGUoLTUwJSwgLTUwJSkgc2NhbGUoMS4xKTsKICAgIGJhY2tncm91bmQ6IHZh
cigtLWx4cy1zaWduYWwpOwogICAgYm94LXNoYWRvdzogMCAwIDAgMTFweCByZ2JhKDI1NSw2NSwx
MDgsLjE4KSwgMCAxMnB4IDMwcHggcmdiYSgyNTUsNjUsMTA4LC4yNSk7Cn0KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1ob3RzcG90IHNwYW4gewogICAg
Zm9udC1zaXplOiAxMHB4OwogICAgZm9udC13ZWlnaHQ6IDkwMDsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWRlc2lnbi1leHBsb3Jlcl9fY2FyZHMg
ewogICAgZGlzcGxheTogZ3JpZDsKICAgIGdhcDogMTJweDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWRlc2lnbi1jYXJkIHsKICAgIG1pbi1oZWln
aHQ6IDE1MHB4OwogICAgZGlzcGxheTogZ3JpZDsKICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczog
NDJweCAxZnI7CiAgICBhbGlnbi1jb250ZW50OiBjZW50ZXI7CiAgICBjb2x1bW4tZ2FwOiAxNHB4
OwogICAgcGFkZGluZzogMjBweDsKICAgIGJvcmRlcjogMXB4IHNvbGlkIHJnYmEoMjU1LDI1NSwy
NTUsLjA4KTsKICAgIGJvcmRlci1yYWRpdXM6IDIycHg7CiAgICBjb2xvcjogI2FkYjVjNzsKICAg
IGJhY2tncm91bmQ6IHJnYmEoMjU1LDI1NSwyNTUsLjA0NSk7CiAgICBjdXJzb3I6IGRlZmF1bHQ7
CiAgICB0cmFuc2l0aW9uOiBiYWNrZ3JvdW5kIC4yMnMgZWFzZSwgYm9yZGVyLWNvbG9yIC4yMnMg
ZWFzZSwgdHJhbnNmb3JtIC4yMnMgZWFzZTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19zaWduYWxfdjEiXSAubHhzLWRlc2lnbi1jYXJkID4gc3BhbiB7CiAgICBncmlkLXJvdzog
MSAvIDQ7CiAgICBjb2xvcjogIzc3N2FmODsKICAgIGZvbnQtc2l6ZTogMTFweDsKICAgIGZvbnQt
d2VpZ2h0OiA5MDA7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3Yx
Il0gLmx4cy1kZXNpZ24tY2FyZCBzbWFsbCB7CiAgICBjb2xvcjogIzg0OGRhMjsKICAgIGZvbnQt
c2l6ZTogMTBweDsKICAgIGZvbnQtd2VpZ2h0OiA5MDA7CiAgICBsZXR0ZXItc3BhY2luZzogLjA4
ZW07CiAgICB0ZXh0LXRyYW5zZm9ybTogdXBwZXJjYXNlOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFy
aWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZGVzaWduLWNhcmQgaDMgewogICAgbWFyZ2lu
OiA0cHggMCAwOwogICAgY29sb3I6ICNmZmY7CiAgICBmb250LXNpemU6IDIxcHg7CiAgICBsaW5l
LWhlaWdodDogMS4xMjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxf
djEiXSAubHhzLWRlc2lnbi1jYXJkIHAgewogICAgbWFyZ2luOiA4cHggMCAwOwogICAgZm9udC1z
aXplOiAxMnB4OwogICAgbGluZS1oZWlnaHQ6IDEuNTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlh
bnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWRlc2lnbi1jYXJkLmlzLWFjdGl2ZSB7CiAgICBi
b3JkZXItY29sb3I6IHJnYmEoMTE5LDEyMiwyNDgsLjU1KTsKICAgIGJhY2tncm91bmQ6IGxpbmVh
ci1ncmFkaWVudCgxMzVkZWcsIHJnYmEoOTEsOTUsMjQyLC4yMiksIHJnYmEoMjU1LDI1NSwyNTUs
LjA3KSk7CiAgICB0cmFuc2Zvcm06IHRyYW5zbGF0ZVgoLTZweCk7Cn0KCi8qIEJlbmVmaXQgY2Fy
ZHMgKi8KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cGRw
LWVuZ2luZS1zZWN0aW9uLS1zdHVkaW9fYmVuZWZpdF9ncmlkIHsKICAgIHBhZGRpbmc6IDkycHgg
MDsKICAgIGJhY2tncm91bmQ6ICNmZmY7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVk
aW9fc2lnbmFsX3YxIl0gLmx4cy1iZW5lZml0c19fZ3JpZCB7CiAgICBkaXNwbGF5OiBncmlkOwog
ICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiAxLjJmciAuOGZyIC44ZnI7CiAgICBncmlkLXRlbXBs
YXRlLXJvd3M6IDMzMHB4IDI0MHB4OwogICAgZ2FwOiAxNnB4Owp9CgoubHhwZHBbZGF0YS1wZHAt
dmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtYmVuZWZpdC1jYXJkIHsKICAgIG1pbi13
aWR0aDogMDsKICAgIHBvc2l0aW9uOiByZWxhdGl2ZTsKICAgIG92ZXJmbG93OiBoaWRkZW47CiAg
ICBib3JkZXItcmFkaXVzOiAyOHB4OwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhzLWNhbnZhcy1k
ZWVwKTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhz
LWJlbmVmaXQtY2FyZC0tMSB7CiAgICBncmlkLXJvdzogMSAvIDM7Cn0KCi5seHBkcFtkYXRhLXBk
cC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1iZW5lZml0LWNhcmQtLTIgewogICAg
Z3JpZC1jb2x1bW46IDIgLyA0Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3Np
Z25hbF92MSJdIC5seHMtYmVuZWZpdC1jYXJkIGltZyB7CiAgICB3aWR0aDogMTAwJTsKICAgIGhl
aWdodDogMTAwJTsKICAgIG9iamVjdC1maXQ6IGNvdmVyOwogICAgdHJhbnNpdGlvbjogdHJhbnNm
b3JtIC43cyBjdWJpYy1iZXppZXIoLjIsLjcsLjIsMSk7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJp
YW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1iZW5lZml0LWNhcmQ6aG92ZXIgaW1nIHsKICAg
IHRyYW5zZm9ybTogc2NhbGUoMS4wMzUpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1
ZGlvX3NpZ25hbF92MSJdIC5seHMtYmVuZWZpdC1jYXJkOjphZnRlciB7CiAgICBjb250ZW50OiAi
IjsKICAgIHBvc2l0aW9uOiBhYnNvbHV0ZTsKICAgIGluc2V0OiAwOwogICAgYmFja2dyb3VuZDog
bGluZWFyLWdyYWRpZW50KDE4MGRlZywgdHJhbnNwYXJlbnQgNTAlLCByZ2JhKDksMTIsMjAsLjc0
KSk7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1i
ZW5lZml0LWNhcmQgZGl2IHsKICAgIHBvc2l0aW9uOiBhYnNvbHV0ZTsKICAgIHotaW5kZXg6IDI7
CiAgICBsZWZ0OiAyMnB4OwogICAgcmlnaHQ6IDIycHg7CiAgICBib3R0b206IDIwcHg7CiAgICBj
b2xvcjogI2ZmZjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEi
XSAubHhzLWJlbmVmaXQtY2FyZCBzbWFsbCB7CiAgICBmb250LXNpemU6IDEwcHg7CiAgICBmb250
LXdlaWdodDogOTAwOwogICAgbGV0dGVyLXNwYWNpbmc6IC4wOGVtOwogICAgdGV4dC10cmFuc2Zv
cm06IHVwcGVyY2FzZTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxf
djEiXSAubHhzLWJlbmVmaXQtY2FyZCBoMyB7CiAgICBtYXJnaW46IDdweCAwIDA7CiAgICBmb250
LXNpemU6IGNsYW1wKDIycHgsIDIuM3Z3LCAzNHB4KTsKICAgIGxpbmUtaGVpZ2h0OiAxOwogICAg
bGV0dGVyLXNwYWNpbmc6IC0uMDM1ZW07Cn0KCi8qIE1lZGlhIGxhYiAqLwoKLmx4cGRwW2RhdGEt
cGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhwZHAtZW5naW5lLXNlY3Rpb24tLXN0
dWRpb19tZWRpYV9sYWIgewogICAgcGFkZGluZzogOTJweCAwOwogICAgYmFja2dyb3VuZDoKICAg
ICAgICBsaW5lYXItZ3JhZGllbnQoMTgwZGVnLCAjZjFmNGZiLCAjZjhmYWZmKTsKfQoKLmx4cGRw
W2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLW1lZGlhLWxhYl9fdGFi
cyB7CiAgICB3aWR0aDogZml0LWNvbnRlbnQ7CiAgICBtYXJnaW4tYm90dG9tOiAyMnB4OwogICAg
ZGlzcGxheTogZmxleDsKICAgIGdhcDogN3B4OwogICAgcGFkZGluZzogNXB4OwogICAgYm9yZGVy
OiAxcHggc29saWQgdmFyKC0tbHhzLWxpbmUpOwogICAgYm9yZGVyLXJhZGl1czogOTk5cHg7CiAg
ICBiYWNrZ3JvdW5kOiAjZmZmOwogICAgYm94LXNoYWRvdzogdmFyKC0tbHhzLXNoYWRvdy1zbSk7
Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1tZWRp
YS1sYWJfX3RhYnMgYnV0dG9uIHsKICAgIG1pbi1oZWlnaHQ6IDM4cHg7CiAgICBwYWRkaW5nOiAw
IDE2cHg7CiAgICBib3JkZXI6IDA7CiAgICBib3JkZXItcmFkaXVzOiA5OTlweDsKICAgIGNvbG9y
OiB2YXIoLS1seHMtaW5rLXNvZnQpOwogICAgYmFja2dyb3VuZDogdHJhbnNwYXJlbnQ7CiAgICBm
b250LXNpemU6IDEycHg7CiAgICBmb250LXdlaWdodDogODUwOwogICAgY3Vyc29yOiBwb2ludGVy
Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtbWVk
aWEtbGFiX190YWJzIGJ1dHRvbi5pcy1hY3RpdmUgewogICAgY29sb3I6ICNmZmY7CiAgICBiYWNr
Z3JvdW5kOiB2YXIoLS1seHMtZ3JhcGhpdGUpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0i
c3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtbWVkaWEtZ3JpZCB7CiAgICBkaXNwbGF5OiBub25lOwog
ICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiByZXBlYXQoMTIsIDFmcik7CiAgICBncmlkLWF1dG8t
cm93czogMTA1cHg7CiAgICBnYXA6IDEzcHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJz
dHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1tZWRpYS1ncmlkLmlzLWFjdGl2ZSB7CiAgICBkaXNwbGF5
OiBncmlkOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5s
eHMtbWVkaWEtZ3JpZF9faXRlbSB7CiAgICBwb3NpdGlvbjogcmVsYXRpdmU7CiAgICBvdmVyZmxv
dzogaGlkZGVuOwogICAgbWFyZ2luOiAwOwogICAgYm9yZGVyLXJhZGl1czogMjRweDsKICAgIGJh
Y2tncm91bmQ6IHZhcigtLWx4cy1jYW52YXMtZGVlcCk7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJp
YW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1tZWRpYS1ncmlkX19pdGVtLS0xIHsKICAgIGdy
aWQtY29sdW1uOiBzcGFuIDU7CiAgICBncmlkLXJvdzogc3BhbiA1Owp9CgoubHhwZHBbZGF0YS1w
ZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtbWVkaWEtZ3JpZF9faXRlbS0tMiB7
CiAgICBncmlkLWNvbHVtbjogc3BhbiA0OwogICAgZ3JpZC1yb3c6IHNwYW4gMzsKfQoKLmx4cGRw
W2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLW1lZGlhLWdyaWRfX2l0
ZW0tLTMgewogICAgZ3JpZC1jb2x1bW46IHNwYW4gMzsKICAgIGdyaWQtcm93OiBzcGFuIDM7Cn0K
Ci5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1tZWRpYS1n
cmlkX19pdGVtLS00IHsKICAgIGdyaWQtY29sdW1uOiBzcGFuIDM7CiAgICBncmlkLXJvdzogc3Bh
biAyOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMt
bWVkaWEtZ3JpZF9faXRlbS0tNSB7CiAgICBncmlkLWNvbHVtbjogc3BhbiA0OwogICAgZ3JpZC1y
b3c6IHNwYW4gMjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEi
XSAubHhzLW1lZGlhLWdyaWRfX2l0ZW0gaW1nIHsKICAgIHdpZHRoOiAxMDAlOwogICAgaGVpZ2h0
OiAxMDAlOwogICAgb2JqZWN0LWZpdDogY292ZXI7CiAgICB0cmFuc2l0aW9uOiB0cmFuc2Zvcm0g
LjZzIGVhc2U7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0g
Lmx4cy1tZWRpYS1ncmlkX19pdGVtOmhvdmVyIGltZyB7CiAgICB0cmFuc2Zvcm06IHNjYWxlKDEu
MDMpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMt
bWVkaWEtZ3JpZF9faXRlbSBmaWdjYXB0aW9uIHsKICAgIHBvc2l0aW9uOiBhYnNvbHV0ZTsKICAg
IGxlZnQ6IDEycHg7CiAgICBib3R0b206IDEycHg7CiAgICBwYWRkaW5nOiA3cHggMTBweDsKICAg
IGJvcmRlci1yYWRpdXM6IDk5OXB4OwogICAgY29sb3I6ICNmZmY7CiAgICBiYWNrZ3JvdW5kOiBy
Z2JhKDE3LDE5LDI2LC41OCk7CiAgICBmb250LXNpemU6IDlweDsKICAgIGZvbnQtd2VpZ2h0OiA4
NTA7CiAgICBiYWNrZHJvcC1maWx0ZXI6IGJsdXIoMTBweCk7Cn0KCi8qIFNpemUgU3R1ZGlvICov
CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHBkcC1lbmdp
bmUtc2VjdGlvbi0tc3R1ZGlvX3NpemVfc3R1ZGlvIHsKICAgIGJhY2tncm91bmQ6CiAgICAgICAg
cmFkaWFsLWdyYWRpZW50KGNpcmNsZSBhdCAwIDAsIHJnYmEoOTEsOTUsMjQyLC4xOCksIHRyYW5z
cGFyZW50IDM0cmVtKSwKICAgICAgICBsaW5lYXItZ3JhZGllbnQoMTgwZGVnLCAjZTllZGZmLCAj
ZjRmNmZmKTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAu
bHhzLXNpemUtc3R1ZGlvIHsKICAgIHBhZGRpbmc6IDk2cHggMDsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtc3R1ZGlvX19zaXplLWNhcmRz
IHsKICAgIG1hcmdpbi1ib3R0b206IDIwcHg7CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ3JpZC10
ZW1wbGF0ZS1jb2x1bW5zOiByZXBlYXQoYXV0by1maXQsIG1pbm1heCgxMzJweCwgMWZyKSk7CiAg
ICBnYXA6IDEwcHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3Yx
Il0gLmx4cy1zaXplLXN0dWRpb19fc2l6ZS1jYXJkcyBidXR0b24gewogICAgbWluLWhlaWdodDog
MTA4cHg7CiAgICBkaXNwbGF5OiBncmlkOwogICAgYWxpZ24tY29udGVudDogY2VudGVyOwogICAg
anVzdGlmeS1pdGVtczogc3RhcnQ7CiAgICBnYXA6IDRweDsKICAgIHBhZGRpbmc6IDE2cHg7CiAg
ICBib3JkZXI6IDFweCBzb2xpZCByZ2JhKDkxLDk1LDI0MiwuMTYpOwogICAgYm9yZGVyLXJhZGl1
czogMjBweDsKICAgIGNvbG9yOiB2YXIoLS1seHMtaW5rKTsKICAgIGJhY2tncm91bmQ6IHJnYmEo
MjU1LDI1NSwyNTUsLjcyKTsKICAgIGJveC1zaGFkb3c6IDAgMTBweCAyOHB4IHJnYmEoNjcsNzUs
MTM1LC4wNyk7CiAgICBjdXJzb3I6IHBvaW50ZXI7CiAgICB0cmFuc2l0aW9uOiB0cmFuc2Zvcm0g
LjJzIGVhc2UsIGJvcmRlci1jb2xvciAuMnMgZWFzZSwgYmFja2dyb3VuZCAuMnMgZWFzZTsKfQoK
Lmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtc3R1
ZGlvX19zaXplLWNhcmRzIGJ1dHRvbjpob3ZlciwKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtc3R1ZGlvX19zaXplLWNhcmRzIGJ1dHRvbi5pcy1h
Y3RpdmUgewogICAgdHJhbnNmb3JtOiB0cmFuc2xhdGVZKC0zcHgpOwogICAgYm9yZGVyLWNvbG9y
OiB2YXIoLS1seHMtcHJpbWFyeSk7CiAgICBiYWNrZ3JvdW5kOiAjZmZmOwp9CgoubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS1zdHVkaW9fX3NpemUt
Y2FyZHMgYnV0dG9uLmlzLWFjdGl2ZSB7CiAgICBib3gtc2hhZG93OiAwIDE2cHggMzZweCByZ2Jh
KDkxLDk1LDI0MiwuMTYpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25h
bF92MSJdIC5seHMtc2l6ZS1zdHVkaW9fX3NpemUtY2FyZHMgc3Ryb25nIHsKICAgIGNvbG9yOiB2
YXIoLS1seHMtcHJpbWFyeSk7CiAgICBmb250LXNpemU6IDI4cHg7CiAgICBsaW5lLWhlaWdodDog
MTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNp
emUtc3R1ZGlvX19zaXplLWNhcmRzIHNwYW4gewogICAgZm9udC1zaXplOiAxNHB4OwogICAgZm9u
dC13ZWlnaHQ6IDg1MDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxf
djEiXSAubHhzLXNpemUtc3R1ZGlvX19zaXplLWNhcmRzIHNtYWxsIHsKICAgIGNvbG9yOiB2YXIo
LS1seHMtaW5rLXNvZnQpOwogICAgZm9udC1zaXplOiAxMHB4OwogICAgdGV4dC10cmFuc2Zvcm06
IHVwcGVyY2FzZTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEi
XSAubHhzLXNpemUtc3R1ZGlvX193b3Jrc3BhY2UgewogICAgZGlzcGxheTogZ3JpZDsKICAgIGdy
aWQtdGVtcGxhdGUtY29sdW1uczogbWlubWF4KDM4MHB4LCAuODJmcikgbWlubWF4KDAsIDEuMThm
cik7CiAgICBnYXA6IDE4cHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2ln
bmFsX3YxIl0gLmx4cy1zaXplLWZpZ3VyZSwKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRp
b19zaWduYWxfdjEiXSAubHhzLXNpemUtdmFsdWVzIHsKICAgIG1pbi1oZWlnaHQ6IDU2MHB4Owog
ICAgYm9yZGVyOiAxcHggc29saWQgcmdiYSg5MSw5NSwyNDIsLjE0KTsKICAgIGJvcmRlci1yYWRp
dXM6IDMwcHg7CiAgICBiYWNrZ3JvdW5kOiByZ2JhKDI1NSwyNTUsMjU1LC44NCk7CiAgICBib3gt
c2hhZG93OiAwIDIycHggNTVweCByZ2JhKDU3LDY4LDEzMiwuMDkpOwp9CgoubHhwZHBbZGF0YS1w
ZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS1maWd1cmUgewogICAgZGlz
cGxheTogZ3JpZDsKICAgIHBsYWNlLWl0ZW1zOiBjZW50ZXI7CiAgICBhbGlnbi1jb250ZW50OiBj
ZW50ZXI7CiAgICBwYWRkaW5nOiAyNHB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1
ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS1maWd1cmUgc3ZnIHsKICAgIHdpZHRoOiBtaW4oMTAw
JSwgMzYwcHgpOwogICAgaGVpZ2h0OiBhdXRvOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0i
c3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS1maWd1cmVfX2RyZXNzIHsKICAgIGZpbGw6IHVy
bCgjbHhzRHJlc3NGaWxsKTsKICAgIHN0cm9rZTogIzlkYTZjNjsKICAgIHN0cm9rZS13aWR0aDog
MjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNp
emUtZmlndXJlX19uZWNrLAoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92
MSJdIC5seHMtc2l6ZS1maWd1cmVfX3NlYW0gewogICAgc3Ryb2tlOiAjYzNjYWUwOwp9CgoubHhw
ZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS1maWd1cmUg
ZyB7CiAgICBjb2xvcjogI2E0YWNjMzsKICAgIG9wYWNpdHk6IC4zOwogICAgdHJhbnNpdGlvbjog
Y29sb3IgLjJzIGVhc2UsIG9wYWNpdHkgLjJzIGVhc2U7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJp
YW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zaXplLWZpZ3VyZSBnIHBhdGggewogICAgc3Ry
b2tlOiBjdXJyZW50Q29sb3I7CiAgICBzdHJva2Utd2lkdGg6IDIuMjsKICAgIHN0cm9rZS1kYXNo
YXJyYXk6IDYgNjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEi
XSAubHhzLXNpemUtZmlndXJlIGcgY2lyY2xlIHsKICAgIGZpbGw6ICNmZmY7CiAgICBzdHJva2U6
IGN1cnJlbnRDb2xvcjsKICAgIHN0cm9rZS13aWR0aDogMjsKfQoKLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtZmlndXJlIGcgdGV4dCB7CiAgICBm
aWxsOiBjdXJyZW50Q29sb3I7CiAgICBzdHJva2U6IG5vbmU7CiAgICBmb250LXNpemU6IDE1cHg7
CiAgICBmb250LXdlaWdodDogOTAwOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlv
X3NpZ25hbF92MSJdIC5seHMtc2l6ZS1maWd1cmUgZy5pcy1hY3RpdmUgewogICAgY29sb3I6IHZh
cigtLWx4cy1zaWduYWwpOwogICAgb3BhY2l0eTogMTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlh
bnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtZmlndXJlID4gcCB7CiAgICBtYXgtd2lk
dGg6IDI5MHB4OwogICAgbWFyZ2luOiA0cHggMCAwOwogICAgY29sb3I6IHZhcigtLWx4cy1pbmst
c29mdCk7CiAgICB0ZXh0LWFsaWduOiBjZW50ZXI7CiAgICBmb250LXNpemU6IDEycHg7CiAgICBs
aW5lLWhlaWdodDogMS41Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25h
bF92MSJdIC5seHMtc2l6ZS12YWx1ZXMgewogICAgcGFkZGluZzogY2xhbXAoMjRweCwgNHZ3LCA0
NHB4KTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhz
LXNpemUtdmFsdWVzX19oZWFkIHsKICAgIGRpc3BsYXk6IGZsZXg7CiAgICBqdXN0aWZ5LWNvbnRl
bnQ6IHNwYWNlLWJldHdlZW47CiAgICBhbGlnbi1pdGVtczogZW5kOwogICAgZ2FwOiAxOHB4Owp9
CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS12
YWx1ZXNfX2hlYWQgc21hbGwgewogICAgY29sb3I6IHZhcigtLWx4cy1pbmstc29mdCk7CiAgICBm
b250LXNpemU6IDEwcHg7CiAgICBmb250LXdlaWdodDogODUwOwogICAgbGV0dGVyLXNwYWNpbmc6
IC4xZW07CiAgICB0ZXh0LXRyYW5zZm9ybTogdXBwZXJjYXNlOwp9CgoubHhwZHBbZGF0YS1wZHAt
dmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS12YWx1ZXNfX2hlYWQgaDMgewog
ICAgbWFyZ2luOiA1cHggMCAwOwogICAgZm9udC1zaXplOiAzNHB4OwogICAgbGV0dGVyLXNwYWNp
bmc6IC0uMDRlbTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEi
XSAubHhzLXNpemUtdmFsdWVzX19oZWFkIGJ1dHRvbiB7CiAgICBib3JkZXI6IDA7CiAgICBjb2xv
cjogdmFyKC0tbHhzLXByaW1hcnkpOwogICAgYmFja2dyb3VuZDogdHJhbnNwYXJlbnQ7CiAgICBm
b250LXNpemU6IDEycHg7CiAgICBmb250LXdlaWdodDogODUwOwogICAgY3Vyc29yOiBwb2ludGVy
Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6
ZS12YWx1ZXNfX2xpc3QgewogICAgbWFyZ2luLXRvcDogMjZweDsKICAgIGRpc3BsYXk6IGdyaWQ7
CiAgICBnYXA6IDlweDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxf
djEiXSAubHhzLXNpemUtdmFsdWVzX19saXN0IGJ1dHRvbiB7CiAgICBtaW4taGVpZ2h0OiA2OHB4
OwogICAgZGlzcGxheTogZmxleDsKICAgIGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAgICBqdXN0aWZ5
LWNvbnRlbnQ6IHNwYWNlLWJldHdlZW47CiAgICBnYXA6IDE4cHg7CiAgICBwYWRkaW5nOiAxM3B4
IDE1cHg7CiAgICBib3JkZXI6IDFweCBzb2xpZCB2YXIoLS1seHMtbGluZSk7CiAgICBib3JkZXIt
cmFkaXVzOiAxN3B4OwogICAgY29sb3I6IHZhcigtLWx4cy1pbmspOwogICAgYmFja2dyb3VuZDog
I2ZmZjsKICAgIGN1cnNvcjogcG9pbnRlcjsKICAgIHRyYW5zaXRpb246IGJvcmRlci1jb2xvciAu
MnMgZWFzZSwgYmFja2dyb3VuZCAuMnMgZWFzZSwgdHJhbnNmb3JtIC4ycyBlYXNlOwp9CgoubHhw
ZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS12YWx1ZXNf
X2xpc3QgYnV0dG9uOmhvdmVyLAoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25h
bF92MSJdIC5seHMtc2l6ZS12YWx1ZXNfX2xpc3QgYnV0dG9uLmlzLWFjdGl2ZSB7CiAgICB0cmFu
c2Zvcm06IHRyYW5zbGF0ZVgoNHB4KTsKICAgIGJvcmRlci1jb2xvcjogdmFyKC0tbHhzLXByaW1h
cnkpOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhzLXByaW1hcnktc29mdCk7Cn0KCi5seHBkcFtk
YXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zaXplLXZhbHVlc19fbGlz
dCBidXR0b24gc3BhbiB7CiAgICBkaXNwbGF5OiBpbmxpbmUtZmxleDsKICAgIGFsaWduLWl0ZW1z
OiBjZW50ZXI7CiAgICBnYXA6IDEwcHg7CiAgICBmb250LXNpemU6IDEzcHg7CiAgICBmb250LXdl
aWdodDogODAwOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
IC5seHMtc2l6ZS12YWx1ZXNfX2xpc3QgYnV0dG9uIGkgewogICAgd2lkdGg6IDI4cHg7CiAgICBo
ZWlnaHQ6IDI4cHg7CiAgICBkaXNwbGF5OiBncmlkOwogICAgcGxhY2UtaXRlbXM6IGNlbnRlcjsK
ICAgIGJvcmRlci1yYWRpdXM6IDUwJTsKICAgIGNvbG9yOiB2YXIoLS1seHMtcHJpbWFyeSk7CiAg
ICBiYWNrZ3JvdW5kOiAjZmZmOwogICAgZm9udC1zdHlsZTogbm9ybWFsOwogICAgZm9udC1zaXpl
OiAxMHB4OwogICAgZm9udC13ZWlnaHQ6IDkwMDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9
InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtdmFsdWVzX19saXN0IGJ1dHRvbiBzdHJvbmcg
ewogICAgZm9udC1zaXplOiAxN3B4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlv
X3NpZ25hbF92MSJdIC5seHMtc2l6ZS12YWx1ZXNfX2xpc3QgYnV0dG9uIHN0cm9uZyBiIHsKICAg
IGZvbnQtc2l6ZTogMjVweDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWdu
YWxfdjEiXSAubHhzLXNpemUtdmFsdWVzX19hY3Rpb25zIHsKICAgIG1hcmdpbi10b3A6IDI0cHg7
CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiBhdXRvIDFmcjsK
ICAgIGdhcDogMThweDsKICAgIGFsaWduLWl0ZW1zOiBjZW50ZXI7Cn0KCi5seHBkcFtkYXRhLXBk
cC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zaXplLXZhbHVlc19fYWN0aW9ucyBw
IHsKICAgIG1hcmdpbjogMDsKICAgIGNvbG9yOiB2YXIoLS1seHMtaW5rLXNvZnQpOwogICAgZm9u
dC1zaXplOiAxMnB4OwogICAgbGluZS1oZWlnaHQ6IDEuNTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtZGlhbG9nIHsKICAgIHdpZHRoOiBt
aW4oOTIwcHgsIGNhbGMoMTAwJSAtIDI0cHgpKTsKICAgIG1heC1oZWlnaHQ6IDg4dmg7CiAgICBw
YWRkaW5nOiAwOwogICAgYm9yZGVyOiAwOwogICAgYm9yZGVyLXJhZGl1czogMjhweDsKICAgIGNv
bG9yOiB2YXIoLS1seHMtaW5rKTsKICAgIGJhY2tncm91bmQ6ICNmZmY7CiAgICBib3gtc2hhZG93
OiAwIDQwcHggMTIwcHggcmdiYSgxMiwxOCwzOCwuMjgpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFy
aWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS1kaWFsb2c6OmJhY2tkcm9wIHsKICAg
IGJhY2tncm91bmQ6IHJnYmEoMTAsMTQsMjYsLjU4KTsKICAgIGJhY2tkcm9wLWZpbHRlcjogYmx1
cig4cHgpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5s
eHMtc2l6ZS1kaWFsb2cgPiBmb3JtIHsKICAgIHBvc2l0aW9uOiBhYnNvbHV0ZTsKICAgIHotaW5k
ZXg6IDI7CiAgICByaWdodDogMTZweDsKICAgIHRvcDogMTZweDsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtZGlhbG9nID4gZm9ybSBidXR0
b24gewogICAgd2lkdGg6IDQwcHg7CiAgICBoZWlnaHQ6IDQwcHg7CiAgICBib3JkZXI6IDA7CiAg
ICBib3JkZXItcmFkaXVzOiA1MCU7CiAgICBiYWNrZ3JvdW5kOiB2YXIoLS1seHMtY2FudmFzKTsK
ICAgIGZvbnQtc2l6ZTogMjNweDsKICAgIGN1cnNvcjogcG9pbnRlcjsKfQoKLmx4cGRwW2RhdGEt
cGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtZGlhbG9nID4gZGl2IHsK
ICAgIHBhZGRpbmc6IDM0cHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2ln
bmFsX3YxIl0gLmx4cy1zaXplLWRpYWxvZyBoMiB7CiAgICBtYXJnaW46IDAgMCAyNHB4OwogICAg
Zm9udC1zaXplOiAzNHB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25h
bF92MSJdIC5seHMtc2l6ZS1kaWFsb2dfX3RhYmxlLXdyYXAgewogICAgb3ZlcmZsb3c6IGF1dG87
CiAgICBib3JkZXI6IDFweCBzb2xpZCB2YXIoLS1seHMtbGluZSk7CiAgICBib3JkZXItcmFkaXVz
OiAxOHB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5s
eHMtc2l6ZS1kaWFsb2cgdGFibGUgewogICAgd2lkdGg6IDEwMCU7CiAgICBib3JkZXItY29sbGFw
c2U6IHNlcGFyYXRlOwogICAgYm9yZGVyLXNwYWNpbmc6IDA7CiAgICBmb250LXNpemU6IDEzcHg7
Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zaXpl
LWRpYWxvZyB0aCwKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAu
bHhzLXNpemUtZGlhbG9nIHRkIHsKICAgIG1pbi13aWR0aDogOTJweDsKICAgIHBhZGRpbmc6IDE0
cHg7CiAgICBib3JkZXItcmlnaHQ6IDFweCBzb2xpZCB2YXIoLS1seHMtbGluZSk7CiAgICBib3Jk
ZXItYm90dG9tOiAxcHggc29saWQgdmFyKC0tbHhzLWxpbmUpOwogICAgdGV4dC1hbGlnbjogY2Vu
dGVyOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMt
c2l6ZS1kaWFsb2cgdGg6Zmlyc3QtY2hpbGQgewogICAgbWluLXdpZHRoOiAyMjBweDsKICAgIHBv
c2l0aW9uOiBzdGlja3k7CiAgICBsZWZ0OiAwOwogICAgei1pbmRleDogMTsKICAgIGJhY2tncm91
bmQ6ICNmZmY7CiAgICB0ZXh0LWFsaWduOiBsZWZ0Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFu
dD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS1kaWFsb2cgdGhlYWQgdGggewogICAgcG9z
aXRpb246IHN0aWNreTsKICAgIHRvcDogMDsKICAgIGJhY2tncm91bmQ6IHZhcigtLWx4cy1wcmlt
YXJ5LXNvZnQpOwogICAgZm9udC13ZWlnaHQ6IDkwMDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlh
bnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXNpemUtZGlhbG9nIHRoIHNtYWxsIHsKICAgIGRp
c3BsYXk6IGJsb2NrOwogICAgbWFyZ2luLXRvcDogNHB4OwogICAgY29sb3I6IHZhcigtLWx4cy1p
bmstc29mdCk7CiAgICBmb250LXdlaWdodDogNTAwOwogICAgbGluZS1oZWlnaHQ6IDEuMzU7Cn0K
Ci5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zaXplLWRp
YWxvZyB0ZCBzbWFsbCB7CiAgICBjb2xvcjogdmFyKC0tbHhzLWluay1zb2Z0KTsKfQoKLyogTWF0
ZXJpYWwgKi8KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4
cGRwLWVuZ2luZS1zZWN0aW9uLS1zdHVkaW9fbWF0ZXJpYWxfZmVlbCB7CiAgICBwYWRkaW5nOiA5
MnB4IDA7CiAgICBiYWNrZ3JvdW5kOgogICAgICAgIHJhZGlhbC1ncmFkaWVudChjaXJjbGUgYXQg
ODYlIDE2JSwgcmdiYSgyMywxMjEsOTEsLjEzKSwgdHJhbnNwYXJlbnQgMjdyZW0pLAogICAgICAg
IHZhcigtLWx4cy1zdXJmYWNlLW1pbnQpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1
ZGlvX3NpZ25hbF92MSJdIC5seHMtbWF0ZXJpYWwtZmVlbF9fZ3JpZCB7CiAgICBkaXNwbGF5OiBn
cmlkOwogICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiByZXBlYXQoMTIsIDFmcik7CiAgICBnYXA6
IDE0cHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4
cy1tYXRlcmlhbC1jYXJkIHsKICAgIGdyaWQtY29sdW1uOiBzcGFuIDQ7CiAgICBtaW4taGVpZ2h0
OiAyMjBweDsKICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBhbGlnbi1jb250ZW50OiBlbmQ7CiAgICBn
YXA6IDdweDsKICAgIHBhZGRpbmc6IDI0cHg7CiAgICBib3JkZXI6IDFweCBzb2xpZCByZ2JhKDIz
LDEyMSw5MSwuMTQpOwogICAgYm9yZGVyLXJhZGl1czogMjZweDsKICAgIGJhY2tncm91bmQ6IHJn
YmEoMjU1LDI1NSwyNTUsLjgyKTsKICAgIGJveC1zaGFkb3c6IDAgMTZweCAzOHB4IHJnYmEoMjMs
ODMsNjksLjA4KTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEi
XSAubHhzLW1hdGVyaWFsLWNhcmQtLXByaW1hcnkgewogICAgZ3JpZC1jb2x1bW46IHNwYW4gNzsK
ICAgIGdyaWQtcm93OiBzcGFuIDI7CiAgICBtaW4taGVpZ2h0OiA0NTRweDsKICAgIGJhY2tncm91
bmQ6CiAgICAgICAgcmFkaWFsLWdyYWRpZW50KGNpcmNsZSBhdCA4MCUgMTglLCByZ2JhKDkxLDk1
LDI0MiwuMTYpLCB0cmFuc3BhcmVudCAxOHJlbSksCiAgICAgICAgcmdiYSgyNTUsMjU1LDI1NSwu
OSk7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1t
YXRlcmlhbC1jYXJkOm50aC1jaGlsZCgyKSB7CiAgICBncmlkLWNvbHVtbjogc3BhbiA1OwogICAg
bWluLWhlaWdodDogMjgwcHg7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2ln
bmFsX3YxIl0gLmx4cy1tYXRlcmlhbC1jYXJkLS1mYWN0IHsKICAgIGdyaWQtY29sdW1uOiBzcGFu
IDU7CiAgICBtaW4taGVpZ2h0OiAxNjBweDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19zaWduYWxfdjEiXSAubHhzLW1hdGVyaWFsLWNhcmRfX2ljb24gewogICAgd2lkdGg6IDY2
cHg7CiAgICBoZWlnaHQ6IDY2cHg7CiAgICBtYXJnaW4tYm90dG9tOiBhdXRvOwogICAgZGlzcGxh
eTogZ3JpZDsKICAgIHBsYWNlLWl0ZW1zOiBjZW50ZXI7CiAgICBib3JkZXItcmFkaXVzOiAyMnB4
OwogICAgY29sb3I6IHZhcigtLWx4cy1zdWNjZXNzKTsKICAgIGJhY2tncm91bmQ6IHJnYmEoMjMs
MTIxLDkxLC4xKTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEi
XSAubHhzLW1hdGVyaWFsLWNhcmRfX2ljb24gc3ZnIHsKICAgIHdpZHRoOiAzOHB4OwogICAgaGVp
Z2h0OiAzOHB4Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
IC5seHMtbWF0ZXJpYWwtY2FyZCBzbWFsbCB7CiAgICBjb2xvcjogdmFyKC0tbHhzLXN1Y2Nlc3Mp
OwogICAgZm9udC1zaXplOiAxMHB4OwogICAgZm9udC13ZWlnaHQ6IDkwMDsKICAgIGxldHRlci1z
cGFjaW5nOiAuMWVtOwogICAgdGV4dC10cmFuc2Zvcm06IHVwcGVyY2FzZTsKfQoKLmx4cGRwW2Rh
dGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLW1hdGVyaWFsLWNhcmQgaDMg
ewogICAgbWFyZ2luOiAwOwogICAgZm9udC1zaXplOiBjbGFtcCgyMnB4LCAyLjV2dywgMzhweCk7
CiAgICBsaW5lLWhlaWdodDogMS4wNjsKICAgIGxldHRlci1zcGFjaW5nOiAtLjAzNWVtOwp9Cgou
bHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtbWF0ZXJpYWwt
Y2FyZCBwIHsKICAgIG1hcmdpbjogNHB4IDAgMDsKICAgIG1heC13aWR0aDogNTQwcHg7CiAgICBj
b2xvcjogIzRlNmQ2MzsKICAgIGZvbnQtc2l6ZTogMTNweDsKICAgIGxpbmUtaGVpZ2h0OiAxLjU1
Owp9CgovKiBDb25maWRlbmNlICovCgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3Np
Z25hbF92MSJdIC5seHBkcC1lbmdpbmUtc2VjdGlvbi0tc3R1ZGlvX2NvbmZpZGVuY2Vfc3RyaXAg
ewogICAgcGFkZGluZzogNDhweCAwOwogICAgYmFja2dyb3VuZDogI2ZmZjsKfQoKLmx4cGRwW2Rh
dGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWNvbmZpZGVuY2UgewogICAg
ZGlzcGxheTogZ3JpZDsKICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogcmVwZWF0KDQsIDFmcik7
CiAgICBvdmVyZmxvdzogaGlkZGVuOwogICAgYm9yZGVyOiAxcHggc29saWQgdmFyKC0tbHhzLWxp
bmUpOwogICAgYm9yZGVyLXJhZGl1czogMjZweDsKICAgIGJhY2tncm91bmQ6ICNmZmY7CiAgICBi
b3gtc2hhZG93OiB2YXIoLS1seHMtc2hhZG93LXNtKTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlh
bnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWNvbmZpZGVuY2UgYXJ0aWNsZSB7CiAgICBtaW4t
aGVpZ2h0OiAxMzZweDsKICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBncmlkLXRlbXBsYXRlLWNvbHVt
bnM6IDQycHggMWZyOwogICAgYWxpZ24tY29udGVudDogY2VudGVyOwogICAgZ2FwOiAxMnB4Owog
ICAgcGFkZGluZzogMjJweDsKICAgIGJvcmRlci1yaWdodDogMXB4IHNvbGlkIHZhcigtLWx4cy1s
aW5lKTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhz
LWNvbmZpZGVuY2UgYXJ0aWNsZTpsYXN0LWNoaWxkIHsKICAgIGJvcmRlci1yaWdodDogMDsKfQoK
Lmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWNvbmZpZGVu
Y2Ugc3ZnIHsKICAgIHdpZHRoOiAzMnB4OwogICAgaGVpZ2h0OiAzMnB4OwogICAgY29sb3I6IHZh
cigtLWx4cy1wcmltYXJ5KTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWdu
YWxfdjEiXSAubHhzLWNvbmZpZGVuY2UgZGl2IHsKICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBnYXA6
IDZweDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhz
LWNvbmZpZGVuY2Ugc3Ryb25nIHsKICAgIGZvbnQtc2l6ZTogMTRweDsKfQoKLmx4cGRwW2RhdGEt
cGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWNvbmZpZGVuY2Ugc3BhbiB7CiAg
ICBjb2xvcjogdmFyKC0tbHhzLWluay1zb2Z0KTsKICAgIGZvbnQtc2l6ZTogMTFweDsKICAgIGxp
bmUtaGVpZ2h0OiAxLjQ1Owp9CgovKiBQcm9kdWN0IHJvd3MgKi8KCi5seHBkcFtkYXRhLXBkcC12
YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cGRwLWVuZ2luZS1zZWN0aW9uLS1zdHVkaW9f
Y29tcGxldGVfbG9vaywKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEi
XSAubHhwZHAtZW5naW5lLXNlY3Rpb24tLXN0dWRpb19yZWNlbnRseV92aWV3ZWQgewogICAgcGFk
ZGluZzogODRweCAwOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhzLWNhbnZhcyk7Cn0KCi5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cGRwLWVuZ2luZS1zZWN0
aW9uLS1zdHVkaW9fcmVjZW50bHlfdmlld2VkIHsKICAgIHBhZGRpbmctdG9wOiAxMnB4Owp9Cgou
bHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtcHJvZHVjdC1y
b3cgewogICAgZGlzcGxheTogZ3JpZDsKICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogcmVwZWF0
KDQsIG1pbm1heCgwLCAxZnIpKTsKICAgIGdhcDogMTRweDsKfQoKLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXByb2R1Y3QtY2FyZCB7CiAgICBtaW4td2lk
dGg6IDA7CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ2FwOiA4cHg7CiAgICBjb2xvcjogdmFyKC0t
bHhzLWluayk7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0g
Lmx4cy1wcm9kdWN0LWNhcmQgPiBzcGFuIHsKICAgIGFzcGVjdC1yYXRpbzogNCAvIDU7CiAgICBv
dmVyZmxvdzogaGlkZGVuOwogICAgZGlzcGxheTogYmxvY2s7CiAgICBib3JkZXItcmFkaXVzOiAy
MnB4OwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhzLWNhbnZhcy1kZWVwKTsKfQoKLmx4cGRwW2Rh
dGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXByb2R1Y3QtY2FyZCBpbWcg
ewogICAgd2lkdGg6IDEwMCU7CiAgICBoZWlnaHQ6IDEwMCU7CiAgICBvYmplY3QtZml0OiBjb3Zl
cjsKICAgIHRyYW5zaXRpb246IHRyYW5zZm9ybSAuNXMgZWFzZTsKfQoKLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXByb2R1Y3QtY2FyZDpob3ZlciBpbWcg
ewogICAgdHJhbnNmb3JtOiBzY2FsZSgxLjAzKTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9
InN0dWRpb19zaWduYWxfdjEiXSAubHhzLXByb2R1Y3QtY2FyZCBzdHJvbmcgewogICAgbWFyZ2lu
LXRvcDogM3B4OwogICAgZm9udC1zaXplOiAxNHB4OwogICAgbGluZS1oZWlnaHQ6IDEuMzU7Cn0K
Ci5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1wcm9kdWN0
LWNhcmQgc21hbGwgewogICAgY29sb3I6IHZhcigtLWx4cy1wcmltYXJ5KTsKICAgIGZvbnQtc2l6
ZTogMTNweDsKICAgIGZvbnQtd2VpZ2h0OiA4NTA7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50
PSJzdHVkaW9fc2lnbmFsX3YxIl0gW2RhdGEtbHhzLXJlY2VudC1lbXB0eV0gewogICAgY29sb3I6
IHZhcigtLWx4cy1pbmstc29mdCk7Cn0KCi8qIEZpbmFsICovCgoubHhwZHBbZGF0YS1wZHAtdmFy
aWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHBkcC1lbmdpbmUtc2VjdGlvbi0tc3R1ZGlvX2Zp
bmFsX2N0YSB7CiAgICBwYWRkaW5nOiAxOHB4IDAgODhweDsKICAgIGJhY2tncm91bmQ6IHZhcigt
LWx4cy1jYW52YXMpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92
MSJdIC5seHMtZmluYWwtY3RhIHsKICAgIG1pbi1oZWlnaHQ6IDIzMHB4OwogICAgZGlzcGxheTog
Z3JpZDsKICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogMTgwcHggbWlubWF4KDAsIDFmcikgYXV0
bzsKICAgIGdhcDogMjhweDsKICAgIGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAgICBvdmVyZmxvdzog
aGlkZGVuOwogICAgcGFkZGluZzogMjJweDsKICAgIGJvcmRlci1yYWRpdXM6IDMycHg7CiAgICBj
b2xvcjogI2ZmZjsKICAgIGJhY2tncm91bmQ6CiAgICAgICAgcmFkaWFsLWdyYWRpZW50KGNpcmNs
ZSBhdCAwIDAsIHJnYmEoOTEsOTUsMjQyLC42NSksIHRyYW5zcGFyZW50IDMwcmVtKSwKICAgICAg
ICByYWRpYWwtZ3JhZGllbnQoY2lyY2xlIGF0IDEwMCUgMTAwJSwgcmdiYSgyNTUsNjUsMTA4LC4z
NSksIHRyYW5zcGFyZW50IDI0cmVtKSwKICAgICAgICB2YXIoLS1seHMtZ3JhcGhpdGUpOwogICAg
Ym94LXNoYWRvdzogMCAyOHB4IDgwcHggcmdiYSgxOCwyNCw0OCwuMik7Cn0KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1maW5hbC1jdGFfX21lZGlhIHsK
ICAgIGhlaWdodDogMTg0cHg7CiAgICBvdmVyZmxvdzogaGlkZGVuOwogICAgYm9yZGVyLXJhZGl1
czogMjJweDsKICAgIGJhY2tncm91bmQ6ICMyYjMwNDA7Cn0KCi5seHBkcFtkYXRhLXBkcC12YXJp
YW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1maW5hbC1jdGFfX21lZGlhIGltZyB7CiAgICB3
aWR0aDogMTAwJTsKICAgIGhlaWdodDogMTAwJTsKICAgIG9iamVjdC1maXQ6IGNvdmVyOwp9Cgou
bHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZmluYWwtY3Rh
IC5seHMta2lja2VyIHsKICAgIGNvbG9yOiAjYjdiOWZmOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFy
aWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZmluYWwtY3RhIGgyIHsKICAgIG1hcmdpbjog
MDsKICAgIGZvbnQtc2l6ZTogY2xhbXAoMzRweCwgNHZ3LCA1NnB4KTsKICAgIGxpbmUtaGVpZ2h0
OiAuOTg7CiAgICBsZXR0ZXItc3BhY2luZzogLS4wNWVtOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFy
aWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZmluYWwtY3RhIHA6bm90KC5seHMta2lja2Vy
KSB7CiAgICBtYXJnaW46IDEwcHggMCAwOwogICAgY29sb3I6ICNiOWMwZDE7Cn0KCi5seHBkcFtk
YXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1maW5hbC1jdGFfX2FjdGlv
biB7CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ2FwOiAxMnB4OwogICAganVzdGlmeS1pdGVtczog
ZW5kOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMt
ZmluYWwtY3RhX19hY3Rpb24gPiBzdHJvbmcgewogICAgZm9udC1zaXplOiAyMnB4Owp9CgoubHhw
ZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZmluYWwtY3RhX19h
Y3Rpb24gLmx4cy1idXR0b24gewogICAgYmFja2dyb3VuZDogdmFyKC0tbHhzLXNpZ25hbCk7Cn0K
Ci8qIE1vYmlsZSBkb2NrICovCgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25h
bF92MSJdIC5seHMtbW9iaWxlLWRvY2sgewogICAgZGlzcGxheTogbm9uZTsKfQoKLyogU2l6ZSBh
ZHZpc29yIHNraW4gKi8KCi5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3Yx
Il0gLmx4cGRwLWFkdmlzb3IgewogICAgYm9yZGVyOiAwOwogICAgYm9yZGVyLXJhZGl1czogMjhw
eDsKICAgIGNvbG9yOiB2YXIoLS1seHMtaW5rKTsKICAgIGJhY2tncm91bmQ6ICNmZmY7CiAgICBi
b3gtc2hhZG93OiAwIDQwcHggMTIwcHggcmdiYSgxMiwxOCwzOCwuMyk7Cn0KCi5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cGRwLWFkdmlzb3I6OmJhY2tkcm9w
IHsKICAgIGJhY2tncm91bmQ6IHJnYmEoMTEsMTUsMjgsLjU4KTsKICAgIGJhY2tkcm9wLWZpbHRl
cjogYmx1cig4cHgpOwp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92
MSJdIC5seHBkcC1hZHZpc29yIGlucHV0LAoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlv
X3NpZ25hbF92MSJdIC5seHBkcC1hZHZpc29yIHNlbGVjdCB7CiAgICBib3JkZXItY29sb3I6IHZh
cigtLWx4cy1saW5lKTsKICAgIGJvcmRlci1yYWRpdXM6IDE0cHg7CiAgICBiYWNrZ3JvdW5kOiB2
YXIoLS1seHMtY2FudmFzKTsKfQoKLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWdu
YWxfdjEiXSAubHhwZHAtYWR2aXNvciBpbnB1dDpmb2N1cywKLmx4cGRwW2RhdGEtcGRwLXZhcmlh
bnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhwZHAtYWR2aXNvciBzZWxlY3Q6Zm9jdXMgewogICAg
Ym9yZGVyLWNvbG9yOiB2YXIoLS1seHMtcHJpbWFyeSk7CiAgICBib3gtc2hhZG93OiAwIDAgMCAz
cHggcmdiYSg5MSw5NSwyNDIsLjEyKTsKfQoKLyogUmV2ZWFsICovCgoubHhwZHBbZGF0YS1wZHAt
dmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIFtkYXRhLWx4cy1yZXZlYWxdIHsKICAgIG9wYWNp
dHk6IDA7CiAgICB0cmFuc2Zvcm06IHRyYW5zbGF0ZVkoMjJweCk7CiAgICB0cmFuc2l0aW9uOiBv
cGFjaXR5IC42NXMgZWFzZSwgdHJhbnNmb3JtIC42NXMgY3ViaWMtYmV6aWVyKC4yLC43LC4yLDEp
Owp9CgoubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIFtkYXRhLWx4
cy1yZXZlYWxdLmlzLXZpc2libGUgewogICAgb3BhY2l0eTogMTsKICAgIHRyYW5zZm9ybTogbm9u
ZTsKfQoKQG1lZGlhIChtYXgtd2lkdGg6IDExNjBweCkgewogICAgLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWhlcm8gewogICAgICAgIGdyaWQtdGVtcGxh
dGUtY29sdW1uczogbWlubWF4KDAsIDEuMTVmcikgbWlubWF4KDM2MHB4LCAuODVmcik7CiAgICB9
CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWdh
bGxlcnlfX3N0YWdlIHsKICAgICAgICBtaW4taGVpZ2h0OiA1ODBweDsKICAgIH0KCiAgICAubHhw
ZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtYnV5IHsKICAgICAg
ICBwYWRkaW5nOiAyOHB4OwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVk
aW9fc2lnbmFsX3YxIl0gLmx4cy1idXlfX2hlYWQgaDEgewogICAgICAgIGZvbnQtc2l6ZTogNTJw
eDsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
IC5seHMtZGVzaWduLWV4cGxvcmVyX192aXN1YWwgewogICAgICAgIG1pbi1oZWlnaHQ6IDYyMHB4
OwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0g
Lmx4cy1jb25maWRlbmNlIHsKICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IHJlcGVhdCgy
LCAxZnIpOwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFs
X3YxIl0gLmx4cy1jb25maWRlbmNlIGFydGljbGU6bnRoLWNoaWxkKDIpIHsKICAgICAgICBib3Jk
ZXItcmlnaHQ6IDA7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19z
aWduYWxfdjEiXSAubHhzLWNvbmZpZGVuY2UgYXJ0aWNsZTpudGgtY2hpbGQoLW4rMikgewogICAg
ICAgIGJvcmRlci1ib3R0b206IDFweCBzb2xpZCB2YXIoLS1seHMtbGluZSk7CiAgICB9Cn0KCkBt
ZWRpYSAobWF4LXdpZHRoOiA5MDBweCkgewogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19zaWduYWxfdjEiXSAubHhzLXNoZWxsLAogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9
InN0dWRpb19zaWduYWxfdjEiXSAubHhwZHBfX2JyZWFkY3J1bWIgewogICAgICAgIHdpZHRoOiBt
aW4oMTAwJSAtIDI4cHgsIHZhcigtLWx4cy1tYXgpKTsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1w
ZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2VjdGlvbi1oZWFkaW5nLS1zcGxp
dCB7CiAgICAgICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiAxZnI7CiAgICAgICAgZ2FwOiAxNHB4
OwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0g
Lmx4cy1zZWN0aW9uLWhlYWRpbmctLXNwbGl0ID4gYSB7CiAgICAgICAganVzdGlmeS1zZWxmOiBz
dGFydDsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92
MSJdIC5seHMtaGVybyB7CiAgICAgICAgbWluLWhlaWdodDogYXV0bzsKICAgICAgICBncmlkLXRl
bXBsYXRlLWNvbHVtbnM6IDFmcjsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0i
c3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZ2FsbGVyeV9fc3RhZ2UgewogICAgICAgIGhlaWdodDog
bWluKDc4dmgsIDY5MHB4KTsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1
ZGlvX3NpZ25hbF92MSJdIC5seHMtYnV5IHsKICAgICAgICBwb3NpdGlvbjogc3RhdGljOwogICAg
fQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1x
dWljay1yZWFkX19ncmlkIHsKICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IHJlcGVhdCgy
LCAxZnIpOwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFs
X3YxIl0gLmx4cy1kZXNpZ24tZXhwbG9yZXJfX2xheW91dCwKICAgIC5seHBkcFtkYXRhLXBkcC12
YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zaXplLXN0dWRpb19fd29ya3NwYWNlIHsK
ICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IDFmcjsKICAgIH0KCiAgICAubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZGVzaWduLWV4cGxvcmVyX192
aXN1YWwgewogICAgICAgIG1pbi1oZWlnaHQ6IDY2MHB4OwogICAgfQoKICAgIC5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1kZXNpZ24tZXhwbG9yZXJfX2Nh
cmRzIHsKICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IHJlcGVhdCgyLCAxZnIpOwogICAg
fQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1k
ZXNpZ24tY2FyZC5pcy1hY3RpdmUgewogICAgICAgIHRyYW5zZm9ybTogdHJhbnNsYXRlWSgtNHB4
KTsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
IC5seHMtYmVuZWZpdHNfX2dyaWQgewogICAgICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogMWZy
IDFmcjsKICAgICAgICBncmlkLXRlbXBsYXRlLXJvd3M6IDQyMHB4IDI3MHB4OwogICAgfQoKICAg
IC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1iZW5lZml0
LWNhcmQtLTEgewogICAgICAgIGdyaWQtcm93OiAxOwogICAgICAgIGdyaWQtY29sdW1uOiAxIC8g
MzsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
IC5seHMtYmVuZWZpdC1jYXJkLS0yIHsKICAgICAgICBncmlkLWNvbHVtbjogYXV0bzsKICAgIH0K
CiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtbWVk
aWEtZ3JpZCB7CiAgICAgICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiByZXBlYXQoNiwgMWZyKTsK
ICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5s
eHMtbWVkaWEtZ3JpZF9faXRlbS0tMSB7CiAgICAgICAgZ3JpZC1jb2x1bW46IHNwYW4gNDsKICAg
IH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMt
bWVkaWEtZ3JpZF9faXRlbS0tMiwKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9f
c2lnbmFsX3YxIl0gLmx4cy1tZWRpYS1ncmlkX19pdGVtLS0zLAogICAgLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLW1lZGlhLWdyaWRfX2l0ZW0tLTQsCiAg
ICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtbWVkaWEt
Z3JpZF9faXRlbS0tNSB7CiAgICAgICAgZ3JpZC1jb2x1bW46IHNwYW4gMjsKICAgIH0KCiAgICAu
bHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtbWF0ZXJpYWwt
Y2FyZC0tcHJpbWFyeSwKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFs
X3YxIl0gLmx4cy1tYXRlcmlhbC1jYXJkOm50aC1jaGlsZCgyKSwKICAgIC5seHBkcFtkYXRhLXBk
cC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1tYXRlcmlhbC1jYXJkLS1mYWN0IHsK
ICAgICAgICBncmlkLWNvbHVtbjogc3BhbiA2OwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12
YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1wcm9kdWN0LXJvdyB7CiAgICAgICAgZ3Jp
ZC10ZW1wbGF0ZS1jb2x1bW5zOiByZXBlYXQoMywgMWZyKTsKICAgIH0KfQoKQG1lZGlhIChtYXgt
d2lkdGg6IDc4MHB4KSB7CiAgICBib2R5OmhhcygubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1
ZGlvX3NpZ25hbF92MSJdKSAubHh2Mi1tYWluLAogICAgYm9keS5seC1wZHAtc3R1ZGlvLXNpZ25h
bCAubHh2Mi1tYWluIHsKICAgICAgICB3aWR0aDogMTAwJTsKICAgICAgICBwYWRkaW5nLWJvdHRv
bTogMTA0cHg7CiAgICB9CgogICAgYm9keTpoYXMoLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19zaWduYWxfdjEiXSkgLmx4djItZm9vdGVyLAogICAgYm9keS5seC1wZHAtc3R1ZGlvLXNp
Z25hbCAubHh2Mi1mb290ZXIgewogICAgICAgIHdpZHRoOiBjYWxjKDEwMCUgLSAyNHB4KTsKICAg
ICAgICBtYXJnaW4tYm90dG9tOiA5NHB4OwogICAgfQoKICAgIGJvZHk6aGFzKC5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0pIC5seHYyLWJvdHRvbS1uYXYsCiAgICBi
b2R5Lmx4LXBkcC1zdHVkaW8tc2lnbmFsIC5seHYyLWJvdHRvbS1uYXYsCiAgICAubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHBkcC1tb2JpbGUtYnV5IHsKICAg
ICAgICBkaXNwbGF5OiBub25lICFpbXBvcnRhbnQ7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhwZHAtcHJldmlldy1iYW5uZXIgewogICAg
ICAgIG1hcmdpbi1pbmxpbmU6IDE0cHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlh
bnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhwZHBfX2JyZWFkY3J1bWIgewogICAgICAgIG1hcmdp
bi10b3A6IDRweDsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3Np
Z25hbF92MSJdIC5seHMtc2hlbGwsCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlv
X3NpZ25hbF92MSJdIC5seHBkcF9fYnJlYWRjcnVtYiB7CiAgICAgICAgd2lkdGg6IGNhbGMoMTAw
JSAtIDI4cHgpOwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2ln
bmFsX3YxIl0gLmx4cGRwLWVuZ2luZS1zZWN0aW9uLS1zdHVkaW9faGVyb19wdXJjaGFzZSB7CiAg
ICAgICAgcGFkZGluZy1ib3R0b206IDUycHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWdhbGxlcnlfX3N0YWdlIHsKICAgICAgICBt
aW4taGVpZ2h0OiAwOwogICAgICAgIGhlaWdodDogYXV0bzsKICAgICAgICBhc3BlY3QtcmF0aW86
IDQgLyA1OwogICAgICAgIGJvcmRlci1yYWRpdXM6IDI0cHg7CiAgICB9CgogICAgLmx4cGRwW2Rh
dGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWdhbGxlcnlfX25hdiB7CiAg
ICAgICAgd2lkdGg6IDQwcHg7CiAgICAgICAgaGVpZ2h0OiA0MHB4OwogICAgfQoKICAgIC5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1nYWxsZXJ5X19uYXYt
LXByZXYgewogICAgICAgIGxlZnQ6IDEwcHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZh
cmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWdhbGxlcnlfX25hdi0tbmV4dCB7CiAgICAg
ICAgcmlnaHQ6IDEwcHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRp
b19zaWduYWxfdjEiXSAubHhzLWdhbGxlcnlfX3RodW1icyB7CiAgICAgICAgZ3JpZC1hdXRvLWNv
bHVtbnM6IDYxcHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19z
aWduYWxfdjEiXSAubHhzLWJ1eSB7CiAgICAgICAgcGFkZGluZzogMjRweCAyMHB4OwogICAgICAg
IGJvcmRlci1yYWRpdXM6IDI2cHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9
InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWJ1eV9faGVhZCBoMSB7CiAgICAgICAgZm9udC1zaXpl
OiA0NnB4OwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFs
X3YxIl0gLmx4cy1wcmljZS1saW5lIHsKICAgICAgICBhbGlnbi1pdGVtczogZmxleC1zdGFydDsK
ICAgICAgICBmbGV4LWRpcmVjdGlvbjogY29sdW1uOwogICAgICAgIGdhcDogOHB4OwogICAgfQoK
ICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1idXkt
Y29uZmlkZW5jZSB7CiAgICAgICAgZGlzcGxheTogZmxleDsKICAgICAgICBvdmVyZmxvdy14OiBh
dXRvOwogICAgICAgIHNjcm9sbGJhci13aWR0aDogbm9uZTsKICAgIH0KCiAgICAubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtYnV5LWNvbmZpZGVuY2Ugc3Bh
biB7CiAgICAgICAgbWluLXdpZHRoOiAxMjBweDsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAt
dmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtcXVpY2stcmVhZF9fZ3JpZCB7CiAgICAg
ICAgZGlzcGxheTogZmxleDsKICAgICAgICBvdmVyZmxvdy14OiBhdXRvOwogICAgICAgIHBhZGRp
bmctYm90dG9tOiA4cHg7CiAgICAgICAgc2Nyb2xsLXNuYXAtdHlwZTogeCBtYW5kYXRvcnk7CiAg
ICAgICAgc2Nyb2xsYmFyLXdpZHRoOiBub25lOwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12
YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1mYWN0LWNhcmQgewogICAgICAgIG1pbi13
aWR0aDogMjIwcHg7CiAgICAgICAgbWluLWhlaWdodDogMTUwcHg7CiAgICAgICAgc2Nyb2xsLXNu
YXAtYWxpZ246IHN0YXJ0OwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVk
aW9fc2lnbmFsX3YxIl0gLmx4cy1zZWN0aW9uLWhlYWRpbmcgaDIgewogICAgICAgIGZvbnQtc2l6
ZTogMzBweDsKICAgICAgICBsaW5lLWhlaWdodDogMS4wODsKICAgIH0KCiAgICAubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHBkcC1lbmdpbmUtc2VjdGlvbi0t
c3R1ZGlvX2Rlc2lnbl9leHBsb3JlciwKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVk
aW9fc2lnbmFsX3YxIl0gLmx4cGRwLWVuZ2luZS1zZWN0aW9uLS1zdHVkaW9fYmVuZWZpdF9ncmlk
LAogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhwZHAt
ZW5naW5lLXNlY3Rpb24tLXN0dWRpb19tZWRpYV9sYWIsCiAgICAubHhwZHBbZGF0YS1wZHAtdmFy
aWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtc2l6ZS1zdHVkaW8sCiAgICAubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHBkcC1lbmdpbmUtc2VjdGlvbi0t
c3R1ZGlvX21hdGVyaWFsX2ZlZWwgewogICAgICAgIHBhZGRpbmctYmxvY2s6IDY4cHg7CiAgICB9
CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWRl
c2lnbi1leHBsb3Jlcl9fdmlzdWFsIHsKICAgICAgICBtaW4taGVpZ2h0OiAwOwogICAgICAgIGFz
cGVjdC1yYXRpbzogNCAvIDU7CiAgICAgICAgYm9yZGVyLXJhZGl1czogMjRweDsKICAgIH0KCiAg
ICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZGVzaWdu
LWV4cGxvcmVyX19jYXJkcyB7CiAgICAgICAgZGlzcGxheTogZmxleDsKICAgICAgICBvdmVyZmxv
dy14OiBhdXRvOwogICAgICAgIHBhZGRpbmctYm90dG9tOiA3cHg7CiAgICAgICAgc2Nyb2xsLXNu
YXAtdHlwZTogeCBtYW5kYXRvcnk7CiAgICAgICAgc2Nyb2xsYmFyLXdpZHRoOiBub25lOwogICAg
fQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1k
ZXNpZ24tY2FyZCB7CiAgICAgICAgbWluLXdpZHRoOiAyNjBweDsKICAgICAgICBzY3JvbGwtc25h
cC1hbGlnbjogc3RhcnQ7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRp
b19zaWduYWxfdjEiXSAubHhzLWJlbmVmaXRzX19ncmlkIHsKICAgICAgICBkaXNwbGF5OiBmbGV4
OwogICAgICAgIG92ZXJmbG93LXg6IGF1dG87CiAgICAgICAgZ2FwOiAxMnB4OwogICAgICAgIHBh
ZGRpbmctYm90dG9tOiA4cHg7CiAgICAgICAgc2Nyb2xsLXNuYXAtdHlwZTogeCBtYW5kYXRvcnk7
CiAgICAgICAgc2Nyb2xsYmFyLXdpZHRoOiBub25lOwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBk
cC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1iZW5lZml0LWNhcmQgewogICAgICAg
IG1pbi13aWR0aDogODJ2dzsKICAgICAgICBoZWlnaHQ6IDQyMHB4OwogICAgICAgIHNjcm9sbC1z
bmFwLWFsaWduOiBjZW50ZXI7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19zaWduYWxfdjEiXSAubHhzLW1lZGlhLWdyaWQuaXMtYWN0aXZlIHsKICAgICAgICBkaXNw
bGF5OiBmbGV4OwogICAgICAgIG92ZXJmbG93LXg6IGF1dG87CiAgICAgICAgZ2FwOiAxMHB4Owog
ICAgICAgIHBhZGRpbmctYm90dG9tOiA4cHg7CiAgICAgICAgc2Nyb2xsLXNuYXAtdHlwZTogeCBt
YW5kYXRvcnk7CiAgICAgICAgc2Nyb2xsYmFyLXdpZHRoOiBub25lOwogICAgfQoKICAgIC5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1tZWRpYS1ncmlkX19p
dGVtIHsKICAgICAgICBtaW4td2lkdGg6IDc4dnc7CiAgICAgICAgaGVpZ2h0OiA0NTBweDsKICAg
ICAgICBzY3JvbGwtc25hcC1hbGlnbjogY2VudGVyOwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBk
cC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1zaXplLXN0dWRpb19fc2l6ZS1jYXJk
cyB7CiAgICAgICAgZGlzcGxheTogZmxleDsKICAgICAgICBvdmVyZmxvdy14OiBhdXRvOwogICAg
ICAgIHBhZGRpbmctYm90dG9tOiA4cHg7CiAgICAgICAgc2Nyb2xsYmFyLXdpZHRoOiBub25lOwog
ICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4
cy1zaXplLXN0dWRpb19fc2l6ZS1jYXJkcyBidXR0b24gewogICAgICAgIG1pbi13aWR0aDogMTI2
cHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEi
XSAubHhzLXNpemUtZmlndXJlLAogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19z
aWduYWxfdjEiXSAubHhzLXNpemUtdmFsdWVzIHsKICAgICAgICBtaW4taGVpZ2h0OiAwOwogICAg
fQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1z
aXplLXZhbHVlc19fYWN0aW9ucyB7CiAgICAgICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiAxZnI7
CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAu
bHhzLXNpemUtdmFsdWVzX19hY3Rpb25zIC5seHMtYnV0dG9uIHsKICAgICAgICB3aWR0aDogMTAw
JTsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
IC5seHMtbWF0ZXJpYWwtZmVlbF9fZ3JpZCB7CiAgICAgICAgZGlzcGxheTogZmxleDsKICAgICAg
ICBvdmVyZmxvdy14OiBhdXRvOwogICAgICAgIGdhcDogMTJweDsKICAgICAgICBwYWRkaW5nLWJv
dHRvbTogOHB4OwogICAgICAgIHNjcm9sbC1zbmFwLXR5cGU6IHggbWFuZGF0b3J5OwogICAgICAg
IHNjcm9sbGJhci13aWR0aDogbm9uZTsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFu
dD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtbWF0ZXJpYWwtY2FyZCwKICAgIC5seHBkcFtkYXRh
LXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1tYXRlcmlhbC1jYXJkLS1wcmlt
YXJ5LAogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhz
LW1hdGVyaWFsLWNhcmQ6bnRoLWNoaWxkKDIpLAogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9
InN0dWRpb19zaWduYWxfdjEiXSAubHhzLW1hdGVyaWFsLWNhcmQtLWZhY3QgewogICAgICAgIG1p
bi13aWR0aDogODJ2dzsKICAgICAgICBtaW4taGVpZ2h0OiAyNzBweDsKICAgICAgICBzY3JvbGwt
c25hcC1hbGlnbjogY2VudGVyOwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJz
dHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1jb25maWRlbmNlIHsKICAgICAgICBkaXNwbGF5OiBmbGV4
OwogICAgICAgIG92ZXJmbG93LXg6IGF1dG87CiAgICAgICAgYm9yZGVyLXJhZGl1czogMjJweDsK
ICAgICAgICBzY3JvbGwtc25hcC10eXBlOiB4IG1hbmRhdG9yeTsKICAgICAgICBzY3JvbGxiYXIt
d2lkdGg6IG5vbmU7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19z
aWduYWxfdjEiXSAubHhzLWNvbmZpZGVuY2UgYXJ0aWNsZSB7CiAgICAgICAgbWluLXdpZHRoOiAy
NjBweDsKICAgICAgICBib3JkZXItcmlnaHQ6IDFweCBzb2xpZCB2YXIoLS1seHMtbGluZSkgIWlt
cG9ydGFudDsKICAgICAgICBib3JkZXItYm90dG9tOiAwICFpbXBvcnRhbnQ7CiAgICAgICAgc2Ny
b2xsLXNuYXAtYWxpZ246IHN0YXJ0OwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50
PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1wcm9kdWN0LXJvdyB7CiAgICAgICAgZGlzcGxheTog
ZmxleDsKICAgICAgICBvdmVyZmxvdy14OiBhdXRvOwogICAgICAgIGdhcDogMTJweDsKICAgICAg
ICBwYWRkaW5nLWJvdHRvbTogOHB4OwogICAgICAgIHNjcm9sbC1zbmFwLXR5cGU6IHggbWFuZGF0
b3J5OwogICAgICAgIHNjcm9sbGJhci13aWR0aDogbm9uZTsKICAgIH0KCiAgICAubHhwZHBbZGF0
YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtcHJvZHVjdC1jYXJkIHsKICAg
ICAgICBtaW4td2lkdGg6IDU4dnc7CiAgICAgICAgc2Nyb2xsLXNuYXAtYWxpZ246IHN0YXJ0Owog
ICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4
cy1maW5hbC1jdGEgewogICAgICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogOTBweCAxZnI7CiAg
ICAgICAgbWluLWhlaWdodDogMDsKICAgICAgICBwYWRkaW5nOiAxNHB4OwogICAgICAgIGJvcmRl
ci1yYWRpdXM6IDI0cHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRp
b19zaWduYWxfdjEiXSAubHhzLWZpbmFsLWN0YV9fbWVkaWEgewogICAgICAgIGhlaWdodDogMTEy
cHg7CiAgICAgICAgYm9yZGVyLXJhZGl1czogMTdweDsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1w
ZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtZmluYWwtY3RhIGgyIHsKICAgICAg
ICBmb250LXNpemU6IDMxcHg7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0
dWRpb19zaWduYWxfdjEiXSAubHhzLWZpbmFsLWN0YV9fYWN0aW9uIHsKICAgICAgICBncmlkLWNv
bHVtbjogMSAvIDM7CiAgICAgICAgd2lkdGg6IDEwMCU7CiAgICAgICAgZ3JpZC10ZW1wbGF0ZS1j
b2x1bW5zOiAxZnIgYXV0bzsKICAgICAgICBhbGlnbi1pdGVtczogY2VudGVyOwogICAgICAgIGp1
c3RpZnktaXRlbXM6IHN0cmV0Y2g7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9
InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWZpbmFsLWN0YV9fYWN0aW9uIC5seHMtYnV0dG9uIHsK
ICAgICAgICBtaW4taGVpZ2h0OiA0NHB4OwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJp
YW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1tb2JpbGUtZG9jayB7CiAgICAgICAgcG9zaXRp
b246IGZpeGVkOwogICAgICAgIHotaW5kZXg6IDMwMDsKICAgICAgICBsZWZ0OiAxMHB4OwogICAg
ICAgIHJpZ2h0OiAxMHB4OwogICAgICAgIGJvdHRvbTogbWF4KDhweCwgZW52KHNhZmUtYXJlYS1p
bnNldC1ib3R0b20pKTsKICAgICAgICBtaW4taGVpZ2h0OiA2NHB4OwogICAgICAgIGRpc3BsYXk6
IGdyaWQ7CiAgICAgICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiByZXBlYXQoNCwgNDJweCkgbWlu
bWF4KDE0MnB4LCAxZnIpOwogICAgICAgIGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAgICAgICAgZ2Fw
OiA0cHg7CiAgICAgICAgcGFkZGluZzogN3B4OwogICAgICAgIGJvcmRlcjogMXB4IHNvbGlkIHJn
YmEoMjE3LDIyNCwyMzgsLjk0KTsKICAgICAgICBib3JkZXItcmFkaXVzOiAyMnB4OwogICAgICAg
IGJhY2tncm91bmQ6IHJnYmEoMjU1LDI1NSwyNTUsLjk0KTsKICAgICAgICBib3gtc2hhZG93OiAw
IDIwcHggNjBweCByZ2JhKDE4LDI3LDU1LC4yMik7CiAgICAgICAgYmFja2Ryb3AtZmlsdGVyOiBi
bHVyKDIwcHgpIHNhdHVyYXRlKDE2MCUpOwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJp
YW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1tb2JpbGUtZG9jayA+IGEgewogICAgICAgIGhl
aWdodDogNDhweDsKICAgICAgICBkaXNwbGF5OiBncmlkOwogICAgICAgIHBsYWNlLWl0ZW1zOiBj
ZW50ZXI7CiAgICAgICAgYWxpZ24tY29udGVudDogY2VudGVyOwogICAgICAgIGdhcDogMXB4Owog
ICAgICAgIGJvcmRlci1yYWRpdXM6IDE0cHg7CiAgICAgICAgY29sb3I6IHZhcigtLWx4cy1pbmst
c29mdCk7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxf
djEiXSAubHhzLW1vYmlsZS1kb2NrID4gYTphY3RpdmUgewogICAgICAgIGNvbG9yOiB2YXIoLS1s
eHMtcHJpbWFyeSk7CiAgICAgICAgYmFja2dyb3VuZDogdmFyKC0tbHhzLXByaW1hcnktc29mdCk7
CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAu
bHhzLW1vYmlsZS1kb2NrID4gYSBzdmcgewogICAgICAgIHdpZHRoOiAyMXB4OwogICAgICAgIGhl
aWdodDogMjFweDsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3Np
Z25hbF92MSJdIC5seHMtbW9iaWxlLWRvY2sgPiBhIHNwYW4gewogICAgICAgIGZvbnQtc2l6ZTog
OHB4OwogICAgICAgIGZvbnQtd2VpZ2h0OiA4NTA7CiAgICB9CgogICAgLmx4cGRwW2RhdGEtcGRw
LXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLW1vYmlsZS1kb2NrX19jdGEgewogICAg
ICAgIGhlaWdodDogNDhweDsKICAgICAgICBtaW4td2lkdGg6IDA7CiAgICAgICAgZGlzcGxheTog
ZmxleDsKICAgICAgICBhbGlnbi1pdGVtczogY2VudGVyOwogICAgICAgIGp1c3RpZnktY29udGVu
dDogY2VudGVyOwogICAgICAgIGdhcDogN3B4OwogICAgICAgIHBhZGRpbmc6IDAgMTJweDsKICAg
ICAgICBib3JkZXI6IDA7CiAgICAgICAgYm9yZGVyLXJhZGl1czogMTVweDsKICAgICAgICBjb2xv
cjogI2ZmZjsKICAgICAgICBiYWNrZ3JvdW5kOiBsaW5lYXItZ3JhZGllbnQoMTM1ZGVnLCB2YXIo
LS1seHMtc2lnbmFsKSwgI2ZmNmI4ZSk7CiAgICAgICAgYm94LXNoYWRvdzogMCAxMHB4IDI0cHgg
cmdiYSgyNTUsNjUsMTA4LC4yNik7CiAgICAgICAgZm9udC1zaXplOiAxMXB4OwogICAgICAgIGZv
bnQtd2VpZ2h0OiA5MDA7CiAgICAgICAgY3Vyc29yOiBwb2ludGVyOwogICAgfQoKICAgIC5seHBk
cFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1tb2JpbGUtZG9ja19f
Y3RhIHN2ZyB7CiAgICAgICAgd2lkdGg6IDE3cHg7CiAgICAgICAgaGVpZ2h0OiAxN3B4OwogICAg
fQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1t
b2JpbGUtZG9ja19fY3RhOmRpc2FibGVkIHsKICAgICAgICBjb2xvcjogIzhmOTdhNzsKICAgICAg
ICBiYWNrZ3JvdW5kOiAjZThlY2YzOwogICAgICAgIGJveC1zaGFkb3c6IG5vbmU7CiAgICAgICAg
Y3Vyc29yOiBub3QtYWxsb3dlZDsKICAgIH0KfQoKQG1lZGlhIChtYXgtd2lkdGg6IDQyMHB4KSB7
CiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtbW9i
aWxlLWRvY2sgewogICAgICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogcmVwZWF0KDQsIDM4cHgp
IG1pbm1heCgxMjVweCwgMWZyKTsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0i
c3R1ZGlvX3NpZ25hbF92MSJdIC5seHMtbW9iaWxlLWRvY2tfX2N0YSB7CiAgICAgICAgcGFkZGlu
Zy1pbmxpbmU6IDlweDsKICAgICAgICBmb250LXNpemU6IDEwcHg7CiAgICB9CgogICAgLmx4cGRw
W2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAubHhzLWNvbG9yIHsKICAgICAg
ICBmbGV4LWJhc2lzOiA2OHB4OwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJz
dHVkaW9fc2lnbmFsX3YxIl0gLmx4cy1jb2xvcl9fdmlzdWFsIHsKICAgICAgICB3aWR0aDogNjRw
eDsKICAgIH0KCiAgICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJd
IC5seHMtc2l6ZS1saXN0IHsKICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IHJlcGVhdCg0
LCAxZnIpOwogICAgfQp9CgpAbWVkaWEgKHByZWZlcnMtcmVkdWNlZC1tb3Rpb246IHJlZHVjZSkg
ewogICAgLmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAqLAogICAg
Lmx4cGRwW2RhdGEtcGRwLXZhcmlhbnQ9InN0dWRpb19zaWduYWxfdjEiXSAqOjpiZWZvcmUsCiAg
ICAubHhwZHBbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdICo6OmFmdGVyIHsK
ICAgICAgICBzY3JvbGwtYmVoYXZpb3I6IGF1dG8gIWltcG9ydGFudDsKICAgICAgICBhbmltYXRp
b246IG5vbmUgIWltcG9ydGFudDsKICAgICAgICB0cmFuc2l0aW9uLWR1cmF0aW9uOiAuMDFtcyAh
aW1wb3J0YW50OwogICAgfQoKICAgIC5seHBkcFtkYXRhLXBkcC12YXJpYW50PSJzdHVkaW9fc2ln
bmFsX3YxIl0gW2RhdGEtbHhzLXJldmVhbF0gewogICAgICAgIG9wYWNpdHk6IDE7CiAgICAgICAg
dHJhbnNmb3JtOiBub25lOwogICAgfQp9Cg==
STUDIO_SIGNAL_PAYLOAD_1

decode_to_file 'public/commerce-v2/pdp/v1/variants/studio-signal-v1.js' <<'STUDIO_SIGNAL_PAYLOAD_2'
aW1wb3J0ICcuLi9jb3JlLmpzJzsKCmNvbnN0IHJvb3QgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9y
KCdbZGF0YS1wZHAtdmFyaWFudD0ic3R1ZGlvX3NpZ25hbF92MSJdJyk7CmNvbnN0IHByb2R1Y3RO
b2RlID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2x4djJQcm9kdWN0RGF0YScpOwoKaWYgKHJv
b3QgJiYgcHJvZHVjdE5vZGUpIHsKICAgIGRvY3VtZW50LmJvZHkuY2xhc3NMaXN0LmFkZCgnbHgt
cGRwLXN0dWRpby1zaWduYWwnKTsKCiAgICBsZXQgcHJvZHVjdCA9IHt9OwoKICAgIHRyeSB7CiAg
ICAgICAgcHJvZHVjdCA9IEpTT04ucGFyc2UocHJvZHVjdE5vZGUudGV4dENvbnRlbnQgfHwgJ3t9
Jyk7CiAgICB9IGNhdGNoIChlcnJvcikgewogICAgICAgIGNvbnNvbGUuZXJyb3IoJ0tow7RuZyDE
keG7jWMgxJHGsOG7o2MgUERQIHBheWxvYWQgY2hvIFN0dWRpbyBTaWduYWwuJywgZXJyb3IpOwog
ICAgfQoKICAgIGNvbnN0IHJlZHVjZWRNb3Rpb24gPSB3aW5kb3cubWF0Y2hNZWRpYSgnKHByZWZl
cnMtcmVkdWNlZC1tb3Rpb246IHJlZHVjZSknKS5tYXRjaGVzOwogICAgY29uc3QgY29sb3JzID0g
QXJyYXkuaXNBcnJheShwcm9kdWN0LmNvbG9ycykgPyBwcm9kdWN0LmNvbG9ycyA6IFtdOwogICAg
Y29uc3Qgcm9sZUxhYmVscyA9IHsKICAgICAgICBoZXJvOiAnVOG7lW5nIHRo4buDJywKICAgICAg
ICBmcm9udDogJ03hurd0IHRyxrDhu5tjJywKICAgICAgICBzaWRlOiAnR8OzYyBuZ2hpw6puZycs
CiAgICAgICAgYmFjazogJ03hurd0IHNhdScsCiAgICAgICAgZGV0YWlsOiAnQ2hpIHRp4bq/dCcs
CiAgICAgICAgbGlmZXN0eWxlOiAnVHLDqm4gbmfGsOG7nWkgbeG6q3UnLAogICAgfTsKCiAgICBj
b25zdCBlc2NhcGVIdG1sID0gKHZhbHVlKSA9PiBTdHJpbmcodmFsdWUgfHwgJycpCiAgICAgICAg
LnJlcGxhY2VBbGwoJyYnLCAnJmFtcDsnKQogICAgICAgIC5yZXBsYWNlQWxsKCc8JywgJyZsdDsn
KQogICAgICAgIC5yZXBsYWNlQWxsKCc+JywgJyZndDsnKQogICAgICAgIC5yZXBsYWNlQWxsKCci
JywgJyZxdW90OycpCiAgICAgICAgLnJlcGxhY2VBbGwoIiciLCAnJiMwMzk7Jyk7CgogICAgY29u
c3QgYWN0aXZlQ29sb3IgPSAoKSA9PiB7CiAgICAgICAgY29uc3QgYnV0dG9uID0gcm9vdC5xdWVy
eVNlbGVjdG9yKCdbZGF0YS1seHBkcC1jb2xvcl0uaXMtYWN0aXZlJyk7CiAgICAgICAgY29uc3Qg
aWQgPSBTdHJpbmcoYnV0dG9uPy5kYXRhc2V0LmNvbG9ySWQgfHwgJycpOwogICAgICAgIGNvbnN0
IHJlcXVlc3RlZCA9IG5ldyBVUkwod2luZG93LmxvY2F0aW9uLmhyZWYpCiAgICAgICAgICAgIC5z
ZWFyY2hQYXJhbXMKICAgICAgICAgICAgLmdldCgnY29sb3InKTsKICAgICAgICBjb25zdCBub3Jt
YWxpemUgPSAodmFsdWUpID0+IFN0cmluZyh2YWx1ZSB8fCAnJykKICAgICAgICAgICAgLnRyaW0o
KQogICAgICAgICAgICAudG9Mb2NhbGVMb3dlckNhc2UoJ3ZpJyk7CgogICAgICAgIHJldHVybiBj
b2xvcnMuZmluZCgoY29sb3IpID0+IFN0cmluZyhjb2xvci5pZCkgPT09IGlkKQogICAgICAgICAg
ICB8fCBjb2xvcnMuZmluZCgoY29sb3IpID0+IHJlcXVlc3RlZCAmJiBbCiAgICAgICAgICAgICAg
ICBjb2xvci5pZCwKICAgICAgICAgICAgICAgIGNvbG9yLmNvZGUsCiAgICAgICAgICAgICAgICBj
b2xvci5rZXksCiAgICAgICAgICAgIF0ubWFwKG5vcm1hbGl6ZSkuaW5jbHVkZXMobm9ybWFsaXpl
KHJlcXVlc3RlZCkpKQogICAgICAgICAgICB8fCBjb2xvcnMuZmluZCgoY29sb3IpID0+IFN0cmlu
Zyhjb2xvci5pZCkgPT09IFN0cmluZyhwcm9kdWN0LmRlZmF1bHRfY29sb3JfaWQgfHwgJycpKQog
ICAgICAgICAgICB8fCBjb2xvcnMuZmluZCgoY29sb3IpID0+IGNvbG9yLnNlbGxhYmxlICYmIE51
bWJlcihjb2xvci5hdmFpbGFibGUgfHwgMCkgPiAwKQogICAgICAgICAgICB8fCBjb2xvcnNbMF0K
ICAgICAgICAgICAgfHwgbnVsbDsKICAgIH07CgogICAgY29uc3Qgcm9sZUxhYmVsID0gKHJvbGUp
ID0+IHJvbGVMYWJlbHNbU3RyaW5nKHJvbGUgfHwgJycpXSB8fCAnSMOsbmgg4bqjbmggc+G6o24g
cGjhuqltJzsKICAgIGNvbnN0IG1lZGlhVXJsID0gKGl0ZW0pID0+IFN0cmluZyhpdGVtPy51cmwg
fHwgaXRlbT8udGh1bWJfdXJsIHx8ICcnKTsKCiAgICBjb25zdCBub3JtYWxpemVTaXplQnV0dG9u
cyA9ICgpID0+IHsKICAgICAgICByb290LnF1ZXJ5U2VsZWN0b3JBbGwoJ1tkYXRhLWx4cGRwLXNp
emVdJykuZm9yRWFjaCgoYnV0dG9uKSA9PiB7CiAgICAgICAgICAgIGNvbnN0IGxhYmVsID0gU3Ry
aW5nKGJ1dHRvbi50ZXh0Q29udGVudCB8fCAnJykudHJpbSgpOwoKICAgICAgICAgICAgaWYgKGJ1
dHRvbi5kaXNhYmxlZCkgewogICAgICAgICAgICAgICAgYnV0dG9uLnNldEF0dHJpYnV0ZSgnYXJp
YS1sYWJlbCcsIGBTaXplICR7bGFiZWx9IOKAlCBo4bq/dCBow6BuZyDhu58gbcOgdSDEkWFuZyBj
aOG7jW5gKTsKICAgICAgICAgICAgICAgIGJ1dHRvbi50aXRsZSA9IGBTaXplICR7bGFiZWx9IOKA
lCBo4bq/dCBow6BuZ2A7CiAgICAgICAgICAgIH0gZWxzZSB7CiAgICAgICAgICAgICAgICBidXR0
b24uc2V0QXR0cmlidXRlKCdhcmlhLWxhYmVsJywgYENo4buNbiBzaXplICR7bGFiZWx9YCk7CiAg
ICAgICAgICAgICAgICBidXR0b24udGl0bGUgPSBgQ2jhu41uIHNpemUgJHtsYWJlbH1gOwogICAg
ICAgICAgICB9CiAgICAgICAgfSk7CiAgICB9OwoKICAgIGNvbnN0IHJlbmRlckNhbXBhaWduR3Jp
ZCA9IChjb2xvcikgPT4gewogICAgICAgIGNvbnN0IHBhbmVsID0gcm9vdC5xdWVyeVNlbGVjdG9y
KCdbZGF0YS1seHMtY2FtcGFpZ24tZ3JpZF0nKTsKCiAgICAgICAgaWYgKCFwYW5lbCkgewogICAg
ICAgICAgICByZXR1cm47CiAgICAgICAgfQoKICAgICAgICBjb25zdCBpdGVtcyA9IEFycmF5Lmlz
QXJyYXkoY29sb3I/Lm1lZGlhKQogICAgICAgICAgICA/IGNvbG9yLm1lZGlhLnNsaWNlKDAsIDYp
LmZpbHRlcigoaXRlbSkgPT4gbWVkaWFVcmwoaXRlbSkpCiAgICAgICAgICAgIDogW107CgogICAg
ICAgIGlmICghaXRlbXMubGVuZ3RoKSB7CiAgICAgICAgICAgIHBhbmVsLmhpZGRlbiA9IHRydWU7
CiAgICAgICAgICAgIHJldHVybjsKICAgICAgICB9CgogICAgICAgIGNvbnN0IGZyYWdtZW50ID0g
ZG9jdW1lbnQuY3JlYXRlRG9jdW1lbnRGcmFnbWVudCgpOwoKICAgICAgICBpdGVtcy5mb3JFYWNo
KChpdGVtLCBpbmRleCkgPT4gewogICAgICAgICAgICBjb25zdCBmaWd1cmUgPSBkb2N1bWVudC5j
cmVhdGVFbGVtZW50KCdmaWd1cmUnKTsKICAgICAgICAgICAgZmlndXJlLmNsYXNzTmFtZSA9IGBs
eHMtbWVkaWEtZ3JpZF9faXRlbSBseHMtbWVkaWEtZ3JpZF9faXRlbS0tJHsoaW5kZXggJSA1KSAr
IDF9YDsKCiAgICAgICAgICAgIGNvbnN0IGltYWdlID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgn
aW1nJyk7CiAgICAgICAgICAgIGltYWdlLnNyYyA9IG1lZGlhVXJsKGl0ZW0pOwogICAgICAgICAg
ICBpbWFnZS5hbHQgPSBgJHtwcm9kdWN0Lm5hbWUgfHwgJ1PhuqNuIHBo4bqpbSd9IOKAlCAke2Nv
bG9yPy5sYWJlbCB8fCAnJ30g4oCUICR7cm9sZUxhYmVsKGl0ZW0/LnJvbGUpfWA7CiAgICAgICAg
ICAgIGltYWdlLmxvYWRpbmcgPSAnbGF6eSc7CiAgICAgICAgICAgIGltYWdlLmRlY29kaW5nID0g
J2FzeW5jJzsKCiAgICAgICAgICAgIGNvbnN0IGNhcHRpb24gPSBkb2N1bWVudC5jcmVhdGVFbGVt
ZW50KCdmaWdjYXB0aW9uJyk7CiAgICAgICAgICAgIGNhcHRpb24udGV4dENvbnRlbnQgPSByb2xl
TGFiZWwoaXRlbT8ucm9sZSk7CgogICAgICAgICAgICBmaWd1cmUuYXBwZW5kKGltYWdlLCBjYXB0
aW9uKTsKICAgICAgICAgICAgZnJhZ21lbnQuYXBwZW5kKGZpZ3VyZSk7CiAgICAgICAgfSk7Cgog
ICAgICAgIHBhbmVsLnJlcGxhY2VDaGlsZHJlbihmcmFnbWVudCk7CiAgICAgICAgcGFuZWwuaGlk
ZGVuID0gZmFsc2U7CiAgICB9OwoKICAgIGNvbnN0IHVwZGF0ZURlc2lnblZpc3VhbCA9IChjb2xv
cikgPT4gewogICAgICAgIGNvbnN0IGltYWdlID0gcm9vdC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1s
eHMtZGVzaWduLWltYWdlXScpOwogICAgICAgIGNvbnN0IGl0ZW1zID0gQXJyYXkuaXNBcnJheShj
b2xvcj8ubWVkaWEpCiAgICAgICAgICAgID8gY29sb3IubWVkaWEuZmlsdGVyKChpdGVtKSA9PiBt
ZWRpYVVybChpdGVtKSkKICAgICAgICAgICAgOiBbXTsKICAgICAgICBjb25zdCBwcmVmZXJyZWQg
PSBpdGVtc1sxXSB8fCBpdGVtc1swXTsKCiAgICAgICAgaWYgKGltYWdlICYmIHByZWZlcnJlZCkg
ewogICAgICAgICAgICBpbWFnZS5zcmMgPSBtZWRpYVVybChwcmVmZXJyZWQpOwogICAgICAgICAg
ICBpbWFnZS5hbHQgPSBgJHtwcm9kdWN0Lm5hbWUgfHwgJ1PhuqNuIHBo4bqpbSd9IOKAlCAke2Nv
bG9yPy5sYWJlbCB8fCAnJ30g4oCUIGNoaSB0aeG6v3QgdGhp4bq/dCBr4bq/YDsKICAgICAgICB9
CiAgICB9OwoKICAgIGNvbnN0IGFwcGx5Q29sb3JBdG1vc3BoZXJlID0gKGNvbG9yKSA9PiB7CiAg
ICAgICAgY29uc3QgaGV4ID0gL14jWzAtOWEtZl17Myw4fSQvaS50ZXN0KFN0cmluZyhjb2xvcj8u
aGV4IHx8ICcnKSkKICAgICAgICAgICAgPyBTdHJpbmcoY29sb3IuaGV4KQogICAgICAgICAgICA6
ICcjNWI1ZmYyJzsKCiAgICAgICAgcm9vdC5zdHlsZS5zZXRQcm9wZXJ0eSgnLS1seHMtY3VycmVu
dC1jb2xvcicsIGhleCk7CiAgICAgICAgcmVuZGVyQ2FtcGFpZ25HcmlkKGNvbG9yKTsKICAgICAg
ICB1cGRhdGVEZXNpZ25WaXN1YWwoY29sb3IpOwoKICAgICAgICB3aW5kb3cucmVxdWVzdEFuaW1h
dGlvbkZyYW1lKG5vcm1hbGl6ZVNpemVCdXR0b25zKTsKICAgIH07CgogICAgcm9vdC5xdWVyeVNl
bGVjdG9yQWxsKCdbZGF0YS1seHBkcC1jb2xvcl0nKS5mb3JFYWNoKChidXR0b24pID0+IHsKICAg
ICAgICBidXR0b24uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKSA9PiB7CiAgICAgICAgICAg
IGNvbnN0IGNvbG9yID0gY29sb3JzLmZpbmQoCiAgICAgICAgICAgICAgICAoaXRlbSkgPT4gU3Ry
aW5nKGl0ZW0uaWQpID09PSBTdHJpbmcoYnV0dG9uLmRhdGFzZXQuY29sb3JJZCkKICAgICAgICAg
ICAgKTsKCiAgICAgICAgICAgIGlmIChjb2xvcikgewogICAgICAgICAgICAgICAgd2luZG93LnJl
cXVlc3RBbmltYXRpb25GcmFtZSgoKSA9PiBhcHBseUNvbG9yQXRtb3NwaGVyZShjb2xvcikpOwog
ICAgICAgICAgICB9CiAgICAgICAgfSk7CiAgICB9KTsKCiAgICBhcHBseUNvbG9yQXRtb3NwaGVy
ZShhY3RpdmVDb2xvcigpKTsKCiAgICAvKiBEZXNpZ24gaG90c3BvdHMgKi8KICAgIGNvbnN0IGFj
dGl2YXRlSG90c3BvdCA9IChpbmRleCkgPT4gewogICAgICAgIHJvb3QucXVlcnlTZWxlY3RvckFs
bCgnW2RhdGEtbHhzLWhvdHNwb3RdJykuZm9yRWFjaCgoYnV0dG9uKSA9PiB7CiAgICAgICAgICAg
IGNvbnN0IGFjdGl2ZSA9IE51bWJlcihidXR0b24uZGF0YXNldC5seHNIb3RzcG90KSA9PT0gaW5k
ZXg7CiAgICAgICAgICAgIGJ1dHRvbi5jbGFzc0xpc3QudG9nZ2xlKCdpcy1hY3RpdmUnLCBhY3Rp
dmUpOwogICAgICAgICAgICBidXR0b24uc2V0QXR0cmlidXRlKCdhcmlhLXByZXNzZWQnLCBhY3Rp
dmUgPyAndHJ1ZScgOiAnZmFsc2UnKTsKICAgICAgICB9KTsKCiAgICAgICAgcm9vdC5xdWVyeVNl
bGVjdG9yQWxsKCdbZGF0YS1seHMtaG90c3BvdC1jYXJkXScpLmZvckVhY2goKGNhcmQpID0+IHsK
ICAgICAgICAgICAgY2FyZC5jbGFzc0xpc3QudG9nZ2xlKAogICAgICAgICAgICAgICAgJ2lzLWFj
dGl2ZScsCiAgICAgICAgICAgICAgICBOdW1iZXIoY2FyZC5kYXRhc2V0Lmx4c0hvdHNwb3RDYXJk
KSA9PT0gaW5kZXgKICAgICAgICAgICAgKTsKICAgICAgICB9KTsKICAgIH07CgogICAgcm9vdC5x
dWVyeVNlbGVjdG9yQWxsKCdbZGF0YS1seHMtaG90c3BvdF0nKS5mb3JFYWNoKChidXR0b24pID0+
IHsKICAgICAgICBidXR0b24uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKSA9PiB7CiAgICAg
ICAgICAgIGFjdGl2YXRlSG90c3BvdChOdW1iZXIoYnV0dG9uLmRhdGFzZXQubHhzSG90c3BvdCB8
fCAwKSk7CiAgICAgICAgfSk7CiAgICB9KTsKCiAgICByb290LnF1ZXJ5U2VsZWN0b3JBbGwoJ1tk
YXRhLWx4cy1ob3RzcG90LWNhcmRdJykuZm9yRWFjaCgoY2FyZCkgPT4gewogICAgICAgIGNhcmQu
YWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKSA9PiB7CiAgICAgICAgICAgIGFjdGl2YXRlSG90
c3BvdChOdW1iZXIoY2FyZC5kYXRhc2V0Lmx4c0hvdHNwb3RDYXJkIHx8IDApKTsKICAgICAgICB9
KTsKICAgIH0pOwoKICAgIC8qIENhbXBhaWduIC8gcmVhbCBwcm9kdWN0IHRhYnMgKi8KICAgIHJv
b3QucXVlcnlTZWxlY3RvckFsbCgnW2RhdGEtbHhzLW1lZGlhLXRhYl0nKS5mb3JFYWNoKChidXR0
b24pID0+IHsKICAgICAgICBidXR0b24uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKSA9PiB7
CiAgICAgICAgICAgIGNvbnN0IGtleSA9IFN0cmluZyhidXR0b24uZGF0YXNldC5seHNNZWRpYVRh
YiB8fCAnJyk7CgogICAgICAgICAgICByb290LnF1ZXJ5U2VsZWN0b3JBbGwoJ1tkYXRhLWx4cy1t
ZWRpYS10YWJdJykuZm9yRWFjaCgodGFiKSA9PiB7CiAgICAgICAgICAgICAgICBjb25zdCBhY3Rp
dmUgPSB0YWIgPT09IGJ1dHRvbjsKICAgICAgICAgICAgICAgIHRhYi5jbGFzc0xpc3QudG9nZ2xl
KCdpcy1hY3RpdmUnLCBhY3RpdmUpOwogICAgICAgICAgICAgICAgdGFiLnNldEF0dHJpYnV0ZSgn
YXJpYS1zZWxlY3RlZCcsIGFjdGl2ZSA/ICd0cnVlJyA6ICdmYWxzZScpOwogICAgICAgICAgICB9
KTsKCiAgICAgICAgICAgIHJvb3QucXVlcnlTZWxlY3RvckFsbCgnW2RhdGEtbHhzLW1lZGlhLXBh
bmVsXScpLmZvckVhY2goKHBhbmVsKSA9PiB7CiAgICAgICAgICAgICAgICBjb25zdCBhY3RpdmUg
PSBTdHJpbmcocGFuZWwuZGF0YXNldC5seHNNZWRpYVBhbmVsIHx8ICcnKSA9PT0ga2V5OwogICAg
ICAgICAgICAgICAgcGFuZWwuY2xhc3NMaXN0LnRvZ2dsZSgnaXMtYWN0aXZlJywgYWN0aXZlKTsK
ICAgICAgICAgICAgICAgIHBhbmVsLmhpZGRlbiA9ICFhY3RpdmU7CiAgICAgICAgICAgIH0pOwog
ICAgICAgIH0pOwogICAgfSk7CgogICAgLyogU2l6ZSBTdHVkaW8gKi8KICAgIGNvbnN0IHNpemVE
YXRhTm9kZSA9IHJvb3QucXVlcnlTZWxlY3RvcignW2RhdGEtbHhzLXNpemUtY2hhcnQtZGF0YV0n
KTsKICAgIGxldCBzaXplQ2hhcnQgPSBudWxsOwoKICAgIGlmIChzaXplRGF0YU5vZGUpIHsKICAg
ICAgICB0cnkgewogICAgICAgICAgICBzaXplQ2hhcnQgPSBKU09OLnBhcnNlKHNpemVEYXRhTm9k
ZS50ZXh0Q29udGVudCB8fCAne30nKTsKICAgICAgICB9IGNhdGNoIChlcnJvcikgewogICAgICAg
ICAgICBjb25zb2xlLmVycm9yKCdLaMO0bmcgxJHhu41jIMSRxrDhu6NjIGThu68gbGnhu4d1IFNp
emUgU3R1ZGlvLicsIGVycm9yKTsKICAgICAgICB9CiAgICB9CgogICAgY29uc3Qgc2l6ZVBvaW50
ID0gKGtleSkgPT4gewogICAgICAgIGlmICghc2l6ZUNoYXJ0IHx8ICFBcnJheS5pc0FycmF5KHNp
emVDaGFydC5wb2ludHMpKSB7CiAgICAgICAgICAgIHJldHVybiBudWxsOwogICAgICAgIH0KCiAg
ICAgICAgY29uc3QgYWxpYXNlcyA9IHsKICAgICAgICAgICAgYnVzdDogWydidXN0JywgJ25n4bux
YyddLAogICAgICAgICAgICB3YWlzdDogWyd3YWlzdCcsICdlbyddLAogICAgICAgICAgICBoaXA6
IFsnaGlwJywgJ23DtG5nJywgJ2jDtG5nJ10sCiAgICAgICAgICAgIGxlbmd0aDogWydsZW5ndGgn
LCAnZMOgaSddLAogICAgICAgIH1ba2V5XSB8fCBba2V5XTsKCiAgICAgICAgcmV0dXJuIHNpemVD
aGFydC5wb2ludHMuZmluZCgocG9pbnQpID0+IHsKICAgICAgICAgICAgY29uc3QgYmxvYiA9IGAk
e3BvaW50Py5jb2RlIHx8ICcnfSAke3BvaW50Py5sYWJlbCB8fCAnJ31gLnRvTG9jYWxlTG93ZXJD
YXNlKCd2aScpOwogICAgICAgICAgICByZXR1cm4gYWxpYXNlcy5zb21lKChhbGlhcykgPT4gYmxv
Yi5pbmNsdWRlcyhhbGlhcykpOwogICAgICAgIH0pIHx8IG51bGw7CiAgICB9OwoKICAgIGNvbnN0
IGRpc3BsYXlQb2ludFZhbHVlID0gKHBvaW50LCBzaXplKSA9PiB7CiAgICAgICAgaWYgKCFwb2lu
dCkgewogICAgICAgICAgICByZXR1cm4gJ+KAlCc7CiAgICAgICAgfQoKICAgICAgICBjb25zdCBk
aXNwbGF5ID0gcG9pbnQuZGlzcGxheV92YWx1ZXM/LltzaXplXTsKICAgICAgICBjb25zdCByYXcg
PSBwb2ludC52YWx1ZXM/LltzaXplXTsKICAgICAgICByZXR1cm4gU3RyaW5nKGRpc3BsYXkgPz8g
cmF3ID8/ICfigJQnKTsKICAgIH07CgogICAgY29uc3Qgc2VsZWN0U3R1ZGlvU2l6ZSA9IChzaXpl
KSA9PiB7CiAgICAgICAgcm9vdC5xdWVyeVNlbGVjdG9yQWxsKCdbZGF0YS1seHMtc2l6ZS1jYXJk
XScpLmZvckVhY2goKGJ1dHRvbikgPT4gewogICAgICAgICAgICBjb25zdCBhY3RpdmUgPSBTdHJp
bmcoYnV0dG9uLmRhdGFzZXQubHhzU2l6ZUNhcmQgfHwgJycpID09PSBTdHJpbmcoc2l6ZSk7CiAg
ICAgICAgICAgIGJ1dHRvbi5jbGFzc0xpc3QudG9nZ2xlKCdpcy1hY3RpdmUnLCBhY3RpdmUpOwog
ICAgICAgICAgICBidXR0b24uc2V0QXR0cmlidXRlKCdhcmlhLXByZXNzZWQnLCBhY3RpdmUgPyAn
dHJ1ZScgOiAnZmFsc2UnKTsKICAgICAgICB9KTsKCiAgICAgICAgY29uc3QgYWN0aXZlU2l6ZSA9
IHJvb3QucXVlcnlTZWxlY3RvcignW2RhdGEtbHhzLWFjdGl2ZS1zaXplXScpOwogICAgICAgIGlm
IChhY3RpdmVTaXplKSB7CiAgICAgICAgICAgIGFjdGl2ZVNpemUudGV4dENvbnRlbnQgPSBTdHJp
bmcoc2l6ZSk7CiAgICAgICAgfQoKICAgICAgICByb290LnF1ZXJ5U2VsZWN0b3JBbGwoJ1tkYXRh
LWx4cy1tZWFzdXJlLXZhbHVlXScpLmZvckVhY2goKG5vZGUpID0+IHsKICAgICAgICAgICAgY29u
c3Qga2V5ID0gU3RyaW5nKG5vZGUuZGF0YXNldC5seHNNZWFzdXJlVmFsdWUgfHwgJycpOwogICAg
ICAgICAgICBub2RlLnRleHRDb250ZW50ID0gZGlzcGxheVBvaW50VmFsdWUoc2l6ZVBvaW50KGtl
eSksIHNpemUpOwogICAgICAgIH0pOwogICAgfTsKCiAgICByb290LnF1ZXJ5U2VsZWN0b3JBbGwo
J1tkYXRhLWx4cy1zaXplLWNhcmRdJykuZm9yRWFjaCgoYnV0dG9uKSA9PiB7CiAgICAgICAgYnV0
dG9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKCkgPT4gewogICAgICAgICAgICBzZWxlY3RT
dHVkaW9TaXplKFN0cmluZyhidXR0b24uZGF0YXNldC5seHNTaXplQ2FyZCB8fCAnJykpOwogICAg
ICAgIH0pOwogICAgfSk7CgogICAgY29uc3QgYWN0aXZhdGVNZWFzdXJlID0gKGtleSkgPT4gewog
ICAgICAgIHJvb3QucXVlcnlTZWxlY3RvckFsbCgnW2RhdGEtbHhzLW1lYXN1cmUtcm93XScpLmZv
ckVhY2goKGJ1dHRvbikgPT4gewogICAgICAgICAgICBidXR0b24uY2xhc3NMaXN0LnRvZ2dsZSgK
ICAgICAgICAgICAgICAgICdpcy1hY3RpdmUnLAogICAgICAgICAgICAgICAgU3RyaW5nKGJ1dHRv
bi5kYXRhc2V0Lmx4c01lYXN1cmVSb3cgfHwgJycpID09PSBrZXkKICAgICAgICAgICAgKTsKICAg
ICAgICB9KTsKCiAgICAgICAgcm9vdC5xdWVyeVNlbGVjdG9yQWxsKCdbZGF0YS1seHMtZGlhZ3Jh
bS1tZWFzdXJlXScpLmZvckVhY2goKGdyb3VwKSA9PiB7CiAgICAgICAgICAgIGdyb3VwLmNsYXNz
TGlzdC50b2dnbGUoCiAgICAgICAgICAgICAgICAnaXMtYWN0aXZlJywKICAgICAgICAgICAgICAg
IFN0cmluZyhncm91cC5kYXRhc2V0Lmx4c0RpYWdyYW1NZWFzdXJlIHx8ICcnKSA9PT0ga2V5CiAg
ICAgICAgICAgICk7CiAgICAgICAgfSk7CiAgICB9OwoKICAgIHJvb3QucXVlcnlTZWxlY3RvckFs
bCgnW2RhdGEtbHhzLW1lYXN1cmUtcm93XScpLmZvckVhY2goKGJ1dHRvbikgPT4gewogICAgICAg
IGJ1dHRvbi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsICgpID0+IHsKICAgICAgICAgICAgYWN0
aXZhdGVNZWFzdXJlKFN0cmluZyhidXR0b24uZGF0YXNldC5seHNNZWFzdXJlUm93IHx8ICcnKSk7
CiAgICAgICAgfSk7CiAgICB9KTsKCiAgICBjb25zdCBzaXplRGlhbG9nID0gcm9vdC5xdWVyeVNl
bGVjdG9yKCdbZGF0YS1seHMtc2l6ZS10YWJsZS1kaWFsb2ddJyk7CiAgICByb290LnF1ZXJ5U2Vs
ZWN0b3IoJ1tkYXRhLWx4cy1zaXplLXRhYmxlLW9wZW5dJyk/LmFkZEV2ZW50TGlzdGVuZXIoJ2Ns
aWNrJywgKCkgPT4gewogICAgICAgIGlmIChzaXplRGlhbG9nPy5zaG93TW9kYWwpIHsKICAgICAg
ICAgICAgc2l6ZURpYWxvZy5zaG93TW9kYWwoKTsKICAgICAgICB9IGVsc2UgewogICAgICAgICAg
ICBzaXplRGlhbG9nPy5zZXRBdHRyaWJ1dGUoJ29wZW4nLCAnJyk7CiAgICAgICAgfQogICAgfSk7
CgogICAgLyogTW9iaWxlIGNvbW1lcmNlIGRvY2sgKi8KICAgIGNvbnN0IGJ1eUJ1dHRvbiA9IHJv
b3QucXVlcnlTZWxlY3RvcignW2RhdGEtbHhwZHAtYnV5XScpOwogICAgY29uc3QgY2FydEZvcm0g
PSByb290LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWx4cGRwLWNhcnQtZm9ybV0nKTsKICAgIGNvbnN0
IGRvY2tCdXR0b24gPSByb290LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWx4cy1kb2NrLXN1Ym1pdF0n
KTsKICAgIGNvbnN0IGRvY2tMYWJlbCA9IHJvb3QucXVlcnlTZWxlY3RvcignW2RhdGEtbHhzLWRv
Y2stbGFiZWxdJyk7CiAgICBjb25zdCBzZWxlY3RvciA9IHJvb3QucXVlcnlTZWxlY3RvcignLmx4
cy1zZWxlY3Rvci0tc2l6ZScpOwoKICAgIGNvbnN0IHN5bmNEb2NrID0gKCkgPT4gewogICAgICAg
IGlmICghZG9ja0J1dHRvbiB8fCAhZG9ja0xhYmVsKSB7CiAgICAgICAgICAgIHJldHVybjsKICAg
ICAgICB9CgogICAgICAgIGNvbnN0IHByb2R1Y3RJblN0b2NrID0gQm9vbGVhbihwcm9kdWN0Lmlu
X3N0b2NrKTsKICAgICAgICBjb25zdCByZWFkeSA9IGJ1eUJ1dHRvbiAmJiAhYnV5QnV0dG9uLmRp
c2FibGVkOwogICAgICAgIGNvbnN0IGJ1eVRleHQgPSBTdHJpbmcoYnV5QnV0dG9uPy50ZXh0Q29u
dGVudCB8fCAnJykudHJpbSgpOwoKICAgICAgICBpZiAocmVhZHkpIHsKICAgICAgICAgICAgZG9j
a0J1dHRvbi5kaXNhYmxlZCA9IGZhbHNlOwogICAgICAgICAgICBkb2NrQnV0dG9uLmRhdGFzZXQu
bW9kZSA9ICdzdWJtaXQnOwogICAgICAgICAgICBkb2NrTGFiZWwudGV4dENvbnRlbnQgPSAnVGjD
qm0gdsOgbyBnaeG7jyc7CiAgICAgICAgICAgIHJldHVybjsKICAgICAgICB9CgogICAgICAgIGlm
ICghcHJvZHVjdEluU3RvY2sgfHwgL2jhur90IGjDoG5nL2kudGVzdChidXlUZXh0KSkgewogICAg
ICAgICAgICBkb2NrQnV0dG9uLmRpc2FibGVkID0gdHJ1ZTsKICAgICAgICAgICAgZG9ja0J1dHRv
bi5kYXRhc2V0Lm1vZGUgPSAnc29sZG91dCc7CiAgICAgICAgICAgIGRvY2tMYWJlbC50ZXh0Q29u
dGVudCA9ICdU4bqhbSBo4bq/dCBow6BuZyc7CiAgICAgICAgICAgIHJldHVybjsKICAgICAgICB9
CgogICAgICAgIGRvY2tCdXR0b24uZGlzYWJsZWQgPSBmYWxzZTsKICAgICAgICBkb2NrQnV0dG9u
LmRhdGFzZXQubW9kZSA9ICdndWlkZSc7CiAgICAgICAgZG9ja0xhYmVsLnRleHRDb250ZW50ID0g
L2vDrWNoIHRoxrDhu5tjfHNpemUvaS50ZXN0KGJ1eVRleHQpCiAgICAgICAgICAgID8gJ0No4buN
biBzaXplJwogICAgICAgICAgICA6ICdDaOG7jW4gbcOgdSAmIHNpemUnOwogICAgfTsKCiAgICBk
b2NrQnV0dG9uPy5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsICgpID0+IHsKICAgICAgICBpZiAo
ZG9ja0J1dHRvbi5kYXRhc2V0Lm1vZGUgPT09ICdzdWJtaXQnICYmIGJ1eUJ1dHRvbiAmJiAhYnV5
QnV0dG9uLmRpc2FibGVkKSB7CiAgICAgICAgICAgIGNhcnRGb3JtPy5yZXF1ZXN0U3VibWl0KCk7
CiAgICAgICAgICAgIHJldHVybjsKICAgICAgICB9CgogICAgICAgIHNlbGVjdG9yPy5zY3JvbGxJ
bnRvVmlldyh7CiAgICAgICAgICAgIGJlaGF2aW9yOiByZWR1Y2VkTW90aW9uID8gJ2F1dG8nIDog
J3Ntb290aCcsCiAgICAgICAgICAgIGJsb2NrOiAnY2VudGVyJywKICAgICAgICB9KTsKICAgICAg
ICBzZWxlY3Rvcj8uYW5pbWF0ZSgKICAgICAgICAgICAgWwogICAgICAgICAgICAgICAgeyBib3hT
aGFkb3c6ICcwIDAgMCAwIHJnYmEoOTEsOTUsMjQyLDApJyB9LAogICAgICAgICAgICAgICAgeyBi
b3hTaGFkb3c6ICcwIDAgMCA4cHggcmdiYSg5MSw5NSwyNDIsLjE1KScgfSwKICAgICAgICAgICAg
ICAgIHsgYm94U2hhZG93OiAnMCAwIDAgMCByZ2JhKDkxLDk1LDI0MiwwKScgfSwKICAgICAgICAg
ICAgXSwKICAgICAgICAgICAgeyBkdXJhdGlvbjogODUwLCBlYXNpbmc6ICdlYXNlLW91dCcgfQog
ICAgICAgICk7CiAgICB9KTsKCiAgICBjb25zdCBzaXplTGlzdCA9IHJvb3QucXVlcnlTZWxlY3Rv
cignW2RhdGEtbHhwZHAtc2l6ZXNdJyk7CiAgICBpZiAoc2l6ZUxpc3QgJiYgJ011dGF0aW9uT2Jz
ZXJ2ZXInIGluIHdpbmRvdykgewogICAgICAgIG5ldyBNdXRhdGlvbk9ic2VydmVyKCgpID0+IHsK
ICAgICAgICAgICAgbm9ybWFsaXplU2l6ZUJ1dHRvbnMoKTsKICAgICAgICAgICAgc3luY0RvY2so
KTsKICAgICAgICB9KS5vYnNlcnZlKHNpemVMaXN0LCB7CiAgICAgICAgICAgIGNoaWxkTGlzdDog
dHJ1ZSwKICAgICAgICAgICAgc3VidHJlZTogdHJ1ZSwKICAgICAgICAgICAgYXR0cmlidXRlczog
dHJ1ZSwKICAgICAgICAgICAgYXR0cmlidXRlRmlsdGVyOiBbJ2Rpc2FibGVkJywgJ2NsYXNzJ10s
CiAgICAgICAgfSk7CiAgICB9CgogICAgaWYgKGJ1eUJ1dHRvbiAmJiAnTXV0YXRpb25PYnNlcnZl
cicgaW4gd2luZG93KSB7CiAgICAgICAgbmV3IE11dGF0aW9uT2JzZXJ2ZXIoc3luY0RvY2spLm9i
c2VydmUoYnV5QnV0dG9uLCB7CiAgICAgICAgICAgIGNoaWxkTGlzdDogdHJ1ZSwKICAgICAgICAg
ICAgc3VidHJlZTogdHJ1ZSwKICAgICAgICAgICAgYXR0cmlidXRlczogdHJ1ZSwKICAgICAgICAg
ICAgYXR0cmlidXRlRmlsdGVyOiBbJ2Rpc2FibGVkJ10sCiAgICAgICAgfSk7CiAgICB9CgogICAg
cm9vdC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIChldmVudCkgPT4gewogICAgICAgIGlmIChl
dmVudC50YXJnZXQuY2xvc2VzdCgnW2RhdGEtbHhwZHAtY29sb3JdLCBbZGF0YS1seHBkcC1zaXpl
XScpKSB7CiAgICAgICAgICAgIHdpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUoKCkgPT4gewog
ICAgICAgICAgICAgICAgbm9ybWFsaXplU2l6ZUJ1dHRvbnMoKTsKICAgICAgICAgICAgICAgIHN5
bmNEb2NrKCk7CiAgICAgICAgICAgIH0pOwogICAgICAgIH0KICAgIH0pOwoKICAgIG5vcm1hbGl6
ZVNpemVCdXR0b25zKCk7CiAgICBzeW5jRG9jaygpOwoKICAgIC8qIFNjcm9sbCB0byBwdXJjaGFz
ZSAqLwogICAgcm9vdC5xdWVyeVNlbGVjdG9yQWxsKCdbZGF0YS1wZHAtc2Nyb2xsLXRvLXB1cmNo
YXNlXScpLmZvckVhY2goKGJ1dHRvbikgPT4gewogICAgICAgIGJ1dHRvbi5hZGRFdmVudExpc3Rl
bmVyKCdjbGljaycsICgpID0+IHsKICAgICAgICAgICAgcm9vdC5xdWVyeVNlbGVjdG9yKCdbZGF0
YS1seHMtcHVyY2hhc2VdJyk/LnNjcm9sbEludG9WaWV3KHsKICAgICAgICAgICAgICAgIGJlaGF2
aW9yOiByZWR1Y2VkTW90aW9uID8gJ2F1dG8nIDogJ3Ntb290aCcsCiAgICAgICAgICAgICAgICBi
bG9jazogJ2NlbnRlcicsCiAgICAgICAgICAgIH0pOwogICAgICAgIH0pOwogICAgfSk7CgogICAg
LyogUmVjZW50bHkgdmlld2VkICovCiAgICBjb25zdCBoaXN0b3J5S2V5ID0gJ2xpbnhlbl9wZHBf
cmVjZW50bHlfdmlld2VkX3YxJzsKICAgIGNvbnN0IGN1cnJlbnQgPSB7CiAgICAgICAgaWQ6IFN0
cmluZyhwcm9kdWN0LmlkIHx8ICcnKSwKICAgICAgICBuYW1lOiBTdHJpbmcocHJvZHVjdC5uYW1l
IHx8ICcnKSwKICAgICAgICB1cmw6IHdpbmRvdy5sb2NhdGlvbi5wYXRobmFtZSwKICAgICAgICBj
b3Zlcl91cmw6IFN0cmluZyhwcm9kdWN0LmNvdmVyX3VybCB8fCBwcm9kdWN0LmNvbG9ycz8uWzBd
Py5tZWRpYT8uWzBdPy51cmwgfHwgJycpLAogICAgICAgIHByaWNlX21pbjogTnVtYmVyKHByb2R1
Y3QucHJpY2VfbWluIHx8IDApLAogICAgICAgIHZpZXdlZF9hdDogbmV3IERhdGUoKS50b0lTT1N0
cmluZygpLAogICAgfTsKICAgIGxldCBoaXN0b3J5ID0gW107CgogICAgdHJ5IHsKICAgICAgICBo
aXN0b3J5ID0gSlNPTi5wYXJzZShsb2NhbFN0b3JhZ2UuZ2V0SXRlbShoaXN0b3J5S2V5KSB8fCAn
W10nKTsKICAgICAgICBoaXN0b3J5ID0gQXJyYXkuaXNBcnJheShoaXN0b3J5KSA/IGhpc3Rvcnkg
OiBbXTsKICAgIH0gY2F0Y2ggewogICAgICAgIGhpc3RvcnkgPSBbXTsKICAgIH0KCiAgICBjb25z
dCBwcmV2aW91cyA9IGhpc3RvcnkKICAgICAgICAuZmlsdGVyKChpdGVtKSA9PiBTdHJpbmcoaXRl
bS5pZCB8fCAnJykgIT09IGN1cnJlbnQuaWQpCiAgICAgICAgLnNsaWNlKDAsIDgpOwoKICAgIGlm
IChjdXJyZW50LmlkICYmIGN1cnJlbnQubmFtZSkgewogICAgICAgIHRyeSB7CiAgICAgICAgICAg
IGxvY2FsU3RvcmFnZS5zZXRJdGVtKAogICAgICAgICAgICAgICAgaGlzdG9yeUtleSwKICAgICAg
ICAgICAgICAgIEpTT04uc3RyaW5naWZ5KFtjdXJyZW50LCAuLi5wcmV2aW91c10uc2xpY2UoMCwg
OCkpCiAgICAgICAgICAgICk7CiAgICAgICAgfSBjYXRjaCB7CiAgICAgICAgICAgIC8vIFByaXZh
dGUgYnJvd3NpbmcgbWF5IGJsb2NrIHN0b3JhZ2UuIFRoZSBQRFAgcmVtYWlucyBmdW5jdGlvbmFs
LgogICAgICAgIH0KICAgIH0KCiAgICBjb25zdCByZWNlbnRMaXN0ID0gcm9vdC5xdWVyeVNlbGVj
dG9yKCdbZGF0YS1seHMtcmVjZW50LWxpc3RdJyk7CiAgICBjb25zdCByZWNlbnRFbXB0eSA9IHJv
b3QucXVlcnlTZWxlY3RvcignW2RhdGEtbHhzLXJlY2VudC1lbXB0eV0nKTsKCiAgICBpZiAocmVj
ZW50TGlzdCAmJiBwcmV2aW91cy5sZW5ndGgpIHsKICAgICAgICByZWNlbnRFbXB0eT8ucmVtb3Zl
KCk7CiAgICAgICAgcmVjZW50TGlzdC5pbm5lckhUTUwgPSBwcmV2aW91cy5zbGljZSgwLCA0KS5t
YXAoKGl0ZW0pID0+IHsKICAgICAgICAgICAgY29uc3QgcHJpY2UgPSBOdW1iZXIoaXRlbS5wcmlj
ZV9taW4gfHwgMCkudG9Mb2NhbGVTdHJpbmcoJ3ZpLVZOJyk7CiAgICAgICAgICAgIGNvbnN0IGlt
YWdlID0gaXRlbS5jb3Zlcl91cmwKICAgICAgICAgICAgICAgID8gYDxzcGFuPjxpbWcgc3JjPSIk
e2VzY2FwZUh0bWwoaXRlbS5jb3Zlcl91cmwpfSIgYWx0PSIke2VzY2FwZUh0bWwoaXRlbS5uYW1l
KX0iIGxvYWRpbmc9ImxhenkiPjwvc3Bhbj5gCiAgICAgICAgICAgICAgICA6ICc8c3Bhbj48L3Nw
YW4+JzsKCiAgICAgICAgICAgIHJldHVybiBgPGEgY2xhc3M9Imx4cy1wcm9kdWN0LWNhcmQiIGhy
ZWY9IiR7ZXNjYXBlSHRtbChpdGVtLnVybCl9Ij4ke2ltYWdlfTxzdHJvbmc+JHtlc2NhcGVIdG1s
KGl0ZW0ubmFtZSl9PC9zdHJvbmc+PHNtYWxsPiR7cHJpY2UgPyBgJHtwcmljZX3igqtgIDogJyd9
PC9zbWFsbD48L2E+YDsKICAgICAgICB9KS5qb2luKCcnKTsKICAgIH0KCiAgICAvKiBWaWV3cG9y
dCByZXZlYWwgKi8KICAgIGNvbnN0IHJldmVhbEl0ZW1zID0gQXJyYXkuZnJvbShyb290LnF1ZXJ5
U2VsZWN0b3JBbGwoJ1tkYXRhLWx4cy1yZXZlYWxdJykpOwoKICAgIGlmICghKCdJbnRlcnNlY3Rp
b25PYnNlcnZlcicgaW4gd2luZG93KSB8fCByZWR1Y2VkTW90aW9uKSB7CiAgICAgICAgcmV2ZWFs
SXRlbXMuZm9yRWFjaCgoaXRlbSkgPT4gaXRlbS5jbGFzc0xpc3QuYWRkKCdpcy12aXNpYmxlJykp
OwogICAgfSBlbHNlIHsKICAgICAgICBjb25zdCBvYnNlcnZlciA9IG5ldyBJbnRlcnNlY3Rpb25P
YnNlcnZlcigoZW50cmllcywgaW5zdGFuY2UpID0+IHsKICAgICAgICAgICAgZW50cmllcy5mb3JF
YWNoKChlbnRyeSkgPT4gewogICAgICAgICAgICAgICAgaWYgKCFlbnRyeS5pc0ludGVyc2VjdGlu
ZykgewogICAgICAgICAgICAgICAgICAgIHJldHVybjsKICAgICAgICAgICAgICAgIH0KCiAgICAg
ICAgICAgICAgICBlbnRyeS50YXJnZXQuY2xhc3NMaXN0LmFkZCgnaXMtdmlzaWJsZScpOwogICAg
ICAgICAgICAgICAgaW5zdGFuY2UudW5vYnNlcnZlKGVudHJ5LnRhcmdldCk7CiAgICAgICAgICAg
IH0pOwogICAgICAgIH0sIHsKICAgICAgICAgICAgdGhyZXNob2xkOiAuMSwKICAgICAgICAgICAg
cm9vdE1hcmdpbjogJzBweCAwcHggLTUlIDBweCcsCiAgICAgICAgfSk7CgogICAgICAgIHJldmVh
bEl0ZW1zLmZvckVhY2goKGl0ZW0pID0+IG9ic2VydmVyLm9ic2VydmUoaXRlbSkpOwogICAgfQoK
ICAgIGlmICgnSW50ZXJzZWN0aW9uT2JzZXJ2ZXInIGluIHdpbmRvdykgewogICAgICAgIGNvbnN0
IHNlY3Rpb25PYnNlcnZlciA9IG5ldyBJbnRlcnNlY3Rpb25PYnNlcnZlcigoZW50cmllcykgPT4g
ewogICAgICAgICAgICBlbnRyaWVzLmZvckVhY2goKGVudHJ5KSA9PiB7CiAgICAgICAgICAgICAg
ICBpZiAoIWVudHJ5LmlzSW50ZXJzZWN0aW5nIHx8IGVudHJ5LnRhcmdldC5kYXRhc2V0Lmx4c1Nl
ZW4gPT09ICcxJykgewogICAgICAgICAgICAgICAgICAgIHJldHVybjsKICAgICAgICAgICAgICAg
IH0KCiAgICAgICAgICAgICAgICBlbnRyeS50YXJnZXQuZGF0YXNldC5seHNTZWVuID0gJzEnOwog
ICAgICAgICAgICAgICAgcm9vdC5kaXNwYXRjaEV2ZW50KG5ldyBDdXN0b21FdmVudCgnbGlueGVu
OnBkcDpzZWN0aW9uLXZpZXdlZCcsIHsKICAgICAgICAgICAgICAgICAgICBidWJibGVzOiB0cnVl
LAogICAgICAgICAgICAgICAgICAgIGRldGFpbDogewogICAgICAgICAgICAgICAgICAgICAgICB2
YXJpYW50OiAnc3R1ZGlvX3NpZ25hbF92MScsCiAgICAgICAgICAgICAgICAgICAgICAgIHNlY3Rp
b246IGVudHJ5LnRhcmdldC5kYXRhc2V0LnBkcFNlY3Rpb24gfHwgbnVsbCwKICAgICAgICAgICAg
ICAgICAgICAgICAgcHJvZHVjdF9pZDogcHJvZHVjdC5pZCB8fCBudWxsLAogICAgICAgICAgICAg
ICAgICAgIH0sCiAgICAgICAgICAgICAgICB9KSk7CiAgICAgICAgICAgIH0pOwogICAgICAgIH0s
IHsgdGhyZXNob2xkOiAuMjggfSk7CgogICAgICAgIHJvb3QucXVlcnlTZWxlY3RvckFsbCgnW2Rh
dGEtcGRwLXNlY3Rpb25dJykuZm9yRWFjaCgoc2VjdGlvbikgPT4gewogICAgICAgICAgICBzZWN0
aW9uT2JzZXJ2ZXIub2JzZXJ2ZShzZWN0aW9uKTsKICAgICAgICB9KTsKICAgIH0KCiAgICByb290
LmRpc3BhdGNoRXZlbnQobmV3IEN1c3RvbUV2ZW50KCdsaW54ZW46cGRwOnN0dWRpby1zaWduYWwt
cmVhZHknLCB7CiAgICAgICAgYnViYmxlczogdHJ1ZSwKICAgICAgICBkZXRhaWw6IHsKICAgICAg
ICAgICAgdmFyaWFudDogJ3N0dWRpb19zaWduYWxfdjEnLAogICAgICAgICAgICBwcm9kdWN0X2lk
OiBwcm9kdWN0LmlkIHx8IG51bGwsCiAgICAgICAgfSwKICAgIH0pKTsKfQo=
STUDIO_SIGNAL_PAYLOAD_2

decode_to_file 'resources/views/commerce_v2/pdp/studio/hero-purchase.blade.php' <<'STUDIO_SIGNAL_PAYLOAD_3'
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
ICAgICAgIDogbnVsbDsKQGVuZHBocAoKPGRpdiBjbGFzcz0ibHhzLXNoZWxsIGx4cy1oZXJvIiBk
YXRhLWx4cy1yZXZlYWw+CiAgICA8ZGl2IGNsYXNzPSJseHMtaGVyb19fZ2FsbGVyeS1jb2x1bW4i
PgogICAgICAgIDxkaXYgY2xhc3M9Imx4cy1oZXJvX190b3BsaW5lIiBhcmlhLWhpZGRlbj0idHJ1
ZSI+CiAgICAgICAgICAgIDxzcGFuPkxJTiBYw4lOIC8gU1RVRElPIFNJR05BTDwvc3Bhbj4KICAg
ICAgICAgICAgPHNwYW4+e3sgbm93KCktPmZvcm1hdCgnWScpIH19PC9zcGFuPgogICAgICAgIDwv
ZGl2PgoKICAgICAgICA8ZGl2IGNsYXNzPSJseHBkcC1nYWxsZXJ5IGx4cy1nYWxsZXJ5IiBkYXRh
LWx4cGRwLWdhbGxlcnkgYXJpYS1sYWJlbD0iSMOsbmgg4bqjbmggc+G6o24gcGjhuqltIj4KICAg
ICAgICAgICAgPGRpdiBjbGFzcz0ibHhwZHAtZ2FsbGVyeV9fc3RhZ2UgbHhzLWdhbGxlcnlfX3N0
YWdlIj4KICAgICAgICAgICAgICAgIDxidXR0b24KICAgICAgICAgICAgICAgICAgICB0eXBlPSJi
dXR0b24iCiAgICAgICAgICAgICAgICAgICAgY2xhc3M9Imx4cGRwLWdhbGxlcnlfX25hdiBseHBk
cC1nYWxsZXJ5X19uYXYtLXByZXYgbHhzLWdhbGxlcnlfX25hdiBseHMtZ2FsbGVyeV9fbmF2LS1w
cmV2IgogICAgICAgICAgICAgICAgICAgIGRhdGEtbHhwZHAtZ2FsbGVyeS1wcmV2CiAgICAgICAg
ICAgICAgICAgICAgYXJpYS1sYWJlbD0i4bqibmggdHLGsOG7m2MiCiAgICAgICAgICAgICAgICA+
CiAgICAgICAgICAgICAgICAgICAgPHN2ZyB2aWV3Qm94PSIwIDAgMjQgMjQiIGFyaWEtaGlkZGVu
PSJ0cnVlIj48cGF0aCBkPSJtMTUgMTgtNi02IDYtNiIvPjwvc3ZnPgogICAgICAgICAgICAgICAg
PC9idXR0b24+CgogICAgICAgICAgICAgICAgPGZpZ3VyZSBjbGFzcz0ibHhwZHAtZ2FsbGVyeV9f
ZmlndXJlIGx4cy1nYWxsZXJ5X19maWd1cmUiPgogICAgICAgICAgICAgICAgICAgIDxpbWcKICAg
ICAgICAgICAgICAgICAgICAgICAgZGF0YS1seHBkcC1tYWluLWltYWdlCiAgICAgICAgICAgICAg
ICAgICAgICAgIHNyYz0ie3sgZGF0YV9nZXQoJGhlcm9NZWRpYSwgJ3VybCcsIGRhdGFfZ2V0KCRw
ZHAsICdtZWRpYS5jb3Zlcl91cmwnKSkgfX0iCiAgICAgICAgICAgICAgICAgICAgICAgIGFsdD0i
e3sgJGZ1bGxOYW1lIH19IC0ge3sgZGF0YV9nZXQoJGRlZmF1bHRDb2xvciwgJ2xhYmVsJykgfX0i
CiAgICAgICAgICAgICAgICAgICAgICAgIHdpZHRoPSIxMTIwIgogICAgICAgICAgICAgICAgICAg
ICAgICBoZWlnaHQ9IjE0MDAiCiAgICAgICAgICAgICAgICAgICAgICAgIGZldGNocHJpb3JpdHk9
ImhpZ2giCiAgICAgICAgICAgICAgICAgICAgICAgIGRlY29kaW5nPSJhc3luYyIKICAgICAgICAg
ICAgICAgICAgICA+CiAgICAgICAgICAgICAgICAgICAgPGZpZ2NhcHRpb24gY2xhc3M9Imx4cy1n
YWxsZXJ5X19tZXRhIj4KICAgICAgICAgICAgICAgICAgICAgICAgPHNwYW4gZGF0YS1seHBkcC1p
bWFnZS1yb2xlPnt7IGRhdGFfZ2V0KCRoZXJvTWVkaWEsICdyb2xlJykgPT09ICdoZXJvJyA/ICdU
4buVbmcgdGjhu4MnIDogJ0jDrG5oIOG6o25oIHPhuqNuIHBo4bqpbScgfX08L3NwYW4+CiAgICAg
ICAgICAgICAgICAgICAgICAgIDxzcGFuIGRhdGEtbHhwZHAtaW1hZ2UtY291bnRlcj57eyAkZGVm
YXVsdE1lZGlhLT5pc05vdEVtcHR5KCkgPyAnMDEgLyAnLnN0cl9wYWQoKHN0cmluZykgJGRlZmF1
bHRNZWRpYS0+Y291bnQoKSwgMiwgJzAnLCBTVFJfUEFEX0xFRlQpIDogJycgfX08L3NwYW4+CiAg
ICAgICAgICAgICAgICAgICAgPC9maWdjYXB0aW9uPgogICAgICAgICAgICAgICAgPC9maWd1cmU+
CgogICAgICAgICAgICAgICAgPGJ1dHRvbgogICAgICAgICAgICAgICAgICAgIHR5cGU9ImJ1dHRv
biIKICAgICAgICAgICAgICAgICAgICBjbGFzcz0ibHhwZHAtZ2FsbGVyeV9fbmF2IGx4cGRwLWdh
bGxlcnlfX25hdi0tbmV4dCBseHMtZ2FsbGVyeV9fbmF2IGx4cy1nYWxsZXJ5X19uYXYtLW5leHQi
CiAgICAgICAgICAgICAgICAgICAgZGF0YS1seHBkcC1nYWxsZXJ5LW5leHQKICAgICAgICAgICAg
ICAgICAgICBhcmlhLWxhYmVsPSLhuqJuaCB0aeG6v3AgdGhlbyIKICAgICAgICAgICAgICAgID4K
ICAgICAgICAgICAgICAgICAgICA8c3ZnIHZpZXdCb3g9IjAgMCAyNCAyNCIgYXJpYS1oaWRkZW49
InRydWUiPjxwYXRoIGQ9Im05IDE4IDYtNi02LTYiLz48L3N2Zz4KICAgICAgICAgICAgICAgIDwv
YnV0dG9uPgogICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4cGRw
LWdhbGxlcnlfX3RodW1icyBseHMtZ2FsbGVyeV9fdGh1bWJzIiBkYXRhLWx4cGRwLXRodW1icyBy
b2xlPSJsaXN0IiBhcmlhLWxhYmVsPSJDaOG7jW4g4bqjbmggc+G6o24gcGjhuqltIj4KICAgICAg
ICAgICAgICAgIEBmb3JlYWNoKCRkZWZhdWx0TWVkaWEgYXMgJGluZGV4ID0+ICRtZWRpYSkKICAg
ICAgICAgICAgICAgICAgICA8YnV0dG9uCiAgICAgICAgICAgICAgICAgICAgICAgIHR5cGU9ImJ1
dHRvbiIKICAgICAgICAgICAgICAgICAgICAgICAgY2xhc3M9Imx4cGRwLWdhbGxlcnlfX3RodW1i
IGx4cy1nYWxsZXJ5X190aHVtYiB7eyAkaW5kZXggPT09IDAgPyAnaXMtYWN0aXZlJyA6ICcnIH19
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
PgoKICAgICAgICAgICAgPHAgY2xhc3M9Imx4cGRwLWdhbGxlcnlfX25vdGljZSBseHMtZ2FsbGVy
eV9fbm90aWNlIiBkYXRhLWx4cGRwLWdhbGxlcnktbm90aWNlIEBpZigkZGVmYXVsdE1lZGlhLT5p
c05vdEVtcHR5KCkpIGhpZGRlbiBAZW5kaWY+CiAgICAgICAgICAgICAgICBNw6B1IG7DoHkgxJFh
bmcgY2jhu50gYuG7mSDhuqNuaCDEkcaw4bujYyBkdXnhu4d0LiBMSU4gWMOJTiBraMO0bmcgZMO5
bmcg4bqjbmggY+G7p2EgbcOgdSBraMOhYyDEkeG7gyBtaW5oIGjhu41hLgogICAgICAgICAgICA8
L3A+CiAgICAgICAgPC9kaXY+CiAgICA8L2Rpdj4KCiAgICA8YXNpZGUgY2xhc3M9Imx4cGRwLWJ1
eS1wYW5lbCBseHMtYnV5IiBhcmlhLWxhYmVsPSJUaMO0bmcgdGluIG11YSBow6BuZyIgZGF0YS1s
eHMtcHVyY2hhc2U+CiAgICAgICAgPGRpdiBjbGFzcz0ibHhzLWJ1eV9faGVhZCI+CiAgICAgICAg
ICAgIDxwIGNsYXNzPSJseHMta2lja2VyIj5UaGnhur90IGvhur8gbeG7m2kgwrcgUmVhZHkgdG8g
d2VhcjwvcD4KICAgICAgICAgICAgPGgxPnt7ICRzaG9ydE5hbWUgfX08L2gxPgogICAgICAgICAg
ICBAaWYoJGRlc2NyaXB0b3IgIT09ICcnKQogICAgICAgICAgICAgICAgPHAgY2xhc3M9Imx4cy1i
dXlfX2Rlc2NyaXB0b3IiPnt7ICRkZXNjcmlwdG9yIH19PC9wPgogICAgICAgICAgICBAZW5kaWYK
ICAgICAgICAgICAgQGlmKCRkZXNjcmlwdGlvbiAhPT0gJycpCiAgICAgICAgICAgICAgICA8cCBj
bGFzcz0ibHhzLWJ1eV9fZGVzY3JpcHRpb24iPnt7ICRkZXNjcmlwdGlvbiB9fTwvcD4KICAgICAg
ICAgICAgQGVuZGlmCiAgICAgICAgPC9kaXY+CgogICAgICAgIDxkaXYgY2xhc3M9Imx4cy1wcmlj
ZS1saW5lIj4KICAgICAgICAgICAgPGRpdiBjbGFzcz0ibHhwZHBfX3ByaWNlIGx4cy1wcmljZSIg
ZGF0YS1seHBkcC1wcmljZT4KICAgICAgICAgICAgICAgIDxzdHJvbmc+e3sgbnVtYmVyX2Zvcm1h
dCgoZmxvYXQpIGRhdGFfZ2V0KCRjb21tZXJjZSwgJ3ByaWNlLm1pbicpLCAwLCAnLCcsICcuJykg
fX3igqs8L3N0cm9uZz4KICAgICAgICAgICAgICAgIEBpZihkYXRhX2dldCgkY29tbWVyY2UsICdw
cmljZS5oYXNfc2FsZScpICYmIGRhdGFfZ2V0KCRjb21tZXJjZSwgJ3ByaWNlLm9yaWdpbmFsX21p
bicpID4gZGF0YV9nZXQoJGNvbW1lcmNlLCAncHJpY2UubWluJykpCiAgICAgICAgICAgICAgICAg
ICAgPGRlbD57eyBudW1iZXJfZm9ybWF0KChmbG9hdCkgZGF0YV9nZXQoJGNvbW1lcmNlLCAncHJp
Y2Uub3JpZ2luYWxfbWluJyksIDAsICcsJywgJy4nKSB9feKCqzwvZGVsPgogICAgICAgICAgICAg
ICAgQGVuZGlmCiAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8c3BhbiBjbGFzcz0ibHhz
LXN0b2NrIHt7IGRhdGFfZ2V0KCRjb21tZXJjZSwgJ2F2YWlsYWJpbGl0eS5pbl9zdG9jaycpID8g
J2lzLWluJyA6ICdpcy1vdXQnIH19Ij4KICAgICAgICAgICAgICAgIDxpIGFyaWEtaGlkZGVuPSJ0
cnVlIj48L2k+CiAgICAgICAgICAgICAgICB7eyBkYXRhX2dldCgkY29tbWVyY2UsICdhdmFpbGFi
aWxpdHkuaW5fc3RvY2snKSA/ICdT4bq1biBzw6BuZyBnaWFvJyA6ICdU4bqhbSBo4bq/dCBow6Bu
ZycgfX0KICAgICAgICAgICAgPC9zcGFuPgogICAgICAgIDwvZGl2PgoKICAgICAgICA8c2VjdGlv
biBjbGFzcz0ibHhwZHAtc2VsZWN0b3IgbHhzLXNlbGVjdG9yIiBhcmlhLWxhYmVsbGVkYnk9Imx4
c0NvbG9yVGl0bGUiPgogICAgICAgICAgICA8ZGl2IGNsYXNzPSJseHMtc2VsZWN0b3JfX2hlYWQi
PgogICAgICAgICAgICAgICAgPGgyIGlkPSJseHNDb2xvclRpdGxlIj5Nw6B1IHPhuq9jPC9oMj4K
ICAgICAgICAgICAgICAgIDxzcGFuIGRhdGEtbHhwZHAtY29sb3ItbGFiZWw+e3sgZGF0YV9nZXQo
JGRlZmF1bHRDb2xvciwgJ2xhYmVsJywgJ0No4buNbiBtw6B1JykgfX08L3NwYW4+CiAgICAgICAg
ICAgIDwvZGl2PgoKICAgICAgICAgICAgQGlmKCRhdmFpbGFibGVDb2xvcnMtPmlzTm90RW1wdHko
KSkKICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4cy1jb2xvci1saXN0IiByb2xlPSJsaXN0
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
eHMtY29sb3Ige3sgJGFjdGl2ZSA/ICdpcy1hY3RpdmUnIDogJycgfX0iCiAgICAgICAgICAgICAg
ICAgICAgICAgICAgICBkYXRhLWx4cGRwLWNvbG9yCiAgICAgICAgICAgICAgICAgICAgICAgICAg
ICBkYXRhLWNvbG9yLWlkPSJ7eyBkYXRhX2dldCgkY29sb3IsICdpZCcpIH19IgogICAgICAgICAg
ICAgICAgICAgICAgICAgICAgZGF0YS1jb2xvci1jb2RlPSJ7eyBkYXRhX2dldCgkY29sb3IsICdj
b2RlJykgfX0iCiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRhLWNvbG9yLXNlbGxhYmxl
PSIxIgogICAgICAgICAgICAgICAgICAgICAgICAgICAgYXJpYS1wcmVzc2VkPSJ7eyAkYWN0aXZl
ID8gJ3RydWUnIDogJ2ZhbHNlJyB9fSIKICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFyaWEt
bGFiZWw9Ik3DoHUge3sgZGF0YV9nZXQoJGNvbG9yLCAnbGFiZWwnKSB9fSIKICAgICAgICAgICAg
ICAgICAgICAgICAgPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHNwYW4gY2xhc3M9Imx4
cy1jb2xvcl9fdmlzdWFsIiBzdHlsZT0iLS1seHMtc3dhdGNoOnt7IGRhdGFfZ2V0KCRjb2xvciwg
J2hleCcpID86ICcjZGZlM2VmJyB9fSI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg
QGlmKCRjb3ZlcikKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGltZyBzcmM9
Int7ICRjb3ZlciB9fSIgYWx0PSIiIHdpZHRoPSI3MiIgaGVpZ2h0PSI5MCIgbG9hZGluZz0ibGF6
eSIgZGVjb2Rpbmc9ImFzeW5jIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBAZWxz
ZQogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aSBzdHlsZT0iLS1seHMtc3dh
dGNoOnt7IGRhdGFfZ2V0KCRjb2xvciwgJ2hleCcpID86ICcjZGZlM2VmJyB9fSI+PC9pPgogICAg
ICAgICAgICAgICAgICAgICAgICAgICAgICAgIEBlbmRpZgogICAgICAgICAgICAgICAgICAgICAg
ICAgICAgPC9zcGFuPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHN0cm9uZz57eyBkYXRh
X2dldCgkY29sb3IsICdsYWJlbCcpIH19PC9zdHJvbmc+CiAgICAgICAgICAgICAgICAgICAgICAg
IDwvYnV0dG9uPgogICAgICAgICAgICAgICAgICAgIEBlbmRmb3JlYWNoCiAgICAgICAgICAgICAg
ICA8L2Rpdj4KICAgICAgICAgICAgQGVsc2UKICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4
cy1hbGwtc29sZG91dCIgcm9sZT0ic3RhdHVzIj4KICAgICAgICAgICAgICAgICAgICBU4bqldCBj
4bqjIG3DoHUgaGnhu4duIMSRYW5nIHThuqFtIGjhur90IGjDoG5nLgogICAgICAgICAgICAgICAg
PC9kaXY+CiAgICAgICAgICAgIEBlbmRpZgoKICAgICAgICAgICAgQGlmKCRyZXF1ZXN0ZWRVbmF2
YWlsYWJsZSkKICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4cy1jb2xvci11bmF2YWlsYWJs
ZSIgcm9sZT0ic3RhdHVzIj4KICAgICAgICAgICAgICAgICAgICA8c3BhbiBzdHlsZT0iLS1seHMt
c3dhdGNoOnt7IGRhdGFfZ2V0KCRyZXF1ZXN0ZWRVbmF2YWlsYWJsZSwgJ2hleCcpID86ICcjY2Jk
NWUxJyB9fSI+PC9zcGFuPgogICAgICAgICAgICAgICAgICAgIDxkaXY+CiAgICAgICAgICAgICAg
ICAgICAgICAgIDxzdHJvbmc+e3sgZGF0YV9nZXQoJHJlcXVlc3RlZFVuYXZhaWxhYmxlLCAnbGFi
ZWwnKSB9fTwvc3Ryb25nPgogICAgICAgICAgICAgICAgICAgICAgICA8c21hbGw+TcOgdSBuw6B5
IMSRYW5nIHThuqFtIGjhur90IGjDoG5nPC9zbWFsbD4KICAgICAgICAgICAgICAgICAgICA8L2Rp
dj4KICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICBAZW5kaWYKICAgICAgICA8L3Nl
Y3Rpb24+CgogICAgICAgIDxzZWN0aW9uIGNsYXNzPSJseHBkcC1zZWxlY3RvciBseHMtc2VsZWN0
b3IgbHhzLXNlbGVjdG9yLS1zaXplIiBhcmlhLWxhYmVsbGVkYnk9Imx4c1NpemVUaXRsZSI+CiAg
ICAgICAgICAgIDxkaXYgY2xhc3M9Imx4cy1zZWxlY3Rvcl9faGVhZCI+CiAgICAgICAgICAgICAg
ICA8aDIgaWQ9Imx4c1NpemVUaXRsZSI+S8OtY2ggdGjGsOG7m2M8L2gyPgogICAgICAgICAgICAg
ICAgPGJ1dHRvbgogICAgICAgICAgICAgICAgICAgIHR5cGU9ImJ1dHRvbiIKICAgICAgICAgICAg
ICAgICAgICBjbGFzcz0ibHhwZHAtc2l6ZS1hZHZpc29yLWxpbmsgbHhzLXNpemUtZ3VpZGUiCiAg
ICAgICAgICAgICAgICAgICAgZGF0YS1seHBkcC1zaXplLWFkdmlzb3Itb3BlbgogICAgICAgICAg
ICAgICAgICAgIEBpZighZGF0YV9nZXQoJGFkdmlzb3IsICdlbmFibGVkJykpIGRpc2FibGVkIEBl
bmRpZgogICAgICAgICAgICAgICAgPgogICAgICAgICAgICAgICAgICAgIFTDrG0gc2l6ZSBj4bun
YSBi4bqhbgogICAgICAgICAgICAgICAgICAgIDxzdmcgdmlld0JveD0iMCAwIDI0IDI0IiBhcmlh
LWhpZGRlbj0idHJ1ZSI+PHBhdGggZD0iTTUgMTJoMTRNMTQgN2w1IDUtNSA1Ii8+PC9zdmc+CiAg
ICAgICAgICAgICAgICA8L2J1dHRvbj4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgIDxk
aXYgY2xhc3M9Imx4cGRwLXNpemUtbGlzdCBseHMtc2l6ZS1saXN0IiBkYXRhLWx4cGRwLXNpemVz
IHJvbGU9Imxpc3QiIGFyaWEtbGl2ZT0icG9saXRlIj48L2Rpdj4KICAgICAgICAgICAgPGRpdiBj
bGFzcz0ibHhwZHAtc2VsZWN0aW9uIGx4cy1zZWxlY3Rpb24iIGRhdGEtbHhwZHAtc2VsZWN0aW9u
IGhpZGRlbj4KICAgICAgICAgICAgICAgIDxzdHJvbmcgZGF0YS1seHBkcC1zZWxlY3RlZC10ZXh0
Pjwvc3Ryb25nPgogICAgICAgICAgICAgICAgPHNwYW4gZGF0YS1seHBkcC1zZWxlY3RlZC1zdG9j
az48L3NwYW4+CiAgICAgICAgICAgIDwvZGl2PgogICAgICAgIDwvc2VjdGlvbj4KCiAgICAgICAg
PGZvcm0gbWV0aG9kPSJwb3N0IiBhY3Rpb249Int7IGRhdGFfZ2V0KCRjb21tZXJjZSwgJ2NhcnRf
YWN0aW9uJykgfX0iIGNsYXNzPSJseHBkcC1jYXJ0LWZvcm0gbHhzLWNhcnQiIGRhdGEtbHhwZHAt
Y2FydC1mb3JtPgogICAgICAgICAgICBAY3NyZgogICAgICAgICAgICA8aW5wdXQgdHlwZT0iaGlk
ZGVuIiBuYW1lPSJzZWxsYWJsZV9za3VfaWQiIHZhbHVlPSIiIGRhdGEtbHhwZHAtc2t1LWlucHV0
PgogICAgICAgICAgICA8aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJxdWFudGl0eSIgdmFsdWU9
IjEiPgogICAgICAgICAgICA8YnV0dG9uIGNsYXNzPSJseHBkcC1wcmltYXJ5LWJ1dHRvbiBseHMt
YnV5LWJ1dHRvbiIgdHlwZT0ic3VibWl0IiBkaXNhYmxlZCBkYXRhLWx4cGRwLWJ1eT4KICAgICAg
ICAgICAgICAgIENo4buNbiBtw6B1IHbDoCBrw61jaCB0aMaw4bubYwogICAgICAgICAgICA8L2J1
dHRvbj4KICAgICAgICA8L2Zvcm0+CgogICAgICAgIDxkaXYgY2xhc3M9Imx4cy1idXktY29uZmlk
ZW5jZSIgYXJpYS1sYWJlbD0iUXV54buBbiBs4bujaSBtdWEgaMOgbmciPgogICAgICAgICAgICA8
c3Bhbj4KICAgICAgICAgICAgICAgIDxzdmcgdmlld0JveD0iMCAwIDI0IDI0IiBhcmlhLWhpZGRl
bj0idHJ1ZSI+PHBhdGggZD0iTTQgN2gxNnYxMEg0ek03IDE3djJNMTcgMTd2Mk04IDEyaDgiLz48
L3N2Zz4KICAgICAgICAgICAgICAgIENPRCBraGkgbmjhuq1uIGjDoG5nCiAgICAgICAgICAgIDwv
c3Bhbj4KICAgICAgICAgICAgPHNwYW4+CiAgICAgICAgICAgICAgICA8c3ZnIHZpZXdCb3g9IjAg
MCAyNCAyNCIgYXJpYS1oaWRkZW49InRydWUiPjxwYXRoIGQ9Ik03IDdoMTB2MTBIN3pNNCAxMmE4
IDggMCAwIDEgMTMuNy01LjdNMjAgMTJhOCA4IDAgMCAxLTEzLjcgNS43Ii8+PC9zdmc+CiAgICAg
ICAgICAgICAgICBI4buXIHRy4bujIMSR4buVaSBzaXplCiAgICAgICAgICAgIDwvc3Bhbj4KICAg
ICAgICAgICAgPHNwYW4+CiAgICAgICAgICAgICAgICA8c3ZnIHZpZXdCb3g9IjAgMCAyNCAyNCIg
YXJpYS1oaWRkZW49InRydWUiPjxwYXRoIGQ9Ik00IDE4aDE2TTYgMThWOWw2LTQgNiA0djlNOSAx
Mmg2Ii8+PC9zdmc+CiAgICAgICAgICAgICAgICBHaWFvIGjDoG5nIHRvw6BuIHF14buRYwogICAg
ICAgICAgICA8L3NwYW4+CiAgICAgICAgPC9kaXY+CiAgICA8L2FzaWRlPgo8L2Rpdj4KCjxuYXYg
Y2xhc3M9Imx4cy1tb2JpbGUtZG9jayIgZGF0YS1seHMtbW9iaWxlLWRvY2sgYXJpYS1sYWJlbD0i
VGhhbmggY8O0bmcgY+G7pSBtdWEgaMOgbmciPgogICAgPGEgaHJlZj0ie3sgcm91dGUoJ2NvbW1l
cmNlLnYyLmhvbWUnKSB9fSIgYXJpYS1sYWJlbD0iVHJhbmcgY2jhu6ciPgogICAgICAgIDxzdmcg
dmlld0JveD0iMCAwIDI0IDI0IiBhcmlhLWhpZGRlbj0idHJ1ZSI+PHBhdGggZD0ibTMgMTEgOS03
IDkgN3Y5aC02di02SDl2NkgzeiIvPjwvc3ZnPgogICAgICAgIDxzcGFuPkhvbWU8L3NwYW4+CiAg
ICA8L2E+CiAgICA8YSBocmVmPSJ7eyByb3V0ZSgnY29tbWVyY2UudjIuc2VhcmNoJykgfX0iIGFy
aWEtbGFiZWw9IlTDrG0ga2nhur9tIj4KICAgICAgICA8c3ZnIHZpZXdCb3g9IjAgMCAyNCAyNCIg
YXJpYS1oaWRkZW49InRydWUiPjxjaXJjbGUgY3g9IjExIiBjeT0iMTEiIHI9IjYiLz48cGF0aCBk
PSJtMTYgMTYgNCA0Ii8+PC9zdmc+CiAgICAgICAgPHNwYW4+VMOsbTwvc3Bhbj4KICAgIDwvYT4K
ICAgIDxhIGhyZWY9Int7IHJvdXRlKCdjb21tZXJjZS52Mi5hY2NvdW50LmluZGV4JykgfX0iIGFy
aWEtbGFiZWw9IlTDoGkga2hv4bqjbiI+CiAgICAgICAgPHN2ZyB2aWV3Qm94PSIwIDAgMjQgMjQi
IGFyaWEtaGlkZGVuPSJ0cnVlIj48Y2lyY2xlIGN4PSIxMiIgY3k9IjgiIHI9IjQiLz48cGF0aCBk
PSJNNCAyMWMuOC01IDMuNS03IDgtN3M3LjIgMiA4IDciLz48L3N2Zz4KICAgICAgICA8c3Bhbj5U
w7RpPC9zcGFuPgogICAgPC9hPgogICAgPGEgaHJlZj0ie3sgcm91dGUoJ2NvbW1lcmNlLnYyLmNh
cnQuaW5kZXgnKSB9fSIgYXJpYS1sYWJlbD0iR2nhu48gaMOgbmciPgogICAgICAgIDxzdmcgdmll
d0JveD0iMCAwIDI0IDI0IiBhcmlhLWhpZGRlbj0idHJ1ZSI+PHBhdGggZD0iTTUgN2gxNGwtMSAx
M0g2TDUgN1oiLz48cGF0aCBkPSJNOSA3YTMgMyAwIDAgMSA2IDAiLz48L3N2Zz4KICAgICAgICA8
c3Bhbj5HaeG7jzwvc3Bhbj4KICAgIDwvYT4KICAgIDxidXR0b24gdHlwZT0iYnV0dG9uIiBjbGFz
cz0ibHhzLW1vYmlsZS1kb2NrX19jdGEiIGRhdGEtbHhzLWRvY2stc3VibWl0IGRpc2FibGVkPgog
ICAgICAgIDxzcGFuIGRhdGEtbHhzLWRvY2stbGFiZWw+Q2jhu41uIG3DoHUgJmFtcDsgc2l6ZTwv
c3Bhbj4KICAgICAgICA8c3ZnIHZpZXdCb3g9IjAgMCAyNCAyNCIgYXJpYS1oaWRkZW49InRydWUi
PjxwYXRoIGQ9Ik01IDEyaDE0TTE0IDdsNSA1LTUgNSIvPjwvc3ZnPgogICAgPC9idXR0b24+Cjwv
bmF2Pgo=
STUDIO_SIGNAL_PAYLOAD_3

decode_to_file 'resources/views/commerce_v2/pdp/studio/quick-read.blade.php' <<'STUDIO_SIGNAL_PAYLOAD_4'
QHBocAogICAgJGZhY3RzID0gY29sbGVjdCgoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdwcm9kdWN0
X3RydXRoLmhpZ2hsaWdodHMnLCBbXSkpCiAgICAgICAgLT5jb25jYXQoKGFycmF5KSBkYXRhX2dl
dCgkcGRwLCAncHJvZHVjdF90cnV0aC5kZXNpZ24uaXRlbXMnLCBbXSkpCiAgICAgICAgLT5jb25j
YXQoKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnZml0LmZpdF9pdGVtcycsIFtdKSkKICAgICAgICAt
PmNvbmNhdCgoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdwcm9kdWN0X3RydXRoLm1hdGVyaWFscy5z
ZWN0aW9uLml0ZW1zJywgW10pKQogICAgICAgIC0+bWFwKGZuICgkaXRlbSkgPT4gWwogICAgICAg
ICAgICAna2V5JyA9PiAoc3RyaW5nKSAoZGF0YV9nZXQoJGl0ZW0sICdrZXknKSA/OiBkYXRhX2dl
dCgkaXRlbSwgJ2xhYmVsJykpLAogICAgICAgICAgICAnbGFiZWwnID0+IHRyaW0oKHN0cmluZykg
ZGF0YV9nZXQoJGl0ZW0sICdsYWJlbCcpKSwKICAgICAgICAgICAgJ3ZhbHVlJyA9PiB0cmltKChz
dHJpbmcpIGRhdGFfZ2V0KCRpdGVtLCAndmFsdWUnKSksCiAgICAgICAgXSkKICAgICAgICAtPmZp
bHRlcihmbiAoJGl0ZW0pID0+IGRhdGFfZ2V0KCRpdGVtLCAnbGFiZWwnKSAhPT0gJycgJiYgZGF0
YV9nZXQoJGl0ZW0sICd2YWx1ZScpICE9PSAnJykKICAgICAgICAtPnVuaXF1ZShmbiAoJGl0ZW0p
ID0+IFxJbGx1bWluYXRlXFN1cHBvcnRcU3RyOjpsb3dlcigoc3RyaW5nKSBkYXRhX2dldCgkaXRl
bSwgJ2xhYmVsJykpKQogICAgICAgIC0+dGFrZSg0KQogICAgICAgIC0+dmFsdWVzKCk7CkBlbmRw
aHAKCkBpZigkZmFjdHMtPmlzTm90RW1wdHkoKSkKPGRpdiBjbGFzcz0ibHhzLXNoZWxsIGx4cy1x
dWljay1yZWFkIiBkYXRhLWx4cy1yZXZlYWw+CiAgICA8ZGl2IGNsYXNzPSJseHMtc2VjdGlvbi1o
ZWFkaW5nIGx4cy1zZWN0aW9uLWhlYWRpbmctLWNvbXBhY3QiPgogICAgICAgIDxwIGNsYXNzPSJs
eHMta2lja2VyIj5OaMOsbiBuaGFuaDwvcD4KICAgICAgICA8aDI+SGnhu4N1IHPhuqNuIHBo4bqp
bSB0cm9uZyB2w6BpIGdpw6J5LjwvaDI+CiAgICA8L2Rpdj4KCiAgICA8ZGl2IGNsYXNzPSJseHMt
cXVpY2stcmVhZF9fZ3JpZCI+CiAgICAgICAgQGZvcmVhY2goJGZhY3RzIGFzICRpbmRleCA9PiAk
ZmFjdCkKICAgICAgICAgICAgQHBocCAkc2VtYW50aWMgPSBcSWxsdW1pbmF0ZVxTdXBwb3J0XFN0
cjo6bG93ZXIoZGF0YV9nZXQoJGZhY3QsICdrZXknKS4nICcuZGF0YV9nZXQoJGZhY3QsICdsYWJl
bCcpKTsgQGVuZHBocAogICAgICAgICAgICA8YXJ0aWNsZSBjbGFzcz0ibHhzLWZhY3QtY2FyZCI+
CiAgICAgICAgICAgICAgICA8c3BhbiBjbGFzcz0ibHhzLWZhY3QtY2FyZF9faWNvbiIgYXJpYS1o
aWRkZW49InRydWUiPgogICAgICAgICAgICAgICAgICAgIEBpZihcSWxsdW1pbmF0ZVxTdXBwb3J0
XFN0cjo6Y29udGFpbnMoJHNlbWFudGljLCBbJ2Zvcm0nLCAnZMOhbmcnLCAnc2lsaG91ZXR0ZSdd
KSkKICAgICAgICAgICAgICAgICAgICAgICAgPHN2ZyB2aWV3Qm94PSIwIDAgNDggNDgiPjxwYXRo
IGQ9Ik0xOCA2aDEybDMgOCA3IDI2SDhsNy0yNiAzLThaIi8+PHBhdGggZD0iTTE4IDZjMSA0IDEx
IDQgMTIgMCIvPjwvc3ZnPgogICAgICAgICAgICAgICAgICAgIEBlbHNlaWYoXElsbHVtaW5hdGVc
U3VwcG9ydFxTdHI6OmNvbnRhaW5zKCRzZW1hbnRpYywgWydkw6BpJywgJ2xlbmd0aCcsICdtaW5p
JywgJ21pZGknLCAnbWF4aSddKSkKICAgICAgICAgICAgICAgICAgICAgICAgPHN2ZyB2aWV3Qm94
PSIwIDAgNDggNDgiPjxwYXRoIGQ9Ik0xMyA4aDIyTTEzIDQwaDIyTTI0IDh2MzIiLz48cGF0aCBk
PSJtMjAgMTMgNC01IDQgNU0yMCAzNWw0IDUgNC01Ii8+PC9zdmc+CiAgICAgICAgICAgICAgICAg
ICAgQGVsc2VpZihcSWxsdW1pbmF0ZVxTdXBwb3J0XFN0cjo6Y29udGFpbnMoJHNlbWFudGljLCBb
J3bhuqNpJywgJ21hdGVyaWFsJywgJ2ZhYnJpYycsICdsw7N0J10pKQogICAgICAgICAgICAgICAg
ICAgICAgICA8c3ZnIHZpZXdCb3g9IjAgMCA0OCA0OCI+PHBhdGggZD0iTTggMTMgMjQgNmwxNiA3
LTE2IDhMOCAxM1oiLz48cGF0aCBkPSJtOCAyMSAxNiA4IDE2LThNOCAyOWwxNiA5IDE2LTkiLz48
L3N2Zz4KICAgICAgICAgICAgICAgICAgICBAZWxzZQogICAgICAgICAgICAgICAgICAgICAgICA8
c3ZnIHZpZXdCb3g9IjAgMCA0OCA0OCI+PHBhdGggZD0iTTI0IDUgMjkgMTdsMTMgMS0xMCA4IDMg
MTMtMTEtNy0xMSA3IDMtMTMtMTAtOCAxMy0xIDUtMTJaIi8+PC9zdmc+CiAgICAgICAgICAgICAg
ICAgICAgQGVuZGlmCiAgICAgICAgICAgICAgICA8L3NwYW4+CiAgICAgICAgICAgICAgICA8ZGl2
PgogICAgICAgICAgICAgICAgICAgIDxzbWFsbD57eyBkYXRhX2dldCgkZmFjdCwgJ2xhYmVsJykg
fX08L3NtYWxsPgogICAgICAgICAgICAgICAgICAgIDxzdHJvbmc+e3sgZGF0YV9nZXQoJGZhY3Qs
ICd2YWx1ZScpIH19PC9zdHJvbmc+CiAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAg
PC9hcnRpY2xlPgogICAgICAgIEBlbmRmb3JlYWNoCiAgICA8L2Rpdj4KPC9kaXY+CkBlbmRpZgo=
STUDIO_SIGNAL_PAYLOAD_4

decode_to_file 'resources/views/commerce_v2/pdp/studio/design-explorer.blade.php' <<'STUDIO_SIGNAL_PAYLOAD_5'
QHBocAogICAgJGZhY3RzID0gY29sbGVjdCgoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdwcm9kdWN0
X3RydXRoLmhpZ2hsaWdodHMnLCBbXSkpCiAgICAgICAgLT5jb25jYXQoKGFycmF5KSBkYXRhX2dl
dCgkcGRwLCAncHJvZHVjdF90cnV0aC5kZXNpZ24uaXRlbXMnLCBbXSkpCiAgICAgICAgLT5tYXAo
Zm4gKCRpdGVtKSA9PiBbCiAgICAgICAgICAgICdsYWJlbCcgPT4gdHJpbSgoc3RyaW5nKSBkYXRh
X2dldCgkaXRlbSwgJ2xhYmVsJykpLAogICAgICAgICAgICAndmFsdWUnID0+IHRyaW0oKHN0cmlu
ZykgZGF0YV9nZXQoJGl0ZW0sICd2YWx1ZScpKSwKICAgICAgICBdKQogICAgICAgIC0+ZmlsdGVy
KGZuICgkaXRlbSkgPT4gZGF0YV9nZXQoJGl0ZW0sICdsYWJlbCcpICE9PSAnJyAmJiBkYXRhX2dl
dCgkaXRlbSwgJ3ZhbHVlJykgIT09ICcnKQogICAgICAgIC0+dW5pcXVlKGZuICgkaXRlbSkgPT4g
XElsbHVtaW5hdGVcU3VwcG9ydFxTdHI6Omxvd2VyKChzdHJpbmcpIGRhdGFfZ2V0KCRpdGVtLCAn
bGFiZWwnKSkpCiAgICAgICAgLT50YWtlKDQpCiAgICAgICAgLT52YWx1ZXMoKTsKICAgICRtZWRp
YSA9IGNvbGxlY3QoKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnY29tbWVyY2UuZGVmYXVsdF9jb2xv
ci5tZWRpYScsIFtdKSktPnZhbHVlcygpOwogICAgJHZpc3VhbCA9IChhcnJheSkgKCRtZWRpYS0+
Z2V0KDEpID86ICRtZWRpYS0+Zmlyc3QoKSA/OiBbXSk7CiAgICAkcG9zaXRpb25zID0gWwogICAg
ICAgIFsneCcgPT4gNTIsICd5JyA9PiAxOF0sCiAgICAgICAgWyd4JyA9PiAzOCwgJ3knID0+IDQy
XSwKICAgICAgICBbJ3gnID0+IDU3LCAneScgPT4gNjJdLAogICAgICAgIFsneCcgPT4gNDYsICd5
JyA9PiA4Ml0sCiAgICBdOwpAZW5kcGhwCgpAaWYoJGZhY3RzLT5pc05vdEVtcHR5KCkgJiYgZGF0
YV9nZXQoJHZpc3VhbCwgJ3VybCcpKQo8ZGl2IGNsYXNzPSJseHMtc2hlbGwgbHhzLWRlc2lnbi1l
eHBsb3JlciIgZGF0YS1seHMtcmV2ZWFsPgogICAgPGRpdiBjbGFzcz0ibHhzLXNlY3Rpb24taGVh
ZGluZyI+CiAgICAgICAgPHAgY2xhc3M9Imx4cy1raWNrZXIiPktow6FtIHBow6EgdGhp4bq/dCBr
4bq/PC9wPgogICAgICAgIDxoMj5DaOG6oW0gdsOgbyB04burbmcgxJFp4buDbSDEkeG7gyB4ZW0g
xJFp4buBdSBsw6BtIG7Dqm4gcGhvbSBkw6FuZy48L2gyPgogICAgICAgIDxwPktow7RuZyBj4bqn
biDEkeG7jWMgbeG7mXQgxJFv4bqhbiBtw7QgdOG6oyBkw6BpLiBN4buXaSDEkWnhu4NtIMSRw6Fu
aCBk4bqldSBk4bqrbiBi4bqhbiDEkeG6v24gxJHDum5nIGNoaSB0aeG6v3QgxJHDoW5nIGNow7og
w70uPC9wPgogICAgPC9kaXY+CgogICAgPGRpdiBjbGFzcz0ibHhzLWRlc2lnbi1leHBsb3Jlcl9f
bGF5b3V0Ij4KICAgICAgICA8ZmlndXJlIGNsYXNzPSJseHMtZGVzaWduLWV4cGxvcmVyX192aXN1
YWwiPgogICAgICAgICAgICA8aW1nCiAgICAgICAgICAgICAgICBzcmM9Int7IGRhdGFfZ2V0KCR2
aXN1YWwsICd1cmwnKSB9fSIKICAgICAgICAgICAgICAgIGFsdD0ie3sgZGF0YV9nZXQoJHBkcCwg
J2lkZW50aXR5Lm5hbWUnKSB9fSAtIGNoaSB0aeG6v3QgdGhp4bq/dCBr4bq/IgogICAgICAgICAg
ICAgICAgbG9hZGluZz0ibGF6eSIKICAgICAgICAgICAgICAgIGRlY29kaW5nPSJhc3luYyIKICAg
ICAgICAgICAgICAgIGRhdGEtbHhzLWRlc2lnbi1pbWFnZQogICAgICAgICAgICA+CiAgICAgICAg
ICAgIEBmb3JlYWNoKCRmYWN0cyBhcyAkaW5kZXggPT4gJGZhY3QpCiAgICAgICAgICAgICAgICBA
cGhwICRwb3NpdGlvbiA9ICRwb3NpdGlvbnNbJGluZGV4XSA/PyAkcG9zaXRpb25zWzBdOyBAZW5k
cGhwCiAgICAgICAgICAgICAgICA8YnV0dG9uCiAgICAgICAgICAgICAgICAgICAgdHlwZT0iYnV0
dG9uIgogICAgICAgICAgICAgICAgICAgIGNsYXNzPSJseHMtaG90c3BvdCB7eyAkaW5kZXggPT09
IDAgPyAnaXMtYWN0aXZlJyA6ICcnIH19IgogICAgICAgICAgICAgICAgICAgIHN0eWxlPSItLWx4
cy1ob3RzcG90LXg6e3sgJHBvc2l0aW9uWyd4J10gfX0lOy0tbHhzLWhvdHNwb3QteTp7eyAkcG9z
aXRpb25bJ3knXSB9fSU7IgogICAgICAgICAgICAgICAgICAgIGRhdGEtbHhzLWhvdHNwb3Q9Int7
ICRpbmRleCB9fSIKICAgICAgICAgICAgICAgICAgICBhcmlhLWxhYmVsPSJ7eyBkYXRhX2dldCgk
ZmFjdCwgJ2xhYmVsJykgfX06IHt7IGRhdGFfZ2V0KCRmYWN0LCAndmFsdWUnKSB9fSIKICAgICAg
ICAgICAgICAgICAgICBhcmlhLXByZXNzZWQ9Int7ICRpbmRleCA9PT0gMCA/ICd0cnVlJyA6ICdm
YWxzZScgfX0iCiAgICAgICAgICAgICAgICA+PHNwYW4+e3sgc3RyX3BhZCgoc3RyaW5nKSAoJGlu
ZGV4ICsgMSksIDIsICcwJywgU1RSX1BBRF9MRUZUKSB9fTwvc3Bhbj48L2J1dHRvbj4KICAgICAg
ICAgICAgQGVuZGZvcmVhY2gKICAgICAgICA8L2ZpZ3VyZT4KCiAgICAgICAgPGRpdiBjbGFzcz0i
bHhzLWRlc2lnbi1leHBsb3Jlcl9fY2FyZHMiPgogICAgICAgICAgICBAZm9yZWFjaCgkZmFjdHMg
YXMgJGluZGV4ID0+ICRmYWN0KQogICAgICAgICAgICAgICAgPGFydGljbGUgY2xhc3M9Imx4cy1k
ZXNpZ24tY2FyZCB7eyAkaW5kZXggPT09IDAgPyAnaXMtYWN0aXZlJyA6ICcnIH19IiBkYXRhLWx4
cy1ob3RzcG90LWNhcmQ9Int7ICRpbmRleCB9fSI+CiAgICAgICAgICAgICAgICAgICAgPHNwYW4+
e3sgc3RyX3BhZCgoc3RyaW5nKSAoJGluZGV4ICsgMSksIDIsICcwJywgU1RSX1BBRF9MRUZUKSB9
fTwvc3Bhbj4KICAgICAgICAgICAgICAgICAgICA8c21hbGw+e3sgZGF0YV9nZXQoJGZhY3QsICds
YWJlbCcpIH19PC9zbWFsbD4KICAgICAgICAgICAgICAgICAgICA8aDM+e3sgZGF0YV9nZXQoJGZh
Y3QsICd2YWx1ZScpIH19PC9oMz4KICAgICAgICAgICAgICAgICAgICA8cD5YZW0gdHLhu7FjIHRp
4bq/cCB0csOqbiBow6xuaCDhuqNuaCDEkeG7gyBj4bqjbSBuaOG6rW4gduG7iyB0csOtIHbDoCB0
4bu3IGzhu4cgY+G7p2EgY2hpIHRp4bq/dCBuw6B5LjwvcD4KICAgICAgICAgICAgICAgIDwvYXJ0
aWNsZT4KICAgICAgICAgICAgQGVuZGZvcmVhY2gKICAgICAgICA8L2Rpdj4KICAgIDwvZGl2Pgo8
L2Rpdj4KQGVuZGlmCg==
STUDIO_SIGNAL_PAYLOAD_5

decode_to_file 'resources/views/commerce_v2/pdp/studio/benefit-grid.blade.php' <<'STUDIO_SIGNAL_PAYLOAD_6'
QHBocAogICAgJGZhY3RzID0gY29sbGVjdCgoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdwcm9kdWN0
X3RydXRoLmRlc2lnbi5pdGVtcycsIFtdKSkKICAgICAgICAtPmNvbmNhdCgoYXJyYXkpIGRhdGFf
Z2V0KCRwZHAsICdwcm9kdWN0X3RydXRoLmhpZ2hsaWdodHMnLCBbXSkpCiAgICAgICAgLT5tYXAo
Zm4gKCRpdGVtKSA9PiBbCiAgICAgICAgICAgICdsYWJlbCcgPT4gdHJpbSgoc3RyaW5nKSBkYXRh
X2dldCgkaXRlbSwgJ2xhYmVsJykpLAogICAgICAgICAgICAndmFsdWUnID0+IHRyaW0oKHN0cmlu
ZykgZGF0YV9nZXQoJGl0ZW0sICd2YWx1ZScpKSwKICAgICAgICBdKQogICAgICAgIC0+ZmlsdGVy
KGZuICgkaXRlbSkgPT4gZGF0YV9nZXQoJGl0ZW0sICdsYWJlbCcpICE9PSAnJyAmJiBkYXRhX2dl
dCgkaXRlbSwgJ3ZhbHVlJykgIT09ICcnKQogICAgICAgIC0+dW5pcXVlKGZuICgkaXRlbSkgPT4g
XElsbHVtaW5hdGVcU3VwcG9ydFxTdHI6Omxvd2VyKChzdHJpbmcpIGRhdGFfZ2V0KCRpdGVtLCAn
bGFiZWwnKS4nfCcuZGF0YV9nZXQoJGl0ZW0sICd2YWx1ZScpKSkKICAgICAgICAtPnRha2UoMykK
ICAgICAgICAtPnZhbHVlcygpOwogICAgJG1lZGlhID0gY29sbGVjdCgoYXJyYXkpIGRhdGFfZ2V0
KCRwZHAsICdjb21tZXJjZS5kZWZhdWx0X2NvbG9yLm1lZGlhJywgW10pKS0+ZmlsdGVyKGZuICgk
aXRlbSkgPT4gZGF0YV9nZXQoJGl0ZW0sICd1cmwnKSktPnZhbHVlcygpOwpAZW5kcGhwCgpAaWYo
JGZhY3RzLT5pc05vdEVtcHR5KCkgJiYgJG1lZGlhLT5pc05vdEVtcHR5KCkpCjxkaXYgY2xhc3M9
Imx4cy1zaGVsbCBseHMtYmVuZWZpdHMiIGRhdGEtbHhzLXJldmVhbD4KICAgIDxkaXYgY2xhc3M9
Imx4cy1zZWN0aW9uLWhlYWRpbmcgbHhzLXNlY3Rpb24taGVhZGluZy0tc3BsaXQiPgogICAgICAg
IDxkaXY+CiAgICAgICAgICAgIDxwIGNsYXNzPSJseHMta2lja2VyIj5CYSBnw7NjIG5ow6xuPC9w
PgogICAgICAgICAgICA8aDI+TeG7l2kgY2hpIHRp4bq/dCDEkeG7gXUgY8OzIGzDvSBkbyDEkeG7
gyB4deG6pXQgaGnhu4duLjwvaDI+CiAgICAgICAgPC9kaXY+CiAgICAgICAgPHA+4bqibmggdsOg
IHRow7RuZyB0aW4gxJHGsOG7o2MgxJHhurd0IGPhuqFuaCBuaGF1IMSR4buDIGLhuqFuIGhp4buD
dSBz4bqjbiBwaOG6qW0gYuG6sW5nIGPhuqMgdGjhu4sgZ2nDoWMgbOG6q24gZOG7ryBsaeG7h3Uu
PC9wPgogICAgPC9kaXY+CgogICAgPGRpdiBjbGFzcz0ibHhzLWJlbmVmaXRzX19ncmlkIj4KICAg
ICAgICBAZm9yZWFjaCgkZmFjdHMgYXMgJGluZGV4ID0+ICRmYWN0KQogICAgICAgICAgICBAcGhw
ICRpbWFnZSA9IChhcnJheSkgKCRtZWRpYS0+Z2V0KCRpbmRleCArIDEpID86ICRtZWRpYS0+Z2V0
KCRpbmRleCkgPzogJG1lZGlhLT5maXJzdCgpKTsgQGVuZHBocAogICAgICAgICAgICA8YXJ0aWNs
ZSBjbGFzcz0ibHhzLWJlbmVmaXQtY2FyZCBseHMtYmVuZWZpdC1jYXJkLS17eyAkaW5kZXggKyAx
IH19Ij4KICAgICAgICAgICAgICAgIDxpbWcgc3JjPSJ7eyBkYXRhX2dldCgkaW1hZ2UsICd1cmwn
KSB9fSIgYWx0PSIiIGxvYWRpbmc9ImxhenkiIGRlY29kaW5nPSJhc3luYyI+CiAgICAgICAgICAg
ICAgICA8ZGl2PgogICAgICAgICAgICAgICAgICAgIDxzbWFsbD57eyBkYXRhX2dldCgkZmFjdCwg
J2xhYmVsJykgfX08L3NtYWxsPgogICAgICAgICAgICAgICAgICAgIDxoMz57eyBkYXRhX2dldCgk
ZmFjdCwgJ3ZhbHVlJykgfX08L2gzPgogICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAg
IDwvYXJ0aWNsZT4KICAgICAgICBAZW5kZm9yZWFjaAogICAgPC9kaXY+CjwvZGl2PgpAZW5kaWYK
STUDIO_SIGNAL_PAYLOAD_6

decode_to_file 'resources/views/commerce_v2/pdp/studio/media-lab.blade.php' <<'STUDIO_SIGNAL_PAYLOAD_7'
QHBocAogICAgJGNhbXBhaWduID0gY29sbGVjdCgoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdjb21t
ZXJjZS5kZWZhdWx0X2NvbG9yLm1lZGlhJywgW10pKQogICAgICAgIC0+ZmlsdGVyKGZuICgkaXRl
bSkgPT4gZGF0YV9nZXQoJGl0ZW0sICd1cmwnKSkKICAgICAgICAtPnRha2UoNikKICAgICAgICAt
PnZhbHVlcygpOwogICAgJHRydXRoID0gY29sbGVjdCgoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdt
ZWRpYS5wcm9kdWN0aW9uX3RydXRoJywgW10pKQogICAgICAgIC0+ZmlsdGVyKGZuICgkaXRlbSkg
PT4gZGF0YV9nZXQoJGl0ZW0sICd1cmwnKSkKICAgICAgICAtPnRha2UoNikKICAgICAgICAtPnZh
bHVlcygpOwogICAgJHJvbGVMYWJlbHMgPSBbCiAgICAgICAgJ2hlcm8nID0+ICdU4buVbmcgdGjh
u4MnLAogICAgICAgICdmcm9udCcgPT4gJ03hurd0IHRyxrDhu5tjJywKICAgICAgICAnc2lkZScg
PT4gJ0fDs2MgbmdoacOqbmcnLAogICAgICAgICdiYWNrJyA9PiAnTeG6t3Qgc2F1JywKICAgICAg
ICAnZGV0YWlsJyA9PiAnQ2hpIHRp4bq/dCcsCiAgICAgICAgJ2xpZmVzdHlsZScgPT4gJ1Ryw6pu
IG5nxrDhu51pIG3huqt1JywKICAgIF07CkBlbmRwaHAKCkBpZigkY2FtcGFpZ24tPmlzTm90RW1w
dHkoKSB8fCAkdHJ1dGgtPmlzTm90RW1wdHkoKSkKPGRpdiBjbGFzcz0ibHhzLXNoZWxsIGx4cy1t
ZWRpYS1sYWIiIGRhdGEtbHhzLXJldmVhbD4KICAgIDxkaXYgY2xhc3M9Imx4cy1zZWN0aW9uLWhl
YWRpbmcgbHhzLXNlY3Rpb24taGVhZGluZy0tc3BsaXQiPgogICAgICAgIDxkaXY+CiAgICAgICAg
ICAgIDxwIGNsYXNzPSJseHMta2lja2VyIj5YZW0gdGjhuq10IGvhu7k8L3A+CiAgICAgICAgICAg
IDxoMj5DaHV54buDbiBnaeG7r2EgY+G6o20gaOG7qW5nIHbDoCBow6xuaCDhuqNuaCBz4bqjbiBw
aOG6qW0uPC9oMj4KICAgICAgICA8L2Rpdj4KICAgICAgICA8cD5IYWkgbOG7m3AgaMOsbmgg4bqj
bmggZ2nDunAgYuG6oW4gduG7q2EgY+G6o20gbmjhuq1uIHBob25nIGPDoWNoLCB24burYSBraeG7
g20gdHJhIG5o4buvbmcgY2hpIHRp4bq/dCB0aOG7sWMgdOG6vy48L3A+CiAgICA8L2Rpdj4KCiAg
ICA8ZGl2IGNsYXNzPSJseHMtbWVkaWEtbGFiX190YWJzIiByb2xlPSJ0YWJsaXN0IiBhcmlhLWxh
YmVsPSJMb+G6oWkgaMOsbmgg4bqjbmgiPgogICAgICAgIEBpZigkY2FtcGFpZ24tPmlzTm90RW1w
dHkoKSkKICAgICAgICAgICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJpcy1hY3RpdmUi
IGRhdGEtbHhzLW1lZGlhLXRhYj0iY2FtcGFpZ24iIHJvbGU9InRhYiIgYXJpYS1zZWxlY3RlZD0i
dHJ1ZSI+4bqibmggY+G6o20gaOG7qW5nPC9idXR0b24+CiAgICAgICAgQGVuZGlmCiAgICAgICAg
QGlmKCR0cnV0aC0+aXNOb3RFbXB0eSgpKQogICAgICAgICAgICA8YnV0dG9uIHR5cGU9ImJ1dHRv
biIgZGF0YS1seHMtbWVkaWEtdGFiPSJ0cnV0aCIgcm9sZT0idGFiIiBhcmlhLXNlbGVjdGVkPSJ7
eyAkY2FtcGFpZ24tPmlzRW1wdHkoKSA/ICd0cnVlJyA6ICdmYWxzZScgfX0iPuG6om5oIHPhuqNu
IHBo4bqpbSB0aOG7sWMgdOG6vzwvYnV0dG9uPgogICAgICAgIEBlbmRpZgogICAgPC9kaXY+Cgog
ICAgQGlmKCRjYW1wYWlnbi0+aXNOb3RFbXB0eSgpKQogICAgICAgIDxkaXYgY2xhc3M9Imx4cy1t
ZWRpYS1ncmlkIGlzLWFjdGl2ZSIgZGF0YS1seHMtbWVkaWEtcGFuZWw9ImNhbXBhaWduIiByb2xl
PSJ0YWJwYW5lbCIgZGF0YS1seHMtY2FtcGFpZ24tZ3JpZD4KICAgICAgICAgICAgQGZvcmVhY2go
JGNhbXBhaWduIGFzICRpbmRleCA9PiAkaXRlbSkKICAgICAgICAgICAgICAgIDxmaWd1cmUgY2xh
c3M9Imx4cy1tZWRpYS1ncmlkX19pdGVtIGx4cy1tZWRpYS1ncmlkX19pdGVtLS17eyAoJGluZGV4
ICUgNSkgKyAxIH19Ij4KICAgICAgICAgICAgICAgICAgICA8aW1nIHNyYz0ie3sgZGF0YV9nZXQo
JGl0ZW0sICd1cmwnKSB9fSIgYWx0PSJ7eyBkYXRhX2dldCgkcGRwLCAnaWRlbnRpdHkubmFtZScp
IH19IiBsb2FkaW5nPSJsYXp5IiBkZWNvZGluZz0iYXN5bmMiPgogICAgICAgICAgICAgICAgICAg
IDxmaWdjYXB0aW9uPnt7ICRyb2xlTGFiZWxzW2RhdGFfZ2V0KCRpdGVtLCAncm9sZScpXSA/PyAn
SMOsbmgg4bqjbmggc+G6o24gcGjhuqltJyB9fTwvZmlnY2FwdGlvbj4KICAgICAgICAgICAgICAg
IDwvZmlndXJlPgogICAgICAgICAgICBAZW5kZm9yZWFjaAogICAgICAgIDwvZGl2PgogICAgQGVu
ZGlmCgogICAgQGlmKCR0cnV0aC0+aXNOb3RFbXB0eSgpKQogICAgICAgIDxkaXYgY2xhc3M9Imx4
cy1tZWRpYS1ncmlkIHt7ICRjYW1wYWlnbi0+aXNFbXB0eSgpID8gJ2lzLWFjdGl2ZScgOiAnJyB9
fSIgZGF0YS1seHMtbWVkaWEtcGFuZWw9InRydXRoIiByb2xlPSJ0YWJwYW5lbCIgQGlmKCRjYW1w
YWlnbi0+aXNOb3RFbXB0eSgpKSBoaWRkZW4gQGVuZGlmPgogICAgICAgICAgICBAZm9yZWFjaCgk
dHJ1dGggYXMgJGluZGV4ID0+ICRpdGVtKQogICAgICAgICAgICAgICAgPGZpZ3VyZSBjbGFzcz0i
bHhzLW1lZGlhLWdyaWRfX2l0ZW0gbHhzLW1lZGlhLWdyaWRfX2l0ZW0tLXt7ICgkaW5kZXggJSA1
KSArIDEgfX0iPgogICAgICAgICAgICAgICAgICAgIDxpbWcgc3JjPSJ7eyBkYXRhX2dldCgkaXRl
bSwgJ3VybCcpIH19IiBhbHQ9Int7IGRhdGFfZ2V0KCRwZHAsICdpZGVudGl0eS5uYW1lJykgfX0g
LSDhuqNuaCB0aOG7sWMgdOG6vyIgbG9hZGluZz0ibGF6eSIgZGVjb2Rpbmc9ImFzeW5jIj4KICAg
ICAgICAgICAgICAgICAgICA8ZmlnY2FwdGlvbj57eyAkcm9sZUxhYmVsc1tkYXRhX2dldCgkaXRl
bSwgJ3JvbGUnKV0gPz8gJ+G6om5oIHPhuqNuIHBo4bqpbSB0aOG7sWMgdOG6vycgfX08L2ZpZ2Nh
cHRpb24+CiAgICAgICAgICAgICAgICA8L2ZpZ3VyZT4KICAgICAgICAgICAgQGVuZGZvcmVhY2gK
ICAgICAgICA8L2Rpdj4KICAgIEBlbmRpZgo8L2Rpdj4KQGVuZGlmCg==
STUDIO_SIGNAL_PAYLOAD_7

decode_to_file 'resources/views/commerce_v2/pdp/studio/size-studio.blade.php' <<'STUDIO_SIGNAL_PAYLOAD_8'
QHBocAogICAgJGNoYXJ0ID0gKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnZml0Lmdhcm1lbnRfc2l6
ZV9jaGFydCcsIFtdKTsKICAgICRzaXplcyA9IGNvbGxlY3QoKGFycmF5KSBkYXRhX2dldCgkY2hh
cnQsICdzaXplcycsIFtdKSktPnZhbHVlcygpOwogICAgJHBvaW50cyA9IGNvbGxlY3QoKGFycmF5
KSBkYXRhX2dldCgkY2hhcnQsICdwb2ludHMnLCBbXSkpLT52YWx1ZXMoKTsKICAgICRwb2ludEJ5
Q29kZSA9ICRwb2ludHMtPmtleUJ5KGZuICgkcG9pbnQpID0+IFxJbGx1bWluYXRlXFN1cHBvcnRc
U3RyOjpsb3dlcigoc3RyaW5nKSBkYXRhX2dldCgkcG9pbnQsICdjb2RlJykpKTsKICAgICRmaW5k
UG9pbnQgPSBmdW5jdGlvbiAoYXJyYXkgJG5lZWRsZXMpIHVzZSAoJHBvaW50cyk6IGFycmF5IHsK
ICAgICAgICByZXR1cm4gKGFycmF5KSAoJHBvaW50cy0+Zmlyc3QoZnVuY3Rpb24gKCRwb2ludCkg
dXNlICgkbmVlZGxlcykgewogICAgICAgICAgICAkYmxvYiA9IFxJbGx1bWluYXRlXFN1cHBvcnRc
U3RyOjpsb3dlcigKICAgICAgICAgICAgICAgIChzdHJpbmcpIGRhdGFfZ2V0KCRwb2ludCwgJ2Nv
ZGUnKS4nICcuKHN0cmluZykgZGF0YV9nZXQoJHBvaW50LCAnbGFiZWwnKQogICAgICAgICAgICAp
OwogICAgICAgICAgICByZXR1cm4gY29sbGVjdCgkbmVlZGxlcyktPmNvbnRhaW5zKGZuICgkbmVl
ZGxlKSA9PiBcSWxsdW1pbmF0ZVxTdXBwb3J0XFN0cjo6Y29udGFpbnMoJGJsb2IsICRuZWVkbGUp
KTsKICAgICAgICB9KSA/OiBbXSk7CiAgICB9OwogICAgJGVzc2VudGlhbCA9IGNvbGxlY3QoWwog
ICAgICAgICdidXN0JyA9PiAkZmluZFBvaW50KFsnYnVzdCcsICduZ+G7sWMnXSksCiAgICAgICAg
J3dhaXN0JyA9PiAkZmluZFBvaW50KFsnd2Fpc3QnLCAnZW8nXSksCiAgICAgICAgJ2hpcCcgPT4g
JGZpbmRQb2ludChbJ2hpcCcsICdtw7RuZycsICdow7RuZyddKSwKICAgICAgICAnbGVuZ3RoJyA9
PiAkZmluZFBvaW50KFsnbGVuZ3RoJywgJ2TDoGknXSksCiAgICBdKS0+ZmlsdGVyKGZuICgkcG9p
bnQpID0+ICRwb2ludCAhPT0gW10pOwogICAgJHNlbGVjdGVkU2l6ZSA9IChzdHJpbmcpICgkc2l6
ZXMtPmZpcnN0KCkgPzogJycpOwogICAgJHZhbHVlRm9yID0gZnVuY3Rpb24gKGFycmF5ICRwb2lu
dCwgc3RyaW5nICRzaXplKTogc3RyaW5nIHsKICAgICAgICAkZGlzcGxheSA9IGRhdGFfZ2V0KCRw
b2ludCwgJ2Rpc3BsYXlfdmFsdWVzLicuJHNpemUpOwogICAgICAgICRyYXcgPSBkYXRhX2dldCgk
cG9pbnQsICd2YWx1ZXMuJy4kc2l6ZSk7CiAgICAgICAgJHZhbHVlID0gJGRpc3BsYXkgIT09IG51
bGwgJiYgJGRpc3BsYXkgIT09ICcnID8gJGRpc3BsYXkgOiAkcmF3OwogICAgICAgIHJldHVybiAk
dmFsdWUgIT09IG51bGwgJiYgJHZhbHVlICE9PSAnJyA/IChzdHJpbmcpICR2YWx1ZSA6ICfigJQn
OwogICAgfTsKICAgICRsZW5ndGhQb2ludCA9IChhcnJheSkgJGVzc2VudGlhbC0+Z2V0KCdsZW5n
dGgnLCBbXSk7CiAgICAkY2hhcnRKc29uID0ganNvbl9lbmNvZGUoWwogICAgICAgICdzaXplcycg
PT4gJHNpemVzLT5hbGwoKSwKICAgICAgICAncG9pbnRzJyA9PiAkcG9pbnRzLT5hbGwoKSwKICAg
ICAgICAnZXNzZW50aWFsX2tleXMnID0+ICRlc3NlbnRpYWwtPmtleXMoKS0+YWxsKCksCiAgICBd
LCBKU09OX0hFWF9UQUcgfCBKU09OX0hFWF9BUE9TIHwgSlNPTl9IRVhfQU1QIHwgSlNPTl9IRVhf
UVVPVCB8IEpTT05fVU5FU0NBUEVEX1VOSUNPREUgfCBKU09OX1VORVNDQVBFRF9TTEFTSEVTKTsK
QGVuZHBocAoKQGlmKGRhdGFfZ2V0KCRjaGFydCwgJ3N0cnVjdHVyZWQnKSAmJiAkc2l6ZXMtPmlz
Tm90RW1wdHkoKSAmJiAkcG9pbnRzLT5pc05vdEVtcHR5KCkpCjxkaXYgY2xhc3M9Imx4cy1zaXpl
LXN0dWRpbyIgZGF0YS1seHMtcmV2ZWFsIGRhdGEtbHhwZHAtc2l6ZS1jaGFydC1zdHJ1Y3R1cmVk
PgogICAgPGRpdiBjbGFzcz0ibHhzLXNoZWxsIj4KICAgICAgICA8ZGl2IGNsYXNzPSJseHMtc2Vj
dGlvbi1oZWFkaW5nIGx4cy1zZWN0aW9uLWhlYWRpbmctLXNwbGl0IGx4cy1zZWN0aW9uLWhlYWRp
bmctLWxpZ2h0Ij4KICAgICAgICAgICAgPGRpdj4KICAgICAgICAgICAgICAgIDxwIGNsYXNzPSJs
eHMta2lja2VyIj5TaXplIFN0dWRpbzwvcD4KICAgICAgICAgICAgICAgIDxoMj5OaMOsbiBz4buR
IMSRbyBuaMawIG3hu5l0IGLhuqNuIMSR4buTLCBraMO0bmcgcGjhuqNpIG3hu5l0IGLhuqNuZyB0
w61uaC48L2gyPgogICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgPHA+Q2jhu41uIG3hu5l0
IHNpemUgxJHhu4MgeGVtIGPDoWMgxJFp4buDbSDEkW8gY2jDrW5oIHRyw6puIHBob20gc+G6o24g
cGjhuqltLiDEkMOieSBsw6Agc+G7kSDEkW8gdGjDoG5oIHBo4bqpbSwgZMO5bmcgxJHhu4Mgc28g
duG7m2kgbeG7mXQgbcOzbiDEkeG7kyDEkWFuZyBt4bq3YyB24burYS48L3A+CiAgICAgICAgPC9k
aXY+CgogICAgICAgIDxkaXYgY2xhc3M9Imx4cy1zaXplLXN0dWRpb19fc2l6ZS1jYXJkcyIgcm9s
ZT0ibGlzdCIgYXJpYS1sYWJlbD0iQ2jhu41uIHNpemUgxJHhu4MgeGVtIHPhu5EgxJFvIj4KICAg
ICAgICAgICAgQGZvcmVhY2goJHNpemVzIGFzICRpbmRleCA9PiAkc2l6ZSkKICAgICAgICAgICAg
ICAgIDxidXR0b24KICAgICAgICAgICAgICAgICAgICB0eXBlPSJidXR0b24iCiAgICAgICAgICAg
ICAgICAgICAgY2xhc3M9Int7ICRpbmRleCA9PT0gMCA/ICdpcy1hY3RpdmUnIDogJycgfX0iCiAg
ICAgICAgICAgICAgICAgICAgZGF0YS1seHMtc2l6ZS1jYXJkPSJ7eyAkc2l6ZSB9fSIKICAgICAg
ICAgICAgICAgICAgICBhcmlhLXByZXNzZWQ9Int7ICRpbmRleCA9PT0gMCA/ICd0cnVlJyA6ICdm
YWxzZScgfX0iCiAgICAgICAgICAgICAgICA+CiAgICAgICAgICAgICAgICAgICAgPHN0cm9uZz57
eyAkc2l6ZSB9fTwvc3Ryb25nPgogICAgICAgICAgICAgICAgICAgIEBpZigkbGVuZ3RoUG9pbnQp
CiAgICAgICAgICAgICAgICAgICAgICAgIDxzcGFuPnt7ICR2YWx1ZUZvcigkbGVuZ3RoUG9pbnQs
IChzdHJpbmcpICRzaXplKSB9fSB7eyBkYXRhX2dldCgkbGVuZ3RoUG9pbnQsICd1bml0JywgJ2Nt
JykgfX08L3NwYW4+CiAgICAgICAgICAgICAgICAgICAgICAgIDxzbWFsbD5jaGnhu4F1IGTDoGk8
L3NtYWxsPgogICAgICAgICAgICAgICAgICAgIEBlbHNlCiAgICAgICAgICAgICAgICAgICAgICAg
IDxzbWFsbD5YZW0gc+G7kSDEkW88L3NtYWxsPgogICAgICAgICAgICAgICAgICAgIEBlbmRpZgog
ICAgICAgICAgICAgICAgPC9idXR0b24+CiAgICAgICAgICAgIEBlbmRmb3JlYWNoCiAgICAgICAg
PC9kaXY+CgogICAgICAgIDxkaXYgY2xhc3M9Imx4cy1zaXplLXN0dWRpb19fd29ya3NwYWNlIj4K
ICAgICAgICAgICAgPGRpdiBjbGFzcz0ibHhzLXNpemUtZmlndXJlIj4KICAgICAgICAgICAgICAg
IDxzdmcgdmlld0JveD0iMCAwIDM2MCA1MjAiIHJvbGU9ImltZyIgYXJpYS1sYWJlbD0iU8ahIMSR
4buTIMSRaeG7g20gxJFvIHPhuqNuIHBo4bqpbSI+CiAgICAgICAgICAgICAgICAgICAgPGRlZnM+
CiAgICAgICAgICAgICAgICAgICAgICAgIDxsaW5lYXJHcmFkaWVudCBpZD0ibHhzRHJlc3NGaWxs
IiB4MT0iMCIgeTE9IjAiIHgyPSIxIiB5Mj0iMSI+CiAgICAgICAgICAgICAgICAgICAgICAgICAg
ICA8c3RvcCBvZmZzZXQ9IjAiIHN0b3AtY29sb3I9IiNmZmZmZmYiLz4KICAgICAgICAgICAgICAg
ICAgICAgICAgICAgIDxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iI2RmZTNmZiIvPgogICAg
ICAgICAgICAgICAgICAgICAgICA8L2xpbmVhckdyYWRpZW50PgogICAgICAgICAgICAgICAgICAg
IDwvZGVmcz4KICAgICAgICAgICAgICAgICAgICA8cGF0aCBjbGFzcz0ibHhzLXNpemUtZmlndXJl
X19kcmVzcyIgZD0iTTEzNiA2MmMxMCAxOCA3OCAxOCA4OCAwbDI0IDQyLTMyIDUwIDU1IDMwMEg4
OWw1NS0zMDAtMzItNTAgMjQtNDJaIi8+CiAgICAgICAgICAgICAgICAgICAgPHBhdGggY2xhc3M9
Imx4cy1zaXplLWZpZ3VyZV9fbmVjayIgZD0iTTE0OCA2M2M3IDIyIDU3IDIyIDY0IDAiLz4KICAg
ICAgICAgICAgICAgICAgICA8cGF0aCBjbGFzcz0ibHhzLXNpemUtZmlndXJlX19zZWFtIiBkPSJN
MTM2IDE1NGg4OE0xMTUgMjcyaDEzMCIvPgogICAgICAgICAgICAgICAgICAgIDxnIGRhdGEtbHhz
LWRpYWdyYW0tbWVhc3VyZT0iYnVzdCIgY2xhc3M9ImlzLWFjdGl2ZSI+CiAgICAgICAgICAgICAg
ICAgICAgICAgIDxwYXRoIGQ9Ik0xMDggMTY0aDE0NCIvPjxjaXJjbGUgY3g9IjEwOCIgY3k9IjE2
NCIgcj0iNSIvPjxjaXJjbGUgY3g9IjI1MiIgY3k9IjE2NCIgcj0iNSIvPjx0ZXh0IHg9IjI2MiIg
eT0iMTcwIj5BPC90ZXh0PgogICAgICAgICAgICAgICAgICAgIDwvZz4KICAgICAgICAgICAgICAg
ICAgICA8ZyBkYXRhLWx4cy1kaWFncmFtLW1lYXN1cmU9IndhaXN0Ij4KICAgICAgICAgICAgICAg
ICAgICAgICAgPHBhdGggZD0iTTEyNiAyMzJoMTA4Ii8+PGNpcmNsZSBjeD0iMTI2IiBjeT0iMjMy
IiByPSI1Ii8+PGNpcmNsZSBjeD0iMjM0IiBjeT0iMjMyIiByPSI1Ii8+PHRleHQgeD0iMjQ0IiB5
PSIyMzgiPkI8L3RleHQ+CiAgICAgICAgICAgICAgICAgICAgPC9nPgogICAgICAgICAgICAgICAg
ICAgIDxnIGRhdGEtbHhzLWRpYWdyYW0tbWVhc3VyZT0iaGlwIj4KICAgICAgICAgICAgICAgICAg
ICAgICAgPHBhdGggZD0iTTEwOCAzMDJoMTQ0Ii8+PGNpcmNsZSBjeD0iMTA4IiBjeT0iMzAyIiBy
PSI1Ii8+PGNpcmNsZSBjeD0iMjUyIiBjeT0iMzAyIiByPSI1Ii8+PHRleHQgeD0iMjYyIiB5PSIz
MDgiPkM8L3RleHQ+CiAgICAgICAgICAgICAgICAgICAgPC9nPgogICAgICAgICAgICAgICAgICAg
IDxnIGRhdGEtbHhzLWRpYWdyYW0tbWVhc3VyZT0ibGVuZ3RoIj4KICAgICAgICAgICAgICAgICAg
ICAgICAgPHBhdGggZD0iTTgyIDY0djM5MCIvPjxjaXJjbGUgY3g9IjgyIiBjeT0iNjQiIHI9IjUi
Lz48Y2lyY2xlIGN4PSI4MiIgY3k9IjQ1NCIgcj0iNSIvPjx0ZXh0IHg9IjU2IiB5PSIyNjYiPkQ8
L3RleHQ+CiAgICAgICAgICAgICAgICAgICAgPC9nPgogICAgICAgICAgICAgICAgPC9zdmc+CiAg
ICAgICAgICAgICAgICA8cD5DaOG6oW0gdsOgbyB04burbmcgZMOybmcgc+G7kSDEkW8gxJHhu4Mg
bMOgbSBu4buVaSBi4bqtdCB24buLIHRyw60gdMawxqFuZyDhu6luZy48L3A+CiAgICAgICAgICAg
IDwvZGl2PgoKICAgICAgICAgICAgPGRpdiBjbGFzcz0ibHhzLXNpemUtdmFsdWVzIj4KICAgICAg
ICAgICAgICAgIDxkaXYgY2xhc3M9Imx4cy1zaXplLXZhbHVlc19faGVhZCI+CiAgICAgICAgICAg
ICAgICAgICAgPGRpdj4KICAgICAgICAgICAgICAgICAgICAgICAgPHNtYWxsPsSQYW5nIHhlbTwv
c21hbGw+CiAgICAgICAgICAgICAgICAgICAgICAgIDxoMz5TaXplIDxzcGFuIGRhdGEtbHhzLWFj
dGl2ZS1zaXplPnt7ICRzZWxlY3RlZFNpemUgfX08L3NwYW4+PC9oMz4KICAgICAgICAgICAgICAg
ICAgICA8L2Rpdj4KICAgICAgICAgICAgICAgICAgICA8YnV0dG9uIHR5cGU9ImJ1dHRvbiIgZGF0
YS1seHMtc2l6ZS10YWJsZS1vcGVuPlNvIHPDoW5oIHThuqV0IGPhuqMgc2l6ZTwvYnV0dG9uPgog
ICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0ibHhzLXNp
emUtdmFsdWVzX19saXN0Ij4KICAgICAgICAgICAgICAgICAgICBAZm9yZWFjaCgkZXNzZW50aWFs
IGFzICRrZXkgPT4gJHBvaW50KQogICAgICAgICAgICAgICAgICAgICAgICA8YnV0dG9uCiAgICAg
ICAgICAgICAgICAgICAgICAgICAgICB0eXBlPSJidXR0b24iCiAgICAgICAgICAgICAgICAgICAg
ICAgICAgICBjbGFzcz0ie3sgJGxvb3AtPmZpcnN0ID8gJ2lzLWFjdGl2ZScgOiAnJyB9fSIKICAg
ICAgICAgICAgICAgICAgICAgICAgICAgIGRhdGEtbHhzLW1lYXN1cmUtcm93PSJ7eyAka2V5IH19
IgogICAgICAgICAgICAgICAgICAgICAgICA+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8
c3Bhbj48aT57eyBbJ2J1c3QnID0+ICdBJywgJ3dhaXN0JyA9PiAnQicsICdoaXAnID0+ICdDJywg
J2xlbmd0aCcgPT4gJ0QnXVska2V5XSA/PyAn4oCiJyB9fTwvaT57eyBkYXRhX2dldCgkcG9pbnQs
ICdsYWJlbCcpIH19PC9zcGFuPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHN0cm9uZz48
YiBkYXRhLWx4cy1tZWFzdXJlLXZhbHVlPSJ7eyAka2V5IH19Ij57eyAkdmFsdWVGb3IoKGFycmF5
KSAkcG9pbnQsICRzZWxlY3RlZFNpemUpIH19PC9iPiB7eyBkYXRhX2dldCgkcG9pbnQsICd1bml0
JywgJ2NtJykgfX08L3N0cm9uZz4KICAgICAgICAgICAgICAgICAgICAgICAgPC9idXR0b24+CiAg
ICAgICAgICAgICAgICAgICAgQGVuZGZvcmVhY2gKICAgICAgICAgICAgICAgIDwvZGl2PgoKICAg
ICAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4cy1zaXplLXZhbHVlc19fYWN0aW9ucyI+CiAgICAg
ICAgICAgICAgICAgICAgPGJ1dHRvbgogICAgICAgICAgICAgICAgICAgICAgICB0eXBlPSJidXR0
b24iCiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzPSJseHMtYnV0dG9uIGx4cy1idXR0b24t
LXByaW1hcnkiCiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGEtbHhwZHAtc2l6ZS1hZHZpc29y
LW9wZW4KICAgICAgICAgICAgICAgICAgICAgICAgQGlmKCFkYXRhX2dldCgkcGRwLCAnZml0LmFk
dmlzb3IuZW5hYmxlZCcpKSBkaXNhYmxlZCBAZW5kaWYKICAgICAgICAgICAgICAgICAgICA+S2nh
u4NtIHRyYSBzaXplIGPhu6dhIGLhuqFuPC9idXR0b24+CiAgICAgICAgICAgICAgICAgICAgPHA+
e3sgZGF0YV9nZXQoJGNoYXJ0LCAnY29tcGFyaXNvbl9ndWlkYW5jZScpID86ICdTbyBzw6FuaCB2
4bubaSBt4buZdCBz4bqjbiBwaOG6qW0gY8O5bmcgbG/huqFpIMSRYW5nIG3hurdjIHbhu6thLicg
fX08L3A+CiAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAg
PC9kaXY+CiAgICA8L2Rpdj4KCiAgICA8ZGlhbG9nIGNsYXNzPSJseHMtc2l6ZS1kaWFsb2ciIGRh
dGEtbHhzLXNpemUtdGFibGUtZGlhbG9nPgogICAgICAgIDxmb3JtIG1ldGhvZD0iZGlhbG9nIj48
YnV0dG9uIHR5cGU9InN1Ym1pdCIgYXJpYS1sYWJlbD0ixJDDs25nIGLhuqNuZyBzaXplIj7Dlzwv
YnV0dG9uPjwvZm9ybT4KICAgICAgICA8ZGl2PgogICAgICAgICAgICA8cCBjbGFzcz0ibHhzLWtp
Y2tlciI+QuG6o25nIHPhu5EgxJFvIHRow6BuaCBwaOG6qW08L3A+CiAgICAgICAgICAgIDxoMj5T
byBzw6FuaCB04bqldCBj4bqjIHNpemU8L2gyPgogICAgICAgICAgICA8ZGl2IGNsYXNzPSJseHMt
c2l6ZS1kaWFsb2dfX3RhYmxlLXdyYXAiPgogICAgICAgICAgICAgICAgPHRhYmxlPgogICAgICAg
ICAgICAgICAgICAgIDx0aGVhZD4KICAgICAgICAgICAgICAgICAgICAgICAgPHRyPgogICAgICAg
ICAgICAgICAgICAgICAgICAgICAgPHRoIHNjb3BlPSJjb2wiPsSQaeG7g20gxJFvPC90aD4KICAg
ICAgICAgICAgICAgICAgICAgICAgICAgIEBmb3JlYWNoKCRzaXplcyBhcyAkc2l6ZSk8dGggc2Nv
cGU9ImNvbCI+e3sgJHNpemUgfX08L3RoPkBlbmRmb3JlYWNoCiAgICAgICAgICAgICAgICAgICAg
ICAgIDwvdHI+CiAgICAgICAgICAgICAgICAgICAgPC90aGVhZD4KICAgICAgICAgICAgICAgICAg
ICA8dGJvZHk+CiAgICAgICAgICAgICAgICAgICAgICAgIEBmb3JlYWNoKCRwb2ludHMgYXMgJHBv
aW50KQogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRyPgogICAgICAgICAgICAgICAgICAg
ICAgICAgICAgICAgIDx0aCBzY29wZT0icm93Ij4KICAgICAgICAgICAgICAgICAgICAgICAgICAg
ICAgICAgICAge3sgZGF0YV9nZXQoJHBvaW50LCAnbGFiZWwnKSB9fQogICAgICAgICAgICAgICAg
ICAgICAgICAgICAgICAgICAgICBAaWYoZGF0YV9nZXQoJHBvaW50LCAnbm90ZScpKTxzbWFsbD57
eyBkYXRhX2dldCgkcG9pbnQsICdub3RlJykgfX08L3NtYWxsPkBlbmRpZgogICAgICAgICAgICAg
ICAgICAgICAgICAgICAgICAgIDwvdGg+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg
QGZvcmVhY2goJHNpemVzIGFzICRzaXplKQogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg
ICAgICA8dGQ+e3sgJHZhbHVlRm9yKChhcnJheSkgJHBvaW50LCAoc3RyaW5nKSAkc2l6ZSkgfX0g
PHNtYWxsPnt7IGRhdGFfZ2V0KCRwb2ludCwgJ3VuaXQnLCAnY20nKSB9fTwvc21hbGw+PC90ZD4K
ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBAZW5kZm9yZWFjaAogICAgICAgICAgICAg
ICAgICAgICAgICAgICAgPC90cj4KICAgICAgICAgICAgICAgICAgICAgICAgQGVuZGZvcmVhY2gK
ICAgICAgICAgICAgICAgICAgICA8L3Rib2R5PgogICAgICAgICAgICAgICAgPC90YWJsZT4KICAg
ICAgICAgICAgPC9kaXY+CiAgICAgICAgPC9kaXY+CiAgICA8L2RpYWxvZz4KCiAgICA8c2NyaXB0
IHR5cGU9ImFwcGxpY2F0aW9uL2pzb24iIGRhdGEtbHhzLXNpemUtY2hhcnQtZGF0YT57ISEgJGNo
YXJ0SnNvbiAhIX08L3NjcmlwdD4KPC9kaXY+CkBlbmRpZgo=
STUDIO_SIGNAL_PAYLOAD_8

decode_to_file 'resources/views/commerce_v2/pdp/studio/material-feel.blade.php' <<'STUDIO_SIGNAL_PAYLOAD_9'
QHBocAogICAgJG1haW4gPSBjb2xsZWN0KChhcnJheSkgZGF0YV9nZXQoJHBkcCwgJ3Byb2R1Y3Rf
dHJ1dGgubWF0ZXJpYWxzLm1haW4nLCBbXSkpCiAgICAgICAgLT5tYXAoZm4gKCRpdGVtKSA9PiB0
cmltKChzdHJpbmcpIChkYXRhX2dldCgkaXRlbSwgJ2ZhbWlseV9uYW1lJykgPzogZGF0YV9nZXQo
JGl0ZW0sICduYW1lJykpKSkKICAgICAgICAtPmZpbHRlcigpCiAgICAgICAgLT51bmlxdWUoKQog
ICAgICAgIC0+dmFsdWVzKCk7CiAgICAkbGluaW5nID0gY29sbGVjdCgoYXJyYXkpIGRhdGFfZ2V0
KCRwZHAsICdwcm9kdWN0X3RydXRoLm1hdGVyaWFscy5saW5pbmcnLCBbXSkpCiAgICAgICAgLT5t
YXAoZm4gKCRpdGVtKSA9PiB0cmltKChzdHJpbmcpIChkYXRhX2dldCgkaXRlbSwgJ2ZhbWlseV9u
YW1lJykgPzogZGF0YV9nZXQoJGl0ZW0sICduYW1lJykpKSkKICAgICAgICAtPmZpbHRlcigpCiAg
ICAgICAgLT51bmlxdWUoKQogICAgICAgIC0+dmFsdWVzKCk7CiAgICAkZmFjdHMgPSBjb2xsZWN0
KChhcnJheSkgZGF0YV9nZXQoJHBkcCwgJ3Byb2R1Y3RfdHJ1dGgubWF0ZXJpYWxzLnNlY3Rpb24u
aXRlbXMnLCBbXSkpCiAgICAgICAgLT5tYXAoZm4gKCRpdGVtKSA9PiBbCiAgICAgICAgICAgICds
YWJlbCcgPT4gdHJpbSgoc3RyaW5nKSBkYXRhX2dldCgkaXRlbSwgJ2xhYmVsJykpLAogICAgICAg
ICAgICAndmFsdWUnID0+IHRyaW0oKHN0cmluZykgZGF0YV9nZXQoJGl0ZW0sICd2YWx1ZScpKSwK
ICAgICAgICBdKQogICAgICAgIC0+ZmlsdGVyKGZuICgkaXRlbSkgPT4gZGF0YV9nZXQoJGl0ZW0s
ICdsYWJlbCcpICE9PSAnJyAmJiBkYXRhX2dldCgkaXRlbSwgJ3ZhbHVlJykgIT09ICcnKQogICAg
ICAgIC0+dW5pcXVlKGZuICgkaXRlbSkgPT4gXElsbHVtaW5hdGVcU3VwcG9ydFxTdHI6Omxvd2Vy
KChzdHJpbmcpIGRhdGFfZ2V0KCRpdGVtLCAnbGFiZWwnKSkpCiAgICAgICAgLT50YWtlKDYpCiAg
ICAgICAgLT52YWx1ZXMoKTsKICAgICRsYXllciA9IHRyaW0oKHN0cmluZykgZGF0YV9nZXQoJHBk
cCwgJ3Byb2R1Y3RfdHJ1dGgubWF0ZXJpYWxzLmxheWVyX2xhYmVsJykpOwpAZW5kcGhwCgpAaWYo
JG1haW4tPmlzTm90RW1wdHkoKSB8fCAkbGluaW5nLT5pc05vdEVtcHR5KCkgfHwgJGZhY3RzLT5p
c05vdEVtcHR5KCkgfHwgJGxheWVyICE9PSAnJykKPGRpdiBjbGFzcz0ibHhzLW1hdGVyaWFsLWZl
ZWwiIGRhdGEtbHhzLXJldmVhbD4KICAgIDxkaXYgY2xhc3M9Imx4cy1zaGVsbCI+CiAgICAgICAg
PGRpdiBjbGFzcz0ibHhzLXNlY3Rpb24taGVhZGluZyBseHMtc2VjdGlvbi1oZWFkaW5nLS1zcGxp
dCI+CiAgICAgICAgICAgIDxkaXY+CiAgICAgICAgICAgICAgICA8cCBjbGFzcz0ibHhzLWtpY2tl
ciI+TWF0ZXJpYWwgRmVlbDwvcD4KICAgICAgICAgICAgICAgIDxoMj5DaOG6pXQgbGnhu4d1IMSR
xrDhu6NjIG7Ds2kgYuG6sW5nIG5nw7RuIG5n4buvIGThu4UgaGnhu4N1LjwvaDI+CiAgICAgICAg
ICAgIDwvZGl2PgogICAgICAgICAgICA8cD5U4bqtcCB0cnVuZyB2w6BvIGPhuqNtIGdpw6FjIG3h
urdjLCBj4bqldSB04bqhbyBs4bubcCB2w6Agbmjhu69uZyB0aMO0bmcgdGluIHRo4buxYyBz4bux
IGjhu691IMOtY2gga2hpIGzhu7FhIGNo4buNbi48L3A+CiAgICAgICAgPC9kaXY+CgogICAgICAg
IDxkaXYgY2xhc3M9Imx4cy1tYXRlcmlhbC1mZWVsX19ncmlkIj4KICAgICAgICAgICAgQGlmKCRt
YWluLT5pc05vdEVtcHR5KCkpCiAgICAgICAgICAgICAgICA8YXJ0aWNsZSBjbGFzcz0ibHhzLW1h
dGVyaWFsLWNhcmQgbHhzLW1hdGVyaWFsLWNhcmQtLXByaW1hcnkiPgogICAgICAgICAgICAgICAg
ICAgIDxzcGFuIGNsYXNzPSJseHMtbWF0ZXJpYWwtY2FyZF9faWNvbiIgYXJpYS1oaWRkZW49InRy
dWUiPgogICAgICAgICAgICAgICAgICAgICAgICA8c3ZnIHZpZXdCb3g9IjAgMCA2NCA2NCI+PHBh
dGggZD0iTTggMTggMzIgOGwyNCAxMC0yNCAxMkw4IDE4WiIvPjxwYXRoIGQ9Im04IDMwIDI0IDEz
IDI0LTEzTTggNDJsMjQgMTQgMjQtMTQiLz48L3N2Zz4KICAgICAgICAgICAgICAgICAgICA8L3Nw
YW4+CiAgICAgICAgICAgICAgICAgICAgPHNtYWxsPlbhuqNpIGNow61uaDwvc21hbGw+CiAgICAg
ICAgICAgICAgICAgICAgPGgzPnt7ICRtYWluLT5pbXBsb2RlKCcgwrcgJykgfX08L2gzPgogICAg
ICAgICAgICAgICAgICAgIDxwPkZhbWlseSB24bqtdCBsaeG7h3UgxJHGsOG7o2MgcsO6dCBn4buN
biDEkeG7gyBi4bqhbiBk4buFIGjDrG5oIGR1bmcgY+G6pXUgdOG6oW8gY2jDrW5oIGPhu6dhIHPh
uqNuIHBo4bqpbS48L3A+CiAgICAgICAgICAgICAgICA8L2FydGljbGU+CiAgICAgICAgICAgIEBl
bmRpZgoKICAgICAgICAgICAgQGlmKCRsYXllciAhPT0gJycgfHwgJGxpbmluZy0+aXNOb3RFbXB0
eSgpKQogICAgICAgICAgICAgICAgPGFydGljbGUgY2xhc3M9Imx4cy1tYXRlcmlhbC1jYXJkIj4K
ICAgICAgICAgICAgICAgICAgICA8c3BhbiBjbGFzcz0ibHhzLW1hdGVyaWFsLWNhcmRfX2ljb24i
IGFyaWEtaGlkZGVuPSJ0cnVlIj4KICAgICAgICAgICAgICAgICAgICAgICAgPHN2ZyB2aWV3Qm94
PSIwIDAgNjQgNjQiPjxwYXRoIGQ9Ik0xOCA5aDI4bDggNDZIMTBMMTggOVoiLz48cGF0aCBkPSJN
MjEgMTloMjJNMTcgMzNoMzAiLz48L3N2Zz4KICAgICAgICAgICAgICAgICAgICA8L3NwYW4+CiAg
ICAgICAgICAgICAgICAgICAgPHNtYWxsPkPhuqV1IHThuqFvPC9zbWFsbD4KICAgICAgICAgICAg
ICAgICAgICA8aDM+e3sgJGxheWVyICE9PSAnJyA/ICRsYXllciA6ICdDw7MgbOG7m3AgbMOzdCcg
fX08L2gzPgogICAgICAgICAgICAgICAgICAgIEBpZigkbGluaW5nLT5pc05vdEVtcHR5KCkpCiAg
ICAgICAgICAgICAgICAgICAgICAgIDxwPkzhu5twIHRyb25nOiB7eyAkbGluaW5nLT5pbXBsb2Rl
KCcgwrcgJykgfX08L3A+CiAgICAgICAgICAgICAgICAgICAgQGVuZGlmCiAgICAgICAgICAgICAg
ICA8L2FydGljbGU+CiAgICAgICAgICAgIEBlbmRpZgoKICAgICAgICAgICAgQGZvcmVhY2goJGZh
Y3RzLT50YWtlKDQpIGFzICRmYWN0KQogICAgICAgICAgICAgICAgPGFydGljbGUgY2xhc3M9Imx4
cy1tYXRlcmlhbC1jYXJkIGx4cy1tYXRlcmlhbC1jYXJkLS1mYWN0Ij4KICAgICAgICAgICAgICAg
ICAgICA8c21hbGw+e3sgZGF0YV9nZXQoJGZhY3QsICdsYWJlbCcpIH19PC9zbWFsbD4KICAgICAg
ICAgICAgICAgICAgICA8aDM+e3sgZGF0YV9nZXQoJGZhY3QsICd2YWx1ZScpIH19PC9oMz4KICAg
ICAgICAgICAgICAgIDwvYXJ0aWNsZT4KICAgICAgICAgICAgQGVuZGZvcmVhY2gKICAgICAgICA8
L2Rpdj4KICAgIDwvZGl2Pgo8L2Rpdj4KQGVuZGlmCg==
STUDIO_SIGNAL_PAYLOAD_9

decode_to_file 'resources/views/commerce_v2/pdp/studio/confidence-strip.blade.php' <<'STUDIO_SIGNAL_PAYLOAD_10'
QHBocCAkcG9saWNpZXMgPSAoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdwb2xpY2llcycsIFtdKTsg
QGVuZHBocAo8ZGl2IGNsYXNzPSJseHMtc2hlbGwgbHhzLWNvbmZpZGVuY2UiIGRhdGEtbHhzLXJl
dmVhbD4KICAgIDxhcnRpY2xlPgogICAgICAgIDxzdmcgdmlld0JveD0iMCAwIDMyIDMyIiBhcmlh
LWhpZGRlbj0idHJ1ZSI+PHBhdGggZD0iTTUgOWgyMnYxNEg1ek05IDIzdjNNMjMgMjN2M00xMSAx
NmgxMCIvPjwvc3ZnPgogICAgICAgIDxkaXY+PHN0cm9uZz5DT0Qga2hpIG5o4bqtbiBow6BuZzwv
c3Ryb25nPjxzcGFuPktp4buDbSB0cmEgxJHGoW4gdsOgIHRoYW5oIHRvw6FuIHRoZW8gaMaw4bub
bmcgZOG6q24uPC9zcGFuPjwvZGl2PgogICAgPC9hcnRpY2xlPgogICAgPGFydGljbGU+CiAgICAg
ICAgPHN2ZyB2aWV3Qm94PSIwIDAgMzIgMzIiIGFyaWEtaGlkZGVuPSJ0cnVlIj48cGF0aCBkPSJN
OCA4aDE2djE2SDh6TTQgMTZhMTIgMTIgMCAwIDEgMjAtOU0yOCAxNmExMiAxMiAwIDAgMS0yMCA5
Ii8+PC9zdmc+CiAgICAgICAgPGRpdj48c3Ryb25nPnt7IGRhdGFfZ2V0KCRwb2xpY2llcywgJ2V4
Y2hhbmdlLmxhYmVsJywgJ0jhu5cgdHLhu6MgxJHhu5VpIHNpemUnKSB9fTwvc3Ryb25nPjxzcGFu
Pnt7IGRhdGFfZ2V0KCRwb2xpY2llcywgJ2V4Y2hhbmdlLm1lc3NhZ2UnKSB9fTwvc3Bhbj48L2Rp
dj4KICAgIDwvYXJ0aWNsZT4KICAgIDxhcnRpY2xlPgogICAgICAgIDxzdmcgdmlld0JveD0iMCAw
IDMyIDMyIiBhcmlhLWhpZGRlbj0idHJ1ZSI+PHBhdGggZD0iTTUgMjNoMjJNOCAyM1YxMmw4LTUg
OCA1djExTTEyIDE2aDgiLz48L3N2Zz4KICAgICAgICA8ZGl2PjxzdHJvbmc+e3sgZGF0YV9nZXQo
JHBvbGljaWVzLCAnc2hpcHBpbmcubGFiZWwnLCAnR2lhbyBow6BuZyB0b8OgbiBxdeG7kWMnKSB9
fTwvc3Ryb25nPjxzcGFuPnt7IGRhdGFfZ2V0KCRwb2xpY2llcywgJ3NoaXBwaW5nLm1lc3NhZ2Un
KSB9fTwvc3Bhbj48L2Rpdj4KICAgIDwvYXJ0aWNsZT4KICAgIDxhcnRpY2xlPgogICAgICAgIDxz
dmcgdmlld0JveD0iMCAwIDMyIDMyIiBhcmlhLWhpZGRlbj0idHJ1ZSI+PGNpcmNsZSBjeD0iMTYi
IGN5PSIxNiIgcj0iMTEiLz48cGF0aCBkPSJNMTIgMTNhNCA0IDAgMSAxIDYgM2MtMiAxLTIgMi0y
IDRNMTYgMjRoLjAxIi8+PC9zdmc+CiAgICAgICAgPGRpdj48c3Ryb25nPkjhu5cgdHLhu6MgY2jh
u41uIHNpemU8L3N0cm9uZz48c3Bhbj5N4bufIFNpemUgU3R1ZGlvIGhv4bq3YyBn4butaSBz4buR
IMSRbyDEkeG7gyDEkcaw4bujYyBn4bujaSDDvS48L3NwYW4+PC9kaXY+CiAgICA8L2FydGljbGU+
CjwvZGl2Pgo=
STUDIO_SIGNAL_PAYLOAD_10

decode_to_file 'resources/views/commerce_v2/pdp/studio/complete-look.blade.php' <<'STUDIO_SIGNAL_PAYLOAD_11'
QHBocCAkaXRlbXMgPSBjb2xsZWN0KChhcnJheSkgZGF0YV9nZXQoJHBkcCwgJ2Rpc2NvdmVyeS5y
ZWxhdGVkX3Byb2R1Y3RzJywgW10pKS0+dGFrZSgzKS0+dmFsdWVzKCk7IEBlbmRwaHAKQGlmKCRp
dGVtcy0+aXNOb3RFbXB0eSgpKQo8ZGl2IGNsYXNzPSJseHMtc2hlbGwgbHhzLWNvbXBsZXRlLWxv
b2siIGRhdGEtbHhzLXJldmVhbD4KICAgIDxkaXYgY2xhc3M9Imx4cy1zZWN0aW9uLWhlYWRpbmcg
bHhzLXNlY3Rpb24taGVhZGluZy0tc3BsaXQiPgogICAgICAgIDxkaXY+CiAgICAgICAgICAgIDxw
IGNsYXNzPSJseHMta2lja2VyIj5Db21wbGV0ZSB0aGUgbG9vazwvcD4KICAgICAgICAgICAgPGgy
Pk5o4buvbmcgbOG7sWEgY2jhu41uIGPDsyB0aOG7gyDEkWkgY8O5bmcgdGhp4bq/dCBr4bq/IG7D
oHkuPC9oMj4KICAgICAgICA8L2Rpdj4KICAgICAgICA8YSBocmVmPSJ7eyByb3V0ZSgnY29tbWVy
Y2UudjIuc2hvcCcpIH19Ij5YZW0gdG/DoG4gYuG7mSBz4bqjbiBwaOG6qW08L2E+CiAgICA8L2Rp
dj4KICAgIDxkaXYgY2xhc3M9Imx4cy1wcm9kdWN0LXJvdyI+CiAgICAgICAgQGZvcmVhY2goJGl0
ZW1zIGFzICRpdGVtKQogICAgICAgICAgICA8YSBocmVmPSJ7eyBkYXRhX2dldCgkaXRlbSwgJ3Vy
bCcpIH19IiBjbGFzcz0ibHhzLXByb2R1Y3QtY2FyZCI+CiAgICAgICAgICAgICAgICA8c3Bhbj48
aW1nIHNyYz0ie3sgZGF0YV9nZXQoJGl0ZW0sICdjb3Zlcl91cmwnKSB9fSIgYWx0PSJ7eyBkYXRh
X2dldCgkaXRlbSwgJ25hbWUnKSB9fSIgbG9hZGluZz0ibGF6eSIgZGVjb2Rpbmc9ImFzeW5jIj48
L3NwYW4+CiAgICAgICAgICAgICAgICA8c3Ryb25nPnt7IGRhdGFfZ2V0KCRpdGVtLCAnbmFtZScp
IH19PC9zdHJvbmc+CiAgICAgICAgICAgICAgICA8c21hbGw+e3sgbnVtYmVyX2Zvcm1hdCgoZmxv
YXQpIGRhdGFfZ2V0KCRpdGVtLCAncHJpY2VfbWluJyksIDAsICcsJywgJy4nKSB9feKCqzwvc21h
bGw+CiAgICAgICAgICAgIDwvYT4KICAgICAgICBAZW5kZm9yZWFjaAogICAgPC9kaXY+CjwvZGl2
PgpAZW5kaWYK
STUDIO_SIGNAL_PAYLOAD_11

decode_to_file 'resources/views/commerce_v2/pdp/studio/recently-viewed.blade.php' <<'STUDIO_SIGNAL_PAYLOAD_12'
PGRpdiBjbGFzcz0ibHhzLXNoZWxsIGx4cy1yZWNlbnQiIGRhdGEtbHhzLXJldmVhbD4KICAgIDxk
aXYgY2xhc3M9Imx4cy1zZWN0aW9uLWhlYWRpbmcgbHhzLXNlY3Rpb24taGVhZGluZy0tc3BsaXQi
PgogICAgICAgIDxkaXY+CiAgICAgICAgICAgIDxwIGNsYXNzPSJseHMta2lja2VyIj5W4burYSB4
ZW08L3A+CiAgICAgICAgICAgIDxoMj5RdWF5IGzhuqFpIG5o4buvbmcgdGhp4bq/dCBr4bq/IGLh
uqFuIMSRYW5nIGPDom4gbmjhuq9jLjwvaDI+CiAgICAgICAgPC9kaXY+CiAgICAgICAgPHAgZGF0
YS1seHMtcmVjZW50LWVtcHR5PkRhbmggc8OhY2ggc+G6vSB4deG6pXQgaGnhu4duIGtoaSBi4bqh
biDEkcOjIHhlbSB0aMOqbSBz4bqjbiBwaOG6qW0uPC9wPgogICAgPC9kaXY+CiAgICA8ZGl2IGNs
YXNzPSJseHMtcHJvZHVjdC1yb3ciIGRhdGEtbHhzLXJlY2VudC1saXN0PjwvZGl2Pgo8L2Rpdj4K
STUDIO_SIGNAL_PAYLOAD_12

decode_to_file 'resources/views/commerce_v2/pdp/studio/final-cta.blade.php' <<'STUDIO_SIGNAL_PAYLOAD_13'
QHBocAogICAgJGlkZW50aXR5ID0gKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnaWRlbnRpdHknLCBb
XSk7CiAgICAkY29tbWVyY2UgPSAoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdjb21tZXJjZScsIFtd
KTsKICAgICRjb3ZlciA9IGRhdGFfZ2V0KCRwZHAsICdjb21tZXJjZS5kZWZhdWx0X2NvbG9yLm1l
ZGlhLjAudXJsJykgPzogZGF0YV9nZXQoJHBkcCwgJ21lZGlhLmNvdmVyX3VybCcpOwpAZW5kcGhw
CjxkaXYgY2xhc3M9Imx4cy1zaGVsbCBseHMtZmluYWwtY3RhIiBkYXRhLWx4cy1yZXZlYWw+CiAg
ICA8ZGl2IGNsYXNzPSJseHMtZmluYWwtY3RhX19tZWRpYSI+CiAgICAgICAgQGlmKCRjb3Zlcik8
aW1nIHNyYz0ie3sgJGNvdmVyIH19IiBhbHQ9IiIgbG9hZGluZz0ibGF6eSIgZGVjb2Rpbmc9ImFz
eW5jIj5AZW5kaWYKICAgIDwvZGl2PgogICAgPGRpdj4KICAgICAgICA8cCBjbGFzcz0ibHhzLWtp
Y2tlciI+U+G6tW4gc8OgbmcgY2jhu41uIHBoacOqbiBi4bqjbiBj4bunYSBi4bqhbj88L3A+CiAg
ICAgICAgPGgyPnt7IGRhdGFfZ2V0KCRpZGVudGl0eSwgJ3Nob3J0X25hbWUnKSA/OiBkYXRhX2dl
dCgkaWRlbnRpdHksICduYW1lJykgfX08L2gyPgogICAgICAgIDxwPlF1YXkgbOG6oWkga2h1IHbh
u7FjIG3DoHUgdsOgIHNpemUgxJHhu4MgaG/DoG4gdOG6pXQgbOG7sWEgY2jhu41uLjwvcD4KICAg
IDwvZGl2PgogICAgPGRpdiBjbGFzcz0ibHhzLWZpbmFsLWN0YV9fYWN0aW9uIj4KICAgICAgICA8
c3Ryb25nPnt7IG51bWJlcl9mb3JtYXQoKGZsb2F0KSBkYXRhX2dldCgkY29tbWVyY2UsICdwcmlj
ZS5taW4nKSwgMCwgJywnLCAnLicpIH194oKrPC9zdHJvbmc+CiAgICAgICAgPGJ1dHRvbiB0eXBl
PSJidXR0b24iIGNsYXNzPSJseHMtYnV0dG9uIGx4cy1idXR0b24tLXByaW1hcnkiIGRhdGEtcGRw
LXNjcm9sbC10by1wdXJjaGFzZT5DaOG7jW4gbcOgdSAmYW1wOyBzaXplPC9idXR0b24+CiAgICA8
L2Rpdj4KPC9kaXY+Cg==
STUDIO_SIGNAL_PAYLOAD_13


php -l "$REGISTRY"
php -l "$SECTIONS"

if command -v node >/dev/null 2>&1; then
    node --check \
      public/commerce-v2/pdp/v1/variants/studio-signal-v1.js
    printf '%s\n' 'PDP_STUDIO_SIGNAL_JS_SYNTAX=PASS'
else
    printf '%s\n' 'PDP_STUDIO_SIGNAL_JS_SYNTAX=SKIPPED_NODE_MISSING'
fi

grep -Fq -- "'studio_signal_v1' => [" "$REGISTRY"
grep -Fq -- "'studio_hero_purchase' => [" "$SECTIONS"
grep -Fq -- 'data-lxpdp-gallery' resources/views/commerce_v2/pdp/studio/hero-purchase.blade.php
grep -Fq -- 'data-lxpdp-color' resources/views/commerce_v2/pdp/studio/hero-purchase.blade.php
grep -Fq -- 'name="sellable_sku_id"' resources/views/commerce_v2/pdp/studio/hero-purchase.blade.php
grep -Fq -- 'data-lxs-mobile-dock' resources/views/commerce_v2/pdp/studio/hero-purchase.blade.php
grep -Fq -- 'data-lxpdp-size-chart-structured' resources/views/commerce_v2/pdp/studio/size-studio.blade.php
grep -Fq -- '--lxs-primary: #5b5ff2' public/commerce-v2/pdp/v1/variants/studio-signal-v1.css
grep -Fq -- '--lxs-signal: #ff416c' public/commerce-v2/pdp/v1/variants/studio-signal-v1.css
grep -Fq -- 'grid-template-columns: repeat(4, 42px) minmax(142px, 1fr)' public/commerce-v2/pdp/v1/variants/studio-signal-v1.css
grep -Fq -- 'linxen:pdp:studio-signal-ready' public/commerce-v2/pdp/v1/variants/studio-signal-v1.js

printf '%s\n' 'PDP_STUDIO_SIGNAL_STATIC_CONTRACT=PASS'

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

if [ -f vendor/autoload.php ]; then
    artisan_safe optimize:clear
    artisan_safe view:cache
    artisan_safe view:clear

    artisan_safe commerce-v2:pdp-variant-smoke \
      --variant=studio_signal_v1

    artisan_safe commerce-v2:pdp-variant-matrix-smoke

    printf '%s\n' 'PDP_STUDIO_SIGNAL_RENDER_SMOKE=PASS'
else
    printf '%s\n' 'PDP_STUDIO_SIGNAL_RENDER_SMOKE=SKIPPED_VENDOR_MISSING'
fi

trap - ERR

printf '%s\n' 'LINXEN_PDP_STUDIO_SIGNAL_V1_SOURCE_PATCH=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'ART_DIRECTION=DIGITAL_FASHION_STUDIO'
printf '%s\n' 'PALETTE=PORCELAIN_GRAPHITE_SIGNAL_CHERRY'
printf '%s\n' 'LIVE_VARIANT=CLASSIC_UNCHANGED'
printf '%s\n' 'STUDIO_SIGNAL_V1=SIGNED_PREVIEW_READY'
printf '%s\n' 'AVAILABLE_COLORS_ONLY=PRIMARY_SELECTOR'
printf '%s\n' 'STOCK_QUANTITY_DISPLAY=REMOVED'
printf '%s\n' 'SOLD_OUT_SIZE_RECOGNITION=ENABLED'
printf '%s\n' 'SIZE_STUDIO=VISUAL_DIAGRAM_AND_COMPARISON'
printf '%s\n' 'MOBILE_COMMERCE_DOCK=SINGLE_ROW'
printf '%s\n' 'SHARED_COLOR_SIZE_CART_ENGINE=PRESERVED'
printf '%s\n' 'EXACT_SELLABLE_SKU_CONTRACT=PRESERVED'
printf '%s\n' 'ERP_SOURCE_CHANGE=NONE'
printf '%s\n' 'MIGRATION=NONE'
printf '%s\n' 'DB_MUTATION=NONE'
printf '%s\n' 'ORDER_PROVIDER_META_MUTATION=NONE'
printf '%s\n' 'NPM_BUILD=NOT_REQUIRED'
