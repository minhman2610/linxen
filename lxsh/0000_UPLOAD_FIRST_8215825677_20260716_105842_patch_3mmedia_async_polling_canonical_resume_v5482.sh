#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME="patch_3mmedia_async_polling_canonical_resume_v5482"
CONTROLLER="app/Http/Controllers/ErpV2/Marketing/ResearchSetMarketingController.php"
JOB_SHOW="resources/views/erp_v2/marketing/media_workbench/job-show.blade.php"
RUNNER="resources/views/erp_v2/marketing/media_workbench/partials/three-mmedia-runner.blade.php"
STAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_${STAMP}"
TMP_PATCHER=""

if [ ! -f artisan ]; then
    printf '%s\n' 'ERROR: Hãy chạy patch từ Laravel root, nơi có file artisan.' >&2
    exit 1
fi

for REQUIRED_FILE in "$CONTROLLER" "$JOB_SHOW" "$RUNNER"; do
    if [ ! -f "$REQUIRED_FILE" ]; then
        printf 'ERROR: Thiếu file bắt buộc: %s\n' "$REQUIRED_FILE" >&2
        exit 1
    fi
done

mkdir -p "$BACKUP_ROOT/$(dirname "$CONTROLLER")"
mkdir -p "$BACKUP_ROOT/$(dirname "$JOB_SHOW")"
mkdir -p "$BACKUP_ROOT/$(dirname "$RUNNER")"

cp "$CONTROLLER" "$BACKUP_ROOT/$CONTROLLER"
cp "$JOB_SHOW" "$BACKUP_ROOT/$JOB_SHOW"
cp "$RUNNER" "$BACKUP_ROOT/$RUNNER"

cat > "$BACKUP_ROOT/manifest.json" <<MANIFEST
{
  "patch": "V548.2",
  "created_at": "$STAMP",
  "files": [
    "$CONTROLLER",
    "$JOB_SHOW",
    "$RUNNER"
  ]
}
MANIFEST

ROLLBACK_REQUIRED=1

rollback_v5482() {
    if [ "$ROLLBACK_REQUIRED" -ne 1 ]; then
        return
    fi

    printf '%s\n' 'ERROR: Patch V548.2 gặp lỗi bắt buộc. Đang rollback source...' >&2

    for FILE in "$CONTROLLER" "$JOB_SHOW" "$RUNNER"; do
        if [ -f "$BACKUP_ROOT/$FILE" ]; then
            cp "$BACKUP_ROOT/$FILE" "$FILE"
        fi
    done

    printf 'ROLLBACK_DONE_V5482 backup=%s\n' "$BACKUP_ROOT" >&2
}

cleanup_v5482() {
    if [ -n "$TMP_PATCHER" ] && [ -f "$TMP_PATCHER" ]; then
        rm -f "$TMP_PATCHER"
    fi
}

trap 'rollback_v5482; cleanup_v5482' ERR INT TERM

TMP_PATCHER="$(mktemp "${TMPDIR:-/tmp}/ai_patch_v5482.XXXXXX")"

cat > "$TMP_PATCHER" <<'PHP_PATCHER_V5482'
<?php

declare(strict_types=1);

$controllerPath = 'app/Http/Controllers/ErpV2/Marketing/ResearchSetMarketingController.php';
$jobShowPath = 'resources/views/erp_v2/marketing/media_workbench/job-show.blade.php';
$runnerPath = 'resources/views/erp_v2/marketing/media_workbench/partials/three-mmedia-runner.blade.php';

function atomicWriteV5482(string $path, string $contents): void
{
    $directory = dirname($path);

    if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        throw new RuntimeException('Không tạo được thư mục: ' . $directory);
    }

    $temporary = tempnam($directory, '.ai_patch_v5482_');

    if ($temporary === false) {
        throw new RuntimeException('Không tạo được file tạm trong: ' . $directory);
    }

    try {
        if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Không ghi được file tạm: ' . $temporary);
        }

        @chmod($temporary, 0664);

        if (! rename($temporary, $path)) {
            throw new RuntimeException('Không atomic replace được file: ' . $path);
        }
    } finally {
        if (is_file($temporary)) {
            @unlink($temporary);
        }
    }
}

