<!DOCTYPE html>
<html lang="vi" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- CSS CORE của Ella --}}
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/base.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/vendor.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/section.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/component-card.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/component-slider.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/component-slideshow.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/toolbar-mobile.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/section-header.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/section-footer.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/template-collection.css') }}">

    {{-- CSS tổng của theme --}}
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/ella/assets/rtl.css') }}">

    {{-- Style custom riêng cho LINXEN nếu cần --}}
    @stack('styles')
</head>
<body class="template-index lx-mobile-first">

    {{-- HEADER --}}
    @include('storefront.ella.components.header')

    {{-- CONTENT --}}
    <main id="MainContent" class="content-for-layout">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    @include('storefront.ella.components.footer')

    {{-- JS CORE --}}
    <script src="{{ asset('themes/ella/assets/vendor.js') }}"></script>
    <script src="{{ asset('themes/ella/assets/global.js') }}"></script>
    <script src="{{ asset('themes/ella/assets/theme-editor.js') }}"></script>

    {{-- JS cho slideshow & video background --}}
    <script src="{{ asset('themes/ella/assets/component-slideshow.js') }}"></script>
    <script src="{{ asset('themes/ella/assets/video-background-component.js') }}"></script>
    <script src="{{ asset('themes/ella/assets/details-disclosure.js') }}"></script>
    <script src="{{ asset('themes/ella/assets/search-form.js') }}"></script>

    @stack('scripts')
</body>
</html>
