#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='linxen_checkout_foundation_smoke_phase5_semantics_hotfix_v1'
COMMAND_FILE='app/Console/Commands/CommerceV2CheckoutFoundationSmokeCommand.php'

BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_$(date +%Y%m%d_%H%M%S)"
MANIFEST="${BACKUP_ROOT}/manifest.tsv"
PATCH_WRITTEN=0

rollback() {
    STATUS=$?

    if [ "$PATCH_WRITTEN" -eq 1 ] && [ -f "$MANIFEST" ]; then
        printf '%s\n' \
          'Có lỗi bắt buộc. Đang rollback checkout smoke hotfix...' \
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

test -f "$COMMAND_FILE" || {
    printf 'ERROR: Thiếu %s\n' "$COMMAND_FILE" >&2
    exit 1
}

mkdir -p "$BACKUP_ROOT/$(dirname "$COMMAND_FILE")"
cp -p "$COMMAND_FILE" "$BACKUP_ROOT/$COMMAND_FILE"
printf 'existing\t%s\n' "$COMMAND_FILE" > "$MANIFEST"
PATCH_WRITTEN=1

export LINXEN_SMOKE_COMMAND_FILE="$COMMAND_FILE"

php <<'PHP'
<?php

$path = getenv('LINXEN_SMOKE_COMMAND_FILE');

if (! is_string($path) || ! is_file($path)) {
    fwrite(STDERR, "ERROR: Smoke command path không hợp lệ.\n");
    exit(1);
}

$source = file_get_contents($path);

if (! is_string($source)) {
    fwrite(STDERR, "ERROR: Không đọc được smoke command.\n");
    exit(1);
}

$oldDescription = <<<'OLD'
    protected $description = 'Static route and Blade smoke for Storefront V2 quote/shipping phase.';
OLD;

$newDescription = <<<'NEW'
    protected $description = 'Static route and Blade smoke for Storefront V2 quote, local-order and outbox foundation.';
NEW;

$oldChecks = <<<'OLD'
                'confirm_view_render' => (
                    str_contains(
                        $confirmHtml,
                        'qt_static_smoke'
                    )
                    && str_contains(
                        $confirmHtml,
                        'Đặt hàng sẽ mở ở Phase 5'
                    )
                ),
                'order_commit_disabled' => str_contains(
                    $confirmHtml,
                    'disabled'
                ),
OLD;

$newChecks = <<<'NEW'
                'confirm_view_render' => (
                    str_contains(
                        $confirmHtml,
                        'qt_static_smoke'
                    )
                    && str_contains(
                        $confirmHtml,
                        'Xác nhận đặt hàng'
                    )
                ),
                'local_order_control_present' => (
                    str_contains(
                        $confirmHtml,
                        route('commerce.v2.orders.store')
                    )
                    && str_contains(
                        $confirmHtml,
                        'Xác nhận đặt hàng COD'
                    )
                ),
                'idempotency_contract_present' => str_contains(
                    $confirmHtml,
                    'idempotency key'
                ),
                'provider_outbox_contract_present' => str_contains(
                    $confirmHtml,
                    'Outbox bất đồng bộ'
                ),
                'order_mutation_none' => true,
                'provider_mutation_none' => true,
NEW;

$alreadyApplied = (
    str_contains(
        $source,
        "'local_order_control_present'"
    )
    && str_contains(
        $source,
        "'provider_outbox_contract_present'"
    )
    && ! str_contains(
        $source,
        "'order_commit_disabled'"
    )
);

if ($alreadyApplied) {
    echo "CHECKOUT_SMOKE_PHASE5_SEMANTICS=ALREADY_APPLIED\n";
    exit(0);
}

$descriptionCount = substr_count($source, $oldDescription);
$checksCount = substr_count($source, $oldChecks);

if ($descriptionCount !== 1 || $checksCount !== 1) {
    fwrite(
        STDERR,
        "ERROR: Checkout smoke anchors mismatch. description="
            . $descriptionCount
            . ", checks="
            . $checksCount
            . "\n"
    );
    exit(1);
}

$patched = str_replace(
    [$oldDescription, $oldChecks],
    [$newDescription, $newChecks],
    $source,
    $count
);

if ($count !== 2) {
    fwrite(
        STDERR,
        "ERROR: Checkout smoke replacement count expected 2; found "
            . $count
            . "\n"
    );
    exit(1);
}

$written = file_put_contents($path, $patched);

if ($written === false || $written !== strlen($patched)) {
    fwrite(STDERR, "ERROR: Không ghi đầy đủ smoke command.\n");
    exit(1);
}

echo "CHECKOUT_SMOKE_PHASE5_SEMANTICS=APPLIED\n";
PHP

php -l "$COMMAND_FILE"

grep -Fq \
  "'local_order_control_present'" \
  "$COMMAND_FILE"

grep -Fq \
  "'idempotency_contract_present'" \
  "$COMMAND_FILE"

grep -Fq \
  "'provider_outbox_contract_present'" \
  "$COMMAND_FILE"

grep -Fq \
  "'order_mutation_none' => true" \
  "$COMMAND_FILE"

grep -Fq \
  "'provider_mutation_none' => true" \
  "$COMMAND_FILE"

if grep -Fq \
  "'order_commit_disabled'" \
  "$COMMAND_FILE"
then
    printf '%s\n' \
      'ERROR: Phase 4 stale assertion vẫn còn.' \
      >&2
    exit 1
fi

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
          php artisan commerce-v2:checkout-foundation-smoke
    else
        env \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan optimize:clear

        env \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan commerce-v2:checkout-foundation-smoke
    fi
)

trap - ERR

printf '%s\n' 'LINXEN_CHECKOUT_SMOKE_PHASE5_SEMANTICS_HOTFIX=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'ROOT_CAUSE=PHASE4_STALE_SMOKE_ASSERTIONS'
printf '%s\n' 'CHECKOUT_CONFIRM_VIEW_SOURCE_CHANGED=NO'
printf '%s\n' 'ORDER_RUNTIME_GATE_CHANGED=NO'
printf '%s\n' 'ORDER_DB_MUTATION=NONE'
printf '%s\n' 'PROVIDER_CALL=NONE'
printf '%s\n' 'MIGRATION=NONE'
printf '%s\n' 'V1_STOREFRONT=UNCHANGED'
