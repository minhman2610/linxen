@extends('storefront.luxe.layouts.app')

@section('content')

@php
    use Illuminate\Support\Str;

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
                    @php
                        // normalize key để JS match chính xác
                        $attrKey = Str::slug($attr, '_');
                    @endphp
                    <div class="lx-attr-group"
                         data-attr="{{ $attr }}"
                         data-attr-key="{{ $attrKey }}">
                        <label>{{ $attr }}</label>
                        <div class="lx-attr-values">
                            @foreach($values as $val)
                                <div class="variant-option"
                                     data-attr="{{ $attr }}"
                                     data-attr-key="{{ $attrKey }}"
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
            STOCK (INIT UX ĐÚNG)
        ========================= --}}
        <p class="lx-product-stock" id="lxStock">
            Vui lòng chọn biến thể
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

            <button
    class="lx-btn-primary lx-btn-full"
    id="lxAddToCartBtn"
    type="button"
>

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
    STYLE
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
.lx-product-actions{margin-top:24px;display:flex;gap:16px}
.lx-qty{display:flex;border:1px solid #ddd}
.lx-qty button{width:36px;background:#fff;border:none}
.lx-qty input{width:50px;text-align:center;border:none}
.lx-toast{position:fixed;top:20px;right:20px;background:#111;color:#fff;padding:12px 18px;border-radius:6px;opacity:0;transition:.3s;z-index:9999}
.lx-toast.show{opacity:1}
.lx-toast.error{background:#c62828}
/* =========================
   CLICK DEBUG – FORCE FIX
========================= */

/* HERO KHÔNG ĂN CLICK */
.lx-hero,
.lx-hero * {
    pointer-events: none !important;
}

/* MOBILE MENU CHỈ ĂN CLICK KHI OPEN */
.mobile-menu {
    pointer-events: none !important;
}
.mobile-menu.is-open {
    pointer-events: auto !important;
}

/* BOTTOM NAV KHÔNG ĐÈ DESKTOP */
.bottom-nav {
    pointer-events: none;
}

/* ĐẢM BẢO PRODUCT NẰM TRÊN */
.lx-product-detail,
.lx-product-info,
.lx-product-actions,
#lxAddToCartBtn {
    position: relative;
    z-index: 9999 !important;
    pointer-events: auto !important;
}

</style>

{{-- =========================
    SCRIPT (FINAL – FIXED)
========================= --}}
<script>
/* =====================================================
   GLOBAL TOAST
===================================================== */
function showToast(message, isError = false) {
    let toast = document.getElementById('lxToast');

    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'lxToast';
        document.body.appendChild(toast);
    }

    toast.textContent = message;
    toast.className = 'lx-toast';
    if (isError) toast.classList.add('error');

    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-10px)';

    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    clearTimeout(window.__lxToastTimer);
    window.__lxToastTimer = setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
    }, 2500);
}

/* =====================================================
   PRODUCT PAGE SCRIPT
===================================================== */
document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
     * 1️⃣ NORMALIZE VARIANTS FROM ERP
     * ===================================================== */
    const RAW_VARIANTS = @json($variants ?? []);

    const VARIANTS = RAW_VARIANTS
        .filter(v => !v.is_master && v.attrs && typeof v.attrs === 'object')
        .map(v => {
            const normalizedAttrs = {};
            Object.entries(v.attrs).forEach(([key, val]) => {
                const normalizedKey = key
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/\s+/g, '_');
                normalizedAttrs[normalizedKey] = val;
            });
            return {
                ...v,
                _attrs: normalizedAttrs,
                _stock: parseInt(v.stock || 0)
            };
        });

    /* =====================================================
     * 2️⃣ STATE
     * ===================================================== */
    let selectedAttrs   = {};
    let selectedVariant = null;

    const stockEl = document.getElementById('lxStock');
    const btnAdd  = document.getElementById('lxAddToCartBtn');
    const qtyEl   = document.getElementById('lxQty');

    /* =====================================================
     * 3️⃣ BIND VARIANT CLICK
     * ===================================================== */
    document.querySelectorAll('.variant-option').forEach(el => {
        el.addEventListener('click', () => {

            const attrKey = el.dataset.attrKey;
            const value   = el.dataset.value;

            document
                .querySelectorAll(`.variant-option[data-attr-key="${attrKey}"]`)
                .forEach(x => x.classList.remove('active'));

            el.classList.add('active');
            selectedAttrs[attrKey] = value;

            resolveVariant();
        });
    });

    /* =====================================================
     * 4️⃣ RESOLVE VARIANT
     * ===================================================== */
    function resolveVariant() {

        selectedVariant = VARIANTS.find(v =>
            Object.entries(selectedAttrs).every(
                ([k, val]) => v._attrs[k] === val
            )
        );

        if (!selectedVariant) {
            stockEl.textContent = 'Vui lòng chọn đầy đủ biến thể';
            btnAdd.disabled = true;
            return;
        }

        if (selectedVariant._stock > 0) {
            stockEl.textContent = `✔ Còn ${selectedVariant._stock} sản phẩm`;
            btnAdd.disabled = false;
        } else {
            stockEl.textContent = '❌ Hết hàng';
            btnAdd.disabled = true;
        }

        window.__selectedVariant = selectedVariant;
    }

    /* =====================================================
     * 5️⃣ ADD TO CART
     * ===================================================== */
    btnAdd.addEventListener('click', async () => {

        console.log('[ADD TO CART CLICKED]');

        if (!window.__selectedVariant) {
            showToast('Vui lòng chọn biến thể', true);
            return;
        }

        const variant = window.__selectedVariant;

        if (variant._stock <= 0) {
            showToast('Biến thể đã hết hàng', true);
            return;
        }

        const qty = Math.max(1, parseInt(qtyEl.value || 1));

        try {
            const res = await fetch("{{ route('linxen.cart') }}/add", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    sku: variant.sku || variant.code,
                    name: @json($product['name'] ?? ''),
                    price: parseFloat(variant.price || {{ $product['price'] ?? 0 }}),
                    image: @json($mainImage ?? ''),
                    qty: qty,
                    attrs: variant.attrs || {}
                })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                showToast(data.message || 'Không thể thêm vào giỏ', true);
                return;
            }

            showToast('Đã thêm vào giỏ hàng');

            if (typeof updateMiniCart === 'function') {
                updateMiniCart(data.cart_count || 0);
            }

        } catch (err) {
            console.error(err);
            showToast('Lỗi hệ thống, vui lòng thử lại', true);
        }
    });

});

/* =====================================================
 * 6️⃣ CHANGE QTY (GLOBAL)
===================================================== */
function changeQty(step) {
    const qtyInput = document.getElementById('lxQty');
    if (!qtyInput) return;

    let qty = parseInt(qtyInput.value || 1);
    qty = isNaN(qty) ? 1 : qty;
    qty = Math.max(1, qty + step);

    qtyInput.value = qty;
}
</script>

<style>
/* =====================================================
   TOAST STYLE
===================================================== */
.lx-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 2000;

    background: #111;
    color: #fff;
    padding: 12px 18px;
    border-radius: 6px;
    font-size: 14px;

    opacity: 0;
    transition: all .25s ease;
}
.lx-toast.error {
    background: #c62828;
}
</style>



