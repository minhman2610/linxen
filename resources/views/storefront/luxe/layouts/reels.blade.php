<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Reels — LIN XÉN</title>

    {{-- BASE --}}
    <link rel="stylesheet" href="/themes/luxe/assets/css/base.css">

    {{-- REELS --}}
    <link rel="stylesheet"
          href="/themes/luxe/assets/css/reels.css?v={{ filemtime(public_path('themes/luxe/assets/css/reels.css')) }}">

    {{-- SWIPER --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>

    @stack('head')
</head>

<body class="lx-reels-body">

    {{-- HEADER REELS --}}
    @include('storefront.luxe.components.reels-header')

    {{-- MAIN MEDIA --}}
    <main class="lx-reels-main">
        @yield('content')
    </main>

    {{-- PRODUCT BAR --}}
    @include('storefront.luxe.components.reels-product-bar')

    {{-- SCRIPTS --}}
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script src="/themes/luxe/assets/js/reels.js?v={{ filemtime(public_path('themes/luxe/assets/js/reels.js')) }}"></script>

    @stack('scripts')

</body>
</html>
