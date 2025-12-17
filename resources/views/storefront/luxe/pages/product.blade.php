@extends('storefront.luxe.layouts.app')

@section('content')

@php
    // ============================
    // NORMALIZE DATA FROM API
    // ============================
    $images = [];

    if (!empty($product['images']) && is_array($product['images'])) {
        $images = $product['images'];
    } elseif (!empty($product['thumb_url'])) {
        $images = [$product['thumb_url']];
    }

    $mainImage = $images[0] ?? '';
@endphp


<section class="lx-product-detail">

    {{-- =========================
        PRODUCT GALLERY
    ========================= --}}
    <div class="lx-product-gallery">

        <div class="lx-product-main-image">
            <img
                id="lxMainImage"
                src="{{ $mainImage }}"
                alt="{{ $product['name'] ?? '' }}"
            >
        </div>

        @if(count($images) > 1)
            <div class="lx-product-thumbs">
                @foreach($images as $img)
                    <img
                        src="{{ $img }}"
                        onclick="previewImage('{{ $img }}')"
                        alt="{{ $product['name'] ?? '' }}"
                    >
                @endforeach
            </div>
        @endif

    </div>


    {{-- =========================
        PRODUCT INFO
    ========================= --}}
    <div class="lx-product-info">

        <h1 class="lx-product-title">
            {{ $product['name'] ?? '' }}
        </h1>

        <div class="lx-product-meta">
            @if(!empty($product['code']))
                <span>Mã SP: <strong>{{ $product['code'] }}</strong></span>
            @endif

            @if(!empty($brand))
                <span class="lx-badge">{{ strtoupper($brand) }}</span>
            @endif
        </div>

        <div class="lx-product-price">
            {{ number_format($product['price'] ?? 0) }}₫
        </div>

        <div class="lx-product-description">
            {!! !empty($product['description'])
                ? nl2br(e($product['description']))
                : 'Thiết kế tinh tế – phom dáng hiện đại, phù hợp phong cách LIN XÉN.' !!}
        </div>

        {{-- =========================
            STOCK
        ========================= --}}
        @if(isset($product['available']))
            <p class="lx-product-stock">
                @if($product['available'] > 0)
                    ✔ Còn {{ $product['available'] }} sản phẩm
                @else
                    ❌ Hết hàng
                @endif
            </p>
        @endif


        {{-- =========================
            QUANTITY + ADD TO CART
        ========================= --}}
        <div class="lx-product-actions">

            <div class="lx-qty">
                <button type="button" onclick="changeQty(-1)">−</button>
                <input type="number" id="lxQty" value="1" min="1">
                <button type="button" onclick="changeQty(1)">+</button>
            </div>

            <button
                class="lx-btn-primary lx-btn-full"
                onclick="addToCart()"
                {{ ($product['available'] ?? 0) <= 0 ? 'disabled' : '' }}
            >
                THÊM VÀO GIỎ
            </button>

        </div>

        <ul class="lx-product-trust">
            <li>✔ Thiết kế độc quyền LIN XÉN</li>
            <li>✔ Chất liệu cao cấp – chọn lọc</li>
            <li>✔ Đổi trả trong 7 ngày</li>
        </ul>

    </div>

</section>

<div id="lxToast" class="lx-toast"></div>

@endsection
{{-- =========================
    STYLE
========================= --}}
<style>
.lx-product-detail{
    max-width:1200px;
    margin:0 auto;
    padding:32px 16px 80px;
    display:grid;
    grid-template-columns:1fr;
    gap:32px;
}
@media(min-width:768px){
    .lx-product-detail{
        grid-template-columns:1fr 1fr;
        gap:60px;
        padding:60px 40px;
    }
}

/* Gallery */
.lx-product-main-image{aspect-ratio:3/4;background:#f5f5f5}
.lx-product-main-image img{width:100%;height:100%;object-fit:cover}
.lx-product-thumbs{display:flex;gap:8px;margin-top:12px}
.lx-product-thumbs img{width:64px;height:86px;object-fit:cover;cursor:pointer;border:1px solid #eaeaea}

/* Info */
.lx-product-title{font-size:24px;font-weight:700}
.lx-product-meta{font-size:13px;color:#777;display:flex;gap:12px;margin:6px 0}
.lx-badge{background:#111;color:#fff;padding:2px 8px;font-size:11px}
.lx-product-price{font-size:20px;font-weight:600;margin:16px 0}
.lx-product-description{font-size:14px;line-height:1.7;color:#444}

/* Variant */
.lx-attr-group{margin-top:20px}
.lx-attr-group label{font-size:13px;font-weight:600}
.lx-attr-values{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}

.variant-option{
    padding:6px 14px;
    border:1px solid #ddd;
    cursor:pointer;
    background:#fff;
}
.variant-option.active{
    border-color:#111;
    background:#111;
    color:#fff;
}

/* Action */
.lx-product-actions{margin-top:28px;display:flex;gap:16px;align-items:center}
.lx-qty{display:flex;border:1px solid #ddd}
.lx-qty button{width:36px;border:none;background:#fff}
.lx-qty input{width:50px;text-align:center;border:none}

/* Toast */
.lx-toast{
    position:fixed;
    top:20px;
    right:20px;
    z-index:9999;
    background:#111;
    color:#fff;
    padding:12px 18px;
    border-radius:6px;
    font-size:13px;
    opacity:0;
    transform:translateY(-10px);
    transition:.3s;
}
.lx-toast.show{opacity:1;transform:translateY(0)}
.lx-toast.error{background:#c0392b}
</style>
<script>
function previewImage(src){
    const img = document.getElementById("lxMainImage");
    if (img) img.src = src;
}

function changeQty(step){
    const input = document.getElementById("lxQty");
    if (!input) return;
    input.value = Math.max(1, parseInt(input.value || 1) + step);
}

async function addToCart(){

    const qty = Math.max(1, parseInt(document.getElementById("lxQty").value || 1));

    try {
        const res = await fetch("{{ route('linxen.cart.add') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                code: "{{ $product['code'] ?? '' }}",
                name: "{{ $product['name'] ?? '' }}",
                price: {{ $product['price'] ?? 0 }},
                image: "{{ $mainImage }}",
                qty: qty
            })
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            showToast(data.message || "Không thể thêm vào giỏ", true);
            return;
        }

        showToast("Đã thêm sản phẩm vào giỏ hàng");

        if (typeof updateMiniCart === "function") {
            updateMiniCart(data.cart_count);
        }

    } catch (e) {
        console.error(e);
        showToast("Lỗi kết nối. Vui lòng thử lại.", true);
    }
}

function showToast(msg, error=false){
    const t = document.getElementById("lxToast");
    if (!t) return;
    t.textContent = msg;
    t.className = "lx-toast show" + (error ? " error":"");
    setTimeout(()=>t.classList.remove("show"), 2500);
}
</script>
