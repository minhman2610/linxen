<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- CSS ELLA --}}
    <link rel="stylesheet" href="/themes/ella/assets/base.css">
    <link rel="stylesheet" href="/themes/ella/assets/vendor.css">
    <link rel="stylesheet" href="/themes/ella/assets/section.css">
    <link rel="stylesheet" href="/themes/ella/assets/component-card.css">
    <link rel="stylesheet" href="/themes/ella/assets/component-slider.css">
    <link rel="stylesheet" href="/themes/ella/assets/component-slideshow.css">

    <title>LINXEN</title>

    @stack('styles')
</head>

<body class="lx-body">

    {{-- Header --}}
    @includeIf('storefront.ella.components.header')

    <main class="lx-main">
        @yield('content')
    </main>

    {{-- Footer --}}
    @includeIf('storefront.ella.components.footer')

    {{-- Bottom Navigation --}}
    @includeIf('storefront.ella.components.bottom-nav')


    {{-- JS ELLA --}}
    <script src="/themes/ella/assets/vendor.js"></script>
    <script src="/themes/ella/assets/global.js"></script>
    <script src="/themes/ella/assets/component-slider.js"></script>
    <script src="/themes/ella/assets/component-slideshow.js"></script>

    @stack('scripts')
</body>
</html>
