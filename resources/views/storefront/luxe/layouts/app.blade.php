<!DOCTYPE html>
<html lang="vi">
<head>
    {{-- =====================================================
        BASIC
    ===================================================== --}}
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- =====================================================
        TITLE
    ===================================================== --}}
    <title>
        @yield('title', ($brand ?? 'LIN XÉN') . ' — ĐAM MÊ VÁY')
    </title>

    {{-- =====================================================
        META SEO CORE
    ===================================================== --}}
    <meta name="description"
          content="@yield(
              'meta_description',
              'LIN XÉN – Thời trang nữ cao cấp, váy thiết kế tinh tế, phom dáng chuẩn cho phụ nữ hiện đại.'
          )">

    <meta name="keywords"
          content="@yield(
              'meta_keywords',
              'LIN XÉN, váy thiết kế, thời trang nữ cao cấp, đầm nữ, váy nữ đẹp'
          )">

    <meta name="robots" content="index, follow">
    <meta name="author" content="LIN XÉN">
    <!-- =====================================================
     FAVICON – LIN XÉN
===================================================== -->

<link rel="icon"
      href="{{ asset('themes/luxe/assets/favicon/favicon.ico') }}"
      type="image/x-icon">

<link rel="icon" type="image/png" sizes="32x32"
      href="{{ asset('themes/luxe/assets/favicon/favicon-32x32.png') }}">

<link rel="icon" type="image/png" sizes="16x16"
      href="{{ asset('themes/luxe/assets/favicon/favicon-16x16.png') }}">

<link rel="apple-touch-icon" sizes="180x180"
      href="{{ asset('themes/luxe/assets/favicon/apple-touch-icon.png') }}">

    {{-- =====================================================
        CANONICAL
    ===================================================== --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- =====================================================
        OPEN GRAPH – FACEBOOK / ZALO
    ===================================================== --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="LIN XÉN">
    <meta property="og:title"
          content="@yield('og_title', 'LIN XÉN — ĐAM MÊ VÁY')">
    <meta property="og:description"
          content="@yield(
              'og_description',
              'Thời trang nữ cao cấp – Váy thiết kế LIN XÉN, tinh tế và sang trọng.'
          )">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image"
          content="@yield('og_image', asset('themes/luxe/assets/images/og-linxen.jpg'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- =====================================================
        TWITTER CARD
    ===================================================== --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title"
          content="@yield('og_title', 'LIN XÉN — ĐAM MÊ VÁY')">
    <meta name="twitter:description"
          content="@yield(
              'og_description',
              'Thời trang nữ cao cấp – Váy thiết kế LIN XÉN.'
          )">
    <meta name="twitter:image"
          content="@yield('og_image', asset('themes/luxe/assets/images/og-linxen.jpg'))">

    {{-- =====================================================
        FAVICON
    ===================================================== --}}
    <link rel="icon" href="{{ asset('themes/luxe/assets/images/favicon.png') }}">

    {{-- =====================================================
        BASE CSS (GLOBAL)
    ===================================================== --}}
    <link rel="stylesheet"
          href="/themes/luxe/assets/css/base.css?v={{ filemtime(public_path('themes/luxe/assets/css/base.css')) }}">

    <link rel="stylesheet"
          href="/themes/luxe/assets/css/theme.css?v={{ filemtime(public_path('themes/luxe/assets/css/theme.css')) }}">

    <link rel="stylesheet"
          href="/themes/luxe/assets/css/icons.css?v={{ filemtime(public_path('themes/luxe/assets/css/icons.css')) }}">

    <link rel="stylesheet"
          href="/themes/luxe/assets/css/mobile-menu.css?v={{ filemtime(public_path('themes/luxe/assets/css/mobile-menu.css')) }}">

    {{-- =====================================================
        HEADER / FOOTER (GLOBAL)
    ===================================================== --}}
    <link rel="stylesheet"
          href="/themes/luxe/assets/css/header.css?v={{ filemtime(public_path('themes/luxe/assets/css/header.css')) }}">

    <link rel="stylesheet"
          href="/themes/luxe/assets/css/footer.css?v={{
              file_exists(public_path('themes/luxe/assets/css/footer.css'))
                ? filemtime(public_path('themes/luxe/assets/css/footer.css'))
                : time()
          }}">

    <link rel="stylesheet"
          href="/themes/luxe/assets/css/bottom-nav.css?v={{ filemtime(public_path('themes/luxe/assets/css/bottom-nav.css')) }}">

    {{-- =====================================================
    HOME ONLY CSS
===================================================== --}}
@if(request()->routeIs('home') || request()->routeIs('linxen.home'))
    <link rel="stylesheet"
          href="/themes/luxe/assets/css/featured-products.css?v={{ filemtime(public_path('themes/luxe/assets/css/featured-products.css')) }}">
    <link rel="stylesheet"
          href="/themes/luxe/assets/css/trust-visual.css?v={{ filemtime(public_path('themes/luxe/assets/css/trust-visual.css')) }}">
    <link rel="stylesheet"
          href="/themes/luxe/assets/css/home.css?v={{ filemtime(public_path('themes/luxe/assets/css/home.css')) }}">
@endif

