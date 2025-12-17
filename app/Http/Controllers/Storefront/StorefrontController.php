<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;

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



}