function replaceExactlyOnceV5482(
    string $source,
    string $needle,
    string $replacement,
    string $label
): string {
    $count = substr_count($source, $needle);

    if ($count !== 1) {
        throw new RuntimeException(
            $label . ': cần đúng 1 anchor nhưng tìm thấy ' . $count . '.'
        );
    }

    return str_replace($needle, $replacement, $source);
}

$controller = file_get_contents($controllerPath);

if (! is_string($controller) || $controller === '') {
    throw new RuntimeException('Không đọc được controller.');
}

$controllerMarker = 'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_CONTROLLER';

if (! str_contains($controller, $controllerMarker)) {
    $controllerNeedle = <<<'CONTROLLER_NEEDLE_V5482'
            'fit_look_review_display_v38' => data_get($meta, 'fit_look_review_display_v38'),
            'execution_status' => data_get($meta, 'execution_status'),
CONTROLLER_NEEDLE_V5482;

    $controllerReplacement = <<<'CONTROLLER_REPLACEMENT_V5482'
            'fit_look_review_display_v38' => data_get($meta, 'fit_look_review_display_v38'),
            /* AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_CONTROLLER
             | Job detail dùng payload nhẹ V295 nhưng vẫn phải server-render đủ
             | active run truth để runner tự nối lại polling sau reload.
             */
            'active_3mmedia_run_v411' => array_filter([
                'run_id' => data_get($meta, 'active_3mmedia_run_v411.run_id'),
                'state' => data_get($meta, 'active_3mmedia_run_v411.state'),
                'queue' => data_get($meta, 'active_3mmedia_run_v411.queue'),
                'requested_at' => data_get($meta, 'active_3mmedia_run_v411.requested_at'),
                'queued_at' => data_get($meta, 'active_3mmedia_run_v411.queued_at'),
                'started_at' => data_get($meta, 'active_3mmedia_run_v411.started_at'),
                'finished_at' => data_get($meta, 'active_3mmedia_run_v411.finished_at'),
                'failed_at' => data_get($meta, 'active_3mmedia_run_v411.failed_at'),
                'blocked_at' => data_get($meta, 'active_3mmedia_run_v411.blocked_at'),
                'terminal_success' => data_get($meta, 'active_3mmedia_run_v411.terminal_success'),
                'terminal_attention' => data_get($meta, 'active_3mmedia_run_v411.terminal_attention'),
                'message' => Str::limit((string) data_get($meta, 'active_3mmedia_run_v411.message'), 500),
                'reason_code' => data_get($meta, 'active_3mmedia_run_v411.reason_code'),
            ], fn ($value) => $value !== null && $value !== ''),
            'last_3mmedia_ui_request' => array_filter([
                'run_id' => data_get($meta, 'last_3mmedia_ui_request.run_id'),
                'requested_at' => data_get($meta, 'last_3mmedia_ui_request.requested_at'),
                'queued_at' => data_get($meta, 'last_3mmedia_ui_request.queued_at'),
                'source' => data_get($meta, 'last_3mmedia_ui_request.source'),
            ], fn ($value) => $value !== null && $value !== ''),
            'execution_status' => data_get($meta, 'execution_status'),
CONTROLLER_REPLACEMENT_V5482;

    $controller = replaceExactlyOnceV5482(
        $controller,
        $controllerNeedle,
        $controllerReplacement,
        'Controller light-meta active-run contract'
    );

    atomicWriteV5482($controllerPath, $controller);
}

$jobShow = file_get_contents($jobShowPath);

if (! is_string($jobShow) || $jobShow === '') {
    throw new RuntimeException('Không đọc được job-show Blade.');
}

$jobShowMarker = 'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_JOB_SHOW';

