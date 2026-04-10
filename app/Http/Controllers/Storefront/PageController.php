<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ERP\ErpStorefrontApi;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
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
    /*
    |--------------------------------------------------------------------------
    | 1️⃣ LOAD DATA FROM ERP
    |--------------------------------------------------------------------------
    */

    $rawHome = $erp->home($this->brand);

    $products = collect($rawHome['products'] ?? [])



    /*
    |--------------------------------------------------------------------------
    | 2️⃣ BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    ->filter(fn ($p) =>
        !empty($p['product_id'])
        && !empty($p['name'])
        && !empty($p['price'])
    )



    /*
    |--------------------------------------------------------------------------
    | 3️⃣ NORMALIZE MEDIA
    |--------------------------------------------------------------------------
    */

    ->map(function ($p) {

        $thumb = null;

        if (!empty($p['media']['images'][0])) {

            $thumb = $p['media']['images'][0];

        } elseif (!empty($p['media']['thumb_mobile'])) {

            $thumb = $p['media']['thumb_mobile'];

        } elseif (!empty($p['media']['thumb'])) {

            $thumb = $p['media']['thumb'];

        }

        return [

            ...$p,

            'thumb' => $thumb,

        ];

    })



    /*
    |--------------------------------------------------------------------------
    | 4️⃣ REQUIRE IMAGE
    |--------------------------------------------------------------------------
    */

    ->filter(fn ($p) => !empty($p['thumb']))



    /*
    |--------------------------------------------------------------------------
    | 5️⃣ RESET INDEX
    |--------------------------------------------------------------------------
    */

    ->values();



    /*
    |--------------------------------------------------------------------------
    | 6️⃣ FLASH SALE PRODUCTS
    |--------------------------------------------------------------------------
    */

    $flashProducts = $products

        ->filter(fn ($p) => !empty($p['is_flash_sale']))

        ->shuffle()

        ->take(10)

        ->values()



        /*
        |--------------------------------------------------------------------------
        | ADD FLASH UI DATA
        |--------------------------------------------------------------------------
        */

        ->map(function ($p) {

            $origin = (float)($p['original_price'] ?? $p['price']);
            $price  = (float)$p['price'];

            $percent = $origin > 0
                ? round(100 - ($price / $origin * 100))
                : 0;

            return [

                ...$p,

                'sale_percent' => $percent,

                'origin_price' => $origin,

            ];

        })

        ->toArray();



    /*
|--------------------------------------------------------------------------
| 7️⃣ FEATURED PRODUCTS (NON FLASH)
|--------------------------------------------------------------------------
*/

$featuredProducts = $products

    // ❌ bỏ flash sale
    ->filter(fn ($p) => empty($p['is_flash_sale']))

    // ❌ bỏ sản phẩm lỗi
    ->filter(fn ($p) =>
        !empty($p['product_id'])
        && !empty($p['name'])
        && !empty($p['media']['thumb_mobile'])
        && !empty($p['price'])
    )

    // ⭐ ưu tiên sản phẩm còn nhiều stock
    ->sortByDesc(function ($p) {
        return $p['available'] ?? 0;
    })

    // lấy tối đa 12
    ->take(12)

    ->values()

    ->toArray();



/*
|--------------------------------------------------------------------------
| FALLBACK – nếu thiếu sản phẩm
|--------------------------------------------------------------------------
*/

