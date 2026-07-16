#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='linxen_storefront_v2_layout_errors_cli_render_hotfix_v1'
LAYOUT='resources/views/commerce_v2/layouts/app.blade.php'
SMOKE='app/Console/Commands/CommerceV2SmokeCommand.php'

BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_$(date +%Y%m%d_%H%M%S)"
MANIFEST="${BACKUP_ROOT}/manifest.tsv"
PATCH_WRITTEN=0

rollback() {
    STATUS=$?

    if [ "$PATCH_WRITTEN" -eq 1 ] && [ -f "$MANIFEST" ]; then
        printf '%s\n' \
          'Có lỗi bắt buộc. Đang rollback layout CLI render hotfix...' \
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

for FILE in "$LAYOUT" "$SMOKE"; do
    test -f "$FILE" || {
        printf 'ERROR: Thiếu source Storefront V2: %s\n' \
          "$FILE" >&2
        exit 1
    }

    mkdir -p "$BACKUP_ROOT/$(dirname "$FILE")"
    cp -p "$FILE" "$BACKUP_ROOT/$FILE"
    printf 'existing\t%s\n' "$FILE" >> "$MANIFEST"
done

PATCH_WRITTEN=1

export LINXEN_LAYOUT_FILE="$LAYOUT"

php <<'PHP'
<?php

$path = getenv('LINXEN_LAYOUT_FILE');

if (! is_string($path) || ! is_file($path)) {
    fwrite(STDERR, "ERROR: Layout path không hợp lệ.\n");
    exit(1);
}

$source = file_get_contents($path);

if (! is_string($source)) {
    fwrite(STDERR, "ERROR: Không đọc được layout.\n");
    exit(1);
}

$old = '@if($errors->any())';
$new = '@if(isset($errors) && $errors->any())';

$oldCount = substr_count($source, $old);
$newCount = substr_count($source, $new);

if ($newCount === 1 && $oldCount === 0) {
    echo "LAYOUT_ERRORS_GUARD=ALREADY_APPLIED\n";
    exit(0);
}

if ($oldCount !== 1 || $newCount !== 0) {
    fwrite(
        STDERR,
        "ERROR: Layout errors anchor expected old=1,new=0; found old="
            . $oldCount
            . ",new="
            . $newCount
            . "\n"
    );
    exit(1);
}

$patched = str_replace($old, $new, $source, $count);

if ($count !== 1) {
    fwrite(STDERR, "ERROR: Layout replacement count invalid.\n");
    exit(1);
}

$written = file_put_contents($path, $patched);

if ($written === false || $written !== strlen($patched)) {
    fwrite(STDERR, "ERROR: Không ghi đầy đủ layout.\n");
    exit(1);
}

echo "LAYOUT_ERRORS_GUARD=APPLIED\n";
PHP

grep -Fq \
  '@if(isset($errors) && $errors->any())' \
  "$LAYOUT"

if grep -Fq '@if($errors->any())' "$LAYOUT"; then
    printf '%s\n' \
      'ERROR: Unsafe direct $errors guard vẫn còn.' \
      >&2
    exit 1
fi

grep -Fq \
  'product_view_render' \
  "$SMOKE"

(
    umask 0022

    if [ "$(id -u)" -eq 0 ] \
        && command -v sudo >/dev/null 2>&1 \
        && id www-data >/dev/null 2>&1
    then
        sudo -u www-data env \
          HOME="$(pwd)" \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan optimize:clear

        sudo -u www-data env \
          HOME="$(pwd)" \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan view:cache

        sudo -u www-data env \
          HOME="$(pwd)" \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan view:clear
    else
        env \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan optimize:clear

        env \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan view:cache

        env \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan view:clear
    fi
)

trap - ERR

printf '%s\n' 'LINXEN_LAYOUT_ERRORS_CLI_RENDER_HOTFIX=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'ROOT_CAUSE=ERROR_BAG_NOT_SHARED_OUTSIDE_WEB_MIDDLEWARE'
printf '%s\n' 'LAYOUT_GUARD=CLI_AND_HTTP_SAFE'
printf '%s\n' 'MIGRATION=NONE'
printf '%s\n' 'DB_MUTATION=NONE'
printf '%s\n' 'ERP_PROVIDER_CALL_DURING_PATCH=NONE'
printf '%s\n' 'ROUTES_CHANGED=NO'
printf '%s\n' 'V1_STOREFRONT=UNCHANGED'
