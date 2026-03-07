<div class="lx-product-actions lx-pdp-actions">

    {{-- QTY --}}
    <div class="lx-qty-bar">
        <button class="lx-qty-btn" onclick="changeQty(-1)">−</button>

        <input type="number" id="lxQty" value="1" min="1">

        <button class="lx-qty-btn" onclick="changeQty(1)">+</button>
    </div>

    {{-- BUTTON GROUP --}}
    <div class="lx-action-buttons">

        <button
        class="lx-btn-addtocart"
        id="lxAddToCartBtn"
        type="button"
        data-add-to-cart
        data-sku="{{ $product['sku'] ?? $product['code'] }}"
        data-name="{{ $product['name'] }}"
        data-price="{{ $product['price'] ?? 0 }}"
        data-image="{{ $mainImage ?? '' }}"
        data-qty="1"
        data-attrs='@json([])'
        >
        👜 Thêm vào giỏ
    </button>

    <button
    class="lx-btn-buynow"
    id="lxBuyNowBtn"
    type="button"
    >
    ⚡ Mua ngay
</button>

</div>

</div>