if (! str_contains($jobShow, $jobShowMarker)) {
    $urlNeedle = <<<'JOB_SHOW_URL_NEEDLE_V5482'
    $threeMMediaRunUrlV207 = ($jobId > 0 && \Illuminate\Support\Facades\Route::has('erp_v2.marketing.media_workbench.jobs.run_3mmedia')) ? route('erp_v2.marketing.media_workbench.jobs.run_3mmedia', $jobId) : null;
    $legacyGenerateUrlV207 = ($jobId > 0 && \Illuminate\Support\Facades\Route::has('erp_v2.marketing.media_workbench.jobs.generate_ai')) ? route('erp_v2.marketing.media_workbench.jobs.generate_ai', $jobId) : null;
JOB_SHOW_URL_NEEDLE_V5482;

    $urlReplacement = <<<'JOB_SHOW_URL_REPLACEMENT_V5482'
    $threeMMediaRunUrlV207 = ($jobId > 0 && \Illuminate\Support\Facades\Route::has('erp_v2.marketing.media_workbench.jobs.run_3mmedia')) ? route('erp_v2.marketing.media_workbench.jobs.run_3mmedia', $jobId) : null;
    /* AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_JOB_SHOW
     | Status URL là server-render truth, không để JS đoán route từ DOM.
     */
    $threeMMediaStatusUrlV5482 = ($jobId > 0 && \Illuminate\Support\Facades\Route::has('erp_v2.marketing.media_workbench.jobs.3mmedia_status'))
        ? route('erp_v2.marketing.media_workbench.jobs.3mmedia_status', $jobId)
        : ($jobId > 0 ? url('/erp-v2/marketing/media-workbench/jobs/' . $jobId . '/3mmedia-status') : null);
    $legacyGenerateUrlV207 = ($jobId > 0 && \Illuminate\Support\Facades\Route::has('erp_v2.marketing.media_workbench.jobs.generate_ai')) ? route('erp_v2.marketing.media_workbench.jobs.generate_ai', $jobId) : null;
JOB_SHOW_URL_REPLACEMENT_V5482;

    $jobShow = replaceExactlyOnceV5482(
        $jobShow,
        $urlNeedle,
        $urlReplacement,
        'Job-show server-render status URL'
    );

    $formNeedle = <<<'JOB_SHOW_FORM_NEEDLE_V5482'
                          data-run-3mmedia-form-v214
                          data-3mmedia-polling-v214
                          data-mw-3mmedia-run-mode-v367=
JOB_SHOW_FORM_NEEDLE_V5482;

    $formReplacement = <<<'JOB_SHOW_FORM_REPLACEMENT_V5482'
                          data-run-3mmedia-form-v214
                          data-3mmedia-polling-v214
                          data-3mmedia-status-url="{{ $threeMMediaStatusUrlV5482 }}"
                          data-mw-3mmedia-run-mode-v367=
JOB_SHOW_FORM_REPLACEMENT_V5482;

    $formCount = substr_count($jobShow, $formNeedle);

    if ($formCount < 2) {
        throw new RuntimeException(
            'Job-show cần ít nhất 2 form run-mode; tìm thấy ' . $formCount . '.'
        );
    }

    $jobShow = str_replace($formNeedle, $formReplacement, $jobShow);

    atomicWriteV5482($jobShowPath, $jobShow);
}

$runner = file_get_contents($runnerPath);

if (! is_string($runner) || $runner === '') {
    throw new RuntimeException('Không đọc được three-mmedia-runner Blade.');
}

$runnerMarker = 'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_CANONICAL_V5482';

