<header class="lxcv1-header" data-lxcv1-header>
    <div class="lxcv1-header__notice">
        Giá, màu, size và tồn kho được xác nhận từ hệ thống chính thức.
    </div>

    <div class="lxcv1-header__inner">
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
        </nav>

        <form class="lxcv1-header-search" method="get" action="{{ route('commerce.v2.search') }}">
            <label class="sr-only" for="lxcv1HeaderSearch">Tìm sản phẩm</label>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="6"></circle>
                <path d="m16 16 4 4"></path>
            </svg>
            <input
                id="lxcv1HeaderSearch"
                name="q"
                value="{{ request('q') }}"
                placeholder="Tìm tên, mã RS hoặc SKU"
                autocomplete="off"
            >
        </form>

        <div class="lxcv1-header-actions">
            <a href="{{ route('commerce.v2.account.index') }}" aria-label="Tài khoản">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M4 21a8 8 0 0 1 16 0"></path>
                </svg>
            </a>
            <a href="{{ route('commerce.v2.cart.index') }}" aria-label="Giỏ hàng">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 7h14l-1 13H6L5 7Z"></path>
                    <path d="M9 7a3 3 0 0 1 6 0"></path>
                </svg>
            </a>
        </div>
    </div>
</header>
