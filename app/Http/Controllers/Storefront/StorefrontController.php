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
            // hero hiện tại đang dùng video cố định trong blade
            // để sẵn key này cho tương lai
            'hero' => $rawHome['hero'] ?? null,

            // QUAN TRỌNG: map đúng key từ API
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
     * =====================================================
     */
    public function product(string $slug, ErpStorefrontApi $erp)
    {
        $product = $erp->product($this->brand, $slug);

        if (empty($product)) {
            abort(404);
        }

        // Debug khi cần
        // Log::info('[LINXEN][PRODUCT]', $product);

        return view(
            "storefront.{$this->theme}.pages.product",
            [
                'product' => $product,
                'brand'   => $this->brand,
            ]
        );
    }
}
