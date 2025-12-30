<!DOCTYPE html>
<html lang="vi">
<head>
    

    

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
