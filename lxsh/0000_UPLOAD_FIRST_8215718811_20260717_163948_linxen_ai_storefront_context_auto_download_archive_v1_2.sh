#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='linxen_ai_storefront_context_auto_download_archive_v1_2'
SOURCE_FILE='app/Console/Commands/Tools/AiStorefrontContextCommand.php'
MARKER='AI_PATCH_AI_STOREFRONT_CONTEXT_AUTO_DOWNLOAD_ARCHIVE_V1'

BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_$(date +%Y%m%d_%H%M%S)"
MANIFEST="${BACKUP_ROOT}/manifest.tsv"
PATCH_WRITTEN=0

rollback() {
    STATUS=$?

    if [ "$PATCH_WRITTEN" -eq 1 ] && [ -f "$MANIFEST" ]; then
        printf '%s\n' \
          'Có lỗi bắt buộc. Đang rollback AI Storefront Context command...' \
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
    printf 'ERROR: Thiếu source command: %s\n' \
      "$SOURCE_FILE" >&2
    exit 1
}

mkdir -p "$BACKUP_ROOT/$(dirname "$SOURCE_FILE")"
cp -p "$SOURCE_FILE" "$BACKUP_ROOT/$SOURCE_FILE"
printf 'existing\t%s\n' "$SOURCE_FILE" > "$MANIFEST"
PATCH_WRITTEN=1

export LINXEN_CONTEXT_COMMAND_FILE="$SOURCE_FILE"
export LINXEN_CONTEXT_DOWNLOAD_MARKER="$MARKER"

php <<'PHP'
<?php

$path = getenv('LINXEN_CONTEXT_COMMAND_FILE');
$marker = getenv('LINXEN_CONTEXT_DOWNLOAD_MARKER');

if (
    ! is_string($path)
    || ! is_file($path)
    || ! is_string($marker)
    || $marker === ''
) {
    fwrite(STDERR, "ERROR: Source path/marker không hợp lệ.\n");
    exit(1);
}

$source = file_get_contents($path);

if (! is_string($source)) {
    fwrite(STDERR, "ERROR: Không đọc được source command.\n");
    exit(1);
}

if (substr_count($source, $marker) > 1) {
    fwrite(
        STDERR,
        "ERROR: Auto-download marker xuất hiện nhiều hơn một lần.\n"
    );
    exit(1);
}

/*
 * Add compatible command options beside the existing download-dir option.
 * The command already exposes --download-dir; keep that contract and add
 * an automatic-download opt-out plus an explicit public base URL override.
 */
$needsNoDownload = ! str_contains(
    $source,
    '{--no-download '
);
$needsBaseUrl = ! str_contains(
    $source,
    '{--base-url='
);

if ($needsNoDownload || $needsBaseUrl) {
    $downloadDirPattern = '/^([ \t]*)\{--download-dir=([^}\r\n]+)\}[ \t]*$/m';

    if (
        preg_match(
            $downloadDirPattern,
            $source,
            $optionMatch
        ) !== 1
    ) {
        fwrite(
            STDERR,
            "ERROR: Không tìm thấy signature option --download-dir.\n"
        );
        exit(1);
    }

    $indent = (string) $optionMatch[1];
    $oldLine = (string) $optionMatch[0];
    $newLines = $oldLine;

    if ($needsNoDownload) {
        $newLines .= "\n"
            . $indent
            . '{--no-download : Không tạo archive/link tải public}';
    }

    if ($needsBaseUrl) {
        $newLines .= "\n"
            . $indent
            . '{--base-url= : Public base URL; mặc định APP_URL hoặc https://linxen.vn}';
    }

    $source = substr_replace(
        $source,
        $newLines,
        (int) strpos($source, $oldLine),
        strlen($oldLine)
    );
}

foreach ([
    '{--no-download',
    '{--base-url=',
] as $requiredOption) {
    if (! str_contains($source, $requiredOption)) {
        fwrite(
            STDERR,
            "ERROR: Không thêm được signature option: "
                . $requiredOption
                . "\n"
        );
        exit(1);
    }
}

/*
 * Insert automatic archive publishing immediately before the successful
 * return in handle(), after the command has written all context artifacts.
 */
