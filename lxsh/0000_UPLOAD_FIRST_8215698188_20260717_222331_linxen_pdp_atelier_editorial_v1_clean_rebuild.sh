#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='linxen_pdp_atelier_editorial_v1_clean_rebuild'
REGISTRY='app/Services/CommerceV2/Pdp/PdpVariantRegistry.php'
SECTIONS='app/Services/CommerceV2/Pdp/PdpSectionRegistry.php'
VARIANT_MARKER='AI_PATCH_LINXEN_PDP_ATELIER_EDITORIAL_V1'
SECTION_MARKER='AI_PATCH_LINXEN_PDP_ATELIER_SECTIONS_V1'
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
        printf '%s\n' 'Có lỗi bắt buộc. Đang rollback Atelier Editorial variant...' >&2

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
  public/commerce-v2/pdp/v1/core.css \
  public/commerce-v2/pdp/v1/core.js
do
    test -f "$FILE" || {
        printf 'ERROR: Thiếu PDP presentation source: %s\n' "$FILE" >&2
        exit 1
    }
done

FILES=(
    'public/commerce-v2/pdp/v1/variants/atelier-editorial-v1.css'
    'public/commerce-v2/pdp/v1/variants/atelier-editorial-v1.js'
    'resources/views/commerce_v2/pdp/atelier/design-gestures.blade.php'
    'resources/views/commerce_v2/pdp/atelier/finale.blade.php'
    'resources/views/commerce_v2/pdp/atelier/fit-story.blade.php'
    'resources/views/commerce_v2/pdp/atelier/hero-purchase.blade.php'
    'resources/views/commerce_v2/pdp/atelier/image-ribbon.blade.php'
    'resources/views/commerce_v2/pdp/atelier/manifesto.blade.php'
    'resources/views/commerce_v2/pdp/atelier/material-story.blade.php'
    'resources/views/commerce_v2/pdp/atelier/size-story.blade.php'
    'resources/views/commerce_v2/pdp/atelier/truth-mosaic.blade.php'
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

$registryEntry = <<<'ENTRY'
/*VARIANT_ENTRY*/
ENTRY;
$registryEntry = str_replace(
    '/*VARIANT_ENTRY*/',
    "            /* {$variantMarker} */\n" . <<<'BODY'
            'atelier_editorial_v1' => [
                'key' => 'atelier_editorial_v1',
                'label' => 'Atelier Editorial V1',
                'version' => '1.0.0',
                'renderer' => 'sectioned',
                'view' => 'commerce_v2.pdp.page',
                'layout' => 'atelier_editorial_v1',
                'view_model_version' => PdpViewModelBuilder::VERSION,
                'sections' => [
                    'atelier_hero_purchase',
                    'atelier_image_ribbon',
                    'atelier_manifesto',
                    'atelier_design_gestures',
                    'atelier_fit_story',
                    'atelier_truth_mosaic',
                    'atelier_size_story',
                    'atelier_material_story',
                    'atelier_finale',
                ],
                'assets' => [
                    'styles' => [
                        'commerce-v2/pdp-sales-experience.css?v=3',
                        'commerce-v2/pdp/v1/core.css?v=1',
                        'commerce-v2/pdp/v1/variants/atelier-editorial-v1.css?v=1',
                    ],
                    'scripts' => [
                        'commerce-v2/pdp/v1/variants/atelier-editorial-v1.js?v=1',
                    ],
                ],
                'art_direction' => [
                    'concept' => 'atelier_editorial',
                    'tone' => 'modern_fashion_house',
                    'empty_sections' => 'hide',
                ],
                'enabled' => true,
            ],
BODY,
    $registryEntry
);

$sectionEntries = <<<'ENTRY'
/*SECTION_ENTRIES*/
ENTRY;
$sectionEntries = str_replace(
    '/*SECTION_ENTRIES*/',
    "            /* {$sectionMarker} */\n" . <<<'BODY'
            'atelier_hero_purchase' => [
                'view' => 'commerce_v2.pdp.atelier.hero-purchase',
                'required' => ['identity.id', 'commerce.colors'],
                'empty_behavior' => 'render',
            ],
            'atelier_image_ribbon' => [
                'view' => 'commerce_v2.pdp.atelier.image-ribbon',
                'required' => ['commerce.default_color.media'],
                'empty_behavior' => 'hide',
            ],
            'atelier_manifesto' => [
                'view' => 'commerce_v2.pdp.atelier.manifesto',
                'required' => ['identity.id'],
                'empty_behavior' => 'render',
            ],
            'atelier_design_gestures' => [
                'view' => 'commerce_v2.pdp.atelier.design-gestures',
                'required_any' => [
                    'product_truth.highlights',
                    'product_truth.design.items',
                ],
                'empty_behavior' => 'hide',
            ],
            'atelier_fit_story' => [
                'view' => 'commerce_v2.pdp.atelier.fit-story',
                'required_any' => [
                    'fit.garment_size_chart.points',
                    'fit.fit_items',
                    'commerce.default_color.media',
                ],
                'empty_behavior' => 'hide',
            ],
            'atelier_truth_mosaic' => [
                'view' => 'commerce_v2.pdp.atelier.truth-mosaic',
                'required_any' => [
                    'media.production_truth',
                    'commerce.default_color.media',
                ],
                'empty_behavior' => 'hide',
            ],
            'atelier_size_story' => [
                'view' => 'commerce_v2.pdp.atelier.size-story',
                'required' => ['fit.garment_size_chart.points'],
                'empty_behavior' => 'hide',
            ],
            'atelier_material_story' => [
                'view' => 'commerce_v2.pdp.atelier.material-story',
                'required_any' => [
                    'product_truth.materials.main',
                    'product_truth.materials.lining',
                    'product_truth.materials.section.items',
                ],
                'empty_behavior' => 'hide',
            ],
            'atelier_finale' => [
                'view' => 'commerce_v2.pdp.atelier.finale',
                'required' => ['identity.id'],
                'empty_behavior' => 'render',
            ],
BODY,
    $sectionEntries
);

$registryAnchor = "\n        ];\n    }\n\n    public function get";
$sectionAnchor = "\n        ];\n    }\n\n    public function compose";

if (! str_contains($registry, "'atelier_editorial_v1' => [")) {
    if (substr_count($registry, $registryAnchor) !== 1) {
        fwrite(STDERR, "ERROR: PDP variant registry anchor không duy nhất.\n");
        exit(1);
    }
    $registry = str_replace(
        $registryAnchor,
        "\n" . rtrim($registryEntry) . $registryAnchor,
        $registry,
        $count
    );
    if ($count !== 1) {
        fwrite(STDERR, "ERROR: Không chèn được Atelier variant.\n");
        exit(1);
    }
}

if (! str_contains($sections, "'atelier_hero_purchase' => [")) {
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
        fwrite(STDERR, "ERROR: Không chèn được Atelier sections.\n");
        exit(1);
    }
}

foreach ([
    "'atelier_editorial_v1' => [",
    "'atelier_hero_purchase'",
    "'atelier_image_ribbon'",
    "'atelier_manifesto'",
    "'atelier_design_gestures'",
    "'atelier_fit_story'",
    "'atelier_truth_mosaic'",
    "'atelier_size_story'",
    "'atelier_material_story'",
    "'atelier_finale'",
    'atelier-editorial-v1.css?v=1',
    'atelier-editorial-v1.js?v=1',
] as $required) {
    if (! str_contains($registry, $required)) {
        fwrite(STDERR, "ERROR: Thiếu Atelier variant contract: {$required}\n");
        exit(1);
    }
}

