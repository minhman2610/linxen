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

    $variants   = $product['variants'] ?? [];
    $attributes = $product['attributes'] ?? [];
@endphp

<section class="lx-product-detail">

    {{-- =========================
        PRODUCT GALLERY
    ========================= --}}
    <div class="lx-product-gallery">
        <div class="lx-product-main-image">
            <img id="lxMainImage" src="{{ $mainImage }}" alt="{{ $product['name'] ?? '' }}">
        </div>

        @if(count($images) > 1)
            <div class="lx-product-thumbs">
                @foreach($images as $img)
                    <img src="{{ $img }}" onclick="previewImage('{{ $img }}')" alt="">
                @endforeach
            </div>
        @endif
    </div>

    {{-- =========================
        PRODUCT INFO
    ========================= --}}
    <div class="lx-product-info">

        <h1 class="lx-product-title">{{ $product['name'] ?? '' }}</h1>

        <div class="lx-product-meta">
            @if(!empty($product['code']))
                <span>Mã SP: <strong>{{ $product['code'] }}</strong></span>
            @endif
            <span class="lx-badge">{{ strtoupper($brand) }}</span>
        </div>

        <div class="lx-product-price">
            {{ number_format($product['price'] ?? 0) }}₫
        </div>

        <div class="lx-product-description">
            {!! nl2br(e($product['description'] ?? 'Thiết kế tinh tế – phom dáng hiện đại.')) !!}
        </div>

        {{-- =========================
            VARIANTS
        ========================= --}}
        @if(!empty($attributes))
            <div class="lx-product-variants" id="lxVariants">
                @foreach($attributes as $attr => $values)
                    <div class="lx-attr-group" data-attr="{{ $attr }}">
                        <label>{{ $attr }}</label>
                        <div class="lx-attr-values">
                            @foreach($values as $val)
                                <div class="variant-option"
                                     data-attr="{{ $attr }}"
                                     data-value="{{ $val }}">
                                    {{ $val }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- =========================
            STOCK
        ========================= --}}
        <p class="lx-product-stock" id="lxStock">
            @if(($product['available'] ?? 0) > 0)
                ✔ Còn {{ $product['available'] }} sản phẩm
            @else
                ❌ Hết hàng
            @endif
        </p>

        {{-- =========================
            ACTIONS
        ========================= --}}
        <div class="lx-product-actions">
            <div class="lx-qty">
                <button type="button" onclick="changeQty(-1)">−</button>
                <input type="number" id="lxQty" value="1" min="1">
                <button type="button" onclick="changeQty(1)">+</button>
            </div>

            <button class="lx-btn-primary lx-btn-full"
                    id="lxAddToCartBtn"
                    onclick="addToCart()"
                    {{ ($product['available'] ?? 0) <= 0 ? 'disabled' : '' }}>
                THÊM VÀO GIỎ
            </button>
        </div>

        <ul class="lx-product-trust">
            <li>✔ Thiết kế độc quyền LIN XÉN</li>
            <li>✔ Chất liệu cao cấp</li>
            <li>✔ Đổi trả trong 7 ngày</li>
        </ul>
    </div>
</section>

<div id="lxToast" class="lx-toast"></div>

@endsection

{{-- =========================
    STYLE (GIỮ GỌN)
========================= --}}
<style>
.lx-product-detail{max-width:1200px;margin:auto;padding:40px 16px;display:grid;gap:40px}
@media(min-width:768px){.lx-product-detail{grid-template-columns:1fr 1fr}}
.lx-product-main-image img{width:100%;aspect-ratio:3/4;object-fit:cover}
.lx-product-thumbs{display:flex;gap:8px;margin-top:10px}
.lx-product-thumbs img{width:64px;height:86px;object-fit:cover;cursor:pointer}
.lx-attr-group{margin-top:20px}
.lx-attr-values{display:flex;gap:8px;flex-wrap:wrap}
.variant-option{border:1px solid #ddd;padding:6px 14px;cursor:pointer}
.variant-option.active{background:#111;color:#fff;border-color:#111}
.variant-option.disabled{opacity:.4;pointer-events:none}
.lx-product-actions{margin-top:24px;display:flex;gap:16px}
.lx-qty{display:flex;border:1px solid #ddd}
.lx-qty button{width:36px;background:#fff;border:none}
.lx-qty input{width:50px;text-align:center;border:none}
.lx-toast{position:fixed;top:20px;right:20px;background:#111;color:#fff;padding:12px 18px;border-radius:6px;opacity:0;transition:.3s}
.lx-toast.show{opacity:1}
</style>

{{-- =========================
    SCRIPT
========================= --}}
<script>
const VARIANTS = @json($variants);
let selectedAttrs = {};
let selectedVariant = null;

document.querySelectorAll('.variant-option').forEach(el => {
    el.addEventListener('click', () => {
        const attr = el.dataset.attr;
        const val  = el.dataset.value;

        document
            .querySelectorAll(`.variant-option[data-attr="${attr}"]`)
            .forEach(x => x.classList.remove('active'));

        el.classList.add('active');
        selectedAttrs[attr] = val;

        resolveVariant();
    });
});

function resolveVariant(){
    selectedVariant = VARIANTS.find(v => {
        return Object.keys(selectedAttrs)
            .every(k => v.attrs?.[k] === selectedAttrs[k]);
    });

    const stockEl = document.getElementById('lxStock');
    const btn     = document.getElementById('lxAddToCartBtn');

    if (!selectedVariant) return;

    if (parseInt(selectedVariant.stock) > 0) {
        stockEl.innerHTML = `✔ Còn ${selectedVariant.stock} sản phẩm`;
        btn.disabled = false;
    } else {
        stockEl.innerHTML = `❌ Hết hàng`;
        btn.disabled = true;
    }
}

function previewImage(src){ document.getElementById("lxMainImage").src = src; }
function changeQty(step){
    const i=document.getElementById("lxQty");
    i.value=Math.max(1,parseInt(i.value||1)+step);
}

async function addToCart(){
    if (!selectedVariant) {
        showToast("Vui lòng chọn biến thể", true);
        return;
    }

    const qty = parseInt(document.getElementById("lxQty").value || 1);

    const res = await fetch("{{ route('linxen.cart.add') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            sku: selectedVariant.sku,
            qty: qty
        })
    });

    const data = await res.json();

    if (!res.ok || !data.success) {
        showToast(data.message || "Không thể thêm vào giỏ", true);
        return;
    }

    showToast("Đã thêm vào giỏ hàng");
}

function showToast(msg, error=false){
    const t=document.getElementById("lxToast");
    t.textContent=msg;
    t.className="lx-toast show"+(error?" error":"");
    setTimeout(()=>t.classList.remove("show"),2500);
}
</script>