if (count($featuredProducts) < 8) {

    $fallback = $products

        ->filter(fn ($p) =>
            !empty($p['product_id'])
            && !empty($p['name'])
            && !empty($p['media']['thumb_mobile'])
        )

        ->take(12 - count($featuredProducts))

        ->values()

        ->toArray();

    $featuredProducts = array_merge(
        $featuredProducts,
        $fallback
    );
}



    /*
    |--------------------------------------------------------------------------
    | 8️⃣ BUILD HOME DATA
    |--------------------------------------------------------------------------
    */

    $home = [

        'hero' => $rawHome['hero'] ?? null,

        'flash_products' => $flashProducts,

        'featured_products' => $featuredProducts,

    ];



    /*
    |--------------------------------------------------------------------------
    | 9️⃣ VIEW
    |--------------------------------------------------------------------------
    */

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
    | 🔑 PRODUCT ID – ERP / KIOTVIET (CỰC KỲ QUAN TRỌNG)
    |--------------------------------------------------------------------------
    | BẮT BUỘC chuẩn hoá để:
    | - addToCart()
    | - checkout
    | - ERP createOrder
    |--------------------------------------------------------------------------
    */
    $productId = $product['id']
        ?? $product['product_id']
        ?? null;

    if (!$productId) {
        \Log::error('[LINXEN PDP] Missing product_id from ERP', [
            'slug'    => $slug,
            'product' => $product,
        ]);

        abort(500, 'Sản phẩm chưa được đồng bộ ERP');
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
    | RENDER VIEW
    |--------------------------------------------------------------------------
    */
    return view(
        "storefront.{$this->theme}.pages.product",
        [
            // 🧾 DATA GỐC ERP
            'product'           => $product,

            // 🔑 ID ERP – DÙNG CHO ADD TO CART
            'productId'         => (int) $productId,

            // 🎛 BIẾN THỂ
            'variants'          => $variants,
            'attributes'        => $attributes,

            // 🖼 GALLERY CHÍNH
            'images'            => $images,
            'mainImage'         => $mainImage,

            // 👥 REAL MEDIA (UGC)
            'ugcMedia'          => $ugcMedia,
            'ugcCount'          => $ugcCount,

            // 🔁 SẢN PHẨM GỢI Ý
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
        'sku'        => 'required|string',
        'product_id' => 'required|integer', // 🔥 BẮT BUỘC – ID ERP / KiotViet
        'name'       => 'required|string',
        'price'      => 'required|numeric|min:0',
        'image'      => 'nullable|string',
        'qty'        => 'required|integer|min:1',
        'attrs'      => 'nullable|array',
    ]);

    $cart = session()->get('cart', []);

    if (isset($cart[$data['sku']])) {
        // ➕ Cộng số lượng nếu đã có
        $cart[$data['sku']]['qty'] += $data['qty'];
    } else {
        // 🆕 Thêm mới sản phẩm
        $cart[$data['sku']] = [
            'sku'        => $data['sku'],
            'product_id' => (int) $data['product_id'], // 🔥 QUAN TRỌNG
            'name'       => $data['name'],
            'price'      => (float) $data['price'],
            'image'      => $data['image'] ?? null,
            'qty'        => (int) $data['qty'],
            'attrs'      => $data['attrs'] ?? [],
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
public function checkout(ErpStorefrontApi $erp)
{
    $cart = session('cart', []);

    /*
    |--------------------------------------------------------------------------
    | 1️⃣ Giỏ hàng trống
    |--------------------------------------------------------------------------
    */
    if (empty($cart) || !is_array($cart)) {
        return redirect()
            ->route('linxen.cart')
            ->with('warning', 'Giỏ hàng của bạn đang trống.');
    }

    /*
    |--------------------------------------------------------------------------
    | 2️⃣ Chuẩn hoá & validate giỏ hàng
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
            'sku'        => $sku,
            'name'       => $item['name'],
            'price'      => (float) $item['price'],
            'qty'        => (int) $item['qty'],
            'image'      => $item['image'] ?? null,
            'attrs'      => is_array($item['attrs'] ?? null) ? $item['attrs'] : [],
            'product_id' => $item['product_id'] ?? null,
        ];
    }

    if (empty($cartItems)) {
        session()->forget('cart');

        return redirect()
            ->route('linxen.cart')
            ->with('warning', 'Giỏ hàng không hợp lệ.');
    }

    /*
    |--------------------------------------------------------------------------
    | 3️⃣ RESOLVE PHONE (SOURCE OF TRUTH)
    |--------------------------------------------------------------------------
    */
    $phone = session('customer.phone');

    // fallback: lấy từ ERP profile bằng login_token
    if (!$phone && session('login_token')) {
        $profile = $erp->customerProfile(
            $this->brand,
            session('login_token')
        );

        $phone = $profile['phone'] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | 4️⃣ FETCH ADDRESSES FROM ERP
    |--------------------------------------------------------------------------
    */
    $addresses      = [];
    $defaultAddress = null;
    $hasAddresses   = false;

    if ($phone) {
        $rawAddresses = $erp->customerAddresses(
            $this->brand,
            $phone
        );

        if (!empty($rawAddresses)) {
            $hasAddresses = true;

            // map ERP → UI
            $addresses = collect($rawAddresses)->map(function ($addr) {
                return [
                    'id'         => $addr['id'],
                    'name'       => $addr['receiver_name'],
                    'phone'      => $addr['receiver_phone'],
                    'address'    => trim(
                        ($addr['street'] ?? '') . ', ' .
                        ($addr['ward_name'] ?? '') . ', ' .
                        ($addr['location_name'] ?? '')
                    ),
                    'is_default' => (bool) ($addr['is_default'] ?? false),
                ];
            })->toArray();

            $defaultAddress = collect($addresses)
                ->firstWhere('is_default', true)
                ?? $addresses[0];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 5️⃣ RETURN VIEW
    |--------------------------------------------------------------------------
    */
    return view(
        "storefront.{$this->theme}.pages.checkout",
        [
            'cart'           => $cartItems,
            'brand'          => $this->brand,
            'justRegistered' => session('just_registered', false),

            'addresses'      => $addresses,
            'hasAddresses'   => $hasAddresses,
            'defaultAddress' => $defaultAddress,
        ]
    );
}

/**
 * =====================================================
 * 🧾 PLACE ORDER (LEGACY / SUCCESS PAGE)
 * =====================================================
 */
public function placeOrder(Request $request)
{
    // Clear cart (an toàn, phòng reload / back)
    session()->forget('cart');

    // Lấy mã đơn nếu có (từ checkout JS redirect)
    $orderCode = $request->query('order_code');

    return view('storefront.luxe.pages.checkout-success', [
        'orderCode' => $orderCode,
    ]);
}


    /**
 * =====================================================
 * 👤 ACCOUNT DASHBOARD
 * =====================================================
 */
public function account()
{
    $customer = session('customer');

    // ❌ Chưa đăng nhập → về trang login
    if (!$customer) {
        return redirect()
            ->route('linxen.login')
            ->with('warning', 'Vui lòng đăng nhập để tiếp tục.');
    }

    // ✅ Đã đăng nhập
    return view('storefront.luxe.pages.account.index', [
        'customer' => (object) $customer,
    ]);
}

/* =====================================================
 * 📦 COLLECTION – LIN XÉN (ERP SOURCE OF TRUTH)
 * ===================================================== */
public function collection(string $slug, ErpStorefrontApi $erp)
{

    /*
    |--------------------------------------------------------------------------
    | 0️⃣ Pagination params
    |--------------------------------------------------------------------------
    */
    $page    = max((int) request()->input('page', 1), 1);
    $perPage = 24;


    /*
    |--------------------------------------------------------------------------
    | 1️⃣ Call ERP service
    |--------------------------------------------------------------------------
    */
    $data = $erp->collection(
        $this->brand,
        $slug,
        $page,
        $perPage
    );


    /*
    |--------------------------------------------------------------------------
    | 2️⃣ ERP ERROR HANDLING
    |--------------------------------------------------------------------------
    */

    if (!$data) {

        \Log::error('ERP collection API failed', [
            'brand' => $this->brand,
            'slug'  => $slug
        ]);

        abort(500, 'Không thể tải dữ liệu bộ sưu tập từ ERP.');
    }


    /*
    |--------------------------------------------------------------------------
    | 3️⃣ COLLECTION NOT FOUND
    |--------------------------------------------------------------------------
    */

    if (!array_key_exists('collection', $data) || $data['collection'] === null) {

    abort(404, "Collection '{$slug}' không tồn tại trong ERP.");

}


    /*
    |--------------------------------------------------------------------------
    | 4️⃣ COLLECTION META
    |--------------------------------------------------------------------------
    */

    $collection = [

        'name'        => $data['collection']['name'] ?? 'Bộ sưu tập',

        'description' => $data['collection']['description'] ?? null,

        'hero'        => $data['collection']['hero'] ?? null,

        'slug'        => $slug,

    ];


    /*
    |--------------------------------------------------------------------------
    | 5️⃣ PRODUCTS
    |--------------------------------------------------------------------------
    */

    $items = collect($data['products'] ?? [])
    ->map(function ($p) {

        $rsId = (int) ($p['rs_id'] ?? 0);

        return [

            'product_id' => $rsId, // 🔥 KEY CHUẨN

            'name' => $p['name'] ?? '',

            'slug' => $p['slug']
                ?? Str::slug($p['name'] ?? '') . '-rs-' . $rsId,

            'price' => (float) ($p['price'] ?? 0),

            'sale_percent' => (int) ($p['sale_percent'] ?? 0),

            'thumb' => $p['thumb'] ?? asset('images/no-image.png'),

            'colors' => collect($p['colors'] ?? [])
                ->map(fn ($c) => is_array($c) ? ($c['code'] ?? null) : $c)
                ->filter(fn ($c) => is_string($c))
                ->values()
                ->toArray(),

            'available' => (int) ($p['available'] ?? 0),

            'tag' => $p['tag'] ?? null,

        ];

    })
    ->filter(fn ($p) => $p['product_id'] > 0) // 🔥 giờ OK
    ->values();


    /*
    |--------------------------------------------------------------------------
    | 6️⃣ Empty collection notice
    |--------------------------------------------------------------------------
    */

    $isEmpty = $items->isEmpty();


    /*
    |--------------------------------------------------------------------------
    | 7️⃣ LengthAwarePaginator
    |--------------------------------------------------------------------------
    */

    $products = new LengthAwarePaginator(
        $items,
        (int) ($data['meta']['total'] ?? $items->count()),
        $perPage,
        $page,
        [
            'path'     => request()->url(),
            'pageName' => 'page',
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | 8️⃣ Render view
    |--------------------------------------------------------------------------
    */

    return view(
        "storefront.{$this->theme}.pages.collection",
        compact(
            'collection',
            'products',
            'isEmpty'
        )
    );

}


}
