#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='customer_messaging_v900_phase335_provider_cost_guard_material_contract'
CYCLE='app/Services/ErpV2/Marketing/CustomerMessaging/V700/CustomerMessagingDecisionCycleV750Service.php'
SHADOW='app/Console/Commands/CustomerMessagingV900ShadowBrainCommand.php'
ROUTER='app/Services/ErpV2/Marketing/CustomerMessaging/CustomerMessagingDomainFactsRouterV500Service.php'
GUARD='app/Services/ErpV2/Marketing/CustomerMessaging/V700/V900/CustomerMessagingProviderCostGuardV900Service.php'
SMOKE='app/Console/Commands/CustomerMessagingV900CostGuardSmokeCommand.php'
FILES=("$CYCLE" "$SHADOW" "$ROUTER" "$GUARD" "$SMOKE")

if [ ! -f artisan ]; then
  printf '%s\n' 'ERROR: Hãy chạy patch từ root Laravel có file artisan.' >&2
  exit 1
fi

for required in "$CYCLE" "$SHADOW" "$ROUTER"; do
  if [ ! -f "$required" ]; then
    printf 'ERROR: Thiếu file bắt buộc: %s\n' "$required" >&2
    exit 1
  fi
done

grep -Fq 'AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE33_RISK_ACTION_CONTRACT' "$CYCLE" || {
  printf '%s\n' 'ERROR: Decision Cycle V900 Phase 3.3 marker không tồn tại.' >&2
  exit 1
}
grep -Fq 'AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE2_SHADOW_COMMAND' "$SHADOW" || {
  printf '%s\n' 'ERROR: V900 Shadow command marker không tồn tại.' >&2
  exit 1
}
grep -Fq 'final class CustomerMessagingDomainFactsRouterV500Service' "$ROUTER" || {
  printf '%s\n' 'ERROR: Domain Facts Router V500 không đúng source dự kiến.' >&2
  exit 1
}

ALL_APPLIED=1
grep -Fq 'AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_COST_GUARD_CYCLE' "$CYCLE" || ALL_APPLIED=0
grep -Fq 'AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_SHADOW_COST_AUTH' "$SHADOW" || ALL_APPLIED=0
grep -Fq 'AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_MATERIAL_LIST_REPLACE' "$ROUTER" || ALL_APPLIED=0
[ -f "$GUARD" ] && grep -Fq 'AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_PROVIDER_COST_GUARD' "$GUARD" || ALL_APPLIED=0
[ -f "$SMOKE" ] && grep -Fq 'AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_COST_GUARD_SMOKE' "$SMOKE" || ALL_APPLIED=0

TIMESTAMP="$(date '+%Y%m%d_%H%M%S')"
BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_${TIMESTAMP}"
TMP_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/customer_messaging_phase335.XXXXXX")"
PATCH_WRITTEN=0
GUARD_EXISTED=0
SMOKE_EXISTED=0
[ -f "$GUARD" ] && GUARD_EXISTED=1
[ -f "$SMOKE" ] && SMOKE_EXISTED=1

rollback() {
  status=$?
  trap - EXIT INT TERM
  if [ "$status" -ne 0 ] && [ "$PATCH_WRITTEN" -eq 1 ]; then
    for file in "${FILES[@]}"; do
      if [ -f "$BACKUP_ROOT/$file" ]; then
        mkdir -p "$(dirname "$file")"
        cp -p "$BACKUP_ROOT/$file" "$file"
      elif [ "$file" = "$GUARD" ] && [ "$GUARD_EXISTED" -eq 0 ]; then
        rm -f "$file"
      elif [ "$file" = "$SMOKE" ] && [ "$SMOKE_EXISTED" -eq 0 ]; then
        rm -f "$file"
      fi
    done
    php artisan optimize:clear >/dev/null 2>&1 || true
    printf 'ERROR: Patch failed; source restored from %s\n' "$BACKUP_ROOT" >&2
  fi
  rm -rf "$TMP_ROOT"
  exit "$status"
}
trap rollback EXIT INT TERM

if [ "$ALL_APPLIED" -eq 0 ]; then
  mkdir -p "$BACKUP_ROOT"
  for file in "${FILES[@]}"; do
    if [ -e "$file" ]; then
      mkdir -p "$BACKUP_ROOT/$(dirname "$file")"
      cp -p "$file" "$BACKUP_ROOT/$file"
    fi
  done

  printf '{"patch":"%s","generated_at":"%s","files":[' \
    "$PATCH_NAME" "$(date '+%Y-%m-%dT%H:%M:%S%z')" \
    > "$BACKUP_ROOT/manifest.json"
  printf '"%s","%s","%s","%s","%s"]}\n' \
    "$CYCLE" "$SHADOW" "$ROUTER" "$GUARD" "$SMOKE" \
    >> "$BACKUP_ROOT/manifest.json"

  for file in "$CYCLE" "$SHADOW" "$ROUTER"; do
    mkdir -p "$TMP_ROOT/$(dirname "$file")"
    cp -p "$file" "$TMP_ROOT/$file"
  done

  mkdir -p "$TMP_ROOT/$(dirname "$GUARD")"
  cat > "$TMP_ROOT/$GUARD" <<'PHP_GUARD'
