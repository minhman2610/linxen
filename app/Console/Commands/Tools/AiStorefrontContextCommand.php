<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;
use ZipArchive;

class AiStorefrontContextCommand extends Command
{
    protected $signature = 'tools:ai-storefront-context
        {--mode=audit-v1 : audit-v1|erp-contract|phase|release-audit}
        {--site=linxen : Site code}
        {--project-id= : Stable project identifier, e.g. linxen-v1 or 3mg-erp}
        {--phase= : shop-core|checkout|discover|tracking|seo|release}
        {--url=* : Public URLs to include in the audit}
        {--route-prefix=* : Route prefixes to prioritize}
        {--scan=* : routes,pages,views,assets,api,commerce,tracking,seo,auth,deploy,tests}
        {--refs=* : Exact files to include, repeatable or comma-separated}
        {--include-dir=* : Additional files/directories to scan}
        {--exclude-dir=* : Additional directories to skip}
        {--tables=* : DB tables for schema/sample}
        {--sample-where=* : table:column=value filters for DB samples}
        {--sample-columns=* : table:column1|column2 allowlist for DB samples}
        {--max-scan-files=6000 : Maximum text files scanned}
        {--max-files=220 : Maximum source files embedded in context.md}
        {--max-file-kb=240 : Maximum bytes embedded per source file}
        {--max-context-mb=32 : Maximum context.md size}
        {--max-matches=250 : Maximum matches per scanner}
        {--log-lines=0 : Tail Laravel log lines, 0 disables logs}
        {--output= : Output directory; relative paths resolve from Laravel root}
        {--download : Copy a zip/context file to public for download}
        {--no-download : Disable public copy even when --download is present}
        {--download-dir=ai-context : Public download directory}
        {--base-url= : Public base URL, defaults to config(app.url)}
        {--preview : Print collection plan without writing files}
        {--no-db : Skip all DB schema/sample access}
        {--include-pii : Do not mask common PII fields in DB samples}';

    protected $description = 'Audit a Laravel storefront/ERP source and export architecture, route, API, commerce, tracking and migration context for AI.';

    private const MODES = ['audit-v1', 'erp-contract', 'phase', 'release-audit'];

    private array $warnings = [];
    private array $fileCache = [];

