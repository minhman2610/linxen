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

            <button class="lx-btn-primary lx-btn-full"
                    id="lxAddToCartBtn"
                    onclick="addToCart()">
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
</style>

{{-- =========================
    SCRIPT (FIXED)
========================= --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

    // ===== Normalize variants từ ERP =====
    const VARIANTS = (@json($variants) || [])
        .filter(v => !v.is_master && v.attrs)
        .map(v => {
            const normalized = {};
            Object.entries(v.attrs).forEach(([k, val]) => {
                const key = k.toLowerCase()
                    .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                    .replace(/\s+/g, "_");
                normalized[key] = val;
            });
            return { ...v, _attrs: normalized };
        });

    let selectedAttrs = {};
    let selectedVariant = null;

    document.querySelectorAll('.variant-option').forEach(el => {
        el.addEventListener('click', () => {
            const attrKey = el.dataset.attrKey;
            const val     = el.dataset.value;

            document
                .querySelectorAll(`.variant-option[data-attr-key="${attrKey}"]`)
                .forEach(x => x.classList.remove('active'));

            el.classList.add('active');
            selectedAttrs[attrKey] = val;

            resolveVariant();
        });
    });

    function resolveVariant() {
        selectedVariant = VARIANTS.find(v =>
            Object.entries(selectedAttrs)
                .every(([k, val]) => v._attrs?.[k] === val)
        );
        window.__selectedVariant = selectedVariant;

        const stockEl = document.getElementById('lxStock');
        const btn     = document.getElementById('lxAddToCartBtn');

        if (!selectedVariant) {
            stockEl.innerHTML = "Vui lòng chọn đầy đủ biến thể";
            btn.disabled = true;
            return;
        }

        const stock = parseInt(selectedVariant.stock || 0);

        if (stock > 0) {
            stockEl.innerHTML = `✔ Còn ${stock} sản phẩm`;
            btn.disabled = false;
        } else {
            stockEl.innerHTML = "❌ Hết hàng";
            btn.disabled = true;
        }
    }

    window.addToCart = async function () {
    if (!window.__selectedVariant) {
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
            sku: window.__selectedVariant.sku,
            qty: qty
        })
    });

    const data = await res.json();

    if (!res.ok || !data.success) {
        showToast(data.message || "Không thể thêm vào giỏ", true);
        return;
    }

    showToast("Đã thêm vào giỏ hàng");
};

});

function previewImage(src){
    document.getElementById("lxMainImage").src = src;
}

function changeQty(step){
    const i=document.getElementById("lxQty");
    i.value=Math.max(1,parseInt(i.value||1)+step);
}

function showToast(message, isError = false) {
    const toast = document.getElementById('lxToast');
    if (!toast) return;

    toast.textContent = message;
    toast.classList.remove('show','error');
    if (isError) toast.classList.add('error');

    void toast.offsetWidth;
    toast.classList.add('show');

    clearTimeout(window.__lxToastTimer);
    window.__lxToastTimer = setTimeout(() => {
        toast.classList.remove('show');
    }, 2500);
}
</script>
