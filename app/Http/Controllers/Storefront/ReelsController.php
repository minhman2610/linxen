<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ERP\ErpStorefrontApi;
use Illuminate\Support\Str;

class ReelsController extends Controller
{
    protected string $theme;
    protected string $brand;

    public function __construct()
    {
        $this->theme = config('storefront.theme', 'luxe');
        $this->brand = config('storefront.brand', 'linxen');
    }

    /**
     * =====================================================
     * REELS – IMAGE PRODUCT FEED
     * =====================================================
     */
    public function index(ErpStorefrontApi $erp)
    {
        // Tạm dùng home feed (sau này tách API riêng cho reels)
        $raw = $erp->home($this->brand);

        $products = collect($raw['products'] ?? [])
            ->filter(fn ($p) =>
                !empty($p['product_id']) &&
                !empty($p['price']) &&
                !empty($p['media']['images'][0])
            )
            ->map(function ($p) {

                /* ===============================
                   MEDIA
                   =============================== */
                $images = array_values(
                    array_filter($p['media']['images'] ?? [])
                );

                $thumb = $p['media']['thumb_mobile']
                    ?? $p['media']['thumb']
                    ?? $images[0]
                    ?? null;

                /* ===============================
                   IDENTITY
                   =============================== */
                $id   = (int) $p['product_id'];
                $name = (string) $p['name'];

                // 🔥 SLUG CHUẨN – DÙNG CHO LINK CHI TIẾT
                $slug = Str::slug($name) . '-' . $id;

                /* ===============================
                   META / DESC (AN TOÀN)
                   =============================== */
                $desc = $p['short_desc']
                    ?? $p['description']
                    ?? '';

                return [
                    /* ===============================
                       IDENTITY
                       =============================== */
                    'id'   => $id,
                    'sku'  => $p['code'] ?? (string) $id,
                    'name' => $name,
                    'slug' => $slug,

                    /* ===============================
                       PRICING
                       =============================== */
                    'price' => (float) $p['price'],

                    /* ===============================
                       STOCK / TAG
                       =============================== */
                    'available' => (int) ($p['available'] ?? 0),
                    'tag' => ($p['available'] ?? 0) > 0
                        ? 'Có sẵn'
                        : 'Đặt trước',

                    /* ===============================
                       MEDIA
                       =============================== */
                    'thumb'  => $thumb,
                    'images' => $images,

                    /* ===============================
                       DESC (REELS BAR)
                       =============================== */
                    'desc' => trim($desc),

                    /* ===============================
                       META (RESERVE)
                       =============================== */
                    'brand' => $this->brand,
                ];
            })
            ->values()
            ->toArray();

        return view(
            "storefront.{$this->theme}.pages.reels",
            compact('products')
        );
    }
}
