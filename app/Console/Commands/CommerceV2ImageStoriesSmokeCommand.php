<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Throwable;

final class CommerceV2ImageStoriesSmokeCommand extends Command
{
    protected $signature = 'commerce-v2:image-stories-smoke';

    protected $description =
        'Static contract smoke for the image-first vertical Stories experience.';

    public function handle(): int
    {
        try {
            $products = [$this->product()];
            $view =
                'commerce_v2.themes.luxe_commerce_v1.pages.video';
            $html = view($view, compact('products'))->render();
            $css = (string) file_get_contents(public_path(
                'commerce-v2/themes/luxe-commerce-v1.css'
            ));
            $javascript = (string) file_get_contents(public_path(
                'commerce-v2/themes/luxe-commerce-v1.js'
            ));
            $controller = (string) file_get_contents(app_path(
                'Http/Controllers/CommerceV2/VideoController.php'
            ));

            $checks = [
                'video_route' => Route::has('commerce.v2.video'),
                'story_view_exists' => View::exists($view),
                'image_story_contract' => (
                    str_contains(
                        $html,
                        'data-lxstory-experience="image-stories-v1"'
                    )
                    && str_contains($html, 'data-lxstory-feed')
                    && str_contains($html, 'data-lxstory-item')
                ),
                'image_only_media' => (
                    str_contains($html, 'elisa-cover.jpg')
                    && str_contains($html, 'elisa-red.jpg')
                    && ! str_contains($html, '<video')
                    && ! str_contains($html, '.mp4')
                ),
                'product_truth' => (
                    str_contains($html, 'RS260616002')
                    && str_contains($html, '799.000₫')
                    && str_contains($html, '/v2/p/elisa')
                ),
                'vertical_snap' => (
                    str_contains($css, 'scroll-snap-type: y mandatory')
                    && str_contains($css, '.lxstory-item')
                ),
                'story_runtime' => (
                    str_contains($javascript, '[data-lxstory-feed]')
                    && str_contains($javascript, 'IntersectionObserver')
                    && str_contains($javascript, '4000')
                ),
                'catalog_fallback' => (
                    str_contains($controller, "discover('de-xuat', 12)")
                    && str_contains($controller, 'listing(12)')
                ),
                'no_commerce_mutation' => (
                    ! str_contains($controller, 'validateCart(')
                    && ! str_contains($controller, 'commitOrder(')
                ),
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
                'LINXEN_IMAGE_STORIES_SMOKE='
                .($ok ? 'PASS' : 'FAIL')
            );

            return $ok ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $e) {
            report($e);
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function product(): array
    {
        return [
            'id' => 'rs_4477',
            'code' => 'RS260616002',
            'slug' => 'elisa',
            'url' => route(
                'commerce.v2.product',
                ['slug' => 'elisa']
            ),
            'name' => 'Elisa',
            'short_name' => 'Elisa',
            'cover_url' => 'https://example.test/elisa-cover.jpg',
            'cover_alt' => 'Elisa',
            'price_min' => 799000,
            'price_max' => 799000,
            'original_min' => 899000,
            'has_sale' => true,
            'is_range' => false,
            'available_total' => 8,
            'in_stock' => true,
            'colors' => [[
                'id' => 'pvg_black',
                'code' => 'BLACK',
                'label' => 'Đen',
                'hex' => '#111111',
                'sellable' => true,
                'available' => 4,
                'cover_url' => 'https://example.test/elisa-cover.jpg',
                'available_sizes' => ['M', 'L'],
            ], [
                'id' => 'pvg_red',
                'code' => 'RED',
                'label' => 'Đỏ',
                'hex' => '#8f1f2b',
                'sellable' => true,
                'available' => 4,
                'cover_url' => 'https://example.test/elisa-red.jpg',
                'available_sizes' => ['S', 'M'],
            ]],
        ];
    }
}