if (! str_contains($runner, $runnerMarker)) {
    $runnerHeaderNeedle = <<<'RUNNER_HEADER_NEEDLE_V5482'
{{-- AI_PATCH_3MMEDIA_LOADING_POLLING_V214_PARTIAL --}}
@once
RUNNER_HEADER_NEEDLE_V5482;

    $runnerHeaderReplacement = <<<'RUNNER_HEADER_REPLACEMENT_V5482'
{{-- AI_PATCH_3MMEDIA_LOADING_POLLING_V214_PARTIAL --}}
@php
    /* AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_SERVER_CONFIG
     | Render a durable resume contract only for a non-terminal active run.
     */
    $mw3mResumeJobV5482 = $job ?? null;
    $mw3mResumeJobIdV5482 = (int) data_get($mw3mResumeJobV5482, 'id', 0);
    $mw3mResumeMetaRawV5482 = data_get($mw3mResumeJobV5482, 'meta', []);

    if (is_string($mw3mResumeMetaRawV5482) && trim($mw3mResumeMetaRawV5482) !== '') {
        $mw3mResumeMetaDecodedV5482 = json_decode($mw3mResumeMetaRawV5482, true);
        $mw3mResumeMetaV5482 = is_array($mw3mResumeMetaDecodedV5482)
            ? $mw3mResumeMetaDecodedV5482
            : [];
    } elseif (is_object($mw3mResumeMetaRawV5482)) {
        $mw3mResumeMetaV5482 = (array) $mw3mResumeMetaRawV5482;
    } else {
        $mw3mResumeMetaV5482 = is_array($mw3mResumeMetaRawV5482)
            ? $mw3mResumeMetaRawV5482
            : [];
    }

    $mw3mActiveRunV5482 = (array) data_get(
        $mw3mResumeMetaV5482,
        'active_3mmedia_run_v411',
        []
    );
    $mw3mActiveRunIdV5482 = trim((string) data_get(
        $mw3mActiveRunV5482,
        'run_id',
        ''
    ));
    $mw3mActiveStateV5482 = \Illuminate\Support\Str::lower(
        trim((string) (
            data_get($mw3mActiveRunV5482, 'state')
            ?: data_get($mw3mResumeMetaV5482, 'execution_status')
        ))
    );
    $mw3mActiveTerminalAtV5482 = trim((string) (
        data_get($mw3mActiveRunV5482, 'finished_at')
        ?: data_get($mw3mActiveRunV5482, 'failed_at')
        ?: data_get($mw3mActiveRunV5482, 'blocked_at')
    ));
    $mw3mResumeStatesV5482 = [
        'queued',
        'queueing',
        'pending',
        'running',
        'claimed',
        'processing',
        'provider_processing',
        'fal_processing',
        'replicate_processing',
        '3mmedia_queued',
        '3mmedia_running',
        '3mmedia_processing',
        'generating',
        'queued_for_ai',
    ];
    $mw3mShouldResumeV5482 = $mw3mResumeJobIdV5482 > 0
        && $mw3mActiveRunIdV5482 !== ''
        && $mw3mActiveTerminalAtV5482 === ''
        && in_array($mw3mActiveStateV5482, $mw3mResumeStatesV5482, true);
    $mw3mResumeStatusUrlV5482 = $mw3mResumeJobIdV5482 > 0
        ? (
            \Illuminate\Support\Facades\Route::has(
                'erp_v2.marketing.media_workbench.jobs.3mmedia_status'
            )
                ? route(
                    'erp_v2.marketing.media_workbench.jobs.3mmedia_status',
                    $mw3mResumeJobIdV5482
                )
                : url(
                    '/erp-v2/marketing/media-workbench/jobs/'
                    . $mw3mResumeJobIdV5482
                    . '/3mmedia-status'
                )
        )
        : '';
@endphp

@if($mw3mShouldResumeV5482)
    <span
        hidden
        data-mw3m-resume-v5482
        data-job-id="{{ $mw3mResumeJobIdV5482 }}"
        data-run-id="{{ $mw3mActiveRunIdV5482 }}"
        data-run-state="{{ $mw3mActiveStateV5482 }}"
        data-status-url="{{ $mw3mResumeStatusUrlV5482 }}"
    ></span>
@endif

@once
RUNNER_HEADER_REPLACEMENT_V5482;

    $runner = replaceExactlyOnceV5482(
        $runner,
        $runnerHeaderNeedle,
        $runnerHeaderReplacement,
        'Runner server resume config'
    );

    $pollBlockStart = strpos(
        $runner,
        '    /* AI_PATCH_MW_3MMEDIA_POLLING_SINGLE_FLIGHT_V540 */'
    );
    $pollBlockEnd = strpos(
        $runner,
        '    function reloadSoon(ms){',
        $pollBlockStart === false ? 0 : $pollBlockStart
    );

    if ($pollBlockStart === false || $pollBlockEnd === false || $pollBlockEnd <= $pollBlockStart) {
        throw new RuntimeException(
            'Không xác định được canonical polling block V540/V548 để rebuild.'
        );
    }

    $canonicalPollingBlock = <<<'RUNNER_POLLING_BLOCK_V5482'
    /* AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_CANONICAL_V5482
     | Rebuild one compatible polling engine.
     |
     | Root cause V548:
     | - pollOnce() required pollStopped=false and the current pollToken;
     | - replacement startPolling() never cleared pollStopped;
     | - it set pollInFlight=true before calling pollOnce();
     | - it called pollOnce(statusUrl) without token.
     | Therefore every tick returned before fetch() and Nginx saw no status GET.
     */
    function schedulePolling(statusUrl, delay, token){
        if (
            !statusUrl
            || state.pollStopped
            || state.pollPaused
            || token !== state.pollToken
        ) return;

        if (state.timer) {
            window.clearTimeout(state.timer);
            state.timer=null;
        }

        state.timer=window.setTimeout(async function(){
            state.timer=null;

            if (
                state.pollStopped
                || state.pollPaused
                || token !== state.pollToken
            ) return;

            try {
                var data=await pollOnce(statusUrl, token);

                if (
                    state.pollStopped
                    || state.pollPaused
                    || token !== state.pollToken
                ) return;

                state.pollFailureCount=0;

                var progress=data && data.progress && typeof data.progress==='object'
                    ? data.progress
                    : {};

                if (progress.terminal) return;

                var nextDelay=Number(progress.poll_after_ms || 2500);
                nextDelay=Math.max(1800, Math.min(15000, nextDelay));

                schedulePolling(statusUrl, nextDelay, token);
            } catch(error) {
                if (
                    state.pollStopped
                    || state.pollPaused
                    || token !== state.pollToken
                ) return;

                state.pollFailureCount=Math.min(
                    5,
                    Number(state.pollFailureCount || 0) + 1
                );

                var message=(error && error.message)
                    ? error.message
                    : String(error || 'Polling lỗi');

                addLog('Polling lỗi: ' + message);

                var backoff=Math.min(
                    15000,
                    2500 * Math.pow(
                        2,
                        Math.max(0, state.pollFailureCount - 1)
                    )
                );

                schedulePolling(statusUrl, backoff, token);
            }
        }, Math.max(0, Number(delay || 0)));
    }

    async function pollOnce(statusUrl, token){
        token=token == null ? state.pollToken : token;

        if (
            !statusUrl
            || state.pollStopped
            || state.pollPaused
            || token !== state.pollToken
        ) return null;

        if (state.pollInFlight) return null;

        state.pollInFlight=true;

        var controller=typeof AbortController !== 'undefined'
            ? new AbortController()
            : null;

        state.pollController=controller;

        try {
            var options={
                method:'GET',
                credentials:'same-origin',
                headers:{
                    'Accept':'application/json',
                    'X-Requested-With':'XMLHttpRequest'
                }
            };

            if (controller) options.signal=controller.signal;

            var res=await fetch(statusUrl, options);
            var data=await readJson(res);

            if (!res.ok || data.ok===false) {
                throw new Error(data.message || ('HTTP ' + res.status));
            }

            var progress=data && data.progress && typeof data.progress==='object'
                ? data.progress
                : {};
            var outputDelta=Number(
                progress.output_delta != null
                    ? progress.output_delta
                    : (data.output_delta || 0)
            );
            var madeNewOutput=!!progress.made_new_output
                || !!data.made_new_output;
            var newMediaIds=progress.new_media_ids
                || data.new_media_ids
                || [];
            var persistedOutputCount=Number(
                progress.persisted_new_output_count != null
                    ? progress.persisted_new_output_count
                    : (
                        (data.counts && data.counts.persisted_new_output_count)
                        || data.persisted_new_output_count
                        || 0
                    )
            );
            var hasPersistedOutput=persistedOutputCount > 0
                || (
                    Array.isArray(newMediaIds)
                    && newMediaIds.length > 0
                    && madeNewOutput
                );
            var terminalSuccess=!!progress.terminal_success
                || (
                    !!progress.completed
                    && (madeNewOutput || hasPersistedOutput)
                    && (outputDelta > 0 || hasPersistedOutput)
                );
            var terminalAttention=!!progress.terminal_attention
                || (!!progress.terminal && !terminalSuccess);

            updateOverlay(
                data,
                progress.failed
                    ? 'error'
                    : (terminalSuccess ? 'success' : 'info')
            );

            if (progress.terminal) {
                stopPolling(false);
                state.posting=false;

                if (terminalSuccess) {
                    addLog(
                        'Đã xác nhận output thật: delta='
                        + outputDelta
                        + ', persisted='
                        + persistedOutputCount
                        + '; reload để hiển thị ảnh mới.'
                    );
                    reloadSoon(1700);
                } else if (terminalAttention) {
                    addLog(
                        'Lượt chạy đã dừng nhưng không có output mới. '
                        + 'Giữ modal để xem nguyên nhân.'
                    );

                    if (typeof state.restore === 'function') {
                        state.restore();
                    }

                    state.restore=null;
                }
            }

            return data;
        } catch(error) {
            if (error && error.name === 'AbortError') return null;
            throw error;
        } finally {
            if (state.pollController === controller) {
                state.pollController=null;
            }

            state.pollInFlight=false;
        }
    }

    function stopPolling(abortRequest){
        if (abortRequest === undefined) abortRequest=true;

        state.pollStopped=true;
        state.polling=false;
        state.pollPaused=false;
        state.pollToken=Number(state.pollToken || 0) + 1;

        if (state.timer) {
            window.clearTimeout(state.timer);
            window.clearInterval(state.timer);
            state.timer=null;
        }

        if (
            abortRequest
            && state.pollController
            && typeof state.pollController.abort === 'function'
        ) {
            try {
                state.pollController.abort();
            } catch(ignoreAbortV5482) {}
        }

        state.pollController=null;
        state.pollInFlight=false;
        state.pollUrl='';
    }

    function startPolling(statusUrl){
        statusUrl=text(statusUrl);

        if (!statusUrl) return;

        stopPolling(true);

        state.pollUrl=statusUrl;
        state.pollStopped=false;
        state.pollPaused=!!document.hidden;
        state.polling=true;
        state.pollFailureCount=0;

        var token=state.pollToken;

        addLog('GET ' + statusUrl);

        if (!state.pollPaused) {
            schedulePolling(statusUrl, 0, token);
        }
    }

RUNNER_POLLING_BLOCK_V5482;

    $runner = substr($runner, 0, $pollBlockStart)
        . $canonicalPollingBlock
        . substr($runner, $pollBlockEnd);

    $resumeNeedle = <<<'RUNNER_RESUME_NEEDLE_V5482'
    /* AI_PATCH_MW_3MMEDIA_POLLING_LIFECYCLE_V540 */
RUNNER_RESUME_NEEDLE_V5482;

    $resumeReplacement = <<<'RUNNER_RESUME_REPLACEMENT_V5482'
    /* AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_BOOT */
    function withRunIdV5482(statusUrl, runId){
        statusUrl=text(statusUrl);
        runId=text(runId);

        if (!statusUrl) return '';

        try {
            var urlV5482=new URL(statusUrl, window.location.origin);

            if (runId) {
                urlV5482.searchParams.set('run_id', runId);
            }

            return urlV5482.toString();
        } catch(errorV5482) {
            if (!runId) return statusUrl;

            return statusUrl
                + (statusUrl.indexOf('?') === -1 ? '?' : '&')
                + 'run_id='
                + encodeURIComponent(runId);
        }
    }

    function resumeServerRenderedRunV5482(){
        if (!state.pollStopped || state.posting) return;

        var nodes=[].slice.call(
            document.querySelectorAll('[data-mw3m-resume-v5482]')
        );

        var node=nodes.find(function(item){
            return text(item.getAttribute('data-status-url'))
                && text(item.getAttribute('data-run-id'));
        });

        if (!node) return;

        var runId=text(node.getAttribute('data-run-id'));
        var runState=text(node.getAttribute('data-run-state'));
        var statusUrl=withRunIdV5482(
            node.getAttribute('data-status-url'),
            runId
        );

        if (!statusUrl) return;

        updateOverlay({
            progress:{
                stage:'queued',
                label:'Tiếp tục theo dõi lượt đang chạy',
                message:'Đang nối lại polling cho run_id ' + runId + '.',
                percent:18,
                running:true,
                terminal:false,
                run_id:runId,
                active_run_state:runState
            },
            status:{
                execution_status:runState,
                run_id:runId
            }
        }, 'info');

        addLog(
            'Resume run_id ' + runId
            + (runState ? (' · state=' + runState) : '')
        );

        startPolling(statusUrl);
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            resumeServerRenderedRunV5482,
            {once:true}
        );
    } else {
        window.setTimeout(resumeServerRenderedRunV5482, 0);
    }

    /* AI_PATCH_MW_3MMEDIA_POLLING_LIFECYCLE_V540 */
