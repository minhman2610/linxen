<!doctype html>
<html lang="vi">
<head>
    {{-- AI_PATCH_LINXEN_LUXE_COMMERCE_DEFAULT_THEME_V1 --}}
    @php
        $commerceTheme = app(
            \App\Services\CommerceV2\CommerceThemePreviewService::class
        )->active(session());
        $luxeCommercePreview = $commerceTheme
            === \App\Services\CommerceV2\CommerceThemePreviewService::THEME;
        $isVideoExperience = request()->routeIs('commerce.v2.video');
    @endphp

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

    <link rel="stylesheet" href="{{ asset('commerce-v2/commerce.css') }}?v=5">
    @if($luxeCommercePreview)
        <link rel="stylesheet" href="{{ asset('commerce-v2/themes/luxe-commerce-v1.css') }}?v=16">
    @endif
    @stack('head')
</head>
<body
    class="lxv2-body {{ $luxeCommercePreview ? 'lxv2-theme--luxe-commerce-v1' : '' }} {{ $isVideoExperience ? 'lxv2-page--video' : '' }}"
    data-commerce-theme="{{ $commerceTheme ?: 'live_default' }}"
>
    <a class="lxv2-skip" href="#main-content">Bỏ qua điều hướng</a>

    @if($luxeCommercePreview)
        @unless($isVideoExperience)
            @if(request()->routeIs('commerce.v2.home'))
                @include('commerce_v2.themes.luxe_commerce_v1.partials.home-ticker')
            @endif
            @include('commerce_v2.themes.luxe_commerce_v1.shell.header')
        @endunless
    @else
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
                    <a href="{{ route('commerce.v2.discover') }}" @class(['active' => request()->routeIs('commerce.v2.discover')])>Khám phá</a>
                    <a href="{{ route('commerce.v2.cart.index') }}" @class(['active' => request()->routeIs('commerce.v2.cart.*')])>Giỏ hàng</a>
                    <a href="{{ route('commerce.v2.orders.index') }}" @class(['active' => request()->routeIs('commerce.v2.orders.*')])>Đơn hàng</a>
                    <a href="{{ route('commerce.v2.account.index') }}" @class(['active' => request()->routeIs('commerce.v2.account.*')])>Tài khoản</a>
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
    @endif

    @if(($cacheStatus ?? null) === 'stale')
        <div class="lxv2-status-bar">
            Dữ liệu đang hiển thị từ bản lưu gần nhất do hệ thống cập nhật tạm thời gián đoạn.
        </div>
    @endif

    <main
        id="main-content"
        @class([
            'lxv2-main',
            'lxcv1-main--home' => $luxeCommercePreview
                && request()->routeIs('commerce.v2.home'),
        ])
    >
        @if(session('success'))
            <div class="lxv2-alert lxv2-alert--success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="lxv2-alert lxv2-alert--error">
                {{ session('error') }}
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="lxv2-alert lxv2-alert--error">
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </main>

    @if($luxeCommercePreview)
        @unless($isVideoExperience)
            @include('commerce_v2.themes.luxe_commerce_v1.shell.footer')
        @endunless
        @unless(
            request()->routeIs('commerce.v2.product')
            || request()->routeIs('commerce.v2.product.preview')
        )
            @include('commerce_v2.themes.luxe_commerce_v1.shell.bottom-nav')
        @endunless
    @else
        @unless($isVideoExperience)
            <footer class="lxv2-footer">
                <div>
                    <strong>{{ config('commerce_v2.brand_name', 'LIN XÉN') }}</strong>
                    <p>Thiết kế dành cho những khoảnh khắc anh muốn mình thật đẹp.</p>
                </div>
                <div class="lxv2-footer__links">
                    <a href="{{ route('commerce.v2.shop') }}">Sản phẩm</a>
                    <a href="{{ route('commerce.v2.discover') }}">Khám phá</a>
                    <a href="{{ route('commerce.v2.orders.index') }}">Đơn hàng</a>
                    <a href="{{ route('commerce.v2.search') }}">Tìm kiếm</a>
                    @if(config('commerce_v2.support_url'))
                        <a href="{{ config('commerce_v2.support_url') }}" rel="nofollow">Hỗ trợ</a>
                    @endif
                </div>
            </footer>
        @endunless

        <nav class="lxv2-bottom-nav" aria-label="Điều hướng di động">
            <a href="{{ route('commerce.v2.home') }}" @class(['active' => request()->routeIs('commerce.v2.home')])>
                <span>⌂</span><small>Trang chủ</small>
            </a>
            <a href="{{ route('commerce.v2.shop') }}" @class(['active' => request()->routeIs('commerce.v2.shop')])>
                <span>◇</span><small>Sản phẩm</small>
            </a>
            <a href="{{ route('commerce.v2.video') }}" @class(['active' => request()->routeIs('commerce.v2.video')])>
                <span>▷</span><small>Video</small>
            </a>
            <a href="{{ route('commerce.v2.cart.index') }}" @class(['active' => request()->routeIs('commerce.v2.cart.*')])>
                <span>□</span><small>Giỏ hàng</small>
            </a>
            <a href="{{ route('commerce.v2.account.index') }}" @class(['active' => request()->routeIs('commerce.v2.account.*') || request()->routeIs('commerce.v2.orders.*')])>
                <span>○</span><small>Tài khoản</small>
            </a>
        </nav>
    @endif

    <script src="{{ asset('commerce-v2/commerce.js') }}?v=5" defer></script>
    @if($luxeCommercePreview)
        <script src="{{ asset('commerce-v2/themes/luxe-commerce-v1.js') }}?v=16" defer></script>
    @endif
    @stack('scripts')
</body>
</html>
