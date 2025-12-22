<header class="lx-header {{ request()->routeIs('linxen.home') ? 'lx-header--transparent' : '' }}">

    {{-- MENU (MOBILE) --}}
    <button class="lx-header-btn lx-header-btn-menu" data-menu-open type="button">
        <img src="{{ asset('themes/luxe/assets/icons/icon-hamburger.svg') }}" alt="Menu">
    </button>

    {{-- SEARCH --}}
    <a href="{{ route('linxen.search') }}" class="lx-header-btn">
        <img src="{{ asset('themes/luxe/assets/icons/icon-search.svg') }}" class="lx-icon" alt="Search">
    </a>

    {{-- LOGO --}}
    <div class="lx-header-logo">
        <a href="{{ route('linxen.home') }}" class="lx-header-logo-link">
            LIN XÉN
        </a>
    </div>

    {{-- ACCOUNT --}}
    <a href="{{ route('linxen.account') }}" class="lx-header-btn">
        <img src="{{ asset('themes/luxe/assets/icons/icon-account.svg') }}" alt="Account">
    </a>

    {{-- CART --}}
    <a href="{{ route('linxen.cart') }}" class="lx-header-btn cart-btn">
        <img src="{{ asset('themes/luxe/assets/icons/icon-cart.svg') }}" alt="Cart">

        <span class="cart-count" id="lxHeaderCartCount">
            {{ array_sum(array_column(session('cart', []), 'qty')) }}
        </span>
    </a>

</header>

<style>
/* -----------------------------------------------
   LUXE HEADER — Mobile First (FINAL)
   👉 Auto offset by announcement height
-----------------------------------------------*/
.lx-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 10px 12px;
    height: 56px;

    background: #fff;
    border-bottom: 1px solid #f0f0f0;

    position: sticky;
    top: var(--announcement-height, 0);

    z-index: 40; /* cao hơn content, thấp hơn overlay/menu */
}

.lx-header-btn {
    background: none;
    border: none;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.lx-header-btn img {
    width: 22px;
    height: 22px;
}

.lx-header-logo {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 2px;
}

.lx-header-logo-link {
    text-decoration: none;
    color: inherit;
}

.lx-header-logo-link:hover {
    opacity: .85;
}

/* CART BADGE */
.cart-btn {
    position: relative;
}

.cart-count {
    position: absolute;
    top: -4px;
    right: -4px;

    background: #000;
    color: #fff;
    width: 16px;
    height: 16px;

    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;
}

/* HOME — TRANSPARENT HEADER */
.lx-header--transparent {
    background: transparent !important;
    border-bottom: none !important;

    position: absolute;
    top: calc(var(--announcement-height, 0) + 30px);
    left: 0;
    width: 100%;

    z-index: 50;
}


.lx-header--transparent .lx-header-logo {
    color: #111;
}

.lx-header--transparent .cart-count {
    background: #fff;
    color: #000;
}

@media (min-width: 768px) {
    .lx-header {
        padding: 12px 20px;
        height: 66px;
    }

    .lx-header-logo {
        font-size: 26px;
    }
}
</style>
