#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='linxen_checkout_controller_attribution_import_runtime_smoke_hotfix_v1_2'
CHECKOUT_CONTROLLER='app/Http/Controllers/CommerceV2/CheckoutController.php'
PHASES_SMOKE='app/Console/Commands/CommerceV2Phases47SmokeCommand.php'

BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_$(date +%Y%m%d_%H%M%S)"
MANIFEST="${BACKUP_ROOT}/manifest.tsv"
PATCH_WRITTEN=0

rollback() {
    STATUS=$?

    if [ "$PATCH_WRITTEN" -eq 1 ] && [ -f "$MANIFEST" ]; then
        printf '%s\n' \
          'Có lỗi bắt buộc. Đang rollback controller runtime hotfix...' \
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

for FILE in \
  "$CHECKOUT_CONTROLLER" \
  "$PHASES_SMOKE"
do
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

export LINXEN_CHECKOUT_CONTROLLER="$CHECKOUT_CONTROLLER"
export LINXEN_PHASES_SMOKE="$PHASES_SMOKE"

php <<'PHP'
<?php

$controllerPath = getenv('LINXEN_CHECKOUT_CONTROLLER');
$smokePath = getenv('LINXEN_PHASES_SMOKE');

if (
    ! is_string($controllerPath)
    || ! is_file($controllerPath)
    || ! is_string($smokePath)
    || ! is_file($smokePath)
) {
    fwrite(STDERR, "ERROR: Hotfix paths không hợp lệ.\n");
    exit(1);
}

$controller = file_get_contents($controllerPath);
$smoke = file_get_contents($smokePath);

if (! is_string($controller) || ! is_string($smoke)) {
    fwrite(STDERR, "ERROR: Không đọc được source cần sửa.\n");
    exit(1);
}

$missingImportAnchor = <<<'OLD'
use App\Services\CommerceV2\CheckoutQuoteSessionService;
use App\Services\CommerceV2\CustomerSessionService;
OLD;

$importReplacement = <<<'NEW'
use App\Services\CommerceV2\AttributionSessionService;
use App\Services\CommerceV2\CheckoutQuoteSessionService;
use App\Services\CommerceV2\CustomerSessionService;
NEW;

$importLine = 'use App\Services\CommerceV2\AttributionSessionService;';

if (! str_contains($controller, $importLine)) {
    $anchorCount = substr_count(
        $controller,
        $missingImportAnchor
    );

    if ($anchorCount !== 1) {
        fwrite(
            STDERR,
            "ERROR: CheckoutController import anchor expected 1; found "
                . $anchorCount
                . "\n"
        );
        exit(1);
    }

    $controller = str_replace(
        $missingImportAnchor,
        $importReplacement,
        $controller,
        $count
    );

    if ($count !== 1) {
        fwrite(STDERR, "ERROR: Import replacement count invalid.\n");
        exit(1);
    }

    echo "CHECKOUT_ATTRIBUTION_IMPORT=APPLIED\n";
} else {
    echo "CHECKOUT_ATTRIBUTION_IMPORT=ALREADY_APPLIED\n";
}

$expectedRoutesAnchor = <<<'OLD'
            $expectedRoutes = [
                'commerce.v2.attribution.go',
OLD;

$controllerResolutionBlock = <<<'NEW'
            $resolvedControllers = collect([
                \App\Http\Controllers\CommerceV2\CheckoutController::class,
                \App\Http\Controllers\CommerceV2\OrderController::class,
                \App\Http\Controllers\CommerceV2\AttributionRedirectController::class,
                \App\Http\Controllers\CommerceV2\DiscoverController::class,
            ])->every(function (string $controller): bool {
                return app()->make($controller) instanceof $controller;
            });

            $expectedRoutes = [
                'commerce.v2.attribution.go',
NEW;

$checksAnchor = <<<'OLD'
                'routes' => collect($expectedRoutes)
                    ->every(fn ($name) => Route::has($name)),
                'discover_view_render' => str_contains(
OLD;

$checksReplacement = <<<'NEW'
                'routes' => collect($expectedRoutes)
                    ->every(fn ($name) => Route::has($name)),
                'controller_container_resolution' =>
                    $resolvedControllers,
                'discover_view_render' => str_contains(
NEW;

$alreadySmokePatched = (
    str_contains(
        $smoke,
        '$resolvedControllers = collect(['
    )
    && str_contains(
        $smoke,
        "'controller_container_resolution'"
    )
);

if (! $alreadySmokePatched) {
    $routesAnchorCount = substr_count(
        $smoke,
        $expectedRoutesAnchor
    );
    $checksAnchorCount = substr_count(
        $smoke,
        $checksAnchor
    );

    if (
        $routesAnchorCount !== 1
        || $checksAnchorCount !== 1
    ) {
        fwrite(
            STDERR,
            "ERROR: Smoke anchors mismatch. routes="
                . $routesAnchorCount
                . ", checks="
                . $checksAnchorCount
                . "\n"
        );
        exit(1);
    }

    $smoke = str_replace(
        [
            $expectedRoutesAnchor,
            $checksAnchor,
        ],
        [
            $controllerResolutionBlock,
            $checksReplacement,
        ],
        $smoke,
        $count
    );

    if ($count !== 2) {
        fwrite(
            STDERR,
            "ERROR: Smoke replacement count expected 2; found "
                . $count
                . "\n"
        );
        exit(1);
    }

    echo "CONTROLLER_CONTAINER_SMOKE=APPLIED\n";
} else {
    echo "CONTROLLER_CONTAINER_SMOKE=ALREADY_APPLIED\n";
}

foreach ([
    $controllerPath => $controller,
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

php -l "$CHECKOUT_CONTROLLER"
php -l "$PHASES_SMOKE"

grep -Fq \
  'use App\Services\CommerceV2\AttributionSessionService;' \
  "$CHECKOUT_CONTROLLER"

grep -Fq \
  "'controller_container_resolution'" \
  "$PHASES_SMOKE"

grep -Fq \
  '\App\Http\Controllers\CommerceV2\CheckoutController::class' \
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
    else
        env \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan optimize:clear
    fi
)

php <<'PHP'
<?php

require getcwd() . '/vendor/autoload.php';

$app = require getcwd() . '/bootstrap/app.php';

$app->make(
    Illuminate\Contracts\Console\Kernel::class
)->bootstrap();

$controllers = [
    App\Http\Controllers\CommerceV2\CheckoutController::class,
    App\Http\Controllers\CommerceV2\OrderController::class,
    App\Http\Controllers\CommerceV2\AttributionRedirectController::class,
    App\Http\Controllers\CommerceV2\DiscoverController::class,
];

foreach ($controllers as $controller) {
    $resolved = $app->make($controller);

    if (! $resolved instanceof $controller) {
        fwrite(
            STDERR,
            "CONTROLLER_CONTAINER_RESOLUTION_FAIL={$controller}\n"
        );
        exit(1);
    }

    printf(
        "CONTROLLER_RESOLVED=%s\n",
        $controller
    );
}

$expectedRoutes = [
    'commerce.v2.attribution.go',
    'commerce.v2.checkout.index',
    'commerce.v2.checkout.confirm',
    'commerce.v2.orders.store',
    'commerce.v2.orders.index',
    'commerce.v2.orders.show',
    'commerce.v2.discover',
];

foreach ($expectedRoutes as $routeName) {
    if (! Illuminate\Support\Facades\Route::has($routeName)) {
        fwrite(
            STDERR,
            "ROUTE_DISCOVERY_FAIL={$routeName}\n"
        );
        exit(1);
    }
}

echo "CONTROLLER_CONTAINER_RESOLUTION=PASS\n";
echo "PHASES4_7_ROUTE_DISCOVERY=PASS\n";
echo "LOCAL_ERP_API_CALL=NONE\n";
PHP

trap - ERR

printf '%s\n' 'LINXEN_CHECKOUT_CONTROLLER_RUNTIME_HOTFIX_V1_2=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'ROOT_CAUSE=MISSING_ATTRIBUTION_SESSION_SERVICE_IMPORT'
printf '%s\n' 'LOCAL_FULL_API_SMOKE=SKIPPED_TOKEN_NOT_REQUIRED'
printf '%s\n' 'ROUTE_PREFLIGHT=CANONICAL_MASTER_BUNDLE_NAMES'
printf '%s\n' 'CONTROLLER_CONTAINER_RESOLUTION_SMOKE=ENABLED'
printf '%s\n' 'ROUTES_CHANGED=NO'
printf '%s\n' 'MIGRATION=NONE'
printf '%s\n' 'DB_MUTATION=NONE'
printf '%s\n' 'ORDER_MUTATION=NONE'
printf '%s\n' 'PROVIDER_CALL=NONE'
printf '%s\n' 'COMMERCE_RUNTIME_GATE_CHANGED=NO'
printf '%s\n' 'V1_STOREFRONT=UNCHANGED'