foreach ([
    "'atelier_hero_purchase' => [",
    "'atelier_image_ribbon' => [",
    "'atelier_manifesto' => [",
    "'atelier_design_gestures' => [",
    "'atelier_fit_story' => [",
    "'atelier_truth_mosaic' => [",
    "'atelier_size_story' => [",
    "'atelier_material_story' => [",
    "'atelier_finale' => [",
] as $required) {
    if (! str_contains($sections, $required)) {
        fwrite(STDERR, "ERROR: Thiếu Atelier section contract: {$required}\n");
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

echo "PDP_ATELIER_REGISTRY=APPLIED\n";
echo "PDP_ATELIER_SECTION_REGISTRY=APPLIED\n";
PHP

decode_to_file() {
    FILE="$1"
    TMP_FILE="$(mktemp "${TMPDIR:-/tmp}/linxen_atelier.XXXXXX")"
    mkdir -p "$(dirname "$FILE")"

    if printf 'Zg==' | base64 --decode >/dev/null 2>&1; then
        base64 --decode > "$TMP_FILE"
    else
        base64 -D > "$TMP_FILE"
    fi

    mv "$TMP_FILE" "$FILE"
    chmod 0644 "$FILE"
}

decode_to_file 'public/commerce-v2/pdp/v1/variants/atelier-editorial-v1.css' <<'ATELIER_PAYLOAD_1'
LyoKICogTElOIFjDiU4g4oCUIEF0ZWxpZXIgRWRpdG9yaWFsIFBEUCBWMQogKiBBIGZ1bGwgYXJ0
LWRpcmVjdGlvbiB2YXJpYW50LiBCdXNpbmVzcyBpbnRlcmFjdGlvbiByZW1haW5zIGluIHRoZSBz
aGFyZWQgUERQIGVuZ2luZS4KICovCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlh
bF92MSJdIHsKICAgIC0tbHhhLWluazogIzE5MTcxNTsKICAgIC0tbHhhLWNoYXJjb2FsOiAjMmEy
NzI1OwogICAgLS1seGEtcGFwZXI6ICNmM2VlZTc7CiAgICAtLWx4YS1jcmVhbTogI2ZiZjhmMzsK
ICAgIC0tbHhhLXdpbmU6ICM2ZTI0MzA7CiAgICAtLWx4YS13aW5lLWRlZXA6ICM0OTE2MWU7CiAg
ICAtLWx4YS1jb3BwZXI6ICNhNzY2NDg7CiAgICAtLWx4YS1zYW5kOiAjZDhjOWJhOwogICAgLS1s
eGEtbXV0ZWQ6ICM3MjZhNjQ7CiAgICAtLWx4YS1saW5lOiByZ2JhKDI1LCAyMywgMjEsIC4xNSk7
CiAgICAtLWx4YS1saWdodC1saW5lOiByZ2JhKDI1NSwgMjU1LCAyNTUsIC4xOCk7CiAgICAtLWx4
YS1jdXJyZW50LWNvbG9yOiAjNGQ0YjRjOwogICAgLS1seGEtc2VyaWY6ICJCb2RvbmkgNzIiLCBE
aWRvdCwgIklvd2FuIE9sZCBTdHlsZSIsIEJhc2tlcnZpbGxlLCAiVGltZXMgTmV3IFJvbWFuIiwg
c2VyaWY7CiAgICAtLWx4YS1zYW5zOiBJbnRlciwgdWktc2Fucy1zZXJpZiwgLWFwcGxlLXN5c3Rl
bSwgQmxpbmtNYWNTeXN0ZW1Gb250LCAiU2Vnb2UgVUkiLCBzYW5zLXNlcmlmOwogICAgd2lkdGg6
IDEwMHZ3OwogICAgbWF4LXdpZHRoOiBub25lOwogICAgbWFyZ2luLWlubGluZTogY2FsYyg1MCUg
LSA1MHZ3KTsKICAgIHBhZGRpbmc6IDAgMCAxMjRweDsKICAgIG92ZXJmbG93OiBjbGlwOwogICAg
Y29sb3I6IHZhcigtLWx4YS1pbmspOwogICAgZm9udC1mYW1pbHk6IHZhcigtLWx4YS1zYW5zKTsK
ICAgIGJhY2tncm91bmQ6IHZhcigtLWx4YS1wYXBlcik7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJh
dGVsaWVyX2VkaXRvcmlhbF92MSJdICosCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRv
cmlhbF92MSJdICo6OmJlZm9yZSwKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFs
X3YxIl0gKjo6YWZ0ZXIgewogICAgYm94LXNpemluZzogYm9yZGVyLWJveDsKfQoKW2RhdGEtcGRw
LXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gaW1nIHsKICAgIG1heC13aWR0aDogMTAw
JTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gYnV0dG9uLApb
ZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSBpbnB1dCwKW2RhdGEtcGRw
LXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gc2VsZWN0IHsKICAgIGZvbnQ6IGluaGVy
aXQ7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIGJ1dHRvbjpm
b2N1cy12aXNpYmxlLApbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSBh
OmZvY3VzLXZpc2libGUsCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJd
IFt0YWJpbmRleD0iMCJdOmZvY3VzLXZpc2libGUgewogICAgb3V0bGluZTogMnB4IHNvbGlkIHZh
cigtLWx4YS13aW5lKTsKICAgIG91dGxpbmUtb2Zmc2V0OiA0cHg7Cn0KCltkYXRhLXBkcC12YXJp
YW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seHBkcC1wcmV2aWV3LWJhbm5lciwKW2RhdGEt
cGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4cGRwX19icmVhZGNydW1iIHsK
ICAgIHdpZHRoOiBtaW4oMTAwJSAtIDQ4cHgsIDE2MDBweCk7CiAgICBtYXJnaW4taW5saW5lOiBh
dXRvOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhwZHAt
cHJldmlldy1iYW5uZXIgewogICAgbWFyZ2luLXRvcDogMTZweDsKICAgIG1hcmdpbi1ib3R0b206
IDEycHg7CiAgICBib3JkZXItY29sb3I6IHJnYmEoMTEwLCAzNiwgNDgsIC4yNik7CiAgICBib3Jk
ZXItcmFkaXVzOiAwOwogICAgYmFja2dyb3VuZDogI2ZmZjllZDsKfQoKW2RhdGEtcGRwLXZhcmlh
bnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4cGRwX19icmVhZGNydW1iIHsKICAgIHBvc2l0
aW9uOiByZWxhdGl2ZTsKICAgIHotaW5kZXg6IDEwOwogICAgbWFyZ2luLXRvcDogMThweDsKICAg
IG1hcmdpbi1ib3R0b206IDE4cHg7CiAgICBjb2xvcjogIzgyNzc2ZjsKICAgIGZvbnQtc2l6ZTog
MTFweDsKICAgIGxldHRlci1zcGFjaW5nOiAuMTJlbTsKICAgIHRleHQtdHJhbnNmb3JtOiB1cHBl
cmNhc2U7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seHBk
cC1lbmdpbmUtc2VjdGlvbiB7CiAgICBtYXJnaW46IDA7CiAgICBwYWRkaW5nOiAwOwp9CgpbZGF0
YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhwZHAtZW5naW5lLXNlY3Rp
b24tLWF0ZWxpZXJfaGVyb19wdXJjaGFzZSB7CiAgICBjb250ZW50LXZpc2liaWxpdHk6IHZpc2li
bGU7CiAgICBjb250YWluLWludHJpbnNpYy1zaXplOiBhdXRvOwp9CgpbZGF0YS1wZHAtdmFyaWFu
dD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWtpY2tlciB7CiAgICBtYXJnaW46IDA7CiAg
ICBjb2xvcjogdmFyKC0tbHhhLXdpbmUpOwogICAgZm9udC1zaXplOiAxMHB4OwogICAgZm9udC13
ZWlnaHQ6IDc4MDsKICAgIGxldHRlci1zcGFjaW5nOiAuMjRlbTsKICAgIGxpbmUtaGVpZ2h0OiAx
LjQ7CiAgICB0ZXh0LXRyYW5zZm9ybTogdXBwZXJjYXNlOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0i
YXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWtpY2tlci0tbGlnaHQgewogICAgY29sb3I6IHJn
YmEoMjU1LCAyNTUsIDI1NSwgLjYyKTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRp
dG9yaWFsX3YxIl0gLmx4YS1jaGFwdGVyIHsKICAgIHdpZHRoOiBtaW4oMTAwJSAtIDcycHgsIDE1
NDBweCk7CiAgICBtYXJnaW4taW5saW5lOiBhdXRvOwogICAgcGFkZGluZy1ibG9jazogY2xhbXAo
MTAwcHgsIDEwdncsIDE3MHB4KTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9y
aWFsX3YxIl0gW2RhdGEtbHhhLXJldmVhbF0gewogICAgb3BhY2l0eTogMDsKICAgIHRyYW5zZm9y
bTogdHJhbnNsYXRlWSg0MnB4KTsKICAgIHRyYW5zaXRpb246CiAgICAgICAgb3BhY2l0eSAuOXMg
Y3ViaWMtYmV6aWVyKC4yMiwgMSwgLjM2LCAxKSwKICAgICAgICB0cmFuc2Zvcm0gLjlzIGN1Ymlj
LWJlemllciguMjIsIDEsIC4zNiwgMSk7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2Vk
aXRvcmlhbF92MSJdIFtkYXRhLWx4YS1yZXZlYWxdLmlzLXZpc2libGUgewogICAgb3BhY2l0eTog
MTsKICAgIHRyYW5zZm9ybTogbm9uZTsKfQoKLyogU2Nyb2xsIHByb2dyZXNzICovCltkYXRhLXBk
cC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtc2Nyb2xsLXByb2dyZXNzIHsK
ICAgIHBvc2l0aW9uOiBmaXhlZDsKICAgIHotaW5kZXg6IDkwOwogICAgdG9wOiAwOwogICAgbGVm
dDogMDsKICAgIHdpZHRoOiAxMDAlOwogICAgaGVpZ2h0OiAzcHg7CiAgICBwb2ludGVyLWV2ZW50
czogbm9uZTsKICAgIGJhY2tncm91bmQ6IHRyYW5zcGFyZW50Owp9CgpbZGF0YS1wZHAtdmFyaWFu
dD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLXNjcm9sbC1wcm9ncmVzcyA+IGkgewogICAg
ZGlzcGxheTogYmxvY2s7CiAgICB3aWR0aDogMDsKICAgIGhlaWdodDogMTAwJTsKICAgIGJhY2tn
cm91bmQ6IHZhcigtLWx4YS13aW5lKTsKICAgIHRyYW5zZm9ybS1vcmlnaW46IGxlZnQgY2VudGVy
Owp9CgovKiAtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQogKiBIZXJvCiAqIC0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovCltk
YXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtaGVybyB7CiAgICBw
b3NpdGlvbjogcmVsYXRpdmU7CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ3JpZC10ZW1wbGF0ZS1j
b2x1bW5zOiBtaW5tYXgoMCwgMS41NmZyKSBtaW5tYXgoNDQwcHgsIC44NGZyKTsKICAgIHdpZHRo
OiBtaW4oMTAwJSwgMTkyMHB4KTsKICAgIG1pbi1oZWlnaHQ6IG1pbig5MDBweCwgY2FsYygxMDBz
dmggLSA3MnB4KSk7CiAgICBtYXJnaW4taW5saW5lOiBhdXRvOwogICAgYmFja2dyb3VuZDogdmFy
KC0tbHhhLWNyZWFtKTsKICAgIGJvcmRlci10b3A6IDFweCBzb2xpZCB2YXIoLS1seGEtbGluZSk7
CiAgICBib3JkZXItYm90dG9tOiAxcHggc29saWQgdmFyKC0tbHhhLWxpbmUpOwp9CgpbZGF0YS1w
ZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWhlcm9fX21lZGlhIHsKICAg
IHBvc2l0aW9uOiByZWxhdGl2ZTsKICAgIG1pbi13aWR0aDogMDsKICAgIG1pbi1oZWlnaHQ6IDgy
MHB4OwogICAgb3ZlcmZsb3c6IGhpZGRlbjsKICAgIGJhY2tncm91bmQ6ICNkOWQyY2I7Cn0KCltk
YXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtaGVyb19faXNzdWUg
ewogICAgcG9zaXRpb246IGFic29sdXRlOwogICAgei1pbmRleDogNTsKICAgIHRvcDogMjhweDsK
ICAgIHJpZ2h0OiAyOHB4OwogICAgbGVmdDogMjhweDsKICAgIGRpc3BsYXk6IGZsZXg7CiAgICBh
bGlnbi1pdGVtczogZmxleC1zdGFydDsKICAgIGp1c3RpZnktY29udGVudDogc3BhY2UtYmV0d2Vl
bjsKICAgIGNvbG9yOiAjZmZmOwogICAgdGV4dC1zaGFkb3c6IDAgMXB4IDE2cHggcmdiYSgwLCAw
LCAwLCAuMjQpOwogICAgcG9pbnRlci1ldmVudHM6IG5vbmU7Cn0KCltkYXRhLXBkcC12YXJpYW50
PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtaGVyb19faXNzdWUgc3BhbiB7CiAgICBmb250
LXNpemU6IDEwcHg7CiAgICBmb250LXdlaWdodDogODAwOwogICAgbGV0dGVyLXNwYWNpbmc6IC4y
NGVtOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWhl
cm9fX2lzc3VlIHN0cm9uZyB7CiAgICBmb250LWZhbWlseTogdmFyKC0tbHhhLXNlcmlmKTsKICAg
IGZvbnQtc2l6ZTogMzhweDsKICAgIGZvbnQtd2VpZ2h0OiA0MDA7CiAgICBsaW5lLWhlaWdodDog
Ljg7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZ2Fs
bGVyeSwKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nYWxs
ZXJ5X19zdGFnZSwKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4
YS1nYWxsZXJ5X19maWd1cmUgewogICAgd2lkdGg6IDEwMCU7CiAgICBoZWlnaHQ6IDEwMCU7CiAg
ICBtaW4taGVpZ2h0OiBpbmhlcml0Owp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0
b3JpYWxfdjEiXSAubHhhLWdhbGxlcnlfX3N0YWdlIHsKICAgIG92ZXJmbG93OiBoaWRkZW47CiAg
ICBib3JkZXItcmFkaXVzOiAwOwogICAgYmFja2dyb3VuZDogI2Q5ZDJjYjsKfQoKW2RhdGEtcGRw
LXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nYWxsZXJ5X19maWd1cmUgewog
ICAgYXNwZWN0LXJhdGlvOiBhdXRvOwogICAgYmFja2dyb3VuZDogI2Q5ZDJjYjsKfQoKW2RhdGEt
cGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nYWxsZXJ5X19maWd1cmU6
OmFmdGVyIHsKICAgIGNvbnRlbnQ6ICIiOwogICAgcG9zaXRpb246IGFic29sdXRlOwogICAgaW5z
ZXQ6IDA7CiAgICBwb2ludGVyLWV2ZW50czogbm9uZTsKICAgIGJhY2tncm91bmQ6CiAgICAgICAg
bGluZWFyLWdyYWRpZW50KDE4MGRlZywgcmdiYSg5LCA3LCA2LCAuMjIpLCB0cmFuc3BhcmVudCAy
MCUsIHRyYW5zcGFyZW50IDcwJSwgcmdiYSg5LCA3LCA2LCAuMzgpKSwKICAgICAgICBsaW5lYXIt
Z3JhZGllbnQoOTBkZWcsIHRyYW5zcGFyZW50IDY1JSwgcmdiYSg5LCA3LCA2LCAuMDgpKTsKfQoK
W2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nYWxsZXJ5X19m
aWd1cmUgPiBpbWcgewogICAgd2lkdGg6IDEwMCU7CiAgICBoZWlnaHQ6IDEwMCU7CiAgICBvYmpl
Y3QtZml0OiBjb3ZlcjsKICAgIG9iamVjdC1wb3NpdGlvbjogY2VudGVyIDI4JTsKICAgIC0tbHhh
LXNoaWZ0LXg6IDBweDsKICAgIC0tbHhhLXNoaWZ0LXk6IDBweDsKICAgIHRyYW5zZm9ybTogdHJh
bnNsYXRlM2QodmFyKC0tbHhhLXNoaWZ0LXgpLCB2YXIoLS1seGEtc2hpZnQteSksIDApIHNjYWxl
KDEuMDAyKTsKICAgIHRyYW5zaXRpb246CiAgICAgICAgb3BhY2l0eSAuMzVzIGVhc2UsCiAgICAg
ICAgdHJhbnNmb3JtIDEuMnMgY3ViaWMtYmV6aWVyKC4yMiwgMSwgLjM2LCAxKTsKICAgIHdpbGwt
Y2hhbmdlOiB0cmFuc2Zvcm07Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlh
bF92MSJdIC5seGEtZ2FsbGVyeV9fZmlndXJlLmlzLWxvYWRpbmcgPiBpbWcgewogICAgb3BhY2l0
eTogLjU2OwogICAgdHJhbnNmb3JtOiB0cmFuc2xhdGUzZCh2YXIoLS1seGEtc2hpZnQteCksIHZh
cigtLWx4YS1zaGlmdC15KSwgMCkgc2NhbGUoMS4wMjUpOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0i
YXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWdhbGxlcnlfX2NhcHRpb24gewogICAgei1pbmRl
eDogMzsKICAgIHJpZ2h0OiAyOHB4OwogICAgYm90dG9tOiAyNnB4OwogICAgbGVmdDogMjhweDsK
ICAgIHBhZGRpbmc6IDA7CiAgICBib3JkZXItcmFkaXVzOiAwOwogICAgY29sb3I6ICNmZmY7CiAg
ICBmb250LXNpemU6IDEwcHg7CiAgICBmb250LXdlaWdodDogNzAwOwogICAgbGV0dGVyLXNwYWNp
bmc6IC4xOGVtOwogICAgdGV4dC10cmFuc2Zvcm06IHVwcGVyY2FzZTsKICAgIGJhY2tncm91bmQ6
IHRyYW5zcGFyZW50OwogICAgYmFja2Ryb3AtZmlsdGVyOiBub25lOwp9CgpbZGF0YS1wZHAtdmFy
aWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWdhbGxlcnlfX25hdiB7CiAgICB6LWlu
ZGV4OiA1OwogICAgd2lkdGg6IDQ4cHg7CiAgICBoZWlnaHQ6IDQ4cHg7CiAgICBib3JkZXI6IDFw
eCBzb2xpZCByZ2JhKDI1NSwgMjU1LCAyNTUsIC40KTsKICAgIGJvcmRlci1yYWRpdXM6IDk5OXB4
OwogICAgY29sb3I6ICNmZmY7CiAgICBiYWNrZ3JvdW5kOiByZ2JhKDIwLCAxNiwgMTQsIC4xMik7
CiAgICBib3gtc2hhZG93OiBub25lOwogICAgYmFja2Ryb3AtZmlsdGVyOiBibHVyKDE0cHgpOwog
ICAgdHJhbnNpdGlvbjogYmFja2dyb3VuZCAuMnMgZWFzZSwgdHJhbnNmb3JtIC4ycyBlYXNlOwp9
CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWdhbGxlcnlf
X25hdjpob3ZlciB7CiAgICBiYWNrZ3JvdW5kOiByZ2JhKDIwLCAxNiwgMTQsIC41KTsKfQoKW2Rh
dGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nYWxsZXJ5X19uYXYt
LXByZXYgewogICAgbGVmdDogMzBweDsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRp
dG9yaWFsX3YxIl0gLmx4YS1nYWxsZXJ5X19uYXYtLW5leHQgewogICAgcmlnaHQ6IDMwcHg7Cn0K
CltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZ2FsbGVyeV9f
dGh1bWJzIHsKICAgIHBvc2l0aW9uOiBhYnNvbHV0ZTsKICAgIHotaW5kZXg6IDU7CiAgICByaWdo
dDogMjZweDsKICAgIGJvdHRvbTogNjJweDsKICAgIGRpc3BsYXk6IGZsZXg7CiAgICB3aWR0aDog
YXV0bzsKICAgIG1hcmdpbjogMDsKICAgIGdhcDogN3B4Owp9CgpbZGF0YS1wZHAtdmFyaWFudD0i
YXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWdhbGxlcnlfX3RodW1iIHsKICAgIHdpZHRoOiA0
OHB4OwogICAgZmxleDogMCAwIDQ4cHg7CiAgICBhc3BlY3QtcmF0aW86IDQgLyA1OwogICAgYm9y
ZGVyOiAxcHggc29saWQgcmdiYSgyNTUsIDI1NSwgMjU1LCAuMjgpOwogICAgYm9yZGVyLXJhZGl1
czogMDsKICAgIG9wYWNpdHk6IC42NjsKICAgIGJhY2tncm91bmQ6IHJnYmEoMCwgMCwgMCwgLjE0
KTsKICAgIHRyYW5zaXRpb246IG9wYWNpdHkgLjJzIGVhc2UsIHRyYW5zZm9ybSAuMnMgZWFzZSwg
Ym9yZGVyLWNvbG9yIC4ycyBlYXNlOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0
b3JpYWxfdjEiXSAubHhhLWdhbGxlcnlfX3RodW1iOmhvdmVyLApbZGF0YS1wZHAtdmFyaWFudD0i
YXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWdhbGxlcnlfX3RodW1iLmlzLWFjdGl2ZSB7CiAg
ICBvcGFjaXR5OiAxOwogICAgdHJhbnNmb3JtOiB0cmFuc2xhdGVZKC0zcHgpOwogICAgYm9yZGVy
LWNvbG9yOiAjZmZmOwogICAgYm94LXNoYWRvdzogbm9uZTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9
ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nYWxsZXJ5X19ub3RpY2UgewogICAgcG9zaXRp
b246IGFic29sdXRlOwogICAgei1pbmRleDogNjsKICAgIGluc2V0OiBhdXRvIDI4cHggMjhweDsK
ICAgIGJvcmRlci1jb2xvcjogcmdiYSgyNTUsIDI1NSwgMjU1LCAuMyk7CiAgICBib3JkZXItcmFk
aXVzOiAwOwogICAgY29sb3I6ICNmZmY7CiAgICBiYWNrZ3JvdW5kOiByZ2JhKDI1LCAyMywgMjEs
IC43Mik7CiAgICBiYWNrZHJvcC1maWx0ZXI6IGJsdXIoMTZweCk7Cn0KCltkYXRhLXBkcC12YXJp
YW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtYnV5IHsKICAgIHBvc2l0aW9uOiBzdGlj
a3k7CiAgICB0b3A6IDA7CiAgICBhbGlnbi1zZWxmOiBzdGFydDsKICAgIGRpc3BsYXk6IGZsZXg7
CiAgICBmbGV4LWRpcmVjdGlvbjogY29sdW1uOwogICAgZ2FwOiAwOwogICAgbWluLWhlaWdodDog
bWluKDkwMHB4LCBjYWxjKDEwMHN2aCAtIDcycHgpKTsKICAgIHBhZGRpbmc6IGNsYW1wKDU2cHgs
IDUuMnZ3LCA5OHB4KSBjbGFtcCgzOHB4LCA0LjZ2dywgODJweCkgNTRweDsKICAgIGJhY2tncm91
bmQ6CiAgICAgICAgcmFkaWFsLWdyYWRpZW50KGNpcmNsZSBhdCA5MCUgOCUsIHJnYmEoMTEwLCAz
NiwgNDgsIC4wNyksIHRyYW5zcGFyZW50IDM1JSksCiAgICAgICAgdmFyKC0tbHhhLWNyZWFtKTsK
fQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1idXk6OmJl
Zm9yZSB7CiAgICBjb250ZW50OiAiIjsKICAgIHdpZHRoOiA1NHB4OwogICAgaGVpZ2h0OiAxcHg7
CiAgICBtYXJnaW4tYm90dG9tOiAzNHB4OwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhhLXdpbmUp
Owp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWJ1eV9f
aGVhZCBoMSB7CiAgICBtYXgtd2lkdGg6IDljaDsKICAgIG1hcmdpbjogMTBweCAwIDA7CiAgICBm
b250LWZhbWlseTogdmFyKC0tbHhhLXNlcmlmKTsKICAgIGZvbnQtc2l6ZTogY2xhbXAoNjJweCwg
NS4zdncsIDEwNHB4KTsKICAgIGZvbnQtd2VpZ2h0OiA0MDA7CiAgICBsaW5lLWhlaWdodDogLjgy
OwogICAgbGV0dGVyLXNwYWNpbmc6IC0uMDY1ZW07Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVs
aWVyX2VkaXRvcmlhbF92MSJdIC5seGEtYnV5X19kZXNjcmlwdG9yIHsKICAgIG1heC13aWR0aDog
NTIwcHg7CiAgICBtYXJnaW46IDI2cHggMCAwOwogICAgY29sb3I6IHZhcigtLWx4YS1jaGFyY29h
bCk7CiAgICBmb250LWZhbWlseTogdmFyKC0tbHhhLXNlcmlmKTsKICAgIGZvbnQtc2l6ZTogY2xh
bXAoMjBweCwgMS40NXZ3LCAyOHB4KTsKICAgIGxpbmUtaGVpZ2h0OiAxLjI1Owp9CgpbZGF0YS1w
ZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWJ1eV9fZGVjayB7CiAgICBt
YXgtd2lkdGg6IDU0MHB4OwogICAgbWFyZ2luOiAxOHB4IDAgMDsKICAgIGNvbG9yOiB2YXIoLS1s
eGEtbXV0ZWQpOwogICAgZm9udC1zaXplOiAxM3B4OwogICAgbGluZS1oZWlnaHQ6IDEuNzU7Cn0K
CltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtcHJpY2Utcm93
IHsKICAgIGRpc3BsYXk6IGZsZXg7CiAgICBhbGlnbi1pdGVtczogZW5kOwogICAganVzdGlmeS1j
b250ZW50OiBzcGFjZS1iZXR3ZWVuOwogICAgZ2FwOiAxOHB4OwogICAgbWFyZ2luLXRvcDogMzJw
eDsKICAgIHBhZGRpbmctYmxvY2s6IDIycHg7CiAgICBib3JkZXItdG9wOiAxcHggc29saWQgdmFy
KC0tbHhhLWxpbmUpOwogICAgYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkIHZhcigtLWx4YS1saW5l
KTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1wcmlj
ZS1yb3cgLmx4cGRwX19wcmljZSB7CiAgICBtYXJnaW46IDA7Cn0KCltkYXRhLXBkcC12YXJpYW50
PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtcHJpY2Utcm93IC5seHBkcF9fcHJpY2Ugc3Ry
b25nIHsKICAgIGZvbnQtZmFtaWx5OiB2YXIoLS1seGEtc2VyaWYpOwogICAgZm9udC1zaXplOiBj
bGFtcCgyOHB4LCAyLjF2dywgNDBweCk7CiAgICBmb250LXdlaWdodDogNDAwOwp9CgpbZGF0YS1w
ZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLXByaWNlLXJvdyAubHhwZHBf
X3ByaWNlIGRlbCB7CiAgICBmb250LXNpemU6IDEzcHg7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJh
dGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtc3RvY2sgewogICAgZGlzcGxheTogaW5saW5lLWZs
ZXg7CiAgICBhbGlnbi1pdGVtczogY2VudGVyOwogICAgZ2FwOiA4cHg7CiAgICBtYXJnaW46IDA7
CiAgICBjb2xvcjogdmFyKC0tbHhhLW11dGVkKTsKICAgIGZvbnQtc2l6ZTogMTBweDsKICAgIGZv
bnQtd2VpZ2h0OiA3NTA7CiAgICBsZXR0ZXItc3BhY2luZzogLjEzZW07CiAgICB0ZXh0LXRyYW5z
Zm9ybTogdXBwZXJjYXNlOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxf
djEiXSAubHhhLXN0b2NrIGkgewogICAgd2lkdGg6IDdweDsKICAgIGhlaWdodDogN3B4OwogICAg
Ym9yZGVyLXJhZGl1czogNTAlOwogICAgYmFja2dyb3VuZDogI2EwNDQ0NDsKfQoKW2RhdGEtcGRw
LXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1zdG9jay5pcy1pbiBpIHsKICAg
IGJhY2tncm91bmQ6ICM0ZDc5NTg7CiAgICBib3gtc2hhZG93OiAwIDAgMCA1cHggcmdiYSg3Nywg
MTIxLCA4OCwgLjEpOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEi
XSAubHhhLXNlbGVjdG9yIHsKICAgIGdhcDogMTRweDsKICAgIG1hcmdpbi10b3A6IDI0cHg7Cn0K
CltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtc2VsZWN0b3Jf
X2hlYWQgewogICAgZGlzcGxheTogZmxleDsKICAgIGFsaWduLWl0ZW1zOiBiYXNlbGluZTsKICAg
IGp1c3RpZnktY29udGVudDogc3BhY2UtYmV0d2VlbjsKICAgIGdhcDogMTRweDsKfQoKW2RhdGEt
cGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1zZWxlY3Rvcl9faGVhZCBo
MiB7CiAgICBtYXJnaW46IDA7CiAgICBmb250LXNpemU6IDEwcHg7CiAgICBmb250LXdlaWdodDog
ODAwOwogICAgbGV0dGVyLXNwYWNpbmc6IC4xOGVtOwogICAgdGV4dC10cmFuc2Zvcm06IHVwcGVy
Y2FzZTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1z
ZWxlY3Rvcl9faGVhZCA+IHNwYW4gewogICAgY29sb3I6IHZhcigtLWx4YS1tdXRlZCk7CiAgICBm
b250LWZhbWlseTogdmFyKC0tbHhhLXNlcmlmKTsKICAgIGZvbnQtc2l6ZTogMTZweDsKICAgIGZv
bnQtc3R5bGU6IGl0YWxpYzsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFs
X3YxIl0gLmx4YS1jb2xvci1saXN0IHsKICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBncmlkLXRlbXBs
YXRlLWNvbHVtbnM6IHJlcGVhdCg1LCBtaW5tYXgoMCwgMWZyKSk7CiAgICBnYXA6IDhweDsKfQoK
W2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1jb2xvciB7CiAg
ICBwb3NpdGlvbjogcmVsYXRpdmU7CiAgICBkaXNwbGF5OiBibG9jazsKICAgIG1pbi13aWR0aDog
MDsKICAgIG1pbi1oZWlnaHQ6IDA7CiAgICBwYWRkaW5nOiAwIDAgOXB4OwogICAgYm9yZGVyOiAw
OwogICAgYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkIHRyYW5zcGFyZW50OwogICAgYm9yZGVyLXJh
ZGl1czogMDsKICAgIHRleHQtYWxpZ246IGxlZnQ7CiAgICBjb2xvcjogaW5oZXJpdDsKICAgIGJh
Y2tncm91bmQ6IHRyYW5zcGFyZW50Owp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0
b3JpYWxfdjEiXSAubHhhLWNvbG9yOmhvdmVyLApbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9l
ZGl0b3JpYWxfdjEiXSAubHhhLWNvbG9yOmZvY3VzLXZpc2libGUgewogICAgYm9yZGVyLWNvbG9y
OiB2YXIoLS1seGEtc2FuZCk7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlh
bF92MSJdIC5seGEtY29sb3IuaXMtYWN0aXZlIHsKICAgIGJvcmRlci1jb2xvcjogdmFyKC0tbHhh
LWluayk7CiAgICBib3gtc2hhZG93OiBub25lOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGll
cl9lZGl0b3JpYWxfdjEiXSAubHhhLWNvbG9yW2RhdGEtY29sb3Itc2VsbGFibGU9IjAiXSB7CiAg
ICBvcGFjaXR5OiAuNTsKICAgIGJhY2tncm91bmQ6IHRyYW5zcGFyZW50Owp9CgpbZGF0YS1wZHAt
dmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWNvbG9yX192aXN1YWwgewogICAg
ZGlzcGxheTogYmxvY2s7CiAgICBvdmVyZmxvdzogaGlkZGVuOwogICAgd2lkdGg6IDEwMCU7CiAg
ICBoZWlnaHQ6IGF1dG87CiAgICBhc3BlY3QtcmF0aW86IDQgLyA1OwogICAgYm9yZGVyLXJhZGl1
czogMDsKICAgIGJhY2tncm91bmQ6ICNlNGRkZDY7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVs
aWVyX2VkaXRvcmlhbF92MSJdIC5seGEtY29sb3JfX3Zpc3VhbCBpbWcsCltkYXRhLXBkcC12YXJp
YW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtY29sb3JfX3Zpc3VhbCBpIHsKICAgIGRp
c3BsYXk6IGJsb2NrOwogICAgd2lkdGg6IDEwMCU7CiAgICBoZWlnaHQ6IDEwMCU7CiAgICBvYmpl
Y3QtZml0OiBjb3ZlcjsKICAgIGJhY2tncm91bmQ6IHZhcigtLWx4YS1zd2F0Y2gpOwogICAgdHJh
bnNpdGlvbjogdHJhbnNmb3JtIC41NXMgY3ViaWMtYmV6aWVyKC4yMiwgMSwgLjM2LCAxKTsKfQoK
W2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1jb2xvcjpob3Zl
ciAubHhhLWNvbG9yX192aXN1YWwgaW1nIHsKICAgIHRyYW5zZm9ybTogc2NhbGUoMS4wNDUpOwp9
CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWNvbG9yX19j
b3B5IHsKICAgIGRpc3BsYXk6IGJsb2NrOwogICAgcGFkZGluZy10b3A6IDhweDsKfQoKW2RhdGEt
cGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1jb2xvcl9fY29weSBzdHJv
bmcsCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtY29sb3Jf
X2NvcHkgc21hbGwgewogICAgZGlzcGxheTogYmxvY2s7CiAgICBvdmVyZmxvdzogaGlkZGVuOwog
ICAgdGV4dC1vdmVyZmxvdzogZWxsaXBzaXM7CiAgICB3aGl0ZS1zcGFjZTogbm93cmFwOwp9Cgpb
ZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWNvbG9yX19jb3B5
IHN0cm9uZyB7CiAgICBmb250LXNpemU6IDEwcHg7CiAgICBmb250LXdlaWdodDogNzUwOwp9Cgpb
ZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWNvbG9yX19jb3B5
IHNtYWxsIHsKICAgIG1hcmdpbi10b3A6IDNweDsKICAgIGNvbG9yOiB2YXIoLS1seGEtbXV0ZWQp
OwogICAgZm9udC1zaXplOiA5cHg7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRv
cmlhbF92MSJdIC5seGEtZml0LWxpbmsgewogICAgY29sb3I6IHZhcigtLWx4YS13aW5lKTsKICAg
IGZvbnQtc2l6ZTogMTFweDsKICAgIGZvbnQtd2VpZ2h0OiA3NTA7CiAgICB0ZXh0LWRlY29yYXRp
b246IG5vbmU7CiAgICBib3JkZXItYm90dG9tOiAxcHggc29saWQgY3VycmVudENvbG9yOwp9Cgpb
ZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLXNpemUtbGlzdCB7
CiAgICBnYXA6IDdweDsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3Yx
Il0gLmx4YS1zaXplLWxpc3QgLmx4cGRwLXNpemUtYnV0dG9uIHsKICAgIG1pbi13aWR0aDogNDhw
eDsKICAgIG1pbi1oZWlnaHQ6IDQ2cHg7CiAgICBib3JkZXItY29sb3I6IHZhcigtLWx4YS1saW5l
KTsKICAgIGJvcmRlci1yYWRpdXM6IDA7CiAgICBmb250LXNpemU6IDEycHg7CiAgICBiYWNrZ3Jv
dW5kOiB0cmFuc3BhcmVudDsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFs
X3YxIl0gLmx4YS1zaXplLWxpc3QgLmx4cGRwLXNpemUtYnV0dG9uOmhvdmVyOm5vdCg6ZGlzYWJs
ZWQpIHsKICAgIGJvcmRlci1jb2xvcjogdmFyKC0tbHhhLWluayk7Cn0KCltkYXRhLXBkcC12YXJp
YW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtc2l6ZS1saXN0IC5seHBkcC1zaXplLWJ1
dHRvbi5pcy1hY3RpdmUgewogICAgY29sb3I6ICNmZmY7CiAgICBiYWNrZ3JvdW5kOiB2YXIoLS1s
eGEtaW5rKTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4
YS1zZWxlY3Rpb24gewogICAgbWFyZ2luLXRvcDogM3B4OwogICAgZm9udC1zaXplOiAxMXB4Owp9
CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWNhcnQgewog
ICAgbWFyZ2luLXRvcDogMjJweDsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9y
aWFsX3YxIl0gLmx4YS1idXktYnV0dG9uIHsKICAgIHBvc2l0aW9uOiByZWxhdGl2ZTsKICAgIG92
ZXJmbG93OiBoaWRkZW47CiAgICBtaW4taGVpZ2h0OiA2MHB4OwogICAgYm9yZGVyOiAwOwogICAg
Ym9yZGVyLXJhZGl1czogMDsKICAgIGNvbG9yOiAjZmZmOwogICAgZm9udC1zaXplOiAxMXB4Owog
ICAgZm9udC13ZWlnaHQ6IDgyMDsKICAgIGxldHRlci1zcGFjaW5nOiAuMTRlbTsKICAgIHRleHQt
dHJhbnNmb3JtOiB1cHBlcmNhc2U7CiAgICBiYWNrZ3JvdW5kOiB2YXIoLS1seGEtd2luZSk7CiAg
ICB0cmFuc2l0aW9uOiBiYWNrZ3JvdW5kIC4yNXMgZWFzZSwgdHJhbnNmb3JtIC4yNXMgZWFzZTsK
fQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1idXktYnV0
dG9uOjpiZWZvcmUgewogICAgY29udGVudDogIiI7CiAgICBwb3NpdGlvbjogYWJzb2x1dGU7CiAg
ICBpbnNldDogMDsKICAgIHRyYW5zZm9ybTogdHJhbnNsYXRlWCgtMTAyJSk7CiAgICBiYWNrZ3Jv
dW5kOiB2YXIoLS1seGEtaW5rKTsKICAgIHRyYW5zaXRpb246IHRyYW5zZm9ybSAuNTVzIGN1Ymlj
LWJlemllciguMjIsIDEsIC4zNiwgMSk7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2Vk
aXRvcmlhbF92MSJdIC5seGEtYnV5LWJ1dHRvbjpub3QoOmRpc2FibGVkKTpob3Zlcjo6YmVmb3Jl
IHsKICAgIHRyYW5zZm9ybTogdHJhbnNsYXRlWCgwKTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0
ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1idXktYnV0dG9uOm5vdCg6ZGlzYWJsZWQpOmFjdGl2
ZSB7CiAgICB0cmFuc2Zvcm06IHRyYW5zbGF0ZVkoMXB4KTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9
ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1idXktYnV0dG9uIHsKICAgIGlzb2xhdGlvbjog
aXNvbGF0ZTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4
YS1idXktYnV0dG9uOjpiZWZvcmUgewogICAgei1pbmRleDogMDsKfQoKW2RhdGEtcGRwLXZhcmlh
bnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1hc3N1cmFuY2UgewogICAgZGlzcGxheTog
Z3JpZDsKICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogcmVwZWF0KDMsIG1pbm1heCgwLCAxZnIp
KTsKICAgIGdhcDogMDsKICAgIG1hcmdpbi10b3A6IDE4cHg7CiAgICBib3JkZXItdG9wOiAxcHgg
c29saWQgdmFyKC0tbHhhLWxpbmUpOwogICAgYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkIHZhcigt
LWx4YS1saW5lKTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0g
Lmx4YS1hc3N1cmFuY2UgPiBkaXYgewogICAgbWluLXdpZHRoOiAwOwogICAgcGFkZGluZzogMTRw
eCA5cHggMTRweCAwOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEi
XSAubHhhLWFzc3VyYW5jZSA+IGRpdiArIGRpdiB7CiAgICBwYWRkaW5nLWxlZnQ6IDExcHg7CiAg
ICBib3JkZXItbGVmdDogMXB4IHNvbGlkIHZhcigtLWx4YS1saW5lKTsKfQoKW2RhdGEtcGRwLXZh
cmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1hc3N1cmFuY2Ugc3Ryb25nLApbZGF0
YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWFzc3VyYW5jZSBzcGFu
IHsKICAgIGRpc3BsYXk6IGJsb2NrOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0
b3JpYWxfdjEiXSAubHhhLWFzc3VyYW5jZSBzdHJvbmcgewogICAgZm9udC1zaXplOiAxMHB4Owog
ICAgbGV0dGVyLXNwYWNpbmc6IC4wOGVtOwogICAgdGV4dC10cmFuc2Zvcm06IHVwcGVyY2FzZTsK
fQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1hc3N1cmFu
Y2Ugc3BhbiB7CiAgICBtYXJnaW4tdG9wOiA1cHg7CiAgICBjb2xvcjogdmFyKC0tbHhhLW11dGVk
KTsKICAgIGZvbnQtc2l6ZTogOXB4OwogICAgbGluZS1oZWlnaHQ6IDEuNDsKfQoKW2RhdGEtcGRw
LXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1taW5pLWZhY3RzIHsKICAgIGRp
c3BsYXk6IGdyaWQ7CiAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IHJlcGVhdCgzLCBtaW5tYXgo
MCwgMWZyKSk7CiAgICBnYXA6IDEycHg7CiAgICBtYXJnaW46IDIwcHggMCAwOwp9CgpbZGF0YS1w
ZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1pbmktZmFjdHMgZGl2IHsK
ICAgIG1pbi13aWR0aDogMDsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFs
X3YxIl0gLmx4YS1taW5pLWZhY3RzIGR0IHsKICAgIGNvbG9yOiB2YXIoLS1seGEtbXV0ZWQpOwog
ICAgZm9udC1zaXplOiA5cHg7CiAgICBsZXR0ZXItc3BhY2luZzogLjFlbTsKICAgIHRleHQtdHJh
bnNmb3JtOiB1cHBlcmNhc2U7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlh
bF92MSJdIC5seGEtbWluaS1mYWN0cyBkZCB7CiAgICBtYXJnaW46IDVweCAwIDA7CiAgICBmb250
LWZhbWlseTogdmFyKC0tbHhhLXNlcmlmKTsKICAgIGZvbnQtc2l6ZTogMTRweDsKfQoKLyogLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0KICogSW1hZ2UgcmliYm9uCiAqIC0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovCltkYXRh
LXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZmlsbSB7CiAgICBkaXNw
bGF5OiBncmlkOwogICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiAxLjE1ZnIgLjc4ZnIgLjk1ZnIg
Ljc4ZnIgMS4wNWZyOwogICAgYWxpZ24taXRlbXM6IHN0YXJ0OwogICAgZ2FwOiAxMHB4OwogICAg
d2lkdGg6IDEwMCU7CiAgICBwYWRkaW5nOiAxMHB4OwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhh
LWluayk7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEt
ZmlsbV9faXRlbSB7CiAgICBwb3NpdGlvbjogcmVsYXRpdmU7CiAgICBvdmVyZmxvdzogaGlkZGVu
OwogICAgaGVpZ2h0OiBjbGFtcCg0NDBweCwgNjF2dywgOTAwcHgpOwogICAgbWFyZ2luOiAwOwog
ICAgYmFja2dyb3VuZDogIzI4MjMyMTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRp
dG9yaWFsX3YxIl0gLmx4YS1maWxtX19pdGVtOm50aC1jaGlsZCgyKSwKW2RhdGEtcGRwLXZhcmlh
bnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maWxtX19pdGVtOm50aC1jaGlsZCg0KSB7
CiAgICBoZWlnaHQ6IGNsYW1wKDM2MHB4LCA0OXZ3LCA3NDBweCk7CiAgICBtYXJnaW4tdG9wOiBj
bGFtcCg1MHB4LCA2dncsIDEwMHB4KTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRp
dG9yaWFsX3YxIl0gLmx4YS1maWxtX19pdGVtIGltZyB7CiAgICB3aWR0aDogMTAwJTsKICAgIGhl
aWdodDogMTAwJTsKICAgIG9iamVjdC1maXQ6IGNvdmVyOwogICAgdHJhbnNpdGlvbjogdHJhbnNm
b3JtIDEuMnMgY3ViaWMtYmV6aWVyKC4yMiwgMSwgLjM2LCAxKSwgZmlsdGVyIC40cyBlYXNlOwp9
CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZpbG1fX2l0
ZW06aG92ZXIgaW1nIHsKICAgIHRyYW5zZm9ybTogc2NhbGUoMS4wNCk7CiAgICBmaWx0ZXI6IHNh
dHVyYXRlKDEuMDUpOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEi
XSAubHhhLWZpbG1fX2l0ZW0gZmlnY2FwdGlvbiB7CiAgICBwb3NpdGlvbjogYWJzb2x1dGU7CiAg
ICByaWdodDogMTZweDsKICAgIGJvdHRvbTogMTZweDsKICAgIGxlZnQ6IDE2cHg7CiAgICBkaXNw
bGF5OiBmbGV4OwogICAgYWxpZ24taXRlbXM6IGVuZDsKICAgIGp1c3RpZnktY29udGVudDogc3Bh
Y2UtYmV0d2VlbjsKICAgIGdhcDogMTJweDsKICAgIGNvbG9yOiAjZmZmOwogICAgdGV4dC1zaGFk
b3c6IDAgMnB4IDE1cHggcmdiYSgwLCAwLCAwLCAuMzUpOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0i
YXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZpbG1fX2l0ZW0gZmlnY2FwdGlvbiBzcGFuIHsK
ICAgIGZvbnQtZmFtaWx5OiB2YXIoLS1seGEtc2VyaWYpOwogICAgZm9udC1zaXplOiAyNHB4Owp9
CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZpbG1fX2l0
ZW0gZmlnY2FwdGlvbiBzdHJvbmcgewogICAgZm9udC1zaXplOiA5cHg7CiAgICBsZXR0ZXItc3Bh
Y2luZzogLjE3ZW07CiAgICB0ZXh0LWFsaWduOiByaWdodDsKICAgIHRleHQtdHJhbnNmb3JtOiB1
cHBlcmNhc2U7Cn0KCi8qIC0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCiAqIE1hbmlmZXN0bwogKiAtLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLSAqLwpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhh
LW1hbmlmZXN0byB7CiAgICBwb3NpdGlvbjogcmVsYXRpdmU7CiAgICBkaXNwbGF5OiBncmlkOwog
ICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiBtaW5tYXgoMTkwcHgsIC40NWZyKSBtaW5tYXgoMCwg
MWZyKTsKICAgIGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAgICBtaW4taGVpZ2h0OiA3MjBweDsKICAg
IHBhZGRpbmc6IGNsYW1wKDgwcHgsIDEwdncsIDE4MHB4KSBtYXgoMzZweCwgY2FsYygoMTAwdncg
LSAxNTQwcHgpIC8gMikpOwogICAgb3ZlcmZsb3c6IGhpZGRlbjsKICAgIGNvbG9yOiAjZmZmOwog
ICAgYmFja2dyb3VuZDoKICAgICAgICByYWRpYWwtZ3JhZGllbnQoY2lyY2xlIGF0IDg0JSAxMiUs
IHJnYmEoMTUwLCA3MywgNzAsIC4yNSksIHRyYW5zcGFyZW50IDM2JSksCiAgICAgICAgdmFyKC0t
bHhhLWluayk7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5s
eGEtbWFuaWZlc3RvOjphZnRlciB7CiAgICBjb250ZW50OiAiIjsKICAgIHBvc2l0aW9uOiBhYnNv
bHV0ZTsKICAgIHdpZHRoOiAzNnZ3OwogICAgaGVpZ2h0OiAzNnZ3OwogICAgcmlnaHQ6IC0xMHZ3
OwogICAgYm90dG9tOiAtMjJ2dzsKICAgIGJvcmRlcjogMXB4IHNvbGlkIHJnYmEoMjU1LCAyNTUs
IDI1NSwgLjExKTsKICAgIGJvcmRlci1yYWRpdXM6IDUwJTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9
ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1tYW5pZmVzdG9fX251bWJlciB7CiAgICBwb3Np
dGlvbjogcmVsYXRpdmU7CiAgICB6LWluZGV4OiAxOwogICAgYWxpZ24tc2VsZjogc3RhcnQ7CiAg
ICBjb2xvcjogdHJhbnNwYXJlbnQ7CiAgICBmb250LWZhbWlseTogdmFyKC0tbHhhLXNlcmlmKTsK
ICAgIGZvbnQtc2l6ZTogY2xhbXAoMTUwcHgsIDE4dncsIDM2MHB4KTsKICAgIGxpbmUtaGVpZ2h0
OiAuNzQ7CiAgICAtd2Via2l0LXRleHQtc3Ryb2tlOiAxcHggcmdiYSgyNTUsIDI1NSwgMjU1LCAu
MTcpOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1h
bmlmZXN0b19fY29weSB7CiAgICBwb3NpdGlvbjogcmVsYXRpdmU7CiAgICB6LWluZGV4OiAyOwog
ICAgbWF4LXdpZHRoOiAxMDQwcHg7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRv
cmlhbF92MSJdIC5seGEtbWFuaWZlc3RvX19jb3B5IGgyIHsKICAgIG1hcmdpbjogMjJweCAwIDA7
CiAgICBmb250LWZhbWlseTogdmFyKC0tbHhhLXNlcmlmKTsKICAgIGZvbnQtc2l6ZTogY2xhbXAo
NThweCwgNy40dncsIDEzNnB4KTsKICAgIGZvbnQtd2VpZ2h0OiA0MDA7CiAgICBsaW5lLWhlaWdo
dDogLjk7CiAgICBsZXR0ZXItc3BhY2luZzogLS4wNjVlbTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9
ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1tYW5pZmVzdG9fX2NvcHkgPiBwOmxhc3QtY2hp
bGQgewogICAgbWF4LXdpZHRoOiA2NDBweDsKICAgIG1hcmdpbjogNDBweCAwIDAgYXV0bzsKICAg
IGNvbG9yOiByZ2JhKDI1NSwgMjU1LCAyNTUsIC42Mik7CiAgICBmb250LXNpemU6IDE0cHg7CiAg
ICBsaW5lLWhlaWdodDogMS45Owp9CgovKiAtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQogKiBHZXN0dXJlcwog
KiAtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLSAqLwpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxf
djEiXSAubHhhLWNoYXB0ZXJfX2hlYWQgewogICAgZGlzcGxheTogZ3JpZDsKICAgIGdyaWQtdGVt
cGxhdGUtY29sdW1uczogbWlubWF4KDE0MHB4LCAuMzVmcikgbWlubWF4KDAsIDFmcik7CiAgICBn
YXA6IGNsYW1wKDI4cHgsIDV2dywgOTBweCk7CiAgICBhbGlnbi1pdGVtczogc3RhcnQ7CiAgICBt
YXJnaW4tYm90dG9tOiBjbGFtcCg1MnB4LCA3dncsIDEwMHB4KTsKfQoKW2RhdGEtcGRwLXZhcmlh
bnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1jaGFwdGVyX19oZWFkIGgyIHsKICAgIG1h
eC13aWR0aDogOTIwcHg7CiAgICBtYXJnaW46IDA7CiAgICBmb250LWZhbWlseTogdmFyKC0tbHhh
LXNlcmlmKTsKICAgIGZvbnQtc2l6ZTogY2xhbXAoNDhweCwgNnZ3LCAxMDRweCk7CiAgICBmb250
LXdlaWdodDogNDAwOwogICAgbGluZS1oZWlnaHQ6IC45MjsKICAgIGxldHRlci1zcGFjaW5nOiAt
LjA1NWVtOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhh
LWdlc3R1cmUtZ3JpZCB7CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ3JpZC10ZW1wbGF0ZS1jb2x1
bW5zOiByZXBlYXQoMTIsIG1pbm1heCgwLCAxZnIpKTsKICAgIGdhcDogMTZweDsKfQoKW2RhdGEt
cGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nZXN0dXJlIHsKICAgIHBv
c2l0aW9uOiByZWxhdGl2ZTsKICAgIGRpc3BsYXk6IGZsZXg7CiAgICBtaW4taGVpZ2h0OiA0ODBw
eDsKICAgIHBhZGRpbmc6IGNsYW1wKDI2cHgsIDN2dywgNDhweCk7CiAgICBvdmVyZmxvdzogaGlk
ZGVuOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWdl
c3R1cmU6bnRoLWNoaWxkKDEpIHsKICAgIGdyaWQtY29sdW1uOiBzcGFuIDU7Cn0KCltkYXRhLXBk
cC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZ2VzdHVyZTpudGgtY2hpbGQo
MikgewogICAgZ3JpZC1jb2x1bW46IHNwYW4gMzsKICAgIG1hcmdpbi10b3A6IDk2cHg7Cn0KCltk
YXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZ2VzdHVyZTpudGgt
Y2hpbGQoMykgewogICAgZ3JpZC1jb2x1bW46IHNwYW4gNDsKICAgIG1pbi1oZWlnaHQ6IDYyMHB4
Owp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWdlc3R1
cmUuaXMtaW5rIHsKICAgIGNvbG9yOiAjZmZmOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhhLWlu
ayk7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZ2Vz
dHVyZS5pcy13aW5lIHsKICAgIGNvbG9yOiAjZmZmOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhh
LXdpbmUpOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhh
LWdlc3R1cmUuaXMtcGFwZXIgewogICAgY29sb3I6IHZhcigtLWx4YS1pbmspOwogICAgYmFja2dy
b3VuZDogI2U5ZGZkNTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3Yx
Il0gLmx4YS1nZXN0dXJlX19pbmRleCB7CiAgICBwb3NpdGlvbjogYWJzb2x1dGU7CiAgICB0b3A6
IDI0cHg7CiAgICByaWdodDogMjZweDsKICAgIGZvbnQtZmFtaWx5OiB2YXIoLS1seGEtc2VyaWYp
OwogICAgZm9udC1zaXplOiAyMnB4OwogICAgb3BhY2l0eTogLjYyOwp9CgpbZGF0YS1wZHAtdmFy
aWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWdlc3R1cmVfX29yYml0IHsKICAgIHBv
c2l0aW9uOiBhYnNvbHV0ZTsKICAgIHdpZHRoOiAzNDBweDsKICAgIGhlaWdodDogMzQwcHg7CiAg
ICB0b3A6IDUwJTsKICAgIGxlZnQ6IDUwJTsKICAgIGJvcmRlcjogMXB4IHNvbGlkIGN1cnJlbnRD
b2xvcjsKICAgIGJvcmRlci1yYWRpdXM6IDUwJTsKICAgIG9wYWNpdHk6IC4xNjsKICAgIHRyYW5z
Zm9ybTogdHJhbnNsYXRlKC01MCUsIC01MCUpOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGll
cl9lZGl0b3JpYWxfdjEiXSAubHhhLWdlc3R1cmVfX29yYml0OjpiZWZvcmUsCltkYXRhLXBkcC12
YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZ2VzdHVyZV9fb3JiaXQ6OmFmdGVy
IHsKICAgIGNvbnRlbnQ6ICIiOwogICAgcG9zaXRpb246IGFic29sdXRlOwogICAgaW5zZXQ6IDE4
JTsKICAgIGJvcmRlcjogMXB4IHNvbGlkIGN1cnJlbnRDb2xvcjsKICAgIGJvcmRlci1yYWRpdXM6
IDUwJTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1n
ZXN0dXJlX19vcmJpdDo6YWZ0ZXIgewogICAgaW5zZXQ6IDM4JTsKfQoKW2RhdGEtcGRwLXZhcmlh
bnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nZXN0dXJlID4gZGl2Omxhc3QtY2hpbGQg
ewogICAgcG9zaXRpb246IHJlbGF0aXZlOwogICAgei1pbmRleDogMjsKICAgIGFsaWduLXNlbGY6
IGZsZXgtZW5kOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAu
bHhhLWdlc3R1cmUgcCB7CiAgICBtYXJnaW46IDA7CiAgICBmb250LXNpemU6IDEwcHg7CiAgICBm
b250LXdlaWdodDogODAwOwogICAgbGV0dGVyLXNwYWNpbmc6IC4xOGVtOwogICAgdGV4dC10cmFu
c2Zvcm06IHVwcGVyY2FzZTsKICAgIG9wYWNpdHk6IC42ODsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9
ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nZXN0dXJlIGgzIHsKICAgIG1hcmdpbjogMTJw
eCAwIDA7CiAgICBmb250LWZhbWlseTogdmFyKC0tbHhhLXNlcmlmKTsKICAgIGZvbnQtc2l6ZTog
Y2xhbXAoNDJweCwgNHZ3LCA3MnB4KTsKICAgIGZvbnQtd2VpZ2h0OiA0MDA7CiAgICBsaW5lLWhl
aWdodDogLjkyOwogICAgbGV0dGVyLXNwYWNpbmc6IC0uMDQ1ZW07Cn0KCltkYXRhLXBkcC12YXJp
YW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZ2VzdHVyZSA+IGRpdjpsYXN0LWNoaWxk
ID4gc3BhbiB7CiAgICBkaXNwbGF5OiBibG9jazsKICAgIG1heC13aWR0aDogMzYwcHg7CiAgICBt
YXJnaW4tdG9wOiAxOHB4OwogICAgZm9udC1zaXplOiAxMnB4OwogICAgbGluZS1oZWlnaHQ6IDEu
NjU7CiAgICBvcGFjaXR5OiAuNzsKfQoKLyogLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0KICogRml0IHN0b3J5
CiAqIC0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tICovCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlh
bF92MSJdIC5seGEtZml0IHsKICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBncmlkLXRlbXBsYXRlLWNv
bHVtbnM6IG1pbm1heCgwLCAxLjA4ZnIpIG1pbm1heCg0MjBweCwgLjkyZnIpOwogICAgbWluLWhl
aWdodDogODIwcHg7CiAgICBjb2xvcjogI2ZmZjsKICAgIGJhY2tncm91bmQ6IHZhcigtLWx4YS1j
aGFyY29hbCk7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5s
eGEtZml0X19pbWFnZSB7CiAgICBwb3NpdGlvbjogcmVsYXRpdmU7CiAgICBtaW4taGVpZ2h0OiA4
MjBweDsKICAgIG92ZXJmbG93OiBoaWRkZW47Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVy
X2VkaXRvcmlhbF92MSJdIC5seGEtZml0X19pbWFnZTo6YWZ0ZXIgewogICAgY29udGVudDogIiI7
CiAgICBwb3NpdGlvbjogYWJzb2x1dGU7CiAgICBpbnNldDogMDsKICAgIGJhY2tncm91bmQ6IGxp
bmVhci1ncmFkaWVudCgxODBkZWcsIHRyYW5zcGFyZW50IDUwJSwgcmdiYSgxMiwgMTAsIDksIC42
OCkpOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZp
dF9faW1hZ2UgaW1nIHsKICAgIHdpZHRoOiAxMDAlOwogICAgaGVpZ2h0OiAxMDAlOwogICAgb2Jq
ZWN0LWZpdDogY292ZXI7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92
MSJdIC5seGEtZml0X19pbWFnZS1jb3B5IHsKICAgIHBvc2l0aW9uOiBhYnNvbHV0ZTsKICAgIHot
aW5kZXg6IDI7CiAgICByaWdodDogNDRweDsKICAgIGJvdHRvbTogNDJweDsKICAgIGxlZnQ6IDQ0
cHg7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZml0
X19pbWFnZS1jb3B5IHAgewogICAgbWFyZ2luOiAwOwogICAgZm9udC1zaXplOiAxMHB4OwogICAg
Zm9udC13ZWlnaHQ6IDgwMDsKICAgIGxldHRlci1zcGFjaW5nOiAuMmVtOwogICAgdGV4dC10cmFu
c2Zvcm06IHVwcGVyY2FzZTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFs
X3YxIl0gLmx4YS1maXRfX2ltYWdlLWNvcHkgaDIgewogICAgbWF4LXdpZHRoOiA3MjBweDsKICAg
IG1hcmdpbjogMTRweCAwIDA7CiAgICBmb250LWZhbWlseTogdmFyKC0tbHhhLXNlcmlmKTsKICAg
IGZvbnQtc2l6ZTogY2xhbXAoNTRweCwgNS40dncsIDEwMHB4KTsKICAgIGZvbnQtd2VpZ2h0OiA0
MDA7CiAgICBsaW5lLWhlaWdodDogLjg4OwogICAgbGV0dGVyLXNwYWNpbmc6IC0uMDU1ZW07Cn0K
CltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZml0X19wYW5l
bCB7CiAgICBkaXNwbGF5OiBmbGV4OwogICAgZmxleC1kaXJlY3Rpb246IGNvbHVtbjsKICAgIGp1
c3RpZnktY29udGVudDogY2VudGVyOwogICAgcGFkZGluZzogY2xhbXAoNThweCwgN3Z3LCAxMjZw
eCk7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZml0
X19wYW5lbCBoMiB7CiAgICBtYXgtd2lkdGg6IDYyMHB4OwogICAgbWFyZ2luOiAxOHB4IDAgMDsK
ICAgIGZvbnQtZmFtaWx5OiB2YXIoLS1seGEtc2VyaWYpOwogICAgZm9udC1zaXplOiBjbGFtcCg1
MnB4LCA1LjN2dywgOTRweCk7CiAgICBmb250LXdlaWdodDogNDAwOwogICAgbGluZS1oZWlnaHQ6
IC45MjsKICAgIGxldHRlci1zcGFjaW5nOiAtLjA1NWVtOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0i
YXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZpdF9faW50cm8gewogICAgbWF4LXdpZHRoOiA2
MDBweDsKICAgIG1hcmdpbjogMjhweCAwIDA7CiAgICBjb2xvcjogcmdiYSgyNTUsIDI1NSwgMjU1
LCAuNTgpOwogICAgZm9udC1zaXplOiAxM3B4OwogICAgbGluZS1oZWlnaHQ6IDEuODsKfQoKW2Rh
dGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maXRfX21ldHJpY3Mg
ewogICAgZGlzcGxheTogZ3JpZDsKICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogcmVwZWF0KDIs
IG1pbm1heCgwLCAxZnIpKTsKICAgIGdhcDogMXB4OwogICAgbWFyZ2luLXRvcDogNDBweDsKICAg
IGJhY2tncm91bmQ6IHZhcigtLWx4YS1saWdodC1saW5lKTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9
ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maXRfX21ldHJpY3MgYXJ0aWNsZSB7CiAgICBt
aW4taGVpZ2h0OiAxMzBweDsKICAgIHBhZGRpbmc6IDI0cHg7CiAgICBiYWNrZ3JvdW5kOiB2YXIo
LS1seGEtY2hhcmNvYWwpOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxf
djEiXSAubHhhLWZpdF9fbWV0cmljcyBzcGFuLApbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9l
ZGl0b3JpYWxfdjEiXSAubHhhLWZpdF9fbWV0cmljcyBzdHJvbmcgewogICAgZGlzcGxheTogYmxv
Y2s7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZml0
X19tZXRyaWNzIHNwYW4gewogICAgY29sb3I6IHJnYmEoMjU1LCAyNTUsIDI1NSwgLjQ4KTsKICAg
IGZvbnQtc2l6ZTogOXB4OwogICAgbGV0dGVyLXNwYWNpbmc6IC4xNWVtOwogICAgdGV4dC10cmFu
c2Zvcm06IHVwcGVyY2FzZTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFs
X3YxIl0gLmx4YS1maXRfX21ldHJpY3Mgc3Ryb25nIHsKICAgIG1hcmdpbi10b3A6IDE0cHg7CiAg
ICBmb250LWZhbWlseTogdmFyKC0tbHhhLXNlcmlmKTsKICAgIGZvbnQtc2l6ZTogY2xhbXAoMjRw
eCwgMnZ3LCAzNHB4KTsKICAgIGZvbnQtd2VpZ2h0OiA0MDA7Cn0KCltkYXRhLXBkcC12YXJpYW50
PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtb3V0bGluZS1idXR0b24gewogICAgYWxpZ24t
c2VsZjogZmxleC1zdGFydDsKICAgIG1pbi1oZWlnaHQ6IDUycHg7CiAgICBtYXJnaW4tdG9wOiAz
NHB4OwogICAgcGFkZGluZzogMCAyMnB4OwogICAgYm9yZGVyOiAxcHggc29saWQgY3VycmVudENv
bG9yOwogICAgYm9yZGVyLXJhZGl1czogMDsKICAgIGN1cnNvcjogcG9pbnRlcjsKICAgIGNvbG9y
OiBpbmhlcml0OwogICAgZm9udC1zaXplOiAxMHB4OwogICAgZm9udC13ZWlnaHQ6IDgwMDsKICAg
IGxldHRlci1zcGFjaW5nOiAuMTRlbTsKICAgIHRleHQtdHJhbnNmb3JtOiB1cHBlcmNhc2U7CiAg
ICBiYWNrZ3JvdW5kOiB0cmFuc3BhcmVudDsKICAgIHRyYW5zaXRpb246IGNvbG9yIC4yNXMgZWFz
ZSwgYmFja2dyb3VuZCAuMjVzIGVhc2U7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2Vk
aXRvcmlhbF92MSJdIC5seGEtb3V0bGluZS1idXR0b246aG92ZXI6bm90KDpkaXNhYmxlZCkgewog
ICAgY29sb3I6IHZhcigtLWx4YS1pbmspOwogICAgYmFja2dyb3VuZDogI2ZmZjsKfQoKW2RhdGEt
cGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1vdXRsaW5lLWJ1dHRvbjpk
aXNhYmxlZCB7CiAgICBvcGFjaXR5OiAuMzU7CiAgICBjdXJzb3I6IG5vdC1hbGxvd2VkOwp9Cgpb
ZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW91dGxpbmUtYnV0
dG9uLS1kYXJrIHsKICAgIGNvbG9yOiB2YXIoLS1seGEtaW5rKTsKfQoKW2RhdGEtcGRwLXZhcmlh
bnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1vdXRsaW5lLWJ1dHRvbi0tZGFyazpob3Zl
cjpub3QoOmRpc2FibGVkKSB7CiAgICBjb2xvcjogI2ZmZjsKICAgIGJhY2tncm91bmQ6IHZhcigt
LWx4YS1pbmspOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAu
bHhhLXNvdXJjZS1ub3RlIHsKICAgIG1hcmdpbjogMThweCAwIDA7CiAgICBjb2xvcjogcmdiYSgy
NTUsIDI1NSwgMjU1LCAuNCk7CiAgICBmb250LXNpemU6IDlweDsKICAgIGxldHRlci1zcGFjaW5n
OiAuMWVtOwogICAgdGV4dC10cmFuc2Zvcm06IHVwcGVyY2FzZTsKfQoKLyogLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0KICogUHJvZHVjdCB0cnV0aCBtb3NhaWMKICogLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8KW2RhdGEt
cGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS10cnV0aCB7CiAgICB3aWR0
aDogbWluKDEwMCUgLSA3MnB4LCAxNTgwcHgpOwogICAgbWFyZ2luLWlubGluZTogYXV0bzsKICAg
IHBhZGRpbmctYmxvY2s6IGNsYW1wKDExMHB4LCAxMXZ3LCAxOTBweCk7Cn0KCltkYXRhLXBkcC12
YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtdHJ1dGhfX2hlYWQgewogICAgZGlz
cGxheTogZ3JpZDsKICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogbWlubWF4KDAsIDFmcikgbWlu
bWF4KDI4MHB4LCAuNDVmcik7CiAgICBnYXA6IDU2cHg7CiAgICBhbGlnbi1pdGVtczogZW5kOwog
ICAgbWFyZ2luLWJvdHRvbTogNjRweDsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRp
dG9yaWFsX3YxIl0gLmx4YS10cnV0aF9faGVhZCBoMiB7CiAgICBtYXJnaW46IDE2cHggMCAwOwog
ICAgZm9udC1mYW1pbHk6IHZhcigtLWx4YS1zZXJpZik7CiAgICBmb250LXNpemU6IGNsYW1wKDU4
cHgsIDd2dywgMTEycHgpOwogICAgZm9udC13ZWlnaHQ6IDQwMDsKICAgIGxpbmUtaGVpZ2h0OiAu
OTsKICAgIGxldHRlci1zcGFjaW5nOiAtLjA2ZW07Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVs
aWVyX2VkaXRvcmlhbF92MSJdIC5seGEtdHJ1dGhfX2hlYWQgPiBwIHsKICAgIG1hcmdpbjogMDsK
ICAgIGNvbG9yOiB2YXIoLS1seGEtbXV0ZWQpOwogICAgZm9udC1zaXplOiAxM3B4OwogICAgbGlu
ZS1oZWlnaHQ6IDEuODsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3Yx
Il0gLmx4YS1tb3NhaWMgewogICAgZGlzcGxheTogZ3JpZDsKICAgIGdyaWQtdGVtcGxhdGUtY29s
dW1uczogcmVwZWF0KDEyLCBtaW5tYXgoMCwgMWZyKSk7CiAgICBncmlkLWF1dG8tcm93czogODhw
eDsKICAgIGdhcDogMTRweDsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFs
X3YxIl0gLmx4YS1tb3NhaWNfX2l0ZW0gewogICAgcG9zaXRpb246IHJlbGF0aXZlOwogICAgb3Zl
cmZsb3c6IGhpZGRlbjsKICAgIG1hcmdpbjogMDsKICAgIGJhY2tncm91bmQ6ICNkZmQ2Y2U7Cn0K
CltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbW9zYWljX19p
dGVtLS0xIHsKICAgIGdyaWQtY29sdW1uOiAxIC8gc3BhbiA3OwogICAgZ3JpZC1yb3c6IDEgLyBz
cGFuIDg7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEt
bW9zYWljX19pdGVtLS0yIHsKICAgIGdyaWQtY29sdW1uOiA4IC8gc3BhbiA1OwogICAgZ3JpZC1y
b3c6IDIgLyBzcGFuIDU7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92
MSJdIC5seGEtbW9zYWljX19pdGVtLS0zIHsKICAgIGdyaWQtY29sdW1uOiA4IC8gc3BhbiA1Owog
ICAgZ3JpZC1yb3c6IDcgLyBzcGFuIDY7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2Vk
aXRvcmlhbF92MSJdIC5seGEtbW9zYWljX19pdGVtLS00IHsKICAgIGdyaWQtY29sdW1uOiAyIC8g
c3BhbiA0OwogICAgZ3JpZC1yb3c6IDkgLyBzcGFuIDU7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJh
dGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbW9zYWljX19pdGVtLS01IHsKICAgIGdyaWQtY29s
dW1uOiA2IC8gc3BhbiA2OwogICAgZ3JpZC1yb3c6IDEzIC8gc3BhbiA1Owp9CgpbZGF0YS1wZHAt
dmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1vc2FpY19faXRlbSBpbWcgewog
ICAgd2lkdGg6IDEwMCU7CiAgICBoZWlnaHQ6IDEwMCU7CiAgICBvYmplY3QtZml0OiBjb3ZlcjsK
ICAgIHRyYW5zaXRpb246IHRyYW5zZm9ybSAxcyBjdWJpYy1iZXppZXIoLjIyLCAxLCAuMzYsIDEp
Owp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1vc2Fp
Y19faXRlbTpob3ZlciBpbWcgewogICAgdHJhbnNmb3JtOiBzY2FsZSgxLjAzNSk7Cn0KCltkYXRh
LXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbW9zYWljX19pdGVtIGZp
Z2NhcHRpb24gewogICAgcG9zaXRpb246IGFic29sdXRlOwogICAgcmlnaHQ6IDE2cHg7CiAgICBi
b3R0b206IDE2cHg7CiAgICBwYWRkaW5nOiA3cHggOXB4OwogICAgY29sb3I6ICNmZmY7CiAgICBm
b250LXNpemU6IDlweDsKICAgIGZvbnQtd2VpZ2h0OiA3NTA7CiAgICBsZXR0ZXItc3BhY2luZzog
LjEyZW07CiAgICB0ZXh0LXRyYW5zZm9ybTogdXBwZXJjYXNlOwogICAgYmFja2dyb3VuZDogcmdi
YSgyNSwgMjMsIDIxLCAuNTYpOwogICAgYmFja2Ryb3AtZmlsdGVyOiBibHVyKDhweCk7Cn0KCi8q
IC0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tCiAqIFNpemUgc3RvcnkKICogLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8KW2Rh
dGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1zaXplLXN0b3J5IHsK
ICAgIGRpc3BsYXk6IGdyaWQ7CiAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IG1pbm1heCgzNjBw
eCwgLjcyZnIpIG1pbm1heCgwLCAxLjI4ZnIpOwogICAgbWluLWhlaWdodDogNzYwcHg7CiAgICBj
b2xvcjogI2ZmZjsKICAgIGJhY2tncm91bmQ6IHZhcigtLWx4YS13aW5lLWRlZXApOwp9CgpbZGF0
YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLXNpemUtc3RvcnlfX2lu
dHJvIHsKICAgIGRpc3BsYXk6IGZsZXg7CiAgICBmbGV4LWRpcmVjdGlvbjogY29sdW1uOwogICAg
anVzdGlmeS1jb250ZW50OiBjZW50ZXI7CiAgICBwYWRkaW5nOiBjbGFtcCg1NnB4LCA3dncsIDEy
OHB4KTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1z
aXplLXN0b3J5X19pbnRybyBoMiB7CiAgICBtYXJnaW46IDIwcHggMCAwOwogICAgZm9udC1mYW1p
bHk6IHZhcigtLWx4YS1zZXJpZik7CiAgICBmb250LXNpemU6IGNsYW1wKDUycHgsIDUuNXZ3LCAx
MDBweCk7CiAgICBmb250LXdlaWdodDogNDAwOwogICAgbGluZS1oZWlnaHQ6IC44ODsKICAgIGxl
dHRlci1zcGFjaW5nOiAtLjA1NWVtOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0
b3JpYWxfdjEiXSAubHhhLXNpemUtc3RvcnlfX2ludHJvID4gcDpub3QoLmx4YS1raWNrZXIpOm5v
dCgubHhhLXNvdXJjZS1ub3RlKSB7CiAgICBtYXgtd2lkdGg6IDUyMHB4OwogICAgbWFyZ2luOiAz
MHB4IDAgMDsKICAgIGNvbG9yOiByZ2JhKDI1NSwgMjU1LCAyNTUsIC42Mik7CiAgICBmb250LXNp
emU6IDEzcHg7CiAgICBsaW5lLWhlaWdodDogMS44NTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0
ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1zaXplLXN0b3J5X19pbnRybyAubHhhLW91dGxpbmUt
YnV0dG9uLS1kYXJrIHsKICAgIGNvbG9yOiAjZmZmOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRl
bGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLXNpemUtc3RvcnlfX2ludHJvIC5seGEtb3V0bGluZS1i
dXR0b24tLWRhcms6aG92ZXI6bm90KDpkaXNhYmxlZCkgewogICAgY29sb3I6IHZhcigtLWx4YS13
aW5lLWRlZXApOwogICAgYmFja2dyb3VuZDogI2ZmZjsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0
ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1zaXplLXN0b3J5X190YWJsZS13cmFwIHsKICAgIGFs
aWduLXNlbGY6IGNlbnRlcjsKICAgIG92ZXJmbG93OiBhdXRvOwogICAgbWF4LXdpZHRoOiBjYWxj
KDEwMHZ3IC0gMzYwcHgpOwogICAgbWFyZ2luOiA0OHB4IDQ4cHggNDhweCAwOwogICAgY29sb3I6
IHZhcigtLWx4YS1pbmspOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhhLWNyZWFtKTsKICAgIGJv
eC1zaGFkb3c6IDAgMzhweCA5MHB4IHJnYmEoMCwgMCwgMCwgLjIyKTsKfQoKW2RhdGEtcGRwLXZh
cmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1zaXplLXRhYmxlIHsKICAgIHdpZHRo
OiAxMDAlOwogICAgbWluLXdpZHRoOiA3NjBweDsKICAgIGJvcmRlci1jb2xsYXBzZTogY29sbGFw
c2U7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtc2l6
ZS10YWJsZSB0aCwKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4
YS1zaXplLXRhYmxlIHRkIHsKICAgIHBhZGRpbmc6IDIycHggMThweDsKICAgIGJvcmRlci1ib3R0
b206IDFweCBzb2xpZCB2YXIoLS1seGEtbGluZSk7CiAgICB0ZXh0LWFsaWduOiBjZW50ZXI7Cn0K
CltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtc2l6ZS10YWJs
ZSB0aGVhZCB0aCB7CiAgICBwb3NpdGlvbjogc3RpY2t5OwogICAgdG9wOiAwOwogICAgei1pbmRl
eDogMjsKICAgIGNvbG9yOiB2YXIoLS1seGEtbXV0ZWQpOwogICAgZm9udC1zaXplOiAxMHB4Owog
ICAgZm9udC13ZWlnaHQ6IDgwMDsKICAgIGxldHRlci1zcGFjaW5nOiAuMTZlbTsKICAgIHRleHQt
dHJhbnNmb3JtOiB1cHBlcmNhc2U7CiAgICBiYWNrZ3JvdW5kOiAjZWNlM2RiOwp9CgpbZGF0YS1w
ZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLXNpemUtdGFibGUgdGhlYWQg
dGg6Zmlyc3QtY2hpbGQsCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJd
IC5seGEtc2l6ZS10YWJsZSB0Ym9keSB0aCB7CiAgICBwb3NpdGlvbjogc3RpY2t5OwogICAgbGVm
dDogMDsKICAgIHotaW5kZXg6IDM7CiAgICB3aWR0aDogMjYwcHg7CiAgICB0ZXh0LWFsaWduOiBs
ZWZ0OwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhhLWNyZWFtKTsKfQoKW2RhdGEtcGRwLXZhcmlh
bnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1zaXplLXRhYmxlIHRoZWFkIHRoOmZpcnN0
LWNoaWxkIHsKICAgIHotaW5kZXg6IDQ7CiAgICBiYWNrZ3JvdW5kOiAjZWNlM2RiOwp9CgpbZGF0
YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLXNpemUtdGFibGUgdGJv
ZHkgdGggc3BhbiwKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4
YS1zaXplLXRhYmxlIHRib2R5IHRoIHNtYWxsLApbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9l
ZGl0b3JpYWxfdjEiXSAubHhhLXNpemUtdGFibGUgdGQgc3Ryb25nLApbZGF0YS1wZHAtdmFyaWFu
dD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLXNpemUtdGFibGUgdGQgc21hbGwgewogICAg
ZGlzcGxheTogYmxvY2s7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92
MSJdIC5seGEtc2l6ZS10YWJsZSB0Ym9keSB0aCBzcGFuIHsKICAgIGZvbnQtZmFtaWx5OiB2YXIo
LS1seGEtc2VyaWYpOwogICAgZm9udC1zaXplOiAxOHB4OwogICAgZm9udC13ZWlnaHQ6IDQwMDsK
fQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1zaXplLXRh
YmxlIHRib2R5IHRoIHNtYWxsIHsKICAgIG1heC13aWR0aDogMjMwcHg7CiAgICBtYXJnaW4tdG9w
OiA2cHg7CiAgICBjb2xvcjogdmFyKC0tbHhhLW11dGVkKTsKICAgIGZvbnQtc2l6ZTogOXB4Owog
ICAgZm9udC13ZWlnaHQ6IDQwMDsKICAgIGxpbmUtaGVpZ2h0OiAxLjQ7Cn0KCltkYXRhLXBkcC12
YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtc2l6ZS10YWJsZSB0ZCBzdHJvbmcg
ewogICAgZm9udC1mYW1pbHk6IHZhcigtLWx4YS1zZXJpZik7CiAgICBmb250LXNpemU6IDI0cHg7
CiAgICBmb250LXdlaWdodDogNDAwOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0
b3JpYWxfdjEiXSAubHhhLXNpemUtdGFibGUgdGQgc21hbGwgewogICAgbWFyZ2luLXRvcDogM3B4
OwogICAgY29sb3I6IHZhcigtLWx4YS1tdXRlZCk7CiAgICBmb250LXNpemU6IDlweDsKfQoKLyog
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0KICogTWF0ZXJpYWwgc3RvcnkKICogLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8K
W2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1tYXRlcmlhbCB7
CiAgICB3aWR0aDogbWluKDEwMCUgLSA3MnB4LCAxNTQwcHgpOwogICAgbWFyZ2luLWlubGluZTog
YXV0bzsKICAgIHBhZGRpbmctYmxvY2s6IGNsYW1wKDExMHB4LCAxMnZ3LCAyMDBweCk7Cn0KCltk
YXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbWF0ZXJpYWxfX3N0
YXRlbWVudCB7CiAgICBkaXNwbGF5OiBncmlkOwogICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiBt
aW5tYXgoMjAwcHgsIC4zNmZyKSBtaW5tYXgoMCwgMS4xZnIpIG1pbm1heCgyNjBweCwgLjQyZnIp
OwogICAgZ2FwOiA0OHB4OwogICAgYWxpZ24taXRlbXM6IHN0YXJ0OwogICAgbWFyZ2luLWJvdHRv
bTogNzJweDsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4
YS1tYXRlcmlhbF9fc3RhdGVtZW50IC5seGEta2lja2VyIHsKICAgIGNvbG9yOiB2YXIoLS1seGEt
d2luZSk7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEt
bWF0ZXJpYWxfX3N0YXRlbWVudCBoMiB7CiAgICBtYXJnaW46IDA7CiAgICBmb250LWZhbWlseTog
dmFyKC0tbHhhLXNlcmlmKTsKICAgIGZvbnQtc2l6ZTogY2xhbXAoNTBweCwgNi4ydncsIDEwOHB4
KTsKICAgIGZvbnQtd2VpZ2h0OiA0MDA7CiAgICBsaW5lLWhlaWdodDogLjk7CiAgICBsZXR0ZXIt
c3BhY2luZzogLS4wNmVtOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxf
djEiXSAubHhhLW1hdGVyaWFsX19zdGF0ZW1lbnQgPiBwOmxhc3QtY2hpbGQgewogICAgbWFyZ2lu
OiAwOwogICAgY29sb3I6IHZhcigtLWx4YS1tdXRlZCk7CiAgICBmb250LXNpemU6IDEycHg7CiAg
ICBsaW5lLWhlaWdodDogMS44NTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9y
aWFsX3YxIl0gLmx4YS1tYXRlcmlhbF9fY2FyZHMgewogICAgZGlzcGxheTogZ3JpZDsKICAgIGdy
aWQtdGVtcGxhdGUtY29sdW1uczogcmVwZWF0KDEyLCBtaW5tYXgoMCwgMWZyKSk7CiAgICBnYXA6
IDE0cHg7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEt
bWF0ZXJpYWwtY2FyZCB7CiAgICBwb3NpdGlvbjogcmVsYXRpdmU7CiAgICBkaXNwbGF5OiBmbGV4
OwogICAgZmxleC1kaXJlY3Rpb246IGNvbHVtbjsKICAgIG1pbi1oZWlnaHQ6IDM2MHB4OwogICAg
cGFkZGluZzogMzJweDsKICAgIG92ZXJmbG93OiBoaWRkZW47CiAgICBib3JkZXI6IDFweCBzb2xp
ZCB2YXIoLS1seGEtbGluZSk7CiAgICBiYWNrZ3JvdW5kOiB2YXIoLS1seGEtY3JlYW0pOwp9Cgpb
ZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1hdGVyaWFsLWNh
cmQ6bnRoLWNoaWxkKDEpIHsKICAgIGdyaWQtY29sdW1uOiBzcGFuIDU7Cn0KCltkYXRhLXBkcC12
YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbWF0ZXJpYWwtY2FyZDpudGgtY2hp
bGQoMikgewogICAgZ3JpZC1jb2x1bW46IHNwYW4gNDsKICAgIG1hcmdpbi10b3A6IDgwcHg7CiAg
ICBiYWNrZ3JvdW5kOiAjZGZkMmM2Owp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0
b3JpYWxfdjEiXSAubHhhLW1hdGVyaWFsLWNhcmQ6bnRoLWNoaWxkKG4rMykgewogICAgZ3JpZC1j
b2x1bW46IHNwYW4gMzsKICAgIG1pbi1oZWlnaHQ6IDI4MHB4Owp9CgpbZGF0YS1wZHAtdmFyaWFu
dD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1hdGVyaWFsLWNhcmQgPiBzcGFuIHsKICAg
IGZvbnQtZmFtaWx5OiB2YXIoLS1seGEtc2VyaWYpOwogICAgZm9udC1zaXplOiAyMHB4OwogICAg
b3BhY2l0eTogLjUyOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEi
XSAubHhhLW1hdGVyaWFsLWNhcmQgcCB7CiAgICBtYXJnaW46IGF1dG8gMCAwOwogICAgY29sb3I6
IHZhcigtLWx4YS1tdXRlZCk7CiAgICBmb250LXNpemU6IDlweDsKICAgIGZvbnQtd2VpZ2h0OiA4
MDA7CiAgICBsZXR0ZXItc3BhY2luZzogLjE2ZW07CiAgICB0ZXh0LXRyYW5zZm9ybTogdXBwZXJj
YXNlOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1h
dGVyaWFsLWNhcmQgaDMgewogICAgcG9zaXRpb246IHJlbGF0aXZlOwogICAgei1pbmRleDogMjsK
ICAgIG1hcmdpbjogMTJweCAwIDA7CiAgICBmb250LWZhbWlseTogdmFyKC0tbHhhLXNlcmlmKTsK
ICAgIGZvbnQtc2l6ZTogY2xhbXAoMzRweCwgMy41dncsIDY0cHgpOwogICAgZm9udC13ZWlnaHQ6
IDQwMDsKICAgIGxpbmUtaGVpZ2h0OiAuOTU7CiAgICBsZXR0ZXItc3BhY2luZzogLS4wNGVtOwp9
CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1hdGVyaWFs
LWNhcmQgc21hbGwgewogICAgcG9zaXRpb246IHJlbGF0aXZlOwogICAgei1pbmRleDogMjsKICAg
IG1hcmdpbi10b3A6IDE0cHg7CiAgICBjb2xvcjogdmFyKC0tbHhhLW11dGVkKTsKICAgIGZvbnQt
c2l6ZTogMTBweDsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0g
Lmx4YS1tYXRlcmlhbC1jYXJkIGkgewogICAgcG9zaXRpb246IGFic29sdXRlOwogICAgd2lkdGg6
IDI1MHB4OwogICAgaGVpZ2h0OiAyNTBweDsKICAgIHJpZ2h0OiAtMTAwcHg7CiAgICBib3R0b206
IC0xMTVweDsKICAgIGJvcmRlcjogMXB4IHNvbGlkIGN1cnJlbnRDb2xvcjsKICAgIGJvcmRlci1y
YWRpdXM6IDUwJTsKICAgIG9wYWNpdHk6IC4xOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGll
cl9lZGl0b3JpYWxfdjEiXSAubHhhLWNhcmUtbGluZSB7CiAgICBkaXNwbGF5OiBmbGV4OwogICAg
ZmxleC13cmFwOiB3cmFwOwogICAgZ2FwOiAxMnB4IDMwcHg7CiAgICBtYXJnaW4tdG9wOiAzNnB4
OwogICAgcGFkZGluZy10b3A6IDIycHg7CiAgICBib3JkZXItdG9wOiAxcHggc29saWQgdmFyKC0t
bHhhLWxpbmUpOwogICAgY29sb3I6IHZhcigtLWx4YS1tdXRlZCk7CiAgICBmb250LXNpemU6IDEx
cHg7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtY2Fy
ZS1saW5lIHN0cm9uZyB7CiAgICBjb2xvcjogdmFyKC0tbHhhLWluayk7CiAgICBsZXR0ZXItc3Bh
Y2luZzogLjEzZW07CiAgICB0ZXh0LXRyYW5zZm9ybTogdXBwZXJjYXNlOwp9CgovKiAtLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLQogKiBGaW5hbGUKICogLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8KW2RhdGEtcGRwLXZhcmlh
bnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maW5hbGUgewogICAgcG9zaXRpb246IHJl
bGF0aXZlOwogICAgZGlzcGxheTogZ3JpZDsKICAgIHBsYWNlLWl0ZW1zOiBjZW50ZXI7CiAgICBt
aW4taGVpZ2h0OiBtaW4oOTUwcHgsIDEwMHN2aCk7CiAgICBvdmVyZmxvdzogaGlkZGVuOwogICAg
Y29sb3I6ICNmZmY7CiAgICBiYWNrZ3JvdW5kOiB2YXIoLS1seGEtaW5rKTsKfQoKW2RhdGEtcGRw
LXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maW5hbGVfX2ltYWdlLApbZGF0
YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZpbmFsZV9fc2hhZGUg
ewogICAgcG9zaXRpb246IGFic29sdXRlOwogICAgaW5zZXQ6IDA7CiAgICB3aWR0aDogMTAwJTsK
ICAgIGhlaWdodDogMTAwJTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFs
X3YxIl0gLmx4YS1maW5hbGVfX2ltYWdlIHsKICAgIG9iamVjdC1maXQ6IGNvdmVyOwogICAgb2Jq
ZWN0LXBvc2l0aW9uOiBjZW50ZXIgMzUlOwogICAgdHJhbnNmb3JtOiBzY2FsZSgxLjAyKTsKfQoK
W2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maW5hbGVfX3No
YWRlIHsKICAgIGJhY2tncm91bmQ6CiAgICAgICAgbGluZWFyLWdyYWRpZW50KDkwZGVnLCByZ2Jh
KDE1LCAxMiwgMTEsIC44MiksIHJnYmEoMTUsIDEyLCAxMSwgLjM4KSA1NSUsIHJnYmEoMTUsIDEy
LCAxMSwgLjIpKSwKICAgICAgICBsaW5lYXItZ3JhZGllbnQoMTgwZGVnLCByZ2JhKDE1LCAxMiwg
MTEsIC4xOCksIHJnYmEoMTUsIDEyLCAxMSwgLjU2KSk7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJh
dGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZmluYWxlX19jb3B5IHsKICAgIHBvc2l0aW9uOiBy
ZWxhdGl2ZTsKICAgIHotaW5kZXg6IDI7CiAgICB3aWR0aDogbWluKDEwMCUgLSA3MnB4LCAxNTQw
cHgpOwogICAgbWFyZ2luLWlubGluZTogYXV0bzsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxp
ZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maW5hbGVfX2NvcHkgaDIgewogICAgbWF4LXdpZHRoOiAx
MDAwcHg7CiAgICBtYXJnaW46IDIycHggMCAwOwogICAgZm9udC1mYW1pbHk6IHZhcigtLWx4YS1z
ZXJpZik7CiAgICBmb250LXNpemU6IGNsYW1wKDY0cHgsIDh2dywgMTQ0cHgpOwogICAgZm9udC13
ZWlnaHQ6IDQwMDsKICAgIGxpbmUtaGVpZ2h0OiAuODY7CiAgICBsZXR0ZXItc3BhY2luZzogLS4w
NjVlbTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1m
aW5hbGVfX2NvcHkgPiBwOm5vdCgubHhhLWtpY2tlcikgewogICAgbWF4LXdpZHRoOiA1MjBweDsK
ICAgIG1hcmdpbjogMzRweCAwIDA7CiAgICBjb2xvcjogcmdiYSgyNTUsIDI1NSwgMjU1LCAuNzIp
OwogICAgZm9udC1zaXplOiAxM3B4OwogICAgbGluZS1oZWlnaHQ6IDEuODsKfQoKW2RhdGEtcGRw
LXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maW5hbGVfX2J1dHRvbiB7CiAg
ICBtaW4taGVpZ2h0OiA1OHB4OwogICAgbWFyZ2luLXRvcDogMzRweDsKICAgIHBhZGRpbmc6IDAg
MjhweDsKICAgIGJvcmRlcjogMXB4IHNvbGlkICNmZmY7CiAgICBib3JkZXItcmFkaXVzOiAwOwog
ICAgY3Vyc29yOiBwb2ludGVyOwogICAgY29sb3I6IHZhcigtLWx4YS1pbmspOwogICAgZm9udC1z
aXplOiAxMHB4OwogICAgZm9udC13ZWlnaHQ6IDgyMDsKICAgIGxldHRlci1zcGFjaW5nOiAuMTRl
bTsKICAgIHRleHQtdHJhbnNmb3JtOiB1cHBlcmNhc2U7CiAgICBiYWNrZ3JvdW5kOiAjZmZmOwog
ICAgdHJhbnNpdGlvbjogY29sb3IgLjI1cyBlYXNlLCBiYWNrZ3JvdW5kIC4yNXMgZWFzZTsKfQoK
W2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maW5hbGVfX2J1
dHRvbjpob3ZlciB7CiAgICBjb2xvcjogI2ZmZjsKICAgIGJhY2tncm91bmQ6IHRyYW5zcGFyZW50
Owp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZpbmFs
ZV9fcG9saWNpZXMgewogICAgZGlzcGxheTogZmxleDsKICAgIGZsZXgtd3JhcDogd3JhcDsKICAg
IGdhcDogMTJweCAyOHB4OwogICAgbWFyZ2luLXRvcDogNDBweDsKICAgIGZvbnQtc2l6ZTogOXB4
OwogICAgZm9udC13ZWlnaHQ6IDc1MDsKICAgIGxldHRlci1zcGFjaW5nOiAuMTRlbTsKICAgIHRl
eHQtdHJhbnNmb3JtOiB1cHBlcmNhc2U7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2Vk
aXRvcmlhbF92MSJdIC5seGEtZmluYWxlX19wb2xpY2llcyBzcGFuOjpiZWZvcmUgewogICAgY29u
dGVudDogIuKAoiI7CiAgICBtYXJnaW4tcmlnaHQ6IDlweDsKfQoKLyogLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0KICogU2hhcmVkIG1vYmlsZSBDVEEgYW5kIGFkdmlzb3IgcmVzdHlsZQogKiAtLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLSAqLwpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhwZHAt
bW9iaWxlLWJ1eSB7CiAgICBib3JkZXItY29sb3I6IHJnYmEoMjU1LCAyNTUsIDI1NSwgLjEyKTsK
ICAgIGNvbG9yOiAjZmZmOwogICAgYmFja2dyb3VuZDogcmdiYSgyNSwgMjMsIDIxLCAuOTYpOwp9
CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhwZHAtbW9iaWxl
LWJ1eSBzcGFuIHsKICAgIGNvbG9yOiByZ2JhKDI1NSwgMjU1LCAyNTUsIC41OCk7Cn0KCltkYXRh
LXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seHBkcC1tb2JpbGUtYnV5IGJ1
dHRvbiB7CiAgICBib3JkZXItcmFkaXVzOiAwOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhhLXdp
bmUpOwp9CgpbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhwZHAt
YWR2aXNvciB7CiAgICBib3JkZXItcmFkaXVzOiAwOwogICAgY29sb3I6IHZhcigtLWx4YS1pbmsp
OwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhhLWNyZWFtKTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9
ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4cGRwLWFkdmlzb3JfX2NvbnRlbnQgewogICAgcGFk
ZGluZzogY2xhbXAoMzBweCwgNXZ3LCA2NHB4KTsKfQoKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxp
ZXJfZWRpdG9yaWFsX3YxIl0gLmx4cGRwLWFkdmlzb3JfX2NvbnRlbnQgaDIgewogICAgbWF4LXdp
ZHRoOiA1MjBweDsKICAgIGZvbnQtZmFtaWx5OiB2YXIoLS1seGEtc2VyaWYpOwogICAgZm9udC1z
aXplOiBjbGFtcCg0MnB4LCA1dncsIDY4cHgpOwogICAgZm9udC13ZWlnaHQ6IDQwMDsKICAgIGxp
bmUtaGVpZ2h0OiAuOTU7CiAgICBsZXR0ZXItc3BhY2luZzogLS4wNDVlbTsKfQoKW2RhdGEtcGRw
LXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4cGRwLWFkdmlzb3JfX2dyaWQgaW5w
dXQsCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seHBkcC1hZHZp
c29yX19ncmlkIHNlbGVjdCwKW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3Yx
Il0gLmx4cGRwLWFkdmlzb3JfX2Nsb3NlLWZvcm0gYnV0dG9uLApbZGF0YS1wZHAtdmFyaWFudD0i
YXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhwZHAtYWR2aXNvcl9fcmVzdWx0IHsKICAgIGJvcmRl
ci1yYWRpdXM6IDA7Cn0KCltkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJd
IC5seHBkcC1hZHZpc29yIC5seHBkcC1wcmltYXJ5LWJ1dHRvbiB7CiAgICBib3JkZXItcmFkaXVz
OiAwOwogICAgYmFja2dyb3VuZDogdmFyKC0tbHhhLXdpbmUpOwp9CgovKiAtLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLQogKiBSZXNwb25zaXZlCiAqIC0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovCkBtZWRpYSAobWF4LXdpZHRo
OiAxMjIwcHgpIHsKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJd
IC5seGEtaGVybyB7CiAgICAgICAgZ3JpZC10ZW1wbGF0ZS1jb2x1bW5zOiBtaW5tYXgoMCwgMS4x
OGZyKSBtaW5tYXgoNDAwcHgsIC44MmZyKTsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0i
YXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWJ1eSB7CiAgICAgICAgcGFkZGluZy1pbmxpbmU6
IDM4cHg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3Yx
Il0gLmx4YS1idXlfX2hlYWQgaDEgewogICAgICAgIGZvbnQtc2l6ZTogY2xhbXAoNThweCwgNnZ3
LCA4NHB4KTsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxf
djEiXSAubHhhLWNvbG9yLWxpc3QgewogICAgICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogcmVw
ZWF0KDQsIG1pbm1heCgwLCAxZnIpKTsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRl
bGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1hdGVyaWFsX19zdGF0ZW1lbnQgewogICAgICAgIGdy
aWQtdGVtcGxhdGUtY29sdW1uczogbWlubWF4KDE0MHB4LCAuMjhmcikgbWlubWF4KDAsIDFmcik7
CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4
YS1tYXRlcmlhbF9fc3RhdGVtZW50ID4gcDpsYXN0LWNoaWxkIHsKICAgICAgICBncmlkLWNvbHVt
bjogMjsKICAgIH0KfQoKQG1lZGlhIChtYXgtd2lkdGg6IDk4MHB4KSB7CiAgICBbZGF0YS1wZHAt
dmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSB7CiAgICAgICAgcGFkZGluZy1ib3R0b206
IDExMnB4OwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92
MSJdIC5seHBkcC1wcmV2aWV3LWJhbm5lciwKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVy
X2VkaXRvcmlhbF92MSJdIC5seHBkcF9fYnJlYWRjcnVtYiB7CiAgICAgICAgd2lkdGg6IG1pbigx
MDAlIC0gMzJweCwgNzYwcHgpOwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVy
X2VkaXRvcmlhbF92MSJdIC5seGEtaGVybyB7CiAgICAgICAgZGlzcGxheTogYmxvY2s7CiAgICAg
ICAgbWluLWhlaWdodDogMDsKICAgICAgICBib3JkZXItYm90dG9tOiAwOwogICAgfQoKICAgIFtk
YXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtaGVyb19fbWVkaWEg
ewogICAgICAgIG1pbi1oZWlnaHQ6IDcyc3ZoOwogICAgICAgIGhlaWdodDogNzJzdmg7CiAgICB9
CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1idXkg
ewogICAgICAgIHBvc2l0aW9uOiByZWxhdGl2ZTsKICAgICAgICB6LWluZGV4OiA3OwogICAgICAg
IG1pbi1oZWlnaHQ6IDA7CiAgICAgICAgbWFyZ2luLXRvcDogLTMycHg7CiAgICAgICAgcGFkZGlu
ZzogNDhweCAyOHB4IDUycHg7CiAgICAgICAgYm9yZGVyLXJhZGl1czogMzBweCAzMHB4IDAgMDsK
ICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhh
LWJ1eTo6YmVmb3JlIHsKICAgICAgICBtYXJnaW4tYm90dG9tOiAyNnB4OwogICAgfQoKICAgIFtk
YXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtYnV5X19oZWFkIGgx
IHsKICAgICAgICBmb250LXNpemU6IGNsYW1wKDYwcHgsIDEzdncsIDkycHgpOwogICAgfQoKICAg
IFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtY29sb3ItbGlz
dCB7CiAgICAgICAgZGlzcGxheTogZmxleDsKICAgICAgICBvdmVyZmxvdy14OiBhdXRvOwogICAg
ICAgIGdhcDogMTBweDsKICAgICAgICBtYXJnaW4taW5saW5lOiAtMjhweDsKICAgICAgICBwYWRk
aW5nLWlubGluZTogMjhweDsKICAgICAgICBzY3JvbGwtc25hcC10eXBlOiB4IG1hbmRhdG9yeTsK
ICAgICAgICBzY3JvbGxiYXItd2lkdGg6IG5vbmU7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlh
bnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1jb2xvci1saXN0Ojotd2Via2l0LXNjcm9s
bGJhciB7CiAgICAgICAgZGlzcGxheTogbm9uZTsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFu
dD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWNvbG9yIHsKICAgICAgICBmbGV4OiAwIDAg
OTRweDsKICAgICAgICBzY3JvbGwtc25hcC1hbGlnbjogc3RhcnQ7CiAgICB9CgogICAgW2RhdGEt
cGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nYWxsZXJ5X190aHVtYnMg
ewogICAgICAgIHJpZ2h0OiAxNnB4OwogICAgICAgIGxlZnQ6IDE2cHg7CiAgICAgICAgYm90dG9t
OiA1NHB4OwogICAgICAgIG92ZXJmbG93LXg6IGF1dG87CiAgICAgICAgcGFkZGluZzogMDsKICAg
IH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZp
bG0gewogICAgICAgIGRpc3BsYXk6IGZsZXg7CiAgICAgICAgb3ZlcmZsb3cteDogYXV0bzsKICAg
ICAgICBnYXA6IDhweDsKICAgICAgICBwYWRkaW5nOiA4cHg7CiAgICAgICAgc2Nyb2xsLXNuYXAt
dHlwZTogeCBtYW5kYXRvcnk7CiAgICAgICAgc2Nyb2xsYmFyLXdpZHRoOiBub25lOwogICAgfQoK
ICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZmlsbTo6
LXdlYmtpdC1zY3JvbGxiYXIgewogICAgICAgIGRpc3BsYXk6IG5vbmU7CiAgICB9CgogICAgW2Rh
dGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maWxtX19pdGVtLAog
ICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maWxtX19p
dGVtOm50aC1jaGlsZCgyKSwKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlh
bF92MSJdIC5seGEtZmlsbV9faXRlbTpudGgtY2hpbGQoNCkgewogICAgICAgIGZsZXg6IDAgMCA3
MnZ3OwogICAgICAgIGhlaWdodDogNzhzdmg7CiAgICAgICAgbWFyZ2luLXRvcDogMDsKICAgICAg
ICBzY3JvbGwtc25hcC1hbGlnbjogY2VudGVyOwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50
PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbWFuaWZlc3RvIHsKICAgICAgICBncmlkLXRl
bXBsYXRlLWNvbHVtbnM6IDFmcjsKICAgICAgICBtaW4taGVpZ2h0OiA2ODBweDsKICAgIH0KCiAg
ICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1hbmlmZXN0
b19fbnVtYmVyIHsKICAgICAgICBwb3NpdGlvbjogYWJzb2x1dGU7CiAgICAgICAgdG9wOiA3MHB4
OwogICAgICAgIGxlZnQ6IDI0cHg7CiAgICAgICAgb3BhY2l0eTogLjc1OwogICAgfQoKICAgIFtk
YXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbWFuaWZlc3RvX19j
b3B5IHsKICAgICAgICBtYXJnaW4tdG9wOiAxMjBweDsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFy
aWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1hbmlmZXN0b19fY29weSA+IHA6bGFz
dC1jaGlsZCB7CiAgICAgICAgbWFyZ2luLWxlZnQ6IDA7CiAgICB9CgogICAgW2RhdGEtcGRwLXZh
cmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1jaGFwdGVyLAogICAgW2RhdGEtcGRw
LXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS10cnV0aCwKICAgIFtkYXRhLXBk
cC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbWF0ZXJpYWwsCiAgICBbZGF0
YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZpbmFsZV9fY29weSB7
CiAgICAgICAgd2lkdGg6IG1pbigxMDAlIC0gMzZweCwgODIwcHgpOwogICAgfQoKICAgIFtkYXRh
LXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtY2hhcHRlcl9faGVhZCwK
ICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtdHJ1dGhf
X2hlYWQsCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhh
LW1hdGVyaWFsX19zdGF0ZW1lbnQgewogICAgICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogMWZy
OwogICAgICAgIGdhcDogMjRweDsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGll
cl9lZGl0b3JpYWxfdjEiXSAubHhhLW1hdGVyaWFsX19zdGF0ZW1lbnQgPiBwOmxhc3QtY2hpbGQg
ewogICAgICAgIGdyaWQtY29sdW1uOiBhdXRvOwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50
PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZ2VzdHVyZS1ncmlkIHsKICAgICAgICBncmlk
LXRlbXBsYXRlLWNvbHVtbnM6IHJlcGVhdCgyLCBtaW5tYXgoMCwgMWZyKSk7CiAgICB9CgogICAg
W2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nZXN0dXJlLAog
ICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nZXN0dXJl
Om50aC1jaGlsZCgxKSwKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92
MSJdIC5seGEtZ2VzdHVyZTpudGgtY2hpbGQoMiksCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRl
bGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWdlc3R1cmU6bnRoLWNoaWxkKDMpIHsKICAgICAgICBn
cmlkLWNvbHVtbjogYXV0bzsKICAgICAgICBtaW4taGVpZ2h0OiA0NDBweDsKICAgICAgICBtYXJn
aW4tdG9wOiAwOwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlh
bF92MSJdIC5seGEtZ2VzdHVyZTpudGgtY2hpbGQoMykgewogICAgICAgIGdyaWQtY29sdW1uOiBz
cGFuIDI7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3Yx
Il0gLmx4YS1maXQgewogICAgICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogMWZyOwogICAgfQoK
ICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZml0X19p
bWFnZSB7CiAgICAgICAgbWluLWhlaWdodDogNzJzdmg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZh
cmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maXRfX3BhbmVsIHsKICAgICAgICBw
YWRkaW5nOiA3MnB4IDI4cHggODhweDsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRl
bGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1vc2FpYyB7CiAgICAgICAgZ3JpZC1hdXRvLXJvd3M6
IDc2cHg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3Yx
Il0gLmx4YS1zaXplLXN0b3J5IHsKICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IDFmcjsK
ICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhh
LXNpemUtc3RvcnlfX2ludHJvIHsKICAgICAgICBwYWRkaW5nOiA4MnB4IDI4cHggNTRweDsKICAg
IH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLXNp
emUtc3RvcnlfX3RhYmxlLXdyYXAgewogICAgICAgIG1heC13aWR0aDogY2FsYygxMDB2dyAtIDMy
cHgpOwogICAgICAgIG1hcmdpbjogMCAxNnB4IDcycHg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZh
cmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1tYXRlcmlhbF9fY2FyZHMgewogICAg
ICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczogcmVwZWF0KDIsIG1pbm1heCgwLCAxZnIpKTsKICAg
IH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1h
dGVyaWFsLWNhcmQsCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEi
XSAubHhhLW1hdGVyaWFsLWNhcmQ6bnRoLWNoaWxkKDEpLAogICAgW2RhdGEtcGRwLXZhcmlhbnQ9
ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1tYXRlcmlhbC1jYXJkOm50aC1jaGlsZCgyKSwK
ICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbWF0ZXJp
YWwtY2FyZDpudGgtY2hpbGQobiszKSB7CiAgICAgICAgZ3JpZC1jb2x1bW46IGF1dG87CiAgICAg
ICAgbWluLWhlaWdodDogMzQwcHg7CiAgICAgICAgbWFyZ2luLXRvcDogMDsKICAgIH0KfQoKQG1l
ZGlhIChtYXgtd2lkdGg6IDY0MHB4KSB7CiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9l
ZGl0b3JpYWxfdjEiXSAubHhwZHBfX2JyZWFkY3J1bWIgewogICAgICAgIG1hcmdpbi10b3A6IDEw
cHg7CiAgICAgICAgbWFyZ2luLWJvdHRvbTogMTBweDsKICAgICAgICBmb250LXNpemU6IDlweDsK
ICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhh
LWhlcm9fX2lzc3VlIHsKICAgICAgICB0b3A6IDE4cHg7CiAgICAgICAgcmlnaHQ6IDE4cHg7CiAg
ICAgICAgbGVmdDogMThweDsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9l
ZGl0b3JpYWxfdjEiXSAubHhhLWhlcm9fX21lZGlhIHsKICAgICAgICBtaW4taGVpZ2h0OiA2OHN2
aDsKICAgICAgICBoZWlnaHQ6IDY4c3ZoOwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJh
dGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZ2FsbGVyeV9fbmF2IHsKICAgICAgICB3aWR0aDog
NDBweDsKICAgICAgICBoZWlnaHQ6IDQwcHg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9
ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nYWxsZXJ5X19uYXYtLXByZXYgewogICAgICAg
IGxlZnQ6IDE0cHg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9y
aWFsX3YxIl0gLmx4YS1nYWxsZXJ5X19uYXYtLW5leHQgewogICAgICAgIHJpZ2h0OiAxNHB4Owog
ICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEt
Z2FsbGVyeV9fY2FwdGlvbiB7CiAgICAgICAgcmlnaHQ6IDE4cHg7CiAgICAgICAgYm90dG9tOiAx
OHB4OwogICAgICAgIGxlZnQ6IDE4cHg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0
ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1nYWxsZXJ5X190aHVtYnMgewogICAgICAgIGRpc3Bs
YXk6IG5vbmU7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFs
X3YxIl0gLmx4YS1idXkgewogICAgICAgIHBhZGRpbmc6IDQycHggMThweCA0OHB4OwogICAgfQoK
ICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtYnV5X19o
ZWFkIGgxIHsKICAgICAgICBmb250LXNpemU6IGNsYW1wKDU4cHgsIDE4dncsIDgycHgpOwogICAg
fQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtYnV5
X19kZXNjcmlwdG9yIHsKICAgICAgICBmb250LXNpemU6IDIwcHg7CiAgICB9CgogICAgW2RhdGEt
cGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1idXlfX2RlY2sgewogICAg
ICAgIGZvbnQtc2l6ZTogMTJweDsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGll
cl9lZGl0b3JpYWxfdjEiXSAubHhhLXByaWNlLXJvdyB7CiAgICAgICAgYWxpZ24taXRlbXM6IGZs
ZXgtc3RhcnQ7CiAgICAgICAgZmxleC1kaXJlY3Rpb246IGNvbHVtbjsKICAgIH0KCiAgICBbZGF0
YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWNvbG9yLWxpc3Qgewog
ICAgICAgIG1hcmdpbi1pbmxpbmU6IC0xOHB4OwogICAgICAgIHBhZGRpbmctaW5saW5lOiAxOHB4
OwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5s
eGEtYXNzdXJhbmNlLAogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3Yx
Il0gLmx4YS1taW5pLWZhY3RzIHsKICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IDFmcjsK
ICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhh
LWFzc3VyYW5jZSA+IGRpdiwKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlh
bF92MSJdIC5seGEtYXNzdXJhbmNlID4gZGl2ICsgZGl2IHsKICAgICAgICBwYWRkaW5nOiAxMnB4
IDA7CiAgICAgICAgYm9yZGVyLWxlZnQ6IDA7CiAgICAgICAgYm9yZGVyLXRvcDogMXB4IHNvbGlk
IHZhcigtLWx4YS1saW5lKTsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9l
ZGl0b3JpYWxfdjEiXSAubHhhLWFzc3VyYW5jZSA+IGRpdjpmaXJzdC1jaGlsZCB7CiAgICAgICAg
Ym9yZGVyLXRvcDogMDsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0
b3JpYWxfdjEiXSAubHhhLWZpbG1fX2l0ZW0sCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGll
cl9lZGl0b3JpYWxfdjEiXSAubHhhLWZpbG1fX2l0ZW06bnRoLWNoaWxkKDIpLAogICAgW2RhdGEt
cGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maWxtX19pdGVtOm50aC1j
aGlsZCg0KSB7CiAgICAgICAgZmxleC1iYXNpczogODR2dzsKICAgICAgICBoZWlnaHQ6IDcwc3Zo
OwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5s
eGEtbWFuaWZlc3RvIHsKICAgICAgICBtaW4taGVpZ2h0OiA2MjBweDsKICAgICAgICBwYWRkaW5n
OiA3MHB4IDIwcHg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9y
aWFsX3YxIl0gLmx4YS1tYW5pZmVzdG9fX2NvcHkgaDIgewogICAgICAgIGZvbnQtc2l6ZTogY2xh
bXAoNTJweCwgMTV2dywgODJweCk7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxp
ZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1jaGFwdGVyLAogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0
ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS10cnV0aCwKICAgIFtkYXRhLXBkcC12YXJpYW50PSJh
dGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbWF0ZXJpYWwsCiAgICBbZGF0YS1wZHAtdmFyaWFu
dD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZpbmFsZV9fY29weSB7CiAgICAgICAgd2lk
dGg6IG1pbigxMDAlIC0gMzJweCwgNTYwcHgpOwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50
PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtY2hhcHRlciB7CiAgICAgICAgcGFkZGluZy1i
bG9jazogOTJweDsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3Jp
YWxfdjEiXSAubHhhLWNoYXB0ZXJfX2hlYWQgaDIsCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRl
bGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLXRydXRoX19oZWFkIGgyLAogICAgW2RhdGEtcGRwLXZh
cmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1tYXRlcmlhbF9fc3RhdGVtZW50IGgy
IHsKICAgICAgICBmb250LXNpemU6IGNsYW1wKDQ4cHgsIDE0dncsIDc0cHgpOwogICAgfQoKICAg
IFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtZ2VzdHVyZS1n
cmlkIHsKICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IDFmcjsKICAgIH0KCiAgICBbZGF0
YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWdlc3R1cmUsCiAgICBb
ZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWdlc3R1cmU6bnRo
LWNoaWxkKDMpIHsKICAgICAgICBncmlkLWNvbHVtbjogYXV0bzsKICAgICAgICBtaW4taGVpZ2h0
OiA0MTBweDsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxf
djEiXSAubHhhLWZpdF9faW1hZ2UtY29weSB7CiAgICAgICAgcmlnaHQ6IDE4cHg7CiAgICAgICAg
Ym90dG9tOiAyNHB4OwogICAgICAgIGxlZnQ6IDE4cHg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZh
cmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1maXRfX2ltYWdlLWNvcHkgaDIsCiAg
ICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZpdF9fcGFu
ZWwgaDIsCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhh
LXNpemUtc3RvcnlfX2ludHJvIGgyIHsKICAgICAgICBmb250LXNpemU6IGNsYW1wKDQ4cHgsIDE0
dncsIDc0cHgpOwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlh
bF92MSJdIC5seGEtZml0X19tZXRyaWNzIHsKICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6
IDFmcjsKICAgIH0KCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEi
XSAubHhhLXRydXRoIHsKICAgICAgICBwYWRkaW5nLWJsb2NrOiA5MnB4OwogICAgfQoKICAgIFtk
YXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtdHJ1dGhfX2hlYWQg
ewogICAgICAgIG1hcmdpbi1ib3R0b206IDM0cHg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlh
bnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1tb3NhaWMgewogICAgICAgIGdyaWQtdGVt
cGxhdGUtY29sdW1uczogcmVwZWF0KDIsIG1pbm1heCgwLCAxZnIpKTsKICAgICAgICBncmlkLWF1
dG8tcm93czogMjEwcHg7CiAgICAgICAgZ2FwOiA4cHg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZh
cmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1tb3NhaWNfX2l0ZW0sCiAgICBbZGF0
YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1vc2FpY19faXRlbS0t
MSwKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbW9z
YWljX19pdGVtLS0yLAogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3Yx
Il0gLmx4YS1tb3NhaWNfX2l0ZW0tLTMsCiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9l
ZGl0b3JpYWxfdjEiXSAubHhhLW1vc2FpY19faXRlbS0tNCwKICAgIFtkYXRhLXBkcC12YXJpYW50
PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbW9zYWljX19pdGVtLS01IHsKICAgICAgICBn
cmlkLWNvbHVtbjogYXV0bzsKICAgICAgICBncmlkLXJvdzogYXV0bzsKICAgIH0KCiAgICBbZGF0
YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLW1vc2FpY19faXRlbTpm
aXJzdC1jaGlsZCwKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJd
IC5seGEtbW9zYWljX19pdGVtOmxhc3QtY2hpbGQ6bnRoLWNoaWxkKG9kZCkgewogICAgICAgIGdy
aWQtY29sdW1uOiBzcGFuIDI7CiAgICAgICAgbWluLWhlaWdodDogNDMwcHg7CiAgICB9CgogICAg
W2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1zaXplLXN0b3J5
X19pbnRybyB7CiAgICAgICAgcGFkZGluZzogNzZweCAxOHB4IDQ4cHg7CiAgICB9CgogICAgW2Rh
dGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3YxIl0gLmx4YS1zaXplLXN0b3J5X190
YWJsZS13cmFwIHsKICAgICAgICBtYXgtd2lkdGg6IGNhbGMoMTAwdncgLSAyMHB4KTsKICAgICAg
ICBtYXJnaW46IDAgMTBweCA2NHB4OwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVs
aWVyX2VkaXRvcmlhbF92MSJdIC5seGEtbWF0ZXJpYWwgewogICAgICAgIHBhZGRpbmctYmxvY2s6
IDk2cHg7CiAgICB9CgogICAgW2RhdGEtcGRwLXZhcmlhbnQ9ImF0ZWxpZXJfZWRpdG9yaWFsX3Yx
Il0gLmx4YS1tYXRlcmlhbF9fY2FyZHMgewogICAgICAgIGdyaWQtdGVtcGxhdGUtY29sdW1uczog
MWZyOwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJd
IC5seGEtbWF0ZXJpYWwtY2FyZCwKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRv
cmlhbF92MSJdIC5seGEtbWF0ZXJpYWwtY2FyZDpudGgtY2hpbGQobikgewogICAgICAgIG1pbi1o
ZWlnaHQ6IDMyMHB4OwogICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRv
cmlhbF92MSJdIC5seGEtZmluYWxlIHsKICAgICAgICBtaW4taGVpZ2h0OiA4NnN2aDsKICAgIH0K
CiAgICBbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAubHhhLWZpbmFs
ZV9fY29weSBoMiB7CiAgICAgICAgZm9udC1zaXplOiBjbGFtcCg1NHB4LCAxNXZ3LCA4MnB4KTsK
ICAgIH0KfQoKQG1lZGlhIChwcmVmZXJzLXJlZHVjZWQtbW90aW9uOiByZWR1Y2UpIHsKICAgIFtk
YXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdICosCiAgICBbZGF0YS1wZHAt
dmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAqOjpiZWZvcmUsCiAgICBbZGF0YS1wZHAt
dmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXSAqOjphZnRlciB7CiAgICAgICAgc2Nyb2xs
LWJlaGF2aW9yOiBhdXRvICFpbXBvcnRhbnQ7CiAgICAgICAgYW5pbWF0aW9uLWR1cmF0aW9uOiAu
MDFtcyAhaW1wb3J0YW50OwogICAgICAgIGFuaW1hdGlvbi1pdGVyYXRpb24tY291bnQ6IDEgIWlt
cG9ydGFudDsKICAgICAgICB0cmFuc2l0aW9uLWR1cmF0aW9uOiAuMDFtcyAhaW1wb3J0YW50Owog
ICAgfQoKICAgIFtkYXRhLXBkcC12YXJpYW50PSJhdGVsaWVyX2VkaXRvcmlhbF92MSJdIFtkYXRh
LWx4YS1yZXZlYWxdIHsKICAgICAgICBvcGFjaXR5OiAxOwogICAgICAgIHRyYW5zZm9ybTogbm9u
ZTsKICAgIH0KfQo=
ATELIER_PAYLOAD_1

decode_to_file 'public/commerce-v2/pdp/v1/variants/atelier-editorial-v1.js' <<'ATELIER_PAYLOAD_2'
aW1wb3J0ICcuLi9jb3JlLmpzJzsKCmNvbnN0IHJvb3QgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9y
KCdbZGF0YS1wZHAtdmFyaWFudD0iYXRlbGllcl9lZGl0b3JpYWxfdjEiXScpOwpjb25zdCBwcm9k
dWN0Tm9kZSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdseHYyUHJvZHVjdERhdGEnKTsKCmlm
IChyb290ICYmIHByb2R1Y3ROb2RlKSB7CiAgICBsZXQgcHJvZHVjdCA9IHt9OwoKICAgIHRyeSB7
CiAgICAgICAgcHJvZHVjdCA9IEpTT04ucGFyc2UocHJvZHVjdE5vZGUudGV4dENvbnRlbnQgfHwg
J3t9Jyk7CiAgICB9IGNhdGNoIChlcnJvcikgewogICAgICAgIGNvbnNvbGUuZXJyb3IoJ0tow7Ru
ZyDEkeG7jWMgxJHGsOG7o2MgUERQIHBheWxvYWQgY2hvIEF0ZWxpZXIgRWRpdG9yaWFsLicsIGVy
cm9yKTsKICAgIH0KCiAgICBjb25zdCByZWR1Y2VkTW90aW9uID0gd2luZG93Lm1hdGNoTWVkaWEo
JyhwcmVmZXJzLXJlZHVjZWQtbW90aW9uOiByZWR1Y2UpJykubWF0Y2hlczsKICAgIGNvbnN0IGNv
bG9ycyA9IEFycmF5LmlzQXJyYXkocHJvZHVjdC5jb2xvcnMpID8gcHJvZHVjdC5jb2xvcnMgOiBb
XTsKICAgIGNvbnN0IHJvbGVMYWJlbHMgPSB7CiAgICAgICAgaGVybzogJ1Thu5VuZyB0aOG7gycs
CiAgICAgICAgZnJvbnQ6ICdN4bq3dCB0csaw4bubYycsCiAgICAgICAgc2lkZTogJ0fDs2Mgbmdo
acOqbmcnLAogICAgICAgIGJhY2s6ICdN4bq3dCBzYXUnLAogICAgICAgIGRldGFpbDogJ0NoaSB0
aeG6v3QnLAogICAgICAgIGxpZmVzdHlsZTogJ1Ryw6puIG5nxrDhu51pIG3huqt1JywKICAgIH07
CgogICAgY29uc3Qgc2FmZUhleCA9ICh2YWx1ZSkgPT4gL14jWzAtOWEtZl17Myw4fSQvaS50ZXN0
KFN0cmluZyh2YWx1ZSB8fCAnJykpCiAgICAgICAgPyBTdHJpbmcodmFsdWUpCiAgICAgICAgOiAn
IzZlMjQzMCc7CgogICAgY29uc3QgYWN0aXZlQ29sb3IgPSAoKSA9PiB7CiAgICAgICAgY29uc3Qg
YnV0dG9uID0gcm9vdC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1seHBkcC1jb2xvcl0uaXMtYWN0aXZl
Jyk7CiAgICAgICAgY29uc3QgaWQgPSBTdHJpbmcoYnV0dG9uPy5kYXRhc2V0LmNvbG9ySWQgfHwg
JycpOwoKICAgICAgICByZXR1cm4gY29sb3JzLmZpbmQoKGNvbG9yKSA9PiBTdHJpbmcoY29sb3Iu
aWQpID09PSBpZCkKICAgICAgICAgICAgfHwgY29sb3JzLmZpbmQoKGNvbG9yKSA9PiBTdHJpbmco
Y29sb3IuaWQpID09PSBTdHJpbmcocHJvZHVjdC5kZWZhdWx0X2NvbG9yX2lkIHx8ICcnKSkKICAg
ICAgICAgICAgfHwgY29sb3JzLmZpbmQoKGNvbG9yKSA9PiBjb2xvci5zZWxsYWJsZSkKICAgICAg
ICAgICAgfHwgY29sb3JzWzBdCiAgICAgICAgICAgIHx8IG51bGw7CiAgICB9OwoKICAgIGNvbnN0
IGltYWdlVXJsID0gKG1lZGlhKSA9PiBTdHJpbmcobWVkaWE/LnVybCB8fCBtZWRpYT8udGh1bWJf
dXJsIHx8ICcnKTsKCiAgICBjb25zdCBjcmVhdGVGaWxtSXRlbSA9IChtZWRpYSwgaW5kZXgpID0+
IHsKICAgICAgICBjb25zdCBmaWd1cmUgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdmaWd1cmUn
KTsKICAgICAgICBmaWd1cmUuY2xhc3NOYW1lID0gYGx4YS1maWxtX19pdGVtJHtpbmRleCA9PT0g
MCA/ICcgaXMtbGVhZCcgOiAnJ31gOwogICAgICAgIGZpZ3VyZS5kYXRhc2V0Lmx4YUZpbG1JdGVt
ID0gJyc7CgogICAgICAgIGNvbnN0IGltYWdlID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnaW1n
Jyk7CiAgICAgICAgaW1hZ2Uuc3JjID0gaW1hZ2VVcmwobWVkaWEpOwogICAgICAgIGltYWdlLmFs
dCA9IGAke3Byb2R1Y3QubmFtZSB8fCAnU+G6o24gcGjhuqltJ30g4oCUICR7cm9sZUxhYmVsc1tt
ZWRpYT8ucm9sZV0gfHwgJ0jDrG5oIOG6o25oIHPhuqNuIHBo4bqpbSd9YDsKICAgICAgICBpbWFn
ZS5sb2FkaW5nID0gaW5kZXggPCAyID8gJ2VhZ2VyJyA6ICdsYXp5JzsKICAgICAgICBpbWFnZS5k
ZWNvZGluZyA9ICdhc3luYyc7CgogICAgICAgIGNvbnN0IGNhcHRpb24gPSBkb2N1bWVudC5jcmVh
dGVFbGVtZW50KCdmaWdjYXB0aW9uJyk7CiAgICAgICAgY29uc3QgY291bnQgPSBkb2N1bWVudC5j
cmVhdGVFbGVtZW50KCdzcGFuJyk7CiAgICAgICAgY29uc3QgbGFiZWwgPSBkb2N1bWVudC5jcmVh
dGVFbGVtZW50KCdzdHJvbmcnKTsKICAgICAgICBjb3VudC50ZXh0Q29udGVudCA9IFN0cmluZyhp
bmRleCArIDEpLnBhZFN0YXJ0KDIsICcwJyk7CiAgICAgICAgbGFiZWwudGV4dENvbnRlbnQgPSBy
b2xlTGFiZWxzW21lZGlhPy5yb2xlXSB8fCAnQ2hpIHRp4bq/dCBz4bqjbiBwaOG6qW0nOwogICAg
ICAgIGNhcHRpb24uYXBwZW5kKGNvdW50LCBsYWJlbCk7CiAgICAgICAgZmlndXJlLmFwcGVuZChp
bWFnZSwgY2FwdGlvbik7CgogICAgICAgIHJldHVybiBmaWd1cmU7CiAgICB9OwoKICAgIGNvbnN0
IHJlbmRlckZpbG0gPSAoY29sb3IpID0+IHsKICAgICAgICBjb25zdCBmaWxtID0gcm9vdC5xdWVy
eVNlbGVjdG9yKCdbZGF0YS1seGEtZmlsbV0nKTsKICAgICAgICBpZiAoIWZpbG0pIHJldHVybjsK
CiAgICAgICAgY29uc3QgbWVkaWEgPSBBcnJheS5pc0FycmF5KGNvbG9yPy5tZWRpYSkKICAgICAg
ICAgICAgPyBjb2xvci5tZWRpYS5zbGljZSgwLCA1KS5maWx0ZXIoKGl0ZW0pID0+IGltYWdlVXJs
KGl0ZW0pKQogICAgICAgICAgICA6IFtdOwoKICAgICAgICBpZiAoIW1lZGlhLmxlbmd0aCkgewog
ICAgICAgICAgICBmaWxtLmhpZGRlbiA9IHRydWU7CiAgICAgICAgICAgIHJldHVybjsKICAgICAg
ICB9CgogICAgICAgIGNvbnN0IGZyYWdtZW50ID0gZG9jdW1lbnQuY3JlYXRlRG9jdW1lbnRGcmFn
bWVudCgpOwogICAgICAgIG1lZGlhLmZvckVhY2goKGl0ZW0sIGluZGV4KSA9PiBmcmFnbWVudC5h
cHBlbmQoY3JlYXRlRmlsbUl0ZW0oaXRlbSwgaW5kZXgpKSk7CiAgICAgICAgZmlsbS5yZXBsYWNl
Q2hpbGRyZW4oZnJhZ21lbnQpOwogICAgICAgIGZpbG0uaGlkZGVuID0gZmFsc2U7CiAgICB9OwoK
ICAgIGNvbnN0IHVwZGF0ZUZpbmFsZSA9IChjb2xvcikgPT4gewogICAgICAgIGNvbnN0IGZpbmFs
ZSA9IHJvb3QucXVlcnlTZWxlY3RvcignW2RhdGEtbHhhLWZpbmFsZS1pbWFnZV0nKTsKICAgICAg
ICBpZiAoIWZpbmFsZSkgcmV0dXJuOwoKICAgICAgICBjb25zdCBtZWRpYSA9IEFycmF5LmlzQXJy
YXkoY29sb3I/Lm1lZGlhKSA/IGNvbG9yLm1lZGlhLmZpbHRlcigoaXRlbSkgPT4gaW1hZ2VVcmwo
aXRlbSkpIDogW107CiAgICAgICAgY29uc3QgcHJlZmVycmVkID0gbWVkaWEuZmluZCgoaXRlbSkg
PT4gaXRlbS5yb2xlID09PSAnbGlmZXN0eWxlJykKICAgICAgICAgICAgfHwgbWVkaWEuZmluZCgo
aXRlbSkgPT4gaXRlbS5yb2xlID09PSAnYmFjaycpCiAgICAgICAgICAgIHx8IG1lZGlhLmF0KC0x
KQogICAgICAgICAgICB8fCBtZWRpYVswXTsKCiAgICAgICAgaWYgKHByZWZlcnJlZCkgewogICAg
ICAgICAgICBmaW5hbGUuc3JjID0gaW1hZ2VVcmwocHJlZmVycmVkKTsKICAgICAgICAgICAgZmlu
YWxlLmFsdCA9IGAke3Byb2R1Y3QubmFtZSB8fCAnU+G6o24gcGjhuqltJ30g4oCUICR7Y29sb3I/
LmxhYmVsIHx8ICcnfWAudHJpbSgpOwogICAgICAgIH0KICAgIH07CgogICAgY29uc3QgYXBwbHlD
b2xvckF0bW9zcGhlcmUgPSAoY29sb3IpID0+IHsKICAgICAgICByb290LnN0eWxlLnNldFByb3Bl
cnR5KCctLWx4YS1jdXJyZW50LWNvbG9yJywgc2FmZUhleChjb2xvcj8uaGV4KSk7CiAgICAgICAg
cmVuZGVyRmlsbShjb2xvcik7CiAgICAgICAgdXBkYXRlRmluYWxlKGNvbG9yKTsKICAgIH07Cgog
ICAgcm9vdC5xdWVyeVNlbGVjdG9yQWxsKCdbZGF0YS1seHBkcC1jb2xvcl0nKS5mb3JFYWNoKChi
dXR0b24pID0+IHsKICAgICAgICBidXR0b24uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKSA9
PiB7CiAgICAgICAgICAgIGNvbnN0IGNvbG9yID0gY29sb3JzLmZpbmQoCiAgICAgICAgICAgICAg
ICAoaXRlbSkgPT4gU3RyaW5nKGl0ZW0uaWQpID09PSBTdHJpbmcoYnV0dG9uLmRhdGFzZXQuY29s
b3JJZCkKICAgICAgICAgICAgKTsKCiAgICAgICAgICAgIGlmIChjb2xvcikgewogICAgICAgICAg
ICAgICAgd2luZG93LnJlcXVlc3RBbmltYXRpb25GcmFtZSgoKSA9PiBhcHBseUNvbG9yQXRtb3Nw
aGVyZShjb2xvcikpOwogICAgICAgICAgICB9CiAgICAgICAgfSk7CiAgICB9KTsKCiAgICBhcHBs
eUNvbG9yQXRtb3NwaGVyZShhY3RpdmVDb2xvcigpKTsKCiAgICByb290LnF1ZXJ5U2VsZWN0b3JB
bGwoJ1tkYXRhLXBkcC1zY3JvbGwtdG8tcHVyY2hhc2VdJykuZm9yRWFjaCgoYnV0dG9uKSA9PiB7
CiAgICAgICAgYnV0dG9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKCkgPT4gewogICAgICAg
ICAgICByb290LnF1ZXJ5U2VsZWN0b3IoJyNseGFQdXJjaGFzZVBhbmVsJyk/LnNjcm9sbEludG9W
aWV3KHsKICAgICAgICAgICAgICAgIGJlaGF2aW9yOiByZWR1Y2VkTW90aW9uID8gJ2F1dG8nIDog
J3Ntb290aCcsCiAgICAgICAgICAgICAgICBibG9jazogJ2NlbnRlcicsCiAgICAgICAgICAgIH0p
OwogICAgICAgIH0pOwogICAgfSk7CgogICAgY29uc3QgcmV2ZWFsSXRlbXMgPSBBcnJheS5mcm9t
KHJvb3QucXVlcnlTZWxlY3RvckFsbCgnW2RhdGEtbHhhLXJldmVhbF0nKSk7CgogICAgaWYgKCEo
J0ludGVyc2VjdGlvbk9ic2VydmVyJyBpbiB3aW5kb3cpIHx8IHJlZHVjZWRNb3Rpb24pIHsKICAg
ICAgICByZXZlYWxJdGVtcy5mb3JFYWNoKChpdGVtKSA9PiBpdGVtLmNsYXNzTGlzdC5hZGQoJ2lz
LXZpc2libGUnKSk7CiAgICB9IGVsc2UgewogICAgICAgIGNvbnN0IHJldmVhbE9ic2VydmVyID0g
bmV3IEludGVyc2VjdGlvbk9ic2VydmVyKChlbnRyaWVzLCBvYnNlcnZlcikgPT4gewogICAgICAg
ICAgICBlbnRyaWVzLmZvckVhY2goKGVudHJ5KSA9PiB7CiAgICAgICAgICAgICAgICBpZiAoIWVu
dHJ5LmlzSW50ZXJzZWN0aW5nKSByZXR1cm47CiAgICAgICAgICAgICAgICBlbnRyeS50YXJnZXQu
Y2xhc3NMaXN0LmFkZCgnaXMtdmlzaWJsZScpOwogICAgICAgICAgICAgICAgb2JzZXJ2ZXIudW5v
YnNlcnZlKGVudHJ5LnRhcmdldCk7CiAgICAgICAgICAgIH0pOwogICAgICAgIH0sIHsKICAgICAg
ICAgICAgdGhyZXNob2xkOiAwLjEyLAogICAgICAgICAgICByb290TWFyZ2luOiAnMHB4IDBweCAt
NyUgMHB4JywKICAgICAgICB9KTsKCiAgICAgICAgcmV2ZWFsSXRlbXMuZm9yRWFjaCgoaXRlbSkg
PT4gcmV2ZWFsT2JzZXJ2ZXIub2JzZXJ2ZShpdGVtKSk7CiAgICB9CgogICAgY29uc3QgcHJvZ3Jl
c3MgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTsKICAgIGNvbnN0IHByb2dyZXNzQmFy
ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnaScpOwogICAgcHJvZ3Jlc3MuY2xhc3NOYW1lID0g
J2x4YS1zY3JvbGwtcHJvZ3Jlc3MnOwogICAgcHJvZ3Jlc3Muc2V0QXR0cmlidXRlKCdhcmlhLWhp
ZGRlbicsICd0cnVlJyk7CiAgICBwcm9ncmVzcy5hcHBlbmQocHJvZ3Jlc3NCYXIpOwogICAgcm9v
dC5wcmVwZW5kKHByb2dyZXNzKTsKCiAgICBsZXQgc2Nyb2xsUXVldWVkID0gZmFsc2U7CiAgICBj
b25zdCB1cGRhdGVQcm9ncmVzcyA9ICgpID0+IHsKICAgICAgICBzY3JvbGxRdWV1ZWQgPSBmYWxz
ZTsKICAgICAgICBjb25zdCByZWN0ID0gcm9vdC5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKTsKICAg
ICAgICBjb25zdCB0b3RhbCA9IE1hdGgubWF4KDEsIHJvb3Quc2Nyb2xsSGVpZ2h0IC0gd2luZG93
LmlubmVySGVpZ2h0KTsKICAgICAgICBjb25zdCBwYXNzZWQgPSBNYXRoLm1pbih0b3RhbCwgTWF0
aC5tYXgoMCwgLXJlY3QudG9wKSk7CiAgICAgICAgcHJvZ3Jlc3NCYXIuc3R5bGUud2lkdGggPSBg
JHsocGFzc2VkIC8gdG90YWwpICogMTAwfSVgOwogICAgfTsKCiAgICB3aW5kb3cuYWRkRXZlbnRM
aXN0ZW5lcignc2Nyb2xsJywgKCkgPT4gewogICAgICAgIGlmIChzY3JvbGxRdWV1ZWQpIHJldHVy
bjsKICAgICAgICBzY3JvbGxRdWV1ZWQgPSB0cnVlOwogICAgICAgIHdpbmRvdy5yZXF1ZXN0QW5p
bWF0aW9uRnJhbWUodXBkYXRlUHJvZ3Jlc3MpOwogICAgfSwgeyBwYXNzaXZlOiB0cnVlIH0pOwog
ICAgdXBkYXRlUHJvZ3Jlc3MoKTsKCiAgICBpZiAoIXJlZHVjZWRNb3Rpb24gJiYgd2luZG93Lm1h
dGNoTWVkaWEoJyhwb2ludGVyOiBmaW5lKScpLm1hdGNoZXMpIHsKICAgICAgICBjb25zdCBoZXJv
ID0gcm9vdC5xdWVyeVNlbGVjdG9yKCcubHhhLWhlcm9fX21lZGlhJyk7CiAgICAgICAgY29uc3Qg
aGVyb0ltYWdlID0gcm9vdC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1seHBkcC1tYWluLWltYWdlXScp
OwoKICAgICAgICBoZXJvPy5hZGRFdmVudExpc3RlbmVyKCdwb2ludGVybW92ZScsIChldmVudCkg
PT4gewogICAgICAgICAgICBpZiAoIWhlcm9JbWFnZSkgcmV0dXJuOwogICAgICAgICAgICBjb25z
dCByZWN0ID0gaGVyby5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKTsKICAgICAgICAgICAgY29uc3Qg
eCA9ICgoZXZlbnQuY2xpZW50WCAtIHJlY3QubGVmdCkgLyByZWN0LndpZHRoIC0gLjUpICogMTI7
CiAgICAgICAgICAgIGNvbnN0IHkgPSAoKGV2ZW50LmNsaWVudFkgLSByZWN0LnRvcCkgLyByZWN0
LmhlaWdodCAtIC41KSAqIDg7CiAgICAgICAgICAgIGhlcm9JbWFnZS5zdHlsZS5zZXRQcm9wZXJ0
eSgnLS1seGEtc2hpZnQteCcsIGAke3h9cHhgKTsKICAgICAgICAgICAgaGVyb0ltYWdlLnN0eWxl
LnNldFByb3BlcnR5KCctLWx4YS1zaGlmdC15JywgYCR7eX1weGApOwogICAgICAgIH0pOwoKICAg
ICAgICBoZXJvPy5hZGRFdmVudExpc3RlbmVyKCdwb2ludGVybGVhdmUnLCAoKSA9PiB7CiAgICAg
ICAgICAgIGhlcm9JbWFnZT8uc3R5bGUuc2V0UHJvcGVydHkoJy0tbHhhLXNoaWZ0LXgnLCAnMHB4
Jyk7CiAgICAgICAgICAgIGhlcm9JbWFnZT8uc3R5bGUuc2V0UHJvcGVydHkoJy0tbHhhLXNoaWZ0
LXknLCAnMHB4Jyk7CiAgICAgICAgfSk7CiAgICB9CgogICAgaWYgKCdJbnRlcnNlY3Rpb25PYnNl
cnZlcicgaW4gd2luZG93KSB7CiAgICAgICAgY29uc3Qgc2VjdGlvbk9ic2VydmVyID0gbmV3IElu
dGVyc2VjdGlvbk9ic2VydmVyKChlbnRyaWVzKSA9PiB7CiAgICAgICAgICAgIGVudHJpZXMuZm9y
RWFjaCgoZW50cnkpID0+IHsKICAgICAgICAgICAgICAgIGlmICghZW50cnkuaXNJbnRlcnNlY3Rp
bmcgfHwgZW50cnkudGFyZ2V0LmRhdGFzZXQubHhhU2VlbiA9PT0gJzEnKSByZXR1cm47CiAgICAg
ICAgICAgICAgICBlbnRyeS50YXJnZXQuZGF0YXNldC5seGFTZWVuID0gJzEnOwogICAgICAgICAg
ICAgICAgcm9vdC5kaXNwYXRjaEV2ZW50KG5ldyBDdXN0b21FdmVudCgnbGlueGVuOnBkcDpzZWN0
aW9uLXZpZXdlZCcsIHsKICAgICAgICAgICAgICAgICAgICBidWJibGVzOiB0cnVlLAogICAgICAg
ICAgICAgICAgICAgIGRldGFpbDogewogICAgICAgICAgICAgICAgICAgICAgICB2YXJpYW50OiAn
YXRlbGllcl9lZGl0b3JpYWxfdjEnLAogICAgICAgICAgICAgICAgICAgICAgICBzZWN0aW9uOiBl
bnRyeS50YXJnZXQuZGF0YXNldC5wZHBTZWN0aW9uIHx8IG51bGwsCiAgICAgICAgICAgICAgICAg
ICAgICAgIHByb2R1Y3RfaWQ6IHByb2R1Y3QuaWQgfHwgbnVsbCwKICAgICAgICAgICAgICAgICAg
ICB9LAogICAgICAgICAgICAgICAgfSkpOwogICAgICAgICAgICB9KTsKICAgICAgICB9LCB7IHRo
cmVzaG9sZDogLjMyIH0pOwoKICAgICAgICByb290LnF1ZXJ5U2VsZWN0b3JBbGwoJ1tkYXRhLXBk
cC1zZWN0aW9uXScpLmZvckVhY2goKHNlY3Rpb24pID0+IHsKICAgICAgICAgICAgc2VjdGlvbk9i
c2VydmVyLm9ic2VydmUoc2VjdGlvbik7CiAgICAgICAgfSk7CiAgICB9CgogICAgcm9vdC5kaXNw
YXRjaEV2ZW50KG5ldyBDdXN0b21FdmVudCgnbGlueGVuOnBkcDphdGVsaWVyLXJlYWR5Jywgewog
ICAgICAgIGJ1YmJsZXM6IHRydWUsCiAgICAgICAgZGV0YWlsOiB7CiAgICAgICAgICAgIHZhcmlh
bnQ6ICdhdGVsaWVyX2VkaXRvcmlhbF92MScsCiAgICAgICAgICAgIHByb2R1Y3RfaWQ6IHByb2R1
Y3QuaWQgfHwgbnVsbCwKICAgICAgICB9LAogICAgfSkpOwp9Cg==
ATELIER_PAYLOAD_2

decode_to_file 'resources/views/commerce_v2/pdp/atelier/design-gestures.blade.php' <<'ATELIER_PAYLOAD_3'
QHBocAogICAgJGl0ZW1zID0gY29sbGVjdCgoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdwcm9kdWN0
X3RydXRoLmhpZ2hsaWdodHMnLCBbXSkpOwogICAgaWYgKCRpdGVtcy0+aXNFbXB0eSgpKSB7CiAg
ICAgICAgJGl0ZW1zID0gY29sbGVjdCgoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdwcm9kdWN0X3Ry
dXRoLmRlc2lnbi5pdGVtcycsIFtdKSk7CiAgICB9CiAgICAkaXRlbXMgPSAkaXRlbXMtPnRha2Uo
MyktPnZhbHVlcygpOwogICAgJGRlc2NyaXB0aW9ucyA9IFsKICAgICAgICAnc2lsaG91ZXR0ZScg
PT4gJ8SQxrDhu51uZyBj4bqvdCDEkeG7i25oIGjDrG5oIHThu5VuZyB0aOG7gyB2w6AgdOG6oW8g
a2hv4bqjbmcgdGjhu58gdOG7sSBuaGnDqm4ga2hpIG3hurdjLicsCiAgICAgICAgJ2Zvcm0gZMOh
bmcnID0+ICfEkMaw4budbmcgY+G6r3QgxJHhu4tuaCBow6xuaCB04buVbmcgdGjhu4MgdsOgIHTh
uqFvIGtob+G6o25nIHRo4bufIHThu7Egbmhpw6puIGtoaSBt4bq3Yy4nLAogICAgICAgICduZWNr
bGluZScgPT4gJ03hu5l0IMSRaeG7g20gbmjDrG4gZ+G7jW4gZ8OgbmcgZ2nDunAgcGjhuqduIGPh
u5UgdsOgIGtodcO0biBt4bq3dCBu4buVaSBi4bqtdCBoxqFuLicsCiAgICAgICAgJ2Phu5Ugw6Fv
JyA9PiAnTeG7mXQgxJFp4buDbSBuaMOsbiBn4buNbiBnw6BuZyBnacO6cCBwaOG6p24gY+G7lSB2
w6Aga2h1w7RuIG3hurd0IG7hu5VpIGLhuq10IGjGoW4uJywKICAgICAgICAnc2xlZXZlX2xlbmd0
aCcgPT4gJ1Thu7cgbOG7hyB0YXkgw6FvIHThuqFvIMSR4buZIG3hu4FtIHbDoCBjw6JuIGLhurFu
ZyBjaG8gYuG7nSB2YWkuJywKICAgICAgICAndGF5IMOhbycgPT4gJ1Thu7cgbOG7hyB0YXkgw6Fv
IHThuqFvIMSR4buZIG3hu4FtIHbDoCBjw6JuIGLhurFuZyBjaG8gYuG7nSB2YWkuJywKICAgICAg
ICAnbGVuZ3RoJyA9PiAnQ2hp4buBdSBkw6BpIMSRxrDhu6NjIHjDoWMgbWluaCDEkeG7gyBi4bqh
biBow6xuaCBkdW5nIHLDtSB04bu3IGzhu4cgdHLGsOG7m2Mga2hpIGNo4buNbi4nLAogICAgICAg
ICfEkeG7mSBkw6BpJyA9PiAnQ2hp4buBdSBkw6BpIMSRxrDhu6NjIHjDoWMgbWluaCDEkeG7gyBi
4bqhbiBow6xuaCBkdW5nIHLDtSB04bu3IGzhu4cgdHLGsOG7m2Mga2hpIGNo4buNbi4nLAogICAg
XTsKICAgICRjYXJkQ2xhc3NlcyA9IFsnaXMtaW5rJywgJ2lzLXdpbmUnLCAnaXMtcGFwZXInXTsK
QGVuZHBocAoKQGlmKCRpdGVtcy0+aXNOb3RFbXB0eSgpKQogICAgPGRpdiBjbGFzcz0ibHhhLWNo
YXB0ZXIgbHhhLWdlc3R1cmVzIiBkYXRhLWx4YS1yZXZlYWw+CiAgICAgICAgPGhlYWRlciBjbGFz
cz0ibHhhLWNoYXB0ZXJfX2hlYWQiPgogICAgICAgICAgICA8cCBjbGFzcz0ibHhhLWtpY2tlciI+
RGVzaWduZWQgaW4gZ2VzdHVyZXM8L3A+CiAgICAgICAgICAgIDxoMj5OaOG7r25nIGNoaSB0aeG6
v3QgdOG6oW8gbsOqbiBk4bqldSDhuqVuLjwvaDI+CiAgICAgICAgPC9oZWFkZXI+CgogICAgICAg
IDxkaXYgY2xhc3M9Imx4YS1nZXN0dXJlLWdyaWQiPgogICAgICAgICAgICBAZm9yZWFjaCgkaXRl
bXMgYXMgJGluZGV4ID0+ICRpdGVtKQogICAgICAgICAgICAgICAgQHBocAogICAgICAgICAgICAg
ICAgICAgICRsb29rdXAgPSBtYl9zdHJ0b2xvd2VyKChzdHJpbmcpIChkYXRhX2dldCgkaXRlbSwg
J2tleScpID86IGRhdGFfZ2V0KCRpdGVtLCAnbGFiZWwnKSkpOwogICAgICAgICAgICAgICAgICAg
ICRkZXNjcmlwdGlvbiA9ICRkZXNjcmlwdGlvbnNbJGxvb2t1cF0gPz8gJ03hu5l0IGNoaSB0aeG6
v3QgxJHDoyDEkcaw4bujYyB4w6FjIG1pbmggdHJvbmcgaOG7kyBzxqEgc+G6o24gcGjhuqltLic7
CiAgICAgICAgICAgICAgICBAZW5kcGhwCiAgICAgICAgICAgICAgICA8YXJ0aWNsZSBjbGFzcz0i
bHhhLWdlc3R1cmUge3sgJGNhcmRDbGFzc2VzWyRpbmRleF0gPz8gJ2lzLXBhcGVyJyB9fSI+CiAg
ICAgICAgICAgICAgICAgICAgPHNwYW4gY2xhc3M9Imx4YS1nZXN0dXJlX19pbmRleCI+e3sgc3Ry
X3BhZCgoc3RyaW5nKSAoJGluZGV4ICsgMSksIDIsICcwJywgU1RSX1BBRF9MRUZUKSB9fTwvc3Bh
bj4KICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJseGEtZ2VzdHVyZV9fb3JiaXQiIGFy
aWEtaGlkZGVuPSJ0cnVlIj48L2Rpdj4KICAgICAgICAgICAgICAgICAgICA8ZGl2PgogICAgICAg
ICAgICAgICAgICAgICAgICA8cD57eyBkYXRhX2dldCgkaXRlbSwgJ2xhYmVsJykgfX08L3A+CiAg
ICAgICAgICAgICAgICAgICAgICAgIDxoMz57eyBkYXRhX2dldCgkaXRlbSwgJ3ZhbHVlJykgfX08
L2gzPgogICAgICAgICAgICAgICAgICAgICAgICA8c3Bhbj57eyAkZGVzY3JpcHRpb24gfX08L3Nw
YW4+CiAgICAgICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgICAgICA8L2FydGljbGU+
CiAgICAgICAgICAgIEBlbmRmb3JlYWNoCiAgICAgICAgPC9kaXY+CiAgICA8L2Rpdj4KQGVuZGlm
Cg==
ATELIER_PAYLOAD_3

decode_to_file 'resources/views/commerce_v2/pdp/atelier/finale.blade.php' <<'ATELIER_PAYLOAD_4'
QHBocAogICAgJGlkZW50aXR5ID0gKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnaWRlbnRpdHknLCBb
XSk7CiAgICAkY29tbWVyY2UgPSAoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdjb21tZXJjZScsIFtd
KTsKICAgICRtZWRpYSA9IGNvbGxlY3QoKGFycmF5KSBkYXRhX2dldCgkY29tbWVyY2UsICdkZWZh
dWx0X2NvbG9yLm1lZGlhJywgW10pKTsKICAgICRpbWFnZSA9IGRhdGFfZ2V0KCRtZWRpYS0+bGFz
dCgpLCAndXJsJykKICAgICAgICA/OiBkYXRhX2dldCgkbWVkaWEtPmZpcnN0KCksICd1cmwnKQog
ICAgICAgID86IGRhdGFfZ2V0KCRwZHAsICdtZWRpYS5jb3Zlcl91cmwnKTsKQGVuZHBocAoKPGRp
diBjbGFzcz0ibHhhLWZpbmFsZSIgZGF0YS1seGEtcmV2ZWFsPgogICAgQGlmKCRpbWFnZSkKICAg
ICAgICA8aW1nCiAgICAgICAgICAgIGNsYXNzPSJseGEtZmluYWxlX19pbWFnZSIKICAgICAgICAg
ICAgZGF0YS1seGEtZmluYWxlLWltYWdlCiAgICAgICAgICAgIHNyYz0ie3sgJGltYWdlIH19Igog
ICAgICAgICAgICBhbHQ9Int7IGRhdGFfZ2V0KCRpZGVudGl0eSwgJ25hbWUnKSB9fSIKICAgICAg
ICAgICAgbG9hZGluZz0ibGF6eSIKICAgICAgICAgICAgZGVjb2Rpbmc9ImFzeW5jIgogICAgICAg
ID4KICAgIEBlbmRpZgogICAgPGRpdiBjbGFzcz0ibHhhLWZpbmFsZV9fc2hhZGUiIGFyaWEtaGlk
ZGVuPSJ0cnVlIj48L2Rpdj4KICAgIDxkaXYgY2xhc3M9Imx4YS1maW5hbGVfX2NvcHkiPgogICAg
ICAgIDxwIGNsYXNzPSJseGEta2lja2VyIGx4YS1raWNrZXItLWxpZ2h0Ij5Zb3VyIGNvbG91ci4g
WW91ciBmaXQuPC9wPgogICAgICAgIDxoMj5C4bqhbiDEkcOjIHRo4bqleSBwaG9tLjxicj5HaeG7
nSBow6N5IGNo4buNbiBwaGnDqm4gYuG6o24gY+G7p2EgbcOsbmguPC9oMj4KICAgICAgICA8cD5D
aOG7jW4gbcOgdSwgxJHhu5FpIGNoaeG6v3Ugc2l6ZSB2w6AgdGjDqm0gc+G6o24gcGjhuqltIHbD
oG8gZ2nhu48ga2hpIG3hu41pIGNoaSB0aeG6v3QgxJHDoyDEkeG7pyByw7UuPC9wPgogICAgICAg
IDxidXR0b24gdHlwZT0iYnV0dG9uIiBjbGFzcz0ibHhhLWZpbmFsZV9fYnV0dG9uIiBkYXRhLXBk
cC1zY3JvbGwtdG8tcHVyY2hhc2U+CiAgICAgICAgICAgIFRy4bufIGzhuqFpIGNo4buNbiBtw6B1
ICYgc2l6ZQogICAgICAgIDwvYnV0dG9uPgogICAgICAgIDxkaXYgY2xhc3M9Imx4YS1maW5hbGVf
X3BvbGljaWVzIj4KICAgICAgICAgICAgPHNwYW4+VGhhbmggdG/DoW4ga2hpIG5o4bqtbiBow6Bu
Zzwvc3Bhbj4KICAgICAgICAgICAgPHNwYW4+SOG7lyB0cuG7oyDEkeG7lWkgc2l6ZTwvc3Bhbj4K
ICAgICAgICAgICAgPHNwYW4+R2lhbyBow6BuZyB0b8OgbiBxdeG7kWM8L3NwYW4+CiAgICAgICAg
PC9kaXY+CiAgICA8L2Rpdj4KPC9kaXY+Cg==
ATELIER_PAYLOAD_4

decode_to_file 'resources/views/commerce_v2/pdp/atelier/fit-story.blade.php' <<'ATELIER_PAYLOAD_5'
QHBocAogICAgJGlkZW50aXR5ID0gKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnaWRlbnRpdHknLCBb
XSk7CiAgICAkY2hhcnQgPSAoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdmaXQuZ2FybWVudF9zaXpl
X2NoYXJ0JywgW10pOwogICAgJHBvaW50cyA9IGNvbGxlY3QoKGFycmF5KSBkYXRhX2dldCgkY2hh
cnQsICdwb2ludHMnLCBbXSkpOwogICAgJGRlZmF1bHRNZWRpYSA9IGNvbGxlY3QoKGFycmF5KSBk
YXRhX2dldCgkcGRwLCAnY29tbWVyY2UuZGVmYXVsdF9jb2xvci5tZWRpYScsIFtdKSk7CiAgICAk
cHJvZHVjdGlvbiA9IGNvbGxlY3QoKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnbWVkaWEucHJvZHVj
dGlvbl90cnV0aCcsIFtdKSk7CiAgICAkaW1hZ2UgPSBkYXRhX2dldCgkcHJvZHVjdGlvbi0+Zmly
c3QoKSwgJ3VybCcpCiAgICAgICAgPzogZGF0YV9nZXQoJGRlZmF1bHRNZWRpYS0+Z2V0KDEpLCAn
dXJsJykKICAgICAgICA/OiBkYXRhX2dldCgkZGVmYXVsdE1lZGlhLT5maXJzdCgpLCAndXJsJykK
ICAgICAgICA/OiBkYXRhX2dldCgkcGRwLCAnbWVkaWEuY292ZXJfdXJsJyk7CiAgICAkZmluZFBv
aW50ID0gZnVuY3Rpb24gKGFycmF5ICRjb2RlcykgdXNlICgkcG9pbnRzKTogYXJyYXkgewogICAg
ICAgIHJldHVybiAoYXJyYXkpICgkcG9pbnRzLT5maXJzdChmdW5jdGlvbiAoJHBvaW50KSB1c2Ug
KCRjb2RlcykgewogICAgICAgICAgICAkY29kZSA9IG1iX3N0cnRvbG93ZXIoKHN0cmluZykgZGF0
YV9nZXQoJHBvaW50LCAnY29kZScpKTsKICAgICAgICAgICAgJGxhYmVsID0gbWJfc3RydG9sb3dl
cigoc3RyaW5nKSBkYXRhX2dldCgkcG9pbnQsICdsYWJlbCcpKTsKICAgICAgICAgICAgcmV0dXJu
IGNvbGxlY3QoJGNvZGVzKS0+Y29udGFpbnMoZm4gKCRuZWVkbGUpID0+IHN0cl9jb250YWlucygk
Y29kZS4nICcuJGxhYmVsLCAkbmVlZGxlKSk7CiAgICAgICAgfSkgPzogW10pOwogICAgfTsKICAg
ICRsZW5ndGhQb2ludCA9ICRmaW5kUG9pbnQoWydkcmVzc19sZW5ndGgnLCAnZMOgaSB2w6F5Jywg
J2xlbmd0aCddKTsKICAgICRidXN0UG9pbnQgPSAkZmluZFBvaW50KFsnYnVzdCcsICduZ+G7sWMn
XSk7CiAgICAkd2Fpc3RQb2ludCA9ICRmaW5kUG9pbnQoWyd3YWlzdCcsICdlbyddKTsKICAgICRz
aXplcyA9IGNvbGxlY3QoKGFycmF5KSBkYXRhX2dldCgkY2hhcnQsICdzaXplcycsIFtdKSk7CiAg
ICAkZm9jdXNTaXplID0gKHN0cmluZykgKCRzaXplcy0+Y29udGFpbnMoJ00nKSA/ICdNJyA6ICgk
c2l6ZXMtPmZpcnN0KCkgPzogJycpKTsKICAgICRkaXNwbGF5VmFsdWUgPSBmdW5jdGlvbiAoYXJy
YXkgJHBvaW50LCBzdHJpbmcgJHNpemUpOiBzdHJpbmcgewogICAgICAgICRkaXNwbGF5ID0gZGF0
YV9nZXQoJHBvaW50LCAnZGlzcGxheV92YWx1ZXMuJy4kc2l6ZSk7CiAgICAgICAgJHJhdyA9IGRh
dGFfZ2V0KCRwb2ludCwgJ3ZhbHVlcy4nLiRzaXplKTsKICAgICAgICAkdmFsdWUgPSAkZGlzcGxh
eSAhPT0gbnVsbCAmJiAkZGlzcGxheSAhPT0gJycgPyAkZGlzcGxheSA6ICRyYXc7CiAgICAgICAg
cmV0dXJuICR2YWx1ZSAhPT0gbnVsbCAmJiAkdmFsdWUgIT09ICcnID8gJHZhbHVlLicgJy5kYXRh
X2dldCgkcG9pbnQsICd1bml0JywgJ2NtJykgOiAn4oCUJzsKICAgIH07CkBlbmRwaHAKCjxkaXYg
Y2xhc3M9Imx4YS1maXQiIGRhdGEtbHhhLXJldmVhbD4KICAgIDxkaXYgY2xhc3M9Imx4YS1maXRf
X2ltYWdlIj4KICAgICAgICBAaWYoJGltYWdlKQogICAgICAgICAgICA8aW1nIHNyYz0ie3sgJGlt
YWdlIH19IiBhbHQ9IlBob20gZMOhbmcge3sgZGF0YV9nZXQoJGlkZW50aXR5LCAnbmFtZScpIH19
IiBsb2FkaW5nPSJsYXp5IiBkZWNvZGluZz0iYXN5bmMiPgogICAgICAgIEBlbmRpZgogICAgICAg
IDxkaXYgY2xhc3M9Imx4YS1maXRfX2ltYWdlLWNvcHkiPgogICAgICAgICAgICA8cD5GaXQgJiBz
Y2FsZTwvcD4KICAgICAgICAgICAgPGgyPk5ow6xuIHLDtSB04bu3IGzhu4cgdHLGsOG7m2Mga2hp
IG3hurdjLjwvaDI+CiAgICAgICAgPC9kaXY+CiAgICA8L2Rpdj4KCiAgICA8ZGl2IGNsYXNzPSJs
eGEtZml0X19wYW5lbCI+CiAgICAgICAgPHAgY2xhc3M9Imx4YS1raWNrZXIiPkZpdCBjb25maWRl
bmNlPC9wPgogICAgICAgIDxoMj5DaOG7jW4gc2l6ZSBi4bqxbmcgZOG7ryBsaeG7h3UgdGjhuq10
LjwvaDI+CiAgICAgICAgPHAgY2xhc3M9Imx4YS1maXRfX2ludHJvIj4KICAgICAgICAgICAgQuG6
o25nIHPhu5EgxJFvIMSRxrDhu6NjIMSR4buNYyB04burIGjhu5Mgc8ahIHPhuqNuIHh14bqldCBj
4bunYSByacOqbmcgbeG6q3UgbsOgeS4gxJDDonkgbMOgIHPhu5EgxJFvIHRow6BuaCBwaOG6qW0g
4oCUIGjDo3kgc28gduG7m2kgbeG7mXQgc+G6o24gcGjhuqltIMSRYW5nIG3hurdjIHbhu6thIMSR
4buDIGNo4buNbiB04buxIHRpbiBoxqFuLgogICAgICAgIDwvcD4KCiAgICAgICAgPGRpdiBjbGFz
cz0ibHhhLWZpdF9fbWV0cmljcyI+CiAgICAgICAgICAgIDxhcnRpY2xlPgogICAgICAgICAgICAg
ICAgPHNwYW4+Rm9ybTwvc3Bhbj4KICAgICAgICAgICAgICAgIDxzdHJvbmc+e3sgZGF0YV9nZXQo
JHBkcCwgJ3Byb2R1Y3RfdHJ1dGguZGVzaWduLml0ZW1zLjAudmFsdWUnLCAnxJDDoyB4w6FjIG1p
bmgnKSB9fTwvc3Ryb25nPgogICAgICAgICAgICA8L2FydGljbGU+CiAgICAgICAgICAgIEBpZigk
Zm9jdXNTaXplICE9PSAnJyAmJiAkbGVuZ3RoUG9pbnQgIT09IFtdKQogICAgICAgICAgICAgICAg
PGFydGljbGU+PHNwYW4+xJDhu5kgZMOgaSBzaXplIHt7ICRmb2N1c1NpemUgfX08L3NwYW4+PHN0
cm9uZz57eyAkZGlzcGxheVZhbHVlKCRsZW5ndGhQb2ludCwgJGZvY3VzU2l6ZSkgfX08L3N0cm9u
Zz48L2FydGljbGU+CiAgICAgICAgICAgIEBlbmRpZgogICAgICAgICAgICBAaWYoJGZvY3VzU2l6
ZSAhPT0gJycgJiYgJGJ1c3RQb2ludCAhPT0gW10pCiAgICAgICAgICAgICAgICA8YXJ0aWNsZT48
c3Bhbj5Ww7JuZyBuZ+G7sWMgc2l6ZSB7eyAkZm9jdXNTaXplIH19PC9zcGFuPjxzdHJvbmc+e3sg
JGRpc3BsYXlWYWx1ZSgkYnVzdFBvaW50LCAkZm9jdXNTaXplKSB9fTwvc3Ryb25nPjwvYXJ0aWNs
ZT4KICAgICAgICAgICAgQGVuZGlmCiAgICAgICAgICAgIEBpZigkZm9jdXNTaXplICE9PSAnJyAm
JiAkd2Fpc3RQb2ludCAhPT0gW10pCiAgICAgICAgICAgICAgICA8YXJ0aWNsZT48c3Bhbj5Ww7Ju
ZyBlbyBzaXplIHt7ICRmb2N1c1NpemUgfX08L3NwYW4+PHN0cm9uZz57eyAkZGlzcGxheVZhbHVl
KCR3YWlzdFBvaW50LCAkZm9jdXNTaXplKSB9fTwvc3Ryb25nPjwvYXJ0aWNsZT4KICAgICAgICAg
ICAgQGVuZGlmCiAgICAgICAgPC9kaXY+CgogICAgICAgIDxidXR0b24KICAgICAgICAgICAgdHlw
ZT0iYnV0dG9uIgogICAgICAgICAgICBjbGFzcz0ibHhhLW91dGxpbmUtYnV0dG9uIgogICAgICAg
ICAgICBkYXRhLWx4cGRwLXNpemUtYWR2aXNvci1vcGVuCiAgICAgICAgICAgIEBpZighZGF0YV9n
ZXQoJHBkcCwgJ2ZpdC5hZHZpc29yLmVuYWJsZWQnKSkgZGlzYWJsZWQgQGVuZGlmCiAgICAgICAg
Pktp4buDbSB0cmEgc2l6ZSBj4bunYSBi4bqhbiDigJQga2hv4bqjbmcgMzAgZ2nDonk8L2J1dHRv
bj4KCiAgICAgICAgQGlmKGRhdGFfZ2V0KCRjaGFydCwgJ3N0cnVjdHVyZWQnKSkKICAgICAgICAg
ICAgPHAgY2xhc3M9Imx4YS1zb3VyY2Utbm90ZSI+U+G7kSDEkW8gxJHGsOG7o2MgeMOhYyBtaW5o
IHJpw6puZyBjaG8gbeG6q3UgbsOgeTwvcD4KICAgICAgICBAZW5kaWYKICAgIDwvZGl2Pgo8L2Rp
dj4K
ATELIER_PAYLOAD_5

decode_to_file 'resources/views/commerce_v2/pdp/atelier/hero-purchase.blade.php' <<'ATELIER_PAYLOAD_6'
QHBocAogICAgJGlkZW50aXR5ID0gKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnaWRlbnRpdHknLCBb
XSk7CiAgICAkY29tbWVyY2UgPSAoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdjb21tZXJjZScsIFtd
KTsKICAgICRjb2xvcnMgPSBjb2xsZWN0KChhcnJheSkgZGF0YV9nZXQoJGNvbW1lcmNlLCAnY29s
b3JzJywgW10pKTsKICAgICRkZWZhdWx0Q29sb3IgPSAoYXJyYXkpIGRhdGFfZ2V0KCRjb21tZXJj
ZSwgJ2RlZmF1bHRfY29sb3InLCBbXSk7CiAgICAkZGVmYXVsdE1lZGlhID0gY29sbGVjdCgoYXJy
YXkpIGRhdGFfZ2V0KCRkZWZhdWx0Q29sb3IsICdtZWRpYScsIFtdKSktPnRha2UoNik7CiAgICAk
aGVyb01lZGlhID0gKGFycmF5KSAoJGRlZmF1bHRNZWRpYS0+Zmlyc3QoKSA/OiBbXSk7CiAgICAk
YWR2aXNvciA9IChhcnJheSkgZGF0YV9nZXQoJHBkcCwgJ2ZpdC5hZHZpc29yJywgW10pOwogICAg
JGZhY3RzID0gY29sbGVjdCgoYXJyYXkpIGRhdGFfZ2V0KCRwZHAsICdwcm9kdWN0X3RydXRoLmhp
Z2hsaWdodHMnLCBbXSkpLT50YWtlKDMpOwogICAgJHNob3J0TmFtZSA9IHRyaW0oKHN0cmluZykg
KGRhdGFfZ2V0KCRpZGVudGl0eSwgJ3Nob3J0X25hbWUnKSA/OiBkYXRhX2dldCgkaWRlbnRpdHks
ICduYW1lJykpKTsKICAgICRmdWxsTmFtZSA9IHRyaW0oKHN0cmluZykgZGF0YV9nZXQoJGlkZW50
aXR5LCAnbmFtZScpKTsKICAgICRkZXNjcmlwdG9yID0gdHJpbSgoc3RyaW5nKSBwcmVnX3JlcGxh
Y2UoCiAgICAgICAgJy9eJy5wcmVnX3F1b3RlKCRzaG9ydE5hbWUsICcvJykuJ1xzKlvigJPigJRc
LTpdP1xzKi91JywKICAgICAgICAnJywKICAgICAgICAkZnVsbE5hbWUKICAgICkpOwogICAgJGRl
c2NyaXB0aW9uID0gXElsbHVtaW5hdGVcU3VwcG9ydFxTdHI6OmxpbWl0KAogICAgICAgIChzdHJp
bmcpIGRhdGFfZ2V0KCRpZGVudGl0eSwgJ2Rlc2NyaXB0aW9uJyksCiAgICAgICAgMjEwLAogICAg
ICAgICfigKYnCiAgICApOwpAZW5kcGhwCgo8ZGl2IGNsYXNzPSJseGEtaGVybyIgZGF0YS1seGEt
cmV2ZWFsPgogICAgPGRpdiBjbGFzcz0ibHhhLWhlcm9fX21lZGlhIj4KICAgICAgICA8ZGl2IGNs
YXNzPSJseGEtaGVyb19faXNzdWUiIGFyaWEtaGlkZGVuPSJ0cnVlIj4KICAgICAgICAgICAgPHNw
YW4+TElOIFjDiU4gLyBUSEUgRURJVDwvc3Bhbj4KICAgICAgICAgICAgPHN0cm9uZz4wMTwvc3Ry
b25nPgogICAgICAgIDwvZGl2PgoKICAgICAgICA8ZGl2IGNsYXNzPSJseHBkcC1nYWxsZXJ5IGx4
YS1nYWxsZXJ5IiBkYXRhLWx4cGRwLWdhbGxlcnkgYXJpYS1sYWJlbD0iSMOsbmgg4bqjbmggc+G6
o24gcGjhuqltIj4KICAgICAgICAgICAgPGRpdiBjbGFzcz0ibHhwZHAtZ2FsbGVyeV9fc3RhZ2Ug
bHhhLWdhbGxlcnlfX3N0YWdlIj4KICAgICAgICAgICAgICAgIDxidXR0b24KICAgICAgICAgICAg
ICAgICAgICB0eXBlPSJidXR0b24iCiAgICAgICAgICAgICAgICAgICAgY2xhc3M9Imx4cGRwLWdh
bGxlcnlfX25hdiBseHBkcC1nYWxsZXJ5X19uYXYtLXByZXYgbHhhLWdhbGxlcnlfX25hdiBseGEt
Z2FsbGVyeV9fbmF2LS1wcmV2IgogICAgICAgICAgICAgICAgICAgIGRhdGEtbHhwZHAtZ2FsbGVy
eS1wcmV2CiAgICAgICAgICAgICAgICAgICAgYXJpYS1sYWJlbD0i4bqibmggdHLGsOG7m2MiCiAg
ICAgICAgICAgICAgICA+4oC5PC9idXR0b24+CgogICAgICAgICAgICAgICAgPGZpZ3VyZSBjbGFz
cz0ibHhwZHAtZ2FsbGVyeV9fZmlndXJlIGx4YS1nYWxsZXJ5X19maWd1cmUiPgogICAgICAgICAg
ICAgICAgICAgIDxpbWcKICAgICAgICAgICAgICAgICAgICAgICAgZGF0YS1seHBkcC1tYWluLWlt
YWdlCiAgICAgICAgICAgICAgICAgICAgICAgIHNyYz0ie3sgZGF0YV9nZXQoJGhlcm9NZWRpYSwg
J3VybCcsIGRhdGFfZ2V0KCRwZHAsICdtZWRpYS5jb3Zlcl91cmwnKSkgfX0iCiAgICAgICAgICAg
ICAgICAgICAgICAgIGFsdD0ie3sgJGZ1bGxOYW1lIH19IC0ge3sgZGF0YV9nZXQoJGRlZmF1bHRD
b2xvciwgJ2xhYmVsJykgfX0iCiAgICAgICAgICAgICAgICAgICAgICAgIHdpZHRoPSIxMjAwIgog
ICAgICAgICAgICAgICAgICAgICAgICBoZWlnaHQ9IjE1MDAiCiAgICAgICAgICAgICAgICAgICAg
ICAgIGZldGNocHJpb3JpdHk9ImhpZ2giCiAgICAgICAgICAgICAgICAgICAgICAgIGRlY29kaW5n
PSJhc3luYyIKICAgICAgICAgICAgICAgICAgICA+CiAgICAgICAgICAgICAgICAgICAgPGZpZ2Nh
cHRpb24gY2xhc3M9Imx4YS1nYWxsZXJ5X19jYXB0aW9uIj4KICAgICAgICAgICAgICAgICAgICAg
ICAgPHNwYW4gZGF0YS1seHBkcC1pbWFnZS1yb2xlPgogICAgICAgICAgICAgICAgICAgICAgICAg
ICAge3sgZGF0YV9nZXQoJGhlcm9NZWRpYSwgJ3JvbGUnKSA9PT0gJ2hlcm8nID8gJ+G6om5oIGNo
w61uaCcgOiAnSMOsbmgg4bqjbmggc+G6o24gcGjhuqltJyB9fQogICAgICAgICAgICAgICAgICAg
ICAgICA8L3NwYW4+CiAgICAgICAgICAgICAgICAgICAgICAgIDxzcGFuIGRhdGEtbHhwZHAtaW1h
Z2UtY291bnRlcj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIHt7ICRkZWZhdWx0TWVkaWEt
PmlzTm90RW1wdHkoKSA/ICcwMSDigJQgJy5zdHJfcGFkKChzdHJpbmcpICRkZWZhdWx0TWVkaWEt
PmNvdW50KCksIDIsICcwJywgU1RSX1BBRF9MRUZUKSA6ICcnIH19CiAgICAgICAgICAgICAgICAg
ICAgICAgIDwvc3Bhbj4KICAgICAgICAgICAgICAgICAgICA8L2ZpZ2NhcHRpb24+CiAgICAgICAg
ICAgICAgICA8L2ZpZ3VyZT4KCiAgICAgICAgICAgICAgICA8YnV0dG9uCiAgICAgICAgICAgICAg
ICAgICAgdHlwZT0iYnV0dG9uIgogICAgICAgICAgICAgICAgICAgIGNsYXNzPSJseHBkcC1nYWxs
ZXJ5X19uYXYgbHhwZHAtZ2FsbGVyeV9fbmF2LS1uZXh0IGx4YS1nYWxsZXJ5X19uYXYgbHhhLWdh
bGxlcnlfX25hdi0tbmV4dCIKICAgICAgICAgICAgICAgICAgICBkYXRhLWx4cGRwLWdhbGxlcnkt
bmV4dAogICAgICAgICAgICAgICAgICAgIGFyaWEtbGFiZWw9IuG6om5oIHRp4bq/cCB0aGVvIgog
ICAgICAgICAgICAgICAgPuKAujwvYnV0dG9uPgogICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAg
ICAgIDxkaXYgY2xhc3M9Imx4cGRwLWdhbGxlcnlfX3RodW1icyBseGEtZ2FsbGVyeV9fdGh1bWJz
IiBkYXRhLWx4cGRwLXRodW1icyByb2xlPSJsaXN0IiBhcmlhLWxhYmVsPSJDaOG7jW4g4bqjbmgg
c+G6o24gcGjhuqltIj4KICAgICAgICAgICAgICAgIEBmb3JlYWNoKCRkZWZhdWx0TWVkaWEgYXMg
JGluZGV4ID0+ICRtZWRpYSkKICAgICAgICAgICAgICAgICAgICA8YnV0dG9uCiAgICAgICAgICAg
ICAgICAgICAgICAgIHR5cGU9ImJ1dHRvbiIKICAgICAgICAgICAgICAgICAgICAgICAgY2xhc3M9
Imx4cGRwLWdhbGxlcnlfX3RodW1iIGx4YS1nYWxsZXJ5X190aHVtYiB7eyAkaW5kZXggPT09IDAg
PyAnaXMtYWN0aXZlJyA6ICcnIH19IgogICAgICAgICAgICAgICAgICAgICAgICBkYXRhLWx4cGRw
LXRodW1iCiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGEtaW5kZXg9Int7ICRpbmRleCB9fSIK
ICAgICAgICAgICAgICAgICAgICAgICAgYXJpYS1sYWJlbD0iWGVtIOG6o25oIHt7ICRpbmRleCAr
IDEgfX0iCiAgICAgICAgICAgICAgICAgICAgICAgIGFyaWEtY3VycmVudD0ie3sgJGluZGV4ID09
PSAwID8gJ3RydWUnIDogJ2ZhbHNlJyB9fSIKICAgICAgICAgICAgICAgICAgICA+CiAgICAgICAg
ICAgICAgICAgICAgICAgIDxpbWcKICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNyYz0ie3sg
ZGF0YV9nZXQoJG1lZGlhLCAndGh1bWJfdXJsJywgZGF0YV9nZXQoJG1lZGlhLCAndXJsJykpIH19
IgogICAgICAgICAgICAgICAgICAgICAgICAgICAgYWx0PSIiCiAgICAgICAgICAgICAgICAgICAg
ICAgICAgICB3aWR0aD0iOTYiCiAgICAgICAgICAgICAgICAgICAgICAgICAgICBoZWlnaHQ9IjEy
MCIKICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxvYWRpbmc9ImxhenkiCiAgICAgICAgICAg
ICAgICAgICAgICAgICAgICBkZWNvZGluZz0iYXN5bmMiCiAgICAgICAgICAgICAgICAgICAgICAg
ID4KICAgICAgICAgICAgICAgICAgICA8L2J1dHRvbj4KICAgICAgICAgICAgICAgIEBlbmRmb3Jl
YWNoCiAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgPHAgY2xhc3M9Imx4cGRwLWdhbGxl
cnlfX25vdGljZSBseGEtZ2FsbGVyeV9fbm90aWNlIiBkYXRhLWx4cGRwLWdhbGxlcnktbm90aWNl
IEBpZigkZGVmYXVsdE1lZGlhLT5pc05vdEVtcHR5KCkpIGhpZGRlbiBAZW5kaWY+CiAgICAgICAg
ICAgICAgICBNw6B1IG7DoHkgxJFhbmcgY2jhu50gYuG7mSDhuqNuaCDEkcaw4bujYyBkdXnhu4d0
LiBMSU4gWMOJTiBraMO0bmcgZMO5bmcg4bqjbmggY+G7p2EgbcOgdSBraMOhYyDEkeG7gyBtaW5o
IGjhu41hLgogICAgICAgICAgICA8L3A+CiAgICAgICAgPC9kaXY+CiAgICA8L2Rpdj4KCiAgICA8
YXNpZGUgY2xhc3M9Imx4cGRwLWJ1eS1wYW5lbCBseGEtYnV5IiBhcmlhLWxhYmVsPSJUaMO0bmcg
dGluIG11YSBow6BuZyIgaWQ9Imx4YVB1cmNoYXNlUGFuZWwiPgogICAgICAgIDxkaXYgY2xhc3M9
Imx4YS1idXlfX2hlYWQiPgogICAgICAgICAgICA8cCBjbGFzcz0ibHhhLWtpY2tlciI+TmV3IHNl
YXNvbiDCtyBUaGUgTElOIFjDiU4gZWRpdDwvcD4KICAgICAgICAgICAgPGgxPnt7ICRzaG9ydE5h
bWUgfX08L2gxPgogICAgICAgICAgICBAaWYoJGRlc2NyaXB0b3IgIT09ICcnKQogICAgICAgICAg
ICAgICAgPHAgY2xhc3M9Imx4YS1idXlfX2Rlc2NyaXB0b3IiPnt7ICRkZXNjcmlwdG9yIH19PC9w
PgogICAgICAgICAgICBAZW5kaWYKICAgICAgICAgICAgQGlmKCRkZXNjcmlwdGlvbiAhPT0gJycp
CiAgICAgICAgICAgICAgICA8cCBjbGFzcz0ibHhhLWJ1eV9fZGVjayI+e3sgJGRlc2NyaXB0aW9u
IH19PC9wPgogICAgICAgICAgICBAZW5kaWYKICAgICAgICA8L2Rpdj4KCiAgICAgICAgPGRpdiBj
bGFzcz0ibHhhLXByaWNlLXJvdyI+CiAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4cGRwX19wcmlj
ZSIgZGF0YS1seHBkcC1wcmljZT4KICAgICAgICAgICAgICAgIDxzdHJvbmc+e3sgbnVtYmVyX2Zv
cm1hdCgoZmxvYXQpIGRhdGFfZ2V0KCRjb21tZXJjZSwgJ3ByaWNlLm1pbicpLCAwLCAnLCcsICcu
JykgfX3igqs8L3N0cm9uZz4KICAgICAgICAgICAgICAgIEBpZihkYXRhX2dldCgkY29tbWVyY2Us
ICdwcmljZS5oYXNfc2FsZScpICYmIGRhdGFfZ2V0KCRjb21tZXJjZSwgJ3ByaWNlLm9yaWdpbmFs
X21pbicpID4gZGF0YV9nZXQoJGNvbW1lcmNlLCAncHJpY2UubWluJykpCiAgICAgICAgICAgICAg
ICAgICAgPGRlbD57eyBudW1iZXJfZm9ybWF0KChmbG9hdCkgZGF0YV9nZXQoJGNvbW1lcmNlLCAn
cHJpY2Uub3JpZ2luYWxfbWluJyksIDAsICcsJywgJy4nKSB9feKCqzwvZGVsPgogICAgICAgICAg
ICAgICAgQGVuZGlmCiAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8cCBjbGFzcz0ibHhh
LXN0b2NrIHt7IGRhdGFfZ2V0KCRjb21tZXJjZSwgJ2F2YWlsYWJpbGl0eS5pbl9zdG9jaycpID8g
J2lzLWluJyA6ICdpcy1vdXQnIH19Ij4KICAgICAgICAgICAgICAgIDxpIGFyaWEtaGlkZGVuPSJ0
cnVlIj48L2k+CiAgICAgICAgICAgICAgICA8c3Bhbj57eyBkYXRhX2dldCgkY29tbWVyY2UsICdh
dmFpbGFiaWxpdHkuaW5fc3RvY2snKSA/ICdT4bq1biBzw6BuZyBnaWFvJyA6ICdU4bqhbSBo4bq/
dCBow6BuZycgfX08L3NwYW4+CiAgICAgICAgICAgIDwvcD4KICAgICAgICA8L2Rpdj4KCiAgICAg
ICAgPHNlY3Rpb24gY2xhc3M9Imx4cGRwLXNlbGVjdG9yIGx4YS1zZWxlY3RvciIgYXJpYS1sYWJl
bGxlZGJ5PSJseGFDb2xvclRpdGxlIj4KICAgICAgICAgICAgPGRpdiBjbGFzcz0ibHhhLXNlbGVj
dG9yX19oZWFkIj4KICAgICAgICAgICAgICAgIDxoMiBpZD0ibHhhQ29sb3JUaXRsZSI+TcOgdSBz
4bqvYzwvaDI+CiAgICAgICAgICAgICAgICA8c3BhbiBkYXRhLWx4cGRwLWNvbG9yLWxhYmVsPnt7
IGRhdGFfZ2V0KCRkZWZhdWx0Q29sb3IsICdsYWJlbCcsICdDaOG7jW4gbcOgdScpIH19PC9zcGFu
PgogICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4YS1jb2xvci1s
aXN0IiByb2xlPSJsaXN0Ij4KICAgICAgICAgICAgICAgIEBmb3JlYWNoKCRjb2xvcnMgYXMgJGNv
bG9yKQogICAgICAgICAgICAgICAgICAgIEBwaHAKICAgICAgICAgICAgICAgICAgICAgICAgJGNv
dmVyID0gZGF0YV9nZXQoJGNvbG9yLCAnbWVkaWEuMC50aHVtYl91cmwnKSA/OiBkYXRhX2dldCgk
Y29sb3IsICdjb3Zlcl91cmwnKTsKICAgICAgICAgICAgICAgICAgICAgICAgJGFjdGl2ZSA9IChz
dHJpbmcpIGRhdGFfZ2V0KCRjb2xvciwgJ2lkJykgPT09IChzdHJpbmcpIGRhdGFfZ2V0KCRkZWZh
dWx0Q29sb3IsICdpZCcpOwogICAgICAgICAgICAgICAgICAgIEBlbmRwaHAKICAgICAgICAgICAg
ICAgICAgICA8YnV0dG9uCiAgICAgICAgICAgICAgICAgICAgICAgIHR5cGU9ImJ1dHRvbiIKICAg
ICAgICAgICAgICAgICAgICAgICAgY2xhc3M9Imx4cGRwLWNvbG9yLWNhcmQgbHhhLWNvbG9yIHt7
ICRhY3RpdmUgPyAnaXMtYWN0aXZlJyA6ICcnIH19IgogICAgICAgICAgICAgICAgICAgICAgICBk
YXRhLWx4cGRwLWNvbG9yCiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGEtY29sb3ItaWQ9Int7
IGRhdGFfZ2V0KCRjb2xvciwgJ2lkJykgfX0iCiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGEt
Y29sb3ItY29kZT0ie3sgZGF0YV9nZXQoJGNvbG9yLCAnY29kZScpIH19IgogICAgICAgICAgICAg
ICAgICAgICAgICBkYXRhLWNvbG9yLXNlbGxhYmxlPSJ7eyBkYXRhX2dldCgkY29sb3IsICdzZWxs
YWJsZScpID8gJzEnIDogJzAnIH19IgogICAgICAgICAgICAgICAgICAgICAgICBhcmlhLXByZXNz
ZWQ9Int7ICRhY3RpdmUgPyAndHJ1ZScgOiAnZmFsc2UnIH19IgogICAgICAgICAgICAgICAgICAg
ICAgICBhcmlhLWxhYmVsPSJ7eyBkYXRhX2dldCgkY29sb3IsICdsYWJlbCcpIH19e3sgZGF0YV9n
ZXQoJGNvbG9yLCAnc2VsbGFibGUnKSA/ICcnIDogJywgdOG6oW0gaOG6v3QgaMOgbmcnIH19Igog
ICAgICAgICAgICAgICAgICAgID4KICAgICAgICAgICAgICAgICAgICAgICAgPHNwYW4gY2xhc3M9
Imx4YS1jb2xvcl9fdmlzdWFsIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIEBpZigkY292
ZXIpCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGltZyBzcmM9Int7ICRjb3ZlciB9
fSIgYWx0PSIiIHdpZHRoPSI3MiIgaGVpZ2h0PSI5MCIgbG9hZGluZz0ibGF6eSIgZGVjb2Rpbmc9
ImFzeW5jIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIEBlbHNlCiAgICAgICAgICAgICAg
ICAgICAgICAgICAgICAgICAgPGkgc3R5bGU9Ii0tbHhhLXN3YXRjaDp7eyBkYXRhX2dldCgkY29s
b3IsICdoZXgnKSA/OiAnI2Q5ZDFjYicgfX0iPjwvaT4KICAgICAgICAgICAgICAgICAgICAgICAg
ICAgIEBlbmRpZgogICAgICAgICAgICAgICAgICAgICAgICA8L3NwYW4+CiAgICAgICAgICAgICAg
ICAgICAgICAgIDxzcGFuIGNsYXNzPSJseGEtY29sb3JfX2NvcHkiPgogICAgICAgICAgICAgICAg
ICAgICAgICAgICAgPHN0cm9uZz57eyBkYXRhX2dldCgkY29sb3IsICdsYWJlbCcpIH19PC9zdHJv
bmc+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8c21hbGw+e3sgZGF0YV9nZXQoJGNvbG9y
LCAnc2VsbGFibGUnKSA/ICdDw7JuICcuKGludCkgZGF0YV9nZXQoJGNvbG9yLCAnYXZhaWxhYmxl
JykgOiAnVOG6oW0gaOG6v3QnIH19PC9zbWFsbD4KICAgICAgICAgICAgICAgICAgICAgICAgPC9z
cGFuPgogICAgICAgICAgICAgICAgICAgIDwvYnV0dG9uPgogICAgICAgICAgICAgICAgQGVuZGZv
cmVhY2gKICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgPC9zZWN0aW9uPgoKICAgICAgICA8c2Vj
dGlvbiBjbGFzcz0ibHhwZHAtc2VsZWN0b3IgbHhhLXNlbGVjdG9yIGx4YS1zZWxlY3Rvci0tc2l6
ZXMiIGFyaWEtbGFiZWxsZWRieT0ibHhhU2l6ZVRpdGxlIj4KICAgICAgICAgICAgPGRpdiBjbGFz
cz0ibHhhLXNlbGVjdG9yX19oZWFkIj4KICAgICAgICAgICAgICAgIDxoMiBpZD0ibHhhU2l6ZVRp
dGxlIj5Lw61jaCB0aMaw4bubYzwvaDI+CiAgICAgICAgICAgICAgICA8YnV0dG9uCiAgICAgICAg
ICAgICAgICAgICAgdHlwZT0iYnV0dG9uIgogICAgICAgICAgICAgICAgICAgIGNsYXNzPSJseHBk
cC1zaXplLWFkdmlzb3ItbGluayBseGEtZml0LWxpbmsiCiAgICAgICAgICAgICAgICAgICAgZGF0
YS1seHBkcC1zaXplLWFkdmlzb3Itb3BlbgogICAgICAgICAgICAgICAgICAgIEBpZighZGF0YV9n
ZXQoJGFkdmlzb3IsICdlbmFibGVkJykpIGRpc2FibGVkIEBlbmRpZgogICAgICAgICAgICAgICAg
PlTDrG0gc2l6ZSBj4bunYSBi4bqhbjwvYnV0dG9uPgogICAgICAgICAgICA8L2Rpdj4KICAgICAg
ICAgICAgPGRpdiBjbGFzcz0ibHhwZHAtc2l6ZS1saXN0IGx4YS1zaXplLWxpc3QiIGRhdGEtbHhw
ZHAtc2l6ZXMgcm9sZT0ibGlzdCIgYXJpYS1saXZlPSJwb2xpdGUiPjwvZGl2PgogICAgICAgICAg
ICA8ZGl2IGNsYXNzPSJseHBkcC1zZWxlY3Rpb24gbHhhLXNlbGVjdGlvbiIgZGF0YS1seHBkcC1z
ZWxlY3Rpb24gaGlkZGVuPgogICAgICAgICAgICAgICAgPHN0cm9uZyBkYXRhLWx4cGRwLXNlbGVj
dGVkLXRleHQ+PC9zdHJvbmc+CiAgICAgICAgICAgICAgICA8c3BhbiBkYXRhLWx4cGRwLXNlbGVj
dGVkLXN0b2NrPjwvc3Bhbj4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgPC9zZWN0aW9uPgoK
ICAgICAgICA8Zm9ybSBtZXRob2Q9InBvc3QiIGFjdGlvbj0ie3sgZGF0YV9nZXQoJGNvbW1lcmNl
LCAnY2FydF9hY3Rpb24nKSB9fSIgY2xhc3M9Imx4cGRwLWNhcnQtZm9ybSBseGEtY2FydCIgZGF0
YS1seHBkcC1jYXJ0LWZvcm0+CiAgICAgICAgICAgIEBjc3JmCiAgICAgICAgICAgIDxpbnB1dCB0
eXBlPSJoaWRkZW4iIG5hbWU9InNlbGxhYmxlX3NrdV9pZCIgdmFsdWU9IiIgZGF0YS1seHBkcC1z
a3UtaW5wdXQ+CiAgICAgICAgICAgIDxpbnB1dCB0eXBlPSJoaWRkZW4iIG5hbWU9InF1YW50aXR5
IiB2YWx1ZT0iMSI+CiAgICAgICAgICAgIDxidXR0b24gY2xhc3M9Imx4cGRwLXByaW1hcnktYnV0
dG9uIGx4YS1idXktYnV0dG9uIiB0eXBlPSJzdWJtaXQiIGRpc2FibGVkIGRhdGEtbHhwZHAtYnV5
PgogICAgICAgICAgICAgICAgQ2jhu41uIG3DoHUgdsOgIGvDrWNoIHRoxrDhu5tjCiAgICAgICAg
ICAgIDwvYnV0dG9uPgogICAgICAgIDwvZm9ybT4KCiAgICAgICAgPGRpdiBjbGFzcz0ibHhhLWFz
c3VyYW5jZSIgYXJpYS1sYWJlbD0iUXV54buBbiBs4bujaSBtdWEgaMOgbmciPgogICAgICAgICAg
ICA8ZGl2PjxzdHJvbmc+Q09EPC9zdHJvbmc+PHNwYW4+Tmjhuq1uIGjDoG5nIHLhu5NpIHRoYW5o
IHRvw6FuPC9zcGFuPjwvZGl2PgogICAgICAgICAgICA8ZGl2PjxzdHJvbmc+xJDhu5VpIHNpemU8
L3N0cm9uZz48c3Bhbj5I4buXIHRy4bujIHRoZW8gY2jDrW5oIHPDoWNoPC9zcGFuPjwvZGl2Pgog
ICAgICAgICAgICA8ZGl2PjxzdHJvbmc+U+G7kSDEkW8gcmnDqm5nPC9zdHJvbmc+PHNwYW4+xJDG
sOG7o2MgeMOhYyBtaW5oIHRoZW8gbeG6q3U8L3NwYW4+PC9kaXY+CiAgICAgICAgPC9kaXY+Cgog
ICAgICAgIEBpZigkZmFjdHMtPmlzTm90RW1wdHkoKSkKICAgICAgICAgICAgPGRsIGNsYXNzPSJs
eGEtbWluaS1mYWN0cyI+CiAgICAgICAgICAgICAgICBAZm9yZWFjaCgkZmFjdHMgYXMgJGZhY3Qp
CiAgICAgICAgICAgICAgICAgICAgPGRpdj48ZHQ+e3sgZGF0YV9nZXQoJGZhY3QsICdsYWJlbCcp
IH19PC9kdD48ZGQ+e3sgZGF0YV9nZXQoJGZhY3QsICd2YWx1ZScpIH19PC9kZD48L2Rpdj4KICAg
ICAgICAgICAgICAgIEBlbmRmb3JlYWNoCiAgICAgICAgICAgIDwvZGw+CiAgICAgICAgQGVuZGlm
CiAgICA8L2FzaWRlPgo8L2Rpdj4K
ATELIER_PAYLOAD_6

decode_to_file 'resources/views/commerce_v2/pdp/atelier/image-ribbon.blade.php' <<'ATELIER_PAYLOAD_7'
QHBocAogICAgJGRlZmF1bHRDb2xvciA9IChhcnJheSkgZGF0YV9nZXQoJHBkcCwgJ2NvbW1lcmNl
LmRlZmF1bHRfY29sb3InLCBbXSk7CiAgICAkbWVkaWEgPSBjb2xsZWN0KChhcnJheSkgZGF0YV9n
ZXQoJGRlZmF1bHRDb2xvciwgJ21lZGlhJywgW10pKS0+dGFrZSg1KTsKICAgICRyb2xlTGFiZWxz
ID0gWwogICAgICAgICdoZXJvJyA9PiAnVOG7lW5nIHRo4buDJywKICAgICAgICAnZnJvbnQnID0+
ICdN4bq3dCB0csaw4bubYycsCiAgICAgICAgJ3NpZGUnID0+ICdHw7NjIG5naGnDqm5nJywKICAg
ICAgICAnYmFjaycgPT4gJ03hurd0IHNhdScsCiAgICAgICAgJ2RldGFpbCcgPT4gJ0NoaSB0aeG6
v3QnLAogICAgICAgICdsaWZlc3R5bGUnID0+ICdUcsOqbiBuZ8aw4budaSBt4bqrdScsCiAgICBd
OwpAZW5kcGhwCgpAaWYoJG1lZGlhLT5pc05vdEVtcHR5KCkpCiAgICA8ZGl2IGNsYXNzPSJseGEt
ZmlsbSIgZGF0YS1seGEtZmlsbSBkYXRhLWx4YS1yZXZlYWwgYXJpYS1sYWJlbD0iQ2h14buXaSBo
w6xuaCDhuqNuaCBz4bqjbiBwaOG6qW0iPgogICAgICAgIEBmb3JlYWNoKCRtZWRpYSBhcyAkaW5k
ZXggPT4gJGl0ZW0pCiAgICAgICAgICAgIEBwaHAgJHJvbGUgPSAoc3RyaW5nKSBkYXRhX2dldCgk
aXRlbSwgJ3JvbGUnKTsgQGVuZHBocAogICAgICAgICAgICA8ZmlndXJlIGNsYXNzPSJseGEtZmls
bV9faXRlbSB7eyAkaW5kZXggPT09IDAgPyAnaXMtbGVhZCcgOiAnJyB9fSIgZGF0YS1seGEtZmls
bS1pdGVtPgogICAgICAgICAgICAgICAgPGltZwogICAgICAgICAgICAgICAgICAgIHNyYz0ie3sg
ZGF0YV9nZXQoJGl0ZW0sICd1cmwnKSB9fSIKICAgICAgICAgICAgICAgICAgICBhbHQ9Int7IGRh
dGFfZ2V0KCRwZHAsICdpZGVudGl0eS5uYW1lJykgfX0g4oCUIHt7ICRyb2xlTGFiZWxzWyRyb2xl
XSA/PyAnSMOsbmgg4bqjbmggc+G6o24gcGjhuqltJyB9fSIKICAgICAgICAgICAgICAgICAgICBs
b2FkaW5nPSJ7eyAkaW5kZXggPCAyID8gJ2VhZ2VyJyA6ICdsYXp5JyB9fSIKICAgICAgICAgICAg
ICAgICAgICBkZWNvZGluZz0iYXN5bmMiCiAgICAgICAgICAgICAgICA+CiAgICAgICAgICAgICAg
ICA8ZmlnY2FwdGlvbj4KICAgICAgICAgICAgICAgICAgICA8c3Bhbj57eyBzdHJfcGFkKChzdHJp
bmcpICgkaW5kZXggKyAxKSwgMiwgJzAnLCBTVFJfUEFEX0xFRlQpIH19PC9zcGFuPgogICAgICAg
ICAgICAgICAgICAgIDxzdHJvbmc+e3sgJHJvbGVMYWJlbHNbJHJvbGVdID8/ICdDaGkgdGnhur90
IHPhuqNuIHBo4bqpbScgfX08L3N0cm9uZz4KICAgICAgICAgICAgICAgIDwvZmlnY2FwdGlvbj4K
ICAgICAgICAgICAgPC9maWd1cmU+CiAgICAgICAgQGVuZGZvcmVhY2gKICAgIDwvZGl2PgpAZW5k
aWYK
ATELIER_PAYLOAD_7

decode_to_file 'resources/views/commerce_v2/pdp/atelier/manifesto.blade.php' <<'ATELIER_PAYLOAD_8'
QHBocAogICAgJGlkZW50aXR5ID0gKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnaWRlbnRpdHknLCBb
XSk7CiAgICAkZGVzY3JpcHRpb24gPSB0cmltKChzdHJpbmcpIGRhdGFfZ2V0KCRpZGVudGl0eSwg
J2Rlc2NyaXB0aW9uJykpOwogICAgJHNob3J0TmFtZSA9IChzdHJpbmcpIChkYXRhX2dldCgkaWRl
bnRpdHksICdzaG9ydF9uYW1lJykgPzogZGF0YV9nZXQoJGlkZW50aXR5LCAnbmFtZScpKTsKQGVu
ZHBocAoKPGRpdiBjbGFzcz0ibHhhLW1hbmlmZXN0byIgZGF0YS1seGEtcmV2ZWFsPgogICAgPGRp
diBjbGFzcz0ibHhhLW1hbmlmZXN0b19fbnVtYmVyIiBhcmlhLWhpZGRlbj0idHJ1ZSI+MDE8L2Rp
dj4KICAgIDxkaXYgY2xhc3M9Imx4YS1tYW5pZmVzdG9fX2NvcHkiPgogICAgICAgIDxwIGNsYXNz
PSJseGEta2lja2VyIGx4YS1raWNrZXItLWxpZ2h0Ij5UaGUge3sgJHNob3J0TmFtZSB9fSBlZGl0
PC9wPgogICAgICAgIDxoMj7EkMaw4budbmcgY+G6r3QgcsO1IHLDoG5nLjxicj5DaGkgdGnhur90
IHbhu6thIMSR4bunLjxicj5N4buZdCBwaG9tIGTDoW5nIMSR4buDIG5o4bubLjwvaDI+CiAgICAg
ICAgQGlmKCRkZXNjcmlwdGlvbiAhPT0gJycpCiAgICAgICAgICAgIDxwPnt7ICRkZXNjcmlwdGlv
biB9fTwvcD4KICAgICAgICBAZW5kaWYKICAgIDwvZGl2Pgo8L2Rpdj4K
ATELIER_PAYLOAD_8

decode_to_file 'resources/views/commerce_v2/pdp/atelier/material-story.blade.php' <<'ATELIER_PAYLOAD_9'
QHBocAogICAgJG1hdGVyaWFscyA9IChhcnJheSkgZGF0YV9nZXQoJHBkcCwgJ3Byb2R1Y3RfdHJ1
dGgubWF0ZXJpYWxzJywgW10pOwogICAgJG1haW5GYW1pbGllcyA9IGNvbGxlY3QoKGFycmF5KSBk
YXRhX2dldCgkbWF0ZXJpYWxzLCAnbWFpbicsIFtdKSkKICAgICAgICAtPm1hcChmbiAoJGl0ZW0p
ID0+IHRyaW0oKHN0cmluZykgZGF0YV9nZXQoJGl0ZW0sICdmYW1pbHlfbmFtZScpKSkKICAgICAg
ICAtPmZpbHRlcigpCiAgICAgICAgLT51bmlxdWUoKQogICAgICAgIC0+dGFrZSg0KQogICAgICAg
IC0+dmFsdWVzKCk7CiAgICAkbGluaW5nRmFtaWxpZXMgPSBjb2xsZWN0KChhcnJheSkgZGF0YV9n
ZXQoJG1hdGVyaWFscywgJ2xpbmluZycsIFtdKSkKICAgICAgICAtPm1hcChmbiAoJGl0ZW0pID0+
IHRyaW0oKHN0cmluZykgZGF0YV9nZXQoJGl0ZW0sICdmYW1pbHlfbmFtZScpKSkKICAgICAgICAt
PmZpbHRlcigpCiAgICAgICAgLT51bmlxdWUoKQogICAgICAgIC0+dGFrZSgzKQogICAgICAgIC0+
dmFsdWVzKCk7CiAgICAkY3VzdG9tZXJJdGVtcyA9IGNvbGxlY3QoKGFycmF5KSBkYXRhX2dldCgk
bWF0ZXJpYWxzLCAnc2VjdGlvbi5pdGVtcycsIFtdKSkKICAgICAgICAtPmZpbHRlcihmbiAoJGl0
ZW0pID0+IHRyaW0oKHN0cmluZykgZGF0YV9nZXQoJGl0ZW0sICd2YWx1ZScpKSAhPT0gJycpCiAg
ICAgICAgLT50YWtlKDQpCiAgICAgICAgLT52YWx1ZXMoKTsKICAgICRjYXJlSXRlbXMgPSBjb2xs
ZWN0KChhcnJheSkgZGF0YV9nZXQoJHBkcCwgJ3Byb2R1Y3RfdHJ1dGguY2FyZS5pdGVtcycsIFtd
KSkKICAgICAgICAtPmZpbHRlcihmbiAoJGl0ZW0pID0+IHRyaW0oKHN0cmluZykgZGF0YV9nZXQo
JGl0ZW0sICd2YWx1ZScpKSAhPT0gJycpCiAgICAgICAgLT50YWtlKDQpCiAgICAgICAgLT52YWx1
ZXMoKTsKICAgICRsYXllckxhYmVsID0gdHJpbSgoc3RyaW5nKSBkYXRhX2dldCgkbWF0ZXJpYWxz
LCAnbGF5ZXJfbGFiZWwnKSk7CiAgICAkbGF5ZXJMYWJlbCA9IHRyaW0oc3RyX2lyZXBsYWNlKFsn
IHRoZW8gQk9NJywgJ0JPTSddLCAnJywgJGxheWVyTGFiZWwpKTsKQGVuZHBocAoKQGlmKCRtYWlu
RmFtaWxpZXMtPmlzTm90RW1wdHkoKSB8fCAkbGluaW5nRmFtaWxpZXMtPmlzTm90RW1wdHkoKSB8
fCAkY3VzdG9tZXJJdGVtcy0+aXNOb3RFbXB0eSgpKQogICAgPGRpdiBjbGFzcz0ibHhhLW1hdGVy
aWFsIiBkYXRhLWx4YS1yZXZlYWw+CiAgICAgICAgPGRpdiBjbGFzcz0ibHhhLW1hdGVyaWFsX19z
dGF0ZW1lbnQiPgogICAgICAgICAgICA8cCBjbGFzcz0ibHhhLWtpY2tlciBseGEta2lja2VyLS1s
aWdodCI+TWF0ZXJpYWwgY2hhcmFjdGVyPC9wPgogICAgICAgICAgICA8aDI+Q2jhuqV0IGxp4buH
dSBxdXnhur90IMSR4buLbmggY8OhY2ggbeG7mXQgdGhp4bq/dCBr4bq/IGNodXnhu4NuIMSR4buZ
bmcuPC9oMj4KICAgICAgICAgICAgPHA+CiAgICAgICAgICAgICAgICBMSU4gWMOJTiBjaOG7iSBo
aeG7g24gdGjhu4sgbmjhu69uZyB0aMO0bmcgdGluIMSRw6MgY8OzIG5ndeG7k24gY2hvIHJpw6pu
ZyBz4bqjbiBwaOG6qW0gbsOgeSDigJQgxJHhu6cgxJHhu4MgYuG6oW4gaGnhu4N1IGPhuqV1IHTh
uqFvLCBraMO0bmcgYmnhur9uIHRyYW5nIG11YSBow6BuZyB0aMOgbmggbeG7mXQgYuG6o25nIHbh
uq10IHTGsCBr4bu5IHRodeG6rXQuCiAgICAgICAgICAgIDwvcD4KICAgICAgICA8L2Rpdj4KCiAg
ICAgICAgPGRpdiBjbGFzcz0ibHhhLW1hdGVyaWFsX19jYXJkcyI+CiAgICAgICAgICAgIEBpZigk
bWFpbkZhbWlsaWVzLT5pc05vdEVtcHR5KCkpCiAgICAgICAgICAgICAgICA8YXJ0aWNsZSBjbGFz
cz0ibHhhLW1hdGVyaWFsLWNhcmQgaXMtbWFpbiI+CiAgICAgICAgICAgICAgICAgICAgPHNwYW4+
MDE8L3NwYW4+CiAgICAgICAgICAgICAgICAgICAgPHA+VuG6o2kgY2jDrW5oPC9wPgogICAgICAg
ICAgICAgICAgICAgIDxoMz57eyAkbWFpbkZhbWlsaWVzLT5pbXBsb2RlKCcgwrcgJykgfX08L2gz
PgogICAgICAgICAgICAgICAgICAgIDxpIGFyaWEtaGlkZGVuPSJ0cnVlIj48L2k+CiAgICAgICAg
ICAgICAgICA8L2FydGljbGU+CiAgICAgICAgICAgIEBlbmRpZgoKICAgICAgICAgICAgQGlmKCRs
aW5pbmdGYW1pbGllcy0+aXNOb3RFbXB0eSgpIHx8ICRsYXllckxhYmVsICE9PSAnJykKICAgICAg
ICAgICAgICAgIDxhcnRpY2xlIGNsYXNzPSJseGEtbWF0ZXJpYWwtY2FyZCBpcy1saW5pbmciPgog
ICAgICAgICAgICAgICAgICAgIDxzcGFuPjAyPC9zcGFuPgogICAgICAgICAgICAgICAgICAgIDxw
PkPhuqV1IHThuqFvPC9wPgogICAgICAgICAgICAgICAgICAgIDxoMz57eyAkbGF5ZXJMYWJlbCAh
PT0gJycgPyAkbGF5ZXJMYWJlbCA6ICRsaW5pbmdGYW1pbGllcy0+aW1wbG9kZSgnIMK3ICcpIH19
PC9oMz4KICAgICAgICAgICAgICAgICAgICBAaWYoJGxpbmluZ0ZhbWlsaWVzLT5pc05vdEVtcHR5
KCkpCiAgICAgICAgICAgICAgICAgICAgICAgIDxzbWFsbD5M4bubcCB0cm9uZzoge3sgJGxpbmlu
Z0ZhbWlsaWVzLT5pbXBsb2RlKCcgwrcgJykgfX08L3NtYWxsPgogICAgICAgICAgICAgICAgICAg
IEBlbmRpZgogICAgICAgICAgICAgICAgICAgIDxpIGFyaWEtaGlkZGVuPSJ0cnVlIj48L2k+CiAg
ICAgICAgICAgICAgICA8L2FydGljbGU+CiAgICAgICAgICAgIEBlbmRpZgoKICAgICAgICAgICAg
QGZvcmVhY2goJGN1c3RvbWVySXRlbXMgYXMgJGluZGV4ID0+ICRpdGVtKQogICAgICAgICAgICAg
ICAgPGFydGljbGUgY2xhc3M9Imx4YS1tYXRlcmlhbC1jYXJkIGlzLWZhY3QiPgogICAgICAgICAg
ICAgICAgICAgIDxzcGFuPnt7IHN0cl9wYWQoKHN0cmluZykgKCRpbmRleCArIDMpLCAyLCAnMCcs
IFNUUl9QQURfTEVGVCkgfX08L3NwYW4+CiAgICAgICAgICAgICAgICAgICAgPHA+e3sgZGF0YV9n
ZXQoJGl0ZW0sICdsYWJlbCcpIH19PC9wPgogICAgICAgICAgICAgICAgICAgIDxoMz57eyBkYXRh
X2dldCgkaXRlbSwgJ3ZhbHVlJykgfX08L2gzPgogICAgICAgICAgICAgICAgICAgIDxpIGFyaWEt
aGlkZGVuPSJ0cnVlIj48L2k+CiAgICAgICAgICAgICAgICA8L2FydGljbGU+CiAgICAgICAgICAg
IEBlbmRmb3JlYWNoCiAgICAgICAgPC9kaXY+CgogICAgICAgIEBpZigkY2FyZUl0ZW1zLT5pc05v
dEVtcHR5KCkpCiAgICAgICAgICAgIDxkaXYgY2xhc3M9Imx4YS1jYXJlLWxpbmUiPgogICAgICAg
ICAgICAgICAgPHN0cm9uZz5C4bqjbyBxdeG6o248L3N0cm9uZz4KICAgICAgICAgICAgICAgIEBm
b3JlYWNoKCRjYXJlSXRlbXMgYXMgJGl0ZW0pCiAgICAgICAgICAgICAgICAgICAgPHNwYW4+e3sg
ZGF0YV9nZXQoJGl0ZW0sICd2YWx1ZScpIH19PC9zcGFuPgogICAgICAgICAgICAgICAgQGVuZGZv
cmVhY2gKICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgQGVuZGlmCiAgICA8L2Rpdj4KQGVuZGlm
Cg==
ATELIER_PAYLOAD_9

decode_to_file 'resources/views/commerce_v2/pdp/atelier/size-story.blade.php' <<'ATELIER_PAYLOAD_10'
QHBocAogICAgJGNoYXJ0ID0gKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnZml0Lmdhcm1lbnRfc2l6
ZV9jaGFydCcsIFtdKTsKICAgICRzaXplcyA9IGNvbGxlY3QoKGFycmF5KSBkYXRhX2dldCgkY2hh
cnQsICdzaXplcycsIFtdKSktPnRha2UoNiktPnZhbHVlcygpOwogICAgJHBvaW50cyA9IGNvbGxl
Y3QoKGFycmF5KSBkYXRhX2dldCgkY2hhcnQsICdwb2ludHMnLCBbXSkpLT50YWtlKDgpLT52YWx1
ZXMoKTsKICAgICR2YWx1ZUZvciA9IGZ1bmN0aW9uIChhcnJheSAkcG9pbnQsIHN0cmluZyAkc2l6
ZSk6IHN0cmluZyB7CiAgICAgICAgJGRpc3BsYXkgPSBkYXRhX2dldCgkcG9pbnQsICdkaXNwbGF5
X3ZhbHVlcy4nLiRzaXplKTsKICAgICAgICAkcmF3ID0gZGF0YV9nZXQoJHBvaW50LCAndmFsdWVz
LicuJHNpemUpOwogICAgICAgICR2YWx1ZSA9ICRkaXNwbGF5ICE9PSBudWxsICYmICRkaXNwbGF5
ICE9PSAnJyA/ICRkaXNwbGF5IDogJHJhdzsKCiAgICAgICAgcmV0dXJuICR2YWx1ZSAhPT0gbnVs
bCAmJiAkdmFsdWUgIT09ICcnCiAgICAgICAgICAgID8gKHN0cmluZykgJHZhbHVlCiAgICAgICAg
ICAgIDogJ+KAlCc7CiAgICB9OwpAZW5kcGhwCgpAaWYoZGF0YV9nZXQoJGNoYXJ0LCAnc3RydWN0
dXJlZCcpICYmICRzaXplcy0+aXNOb3RFbXB0eSgpICYmICRwb2ludHMtPmlzTm90RW1wdHkoKSkK
ICAgIDxkaXYgY2xhc3M9Imx4YS1zaXplLXN0b3J5IiBkYXRhLWx4YS1yZXZlYWwgZGF0YS1seHBk
cC1zaXplLWNoYXJ0LXN0cnVjdHVyZWQ+CiAgICAgICAgPGRpdiBjbGFzcz0ibHhhLXNpemUtc3Rv
cnlfX2ludHJvIj4KICAgICAgICAgICAgPHAgY2xhc3M9Imx4YS1raWNrZXIiPk1hZGUgdG8gbWVh
c3VyZTwvcD4KICAgICAgICAgICAgPGgyPlPhu5EgxJFvIHRow6BuaCBwaOG6qW0sPGJyPmtow7Ru
ZyBwaOG6o2kgbeG7mXQgcGjhu49uZyDEkW/DoW4uPC9oMj4KICAgICAgICAgICAgPHA+CiAgICAg
ICAgICAgICAgICDEkOG6t3QgbeG7mXQgc+G6o24gcGjhuqltIMSRYW5nIG3hurdjIHbhu6thIGzD
qm4gbeG6t3QgcGjhurNuZywgxJFvIGPDuW5nIHbhu4sgdHLDrSBy4buTaSBzbyBzw6FuaC4gQ8Oh
Y2ggbsOgeSBnacO6cCBi4bqhbiBoaeG7g3UgcGhvbSB0aOG6rXQgcsO1IGjGoW4gY2jhu4kgbmjD
rG4gY2jhu68gUywgTSBoYXkgTC4KICAgICAgICAgICAgPC9wPgoKICAgICAgICAgICAgPGJ1dHRv
bgogICAgICAgICAgICAgICAgdHlwZT0iYnV0dG9uIgogICAgICAgICAgICAgICAgY2xhc3M9Imx4
YS1vdXRsaW5lLWJ1dHRvbiBseGEtb3V0bGluZS1idXR0b24tLWRhcmsiCiAgICAgICAgICAgICAg
ICBkYXRhLWx4cGRwLXNpemUtYWR2aXNvci1vcGVuCiAgICAgICAgICAgICAgICBAaWYoIWRhdGFf
Z2V0KCRwZHAsICdmaXQuYWR2aXNvci5lbmFibGVkJykpIGRpc2FibGVkIEBlbmRpZgogICAgICAg
ICAgICA+xJDhu5FpIGNoaeG6v3UgduG7m2kgc+G7kSDEkW8gY+G7p2EgYuG6oW48L2J1dHRvbj4K
CiAgICAgICAgICAgIDxwIGNsYXNzPSJseGEtc291cmNlLW5vdGUiPkjhu5Mgc8ahIHPhu5EgxJFv
IHJpw6puZyBj4bunYSBt4bqrdSDCtyDEkcOjIHjDoWMgbWluaDwvcD4KICAgICAgICA8L2Rpdj4K
CiAgICAgICAgPGRpdiBjbGFzcz0ibHhhLXNpemUtc3RvcnlfX3RhYmxlLXdyYXAiIHRhYmluZGV4
PSIwIiBhcmlhLWxhYmVsPSJC4bqjbmcgc+G7kSDEkW8gdGjDoG5oIHBo4bqpbSI+CiAgICAgICAg
ICAgIDx0YWJsZSBjbGFzcz0ibHhhLXNpemUtdGFibGUiPgogICAgICAgICAgICAgICAgPHRoZWFk
PgogICAgICAgICAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgICAgICAgICAgPHRoIHNj
b3BlPSJjb2wiPsSQaeG7g20gxJFvPC90aD4KICAgICAgICAgICAgICAgICAgICAgICAgQGZvcmVh
Y2goJHNpemVzIGFzICRzaXplKQogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRoIHNjb3Bl
PSJjb2wiPnt7ICRzaXplIH19PC90aD4KICAgICAgICAgICAgICAgICAgICAgICAgQGVuZGZvcmVh
Y2gKICAgICAgICAgICAgICAgICAgICA8L3RyPgogICAgICAgICAgICAgICAgPC90aGVhZD4KICAg
ICAgICAgICAgICAgIDx0Ym9keT4KICAgICAgICAgICAgICAgICAgICBAZm9yZWFjaCgkcG9pbnRz
IGFzICRwb2ludCkKICAgICAgICAgICAgICAgICAgICAgICAgPHRyPgogICAgICAgICAgICAgICAg
ICAgICAgICAgICAgPHRoIHNjb3BlPSJyb3ciPgogICAgICAgICAgICAgICAgICAgICAgICAgICAg
ICAgIDxzcGFuPnt7IGRhdGFfZ2V0KCRwb2ludCwgJ2xhYmVsJykgfX08L3NwYW4+CiAgICAgICAg
ICAgICAgICAgICAgICAgICAgICAgICAgQGlmKGRhdGFfZ2V0KCRwb2ludCwgJ25vdGUnKSkKICAg
ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHNtYWxsPnt7IGRhdGFfZ2V0KCRwb2lu
dCwgJ25vdGUnKSB9fTwvc21hbGw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgQGVu
ZGlmCiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RoPgogICAgICAgICAgICAgICAgICAg
ICAgICAgICAgQGZvcmVhY2goJHNpemVzIGFzICRzaXplKQogICAgICAgICAgICAgICAgICAgICAg
ICAgICAgICAgIDx0ZD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHN0cm9u
Zz57eyAkdmFsdWVGb3IoKGFycmF5KSAkcG9pbnQsIChzdHJpbmcpICRzaXplKSB9fTwvc3Ryb25n
PgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8c21hbGw+e3sgZGF0YV9nZXQo
JHBvaW50LCAndW5pdCcsICdjbScpIH19PC9zbWFsbD4KICAgICAgICAgICAgICAgICAgICAgICAg
ICAgICAgICA8L3RkPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgQGVuZGZvcmVhY2gKICAg
ICAgICAgICAgICAgICAgICAgICAgPC90cj4KICAgICAgICAgICAgICAgICAgICBAZW5kZm9yZWFj
aAogICAgICAgICAgICAgICAgPC90Ym9keT4KICAgICAgICAgICAgPC90YWJsZT4KICAgICAgICA8
L2Rpdj4KICAgIDwvZGl2PgpAZW5kaWYK
ATELIER_PAYLOAD_10

decode_to_file 'resources/views/commerce_v2/pdp/atelier/truth-mosaic.blade.php' <<'ATELIER_PAYLOAD_11'
QHBocAogICAgJGlkZW50aXR5ID0gKGFycmF5KSBkYXRhX2dldCgkcGRwLCAnaWRlbnRpdHknLCBb
XSk7CiAgICAkbWVkaWEgPSBjb2xsZWN0KChhcnJheSkgZGF0YV9nZXQoJHBkcCwgJ21lZGlhLnBy
b2R1Y3Rpb25fdHJ1dGgnLCBbXSkpOwogICAgaWYgKCRtZWRpYS0+Y291bnQoKSA8IDMpIHsKICAg
ICAgICAkbWVkaWEgPSAkbWVkaWEKICAgICAgICAgICAgLT5jb25jYXQoKGFycmF5KSBkYXRhX2dl
dCgkcGRwLCAnY29tbWVyY2UuZGVmYXVsdF9jb2xvci5tZWRpYScsIFtdKSkKICAgICAgICAgICAg
LT51bmlxdWUoJ3VybCcpCiAgICAgICAgICAgIC0+dmFsdWVzKCk7CiAgICB9CiAgICAkbWVkaWEg
PSAkbWVkaWEtPnRha2UoNSk7CiAgICAkcm9sZUxhYmVscyA9IFsKICAgICAgICAnaGVybycgPT4g
J1Thu5VuZyB0aOG7gycsCiAgICAgICAgJ2Zyb250JyA9PiAnTeG6t3QgdHLGsOG7m2MnLAogICAg
ICAgICdzaWRlJyA9PiAnR8OzYyBuZ2hpw6puZycsCiAgICAgICAgJ2JhY2snID0+ICdN4bq3dCBz
YXUnLAogICAgICAgICdkZXRhaWwnID0+ICdDaGkgdGnhur90IMSRxrDhu51uZyBj4bqvdCcsCiAg
ICAgICAgJ2xpZmVzdHlsZScgPT4gJ1Ryw6puIG5nxrDhu51pIG3huqt1JywKICAgIF07CkBlbmRw
aHAKCkBpZigkbWVkaWEtPmlzTm90RW1wdHkoKSkKICAgIDxkaXYgY2xhc3M9Imx4YS10cnV0aCIg
ZGF0YS1seGEtcmV2ZWFsPgogICAgICAgIDxoZWFkZXIgY2xhc3M9Imx4YS10cnV0aF9faGVhZCI+
CiAgICAgICAgICAgIDxkaXY+CiAgICAgICAgICAgICAgICA8cCBjbGFzcz0ibHhhLWtpY2tlciI+
TG9vayBjbG9zZXI8L3A+CiAgICAgICAgICAgICAgICA8aDI+WGVtIGvhu7kgdHLGsOG7m2Mga2hp
IGNo4buNbi48L2gyPgogICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgPHA+4bqibmggdOG6
oW8gY+G6o20gaOG7qW5nIGdpw7pwIGLhuqFuIGjDrG5oIGR1bmcgcGhvbmcgY8OhY2guIEPDoWMg
Z8OzYyBz4bqjbiBwaOG6qW0gcsO1IHLDoG5nIGdpw7pwIHjDoWMgbmjhuq1uIHBob20sIG3hurd0
IHNhdSB2w6AgY2hpIHRp4bq/dCB0csaw4bubYyBraGkgbXVhLjwvcD4KICAgICAgICA8L2hlYWRl
cj4KCiAgICAgICAgPGRpdiBjbGFzcz0ibHhhLW1vc2FpYyI+CiAgICAgICAgICAgIEBmb3JlYWNo
KCRtZWRpYSBhcyAkaW5kZXggPT4gJGl0ZW0pCiAgICAgICAgICAgICAgICBAcGhwICRyb2xlID0g
KHN0cmluZykgZGF0YV9nZXQoJGl0ZW0sICdyb2xlJyk7IEBlbmRwaHAKICAgICAgICAgICAgICAg
IDxmaWd1cmUgY2xhc3M9Imx4YS1tb3NhaWNfX2l0ZW0gbHhhLW1vc2FpY19faXRlbS0te3sgJGlu
ZGV4ICsgMSB9fSI+CiAgICAgICAgICAgICAgICAgICAgPGltZwogICAgICAgICAgICAgICAgICAg
ICAgICBzcmM9Int7IGRhdGFfZ2V0KCRpdGVtLCAndXJsJykgfX0iCiAgICAgICAgICAgICAgICAg
ICAgICAgIGFsdD0ie3sgZGF0YV9nZXQoJGlkZW50aXR5LCAnbmFtZScpIH19IOKAlCB7eyAkcm9s
ZUxhYmVsc1skcm9sZV0gPz8gJ0NoaSB0aeG6v3Qgc+G6o24gcGjhuqltJyB9fSIKICAgICAgICAg
ICAgICAgICAgICAgICAgbG9hZGluZz0ibGF6eSIKICAgICAgICAgICAgICAgICAgICAgICAgZGVj
b2Rpbmc9ImFzeW5jIgogICAgICAgICAgICAgICAgICAgID4KICAgICAgICAgICAgICAgICAgICA8
ZmlnY2FwdGlvbj57eyAkcm9sZUxhYmVsc1skcm9sZV0gPz8gKGRhdGFfZ2V0KCRpdGVtLCAnc2hv
dF9hbmdsZScpID86ICdDaGkgdGnhur90IHPhuqNuIHBo4bqpbScpIH19PC9maWdjYXB0aW9uPgog
ICAgICAgICAgICAgICAgPC9maWd1cmU+CiAgICAgICAgICAgIEBlbmRmb3JlYWNoCiAgICAgICAg
PC9kaXY+CiAgICA8L2Rpdj4KQGVuZGlmCg==
ATELIER_PAYLOAD_11

php -l "$REGISTRY"
php -l "$SECTIONS"

grep -Fq -- "'atelier_editorial_v1' => [" "$REGISTRY"
grep -Fq -- "'atelier_hero_purchase' => [" "$SECTIONS"
grep -Fq -- 'data-lxpdp-gallery' resources/views/commerce_v2/pdp/atelier/hero-purchase.blade.php
grep -Fq -- 'data-lxpdp-size-chart-structured' resources/views/commerce_v2/pdp/atelier/size-story.blade.php
grep -Fq -- '.lxa-manifesto' public/commerce-v2/pdp/v1/variants/atelier-editorial-v1.css
grep -Fq -- '.lxa-mosaic' public/commerce-v2/pdp/v1/variants/atelier-editorial-v1.css
grep -Fq -- '.lxa-finale' public/commerce-v2/pdp/v1/variants/atelier-editorial-v1.css
grep -Fq -- 'linxen:pdp:atelier-ready' public/commerce-v2/pdp/v1/variants/atelier-editorial-v1.js

printf '%s\n' 'PDP_ATELIER_STATIC_CONTRACT=PASS'

if command -v node >/dev/null 2>&1; then
    node --check public/commerce-v2/pdp/v1/variants/atelier-editorial-v1.js
    printf '%s\n' 'PDP_ATELIER_JS_SYNTAX=PASS'
else
    printf '%s\n' 'PDP_ATELIER_JS_SYNTAX=SKIPPED_NODE_MISSING'
fi

if [ -f vendor/autoload.php ]; then
    env \
      CACHE_STORE=file \
      SESSION_DRIVER=file \
      php artisan view:clear

    env \
      CACHE_STORE=file \
      SESSION_DRIVER=file \
      php artisan optimize:clear

    env \
      CACHE_STORE=file \
      SESSION_DRIVER=file \
      php artisan commerce-v2:pdp-variant-smoke \
        --variant=atelier_editorial_v1

    env \
      CACHE_STORE=file \
      SESSION_DRIVER=file \
      php artisan commerce-v2:pdp-variant-matrix-smoke
else
    printf '%s\n' 'PDP_ATELIER_RENDER_SMOKE=SKIPPED_VENDOR_MISSING'
fi

trap - ERR

printf '%s\n' 'LINXEN_PDP_ATELIER_EDITORIAL_V1_SOURCE_PATCH=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'ART_DIRECTION=ATELIER_EDITORIAL'
printf '%s\n' 'LIVE_VARIANT=CLASSIC_UNCHANGED'
printf '%s\n' 'OLD_EDITORIAL_VARIANT=UNCHANGED_FOR_COMPARISON'
printf '%s\n' 'ATELIER_EDITORIAL_V1=SIGNED_PREVIEW_READY'
printf '%s\n' 'SHARED_COLOR_SIZE_CART_ENGINE=PRESERVED'
printf '%s\n' 'EXACT_SELLABLE_SKU_CONTRACT=PRESERVED'
printf '%s\n' 'ERP_SOURCE_CHANGE=NONE'
printf '%s\n' 'MIGRATION=NONE'
printf '%s\n' 'DB_MUTATION=NONE'
printf '%s\n' 'ORDER_PROVIDER_META_MUTATION=NONE'
printf '%s\n' 'NPM_BUILD=NOT_REQUIRED'
