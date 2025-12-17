<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ERP\ErpStorefrontApi;
use Illuminate\Support\Facades\Log;

class StorefrontController extends Controller
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
     * 🏠 HOME PAGE
     * =====================================================
     */
    public function home(ErpStorefrontApi $erp)
    {
        $rawHome = $erp->home($this->brand);

        // Normalize data cho Blade
        $home = [
            // để sẵn cho tương lai (hiện hero video hard-code trong blade)
            'hero' => $rawHome['hero'] ?? null,

            // QUAN TRỌNG: ERP trả products → map sang featured_products
            'featured_products' => $rawHome['products'] ?? [],
        ];

        // Debug khi cần
        // Log::info('[LINXEN][HOME]', $home);

        return view(
            "storefront.{$this->theme}.pages.home",
            compact('home')
        );
    }

    /**
     * =====================================================
     * 👗 PRODUCT DETAIL
     * slug format: ten-san-pham-product_id
     * =====================================================
     */
    public function product(string $slug, ErpStorefrontApi $erp)
    {
        // Chỉ forward slug sang ERP
        // ERP sẽ tự parse product_id ở cuối slug
        $product = $erp->product($this->brand, $slug);

        if (empty($product)) {
            abort(404);
        }

        // Debug khi cần
        // Log::info('[LINXEN][PRODUCT]', [
        //     'slug'    => $slug,
        //     'product' => $product,
        // ]);

        return view(
            "storefront.{$this->theme}.pages.product",
            [
                'product' => $product,
                'brand'   => $this->brand,
            ]
        );
    }
}