if (! str_contains($source, $marker)) {
    $readyPosition = strpos(
        $source,
        'AI Storefront Context ready'
    );

    if ($readyPosition === false) {
        fwrite(
            STDERR,
            "ERROR: Không tìm thấy output anchor AI Storefront Context ready.\n"
        );
        exit(1);
    }

    $returnPosition = strpos(
        $source,
        'return self::SUCCESS;',
        $readyPosition
    );

    if ($returnPosition === false) {
        fwrite(
            STDERR,
            "ERROR: Không tìm thấy successful return sau output anchor.\n"
        );
        exit(1);
    }

    $callBlock = <<<'CALL'
        /* AI_PATCH_AI_STOREFRONT_CONTEXT_AUTO_DOWNLOAD_ARCHIVE_V1 */
        if (! (bool) $this->option('no-download')) {
            $download = $this->publishContextArchiveV1(
                $outputDir
            );

            $this->newLine();
            $this->info('AI Storefront Context download ready');
            $this->line(
                'DOWNLOAD_URL='
                . (string) data_get(
                    $download,
                    'url'
                )
            );
            $this->line(
                'DOWNLOAD_PATH='
                . (string) data_get(
                    $download,
                    'path'
                )
            );
            $this->line(
                'SHA256='
                . (string) data_get(
                    $download,
                    'sha256'
                )
            );
            $this->line(
                'SIZE_BYTES='
                . (int) data_get(
                    $download,
                    'size_bytes',
                    0
                )
            );
            $this->line(
                'DELETE_COMMAND='
                . (string) data_get(
                    $download,
                    'delete_command'
                )
            );
            $this->warn(
                'Archive chứa source nội bộ và đang public; '
                . 'hãy chạy DELETE_COMMAND sau khi tải.'
            );
            $this->line(
                'AI_STOREFRONT_CONTEXT_DOWNLOAD=PASS'
            );
        } else {
            $this->line(
                'AI_STOREFRONT_CONTEXT_DOWNLOAD=SKIPPED'
            );
        }

CALL;

    $source = substr_replace(
        $source,
        $callBlock,
        $returnPosition,
        0
    );

    /*
     * Insert a private helper before the final class brace.
     */
    $classEnd = strrpos($source, '}');

    if ($classEnd === false) {
        fwrite(
            STDERR,
            "ERROR: Không tìm thấy closing brace của command class.\n"
        );
        exit(1);
    }

    $helper = <<<'HELPER'

    /**
     * AI_PATCH_AI_STOREFRONT_CONTEXT_AUTO_DOWNLOAD_ARCHIVE_V1
     *
     * Publish the complete generated context directory as one tar.gz archive.
     * This intentionally mirrors the ERP context workflow: a random public
     * filename is printed together with SHA256 and an explicit delete command.
     */
    private function publishContextArchiveV1(
        string $outputDir
    ): array {
        $contextFile = rtrim(
            $outputDir,
            DIRECTORY_SEPARATOR
        ) . DIRECTORY_SEPARATOR . 'context.md';

        if (
            ! is_dir($outputDir)
            || ! is_file($contextFile)
            || filesize($contextFile) < 1
        ) {
            throw new \RuntimeException(
                'Context directory is incomplete: '
                . $outputDir
            );
        }

        $downloadDir = trim(
            (string) (
                $this->option('download-dir')
                ?: 'ai-context'
            ),
            "/\\"
        );

        if (
            $downloadDir === ''
            || str_contains($downloadDir, '..')
            || preg_match(
                '/^[A-Za-z0-9._\/-]+$/',
                $downloadDir
            ) !== 1
        ) {
            throw new \RuntimeException(
                'Invalid public download directory.'
            );
        }

        $publicDir = public_path($downloadDir);

        if (
            ! is_dir($publicDir)
            && ! mkdir(
                $publicDir,
                0775,
                true
            )
            && ! is_dir($publicDir)
        ) {
            throw new \RuntimeException(
                'Unable to create public download directory: '
                . $publicDir
            );
        }

        $site = trim(
            (string) $this->option('site')
        );
        $project = trim(
            (string) (
                $this->option('project-id')
                ?: $this->option('phase')
                ?: $this->option('mode')
                ?: 'context'
            )
        );

        $slug = \Illuminate\Support\Str::slug(
            trim($site . '-' . $project)
        );

        if ($slug === '') {
            $slug = 'storefront-context';
        }

        $slug = substr($slug, 0, 100);
        $timestamp = now()->format(
            'Ymd_His'
        );
        $reverseSortKey = max(
            0,
            9999999999 - now()->timestamp
        );
        $random = bin2hex(
            random_bytes(4)
        );
        $filename = sprintf(
            '0000_UPLOAD_FIRST_%010d_%s_ai_storefront_context_%s_%s.tar.gz',
            $reverseSortKey,
            $timestamp,
            $slug,
            $random
        );
        $archivePath = $publicDir
            . DIRECTORY_SEPARATOR
            . $filename;

        $process = new \Symfony\Component\Process\Process([
            'tar',
            '-C',
            dirname($outputDir),
            '-czf',
            $archivePath,
            basename($outputDir),
        ]);

        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            @unlink($archivePath);

            throw new \RuntimeException(
                'Unable to create storefront context archive: '
                . trim(
                    $process->getErrorOutput()
                    ?: $process->getOutput()
                )
            );
        }

        if (
            ! is_file($archivePath)
            || filesize($archivePath) < 1
        ) {
            throw new \RuntimeException(
                'Storefront context archive is empty.'
            );
        }

        @chmod($archivePath, 0644);

        $sha256 = hash_file(
            'sha256',
            $archivePath
        );

        if (! is_string($sha256) || $sha256 === '') {
            @unlink($archivePath);

            throw new \RuntimeException(
                'Unable to calculate archive SHA256.'
            );
        }

        $baseUrl = rtrim(
            trim(
                (string) $this->option(
                    'base-url'
                )
            ),
            '/'
        );

        if ($baseUrl === '') {
            $baseUrl = rtrim(
                (string) config(
                    'app.url',
                    ''
                ),
                '/'
            );
        }

        if (
            $baseUrl === ''
            || str_contains(
                $baseUrl,
                'localhost'
            )
            || str_contains(
                $baseUrl,
                '127.0.0.1'
            )
        ) {
            $baseUrl = $site === 'linxen'
                ? 'https://linxen.vn'
                : '';
        }

        $relativePath = trim(
            $downloadDir . '/' . $filename,
            '/'
        );
        $url = $baseUrl !== ''
            ? $baseUrl . '/' . $relativePath
            : '/' . $relativePath;

        return [
            'url' => $url,
            'path' => $archivePath,
            'sha256' => $sha256,
            'size_bytes' => (int) filesize(
                $archivePath
            ),
            'delete_command' => 'rm -f '
                . escapeshellarg(
                    $archivePath
                ),
        ];
    }