RUNNER_RESUME_REPLACEMENT_V5482;

    $runner = replaceExactlyOnceV5482(
        $runner,
        $resumeNeedle,
        $resumeReplacement,
        'Runner resume lifecycle bootstrap'
    );

    atomicWriteV5482($runnerPath, $runner);
}

foreach ([
    $controllerPath => [
        'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_CONTROLLER',
        "'active_3mmedia_run_v411' => array_filter",
        "'last_3mmedia_ui_request' => array_filter",
    ],
    $jobShowPath => [
        'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_JOB_SHOW',
        '$threeMMediaStatusUrlV5482',
        'data-3mmedia-status-url="{{ $threeMMediaStatusUrlV5482 }}"',
    ],
    $runnerPath => [
        'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_CANONICAL_V5482',
        'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_SERVER_CONFIG',
        'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_BOOT',
        'state.pollStopped=false;',
        'schedulePolling(statusUrl, 0, token);',
        'data-mw3m-resume-v5482',
    ],
] as $path => $needles) {
    $contents = file_get_contents($path);

    if (! is_string($contents)) {
        throw new RuntimeException('Không đọc được file sau patch: ' . $path);
    }

    foreach ($needles as $needle) {
        if (! str_contains($contents, $needle)) {
            throw new RuntimeException(
                'Thiếu contract marker "' . $needle . '" trong ' . $path
            );
        }
    }
}