<?php

declare(strict_types=1);

namespace App\Services\ErpV2\Marketing\CustomerMessaging\V700\V900;

use RuntimeException;
use Throwable;

/**
 * AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_PROVIDER_COST_GUARD
 *
 * File-backed, fail-closed provider circuit breaker. It does not store secrets,
 * call providers, write business tables or activate runtime workers.
 */
final class CustomerMessagingProviderCostGuardV900Service
{
    public const VERSION =
        'customer_messaging_provider_cost_guard_v900_phase3_3_5';

    public function reserve(
        array $event,
        array $context = [],
        array $options = []
    ): array {
        $policy = $this->policy($options);
        $shadow = data_get($options, 'v900_shadow_only') === true;
        $shadowAuthorized = data_get(
            $options,
            'customer_messaging_cost_guard_shadow_authorized'
        ) === true;

        if ($shadow) {
            if (! $policy['shadow_enabled'] || ! $shadowAuthorized) {
                return $this->blocked(
                    'shadow_not_authorized_v900',
                    $policy,
                    $shadow
                );
            }
        } elseif (! $policy['live_enabled']) {
            return $this->blocked(
                'live_provider_kill_switch_closed_v900',
                $policy,
                $shadow
            );
        }

        if ($policy['hard_kill_file'] !== ''
            && is_file($policy['hard_kill_file'])) {
            return $this->blocked(
                'provider_hard_kill_file_present_v900',
                $policy,
                $shadow
            );
        }

        if ($policy['local_trip_file'] !== ''
            && is_file($policy['local_trip_file'])) {
            return $this->blocked(
                'provider_local_trip_file_present_v900',
                $policy,
                $shadow
            );
        }

        $root = $policy['storage_path'];
        $minuteBucket = now()->format('YmdHi');
        $dayBucket = now()->format('Ymd');
        $reservedCalls = $policy['reserved_calls_per_cycle'];

        try {
            if (! is_dir($root)
                && ! mkdir($root, 0770, true)
                && ! is_dir($root)) {
                throw new RuntimeException(
                    'Cannot create provider cost guard storage directory.'
                );
            }

            $lockPath = $root . '/guard.lock';
            $handle = fopen($lockPath, 'c+');
            if ($handle === false) {
                throw new RuntimeException(
                    'Cannot open provider cost guard lock.'
                );
            }

            try {
                if (! flock($handle, LOCK_EX)) {
                    throw new RuntimeException(
                        'Cannot acquire provider cost guard lock.'
                    );
                }

                $statePath = $root . '/state.json';
                $state = $this->readState($statePath);
                $state = $this->normalizeState(
                    $state,
                    $minuteBucket,
                    $dayBucket
                );

                $nextMinuteCycles =
                    (int) data_get($state, 'minute.cycles', 0) + 1;
                $nextMinuteCalls =
                    (int) data_get($state, 'minute.reserved_calls', 0)
                    + $reservedCalls;
                $nextDayCycles =
                    (int) data_get($state, 'day.cycles', 0) + 1;
                $nextDayCalls =
                    (int) data_get($state, 'day.reserved_calls', 0)
                    + $reservedCalls;

                $reason = null;
                if ($nextMinuteCycles > $policy['max_cycles_per_minute']) {
                    $reason = 'minute_cycle_limit_reached_v900';
                } elseif ($nextMinuteCalls
                    > $policy['max_reserved_calls_per_minute']) {
                    $reason = 'minute_call_limit_reached_v900';
                } elseif ($nextDayCycles > $policy['max_cycles_per_day']) {
                    $reason = 'daily_cycle_limit_reached_v900';
                } elseif ($nextDayCalls
                    > $policy['max_reserved_calls_per_day']) {
                    $reason = 'daily_call_limit_reached_v900';
                }

                if ($reason !== null) {
                    return $this->blocked(
                        $reason,
                        $policy,
                        $shadow,
                        $state
                    );
                }

                $state['minute']['cycles'] = $nextMinuteCycles;
                $state['minute']['reserved_calls'] = $nextMinuteCalls;
                $state['day']['cycles'] = $nextDayCycles;
                $state['day']['reserved_calls'] = $nextDayCalls;
                $state['last_reserved_at'] = now()->toDateTimeString();
                $state['last_turn_key'] = data_get(
                    $event,
                    'customer_turn_v400.turn_key',
                    data_get($event, 'customer_turn.turn_key')
                );
                $state['last_conversation_id'] = data_get(
                    $event,
                    'conversation_row_id',
                    data_get($context, 'conversation_id')
                );

                $this->writeState($statePath, $state);

                return [
                    'allowed' => true,
                    'ok' => true,
                    'version' => self::VERSION,
                    'reason' => 'provider_budget_reserved_v900',
                    'shadow' => $shadow,
                    'reservation_id' => bin2hex(random_bytes(12)),
                    'reserved_calls' => $reservedCalls,
                    'max_calls_per_cycle' =>
                        $policy['max_calls_per_cycle'],
                    'policy' => $this->publicPolicy($policy),
                    'state' => $this->publicState($state),
                    'provider_called' => false,
                    'database_writes' => 0,
                    'business_mutations' => 0,
                ];
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        } catch (Throwable $exception) {
            report($exception);

            return $this->blocked(
                'provider_cost_guard_unavailable_fail_closed_v900',
                $policy,
                $shadow,
                [],
                get_class($exception)
            );
        }
    }


    public function trip(array $options, string $reason): array
    {
        $policy = $this->policy($options);
        $path = $policy['local_trip_file'];
        $payload = [
            'version' => self::VERSION,
            'tripped' => true,
            'reason' => trim($reason) !== ''
                ? trim($reason)
                : 'provider_http_error_v900',
            'tripped_at' => now()->toDateTimeString(),
            'provider_called' => false,
            'database_writes' => 0,
            'business_mutations' => 0,
        ];

        try {
            $root = dirname($path);
            if (! is_dir($root)
                && ! mkdir($root, 0770, true)
                && ! is_dir($root)) {
                throw new RuntimeException(
                    'Cannot create provider trip directory.'
                );
            }
            $this->writeState($path, $payload);

            return $payload;
        } catch (Throwable $exception) {
            report($exception);
            $payload['tripped'] = false;
            $payload['exception_class'] = get_class($exception);

            return $payload;
        }
    }

    public function allowsAdditionalCalls(
        array $reservation,
        int $alreadyUsed,
        int $requested = 1
    ): array {
        $max = max(1, (int) data_get(
            $reservation,
            'max_calls_per_cycle',
            3
        ));
        $alreadyUsed = max(0, $alreadyUsed);
        $requested = max(0, $requested);
        $allowed = data_get($reservation, 'allowed') === true
            && ($alreadyUsed + $requested) <= $max;

        return [
            'allowed' => $allowed,
            'version' => self::VERSION,
            'reason' => $allowed
                ? 'additional_calls_allowed_v900'
                : 'per_cycle_call_limit_reached_v900',
            'already_used' => $alreadyUsed,
            'requested' => $requested,
            'max_calls_per_cycle' => $max,
            'remaining_before_request' => max(0, $max - $alreadyUsed),
        ];
    }

    public function complete(
        array $reservation,
        int $actualCalls
    ): array {
        return array_replace($reservation, [
            'actual_calls' => max(0, $actualCalls),
            'completed_at' => now()->toDateTimeString(),
            'within_cycle_limit' => max(0, $actualCalls)
                <= max(1, (int) data_get(
                    $reservation,
                    'max_calls_per_cycle',
                    3
                )),
        ]);
    }

    public function blockedDecisionCycleResult(
        array $decision,
        ?array $brain = null,
        ?array $readTools = null
    ): array {
        return [
            'ok' => false,
            'version' =>
                'customer_messaging_decision_cycle_v750',
            'status' => 'provider_cost_guard_blocked',
            'reason' => data_get(
                $decision,
                'reason',
                'provider_cost_guard_blocked_v900'
            ),
            'brain' => $brain,
            'read_tools' => $readTools,
            'finalizer' => null,
            'final_action' => null,
            'gpt_call_count' => (int) data_get(
                $brain,
                'gpt_call_count',
                0
            ),
            'production_owner_changed' => false,
            'outbound_executed' => false,
            'state_mutation_executed' => false,
            'side_effects' => [],
            'cost_guard_v900' => $decision,
            'latency_breakdown' => [
                'brain_ms' => (int) data_get(
                    $brain,
                    'latency_breakdown.total_ms',
                    0
                ),
                'read_tools_ms' => (int) data_get(
                    $readTools,
                    'latency_ms',
                    0
                ),
                'finalizer_ms' => 0,
                'total_ms' => 0,
            ],
        ];
    }

    public function policy(array $options = []): array
    {
        $override = data_get(
            $options,
            'customer_messaging_cost_guard_policy_override'
        );
        $testMode = data_get(
            $options,
            'customer_messaging_cost_guard_test_mode'
        ) === true;

        $policy = [
            'live_enabled' => $this->envBool(
                'CUSTOMER_MESSAGING_GPT_LIVE_ENABLED',
                false
            ),
            'shadow_enabled' => $this->envBool(
                'CUSTOMER_MESSAGING_GPT_SHADOW_ENABLED',
                true
            ),
            'max_calls_per_cycle' => $this->envInt(
                'CUSTOMER_MESSAGING_GPT_MAX_CALLS_PER_CYCLE',
                3,
                1,
                4
            ),
            'reserved_calls_per_cycle' => $this->envInt(
                'CUSTOMER_MESSAGING_GPT_RESERVED_CALLS_PER_CYCLE',
                3,
                1,
                4
            ),
            'max_cycles_per_minute' => $this->envInt(
                'CUSTOMER_MESSAGING_GPT_MAX_CYCLES_PER_MINUTE',
                2,
                1,
                20
            ),
            'max_reserved_calls_per_minute' => $this->envInt(
                'CUSTOMER_MESSAGING_GPT_MAX_RESERVED_CALLS_PER_MINUTE',
                6,
                1,
                80
            ),
            'max_cycles_per_day' => $this->envInt(
                'CUSTOMER_MESSAGING_GPT_MAX_CYCLES_PER_DAY',
                100,
                1,
                5000
            ),
            'max_reserved_calls_per_day' => $this->envInt(
                'CUSTOMER_MESSAGING_GPT_MAX_RESERVED_CALLS_PER_DAY',
                300,
                1,
                20000
            ),
            'storage_path' => storage_path(
                'app/customer_messaging_cost_guard_v900'
            ),
            'hard_kill_file' => trim((string) getenv(
                'CUSTOMER_MESSAGING_GPT_HARD_KILL_FILE'
            )) ?: '/etc/3mg/customer-messaging-gpt.disabled',
            'local_trip_file' => storage_path(
                'app/customer_messaging_cost_guard_v900/provider.disabled.json'
            ),
        ];

        if ($testMode && is_array($override)) {
            $policy = array_replace($policy, $override);
        }

        $policy['max_calls_per_cycle'] = max(
            1,
            min(4, (int) $policy['max_calls_per_cycle'])
        );
        $policy['reserved_calls_per_cycle'] = max(
            1,
            min(
                $policy['max_calls_per_cycle'],
                (int) $policy['reserved_calls_per_cycle']
            )
        );

        return $policy;
    }

    private function blocked(
        string $reason,
        array $policy,
        bool $shadow,
        array $state = [],
        ?string $exceptionClass = null
    ): array {
        return [
            'allowed' => false,
            'ok' => false,
            'version' => self::VERSION,
            'reason' => $reason,
            'shadow' => $shadow,
            'reservation_id' => null,
            'reserved_calls' => 0,
            'max_calls_per_cycle' =>
                $policy['max_calls_per_cycle'],
            'policy' => $this->publicPolicy($policy),
            'state' => $this->publicState($state),
            'exception_class' => $exceptionClass,
            'provider_called' => false,
            'database_writes' => 0,
            'business_mutations' => 0,
        ];
    }

    private function normalizeState(
        array $state,
        string $minuteBucket,
        string $dayBucket
    ): array {
        if (data_get($state, 'minute.bucket') !== $minuteBucket) {
            $state['minute'] = [
                'bucket' => $minuteBucket,
                'cycles' => 0,
                'reserved_calls' => 0,
            ];
        }

        if (data_get($state, 'day.bucket') !== $dayBucket) {
            $state['day'] = [
                'bucket' => $dayBucket,
                'cycles' => 0,
                'reserved_calls' => 0,
            ];
        }

        return array_replace([
            'version' => self::VERSION,
            'minute' => [
                'bucket' => $minuteBucket,
                'cycles' => 0,
                'reserved_calls' => 0,
            ],
            'day' => [
                'bucket' => $dayBucket,
                'cycles' => 0,
                'reserved_calls' => 0,
            ],
        ], $state);
    }

    private function readState(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function writeState(string $path, array $state): void
    {
        $tmp = $path . '.tmp.' . bin2hex(random_bytes(6));
        $json = json_encode(
            $state,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        if (! is_string($json)
            || file_put_contents($tmp, $json . PHP_EOL, LOCK_EX) === false) {
            @unlink($tmp);
            throw new RuntimeException(
                'Cannot write provider cost guard state.'
            );
        }

        chmod($tmp, 0660);

        if (! rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException(
                'Cannot atomically replace provider cost guard state.'
            );
        }
    }

    private function publicPolicy(array $policy): array
    {
        return collect($policy)->only([
            'live_enabled',
            'shadow_enabled',
            'max_calls_per_cycle',
            'reserved_calls_per_cycle',
            'max_cycles_per_minute',
            'max_reserved_calls_per_minute',
            'max_cycles_per_day',
            'max_reserved_calls_per_day',
            'hard_kill_file',
            'local_trip_file',
        ])->all();
    }

    private function publicState(array $state): array
    {
        return collect($state)->only([
            'minute',
            'day',
            'last_reserved_at',
            'last_turn_key',
            'last_conversation_id',
        ])->all();
    }

    private function envBool(string $key, bool $default): bool
    {
        $value = getenv($key);
        if ($value === false || trim((string) $value) === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function envInt(
        string $key,
        int $default,
        int $min,
        int $max
    ): int {
        $value = getenv($key);
        if (! is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }
}
PHP_GUARD

  mkdir -p "$TMP_ROOT/$(dirname "$SMOKE")"
  cat > "$TMP_ROOT/$SMOKE" <<'PHP_SMOKE'
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ErpV2\Marketing\CustomerMessaging\V700\V900\CustomerMessagingProviderCostGuardV900Service;
use Illuminate\Console\Command;

/**
 * AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_COST_GUARD_SMOKE
 */
final class CustomerMessagingV900CostGuardSmokeCommand extends Command
{
    protected $signature =
        'customer-messaging:v900-cost-guard-smoke
        {--json : Print complete JSON}';

    protected $description =
        'Offline V900 provider cost guard and material contract smoke.';

    public function handle(
        CustomerMessagingProviderCostGuardV900Service $guard
    ): int {
        $root = storage_path(
            'app/customer_messaging_cost_guard_v900_smoke_'
            . bin2hex(random_bytes(6))
        );
        $options = [
            'v900_shadow_only' => true,
            'customer_messaging_cost_guard_shadow_authorized' => true,
            'customer_messaging_cost_guard_test_mode' => true,
            'customer_messaging_cost_guard_policy_override' => [
                'live_enabled' => false,
                'shadow_enabled' => true,
                'max_calls_per_cycle' => 3,
                'reserved_calls_per_cycle' => 3,
                'max_cycles_per_minute' => 2,
                'max_reserved_calls_per_minute' => 6,
                'max_cycles_per_day' => 3,
                'max_reserved_calls_per_day' => 9,
                'storage_path' => $root,
                'hard_kill_file' => $root . '/disabled',
                'local_trip_file' => $root . '/provider.disabled.json',
            ],
        ];
        $event = [
            'conversation_row_id' => 5,
            'customer_turn_v400' => [
                'turn_key' => 'cm-v900-cost-guard-smoke',
            ],
        ];

        $first = $guard->reserve($event, [], $options);
        $second = $guard->reserve($event, [], $options);
        $third = $guard->reserve($event, [], $options);
        $live = $guard->reserve(
            $event,
            [],
            array_replace($options, [
                'v900_shadow_only' => false,
            ])
        );
        $additionalAllowed = $guard->allowsAdditionalCalls(
            $first,
            2,
            1
        );
        $additionalBlocked = $guard->allowsAdditionalCalls(
            $first,
            3,
            1
        );
        $trip = $guard->trip(
            $options,
            'smoke_provider_http_error_v900'
        );
        $afterTrip = $guard->reserve($event, [], $options);

        $routerSource = (string) @file_get_contents(base_path(
            'app/Services/ErpV2/Marketing/CustomerMessaging/'
            . 'CustomerMessagingDomainFactsRouterV500Service.php'
        ));
        $cycleSource = (string) @file_get_contents(base_path(
            'app/Services/ErpV2/Marketing/CustomerMessaging/V700/'
            . 'CustomerMessagingDecisionCycleV750Service.php'
        ));
        $shadowSource = (string) @file_get_contents(base_path(
            'app/Console/Commands/'
            . 'CustomerMessagingV900ShadowBrainCommand.php'
        ));

        $checks = [
            'live_kill_switch_is_closed_by_default' =>
                data_get($live, 'allowed') === false
                && data_get($live, 'reason') ===
                    'live_provider_kill_switch_closed_v900',
            'first_two_shadow_cycles_are_reserved' =>
                data_get($first, 'allowed') === true
                && data_get($second, 'allowed') === true,
            'third_shadow_cycle_is_rate_limited' =>
                data_get($third, 'allowed') === false
                && in_array(data_get($third, 'reason'), [
                    'minute_cycle_limit_reached_v900',
                    'minute_call_limit_reached_v900',
                ], true),
            'per_cycle_third_call_is_allowed' =>
                data_get($additionalAllowed, 'allowed') === true,
            'per_cycle_fourth_call_is_blocked' =>
                data_get($additionalBlocked, 'allowed') === false
                && data_get($additionalBlocked, 'reason') ===
                    'per_cycle_call_limit_reached_v900',
            'provider_http_error_trips_future_cycles' =>
                data_get($trip, 'tripped') === true
                && data_get($afterTrip, 'allowed') === false
                && data_get($afterTrip, 'reason') ===
                    'provider_local_trip_file_present_v900',
            'decision_cycle_cost_guard_marker_loaded' => str_contains(
                $cycleSource,
                'AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_COST_GUARD_CYCLE'
            ),
            'shadow_requires_explicit_cost_guard_authorization' =>
                str_contains(
                    $shadowSource,
                    'customer_messaging_cost_guard_shadow_authorized'
                ),
            'material_numeric_lists_use_top_level_replace' =>
                str_contains(
                    $routerSource,
                    'AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_MATERIAL_LIST_REPLACE'
                )
                && str_contains(
                    $routerSource,
                    '$normalizedMaterialV900 = array_replace('
                ),
            'no_provider_or_business_side_effects' => true,
        ];

        $this->deleteTree($root);

        $result = [
            'ok' => ! in_array(false, $checks, true),
            'version' =>
                'customer_messaging_v900_cost_guard_smoke_v1',
            'checks' => $checks,
            'samples' => [
                'first' => $first,
                'second' => $second,
                'third' => $third,
                'live' => $live,
                'additional_allowed' => $additionalAllowed,
                'additional_blocked' => $additionalBlocked,
                'trip' => $trip,
                'after_trip' => $afterTrip,
            ],
            'provider_calls' => 0,
            'outbound_sent' => false,
            'database_writes' => 0,
            'business_mutations' => 0,
            'runtime_changed' => false,
        ];

        $this->line(json_encode(
            $result,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        ) ?: '{}');

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }

    private function deleteTree(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $target = $path . '/' . $item;
            if (is_dir($target)) {
                $this->deleteTree($target);
            } else {
                @unlink($target);
            }
        }

        @rmdir($path);
    }
}
PHP_SMOKE

  cat > "$TMP_ROOT/phase335_patcher.php" <<'PHP_PATCHER'
<?php

declare(strict_types=1);

function source(string $env): string
{
    $path = getenv($env);
    if (! is_string($path) || $path === '' || ! is_file($path)) {
        throw new RuntimeException('Missing source path: ' . $env);
    }
    $value = file_get_contents($path);
    if (! is_string($value) || $value === '') {
        throw new RuntimeException('Cannot read source: ' . $env);
    }

    return $value;
}

function writeSource(string $env, string $value): void
{
    $path = getenv($env);
    if (! is_string($path) || $path === '') {
        throw new RuntimeException('Missing destination path: ' . $env);
    }
    if (file_put_contents($path, $value) === false) {
        throw new RuntimeException('Cannot write destination: ' . $env);
    }
}

function replaceOnce(
    string $source,
    string $search,
    string $replace,
    string $label
): string {
    $count = substr_count($source, $search);
    if ($count !== 1) {
        throw new RuntimeException(
            $label . ' expected exactly one anchor; found ' . $count
        );
    }

    return str_replace($search, $replace, $source);
}

function replaceFirst(
    string $source,
    string $search,
    string $replace,
    string $label
): string {
    $position = strpos($source, $search);
    if ($position === false) {
        throw new RuntimeException($label . ' anchor not found.');
    }

    return substr($source, 0, $position)
        . $replace
        . substr($source, $position + strlen($search));
}

$cycle = source('TMP_CYCLE');
if (! str_contains(
    $cycle,
    'AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_COST_GUARD_CYCLE'
)) {
    $cycle = replaceOnce(
        $cycle,
        <<<'OLD'
    ): array {
        /* AI_PATCH_CUSTOMER_MESSAGING_V8141_GPT_SALES_BRAIN_OWNERSHIP
OLD,
        <<<'NEW'
    ): array {
        /* AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_COST_GUARD_CYCLE
         | Reserve a conservative provider budget before any GPT call. Live
         | runtime is fail-closed by default; the acknowledged shadow command
         | supplies its own explicit authorization. No key is read or logged.
         */
        $costGuardV900 = app(
            \App\Services\ErpV2\Marketing\CustomerMessaging\V700\V900\CustomerMessagingProviderCostGuardV900Service::class
        );
        $costGuardReservationV900 = $costGuardV900->reserve(
            $event,
            $context,
            $options
        );
        if (data_get(
            $costGuardReservationV900,
            'allowed'
        ) !== true) {
            return $costGuardV900->blockedDecisionCycleResult(
                $costGuardReservationV900
            );
        }

        /* AI_PATCH_CUSTOMER_MESSAGING_V8141_GPT_SALES_BRAIN_OWNERSHIP
NEW,
        'Decision Cycle provider budget reservation'
    );

    $cycle = replaceOnce(
        $cycle,
        <<<'OLD'
        if (! (bool) data_get($brainResult, 'ok', false)) {
            return [
OLD,
        <<<'NEW'
        if (! (bool) data_get($brainResult, 'ok', false)) {
            if (str_contains(
                (string) data_get($brainResult, 'reason', ''),
                'http_error'
            )) {
                $costGuardReservationV900['trip'] =
                    $costGuardV900->trip(
                        $options,
                        'brain_provider_http_error_v900'
                    );
            }

            return [
NEW,
        'Decision Cycle provider HTTP trip after Brain failure'
    );

    $cycle = replaceOnce(
        $cycle,
        <<<'OLD'
        $finalizerResult = $this->finalizer->finalize(
OLD,
        <<<'NEW'
        $brainCallsBeforeFinalizerV900 = (int) data_get(
            $brainResult,
            'gpt_call_count',
            0
        );
        $finalizerGateV900 = $costGuardV900->allowsAdditionalCalls(
            $costGuardReservationV900,
            $brainCallsBeforeFinalizerV900,
            1
        );
        if (data_get($finalizerGateV900, 'allowed') !== true) {
            $blockedV900 = array_replace(
                $costGuardReservationV900,
                [
                    'reason' => data_get(
                        $finalizerGateV900,
                        'reason'
                    ),
                    'stage' => 'before_finalizer_v750',
                    'call_gate' => $finalizerGateV900,
                ]
            );

            return $costGuardV900->blockedDecisionCycleResult(
                $blockedV900,
                $brainResult,
                $readPacket
            );
        }
        $finalizerOptions['customer_messaging_cost_guard_v900'] =
            $costGuardReservationV900;
        $finalizerOptions[
            'customer_messaging_cost_guard_remaining_calls'
        ] = (int) data_get(
            $finalizerGateV900,
            'remaining_before_request',
            0
        );

        $finalizerResult = $this->finalizer->finalize(
NEW,
        'Decision Cycle finalizer call gate'
    );

    $cycle = replaceOnce(
        $cycle,
        <<<'OLD'
        $finalizerResult =
            $v817TextOnlyCapability->repairIfNeeded(
                $this->finalizer,
                $finalizerResult,
                $turn,
                $snapshot,
                $plan,
                $readPacket,
                $finalizerOptions
            );
OLD,
        <<<'NEW'
        $callsBeforeTextRepairV900 =
            $brainCallsBeforeFinalizerV900
            + (int) data_get(
                $finalizerResult,
                'gpt_call_count',
                0
            );
        $textRepairGateV900 =
            $costGuardV900->allowsAdditionalCalls(
                $costGuardReservationV900,
                $callsBeforeTextRepairV900,
                1
            );
        if (data_get($textRepairGateV900, 'allowed') === true) {
            $finalizerResult =
                $v817TextOnlyCapability->repairIfNeeded(
                    $this->finalizer,
                    $finalizerResult,
                    $turn,
                    $snapshot,
                    $plan,
                    $readPacket,
                    $finalizerOptions
                );
        } else {
            $finalizerResult['text_only_capability_v817'][
                'cost_guard_repair_skipped'
            ] = true;
            $finalizerResult['text_only_capability_v817'][
                'cost_guard_call_gate'
            ] = $textRepairGateV900;
        }
NEW,
        'Decision Cycle text repair call gate'
    );

    $riskCondition = <<<'OLD'
        if ($v900RiskActionAudit['handoff_required']
            && data_get(
                $finalizerResult,
                'final_action.action_type'
            ) !== 'handoff') {
OLD;
    $riskReplacement = <<<'NEW'
        if ($v900RiskActionAudit['handoff_required']
            && data_get(
                $finalizerResult,
                'final_action.action_type'
            ) !== 'handoff'
            && data_get(
                $costGuardV900->allowsAdditionalCalls(
                    $costGuardReservationV900,
                    $brainCallsBeforeFinalizerV900
                        + (int) data_get(
                            $finalizerResult,
                            'gpt_call_count',
                            0
                        ),
                    1
                ),
                'allowed'
            ) === true) {
NEW;
    $cycle = replaceFirst(
        $cycle,
        $riskCondition,
        $riskReplacement,
        'Decision Cycle risk repair call gate'
    );

    $cycle = replaceOnce(
        $cycle,
        <<<'OLD'
        $ok = (bool) data_get(
            $finalizerResult,
            'ok',
            false
        );

        return [
OLD,
        <<<'NEW'
        $ok = (bool) data_get(
            $finalizerResult,
            'ok',
            false
        );
        $finalizerReasonV900 = (string) data_get(
            $finalizerResult,
            'reason',
            ''
        );
        if (! $ok && str_contains(
            $finalizerReasonV900,
            'http_error'
        )) {
            $costGuardReservationV900['trip'] =
                $costGuardV900->trip(
                    $options,
                    'finalizer_provider_http_error_v900'
                );
        }
        $costGuardCompletionV900 = $costGuardV900->complete(
            $costGuardReservationV900,
            (int) data_get($brainResult, 'gpt_call_count', 0)
                + (int) data_get(
                    $finalizerResult,
                    'gpt_call_count',
                    0
                )
        );

        return [
NEW,
        'Decision Cycle completion audit and Finalizer HTTP trip'
    );

    $cycle = replaceOnce(
        $cycle,
        <<<'OLD'
        return [
            'ok' => $ok,
            'version' => self::VERSION,
            'status' => $ok
OLD,
        <<<'NEW'
        return [
            'ok' => $ok,
            'version' => self::VERSION,
            'cost_guard_v900' => $costGuardCompletionV900,
            'status' => $ok
NEW,
        'Decision Cycle final cost guard packet'
    );
}
writeSource('TMP_CYCLE', $cycle);

$shadow = source('TMP_SHADOW');
if (! str_contains(
    $shadow,
    'customer_messaging_cost_guard_shadow_authorized'
)) {
    $shadow = replaceOnce(
        $shadow,
        <<<'OLD'
                    'v900_shadow_only' => true,
                    'v900_shadow_run_id' => $runId,
OLD,
        <<<'NEW'
                    'v900_shadow_only' => true,
                    /* AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_SHADOW_COST_AUTH */
                    'customer_messaging_cost_guard_shadow_authorized' => true,
                    'v900_shadow_run_id' => $runId,
NEW,
        'Shadow command explicit cost guard authorization'
    );
}
writeSource('TMP_SHADOW', $shadow);

$router = source('TMP_ROUTER');
if (! str_contains(
    $router,
    'AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_MATERIAL_LIST_REPLACE'
)) {
    $router = replaceOnce(
        $router,
        <<<'OLD'
                if (is_array($result)) {
                    return array_replace_recursive($base, $result, [
                        'requested' => true,
                        'requested_sub_intents' => $subIntents,
                        'available' => (bool) data_get($result, 'available', false),
                        'locked' => (bool) data_get($result, 'locked', data_get($result, 'available', false)),
                        'missing_reason' => (bool) data_get($result, 'available', false) ? null : data_get($result, 'missing_reason', 'material_not_resolved_v500'),
                    ]);
                }
OLD,
        <<<'NEW'
                if (is_array($result)) {
                    /* AI_PATCH_CUSTOMER_MESSAGING_V900_PHASE335_MATERIAL_LIST_REPLACE
                     | Numeric lists are complete resolver outputs, not recursive
                     | overlays. Recursive replacement retained the base missing
                     | sub-intents at numeric index 0 after a successful result.
                     */
                    $resolvedMaterialSubIntentsV900 = array_values(
                        array_unique(array_filter((array) data_get(
                            $result,
                            'resolved_sub_intents',
                            []
                        )))
                    );
                    $missingMaterialSubIntentsV900 = array_values(
                        array_diff(
                            $subIntents,
                            $resolvedMaterialSubIntentsV900
                        )
                    );
                    $materialAvailableV900 =
                        (bool) data_get($result, 'available', false)
                        && $missingMaterialSubIntentsV900 === [];
                    $normalizedMaterialV900 = array_replace(
                        $base,
                        $result,
                        [
                            'requested' => true,
                            'requested_sub_intents' => $subIntents,
                            'resolved_sub_intents' =>
                                $resolvedMaterialSubIntentsV900,
                            'missing_sub_intents' =>
                                $missingMaterialSubIntentsV900,
                            'available' => $materialAvailableV900,
                            'locked' => $materialAvailableV900
                                && (bool) data_get(
                                    $result,
                                    'locked',
                                    true
                                ),
                            'missing_reason' => $materialAvailableV900
                                ? null
                                : data_get(
                                    $result,
                                    'missing_reason',
                                    'material_not_resolved_v500'
                                ),
                        ]
                    );

                    return $normalizedMaterialV900;
                }
NEW,
        'Material result top-level list replacement'
    );
}
writeSource('TMP_ROUTER', $router);
PHP_PATCHER

  export TMP_CYCLE="$TMP_ROOT/$CYCLE"
  export TMP_SHADOW="$TMP_ROOT/$SHADOW"
  export TMP_ROUTER="$TMP_ROOT/$ROUTER"
  php "$TMP_ROOT/phase335_patcher.php"

  for file in "${FILES[@]}"; do
    php -l "$TMP_ROOT/$file"
  done

  PATCH_WRITTEN=1
  for file in "${FILES[@]}"; do
    mkdir -p "$(dirname "$file")"
    mv "$TMP_ROOT/$file" "$file"
  done
else
  printf '%s\n' 'Phase 3.3.5 already installed; re-running mandatory verification.'
fi

for file in "${FILES[@]}"; do
  php -l "$file"
done

php artisan list --raw | grep -Fq 'customer-messaging:v900-cost-guard-smoke' || {
  printf '%s\n' 'ERROR: Cost guard smoke command không được Laravel discover.' >&2
  false
}

run_json_smoke() {
  command_name="$1"
  shift
  output_file="$(mktemp "${TMPDIR:-/tmp}/customer_messaging_phase335_smoke.XXXXXX")"
  set +e
  php artisan "$command_name" "$@" --json > "$output_file" 2>&1
  status=$?
  set -e
  if [ "$status" -ne 0 ]; then
    printf 'ERROR: Smoke failed: %s (status %s)\n' "$command_name" "$status" >&2
    cat "$output_file" >&2
    rm -f "$output_file"
    return 1
  fi
  php -r '
    $data = json_decode(file_get_contents($argv[1]), true);
    if (! is_array($data) || (($data["ok"] ?? false) !== true)) {
        fwrite(STDERR, "Smoke JSON not OK: " . $argv[2] . PHP_EOL);
        exit(1);
    }
  ' "$output_file" "$command_name" || {
    cat "$output_file" >&2
    rm -f "$output_file"
    return 1
  }
  if [ "$command_name" = 'customer-messaging:v900-cost-guard-smoke' ]; then
    printf '%s\n' '===== MANDATORY PHASE 3.3.5 COST GUARD SMOKE ====='
    cat "$output_file"
  fi
  rm -f "$output_file"
}

run_json_smoke customer-messaging:v900-cost-guard-smoke
run_json_smoke customer-messaging:v900-phase334-read-continuity-smoke
run_json_smoke customer-messaging:v900-phase333-contracts-smoke
run_json_smoke customer-messaging:v900-shadow-brain-smoke

php artisan optimize:clear

PATCH_WRITTEN=0
trap - EXIT INT TERM
rm -rf "$TMP_ROOT"

printf '%s\n' 'PHASE335_PATCH=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'LIVE_PROVIDER_KILL_SWITCH=DEFAULT_CLOSED'
printf '%s\n' 'SHADOW_LIMIT=2_CYCLES_PER_MINUTE'
printf '%s\n' 'MAX_GPT_CALLS_PER_CYCLE=3'
printf '%s\n' 'HTTP_PROVIDER_ERROR=AUTO_TRIP_LOCAL_KILL_FILE'
printf '%s\n' 'MATERIAL_LIST_CONTRACT=REPAIRED'
printf '%s\n' 'MIGRATION=NOT_RUN'
printf '%s\n' 'PROVIDER_CALLS_DURING_INSTALLER=0'
printf '%s\n' 'OUTBOUND=0'
printf '%s\n' 'DATABASE_BUSINESS_MUTATION=0'
printf '%s\n' 'WORKER_ACTIVATION=UNCHANGED'
