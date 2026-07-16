<?php

namespace App\Console\Commands;

use App\Services\CommerceV2\ErpCommerceClient;
use Illuminate\Console\Command;
use Throwable;

class CommerceV2SmokeCommand extends Command
{
    protected $signature = 'commerce-v2:smoke
        {--product=rs_4451 : Public product id}
        {--sku=SP14546158 : Exact SKU search}
        {--collection=moi-nhat : Collection slug}
        {--limit=2 : Small smoke page size}
        {--json : Machine-readable JSON}';

    protected $description = 'Read-only smoke for Lin Xén Storefront V2 → ERP Commerce V2.';

    public function handle(
        ErpCommerceClient $client
    ): int {
        try {
            $limit = max(
                1,
                min(4, (int) $this->option('limit'))
            );
            $productId = (string) $this->option(
                'product'
            );
            $sku = (string) $this->option('sku');
            $collectionSlug = (string) $this->option(
                'collection'
            );

            $configuration = $client
                ->configurationStatus();
            $listing = $client->listing($limit);
            $product = $client->product($productId);
            $search = $client->search(
                $sku,
                $limit
            );
            $collections = $client->collections();
            $collection = $client->collection(
                $collectionSlug,
                $limit
            );

            $listingItems = (array) data_get(
                $listing,
                'data.items',
                []
            );
            $searchItems = (array) data_get(
                $search,
                'data.items',
                []
            );
            $collectionItems = (array) data_get(
                $collection,
                'data.items',
                []
            );

            $checks = [
                'configuration' => (
                    data_get(
                        $configuration,
                        'base_url_configured'
                    ) === true
                    && data_get(
                        $configuration,
                        'site_configured'
                    ) === true
                    && data_get(
                        $configuration,
                        'token_configured'
                    ) === true
                ),
                'listing_non_empty' => count(
                    $listingItems
                ) > 0,
                'product_exact' => data_get(
                    $product,
                    'data.id'
                ) === $productId,
                'search_exact_first' => data_get(
                    $searchItems,
                    '0.id'
                ) === $productId,
                'collections_non_empty' => count(
                    (array) data_get(
                        $collections,
                        'data.items',
                        []
                    )
                ) > 0,
                'collection_exact' => data_get(
                    $collection,
                    'data.collection.slug'
                ) === $collectionSlug,
                'collection_non_empty' => count(
                    $collectionItems
                ) > 0,
            ];

            $ok = ! in_array(false, $checks, true);

            $result = [
                'ok' => $ok,
                'configuration' => $configuration,
                'summary' => [
                    'listing_returned' => count(
                        $listingItems
                    ),
                    'product_id' => data_get(
                        $product,
                        'data.id'
                    ),
                    'search_first' => data_get(
                        $searchItems,
                        '0.id'
                    ),
                    'collections_count' => count(
                        (array) data_get(
                            $collections,
                            'data.items',
                            []
                        )
                    ),
                    'collection_slug' => data_get(
                        $collection,
                        'data.collection.slug'
                    ),
                    'collection_returned' => count(
                        $collectionItems
                    ),
                ],
                'checks' => $checks,
                'mutation' => 'none',
            ];

            if ($this->option('json')) {
                $this->line((string) json_encode(
                    $result,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                ));
            } else {
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['listing_returned', data_get($result, 'summary.listing_returned')],
                        ['product_id', data_get($result, 'summary.product_id')],
                        ['search_first', data_get($result, 'summary.search_first')],
                        ['collections_count', data_get($result, 'summary.collections_count')],
                        ['collection_slug', data_get($result, 'summary.collection_slug')],
                        ['collection_returned', data_get($result, 'summary.collection_returned')],
                    ]
                );

                foreach ($checks as $name => $passed) {
                    $this->line(
                        strtoupper($name)
                        . '='
                        . ($passed ? 'PASS' : 'FAIL')
                    );
                }

                $this->line(
                    'STOREFRONT_V2_SMOKE='
                    . ($ok ? 'PASS' : 'FAIL')
                );
            }

            return $ok
                ? self::SUCCESS
                : self::FAILURE;
        } catch (Throwable $e) {
            report($e);
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