$runnerAfter = file_get_contents($runnerPath);

if (
    substr_count((string) $runnerAfter, 'function schedulePolling(') !== 1
    || substr_count((string) $runnerAfter, 'function pollOnce(') !== 1
    || substr_count((string) $runnerAfter, 'function startPolling(') !== 1
    || substr_count((string) $runnerAfter, 'function stopPolling(') !== 1
) {
    throw new RuntimeException(
        'Runner không còn đúng một polling implementation canonical.'
    );
}

if (str_contains((string) $runnerAfter, 'state.pollInFlight=true;' . PHP_EOL . PHP_EOL . '            try{' . PHP_EOL . '                var dataV548=await pollOnce(statusUrl);')) {
    throw new RuntimeException(
        'Runner vẫn còn V548 nested tick đặt pollInFlight trước pollOnce.'
    );
}

echo "SOURCE_UPDATED_V5482\n";
PHP_PATCHER_V5482

php -l "$TMP_PATCHER"
php "$TMP_PATCHER"

php -l "$CONTROLLER"

php <<'PHP_BLADE_CHECK_V5482'
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$compiler = app('blade.compiler');

foreach ([
    __DIR__ . '/resources/views/erp_v2/marketing/media_workbench/job-show.blade.php',
    __DIR__ . '/resources/views/erp_v2/marketing/media_workbench/partials/three-mmedia-runner.blade.php',
] as $blade) {
    if (! is_file($blade)) {
        fwrite(STDERR, "Thiếu Blade bắt buộc: {$blade}\n");
        exit(1);
    }

    $compiler->compile($blade);
    $compiled = $compiler->getCompiledPath($blade);

    if (! is_file($compiled)) {
        fwrite(STDERR, "Blade compiler chưa tạo file compiled: {$blade}\n");
        exit(1);
    }
}

