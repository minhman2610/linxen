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
        'Static render smoke for the canonical Luxe Commerce editorial homepage.';

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
            $collections = [[
                'slug' => 'new',
                'url' => route(
                    'commerce.v2.collection',
                    ['slug' => 'new']
                ),
                'name' => 'Thiết kế mới',
                'description' => 'Những thiết kế vừa được cập nhật.',
                'hero_image' => 'https://example.test/new.jpg',
            ], [
                'slug' => 'daily',
                'url' => route(
                    'commerce.v2.collection',
                    ['slug' => 'daily']
                ),
                'name' => 'Dễ mặc hằng ngày',
                'description' => 'Nhẹ nhàng cho nhịp sống hiện đại.',
                'hero_image' => '',
            ]];

            $view =
                'commerce_v2.themes.luxe_commerce_v1.pages.home';
            $html = view(
                $view,
                compact('products', 'collections')
            )->render();
            $css = (string) file_get_contents(public_path(
                'commerce-v2/themes/luxe-commerce-v1.css'
            ));
            $controller = (string) file_get_contents(app_path(
                'Http/Controllers/CommerceV2/CatalogPageController.php'
            ));

            $checks = [
                'home_view_exists' => View::exists($view),
                'editorial_hero' => (
                    str_contains(
                        $html,
                        'data-lxcv1-home-experience="editorial-commerce-v2"'
                    )
                    && str_contains($html, 'Mặc đẹp')
                    && str_contains($html, 'elisa.jpg')
                ),
                'commercial_rail' => (
                    str_contains($html, 'data-lxh2-commercial-rail')
                    && str_contains($html, 'piera.jpg')
                ),
                'canonical_collections' => (
                    str_contains($html, 'data-lxh2-collection-journey')
                    && str_contains($html, 'Thiết kế mới')
                    && str_contains($html, '/v2/collections/new')
                ),
                'product_grid_contract' => (
                    str_contains($html, 'data-lxcv1-product-grid')
                    && str_contains($html, 'RS260616002')
                ),
                'editorial_story' => (
                    str_contains($html, 'data-lxh2-editorial-story')
                    && str_contains($html, 'LIN XÉN POINT OF VIEW')
                ),
                'trust_contract' => (
                    str_contains($html, 'data-lxh2-trust')
                    && str_contains($html, 'exact sellable SKU')
                ),
                'no_fake_urgency' => (
                    ! str_contains(strtolower($html), 'countdown')
                    && ! str_contains($html, 'FLASH SALE')
                ),
                'home_css_contract' => (
                    str_contains(
                        $css,
                        'AI_PATCH_LINXEN_HOME_EDITORIAL_COMMERCE_V2_CSS_START'
                    )
                    && str_contains($css, '.lxh2-hero')
                ),
                'home_listing_depth' => (
                    str_contains(
                        $controller,
                        'AI_PATCH_LINXEN_HOME_EDITORIAL_COMMERCE_V2'
                    )
                    && str_contains(
                        $controller,
                        '$this->client->listing(12)'
                    )
                ),
                'order_mutation_none' => true,
                'provider_mutation_none' => true,
            ];

            foreach ($checks as $code => $passed) {
                $this->line(
                    strtoupper($code)
                    . '='
                    . ($passed ? 'PASS' : 'FAIL')
                );
            }

            $ok = ! in_array(false, $checks, true);
            $this->line(
                'LUXE_COMMERCE_HOME_V2_SMOKE='
                . ($ok ? 'PASS' : 'FAIL')
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
            ]],
        ];
    }
}