{{-- =====================================================
    PRODUCT ONLY CSS
===================================================== --}}
@if(request()->routeIs('product.*') || request()->routeIs('linxen.product'))
    <link rel="stylesheet"
          href="{{ asset('themes/luxe/assets/css/product.css') }}?v={{ filemtime(public_path('themes/luxe/assets/css/product.css')) }}">
    <link rel="stylesheet"
          href="{{ asset('themes/luxe/assets/css/product-actions.css') }}?v={{ filemtime(public_path('themes/luxe/assets/css/product-actions.css')) }}">
    <link rel="stylesheet"
          href="{{ asset('themes/luxe/assets/css/brand-simple.css') }}?v={{ filemtime(public_path('themes/luxe/assets/css/brand-simple.css')) }}">
    <link rel="stylesheet"
          href="{{ asset('themes/luxe/assets/css/product-real-gallery.css') }}?v={{ filemtime(public_path('themes/luxe/assets/css/product-real-gallery.css')) }}">
    <link rel="stylesheet"
          href="{{ asset('themes/luxe/assets/css/product-suggested.css') }}?v={{ filemtime(public_path('themes/luxe/assets/css/product-suggested.css')) }}">
    <link rel="stylesheet"
          href="{{ asset('themes/luxe/assets/css/product-breadcrumb.css') }}?v={{ filemtime(public_path('themes/luxe/assets/css/product-breadcrumb.css')) }}">
    <link rel="stylesheet"
          href="{{ asset('themes/luxe/assets/css/size-guide.css') }}?v={{ filemtime(public_path('themes/luxe/assets/css/size-guide.css')) }}">
@endif

{{-- =====================================================
    CART ONLY CSS
===================================================== --}}
@if(request()->routeIs('linxen.cart'))
    <link rel="stylesheet"
          href="{{ asset('themes/luxe/assets/css/cart.css') }}?v={{ filemtime(public_path('themes/luxe/assets/css/cart.css')) }}">
@endif

{{-- =====================================================
    ACCOUNT / CHECKOUT CSS
===================================================== --}}
@if(Route::is('linxen.account.*'))
    <link rel="stylesheet"
          href="/themes/luxe/assets/css/account.css?v={{ filemtime(public_path('themes/luxe/assets/css/account.css')) }}">
@endif

@if(request()->routeIs('linxen.checkout*'))
    <link rel="stylesheet"
          href="/themes/luxe/assets/css/checkout.css?v={{ filemtime(public_path('themes/luxe/assets/css/checkout.css')) }}">
@endif

<link rel="stylesheet"
      href="/themes/luxe/assets/css/auth.css?v={{ filemtime(public_path('themes/luxe/assets/css/auth.css')) }}">

@if(Route::is('linxen.account.addresses'))
    <link rel="stylesheet"
          href="/themes/luxe/assets/css/account-addresses.css?v={{ filemtime(public_path('themes/luxe/assets/css/account-addresses.css')) }}">
@endif

@if(Route::is('linxen.account.profile'))
    <link rel="stylesheet"
          href="/themes/luxe/assets/css/account-profile.css?v={{ filemtime(public_path('themes/luxe/assets/css/account-profile.css')) }}">
@endif

@if(
    request()->routeIs('linxen.account.orders') ||
    request()->routeIs('linxen.account.orders.show')
)
    <link rel="stylesheet"
          href="{{ asset('themes/luxe/assets/css/orders.css') }}">
@endif

{{-- =====================================================
    COLLECTION CSS
===================================================== --}}
@if(request()->routeIs('linxen.collection'))
    <link rel="stylesheet"
          href="{{ asset('themes/luxe/assets/css/collection.css') }}?v={{ filemtime(public_path('themes/luxe/assets/css/collection.css')) }}">
@endif


    {{-- Swiper CSS --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    {{-- =====================================================
    PAGE META / EXTRA HEAD
===================================================== --}}
@stack('head')

{{-- =====================================================
    STRUCTURED DATA – BRAND
===================================================== --}}
@verbatim
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FashionBrand",
  "name": "LIN XÉN",
  "url": "https://linxen.vn",
  "logo": "https://linxen.vn/themes/luxe/assets/images/logo.png",
  "description": "Thời trang nữ cao cấp – Váy thiết kế LIN XÉN",
  "sameAs": [
    "https://www.facebook.com/linxen.vn"
  ]
}
</script>
@endverbatim


</head>

<body class="luxe-body">

    {{-- ANNOUNCEMENT --}}
    @include('storefront.luxe.components.announcement')

    {{-- HEADER --}}
    @include('storefront.luxe.components.header')

    {{-- MOBILE MENU --}}
    @include('storefront.luxe.components.mobile-menu')

    {{-- MAIN CONTENT --}}
    <main class="luxe-main">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    @include('storefront.luxe.components.footer')

    {{-- =====================================================
        BOTTOM NAV (HOME ONLY)
    ===================================================== --}}
    @if(request()->routeIs('home') || request()->routeIs('linxen.home'))
        @include('storefront.luxe.components.bottom-nav')
    @endif

    {{-- =====================================================
        SCRIPTS
    ===================================================== --}}
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    @if(request()->routeIs('product.*') || request()->routeIs('linxen.product'))
        <script src="{{ asset('themes/luxe/assets/js/product.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/product.js')) }}"></script>
        <script src="{{ asset('themes/luxe/assets/js/product-real-gallery.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/product-real-gallery.js')) }}"></script>
    @endif

    @if(request()->routeIs('linxen.cart'))
        <script src="{{ asset('themes/luxe/assets/js/cart.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/cart.js')) }}"></script>
    @endif

    @if(Route::is('linxen.checkout') && file_exists(public_path('themes/luxe/assets/js/checkout.js')))
        <script src="/themes/luxe/assets/js/checkout.js?v={{ filemtime(public_path('themes/luxe/assets/js/checkout.js')) }}" defer></script>
    @endif

    <script src="/themes/luxe/assets/js/mobile-menu.js?v={{ filemtime(public_path('themes/luxe/assets/js/mobile-menu.js')) }}"></script>
    <script src="{{ asset('themes/luxe/assets/js/theme.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/theme.js')) }}"></script>

    @stack('scripts')

</body>
</html>
