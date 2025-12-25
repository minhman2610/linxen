<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        @yield('title', 'LIN XÉN – Thời trang nữ')
    </title>

    {{-- CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- BASE CSS --}}
    <link rel="stylesheet" href="/themes/luxe/assets/css/base.css">

    {{-- ============================
        CHECKOUT / CHECKOUT SUCCESS CSS
    ============================= --}}
    @if(
        Route::is('linxen.checkout')
        || Route::is('linxen.checkout.place_order')
        || Route::is('linxen.checkout.success')
    )
        <link rel="stylesheet"
              href="/themes/luxe/assets/css/checkout.css?v={{ filemtime(public_path('themes/luxe/assets/css/checkout.css')) }}">
    @endif

    {{-- PAGE LEVEL CSS --}}
    @stack('styles')
</head>

<body class="luxe-body {{ Route::currentRouteName() ?? '' }}">

    {{-- HEADER --}}
    @include('storefront.luxe.partials.header')

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
        <div class="lx-flash-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="lx-flash-warning">
            {{ session('warning') }}
        </div>
    @endif

    {{-- MAIN CONTENT --}}
    <main class="luxe-main">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    @include('storefront.luxe.partials.footer')

    {{-- ============================
        CHECKOUT ONLY JS
    ============================= --}}
    @if(
        Route::is('linxen.checkout')
        && file_exists(public_path('themes/luxe/assets/js/checkout.js'))
    )
        <script
            src="/themes/luxe/assets/js/checkout.js?v={{ filemtime(public_path('themes/luxe/assets/js/checkout.js')) }}"
            defer>
        </script>
    @endif

    {{-- GLOBAL JS --}}
    <script src="/themes/luxe/assets/js/app.js" defer></script>

    {{-- PAGE LEVEL JS --}}
    @stack('scripts')

</body>
</html>
