<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ERP\ErpStorefrontApi;   // 🔥 DÒNG QUAN TRỌNG
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

    public function home(ErpStorefrontApi $erp)
{
    $rawHome = $erp->home($this->brand);

    $home = [
        'hero' => $rawHome['hero'] ?? null,
        'featured_products' => $rawHome['products'] ?? [],
    ];

    return view(
        "storefront.{$this->theme}.pages.home",
        compact('home')
    );
}


}
