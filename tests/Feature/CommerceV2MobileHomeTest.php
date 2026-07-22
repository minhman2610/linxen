<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CommerceV2MobileHomeTest extends TestCase
{
    public function test_home_renders_fast_shell_before_catalog_request(): void
    {
        $this->configureCommerce();

        Http::fake(fn (Request $request) => Http::response([
            'ok' => true,
            'data' => [
                'items' => [$this->product('elisa', 'Elisa')],
            ],
            'meta' => [
                'pagination' => [
                    'has_more' => true,
                    'next_cursor' => 'cursor-2',
                ],
            ],
        ]));

        $response = $this->get('/v2');

        $response
            ->assertOk()
            ->assertSee('data-lxhome-experience="video-catalog-v1"', false)
            ->assertSee('data-src="http://localhost/themes/luxe/assets/images/home/herovideo1.mp4"', false)
            ->assertSee('data-lxhome-feed', false)
            ->assertSee('data-lxcv1-search-open', false)
            ->assertSee('data-lxcv1-search-panel', false)
            ->assertSee('Ảnh sản phẩm đã duyệt', false)
            ->assertSee('The Lin Xén Edit', false)
            ->assertSee('luxe-commerce-v1.css?v=5', false)
            ->assertSee('luxe-commerce-v1.js?v=5', false)
            ->assertDontSee('data-lxhome-video-sound', false)
            ->assertDontSee('lxh3-video-hero__shade', false)
            ->assertDontSee('data-lxh2-editorial-story', false)
            ->assertDontSee('data-lxh2-trust', false);

        Http::assertNothingSent();
    }

    public function test_home_products_returns_next_lazy_batch(): void
    {
        $this->configureCommerce();

        Http::fake(fn (Request $request) => Http::response([
            'ok' => true,
            'data' => [
                'items' => [$this->product('xyra', 'Xyra')],
            ],
            'meta' => [
                'pagination' => [
                    'has_more' => false,
                    'next_cursor' => null,
                ],
            ],
        ]));

        $response = $this->getJson(
            '/v2/home/products?cursor=cursor-2'
        );

        $response
            ->assertOk()
            ->assertJsonPath('has_more', false)
            ->assertJsonPath('next_cursor', '')
            ->assertJsonFragment([
                'has_more' => false,
            ]);

        $this->assertStringContainsString(
            'data-lxcv1-product-card',
            (string) $response->json('html')
        );
        $this->assertStringContainsString(
            'loading="lazy"',
            (string) $response->json('html')
        );
        $this->assertStringContainsString(
            'Camily - váy mini cổ tròn',
            (string) $response->json('html')
        );
        $this->assertStringContainsString(
            'xyra-inbox-thumb.webp',
            (string) $response->json('html')
        );
        $this->assertStringNotContainsString(
            'lxcv1-product-card__open',
            (string) $response->json('html')
        );
        $this->assertStringNotContainsString(
            'data-lxcv1-color-step',
            (string) $response->json('html')
        );
        $this->assertStringContainsString(
            'lxcv1-product-card__sizes',
            (string) $response->json('html')
        );
        foreach (['XS', 'S', 'M', 'L', 'XL'] as $size) {
            $this->assertStringContainsString(
                'data-size="'.$size.'"',
                (string) $response->json('html')
            );
        }
        $this->assertStringContainsString(
            'is-unavailable',
            (string) $response->json('html')
        );
        $this->assertStringContainsString(
            'data-sizes="S,M,L"',
            (string) $response->json('html')
        );
        $this->assertSame(
            1,
            substr_count(
                (string) $response->json('html'),
                'aria-label="Xem Đen"'
            )
        );

        Http::assertSent(fn (Request $request) => (
            str_contains($request->url(), '/catalog/products?')
            && str_contains($request->url(), 'limit=8')
            && str_contains($request->url(), 'cursor=cursor-2')
        ));
    }

    private function configureCommerce(): void
    {
        config([
            'commerce_v2.base_url' => 'https://commerce.example.test/api',
            'commerce_v2.site' => 'linxen',
            'commerce_v2.token' => 'testing-token',
        ]);
    }

    private function product(string $slug, string $name): array
    {
        return [
            'id' => 'rs_'.$slug,
            'code' => 'RS-'.strtoupper($slug),
            'slug' => $slug,
            'name' => 'Camily - váy mini cổ tròn',
            'short_name' => $name,
            'cover' => [
                'url' => "https://cdn.example.test/{$slug}.jpg",
            ],
            'listing_media' => [
                'policy' => 'linxen_home_job_category_priority_v1',
                'selected_job_category' => 'SALES_INBOX_SUPPORT',
                'items' => [[
                    'id' => "{$slug}-inbox",
                    'url' => "https://cdn.example.test/{$slug}-inbox.jpg",
                    'thumb_url' => "https://cdn.example.test/{$slug}-inbox-thumb.webp",
                    'job_category' => 'SALES_INBOX_SUPPORT_SINGLE',
                    'color_id' => 'pvg_black',
                    'color_label' => 'Đen',
                    'color_hex' => '#111111',
                ], [
                    'id' => "{$slug}-black-detail-inbox",
                    'url' => "https://cdn.example.test/{$slug}-black-detail-inbox.jpg",
                    'thumb_url' => "https://cdn.example.test/{$slug}-black-detail-inbox-thumb.webp",
                    'job_category' => 'SALES_INBOX_SUPPORT_SINGLE',
                    'color_id' => 'pvg_black',
                    'color_label' => 'Đen',
                    'color_hex' => '#111111',
                ], [
                    'id' => "{$slug}-red-inbox",
                    'url' => "https://cdn.example.test/{$slug}-red-inbox.jpg",
                    'thumb_url' => "https://cdn.example.test/{$slug}-red-inbox-thumb.webp",
                    'job_category' => 'SALES_INBOX_SUPPORT_SINGLE',
                    'color_id' => 'pvg_red',
                    'color_label' => 'Đỏ',
                    'color_hex' => '#8f1f2b',
                ]],
            ],
            'price' => [
                'min' => 799000,
                'max' => 799000,
                'original_min' => 899000,
                'has_sale' => true,
                'is_range' => false,
            ],
            'availability' => [
                'available_total' => 8,
                'in_stock' => true,
            ],
            'colors' => [[
                'id' => 'pvg_black',
                'code' => 'BLACK',
                'label' => 'Đen',
                'hex' => '#111111',
                'sellable' => true,
                'available' => 4,
                'cover' => [
                    'url' => "https://cdn.example.test/{$slug}.jpg",
                ],
                'available_sizes' => ['M', 'L'],
                'size_options' => [[
                    'size' => 'XS',
                    'in_stock' => false,
                    'available' => 0,
                ], [
                    'size' => 'S',
                    'in_stock' => true,
                    'available' => 1,
                ], [
                    'size' => 'M',
                    'in_stock' => true,
                    'available' => 2,
                ], [
                    'size' => 'L',
                    'in_stock' => true,
                    'available' => 1,
                ], [
                    'size' => 'XL',
                    'in_stock' => false,
                    'available' => 0,
                ]],
            ], [
                'id' => 'pvg_red',
                'code' => 'RED',
                'label' => 'Đỏ',
                'hex' => '#8f1f2b',
                'sellable' => true,
                'available' => 4,
                'cover' => [
                    'url' => "https://cdn.example.test/{$slug}-red.jpg",
                ],
                'available_sizes' => ['S', 'M'],
            ]],
        ];
    }
}
