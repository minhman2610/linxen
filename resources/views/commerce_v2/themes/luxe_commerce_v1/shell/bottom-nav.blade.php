@php
    $lxcv1CartQuantity = collect((array) session('commerce_v2.cart.items', []))
        ->sum(fn ($line) => max(0, (int) data_get($line, 'quantity', 0)));
@endphp

<nav class="lxcv1-bottom-nav" aria-label="Điều hướng di động">
    <a href="{{ route('commerce.v2.home') }}" @class(['is-active' => request()->routeIs('commerce.v2.home')])>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-7 9 7v9h-6v-6H9v6H3z"></path></svg>
        <span>Trang chủ</span>
    </a>
    <a href="{{ route('commerce.v2.shop') }}" @class(['is-active' => request()->routeIs('commerce.v2.shop') || request()->routeIs('commerce.v2.collection') || request()->routeIs('commerce.v2.discover')])>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4z"></path><path d="M4 10h16M10 4v16"></path></svg>
        <span>Sản phẩm</span>
    </a>
    <a href="{{ route('commerce.v2.video') }}" @class(['lxcv1-bottom-nav__video', 'is-active' => request()->routeIs('commerce.v2.video')])>
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="5"></rect><path d="m10 8 6 4-6 4Z"></path></svg>
        <span>Video</span>
    </a>
    <a
        href="{{ route('commerce.v2.cart.index') }}"
        data-lxcv1-cart-link
        @class(['is-active' => request()->routeIs('commerce.v2.cart.*') || request()->routeIs('commerce.v2.checkout.*')])
    >
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14l-1 13H6L5 7Z"></path><path d="M9 7a3 3 0 0 1 6 0"></path></svg>
        <span>Giỏ hàng</span>
        <b
            class="lxcv1-cart-badge lxcv1-cart-badge--bottom"
            data-lxcv1-cart-count
            @if($lxcv1CartQuantity < 1) hidden @endif
        >{{ $lxcv1CartQuantity }}</b>
    </a>
    <a href="{{ route('commerce.v2.account.index') }}" @class(['is-active' => request()->routeIs('commerce.v2.account.*') || request()->routeIs('commerce.v2.orders.*')])>
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
        <span>Tài khoản</span>
    </a>
</nav>