echo "BLADE_COMPILE_V5482=PASS\n";
PHP_BLADE_CHECK_V5482

grep -q 'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_CONTROLLER' "$CONTROLLER"
grep -q 'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_JOB_SHOW' "$JOB_SHOW"
grep -q 'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_CANONICAL_V5482' "$RUNNER"
grep -q 'AI_PATCH_MW_3MMEDIA_ASYNC_POLLING_RESUME_V5482_BOOT' "$RUNNER"

SCHEDULE_COUNT="$(grep -c 'function schedulePolling(' "$RUNNER")"
POLL_ONCE_COUNT="$(grep -c 'function pollOnce(' "$RUNNER")"
START_COUNT="$(grep -c 'function startPolling(' "$RUNNER")"
STOP_COUNT="$(grep -c 'function stopPolling(' "$RUNNER")"

if [ "$SCHEDULE_COUNT" -ne 1 ] \
    || [ "$POLL_ONCE_COUNT" -ne 1 ] \
    || [ "$START_COUNT" -ne 1 ] \
    || [ "$STOP_COUNT" -ne 1 ]; then
    printf '%s\n' 'ERROR: Polling runner không còn đúng một implementation canonical.' >&2
    exit 1
fi

php artisan view:clear

if ! php artisan optimize:clear; then
    printf '%s\n' 'WARNING: optimize:clear không hoàn tất; source V548.2 vẫn giữ nguyên vì check bắt buộc đã pass.' >&2
fi

ROLLBACK_REQUIRED=0
trap - ERR INT TERM
cleanup_v5482

printf '%s\n' 'SOURCE_CONTRACT_CHECK_V5482=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'PATCH_DONE_V5482'
printf '%s\n' 'RESTART_WORKER: systemctl restart 3mg-3mmedia-worker.service'