HELPER;

    $source = substr_replace(
        $source,
        $helper . "\n",
        $classEnd,
        0
    );
}

if (
    substr_count($source, $marker) !== 2
) {
    fwrite(
        STDERR,
        "ERROR: Expected exactly two auto-download markers "
            . "(call + helper).\n"
    );
    exit(1);
}

foreach ([
    '{--no-download',
    '{--base-url=',
    'private function publishContextArchiveV1(',
    'AI_STOREFRONT_CONTEXT_DOWNLOAD=PASS',
    "'tar'",
    "'DELETE_COMMAND='",
] as $required) {
    if (! str_contains($source, $required)) {
        fwrite(
            STDERR,
            "ERROR: Missing generated contract: {$required}\n"
        );
        exit(1);
    }
}

$written = file_put_contents($path, $source);

if (
    $written === false
    || $written !== strlen($source)
) {
    fwrite(
        STDERR,
        "ERROR: Không ghi đầy đủ source command.\n"
    );
    exit(1);
}

echo "AI_STOREFRONT_CONTEXT_AUTO_DOWNLOAD_SOURCE=APPLIED\n";
PHP

php -l "$SOURCE_FILE"

HELP_OUTPUT="$(
    env \
      CACHE_STORE=file \
      SESSION_DRIVER=file \
      php artisan \
        help \
        tools:ai-storefront-context
)"

printf '%s\n' "$HELP_OUTPUT" \
  | grep -Fq -- '--download-dir'

printf '%s\n' "$HELP_OUTPUT" \
  | grep -Fq -- '--no-download'

printf '%s\n' "$HELP_OUTPUT" \
  | grep -Fq -- '--base-url'

grep -Fq -- \
  'private function publishContextArchiveV1(' \
  "$SOURCE_FILE"

grep -Fq -- \
  'AI_STOREFRONT_CONTEXT_DOWNLOAD=PASS' \
  "$SOURCE_FILE"

grep -Fq -- \
  '0000_UPLOAD_FIRST_%010d_%s_ai_storefront_context_' \
  "$SOURCE_FILE"

printf '%s\n' \
  'AI_STOREFRONT_CONTEXT_COMMAND_CONTRACT=PASS'

trap - ERR

set +e

if [ "$(id -u)" -eq 0 ] \
    && command -v sudo >/dev/null 2>&1 \
    && id www-data >/dev/null 2>&1
then
    sudo -u www-data env \
      HOME="$(pwd)" \
      CACHE_STORE=file \
      SESSION_DRIVER=file \
      php artisan optimize:clear
    CLEAR_STATUS=$?
else
    env \
      CACHE_STORE=file \
      SESSION_DRIVER=file \
      php artisan optimize:clear
    CLEAR_STATUS=$?
fi

set -e

if [ "$CLEAR_STATUS" -eq 0 ]; then
    printf '%s\n' 'OPTIMIZE_CLEAR=PASS'
else
    printf 'OPTIMIZE_CLEAR=WARNING_EXIT_%s\n' \
      "$CLEAR_STATUS"
fi

printf '%s\n' \
  'LINXEN_AI_STOREFRONT_CONTEXT_AUTO_DOWNLOAD_PATCH_V1_2=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'OPTION_DESCRIPTION_MATCH=NAME_BASED_NOT_TEXT_BASED'
printf '%s\n' 'DEFAULT_DOWNLOAD=ENABLED'
printf '%s\n' 'DOWNLOAD_FORMAT=TAR_GZ_FULL_CONTEXT_DIRECTORY'
printf '%s\n' 'PUBLIC_DOWNLOAD_DIR=OPTION_DOWNLOAD_DIR_DEFAULT_AI_CONTEXT'
printf '%s\n' 'OPT_OUT=--no-download'
printf '%s\n' 'MIGRATION=NONE'
printf '%s\n' 'DB_MUTATION=NONE'
printf '%s\n' 'PROVIDER_CALL=NONE'
printf '%s\n' 'CONTEXT_GENERATION_DURING_PATCH=NONE'
printf '%s\n' 'NGINX_RELOAD=NONE'
printf '%s\n' 'PHP_FPM_RESTART=NONE'
