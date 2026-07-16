#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='linxen_storefront_v2_command_discovery_hotfix_v1'
BOOTSTRAP='bootstrap/app.php'
SMOKE_COMMAND='app/Console/Commands/CommerceV2SmokeCommand.php'
MARKER_START='/* AI_PATCH_LINXEN_COMMERCE_V2_COMMAND_DISCOVERY_V1_START */'
MARKER_END='/* AI_PATCH_LINXEN_COMMERCE_V2_COMMAND_DISCOVERY_V1_END */'

BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_$(date +%Y%m%d_%H%M%S)"
MANIFEST="${BACKUP_ROOT}/manifest.tsv"
PATCH_WRITTEN=0

rollback() {
    STATUS=$?

    if [ "$PATCH_WRITTEN" -eq 1 ] && [ -f "$MANIFEST" ]; then
        printf '%s\n' \
          'Có lỗi bắt buộc. Đang rollback bootstrap/app.php...' \
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
      'ERROR: Hãy chạy patch từ root Laravel Lin Xén, nơi có file artisan.' \
      >&2
    exit 1
}

test -f "$BOOTSTRAP" || {
    printf 'ERROR: Thiếu %s\n' "$BOOTSTRAP" >&2
    exit 1
}

test -f "$SMOKE_COMMAND" || {
    printf '%s\n' \
      'ERROR: Thiếu CommerceV2SmokeCommand.php. Source SF-01/SF-02 chưa được deploy đầy đủ.' \
      >&2
    exit 1
}

for REQUIRED in \
  config/commerce_v2.php \
  app/Services/CommerceV2/ErpCommerceClient.php \
  app/Services/CommerceV2/CommerceV2Presenter.php \
  app/Http/Controllers/CommerceV2/CatalogPageController.php \
  routes/commerce_v2.php
do
    test -f "$REQUIRED" || {
        printf 'ERROR: Thiếu source SF-01/SF-02: %s\n' "$REQUIRED" >&2
        exit 1
    }
done

mkdir -p "$BACKUP_ROOT/$(dirname "$BOOTSTRAP")"
cp -p "$BOOTSTRAP" "$BACKUP_ROOT/$BOOTSTRAP"
printf 'existing\t%s\n' "$BOOTSTRAP" > "$MANIFEST"
PATCH_WRITTEN=1

export LINXEN_BOOTSTRAP_FILE="$BOOTSTRAP"
export LINXEN_MARKER_START="$MARKER_START"
export LINXEN_MARKER_END="$MARKER_END"

php <<'PHP'
<?php

$path = getenv('LINXEN_BOOTSTRAP_FILE');
$start = getenv('LINXEN_MARKER_START');
$end = getenv('LINXEN_MARKER_END');

if (! is_string($path) || ! is_file($path)) {
    fwrite(STDERR, "ERROR: bootstrap/app.php không hợp lệ.\n");
    exit(1);
}

$source = file_get_contents($path);

if (! is_string($source)) {
    fwrite(STDERR, "ERROR: Không đọc được bootstrap/app.php.\n");
    exit(1);
}

$commandDirectory = "__DIR__.'/../app/Console/Commands'";

$startCount = substr_count($source, $start);
$endCount = substr_count($source, $end);

if ($startCount !== $endCount || $startCount > 1) {
    fwrite(
        STDERR,
        "ERROR: Command discovery marker không cân bằng hoặc bị lặp.\n"
    );
    exit(1);
}

if ($startCount === 1) {
    if (! str_contains($source, $commandDirectory)) {
        fwrite(
            STDERR,
            "ERROR: Marker có nhưng thiếu command directory contract.\n"
        );
        exit(1);
    }

    echo "COMMAND_DISCOVERY_PATCH=ALREADY_APPLIED\n";
    exit(0);
}

if (str_contains($source, $commandDirectory)) {
    echo "COMMAND_DIRECTORY=ALREADY_REGISTERED\n";
    exit(0);
}

$anchor = "    ->withMiddleware(";
$anchorCount = substr_count($source, $anchor);

if ($anchorCount !== 1) {
    fwrite(
        STDERR,
        "ERROR: bootstrap withMiddleware anchor expected 1; found "
            . $anchorCount
            . "\n"
    );
    exit(1);
}

$block = <<<'BLOCK'
    /* AI_PATCH_LINXEN_COMMERCE_V2_COMMAND_DISCOVERY_V1_START */
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    /* AI_PATCH_LINXEN_COMMERCE_V2_COMMAND_DISCOVERY_V1_END */
BLOCK;

$patched = str_replace(
    $anchor,
    $block . "\n" . $anchor,
    $source
);

$written = file_put_contents($path, $patched);

if ($written === false || $written !== strlen($patched)) {
    fwrite(STDERR, "ERROR: Không ghi đầy đủ bootstrap/app.php.\n");
    exit(1);
}

echo "COMMAND_DISCOVERY_PATCH=APPLIED\n";
PHP

php -l "$BOOTSTRAP"
php -l "$SMOKE_COMMAND"

grep -Fq "$MARKER_START" "$BOOTSTRAP" \
  || grep -Fq "__DIR__.'/../app/Console/Commands'" "$BOOTSTRAP"

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
    else
        env \
          HOME="$(pwd)" \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan optimize:clear
    fi
)

ARTISAN_RUNNER=(php artisan)

if [ "$(id -u)" -eq 0 ] \
    && command -v sudo >/dev/null 2>&1 \
    && id www-data >/dev/null 2>&1
then
    ARTISAN_RUNNER=(
      sudo -u www-data env
      "HOME=$(pwd)"
      CACHE_STORE=file
      SESSION_DRIVER=file
      php artisan
    )
fi

"${ARTISAN_RUNNER[@]}" list --raw \
  | grep -E '^commerce-v2:smoke([[:space:]]|$)' \
  >/dev/null

printf '%s\n' 'COMMAND_DISCOVERY=PASS'

"${ARTISAN_RUNNER[@]}" about \
  | sed -n '1,45p'

trap - ERR

printf '%s\n' 'LINXEN_COMMERCE_V2_COMMAND_DISCOVERY_HOTFIX=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'TARGET_CODE_TREE=STOREFRONT'
printf '%s\n' 'MIGRATION=NONE'
printf '%s\n' 'DB_MUTATION=NONE'
printf '%s\n' 'PROVIDER_CALL=NONE'
printf '%s\n' 'V1_STOREFRONT=UNCHANGED'
printf '%s\n' 'V2_ROUTES=UNCHANGED'
printf '%s\n' 'COMMAND=commerce-v2:smoke'
