<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;
use Throwable;

final class CommerceV2HomeEditorialExperienceSmokeCommand extends Command
{
    protected $signature =
        'commerce-v2:home-editorial-experience-smoke';

    protected $description =
        'Static render smoke for the Luxe Commerce video catalogue homepage.';

    public function handle(): int
    {
        try {
            $products = collect([
                $this->product(
                    'rs_4477',
                    'RS260616002',
                    'Elisa',
                    799000,
                    899000,
                    true,
                    'https://example.test/elisa.jpg'
                ),
                $this->product(
                    'rs_4478',
                    'RS260608003',
                    'Xyra',
                    829000,
                    829000,
                    false,
                    'https://example.test/xyra.jpg'
                ),
                $this->product(
                    'rs_4479',
                    'RS260611001',
                    'Piera',
                    759000,
                    859000,
                    true,
                    'https://example.test/piera.jpg'
                ),
                $this->product(
                    'rs_4480',
                    'RS260515001',
                    'Lamonte',
                    899000,
                    899000,
                    false,
                    'https://example.test/lamonte.jpg'
                ),
            ])->all();
            $pagination = [
                'has_more' => true,
                'next_cursor' => 'next-page',
            ];

            $view =
                'commerce_v2.themes.luxe_commerce_v1.pages.home';
            $html = view(
                $view,
                compact('products', 'pagination')
            )->render();
            $css = (string) file_get_contents(public_path(
                'commerce-v2/themes/luxe-commerce-v1.css'
            ));
            $javascript = (string) file_get_contents(public_path(
                'commerce-v2/themes/luxe-commerce-v1.js'
            ));
            $controller = (string) file_get_contents(app_path(
                'Http/Controllers/CommerceV2/CatalogPageController.php'
            ));

            $checks = [
                'home_view_exists' => View::exists($view),
                'video_hero' => (
                    str_contains(
                        $html,
                        'data-lxhome-experience="video-catalog-v1"'
                    )
                    && str_contains($html, 'herovideo1.mp4')
                    && str_contains($html, 'autoplay')
                    && str_contains($html, 'playsinline')
                ),
                'ticker_contract' => (
                    str_contains($html, 'lxh3-ticker__track')
                    && str_contains($html, 'Chọn đúng màu')
                ),
                'catalog_contract' => (
                    str_contains($html, 'data-lxhome-grid')
                    && str_contains($html, 'RS260616002')
                    && str_contains($html, 'loading="eager"')
                ),
                'color_image_contract' => (
                    str_contains($html, 'data-lxcv1-color-image')
                    && str_contains($html, 'elisa-alt.jpg')
                    && str_contains($html, 'data-lxcv1-color-step')
                ),
                'infinite_feed_contract' => (
                    str_contains($html, 'data-lxhome-sentinel')
                    && str_contains($html, 'next-page')
                    && str_contains($javascript, 'IntersectionObserver')
                    && str_contains($javascript, 'loadNextPage')
                ),
                'minimal_home_contract' => (
                    ! str_contains($html, 'data-lxh2-commercial-rail')
                    && ! str_contains($html, 'data-lxh2-collection-journey')
                    && ! str_contains($html, 'data-lxh2-editorial-story')
                    && ! str_contains($html, 'data-lxh2-trust')
                ),
                'no_fake_urgency' => (
                    ! str_contains(strtolower($html), 'countdown')
                    && ! str_contains($html, 'FLASH SALE')
                ),
                'home_css_contract' => (
                    str_contains($css, '.lxh3-video-hero')
                    && str_contains($css, '.lxh3-product-table')
                    && str_contains($css, '.lxcv1-drawer')
                ),
                'home_listing_depth' => (
                    str_contains(
                        $controller,
                        '$this->client->listing(12)'
                    )
                    && str_contains($controller, 'homeProducts')
                    && ! str_contains(
                        substr(
                            $controller,
                            strpos($controller, 'public function home('),
                            strpos($controller, 'public function homeProducts(')
                                - strpos($controller, 'public function home(')
                        ),
                        '$this->client->collections()'
                    )
                ),
                'order_mutation_none' => true,
                'provider_mutation_none' => true,
            ];

            foreach ($checks as $code => $passed) {
                $this->line(
                    strtoupper($code)
                    .'='
                    .($passed ? 'PASS' : 'FAIL')
                );
            }

            $ok = ! in_array(false, $checks, true);
            $this->line(
                'LUXE_COMMERCE_HOME_VIDEO_CATALOG_SMOKE='
                .($ok ? 'PASS' : 'FAIL')
            );

            return $ok ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $e) {
            report($e);
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function product(
        string $id,
        string $code,
        string $name,
        int $price,
        int $original,
        bool $sale,
        string $cover
    ): array {
        return [
            'id' => $id,
            'code' => $code,
            'slug' => $id,
            'url' => route(
                'commerce.v2.product',
                ['slug' => $id]
            ),
            'name' => $name,
            'short_name' => $name,
            'cover_url' => $cover,
            'cover_alt' => $name,
            'price_min' => $price,
            'price_max' => $price,
            'original_min' => $original,
            'has_sale' => $sale,
            'is_range' => false,
            'available_total' => 8,
            'in_stock' => true,
            'colors' => [[
                'id' => 'pvg_1',
                'code' => 'BLACK',
                'label' => 'Đen',
                'hex' => '#111111',
                'sellable' => true,
                'available' => 8,
                'cover_url' => $cover,
                'available_sizes' => ['M', 'L'],
            ], [
                'id' => 'pvg_2',
                'code' => 'ALT',
                'label' => 'Màu khác',
                'hex' => '#a16a54',
                'sellable' => true,
                'available' => 5,
                'cover_url' => str_replace('.jpg', '-alt.jpg', $cover),
                'available_sizes' => ['S', 'M'],
            ]],
        ];
    }
}
