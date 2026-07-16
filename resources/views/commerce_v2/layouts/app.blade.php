<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle ?? 'LIN XÉN' }}</title>
    <meta name="description" content="{{ $pageDescription ?? 'Váy thiết kế LIN XÉN.' }}">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="LIN XÉN">
    <meta property="og:title" content="{{ $pageTitle ?? 'LIN XÉN' }}">
    <meta property="og:description" content="{{ $pageDescription ?? 'Váy thiết kế LIN XÉN.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if(!empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    <link rel="stylesheet" href="{{ asset('commerce-v2/commerce.css') }}?v=1">
    @stack('head')
</head>
<body class="lxv2-body">
    <a class="lxv2-skip" href="#main-content">Bỏ qua điều hướng</a>

    <header class="lxv2-header">
        <div class="lxv2-header__inner">
            <a class="lxv2-brand" href="{{ route('commerce.v2.home') }}" aria-label="LIN XÉN">
                <span class="lxv2-brand__mark">LX</span>
                <span class="lxv2-brand__text">
                    <strong>{{ config('commerce_v2.brand_name', 'LIN XÉN') }}</strong>
                    <small>Váy thiết kế</small>
                </span>
            </a>

            <nav class="lxv2-nav" aria-label="Điều hướng chính">
                <a href="{{ route('commerce.v2.home') }}" @class(['active' => request()->routeIs('commerce.v2.home')])>Trang chủ</a>
                <a href="{{ route('commerce.v2.shop') }}" @class(['active' => request()->routeIs('commerce.v2.shop')])>Sản phẩm</a>
                <a href="{{ route('commerce.v2.search') }}" @class(['active' => request()->routeIs('commerce.v2.search')])>Tìm kiếm</a>
            </nav>

            <form class="lxv2-header-search" method="get" action="{{ route('commerce.v2.search') }}">
                <label class="sr-only" for="lxv2HeaderSearch">Tìm sản phẩm</label>
                <input
                    id="lxv2HeaderSearch"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tên, mã sản phẩm, SKU..."
                    autocomplete="off"
                >
                <button type="submit" aria-label="Tìm kiếm">⌕</button>
            </form>
        </div>
    </header>

    @if(($cacheStatus ?? null) === 'stale')
        <div class="lxv2-status-bar">
            Dữ liệu đang hiển thị từ bản lưu gần nhất do hệ thống cập nhật tạm thời gián đoạn.
        </div>
    @endif

    <main id="main-content" class="lxv2-main">
        @yield('content')
    </main>

    <footer class="lxv2-footer">
        <div>
            <strong>{{ config('commerce_v2.brand_name', 'LIN XÉN') }}</strong>
            <p>Thiết kế dành cho những khoảnh khắc anh muốn mình thật đẹp.</p>
        </div>
        <div class="lxv2-footer__links">
            <a href="{{ route('commerce.v2.shop') }}">Sản phẩm</a>
            <a href="{{ route('commerce.v2.search') }}">Tìm kiếm</a>
            @if(config('commerce_v2.support_url'))
                <a href="{{ config('commerce_v2.support_url') }}" rel="nofollow">Hỗ trợ</a>
            @endif
        </div>
    </footer>

    <nav class="lxv2-bottom-nav" aria-label="Điều hướng di động">
        <a href="{{ route('commerce.v2.home') }}" @class(['active' => request()->routeIs('commerce.v2.home')])>
            <span>⌂</span><small>Trang chủ</small>
        </a>
        <a href="{{ route('commerce.v2.shop') }}" @class(['active' => request()->routeIs('commerce.v2.shop')])>
            <span>◇</span><small>Sản phẩm</small>
        </a>
        <a href="{{ route('commerce.v2.search') }}" @class(['active' => request()->routeIs('commerce.v2.search')])>
            <span>⌕</span><small>Tìm kiếm</small>
        </a>
    </nav>

    <script src="{{ asset('commerce-v2/commerce.js') }}?v=1" defer></script>
    @stack('scripts')
</body>
</html>
