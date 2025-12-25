{{-- =====================================================
   PRODUCT ACTIONS – LUXURY CTA
===================================================== --}}
<div class="lx-product-actions lx-pdp-actions">

    {{-- QUANTITY --}}
    <div class="lx-qty luxury-qty">
        <button type="button"
                class="lx-qty-btn"
                aria-label="Giảm số lượng"
                onclick="changeQty(-1)">
            −
        </button>

        <input type="number"
               id="lxQty"
               class="lx-qty-input"
               value="1"
               min="1"
               aria-label="Số lượng">

        <button type="button"
                class="lx-qty-btn"
                aria-label="Tăng số lượng"
                onclick="changeQty(1)">
            +
        </button>
    </div>

    {{-- ADD TO CART --}}
    <button
        class="lx-btn-addtocart"
        id="lxAddToCartBtn"
        type="button">
        <span class="lx-btn-icon">👜</span>
        <span class="lx-btn-text">Thêm vào giỏ</span>
    </button>

</div>
