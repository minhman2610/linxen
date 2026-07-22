@php
    $lxcv1CartQuantity = collect((array) session('commerce_v2.cart.items', []))
        ->sum(fn ($line) => max(0, (int) data_get($line, 'quantity', 0)));
@endphp

<header class="lxcv1-header" data-lxcv1-header>
    @unless(request()->routeIs('commerce.v2.home'))
        <div class="lxcv1-header__notice">
            Giá, màu, size và tồn kho được xác nhận từ hệ thống chính thức.
        </div>
    @endunless

    <div class="lxcv1-header__inner">
        <button
            class="lxcv1-menu-toggle"
            type="button"
            aria-label="Mở menu"
            aria-controls="lxcv1MobileDrawer"
            aria-expanded="false"
            data-lxcv1-drawer-open
        >
            <span></span><span></span><span></span>
        </button>

        <a class="lxcv1-brand" href="{{ route('commerce.v2.home') }}" aria-label="LIN XÉN">
            <span class="lxcv1-brand__monogram">LX</span>
            <span>
                <strong>LIN XÉN</strong>
                <small>Modern womenswear</small>
            </span>
        </a>

        <nav class="lxcv1-nav" aria-label="Điều hướng chính">
            <a href="{{ route('commerce.v2.home') }}" @class(['is-active' => request()->routeIs('commerce.v2.home')])>Trang chủ</a>
            <a href="{{ route('commerce.v2.shop') }}" @class(['is-active' => request()->routeIs('commerce.v2.shop') || request()->routeIs('commerce.v2.collection')])>Sản phẩm</a>
            <a href="{{ route('commerce.v2.discover') }}" @class(['is-active' => request()->routeIs('commerce.v2.discover')])>Khám phá</a>
            <a href="{{ route('commerce.v2.video') }}" @class(['is-active' => request()->routeIs('commerce.v2.video')])>Video</a>
        </nav>

        <div class="lxcv1-header-actions">
            <button
                type="button"
                aria-label="Mở tìm kiếm"
                aria-controls="lxcv1SearchPanel"
                aria-expanded="false"
                data-lxcv1-search-open
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="6"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
            </button>
            <a href="{{ route('commerce.v2.account.index') }}" aria-label="Tài khoản">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M4 21a8 8 0 0 1 16 0"></path>
                </svg>
            </a>
            <a
                href="{{ route('commerce.v2.cart.index') }}"
                aria-label="Giỏ hàng"
                data-lxcv1-cart-link
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 7h14l-1 13H6L5 7Z"></path>
                    <path d="M9 7a3 3 0 0 1 6 0"></path>
                </svg>
                <span
                    class="lxcv1-cart-badge"
                    data-lxcv1-cart-count
                    @if($lxcv1CartQuantity < 1) hidden @endif
                >{{ $lxcv1CartQuantity }}</span>
            </a>
        </div>
    </div>
</header>

<div
    id="lxcv1SearchPanel"
    class="lxcv1-search-panel"
    aria-hidden="true"
    data-lxcv1-search-panel
>
    <form method="get" action="{{ route('commerce.v2.search') }}" role="search">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="6"></circle>
            <path d="m16 16 4 4"></path>
        </svg>
        <label class="sr-only" for="lxcv1HeaderSearch">Tìm sản phẩm</label>
        <input
            id="lxcv1HeaderSearch"
            name="q"
            value="{{ request('q') }}"
            placeholder="Tìm tên, mã RS hoặc SKU"
            autocomplete="off"
            data-lxcv1-search-input
        >
        <button type="submit">Tìm</button>
        <button type="button" aria-label="Đóng tìm kiếm" data-lxcv1-search-close>×</button>
    </form>
</div>

<button
    class="lxcv1-drawer-backdrop"
    type="button"
    aria-label="Đóng menu"
    tabindex="-1"
    data-lxcv1-drawer-backdrop
></button>

<aside
    id="lxcv1MobileDrawer"
    class="lxcv1-drawer"
    aria-hidden="true"
    aria-label="Menu LIN XÉN"
    data-lxcv1-drawer
>
    <header>
        <a href="{{ route('commerce.v2.home') }}">
            <span>LX</span>
            <strong>LIN XÉN</strong>
        </a>
        <button type="button" aria-label="Đóng menu" data-lxcv1-drawer-close>×</button>
    </header>

    <nav aria-label="Điều hướng mobile">
        <a href="{{ route('commerce.v2.home') }}">Trang chủ <span>01</span></a>
        <a href="{{ route('commerce.v2.shop') }}">Sản phẩm <span>02</span></a>
        <a href="{{ route('commerce.v2.video') }}">Image Stories <span>03</span></a>
        <a href="{{ route('commerce.v2.search') }}">Tìm kiếm <span>04</span></a>
        <a href="{{ route('commerce.v2.cart.index') }}">Giỏ hàng <span>05</span></a>
        <a href="{{ route('commerce.v2.account.index') }}">Tài khoản <span>06</span></a>
    </nav>

    <p>Modern womenswear · LIN XÉN</p>
</aside>
