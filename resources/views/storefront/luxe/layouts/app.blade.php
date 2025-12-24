<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $brand ?? 'LIN XÉN' }} — ĐAM MÊ VÁY</title>

    {{-- ============================
        BASE CSS
    ============================= --}}
    <link rel="stylesheet" href="/themes/luxe/assets/css/base.css">
    <link rel="stylesheet" href="/themes/luxe/assets/css/theme.css">
    <link rel="stylesheet" href="/themes/luxe/assets/css/icons.css">

    {{-- ============================
        MOBILE MENU CSS
    ============================= --}}
    <link rel="stylesheet" href="/themes/luxe/assets/css/mobile-menu.css">

    <link rel="stylesheet"
          href="/themes/luxe/assets/css/featured-products.css?v={{ filemtime(public_path('themes/luxe/assets/css/featured-products.css')) }}">

    <link rel="stylesheet"
          href="/themes/luxe/assets/css/trust-visual.css?v={{ filemtime(public_path('themes/luxe/assets/css/trust-visual.css')) }}">

    <link rel="stylesheet"
          href="/themes/luxe/assets/css/home.css?v={{ filemtime(public_path('themes/luxe/assets/css/home.css')) }}">

    <link rel="stylesheet"
          href="/themes/luxe/assets/css/bottom-nav.css?v={{ filemtime(public_path('themes/luxe/assets/css/bottom-nav.css')) }}">
    <link rel="stylesheet"
      href="/themes/luxe/assets/css/footer.css?v={{ file_exists(public_path('themes/luxe/assets/css/footer.css')) 
            ? filemtime(public_path('themes/luxe/assets/css/footer.css')) 
            : time() }}">
            

     
    {{-- Page-specific head --}}
    @stack('head')
</head>

<body class="luxe-body">

    {{-- ============================
        ANNOUNCEMENT BAR
    ============================= --}}
    @include('storefront.luxe.components.announcement')

    {{-- ============================
        HEADER (GLOBAL)
    ============================= --}}
    @include('storefront.luxe.components.header')

    {{-- ============================
        MOBILE MENU SIDEBAR
    ============================= --}}
    @include('storefront.luxe.components.mobile-menu')

    {{-- ============================
        MAIN CONTENT
    ============================= --}}
    <main class="luxe-main">
        @yield('content')
    </main>

    {{-- ============================
        FOOTER (GLOBAL)
    ============================= --}}
    @include('storefront.luxe.components.footer')

    {{-- ============================
        BOTTOM NAVIGATION (MOBILE)
    ============================= --}}
    @include('storefront.luxe.components.bottom-nav')

    {{-- ============================
        SCRIPTS
    ============================= --}}
    <script src="/themes/luxe/assets/js/theme.js"></script>

    <script src="/themes/luxe/assets/js/mobile-menu.js?v={{ filemtime(public_path('themes/luxe/assets/js/mobile-menu.js')) }}"></script>

    

    {{-- Page-specific scripts --}}
    @stack('scripts')

</body>
</html>
