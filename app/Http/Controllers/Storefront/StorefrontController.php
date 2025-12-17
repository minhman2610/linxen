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
     * 🏠 HOME
     * =====================================================
     */
    public function home()
{
    return view("storefront.{$this->theme}.pages.home");
}



    /**
     * =====================================================
     * 👗 PRODUCT
     * =====================================================
     */
    public function product(ErpStorefrontApi $erp, string $slug)
    {
        try {
            $product = $erp->product($this->brand, $slug);

            abort_if(!$product, 404);

            return view(
                "storefront.{$this->theme}.pages.product",
                compact('product')
            );

        } catch (\Throwable $e) {

            Log::error('[LINXEN][PRODUCT]', [
                'slug' => $slug,
                'message' => $e->getMessage(),
            ]);

            return response()->view('errors.500', [], 500);
        }
    }

    /**
     * =====================================================
     * 📦 COLLECTION
     * =====================================================
     */
    public function collection(ErpStorefrontApi $erp, string $slug)
    {
        try {
            $collection = $erp->collection($this->brand, $slug);

            abort_if(!$collection, 404);

            return view(
                "storefront.{$this->theme}.pages.collection",
                compact('collection')
            );

        } catch (\Throwable $e) {

            Log::error('[LINXEN][COLLECTION]', [
                'slug' => $slug,
                'message' => $e->getMessage(),
            ]);

            return response()->view('errors.500', [], 500);
        }
    }
}
