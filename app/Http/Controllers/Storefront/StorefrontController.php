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
    $home = $erp->home($this->brand);

    Log::error('[LINXEN][HOME_API]', [
        'brand' => $this->brand,
        'home'  => $home,
    ]);

    return view(
        "storefront.{$this->theme}.pages.home",
        compact('home')
    );
}

}
