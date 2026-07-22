<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CommerceV2ImageStoriesTest extends TestCase
{
    public function test_image_stories_renders_without_video_media(): void
    {
        config([
            'commerce_v2.base_url' => 'https://commerce.example.test/api',
            'commerce_v2.site' => 'linxen',
            'commerce_v2.token' => 'testing-token',
        ]);

        Http::fake([
            'commerce.example.test/*' => Http::response([
                'ok' => true,
                'data' => [
                    'items' => [$this->product()],
                ],
                'meta' => [
                    'request_id' => 'req_image_stories_test',
                ],
            ]),
        ]);

        $response = $this->get('/v2/video');

        $response
            ->assertOk()
            ->assertSee('data-lxstory-experience="image-stories-v1"', false)
            ->assertSee('Elisa')
            ->assertSee('799.000₫')
            ->assertSee('elisa-red.jpg', false)
            ->assertDontSee('<video', false);

        Http::assertSent(fn ($request) => (
            str_contains(
                $request->url(),
                '/sites/linxen/discover?'
            )
            && str_contains($request->url(), 'feed=de-xuat')
            && str_contains($request->url(), 'limit=12')
        ));
    }

    private function product(): array
    {
        return [
            'id' => 'rs_4477',
            'code' => 'RS260616002',
            'slug' => 'elisa',
            'name' => 'Elisa',
            'short_name' => 'Elisa',
            'cover' => [
                'url' => 'https://cdn.example.test/elisa-cover.jpg',
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
                    'url' => 'https://cdn.example.test/elisa-cover.jpg',
                ],
                'available_sizes' => ['M', 'L'],
            ], [
                'id' => 'pvg_red',
                'code' => 'RED',
                'label' => 'Đỏ',
                'hex' => '#8f1f2b',
                'sellable' => true,
                'available' => 4,
                'cover' => [
                    'url' => 'https://cdn.example.test/elisa-red.jpg',
                ],
                'available_sizes' => ['S', 'M'],
            ]],
        ];
    }
}