    public function handle(): int
    {
        $mode = strtolower(trim((string) $this->option('mode')));
        $site = strtolower(trim((string) $this->option('site')));
        $phase = strtolower(trim((string) $this->option('phase')));

        if (! in_array($mode, self::MODES, true)) {
            $this->error('Invalid --mode. Allowed: ' . implode(', ', self::MODES));
            return self::FAILURE;
        }

        if ($site === '' || preg_match('/^[a-z0-9][a-z0-9_-]{1,49}$/', $site) !== 1) {
            $this->error('Invalid --site. Use lowercase letters, numbers, underscore or dash.');
            return self::FAILURE;
        }

        if ($mode === 'phase' && $phase === '') {
            $this->error('--phase is required when --mode=phase.');
            return self::FAILURE;
        }

        $scan = $this->normalizedScanPlan($mode, $phase);
        $refs = $this->normalizePaths($this->optionList('refs'));
        $includeDirs = $this->normalizePaths($this->optionList('include-dir'));
        $excludeDirs = $this->normalizePaths($this->optionList('exclude-dir'));
        $routePrefixes = $this->optionList('route-prefix');
        $urls = $this->optionList('url');
        $maxScanFiles = max(100, min(20000, (int) $this->option('max-scan-files')));
        $maxSourceFiles = max(20, min(1000, (int) $this->option('max-files')));
        $maxFileBytes = max(32, (int) $this->option('max-file-kb')) * 1024;
        $maxContextBytes = max(1, (int) $this->option('max-context-mb')) * 1024 * 1024;
        $maxMatches = max(20, min(5000, (int) $this->option('max-matches')));

        $allFiles = $this->collectProjectFiles($scan, $refs, $includeDirs, $excludeDirs, $maxScanFiles);
        $sourceFiles = $this->selectSourceFiles($allFiles, $refs, $mode, $phase, $routePrefixes, $maxSourceFiles);

        $routeInventory = in_array('routes', $scan, true)
            ? $this->routeInventory($allFiles, $routePrefixes)
            : [];

        $pageInventory = in_array('pages', $scan, true)
            ? $this->pageInventory($routeInventory)
            : [];

        $apiIntegrations = in_array('api', $scan, true)
            ? $this->scanApiIntegrations($allFiles, $maxMatches)
            : [];

        $tracking = in_array('tracking', $scan, true)
            ? $this->scanTracking($allFiles, $maxMatches)
            : [];

        $commerce = in_array('commerce', $scan, true)
            ? $this->scanCommerce($allFiles)
            : [];

        $seo = in_array('seo', $scan, true)
            ? $this->scanSeo($allFiles, $maxMatches)
            : [];

        $dependencyMap = $this->dependencyMap($sourceFiles);
        $findings = $this->buildFindings(
            $mode,
            $allFiles,
            $routeInventory,
            $apiIntegrations,
            $tracking,
            $commerce,
            $seo
        );
        $urlMigration = $this->buildUrlMigration($pageInventory, $urls);
        $tables = (bool) $this->option('no-db')
            ? []
            : $this->databaseContext($this->optionList('tables'));

        $project = $this->projectMetadata($mode, $site, $phase, $urls);

        $preview = [
            'project' => $project,
            'scan' => $scan,
            'all_files' => count($allFiles),
            'embedded_source_files' => count($sourceFiles),
            'routes' => count($routeInventory),
            'pages' => count($pageInventory),
            'api_integrations' => count($apiIntegrations),
            'tracking_matches' => count($tracking),
            'findings' => count($findings),
            'tables' => array_keys($tables),
            'warnings' => $this->warnings,
        ];

        if ((bool) $this->option('preview')) {
            $this->line(json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $outputDir = $this->resolveOutputDirectory($mode, $site, $phase);
        if (! is_dir($outputDir) && ! mkdir($outputDir, 0775, true) && ! is_dir($outputDir)) {
            $this->error('Unable to create output directory: ' . $outputDir);
            return self::FAILURE;
        }

        $payloads = [
            'manifest.json' => [
                'project' => $project,
                'scan' => $scan,
                'counts' => $preview,
                'generated_files' => [
                    'context.md',
                    'manifest.json',
                    'routes.json',
                    'pages.json',
                    'api_integrations.json',
                    'tracking_events.json',
                    'commerce_map.json',
                    'seo_map.json',
                    'dependency_map.json',
                    'findings.json',
                    'url_migration.json',
                    'database_context.json',
                    'files_inventory.json',
                ],
            ],
            'routes.json' => $routeInventory,
            'pages.json' => $pageInventory,
            'api_integrations.json' => $apiIntegrations,
            'tracking_events.json' => $tracking,
            'commerce_map.json' => $commerce,
            'seo_map.json' => $seo,
            'dependency_map.json' => $dependencyMap,
            'findings.json' => $findings,
            'url_migration.json' => $urlMigration,
            'database_context.json' => $tables,
            'files_inventory.json' => array_map(fn (string $path) => $this->fileInventoryRow($path), $allFiles),
        ];

        foreach ($payloads as $name => $data) {
            $this->writeJson($outputDir . '/' . $name, $data);
        }

        $context = $this->renderContextMarkdown(
            $project,
            $scan,
            $routeInventory,
            $pageInventory,
            $apiIntegrations,
            $tracking,
            $commerce,
            $seo,
            $dependencyMap,
            $findings,
            $urlMigration,
            $tables,
            $sourceFiles,
            $maxFileBytes
        );

        if (strlen($context) > $maxContextBytes) {
            $this->error(sprintf(
                'Context aborted: %.2f MiB exceeds --max-context-mb=%d.',
                strlen($context) / 1024 / 1024,
                (int) $this->option('max-context-mb')
            ));
            return self::FAILURE;
        }

        if (file_put_contents($outputDir . '/context.md', $context) === false) {
            $this->error('Unable to write context.md');
            return self::FAILURE;
        }

        $download = null;
        if ((bool) $this->option('download') && ! (bool) $this->option('no-download')) {
            $download = $this->publishDownload($outputDir, $project);
        }

        $this->newLine();
        $this->info('AI Storefront Context ready');
        $this->line('Directory: ' . $outputDir);
        $this->line('Main file: ' . $outputDir . '/context.md');

        if ($download) {
            $this->newLine();
            $this->line($download['url']);
            $this->line('Delete public file: rm -f ' . escapeshellarg($download['path']));
        }

        if ($this->warnings !== []) {
            $this->newLine();
            $this->warn('Warnings: ' . count($this->warnings));
            foreach (array_slice($this->warnings, 0, 20) as $warning) {
                $this->line('- ' . $warning);
            }
        }

        return self::SUCCESS;
    }

    private function normalizedScanPlan(string $mode, string $phase): array
    {
        $requested = $this->optionList('scan');
        if ($requested !== []) {
            return collect($requested)
                ->map(fn ($value) => strtolower($value))
                ->filter(fn ($value) => in_array($value, [
                    'routes', 'pages', 'views', 'assets', 'api', 'commerce', 'tracking',
                    'seo', 'auth', 'deploy', 'tests',
                ], true))
                ->unique()
                ->values()
                ->all();
        }

        if ($mode === 'erp-contract') {
            return ['routes', 'pages', 'api', 'commerce', 'auth', 'deploy', 'tests'];
        }

        if ($mode === 'release-audit') {
            return ['routes', 'pages', 'views', 'assets', 'api', 'commerce', 'tracking', 'seo', 'auth', 'deploy', 'tests'];
        }

        if ($mode === 'phase') {
            return match ($phase) {
                'tracking' => ['routes', 'pages', 'views', 'assets', 'api', 'tracking', 'tests'],
                'seo' => ['routes', 'pages', 'views', 'assets', 'seo', 'tests'],
                'checkout' => ['routes', 'pages', 'views', 'api', 'commerce', 'auth', 'tracking', 'tests'],
                'discover' => ['routes', 'pages', 'views', 'assets', 'api', 'commerce', 'tracking', 'tests'],
                default => ['routes', 'pages', 'views', 'assets', 'api', 'commerce', 'tracking', 'seo', 'auth', 'tests'],
            };
        }

        return ['routes', 'pages', 'views', 'assets', 'api', 'commerce', 'tracking', 'seo', 'auth', 'deploy', 'tests'];
    }

    private function collectProjectFiles(
        array $scan,
        array $refs,
        array $includeDirs,
        array $excludeDirs,
        int $maxFiles
    ): array {
        $roots = collect();

        if (in_array('routes', $scan, true)) {
            $roots->push('routes');
        }
        if (array_intersect($scan, ['pages', 'api', 'commerce', 'auth'])) {
            $roots->push('app/Http/Controllers', 'app/Http/Middleware', 'app/Services', 'app/Models');
        }
        if (in_array('views', $scan, true) || in_array('seo', $scan, true)) {
            $roots->push('resources/views');
        }
        if (in_array('assets', $scan, true) || in_array('tracking', $scan, true)) {
            $roots->push('resources/js', 'resources/css', 'resources/sass', 'public/js', 'public/css');
        }
        if (in_array('tests', $scan, true)) {
            $roots->push('tests');
        }
        if (in_array('deploy', $scan, true) || in_array('auth', $scan, true)) {
            $roots->push('config', 'app/Providers', 'bootstrap/app.php', 'app/Http/Kernel.php', 'app/Console/Kernel.php');
        }

        $roots->push('composer.json', 'package.json', 'vite.config.js', 'vite.config.ts', 'webpack.mix.js', 'phpunit.xml');
        $roots = $roots->merge($refs)->merge($includeDirs)->unique()->values();

        $skip = array_merge([
            'vendor', 'node_modules', '.git', 'storage/framework', 'storage/logs',
            'bootstrap/cache', 'public/build', 'public/hot', 'public/storage',
        ], $excludeDirs);

        $files = [];
        foreach ($roots as $root) {
            $abs = $this->absolutePath($root);
            if (is_file($abs)) {
                $rel = $this->normalizePath($abs);
                if ($this->isTextFile($rel) && ! $this->isSkipped($rel, $skip)) {
                    $files[$rel] = true;
                }
                continue;
            }
            if (! is_dir($abs)) {
                if (in_array($root, $refs, true)) {
                    $this->warnings[] = 'Ref does not exist: ' . $root;
                }
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($abs, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $rel = $this->normalizePath($file->getPathname());
                if ($this->isSkipped($rel, $skip) || ! $this->isTextFile($rel)) {
                    continue;
                }
                $files[$rel] = true;
                if (count($files) >= $maxFiles) {
                    $this->warnings[] = 'File scan reached --max-scan-files=' . $maxFiles;
                    break 2;
                }
            }
        }

        $paths = array_keys($files);
        sort($paths);
        return $paths;
    }

    private function selectSourceFiles(
        array $allFiles,
        array $refs,
        string $mode,
        string $phase,
        array $routePrefixes,
        int $maxFiles
    ): array {
        $scores = [];
        $boosts = array_filter(array_merge([
            'storefront', 'linxen', 'commerce', 'catalog', 'product', 'collection',
            'cart', 'checkout', 'quote', 'order', 'customer', 'address', 'inventory',
            'price', 'promotion', 'campaign', 'feed', 'discover', 'tracking', 'seo',
            'auth', 'session', 'sanctum', 'cors', 'route', 'api',
        ], $routePrefixes, [$phase]));

        foreach ($allFiles as $path) {
            $score = 0;
            $lower = strtolower($path);
            if (in_array($path, $refs, true)) {
                $score += 10000;
            }
            if (str_starts_with($path, 'routes/')) {
                $score += 900;
            }
            if (in_array($path, [
                'composer.json', 'package.json', 'vite.config.js', 'vite.config.ts',
                'webpack.mix.js', 'app/Http/Kernel.php', 'bootstrap/app.php',
                'config/services.php', 'config/session.php', 'config/sanctum.php',
                'config/cors.php', 'config/cache.php', 'config/filesystems.php',
            ], true)) {
                $score += 800;
            }
            foreach ($boosts as $term) {
                if ($term !== '' && str_contains($lower, strtolower($term))) {
                    $score += 120;
                }
            }
            if ($mode === 'erp-contract' && str_contains($lower, 'api')) {
                $score += 100;
            }
            if (str_starts_with($path, 'resources/views/')) {
                $score += 80;
            }
            if (str_starts_with($path, 'tests/')) {
                $score += 40;
            }
            $scores[$path] = $score;
        }

        arsort($scores);
        return array_slice(array_keys($scores), 0, $maxFiles);
    }

    private function routeInventory(array $allFiles, array $prefixes): array
    {
        $rows = [];
        try {
            foreach (Route::getRoutes() as $route) {
                $uri = (string) $route->uri();
                if ($prefixes !== [] && ! collect($prefixes)->contains(fn ($prefix) => str_starts_with($uri, trim($prefix, '/')))) {
                    continue;
                }

                $action = (string) $route->getActionName();
                [$class, $method] = $this->splitAction($action);
                $controllerFile = $class ? $this->classFile($class) : null;
                $controllerExists = $class ? class_exists($class) : null;

                $middleware = [];
                try {
                    $middleware = $route->gatherMiddleware();
                } catch (Throwable $e) {
                    $middleware = (array) ($route->getAction('middleware') ?? []);
                    $this->warnings[] = 'Middleware reflection failed for ' . $uri . ': ' . $e->getMessage();
                }

                $rows[] = [
                    'methods' => array_values(array_diff($route->methods(), ['HEAD'])),
                    'uri' => $uri,
                    'name' => $route->getName(),
                    'middleware' => array_values($middleware),
                    'action' => $action,
                    'controller_class' => $class,
                    'controller_method' => $method,
                    'controller_exists' => $controllerExists,
                    'controller_file' => $controllerFile,
                    'view_candidates' => $controllerFile ? $this->extractViewCandidates($controllerFile, $method) : [],
                    'page_type' => $this->classifyPage($uri, $action),
                ];
            }
        } catch (Throwable $e) {
            $this->warnings[] = 'Runtime route inventory failed: ' . $e->getMessage();
        }

        if ($rows === []) {
            $rows = $this->staticRouteInventory($allFiles, $prefixes);
        }

        usort($rows, fn ($a, $b) => strcmp(($a['uri'] ?? '') . implode(',', $a['methods'] ?? []), ($b['uri'] ?? '') . implode(',', $b['methods'] ?? [])));
        return $rows;
    }

    private function staticRouteInventory(array $allFiles, array $prefixes): array
    {
        $rows = [];
        foreach ($allFiles as $path) {
            if (! str_starts_with($path, 'routes/') || ! str_ends_with($path, '.php')) {
                continue;
            }
            $lines = preg_split('/\R/', $this->readFile($path));
            foreach ($lines as $index => $line) {
                if (! preg_match('/Route::(get|post|put|patch|delete|options|any|match)\s*\(\s*[\'\"]([^\'\"]+)/i', $line, $m)) {
                    continue;
                }
                $uri = ltrim($m[2], '/');
                if ($prefixes !== [] && ! collect($prefixes)->contains(fn ($prefix) => str_starts_with($uri, trim($prefix, '/')))) {
                    continue;
                }
                $rows[] = [
                    'methods' => [strtoupper($m[1])],
                    'uri' => $uri,
                    'name' => null,
                    'middleware' => [],
                    'action' => null,
                    'controller_class' => null,
                    'controller_method' => null,
                    'controller_exists' => null,
                    'controller_file' => null,
                    'view_candidates' => [],
                    'page_type' => $this->classifyPage($uri, ''),
                    'source_file' => $path,
                    'source_line' => $index + 1,
                    'static_parse' => true,
                ];
            }
        }
        return $rows;
    }

    private function pageInventory(array $routes): array
    {
        return collect($routes)
            ->filter(fn ($route) => in_array('GET', $route['methods'] ?? [], true))
            ->map(function ($route) {
                return [
                    'uri' => $route['uri'] ?? null,
                    'name' => $route['name'] ?? null,
                    'page_type' => $route['page_type'] ?? 'other',
                    'controller' => $route['action'] ?? null,
                    'views' => $route['view_candidates'] ?? [],
                    'middleware' => $route['middleware'] ?? [],
                    'indexable_candidate' => ! $this->isNonIndexableUri((string) ($route['uri'] ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    private function scanApiIntegrations(array $files, int $limit): array
    {
        $needles = ['Http::', 'fetch(', 'axios.', 'axios(', 'XMLHttpRequest', 'GuzzleHttp', 'new Client(', 'curl_init', 'curl_setopt'];
        return $this->scanLines($files, $needles, $limit, function (string $path, int $lineNumber, string $line, array $context) {
            $method = null;
            if (preg_match('/\b(get|post|put|patch|delete|head|options)\s*\(/i', $line, $m)) {
                $method = strtoupper($m[1]);
            }
            if (preg_match('/method\s*[:=]>?\s*[\'\"]([A-Z]+)[\'\"]/i', implode("\n", $context), $m)) {
                $method = strtoupper($m[1]);
            }

            $endpoint = null;
            if (preg_match('/[\'\"]((?:https?:\/\/|\/api\/|api\/|\/storefront\/|\/commerce\/)[^\'\"]*)[\'\"]/', implode("\n", $context), $m)) {
                $endpoint = $m[1];
            }

            preg_match_all('/[\'\"](Authorization|X-[A-Za-z0-9-]+)[\'\"]/i', implode("\n", $context), $headerMatches);

            return [
                'file' => $path,
                'line' => $lineNumber,
                'client' => $this->detectHttpClient($line),
                'method' => $method,
                'endpoint' => $endpoint,
                'headers' => array_values(array_unique($headerMatches[1] ?? [])),
                'excerpt' => $this->redactText(implode("\n", $context)),
            ];
        });
    }

    private function scanTracking(array $files, int $limit): array
    {
        $needles = [
            'fbq(', 'dataLayer', 'gtag(', 'Google Tag Manager', 'Meta Pixel', 'Conversions API',
            'event_id', 'fbclid', 'utm_source', 'utm_campaign', 'ViewContent', 'AddToCart',
            'InitiateCheckout', 'Purchase', 'FeedImpression', 'SwipeNext', 'LandingView',
        ];

        return $this->scanLines($files, $needles, $limit, function (string $path, int $lineNumber, string $line, array $context) {
            $event = null;
            if (preg_match('/[\'\"](ViewContent|AddToCart|InitiateCheckout|Purchase|LandingView|FeedImpression|FeedDwell|SwipeNext|SelectColor|SelectSize|AddShippingInfo)[\'\"]/', implode("\n", $context), $m)) {
                $event = $m[1];
            }
            return [
                'file' => $path,
                'line' => $lineNumber,
                'event' => $event,
                'browser_side' => str_contains($path, 'resources/') || str_contains($path, 'public/'),
                'has_event_id_nearby' => stripos(implode("\n", $context), 'event_id') !== false,
                'excerpt' => $this->redactText(implode("\n", $context)),
            ];
        });
    }

    private function scanCommerce(array $files): array
    {
        $domains = [
            'catalog' => ['catalog', 'product', 'collection', 'search'],
            'variant' => ['variant', 'sku', 'size', 'color'],
            'pricing' => ['price', 'promotion', 'discount', 'quote'],
            'inventory' => ['inventory', 'stock', 'reserve', 'reserved', 'available'],
            'cart' => ['cart', 'basket'],
            'checkout' => ['checkout', 'shipping_fee', 'shipping address'],
            'order' => ['order', 'idempotency', 'purchase'],
            'customer' => ['customer', 'profile', 'address', 'otp'],
            'media' => ['media', 'image', 'video', 'gallery'],
            'campaign' => ['campaign', 'utm_', 'fbclid', 'landing'],
            'discover' => ['discover', 'feed', 'swipe', 'quick buy'],
        ];

        $result = [];
        foreach ($domains as $domain => $terms) {
            $matches = [];
            foreach ($files as $path) {
                $lowerPath = strtolower($path);
                $content = null;
                $score = 0;
                foreach ($terms as $term) {
                    if (str_contains($lowerPath, $term)) {
                        $score += 5;
                    }
                }
                if ($score === 0) {
                    $content = strtolower($this->readFile($path));
                    foreach ($terms as $term) {
                        $score += min(5, substr_count($content, strtolower($term)));
                    }
                }
                if ($score > 0) {
                    $matches[] = ['file' => $path, 'score' => $score];
                }
            }
            usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);
            $result[$domain] = array_slice($matches, 0, 40);
        }
        return $result;
    }

    private function scanSeo(array $files, int $limit): array
    {
        $needles = ['<title', 'meta name="description"', "meta name='description'", 'rel="canonical"', 'application/ld+json', 'og:', 'robots', 'sitemap', 'BreadcrumbList', 'Product'];
        return $this->scanLines($files, $needles, $limit, function (string $path, int $lineNumber, string $line, array $context) {
            return [
                'file' => $path,
                'line' => $lineNumber,
                'signal' => trim($line),
                'excerpt' => $this->redactText(implode("\n", $context)),
            ];
        });
    }

    private function dependencyMap(array $files): array
    {
        $map = [];
        foreach ($files as $path) {
            $content = $this->readFile($path);
            $uses = [];
            if (preg_match_all('/^use\s+(App\\\\[^;]+);/m', $content, $m)) {
                $uses = array_values(array_unique($m[1]));
            }
            $views = [];
            if (preg_match_all('/\bview\s*\(\s*[\'\"]([^\'\"]+)/', $content, $m)) {
                $views = array_values(array_unique($m[1]));
            }
            $routes = [];
            if (preg_match_all('/\broute\s*\(\s*[\'\"]([^\'\"]+)/', $content, $m)) {
                $routes = array_values(array_unique($m[1]));
            }
            $map[] = [
                'file' => $path,
                'uses' => array_slice($uses, 0, 100),
                'views' => array_slice($views, 0, 100),
                'named_routes' => array_slice($routes, 0, 100),
            ];
        }
        return $map;
    }

    private function buildFindings(
        string $mode,
        array $files,
        array $routes,
        array $api,
        array $tracking,
        array $commerce,
        array $seo
    ): array {
        $findings = [];
        $contents = [];
        foreach ($files as $path) {
            if ($this->isHighSignalFile($path)) {
                $contents[$path] = $this->readFile($path);
            }
        }
        $haystack = implode("\n", $contents);

        $headers = [];
        foreach (['X-Storefront-Code', 'X-STOREFRONT', 'X-ERP-TOKEN', 'X-Site-Code', 'Idempotency-Key'] as $header) {
            if (stripos($haystack, $header) !== false) {
                $headers[] = $header;
            }
        }
        if (count(array_intersect($headers, ['X-Storefront-Code', 'X-STOREFRONT', 'X-Site-Code'])) > 1) {
            $findings[] = $this->finding('high', 'mixed_site_headers', 'Multiple storefront/site identity headers are in use.', [], implode(', ', $headers), 'Choose one versioned Commerce API authentication contract.');
        }

        if (stripos($haystack, 'X-Customer-Phone') !== false) {
            $findings[] = $this->finding('critical', 'phone_header_identity', 'Customer phone is used as an identity header.', [], 'X-Customer-Phone', 'Replace with signed customer session/token or OTP-backed identity.');
        }

        if (preg_match('/allowed_origins[\'\"]?\s*=>\s*\[\s*[\'\"]\*[\'\"]\s*\]/i', $haystack)) {
            $findings[] = $this->finding('high', 'cors_wildcard', 'CORS allows every origin.', ['config/cors.php'], "allowed_origins => ['*']", 'Restrict origins per storefront domain and environment.');
        }

        if (preg_match('/items\.\*\.price[^\n]+required/i', $haystack) || preg_match('/[\'\"]price[\'\"]\s*=>\s*\(float\)\s*\$item/i', $haystack)) {
            $findings[] = $this->finding('critical', 'client_price_trust', 'Checkout appears to accept item price from the client.', [], 'items.*.price', 'Send variant_id and quantity only; ERP must issue and validate a checkout quote.');
        }

        if (stripos($haystack, 'checkout') !== false && stripos($haystack, 'Idempotency-Key') === false && stripos($haystack, 'idempotency') === false) {
            $findings[] = $this->finding('critical', 'missing_order_idempotency', 'No idempotency contract was detected around checkout/order creation.', [], null, 'Require Idempotency-Key and persist request/result state in ERP.');
        }

        if (preg_match('/return\s+redirect\s*\(\s*\$request->/i', $haystack)) {
            $findings[] = $this->finding('high', 'open_redirect_candidate', 'A redirect target appears to come directly from request input.', [], 'return redirect($request->...)', 'Allowlist storefront domains or use signed redirect intents.');
        }

        $erpTables = ['kiotviet_products', 'product_stock_availabilities', 'kiotviet_orders', 'research_sets', 'product_prices'];
        $directTables = [];
        foreach ($erpTables as $table) {
            if (stripos($haystack, $table) !== false) {
                $directTables[] = $table;
            }
        }
        if ($mode === 'audit-v1' && $directTables !== []) {
            $findings[] = $this->finding('critical', 'direct_erp_db_coupling', 'Storefront source references ERP-owned tables directly.', [], implode(', ', $directTables), 'Move reads/writes behind versioned Commerce API contracts.');
        }

        $missingControllers = collect($routes)->filter(fn ($route) => ($route['controller_class'] ?? null) && ($route['controller_exists'] ?? true) === false)->values();
        foreach ($missingControllers as $route) {
            $findings[] = $this->finding('critical', 'missing_route_controller', 'Route controller class does not exist or cannot autoload.', [$route['controller_file'] ?? ''], ($route['uri'] ?? '') . ' -> ' . ($route['controller_class'] ?? ''), 'Fix path/namespace before route caching or migration.');
        }

        $duplicates = collect($routes)
            ->groupBy(fn ($route) => implode(',', $route['methods'] ?? []) . ' ' . ($route['uri'] ?? ''))
            ->filter(fn ($group) => $group->count() > 1);
        foreach ($duplicates as $key => $group) {
            $findings[] = $this->finding('medium', 'duplicate_route_candidate', 'Duplicate route signature detected.', [], $key, 'Consolidate route ownership and preserve a single canonical action.');
        }

        if ($tracking !== [] && collect($tracking)->contains(fn ($row) => ($row['event'] ?? null) === 'Purchase') && ! collect($tracking)->contains(fn ($row) => ($row['event'] ?? null) === 'Purchase' && ($row['has_event_id_nearby'] ?? false))) {
            $findings[] = $this->finding('high', 'purchase_dedup_not_detected', 'Purchase tracking exists but event_id deduplication was not detected nearby.', [], null, 'Use the same event_name and event_id for browser Pixel and server CAPI.');
        }

        if ($seo === []) {
            $findings[] = $this->finding('medium', 'seo_signals_not_detected', 'No strong SEO metadata or structured-data signals were detected.', [], null, 'Add server-rendered title, canonical, Open Graph and Product JSON-LD.');
        }

        if ($api === []) {
            $findings[] = $this->finding('medium', 'api_client_not_detected', 'No outbound HTTP client usage was detected.', [], null, 'Confirm whether V1 reads ERP DB directly or hides integration in another package/source.');
        }

        return array_values($findings);
    }

    private function buildUrlMigration(array $pages, array $urls): array
    {
        $rows = collect($pages)->map(function ($page) {
            $uri = trim((string) ($page['uri'] ?? ''), '/');
            $current = '/' . $uri;
            if ($current === '/') {
                $current = '/';
            }
            return [
                'current_url' => $current,
                'page_type' => $page['page_type'] ?? 'other',
                'route_name' => $page['name'] ?? null,
                'indexable_candidate' => $page['indexable_candidate'] ?? false,
                'recommended_v2_url' => null,
                'migration_action' => 'REVIEW',
                'notes' => null,
            ];
        });

        foreach ($urls as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            if (! $rows->contains(fn ($row) => $row['current_url'] === $path)) {
                $rows->push([
                    'current_url' => $url,
                    'page_type' => 'provided_url',
                    'route_name' => null,
                    'indexable_candidate' => true,
                    'recommended_v2_url' => null,
                    'migration_action' => 'REVIEW',
                    'notes' => 'Provided through --url',
                ]);
            }
        }

        return $rows->values()->all();
    }

    private function databaseContext(array $tables): array
    {
        $result = [];
        foreach ($tables as $table) {
            if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
                $this->warnings[] = 'Invalid table name skipped: ' . $table;
                continue;
            }
            if (! Schema::hasTable($table)) {
                $result[$table] = ['exists' => false];
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $query = DB::table($table);
            $where = $this->sampleWhereFor($table);
            if ($where) {
                $query->where($where['column'], $where['value']);
            }
            $allowedColumns = $this->sampleColumnsFor($table, $columns);
            if ($allowedColumns !== []) {
                $query->select($allowedColumns);
            }

            try {
                $rows = $query->limit(5)->get()
                    ->map(fn ($row) => $this->redactDeep((array) $row, '', (bool) $this->option('include-pii')))
                    ->values()
                    ->all();
            } catch (Throwable $e) {
                $rows = [['error' => $e->getMessage()]];
            }

            $result[$table] = [
                'exists' => true,
                'columns' => $columns,
                'sample_filter' => $where,
                'sample_columns' => $allowedColumns,
                'sample_rows' => $rows,
            ];
        }
        return $result;
    }

    private function renderContextMarkdown(
        array $project,
        array $scan,
        array $routes,
        array $pages,
        array $api,
        array $tracking,
        array $commerce,
        array $seo,
        array $dependencies,
        array $findings,
        array $migration,
        array $tables,
        array $sourceFiles,
        int $maxFileBytes
    ): string {
        $md = [];
        $md[] = '# AI Storefront Context';
        $md[] = '';
        $md[] = '- Generated at: `' . $project['generated_at'] . '`';
        $md[] = '- Project: `' . $project['project_id'] . '`';
        $md[] = '- Laravel root: `' . $project['root'] . '`';
        $md[] = '- Mode: `' . $project['mode'] . '`';
        $md[] = '- Site: `' . $project['site'] . '`';
        $md[] = '- Phase: `' . ($project['phase'] ?: 'N/A') . '`';
        $md[] = '- Git branch/commit: `' . ($project['git']['branch'] ?? 'N/A') . '` / `' . ($project['git']['commit'] ?? 'N/A') . '`';
        $md[] = '- Scan: `' . implode(', ', $scan) . '`';
        $md[] = '';

        $md[] = '## 1. Yêu cầu cho AI';
        $md[] = '';
        $md[] = '```text';
        $md[] = 'Bạn đang phân tích một Laravel Storefront/ERP source để xây Lin Xén V2.';
        $md[] = 'Đây là bước audit và thiết kế nền, KHÔNG tạo patch nếu người dùng chưa yêu cầu.';
        $md[] = 'Hãy:';
        $md[] = '1. Lập page/route inventory và luồng người dùng hiện tại.';
        $md[] = '2. Lập dependency map route → controller → service/API client → view/assets.';
        $md[] = '3. Phân tích catalog, product, variant, price, inventory, cart, checkout, order, customer, tracking và SEO.';
        $md[] = '4. Chỉ ra phụ thuộc trực tiếp ERP DB, contract/auth không thống nhất, rủi ro bảo mật và rủi ro chuyển đổi.';
        $md[] = '5. Phân loại từng phần: KEEP, MIGRATE, REBUILD, REMOVE hoặc REDIRECT.';
        $md[] = '6. Tách rõ phần thuộc ERP và phần thuộc Storefront source mới; Storefront không được đọc trực tiếp DB ERP.';
        $md[] = '7. Đề xuất phase tiếp theo và danh sách file/context còn thiếu.';
        $md[] = '8. Không suy đoán secret/credential đã bị redacted.';
        $md[] = '```';
        $md[] = '';

        $md[] = '## 2. Project Metadata';
        $md[] = '';
        $md[] = '```json';
        $md[] = json_encode($project, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $md[] = '```';
        $md[] = '';

        $md[] = '## 3. Findings';
        $md[] = '';
        if ($findings === []) {
            $md[] = '_No automated findings._';
        } else {
            foreach ($findings as $finding) {
                $md[] = '- **' . strtoupper($finding['severity']) . ' · ' . $finding['code'] . '** — ' . $finding['message'];
                if (! empty($finding['evidence'])) {
                    $md[] = '  - Evidence: `' . str_replace('`', '', (string) $finding['evidence']) . '`';
                }
                $md[] = '  - Recommendation: ' . $finding['recommendation'];
            }
        }
        $md[] = '';

        $md[] = '## 4. Route Inventory';
        $md[] = '';
        $md[] = '```json';
        $md[] = json_encode($routes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $md[] = '```';
        $md[] = '';

        $md[] = '## 5. Page Inventory';
        $md[] = '';
        $md[] = '```json';
        $md[] = json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $md[] = '```';
        $md[] = '';

        $sections = [
            '## 6. API Integrations' => $api,
            '## 7. Tracking Events' => $tracking,
            '## 8. Commerce Map' => $commerce,
            '## 9. SEO Map' => $seo,
            '## 10. Dependency Map' => $dependencies,
            '## 11. URL Migration Template' => $migration,
            '## 12. Database Context' => $tables,
        ];
        foreach ($sections as $title => $data) {
            $md[] = $title;
            $md[] = '';
            $md[] = '```json';
            $md[] = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $md[] = '```';
            $md[] = '';
        }

        $md[] = '## 13. Source Files';
        $md[] = '';
        foreach ($sourceFiles as $path) {
            $content = $this->readFile($path);
            if (strlen($content) > $maxFileBytes) {
                $content = substr($content, 0, $maxFileBytes)
                    . "\n\n/* --- TRUNCATED: file exceeds " . number_format($maxFileBytes / 1024, 0) . " KiB --- */\n";
            }
            $md[] = '### `' . $path . '`';
            $md[] = '';
            $md[] = '```' . $this->languageFor($path);
            $md[] = rtrim($this->redactText($content));
            $md[] = '```';
            $md[] = '';
        }

        if ($this->warnings !== []) {
            $md[] = '## 14. Warnings';
            $md[] = '';
            foreach ($this->warnings as $warning) {
                $md[] = '- ' . $warning;
            }
            $md[] = '';
        }

        return implode("\n", $md) . "\n";
    }

    private function projectMetadata(string $mode, string $site, string $phase, array $urls): array
    {
        $composer = $this->readJsonFile('composer.json');
        $package = $this->readJsonFile('package.json');
        $projectId = trim((string) $this->option('project-id'));
        if ($projectId === '') {
            $projectId = Str::slug((string) ($composer['name'] ?? basename(base_path())), '-');
        }

        return [
            'project_id' => $projectId,
            'root' => base_path(),
            'mode' => $mode,
            'site' => $site,
            'phase' => $phase ?: null,
            'urls' => $urls,
            'generated_at' => now()->toIso8601String(),
            'environment_name' => (string) app()->environment(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'composer_name' => $composer['name'] ?? null,
            'composer_require' => $composer['require'] ?? [],
            'package_dependencies' => array_merge($package['dependencies'] ?? [], $package['devDependencies'] ?? []),
            'git' => $this->gitMetadata(),
            'env_keys' => $this->environmentKeys(),
        ];
    }

    private function gitMetadata(): array
    {
        $branch = null;
        $commit = null;
        $dirty = null;
        $gitDir = base_path('.git');
        if (is_dir($gitDir)) {
            $head = @file_get_contents($gitDir . '/HEAD');
            if (is_string($head)) {
                $head = trim($head);
                if (str_starts_with($head, 'ref: ')) {
                    $ref = trim(substr($head, 5));
                    $branch = basename($ref);
                    $commit = trim((string) @file_get_contents($gitDir . '/' . $ref));
                } elseif (preg_match('/^[a-f0-9]{40}$/i', $head)) {
                    $commit = $head;
                }
            }
        }

        if (function_exists('shell_exec')) {
            $status = @shell_exec('git -C ' . escapeshellarg(base_path()) . ' status --porcelain 2>/dev/null');
            if (is_string($status)) {
                $dirty = trim($status) !== '';
            }
        }

        return ['branch' => $branch, 'commit' => $commit ?: null, 'dirty' => $dirty];
    }

    private function environmentKeys(): array
    {
        $path = base_path('.env');
        if (! is_file($path)) {
            return [];
        }
        $keys = [];
        foreach ((array) @file($path, FILE_IGNORE_NEW_LINES) as $line) {
            $line = trim((string) $line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            $key = trim(Str::before($line, '='));
            if (preg_match('/^[A-Z0-9_]+$/', $key)) {
                $keys[] = $key;
            }
        }
        sort($keys);
        return array_values(array_unique($keys));
    }

    private function resolveOutputDirectory(string $mode, string $site, string $phase): string
    {
        $output = trim((string) $this->option('output'));
        if ($output !== '') {
            return str_starts_with($output, '/') ? rtrim($output, '/') : base_path(trim($output, '/'));
        }

        $suffix = $phase !== '' ? '_' . Str::slug($phase, '-') : '';
        return storage_path('app/ai-storefront-context/' . now()->format('Ymd_His') . '_' . $mode . '_' . $site . $suffix);
    }

    private function publishDownload(string $outputDir, array $project): ?array
    {
        $downloadDir = trim((string) $this->option('download-dir'), '/');
        $downloadDir = $downloadDir !== '' ? $downloadDir : 'ai-context';
        $now = now();
        $sortKey = sprintf('%010d', max(0, 9999999999 - $now->timestamp));
        $baseName = '0000_UPLOAD_FIRST_' . $sortKey . '_' . $now->format('Ymd_His')
            . '_ai_storefront_context_' . Str::slug($project['project_id'] . '_' . $project['mode'], '-')
            . '_' . Str::lower(Str::random(10));
        $publicDir = public_path($downloadDir);
        if (! is_dir($publicDir) && ! mkdir($publicDir, 0775, true) && ! is_dir($publicDir)) {
            $this->warnings[] = 'Unable to create public download directory.';
            return null;
        }

        $path = $publicDir . '/' . $baseName . '.md';
        $source = $outputDir . '/context.md';

        if (class_exists(ZipArchive::class)) {
            $zipPath = $publicDir . '/' . $baseName . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach (glob($outputDir . '/*') ?: [] as $file) {
                    if (is_file($file)) {
                        $zip->addFile($file, basename($file));
                    }
                }
                $zip->close();
                $path = $zipPath;
            } else {
                copy($source, $path);
            }
        } else {
            copy($source, $path);
        }

        $baseUrl = trim((string) $this->option('base-url')) ?: trim((string) config('app.url')) ?: 'https://3mg.ai';
        return [
            'path' => $path,
            'url' => rtrim($baseUrl, '/') . '/' . $downloadDir . '/' . basename($path),
        ];
    }

    private function scanLines(array $files, array $needles, int $limit, callable $mapper): array
    {
        $rows = [];
        foreach ($files as $path) {
            $content = $this->readFile($path);
            if ($content === '') {
                continue;
            }
            $lines = preg_split('/\R/', $content);
            foreach ($lines as $i => $line) {
                $matched = false;
                foreach ($needles as $needle) {
                    if (stripos($line, $needle) !== false) {
                        $matched = true;
                        break;
                    }
                }
                if (! $matched) {
                    continue;
                }
                $start = max(0, $i - 2);
                $context = array_slice($lines, $start, 5);
                $rows[] = $mapper($path, $i + 1, $line, $context);
                if (count($rows) >= $limit) {
                    return $rows;
                }
            }
        }
        return $rows;
    }

    private function splitAction(string $action): array
    {
        if ($action === 'Closure' || $action === '') {
            return [null, null];
        }
        if (str_contains($action, '@')) {
            return [Str::before($action, '@'), Str::after($action, '@')];
        }
        return [$action, '__invoke'];
    }

    private function classFile(string $class): ?string
    {
        try {
            if (class_exists($class)) {
                $file = (new ReflectionClass($class))->getFileName();
                return $file ? $this->normalizePath($file) : null;
            }
        } catch (Throwable) {
        }

        if (str_starts_with($class, 'App\\')) {
            $candidate = 'app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
            return is_file($this->absolutePath($candidate)) ? $candidate : null;
        }
        return null;
    }

    private function extractViewCandidates(string $controllerFile, ?string $method): array
    {
        $content = $this->readFile($controllerFile);
        if ($method && $method !== '__invoke') {
            $content = $this->methodSlice($content, $method) ?: $content;
        }
        if (preg_match_all('/\bview\s*\(\s*[\'\"]([^\'\"]+)/', $content, $m)) {
            return array_values(array_unique($m[1]));
        }
        return [];
    }

    private function methodSlice(string $content, string $method): ?string
    {
        if (! preg_match('/function\s+' . preg_quote($method, '/') . '\s*\([^)]*\)\s*(?::[^\{]+)?\{/m', $content, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $start = $m[0][1];
        $open = strpos($content, '{', $start);
        if ($open === false) {
            return null;
        }
        $depth = 0;
        $length = strlen($content);
        for ($i = $open; $i < $length; $i++) {
            if ($content[$i] === '{') {
                $depth++;
            } elseif ($content[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $start, $i - $start + 1);
                }
            }
        }
        return null;
    }

    private function classifyPage(string $uri, string $action): string
    {
        $value = strtolower($uri . ' ' . $action);
        return match (true) {
            str_contains($value, 'checkout') => 'checkout',
            str_contains($value, 'cart') => 'cart',
            str_contains($value, 'discover'), str_contains($value, 'feed') => 'discover',
            str_contains($value, 'campaign'), str_contains($value, '/go/') => 'campaign_entry',
            str_contains($value, 'collection') => 'collection',
            str_contains($value, 'product'), preg_match('/(^|\/)p\//', $uri) === 1 => 'product',
            str_contains($value, 'search') => 'search',
            str_contains($value, 'order') => 'order',
            str_contains($value, 'account'), str_contains($value, 'profile'), str_contains($value, 'customer') => 'account',
            str_contains($value, 'privacy'), str_contains($value, 'policy'), str_contains($value, 'terms') => 'legal',
            trim($uri, '/') === '' => 'home',
            str_contains($value, 'api/') => 'api',
            default => 'other',
        };
    }

    private function isNonIndexableUri(string $uri): bool
    {
        $uri = strtolower($uri);
        return str_contains($uri, 'api/')
            || str_contains($uri, 'checkout')
            || str_contains($uri, 'cart')
            || str_contains($uri, 'account')
            || str_contains($uri, 'orders/')
            || str_contains($uri, 'admin');
    }

    private function detectHttpClient(string $line): string
    {
        return match (true) {
            stripos($line, 'Http::') !== false => 'laravel_http',
            stripos($line, 'fetch(') !== false => 'fetch',
            stripos($line, 'axios') !== false => 'axios',
            stripos($line, 'Guzzle') !== false, stripos($line, 'new Client(') !== false => 'guzzle',
            stripos($line, 'curl_') !== false => 'curl',
            stripos($line, 'XMLHttpRequest') !== false => 'xhr',
            default => 'unknown',
        };
    }

    private function finding(string $severity, string $code, string $message, array $files, ?string $evidence, string $recommendation): array
    {
        return [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'files' => array_values(array_filter($files)),
            'evidence' => $evidence,
            'recommendation' => $recommendation,
        ];
    }

    private function sampleWhereFor(string $table): ?array
    {
        foreach ($this->optionList('sample-where') as $value) {
            if (! str_starts_with($value, $table . ':')) {
                continue;
            }
            $expression = substr($value, strlen($table) + 1);
            if (! str_contains($expression, '=')) {
                continue;
            }
            $column = trim(Str::before($expression, '='));
            $filterValue = trim(Str::after($expression, '='));
            if (preg_match('/^[A-Za-z0-9_]+$/', $column) === 1) {
                return ['column' => $column, 'value' => $filterValue];
            }
        }
        return null;
    }

    private function sampleColumnsFor(string $table, array $existing): array
    {
        foreach ($this->optionList('sample-columns') as $value) {
            if (! str_starts_with($value, $table . ':')) {
                continue;
            }
            $columns = preg_split('/[|,]/', substr($value, strlen($table) + 1));
            return collect($columns)
                ->map(fn ($column) => trim((string) $column))
                ->filter(fn ($column) => in_array($column, $existing, true))
                ->values()
                ->all();
        }

        $safe = collect($existing)->reject(fn ($column) => $this->isSensitiveKey($column, true))->take(30)->values()->all();
        return $safe;
    }

    private function redactDeep(mixed $value, string $path = '', bool $includePii = false, int $depth = 0): mixed
    {
        if ($depth > 8) {
            return '/* truncated: max depth */';
        }
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (is_array($value)) {
            $result = [];
            $count = 0;
            foreach ($value as $key => $item) {
                $count++;
                if ($count > 120) {
                    $result['__truncated_items'] = count($value) - 120;
                    break;
                }
                $keyString = (string) $key;
                $nextPath = $path === '' ? $keyString : $path . '.' . $keyString;
                if ($this->isSensitiveKey($nextPath, ! $includePii)) {
                    $result[$key] = '***REDACTED***';
                    continue;
                }
                $result[$key] = $this->redactDeep($item, $nextPath, $includePii, $depth + 1);
            }
            return $result;
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if (($trimmed !== '' && ($trimmed[0] ?? '') === '{') || (($trimmed[0] ?? '') === '[')) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->redactDeep($decoded, $path, $includePii, $depth + 1);
                }
            }
            return Str::limit($this->redactText($value), 20000, ' /* truncated */');
        }
        return $value;
    }

    private function isSensitiveKey(string $key, bool $includePii): bool
    {
        $key = strtolower($key);
        $secrets = [
            'password', 'passwd', 'secret', 'token', 'api_key', 'apikey', 'access_key',
            'private_key', 'credential', 'client_secret', 'bearer', 'authorization',
            'tfa', 'otp_secret', 'cookie', 'session_id', 'remember_token', 'refresh_token',
            'access_token',
        ];
        foreach ($secrets as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }
        if ($includePii) {
            return false;
        }
        foreach (['phone', 'mobile', 'email', 'address', 'birthday', 'contact_number', 'receiver_phone'] as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function redactText(string $text): string
    {
        $patterns = [
            '/(Authorization\s*[:=]\s*Bearer\s+)[A-Za-z0-9._~+\/-]+/i' => '$1***REDACTED***',
            '/(Bearer\s+)[A-Za-z0-9._~+\/-]{20,}/i' => '$1***REDACTED***',
            '/([\'\"]?(?:password|passwd|secret|token|api[_-]?key|client_secret|private_key|tfa_secret_key|access_token|refresh_token)[\'\"]?\s*(?:=>|:|=)\s*)[\'\"][^\'\"]+[\'\"]/i' => '$1"***REDACTED***"',
            '/(env\(\s*[\'\"](?:[^\'\"]*(?:TOKEN|SECRET|KEY|PASSWORD)[^\'\"]*)[\'\"]\s*,\s*)[\'\"][^\'\"]+[\'\"]/i' => '$1"***REDACTED***"',
        ];
        return preg_replace(array_keys($patterns), array_values($patterns), $text) ?? $text;
    }

    private function fileInventoryRow(string $path): array
    {
        $abs = $this->absolutePath($path);
        return [
            'path' => $path,
            'bytes' => is_file($abs) ? filesize($abs) : null,
            'modified_at' => is_file($abs) ? date(DATE_ATOM, filemtime($abs)) : null,
            'sha1' => is_file($abs) ? sha1_file($abs) : null,
        ];
    }

    private function writeJson(string $path, mixed $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || file_put_contents($path, $json . "\n") === false) {
            throw new \RuntimeException('Unable to write JSON: ' . $path);
        }
    }

    private function readJsonFile(string $path): array
    {
        $abs = $this->absolutePath($path);
        if (! is_file($abs)) {
            return [];
        }
        $decoded = json_decode((string) @file_get_contents($abs), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function readFile(string $path): string
    {
        if (array_key_exists($path, $this->fileCache)) {
            return $this->fileCache[$path];
        }
        $abs = $this->absolutePath($path);
        $content = is_file($abs) ? @file_get_contents($abs) : false;
        $this->fileCache[$path] = is_string($content) ? $content : '';
        return $this->fileCache[$path];
    }

    private function isHighSignalFile(string $path): bool
    {
        $path = strtolower($path);
        return str_starts_with($path, 'routes/')
            || str_starts_with($path, 'config/')
            || str_contains($path, 'storefront')
            || str_contains($path, 'commerce')
            || str_contains($path, 'checkout')
            || str_contains($path, 'cart')
            || str_contains($path, 'order')
            || str_contains($path, 'product')
            || str_contains($path, 'tracking')
            || str_contains($path, 'customer');
    }

    private function languageFor(string $path): string
    {
        return match (true) {
            str_ends_with($path, '.blade.php') => 'blade',
            str_ends_with($path, '.php') => 'php',
            str_ends_with($path, '.js') => 'javascript',
            str_ends_with($path, '.ts') => 'typescript',
            str_ends_with($path, '.css') => 'css',
            str_ends_with($path, '.scss') => 'scss',
            str_ends_with($path, '.json') => 'json',
            str_ends_with($path, '.md') => 'markdown',
            default => 'text',
        };
    }

    private function optionList(string $name): array
    {
        $values = $this->option($name);
        $values = is_array($values) ? $values : [$values];
        return collect($values)
            ->flatMap(fn ($value) => explode(',', (string) $value))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizePaths(array $paths): array
    {
        return collect($paths)
            ->map(fn ($path) => $this->normalizePath($path))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function isTextFile(string $path): bool
    {
        return preg_match('/(?:\.blade\.php|\.php|\.js|\.mjs|\.cjs|\.ts|\.tsx|\.vue|\.css|\.scss|\.sass|\.json|\.md|\.xml|\.yml|\.yaml|\.env|phpunit\.xml)$/i', $path) === 1;
    }

    private function isSkipped(string $path, array $skip): bool
    {
        $normalized = '/' . trim(str_replace('\\', '/', $path), '/') . '/';
        foreach ($skip as $entry) {
            $entry = trim(str_replace('\\', '/', $entry), '/');
            if ($entry !== '' && str_contains($normalized, '/' . $entry . '/')) {
                return true;
            }
        }
        return false;
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $base = str_replace('\\', '/', base_path());
        if (str_starts_with($path, $base)) {
            return ltrim(substr($path, strlen($base)), '/');
        }
        return ltrim($path, '/');
    }

    private function absolutePath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}
