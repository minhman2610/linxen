<header class="lx-reels-header">

    {{-- LEFT: BACK / MENU --}}
    <button
        class="lx-reels-icon lx-reels-back"
        onclick="history.back()"
        aria-label="Quay lại">
        ‹
    </button>

    {{-- CENTER: MODE SWITCH --}}
    <div class="lx-reels-mode">
        <span class="active" data-mode="image">Ảnh</span>
        <span data-mode="video">Video</span>
    </div>

    {{-- RIGHT: ACTIONS --}}
    <div class="lx-reels-actions">

        {{-- WISHLIST --}}
        <button
            class="lx-reels-icon lx-reels-wishlist"
            id="lxReelsHeaderWishlist"
            aria-label="Yêu thích">
            ❤️
        </button>

        {{-- CART --}}
        <a
            href="{{ route('linxen.cart') }}"
            class="lx-reels-icon lx-reels-cart"
            aria-label="Giỏ hàng">
            🛒
            <em id="lxCartCount">
                {{ array_sum(array_column(session('cart', []), 'qty')) }}
            </em>
        </a>

    </div>

</header>
