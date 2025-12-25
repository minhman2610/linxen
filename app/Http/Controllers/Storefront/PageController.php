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

    if (empty($product)) {
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
    | 🧭 BREADCRUMB – CATEGORY TREE
    |--------------------------------------------------------------------------
    | ERP khuyến nghị trả:
    | $product['categories'] = [
    |   { id, name, slug, parent_id }
    | ]
    |--------------------------------------------------------------------------
    */
    $breadcrumbs = [];

    if (!empty($product['categories'])) {

        /**
         * Ví dụ ERP trả category path đã sort sẵn:
         * [
         *   ['name' => 'Đồ Nữ', 'slug' => 'do-nu'],
         *   ['name' => 'Váy', 'slug' => 'vay'],
         *   ['name' => 'Váy thiết kế', 'slug' => 'vay-thiet-ke'],
         * ]
         */
        foreach ($product['categories'] as $cat) {
            $breadcrumbs[] = [
                'name' => $cat['name'],
                'url'  => '/collection/' . $cat['slug'],
            ];
        }

    } else {
        /**
         * Fallback an toàn (ERP chưa map category)
         */
        $breadcrumbs = [
            [
                'name' => 'Sản phẩm',
                'url'  => '/collections',
            ],
        ];
    }

    return view(
        "storefront.{$this->theme}.pages.product",
        [
            // 🧾 DATA GỐC
            'product'    => $product,

            // 🎛 BIẾN THỂ
            'variants'   => $variants,
            'attributes' => $attributes,

            // 🖼 GALLERY
            'images'     => $images,
            'mainImage'  => $mainImage,

            // 🧭 BREADCRUMB
            'breadcrumbs'=> $breadcrumbs,

            // 🏷 BRAND
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
    $cartItems = array_filter($cart, function ($item) {
        return is_array($item)
            && isset(
                $item['sku'],
                $item['name'],
                $item['price'],
                $item['qty']
            )
            && is_numeric($item['price'])
            && is_numeric($item['qty'])
            && $item['qty'] > 0;
    });

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
