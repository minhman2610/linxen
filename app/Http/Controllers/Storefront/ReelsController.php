<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ERP\ErpStorefrontApi;

class ReelsController extends Controller
{
    protected string $theme;
    protected string $brand;

    public function __construct()
    {
        $this->theme = config('storefront.theme', 'luxe');
        $this->brand = config('storefront.brand', 'linxen');
    }

    public function index(ErpStorefrontApi $erp)
    {
        $raw = $erp->home($this->brand);

        $products = collect($raw['products'] ?? [])
            ->filter(fn ($p) =>
                !empty($p['product_id'])
                && !empty($p['price'])
                && !empty($p['media']['images'][0])
            )
            ->map(function ($p) {

                $thumb = $p['media']['thumb_mobile']
                    ?? $p['media']['images'][0]
                    ?? null;

                return [
                    // Identity
                    'id'    => $p['product_id'],
                    'sku'   => $p['code'] ?? $p['product_id'],
                    'name'  => $p['name'],

                    // Pricing
                    'price' => $p['price'],

                    // Stock
                    'available' => $p['available'] ?? 0,
                    'tag'       => ($p['available'] ?? 0) > 0
                        ? 'Có sẵn'
                        : 'Đặt trước',

                    // Media
                    'thumb'  => $thumb,
                    'images' => $p['media']['images'],

                    // Optional (future use)
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
