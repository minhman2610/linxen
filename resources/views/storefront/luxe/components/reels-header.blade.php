<header class="lx-reels-header" data-reels-header>

    {{-- LEFT: HOME --}}
    <a href="{{ route('linxen.home') }}"
       class="lx-reels-icon lx-reels-home"
       aria-label="Trang chủ">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none"
             stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 10.5L12 3l9 7.5"></path>
            <path d="M5 9.5V21h14V9.5"></path>
        </svg>
    </a>

    {{-- CENTER: MODE --}}
    <div class="lx-reels-mode">
        <span class="active" data-mode="image">ẢNH</span>
        <span data-mode="video">VIDEO</span>
    </div>

    {{-- RIGHT: ACTIONS --}}
    <div class="lx-reels-actions">

        {{-- WISHLIST --}}
        <button class="lx-reels-icon lx-reels-wishlist"
                id="lxReelsHeaderWishlist"
                aria-label="Yêu thích">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/>
            </svg>
        </button>

        {{-- CART --}}
        <a href="{{ route('linxen.cart') }}"
           class="lx-reels-icon lx-reels-cart"
           aria-label="Giỏ hàng">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.6a2 2 0 0 0 2-1.6L23 6H6"></path>
            </svg>
            <em id="lxCartCount">
                {{ array_sum(array_column(session('cart', []), 'qty')) }}
            </em>
        </a>

        {{-- SEARCH --}}
        <a href="{{ route('linxen.search') }}"
           class="lx-reels-icon lx-reels-search"
           aria-label="Tìm kiếm">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="M21 21l-4.3-4.3"></path>
            </svg>
        </a>

    </div>
</header>
