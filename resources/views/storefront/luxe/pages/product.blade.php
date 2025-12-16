@extends('storefront.luxe.layouts.master')

@section('content')

<section class="lx-product-detail">

    {{-- =========================
        PRODUCT GALLERY
    ========================= --}}
    <div class="lx-product-gallery">
        <div class="lx-product-main-image">
            <img id="lxMainImage"
                 src="{{ $mainImage }}"
                 alt="{{ $product->name }}">
        </div>

        @if(!empty($photos) && count($photos) > 1)
            <div class="lx-product-thumbs">
                @foreach($photos as $photo)
                    <img src="{{ $photo }}"
                         onclick="previewImage('{{ $photo }}')"
                         alt="{{ $product->name }}">
                @endforeach
            </div>
        @endif
    </div>

    {{-- =========================
        PRODUCT INFO
    ========================= --}}
    <div class="lx-product-info">

        <h1 class="lx-product-title">{{ $product->name }}</h1>

        <div class="lx-product-meta">
            <span>Mã SP: <strong>{{ $product->code }}</strong></span>
            @if(!empty($brand))
                <span class="lx-badge">{{ $brand }}</span>
            @endif
        </div>

        <div class="lx-product-price">
            {{ number_format($product->retail_price ?? $product->base_price ?? 0) }}₫
        </div>

        <div class="lx-product-description">
            {!! !empty($product->description)
                ? nl2br(e($product->description))
                : 'Thiết kế tinh tế – phom dáng hiện đại, phù hợp phong cách LIN XÉN.' !!}
        </div>

        {{-- =========================
            VARIANT ATTRIBUTES
        ========================= --}}
        @if(!empty($attributes))
            <div class="lx-product-attrs" id="lxVariantAttrs">
                @foreach($attributes as $attrName => $values)
                    <div class="lx-attr-group attr-group" data-attr="{{ $attrName }}">
                        <label>{{ $attrName }}</label>
                        <div class="lx-attr-values">
                            @foreach($values as $val)
                                <div class="variant-option"
                                     data-attr="{{ $attrName }}"
                                     data-value="{{ $val }}">
                                    <span>{{ $val }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
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

            <button class="lx-btn-primary lx-btn-full" onclick="addToCart()">
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

{{-- =========================
    SCRIPT (FIXED)
========================= --}}
<script>
document.addEventListener("DOMContentLoaded", function () {

    // ===============================
    // DATA
    // ===============================
    const VARIANTS = @json($variants)
        .filter(v => v && v.attrs && Object.keys(v.attrs).length);

    const container = document.getElementById("lxVariantAttrs");
    const qtyInput  = document.getElementById("lxQty");
    let selectedAttrs = {};

    if (!container || VARIANTS.length === 0) return;

    // ===============================
    // CHỌN BIẾN THỂ
    // ===============================
    container.querySelectorAll(".variant-option").forEach(opt => {
        opt.addEventListener("click", () => {
            const group = opt.closest(".attr-group");
            const attr  = group.dataset.attr;
            const val   = opt.dataset.value;

            selectedAttrs[attr] = val;

            group.querySelectorAll(".variant-option")
                 .forEach(o => o.classList.remove("active"));
            opt.classList.add("active");
        });
    });

    // ===============================
    // TÌM VARIANT KHỚP
    // ===============================
    function findVariant() {
        return VARIANTS.find(v =>
            Object.entries(selectedAttrs)
                .every(([k,val]) => v.attrs[k] === val)
        );
    }

    // ===============================
    // ADD TO CART (AJAX)
    // ===============================
    window.addToCart = async function () {

        const requiredCount = container.querySelectorAll(".attr-group").length;
        if (Object.keys(selectedAttrs).length < requiredCount) {
            showToast("Vui lòng chọn đầy đủ biến thể", true);
            return;
        }

        const variant = findVariant();
        if (!variant) {
            showToast("Không tìm thấy biến thể phù hợp", true);
            return;
        }

        if ((variant.stock ?? 0) <= 0) {
            showToast("Biến thể đã hết hàng", true);
            return;
        }

        const qty = Math.max(1, parseInt(qtyInput.value) || 1);

        try {
            const res = await fetch("{{ route('linxen.cart') }}/add", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    sku: variant.sku,
                    name: "{{ $product->name }}",
                    price: variant.price,
                    image: "{{ $mainImage }}",
                    qty: qty,
                    attrs: selectedAttrs
                })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                showToast(data.message || "Không thể thêm vào giỏ", true);
                return;
            }

            showToast("Đã thêm sản phẩm vào giỏ hàng");

            // 👉 Optional: update mini cart count
            if (typeof updateMiniCart === "function") {
                updateMiniCart(data.cart_count);
            }

        } catch (err) {
            console.error(err);
            showToast("Lỗi kết nối. Vui lòng thử lại.", true);
        }
    };

});

// ===============================
// UTILS
// ===============================
function previewImage(src){
    const img = document.getElementById("lxMainImage");
    if (img) img.src = src;
}

function changeQty(step){
    const input = document.getElementById("lxQty");
    if (!input) return;
    input.value = Math.max(1, parseInt(input.value) + step);
}

function showToast(msg, error=false){
    const t = document.getElementById("lxToast");
    if (!t) return;
    t.textContent = msg;
    t.className = "lx-toast show" + (error ? " error":"");
    setTimeout(()=>t.classList.remove("show"), 2500);
}
</script>

