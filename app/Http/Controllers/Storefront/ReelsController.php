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
        // Tận dụng home API (sau này tách riêng cũng OK)
        $raw = $erp->home($this->brand);

        $products = collect($raw['products'] ?? [])
            ->filter(fn ($p) =>
                !empty($p['product_id'])
                && !empty($p['price'])
                && !empty($p['media']['images'][0])
            )
            ->map(function ($p) {
                return [
                    'id'     => $p['product_id'],
                    'name'   => $p['name'],
                    'price'  => $p['price'],
                    'images' => $p['media']['images'],
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
