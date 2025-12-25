{{-- =====================================================
   PRODUCT ACTIONS – CLEAN CONTROL BAR (AJAX READY)
===================================================== --}}
<div class="lx-product-actions lx-pdp-actions">

    {{-- QUANTITY --}}
    <div class="lx-qty-bar">
        <button
            type="button"
            class="lx-qty-btn"
            aria-label="Giảm số lượng"
            onclick="changeQty(-1)"
        >−</button>

        <input
            type="number"
            id="lxQty"
            class="lx-qty-input"
            value="1"
            min="1"
            aria-label="Số lượng"
        >

        <button
            type="button"
            class="lx-qty-btn"
            aria-label="Tăng số lượng"
            onclick="changeQty(1)"
        >+</button>
    </div>

    {{-- ADD TO CART --}}
    <button
        class="lx-btn-addtocart"
        id="lxAddToCartBtn"
        type="button"
        data-add-to-cart

        {{-- 🔑 ID ERP / KiotViet (BẮT BUỘC) --}}
        data-product-id="{{ $product['product_id'] ?? $product['id'] }}"

        {{-- SKU hiển thị / tracking --}}
        data-sku="{{ $product['sku'] ?? $product['code'] }}"

        data-name="{{ $product['name'] }}"
        data-price="{{ $product['price'] ?? 0 }}"
        data-image="{{ $mainImage ?? '' }}"

        {{-- JS sẽ sync theo input #lxQty --}}
        data-qty="1"

        {{-- Attrs sẽ được JS update khi chọn biến thể --}}
        data-attrs='@json([])'
    >
        <span class="lx-btn-icon">👜</span>
        <span class="lx-btn-text">Thêm vào giỏ</span>
    </button>

</div>
