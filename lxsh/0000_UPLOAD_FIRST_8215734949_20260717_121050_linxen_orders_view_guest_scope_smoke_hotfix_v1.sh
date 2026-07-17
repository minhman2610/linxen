#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='linxen_orders_view_guest_scope_smoke_hotfix_v1'
ORDERS_VIEW='resources/views/commerce_v2/pages/orders.blade.php'
PHASES_SMOKE='app/Console/Commands/CommerceV2Phases47SmokeCommand.php'

BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_$(date +%Y%m%d_%H%M%S)"
MANIFEST="${BACKUP_ROOT}/manifest.tsv"
PATCH_WRITTEN=0

rollback() {
    STATUS=$?

    if [ "$PATCH_WRITTEN" -eq 1 ] && [ -f "$MANIFEST" ]; then
        printf '%s\n' \
          'Có lỗi bắt buộc. Đang rollback orders view hotfix...' \
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

for FILE in "$ORDERS_VIEW" "$PHASES_SMOKE"; do
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

export LINXEN_ORDERS_VIEW="$ORDERS_VIEW"
export LINXEN_PHASES_SMOKE="$PHASES_SMOKE"

php <<'PHP'
<?php

$viewPath = getenv('LINXEN_ORDERS_VIEW');
$smokePath = getenv('LINXEN_PHASES_SMOKE');

if (
    ! is_string($viewPath)
    || ! is_file($viewPath)
    || ! is_string($smokePath)
    || ! is_file($smokePath)
) {
    fwrite(STDERR, "ERROR: Hotfix paths không hợp lệ.\n");
    exit(1);
}

$view = file_get_contents($viewPath);
$smoke = file_get_contents($smokePath);

if (! is_string($view) || ! is_string($smoke)) {
    fwrite(STDERR, "ERROR: Không đọc được source cần sửa.\n");
    exit(1);
}

$oldViewBlock = <<<'OLD'
@php
    $items = collect((array) data_get($orders, 'items', []));
@endphp
OLD;

$newViewBlock = <<<'NEW'
@php
    $verifiedHistory = (bool) ($verifiedHistory ?? false);
    $guestHistoryNotice = (bool) (
        $guestHistoryNotice ?? ! $verifiedHistory
    );
    $items = collect(
        (array) data_get(
            $orders ?? [],
            'items',
            []
        )
    );
@endphp
NEW;

if (! str_contains(
    $view,
    '$verifiedHistory = (bool) ($verifiedHistory ?? false);'
)) {
    $viewAnchorCount = substr_count(
        $view,
        $oldViewBlock
    );

    if ($viewAnchorCount !== 1) {
        fwrite(
            STDERR,
            "ERROR: Orders view anchor expected 1; found "
                . $viewAnchorCount
                . "\n"
        );
        exit(1);
    }

    $view = str_replace(
        $oldViewBlock,
        $newViewBlock,
        $view,
        $count
    );

    if ($count !== 1) {
        fwrite(
            STDERR,
            "ERROR: Orders view replacement count invalid.\n"
        );
        exit(1);
    }

    echo "ORDERS_VIEW_SAFE_DEFAULTS=APPLIED\n";
} else {
    echo "ORDERS_VIEW_SAFE_DEFAULTS=ALREADY_APPLIED\n";
}

$oldSmokeBlock = <<<'OLD'
                'orders_view_render' => str_contains(
                    view('commerce_v2.pages.orders', [
                        'orders' => ['items' => []],
                        'pageTitle' => 'Đơn hàng — LIN XÉN',
                    ])->render(),
                    'Đơn hàng'
                ),
OLD;

$newSmokeBlock = <<<'NEW'
                'orders_view_render' => (
                    str_contains(
                        view('commerce_v2.pages.orders', [
                            'orders' => ['items' => []],
                            'verifiedHistory' => false,
                            'guestHistoryNotice' => true,
                            'pageTitle' => 'Đơn hàng — LIN XÉN',
                        ])->render(),
                        'Đơn trong phiên hiện tại'
                    )
                ),
                'orders_guest_scope_copy' => (
                    str_contains(
                        view('commerce_v2.pages.orders', [
                            'orders' => ['items' => []],
                            'verifiedHistory' => false,
                            'guestHistoryNotice' => true,
                            'pageTitle' => 'Đơn hàng — LIN XÉN',
                        ])->render(),
                        'Guest checkout chỉ hiển thị đơn được tạo trong phiên'
                    )
                ),
NEW;

if (! str_contains(
    $smoke,
    "'orders_guest_scope_copy'"
)) {
    $smokeAnchorCount = substr_count(
        $smoke,
        $oldSmokeBlock
    );

    if ($smokeAnchorCount !== 1) {
        fwrite(
            STDERR,
            "ERROR: Orders smoke anchor expected 1; found "
                . $smokeAnchorCount
                . "\n"
        );
        exit(1);
    }

    $smoke = str_replace(
        $oldSmokeBlock,
        $newSmokeBlock,
        $smoke,
        $count
    );

    if ($count !== 1) {
        fwrite(
            STDERR,
            "ERROR: Orders smoke replacement count invalid.\n"
        );
        exit(1);
    }

    echo "ORDERS_GUEST_SCOPE_SMOKE=APPLIED\n";
} else {
    echo "ORDERS_GUEST_SCOPE_SMOKE=ALREADY_APPLIED\n";
}

foreach ([
    $viewPath => $view,
    $smokePath => $smoke,
] as $path => $source) {
    $written = file_put_contents($path, $source);

    if (
        $written === false
        || $written !== strlen($source)
    ) {
        fwrite(
            STDERR,
            "ERROR: Không ghi đầy đủ source: {$path}\n"
        );
        exit(1);
    }
}
PHP

php -l "$PHASES_SMOKE"

grep -Fq -- \
  '$verifiedHistory = (bool) ($verifiedHistory ?? false);' \
  "$ORDERS_VIEW"

grep -Fq -- \
  "'orders_guest_scope_copy'" \
  "$PHASES_SMOKE"

grep -Fq -- \
  "'verifiedHistory' => false" \
  "$PHASES_SMOKE"

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

php <<'PHP'
<?php

require getcwd() . '/vendor/autoload.php';

$app = require getcwd() . '/bootstrap/app.php';

$app->make(
    Illuminate\Contracts\Console\Kernel::class
)->bootstrap();

$html = view(
    'commerce_v2.pages.orders',
    [
        'orders' => ['items' => []],
        'pageTitle' => 'Đơn hàng — LIN XÉN',
    ]
)->render();

$checks = [
    'orders_view_default_render' => str_contains(
        $html,
        'Đơn trong phiên hiện tại'
    ),
    'guest_scope_privacy_copy' => str_contains(
        $html,
        'Guest checkout chỉ hiển thị đơn được tạo trong phiên'
    ),
];

foreach ($checks as $code => $passed) {
    printf(
        "%s=%s\n",
        strtoupper($code),
        $passed ? 'PASS' : 'FAIL'
    );
}

if (in_array(false, $checks, true)) {
    exit(1);
}

echo "LOCAL_ORDERS_VIEW_RENDER=PASS\n";
echo "LOCAL_ERP_API_CALL=NONE\n";
PHP

trap - ERR

printf '%s\n' 'LINXEN_ORDERS_VIEW_GUEST_SCOPE_HOTFIX=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'ROOT_CAUSE=SMOKE_DID_NOT_PASS_VERIFIED_HISTORY'
printf '%s\n' 'ORDERS_VIEW_DEFAULTS=DEFENSIVE'
printf '%s\n' 'GUEST_ORDER_PRIVACY=UNCHANGED'
printf '%s\n' 'MIGRATION=NONE'
printf '%s\n' 'DB_MUTATION=NONE'
printf '%s\n' 'ORDER_MUTATION=NONE'
printf '%s\n' 'PROVIDER_CALL=NONE'
printf '%s\n' 'COMMERCE_RUNTIME_GATE_CHANGED=NO'
printf '%s\n' 'V1_STOREFRONT=UNCHANGED'
