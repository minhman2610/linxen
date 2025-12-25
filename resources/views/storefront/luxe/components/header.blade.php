<header class="lx-header {{ request()->routeIs('linxen.home') ? 'lx-header--transparent' : '' }}">

    {{-- MENU (MOBILE) --}}
    <button
        class="lx-header-btn lx-header-btn-menu"
        data-menu-open
        type="button"
        aria-label="Open menu"
    >
        <img src="{{ asset('themes/luxe/assets/icons/icon-hamburger.svg') }}" alt="Menu">
    </button>

    {{-- SEARCH --}}
    <a href="{{ route('linxen.search') }}" class="lx-header-btn" aria-label="Search">
        <img src="{{ asset('themes/luxe/assets/icons/icon-search.svg') }}" alt="Search">
    </a>

    {{-- LOGO --}}
    <div class="lx-header-logo">
        <a href="{{ route('linxen.home') }}" class="lx-header-logo-link">
            LIN XÉN
        </a>
    </div>

    {{-- ACCOUNT --}}
    <a href="{{ route('linxen.account') }}" class="lx-header-btn" aria-label="Account">
        <img src="{{ asset('themes/luxe/assets/icons/icon-account.svg') }}" alt="Account">
    </a>

    {{-- CART --}}
<a href="{{ route('linxen.cart') }}"
   class="lx-header-btn cart-btn"
   aria-label="Cart">

    <img src="{{ asset('themes/luxe/assets/icons/icon-cart.svg') }}" alt="Cart">
    <span
        class="cart-count"
        id="lxHeaderCartCount">
        {{ array_sum(array_column(session('cart', []), 'qty')) }}
    </span>
</a>

{{-- CART --}}
<a href="{{ route('linxen.cart') }}"
   class="lx-header-btn cart-btn"
   aria-label="Cart">

    <img src="{{ asset('themes/luxe/assets/icons/icon-cart.svg') }}" alt="Cart">
    <span
        class="cart-count"
        id="lxHeaderCartCount">
        {{ array_sum(array_column(session('cart', []), 'qty')) }}
    </span>
</a>

</header>

