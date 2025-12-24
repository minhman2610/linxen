<div class="lx-reels-product-bar">

    {{-- ===============================
         THUMBNAIL
         =============================== --}}
    <div class="lx-reels-thumb">
        <img
            id="lxReelsThumb"
            src="/images/no-image.png"
            alt="Product thumbnail">
    </div>

    {{-- ===============================
         INFO
         =============================== --}}
    <div class="lx-reels-product-info">
        <strong id="lxReelsName">Tên sản phẩm</strong>

        <div class="lx-reels-meta">
            <span class="lx-reels-price" id="lxReelsPrice">0₫</span>
            <span class="lx-reels-tag" id="lxReelsTag">Có sẵn</span>
        </div>
    </div>

    {{-- ===============================
         ACTION ICONS
         =============================== --}}
    <div class="lx-reels-actions">

        {{-- WISHLIST --}}
        <button
            type="button"
            class="lx-reels-action-btn lx-reels-wishlist"
            id="lxReelsWishlist"
            aria-label="Yêu thích">

            <svg viewBox="0 0 24 24" width="20" height="20"
                 fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/>
            </svg>
        </button>

        {{-- ADD TO CART --}}
        <button
            type="button"
            class="lx-reels-action-btn lx-reels-add-cart"
            id="lxReelsAddCart"
            aria-label="Thêm vào giỏ">

            <svg viewBox="0 0 24 24" width="20" height="20"
                 fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.6a2 2 0 0 0 2-1.6L23 6H6"></path>
            </svg>
        </button>

    </div>

</div>
