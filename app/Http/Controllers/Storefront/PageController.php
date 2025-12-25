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
 * 🏠 HOME – LIN XÉN STORE (MEDIA-SAFE)
 * ===================================================== */
public function home(ErpStorefrontApi $erp)
{
    // Lấy dữ liệu home từ ERP
    $rawHome = $erp->home($this->brand);

    // Chuẩn hoá data cho view
    $home = [
        // HERO (video / banner / null đều OK)
        'hero' => $rawHome['hero'] ?? null,

        // SẢN PHẨM CHỦ LỰC
        'featured_products' => collect($rawHome['products'] ?? [])

            // 1️⃣ Lọc sản phẩm hợp lệ cơ bản
            ->filter(fn ($p) =>
                !empty($p['product_id'])
                && !empty($p['name'])
                && !empty($p['price'])
            )

            // 2️⃣ Chuẩn hoá MEDIA → 1 KEY DUY NHẤT
            ->map(function ($p) {

                $thumb = null;

                // Ưu tiên thứ tự: images → thumb → mobile
                if (!empty($p['media']['images'][0])) {
                    $thumb = $p['media']['images'][0];
                } elseif (!empty($p['media']['thumb'])) {
                    $thumb = $p['media']['thumb'];
                } elseif (!empty($p['media']['mobile'])) {
                    $thumb = $p['media']['mobile'];
                }

                return [
                    ...$p,

                    // 🔑 KEY DUY NHẤT CHO BLADE
                    'thumb' => $thumb,
                ];
            })

            // 3️⃣ Chỉ giữ sản phẩm có ảnh hợp lệ
            ->filter(fn ($p) => !empty($p['thumb']))

            // 4️⃣ Reset index
            ->values()
            ->toArray(),
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
 * 👗 PRODUCT DETAIL – LIN XÉN
 * Lấy dữ liệu từ ERP Storefront API
 * ===================================================== */
public function product(string $slug, ErpStorefrontApi $erp)
{
    $product = $erp->product($this->brand, $slug);

    if (empty($product) || !is_array($product)) {
        abort(404);
    }

    /*
    |--------------------------------------------------------------------------
    | VARIANTS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */
    $variants   = $product['variants']   ?? [];
    $attributes = $product['attributes'] ?? [];

    /*
    |--------------------------------------------------------------------------
    | MEDIA – STOREFRONT (PROGRESSIVE GALLERY)
    |--------------------------------------------------------------------------
    */
    $images = $product['images'] ?? [];

    $mainImage = $images[0]['mobile']
        ?? $product['thumb_mobile']
        ?? $product['thumb_url']
        ?? null;

    /*
    |--------------------------------------------------------------------------
    | 👥 REAL CUSTOMER MEDIA (UGC FROM ERP)
    |--------------------------------------------------------------------------
    */
    $ugcMedia = isset($product['real_media_gallery'])
        && is_array($product['real_media_gallery'])
        ? $product['real_media_gallery']
        : [];

    $ugcCount = (int) (
        $product['real_media_count']
        ?? count($ugcMedia)
    );

    /*
    |--------------------------------------------------------------------------
    | 🧭 BREADCRUMB – CATEGORY TREE
    |--------------------------------------------------------------------------
    */
    $breadcrumbs = [];

    if (!empty($product['categories']) && is_array($product['categories'])) {

        foreach ($product['categories'] as $cat) {
            $breadcrumbs[] = [
                'name' => $cat['name'],
                'url'  => '/collection/' . $cat['slug'],
            ];
        }

    } else {
        $breadcrumbs = [
            [
                'name' => 'Sản phẩm',
                'url'  => '/collections',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 🔁 SUGGESTED PRODUCTS (FROM ERP)
    |--------------------------------------------------------------------------
    | 🔴 FIX QUAN TRỌNG:
    | ERP trả snake_case → LIN XÉN PHẢI ĐỌC ĐÚNG KEY
    |--------------------------------------------------------------------------
    */
    $suggestedProducts = isset($product['suggested_products'])
        && is_array($product['suggested_products'])
        ? $product['suggested_products']
        : [];

    $suggestedCount = (int) (
        $product['suggested_count']
        ?? count($suggestedProducts)
    );

    /*
    |--------------------------------------------------------------------------
    | 🔍 DEBUG TẠM (CÓ THỂ XOÁ SAU)
    |--------------------------------------------------------------------------
    */
    /*
    \Log::debug('[LINXEN PDP]', [
        'suggested_count' => $suggestedCount,
        'suggested_products' => $suggestedProducts,
    ]);
    */

    /*
    |--------------------------------------------------------------------------
    | RENDER VIEW
    |--------------------------------------------------------------------------
    */
    return view(
        "storefront.{$this->theme}.pages.product",
        [
            // 🧾 DATA GỐC ERP
            'product'           => $product,

            // 🎛 BIẾN THỂ
            'variants'          => $variants,
            'attributes'        => $attributes,

            // 🖼 GALLERY CHÍNH
            'images'            => $images,
            'mainImage'         => $mainImage,

            // 👥 REAL MEDIA (UGC)
            'ugcMedia'          => $ugcMedia,
            'ugcCount'          => $ugcCount,

            // 🔁 SẢN PHẨM GỢI Ý (KEY ĐÃ ĐÚNG)
            'suggestedProducts' => $suggestedProducts,
            'suggestedCount'    => $suggestedCount,

            // 🧭 BREADCRUMB
            'breadcrumbs'       => $breadcrumbs,

            // 🏷 BRAND
            'brand'             => $this->brand,
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
 * 🔄 UPDATE CART QTY (AJAX)
 * Nhận: sku + delta (+1 / -1)
 */
public function updateCart(Request $request)
{
    $data = $request->validate([
        'sku'   => 'required|string',
        'delta' => 'required|integer|in:-1,1',
    ]);

    $cart = session('cart', []);

    // SKU không tồn tại
    if (!isset($cart[$data['sku']])) {
        return response()->json([
            'success' => false,
            'message' => 'Sản phẩm không tồn tại trong giỏ hàng',
        ], 404);
    }

    // Update qty
    $cart[$data['sku']]['qty'] += $data['delta'];

    // Nếu qty <= 0 → xoá sản phẩm
    if ($cart[$data['sku']]['qty'] <= 0) {
        unset($cart[$data['sku']]);
    }

    // Lưu session
    session(['cart' => $cart]);

    return response()->json([
        'success'    => true,
        'cart'       => $cart,
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

    $cart = session('cart', []);

    // Nếu SKU không tồn tại → vẫn success (idempotent)
    if (!isset($cart[$data['sku']])) {
        return response()->json([
            'success'    => true,
            'cart'       => $cart,
            'cart_count' => array_sum(array_column($cart, 'qty')),
        ]);
    }

    unset($cart[$data['sku']]);

    session(['cart' => $cart]);

    return response()->json([
        'success'    => true,
        'cart'       => $cart,
        'cart_count' => array_sum(array_column($cart, 'qty')),
    ]);
}


    /* =====================================================
 * 💳 CHECKOUT
 * ===================================================== */
public function checkout()
{
    $cart = session('cart', []);

    /*
    |--------------------------------------------------------------------------
    | 1️⃣ Giỏ hàng trống → quay về trang giỏ
    |--------------------------------------------------------------------------
    */
    if (empty($cart) || !is_array($cart)) {
        return redirect()
            ->route('linxen.cart')
            ->with('warning', 'Giỏ hàng của bạn đang trống.');
    }

    /*
    |--------------------------------------------------------------------------
    | 2️⃣ Chuẩn hoá & validate dữ liệu giỏ hàng
    |    (phòng session lỗi / data cũ)
    |--------------------------------------------------------------------------
    */
    $cartItems = [];

    foreach ($cart as $sku => $item) {

        if (
            !is_array($item)
            || empty($sku)
            || empty($item['name'])
            || !isset($item['price'], $item['qty'])
            || !is_numeric($item['price'])
            || !is_numeric($item['qty'])
            || (int) $item['qty'] <= 0
        ) {
            continue;
        }

        $cartItems[$sku] = [
            'sku'   => $sku,
            'name'  => $item['name'],
            'price'=> (float) $item['price'],
            'qty'  => (int) $item['qty'],

            // Optional fields – phục vụ UI checkout
            'image'=> $item['image'] ?? null,
            'attrs'=> is_array($item['attrs'] ?? null) ? $item['attrs'] : [],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 3️⃣ Giỏ hàng không hợp lệ → clear session
    |--------------------------------------------------------------------------
    */
    if (empty($cartItems)) {
        session()->forget('cart');

        return redirect()
            ->route('linxen.cart')
            ->with('warning', 'Giỏ hàng không hợp lệ, vui lòng chọn lại sản phẩm.');
    }

    /*
    |--------------------------------------------------------------------------
    | 4️⃣ Trả view checkout
    |--------------------------------------------------------------------------
    */
    return view(
        "storefront.{$this->theme}.pages.checkout",
        [
            'cart'  => $cartItems,
            'brand' => $this->brand,
        ]
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
