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
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FashionBrand",
      "name": "LIN XÉN",
      "url": "{{ url('/') }}",
      "logo": "{{ asset('themes/luxe/assets/images/logo.png') }}",
      "description": "Thời trang nữ cao cấp – Váy thiết kế LIN XÉN",
      "sameAs": [
        "https://www.facebook.com/linxen.official"
      ]
    }
    </script>

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
        @if(file_exists(public_path('themes/luxe/assets/js/product.js')))
            <script src="{{ asset('themes/luxe/assets/js/product.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/product.js')) }}"></script>
        @endif

        @if(file_exists(public_path('themes/luxe/assets/js/product-real-gallery.js')))
            <script src="{{ asset('themes/luxe/assets/js/product-real-gallery.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/product-real-gallery.js')) }}"></script>
        @endif
    @endif

    @if(request()->routeIs('linxen.cart'))
        @if(file_exists(public_path('themes/luxe/assets/js/cart.js')))
            <script src="{{ asset('themes/luxe/assets/js/cart.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/cart.js')) }}"></script>
        @endif
    @endif

    @if(request()->routeIs('linxen.checkout'))
        @if(file_exists(public_path('themes/luxe/assets/js/checkout.js')))
            <script src="{{ asset('themes/luxe/assets/js/checkout.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/checkout.js')) }}" defer></script>
        @endif
    @endif

    @if(file_exists(public_path('themes/luxe/assets/js/mobile-menu.js')))
        <script src="{{ asset('themes/luxe/assets/js/mobile-menu.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/mobile-menu.js')) }}"></script>
    @endif

    @if(file_exists(public_path('themes/luxe/assets/js/theme.js')))
        <script src="{{ asset('themes/luxe/assets/js/theme.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/theme.js')) }}"></script>
    @endif

    @stack('scripts')

</body>

</html>
