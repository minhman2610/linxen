<?php

namespace App\Http\Controllers\CommerceV2;

use App\Http\Controllers\Controller;
use App\Services\CommerceV2\CommerceV2Presenter;
use App\Services\CommerceV2\ErpCommerceClient;
use Illuminate\View\View;
use Throwable;

final class VideoController extends Controller
{
    public function __construct(
        private readonly ErpCommerceClient $client,
        private readonly CommerceV2Presenter $presenter
    ) {}

    public function index(): View
    {
        $items = [];
        $error = null;

        try {
            $items = (array) data_get(
                $this->client->discover('de-xuat', 12),
                'data.items',
                []
            );
        } catch (Throwable $discoverError) {
            report($discoverError);

            try {
                $items = (array) data_get(
                    $this->client->listing(12),
                    'data.items',
                    []
                );
            } catch (Throwable $listingError) {
                report($listingError);
                $error = 'LIN XÉN Stories đang được cập nhật.';
            }
        }

        $products = collect($items)
            ->map(fn ($item) => $this->presenter
                ->productSummary((array) $item))
            ->filter(fn ($product) => (
                $product['id'] !== ''
                && $product['name'] !== ''
                && $product['cover_url'] !== ''
            ))
            ->take(12)
            ->values()
            ->all();

        return view('commerce_v2.pages.video', [
            'products' => $products,
            'videoError' => $error,
            'pageTitle' => 'LIN XÉN Stories — Khám phá bằng hình ảnh',
            'pageDescription' => 'Vuốt dọc để khám phá thiết kế LIN XÉN qua những câu chuyện bằng hình ảnh.',
        ]);
    }
}
