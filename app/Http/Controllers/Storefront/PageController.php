<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ERP\ErpStorefrontApi;
use Illuminate\Http\Request;

class PageController extends Controller
{
    protected string $theme;
    protected string $brand;

    public function __construct()
    {
        $this->theme = config('storefront.theme', 'luxe');
        $this->brand = config('storefront.brand', 'linxen');
    }

    /* =====================================================
     * 🏠 HOME
     * ===================================================== */
    public function home(ErpStorefrontApi $erp)
    {
        $rawHome = $erp->home($this->brand);

        $home = [
            'hero'              => $rawHome['hero'] ?? null,
            'featured_products' => $rawHome['products'] ?? [],
        ];

        return view(
            "storefront.{$this->theme}.pages.home",
            compact('home')
        );
    }

    /* =====================================================
     * 🔍 SEARCH (placeholder)
     * ===================================================== */
    public function search()
    {
        return view("storefront.{$this->theme}.pages.search");
    }

    /* =====================================================
     * 👗 PRODUCT DETAIL
     * ===================================================== */
    public function product(string $slug, ErpStorefrontApi $erp)
    {
        $product = $erp->product($this->brand, $slug);

        if (empty($product)) {
            abort(404);
        }

        // Chuẩn hoá dữ liệu cho blade
        $variants   = $product['variants'] ?? [];
        $attributes = $product['attributes'] ?? [];

        $photos = $product['images'] ?? [];
        $mainImage = $photos[0] ?? ($product['thumb_url'] ?? null);

        return view(
            "storefront.{$this->theme}.pages.product",
            [
                'product'    => $product,
                'variants'   => $variants,
                'attributes' => $attributes,
                'photos'     => $photos,
                'mainImage'  => $mainImage,
                'brand'      => $this->brand,
            ]
        );
    }

    /* =====================================================
     * 📦 COLLECTION / CATEGORY
     * ===================================================== */
    public function collection(string $slug, ErpStorefrontApi $erp)
    {
        $data = $erp->collection($this->brand, $slug);

        return view(
            "storefront.{$this->theme}.pages.collection",
            [
                'collection' => $data['collection'] ?? null,
                'products'   => $data['products'] ?? [],
                'brand'      => $this->brand,
            ]
        );
    }

    /* =====================================================
     * 🛒 CART
     * ===================================================== */
    public function cart()
    {
        $cart = session('cart', []);

        return view(
            "storefront.{$this->theme}.pages.cart",
            compact('cart')
        );
    }

    /**
     * ➕ ADD TO CART (AJAX – SESSION)
     */
    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'sku'   => 'required|string',
            'name'  => 'required|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'qty'   => 'required|integer|min:1',
            'attrs' => 'nullable|array',
        ]);

        $cart = session()->get('cart', []);

        if (isset($cart[$data['sku']])) {
            $cart[$data['sku']]['qty'] += $data['qty'];
        } else {
            $cart[$data['sku']] = [
                'sku'   => $data['sku'],
                'name'  => $data['name'],
                'price' => $data['price'],
                'image' => $data['image'] ?? null,
                'qty'   => $data['qty'],
                'attrs' => $data['attrs'] ?? [],
            ];
        }

        session()->put('cart', $cart);

        return response()->json([
            'success'    => true,
            'message'    => 'Đã thêm vào giỏ hàng',
            'cart_count' => array_sum(array_column($cart, 'qty')),
        ]);
    }

    /**
     * 🔄 UPDATE QTY (AJAX)
     */
    public function updateCart(Request $request)
    {
        $data = $request->validate([
            'sku' => 'required|string',
            'qty' => 'required|integer|min:1',
        ]);

        $cart = session()->get('cart', []);

        if (isset($cart[$data['sku']])) {
            $cart[$data['sku']]['qty'] = $data['qty'];
            session()->put('cart', $cart);
        }

        return response()->json([
            'success'    => true,
            'cart_count' => array_sum(array_column($cart, 'qty')),
        ]);
    }

    /**
     * ❌ REMOVE ITEM (AJAX)
     */
    public function removeFromCart(Request $request)
    {
        $data = $request->validate([
            'sku' => 'required|string',
        ]);

        $cart = session()->get('cart', []);
        unset($cart[$data['sku']]);

        session()->put('cart', $cart);

        return response()->json([
            'success'    => true,
            'cart_count' => array_sum(array_column($cart, 'qty')),
        ]);
    }

    /* =====================================================
     * 💳 CHECKOUT (placeholder)
     * ===================================================== */
    public function checkout()
    {
        $cart = session('cart', []);

        return view(
            "storefront.{$this->theme}.pages.checkout",
            compact('cart')
        );
    }

    public function placeOrder(Request $request)
    {
        // TODO: tạo đơn hàng + sync ERP
        session()->forget('cart');

        return redirect()
            ->route('linxen.home')
            ->with('success', 'Đặt hàng thành công');
    }

    /* =====================================================
     * 👤 ACCOUNT (placeholder)
     * ===================================================== */
    public function account()
    {
        return view("storefront.{$this->theme}.pages.account");
    }

    public function orders()
    {
        return view("storefront.{$this->theme}.pages.orders");
    }

    public function orderDetail(string $code)
    {
        return view(
            "storefront.{$this->theme}.pages.order-detail",
            compact('code')
        );
    }
}
