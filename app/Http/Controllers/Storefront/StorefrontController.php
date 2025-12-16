<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use App\Models\KiotViet\KiotVietProduct;

class StorefrontController extends Controller
{
    protected $brand = 'linxen';

    public function home()
{
    /*
    |--------------------------------------------------------------------------
    | HOMEPAGE PRODUCTS
    |--------------------------------------------------------------------------
    | - Lấy sản phẩm CHA
    | - Đang active
    | - Ưu tiên còn hàng
    | - Limit cho homepage (12 sp)
    */

    $rows = DB::table('kiotviet_products as p')
        ->leftJoin('kiotviet_product_inventories as inv', 'inv.product_id', '=', 'p.product_id')
        ->where('p.is_master', 1)
        ->where('p.is_active', 1)
        ->groupBy(
            'p.product_id',
            'p.kiotviet_id',
            'p.code',
            'p.name',
            'p.full_name',
            'p.base_price',
            'p.retail_price'
        )
        ->selectRaw("
            p.product_id,
            p.kiotviet_id,
            p.code,
            p.name,
            p.full_name,
            p.base_price,
            COALESCE(p.retail_price, p.base_price) as price,
            COALESCE(SUM(inv.on_hand), 0) as on_hand,
            COALESCE(SUM(inv.reserved), 0) as reserved,
            COALESCE(SUM(inv.on_hand) - SUM(inv.reserved), 0) as available
        ")
        ->havingRaw("COALESCE(SUM(inv.on_hand) - SUM(inv.reserved), 0) > 0")
        ->orderByDesc('p.product_id')
        ->limit(12)
        ->get();

    // Map sang format dùng cho view
    $products = $rows->map(function ($r) {
        $model = KiotVietProduct::where('product_id', $r->product_id)->first();

        $media = $model
            ? $model->getMedia()
            : ['thumb' => asset('images/no-image.png'), 'photos' => []];

        return [
            'product_id'  => (int) $r->product_id,
            'kiotviet_id' => (int) $r->kiotviet_id,
            'code'        => $r->code,
            'name'        => $r->name ?? $r->full_name,
            'price'       => (float) $r->price,
            'available'   => (float) $r->available,
            'thumb_url'   => $media['thumb'],
            'photos'      => $media['photos'],
        ];
    });

    return view('storefront.luxe.pages.home', [
        'products' => $products,
        'brand'    => $this->brand,
    ]);
}

    public function product($slug)
{
    // =======================================================
    // 1️⃣ LẤY SẢN PHẨM THEO CODE (CÓ THỂ LÀ CHA HOẶC CON)
    // =======================================================
    $product = KiotVietProduct::where('code', $slug)->firstOrFail();

    // =======================================================
    // 2️⃣ LUÔN QUY VỀ SKU CHA (MASTER)
    // =======================================================
    $master = $product->getMasterProduct();

    // =======================================================
    // 3️⃣ MEDIA (ẢNH CHÍNH + GALLERY)
    // =======================================================
    $photos = is_array($master->photos ?? null)
        ? $master->photos
        : [];

    if (empty($photos) && method_exists($master, 'getMedia')) {
        $media  = $master->getMedia();
        $photos = $media['photos'] ?? [];
    }

    $mainImage = $photos[0] ?? asset('images/no-image.png');

    // =======================================================
    // 4️⃣ RESOLVE VARIANTS (CHA + CON) TỪ MODEL
    // =======================================================
    $variantData = $master->resolveVariants();
    // [
    //   'variants'   => [...],
    //   'attributes' => [...]
    // ]

    // =======================================================
    // 5️⃣ RENDER VIEW
    // =======================================================
    return view('storefront.luxe.pages.product', [
        'product'    => $master,                    // luôn là SKU cha
        'mainImage'  => $mainImage,
        'photos'     => $photos,
        'variants'   => $variantData['variants'],   // gồm cha + con
        'attributes' => $variantData['attributes'], // Size / Màu
        'brand'      => $this->brand,
    ]);
}



    public function collection($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $products = KiotVietProduct::where('category_id', $category->id)->get();

        return view('storefront.luxe.pages.collection', [
            'category' => $category,
            'products' => $products,
            'brand'    => $this->brand
        ]);
    }

    public function cart()
{
    // =======================================================
    // 🛒 LẤY GIỎ HÀNG TỪ SESSION
    // =======================================================
    $cartItems = session('cart', []);

    // =======================================================
    // 🧮 TÍNH TOÁN TẠM TÍNH
    // =======================================================
    $subtotal = 0;

    foreach ($cartItems as $item) {
        $price = $item['price'] ?? 0;
        $qty   = $item['qty'] ?? 1;
        $subtotal += $price * $qty;
    }

    // =======================================================
    // 🚚 PHÍ VẬN CHUYỂN (TẠM FREE)
    // =======================================================
    $shippingFee = 0;
    $total       = $subtotal + $shippingFee;

    // =======================================================
    // ⭐ SẢN PHẨM THAM KHẢO (MODEL THẬT)
    // =======================================================
    $suggestedProducts = KiotVietProduct::query()
        ->where('is_master', 1)
        ->orderByDesc('product_id')
        ->limit(4)
        ->get();

    // =======================================================
    // 🖼️ RENDER VIEW
    // =======================================================
    return view('storefront.luxe.pages.cart', [
        'brand'              => $this->brand,
        'cartItems'          => $cartItems,
        'subtotal'           => $subtotal,
        'shippingFee'        => $shippingFee,
        'total'              => $total,
        'suggestedProducts'  => $suggestedProducts,
    ]);
}

public function addToCart(Request $request)
{
    // =======================================================
    // 1️⃣ VALIDATE INPUT CƠ BẢN
    // =======================================================
    $data = $request->validate([
        'sku'   => 'required|string',
        'name'  => 'required|string',
        'price' => 'required|numeric|min:0',
        'qty'   => 'required|integer|min:1',
        'image' => 'nullable|string',
        'attrs' => 'nullable|array',
    ]);

    $sku = $data['sku'];
    $qty = (int) $data['qty'];

    // =======================================================
    // 2️⃣ LẤY CART TỪ SESSION
    // =======================================================
    $cart = session('cart', []);

    /*
    |--------------------------------------------------------------------------
    | 3️⃣ NẾU SKU ĐÃ CÓ → TĂNG SỐ LƯỢNG
    |--------------------------------------------------------------------------
    */
    if (isset($cart[$sku])) {

        $cart[$sku]['qty'] += $qty;

    } else {

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ THÊM MỚI SKU VÀO CART
        |--------------------------------------------------------------------------
        */
        $cart[$sku] = [
            'sku'   => $sku,
            'name'  => $data['name'],
            'price' => (float) $data['price'],
            'qty'   => $qty,
            'image' => $data['image'] ?? asset('images/no-image.png'),
            'attrs' => $data['attrs'] ?? [],
        ];
    }

    // =======================================================
    // 5️⃣ LƯU SESSION
    // =======================================================
    session(['cart' => $cart]);

    // =======================================================
    // 6️⃣ TRẢ JSON RESPONSE
    // =======================================================
    return response()->json([
        'success'     => true,
        'message'     => 'Đã thêm sản phẩm vào giỏ hàng',
        'cart_count'  => array_sum(array_column($cart, 'qty')),
        'cart_items'  => $cart,
    ]);
}

}
