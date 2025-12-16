<header class="lx-header {{ request()->routeIs('linxen.home') ? 'lx-header--transparent' : '' }}">

    {{-- Nút mở menu mobile --}}
    <button class="lx-header-btn lx-header-btn-menu" data-menu-open>
        <img src="{{ asset('themes/luxe/assets/icons/icon-hamburger.svg') }}" alt="Menu">
    </button>

    {{-- Search --}}
    <button class="lx-header-btn" onclick="window.location='/linxen/search'">
        <img src="{{ asset('themes/luxe/assets/icons/icon-search.svg') }}" class="lx-icon" alt="Search">
    </button>

    {{-- Logo trung tâm --}}
<div class="lx-header-logo">
    <a href="{{ url('/') }}" class="lx-header-logo-link">
        LIN XÉN
    </a>
</div>


    {{-- Tài khoản --}}
    <button class="lx-header-btn" onclick="window.location='/linxen/account'">
        <img src="{{ asset('themes/luxe/assets/icons/icon-account.svg') }}" alt="Account">
    </button>

    {{-- Giỏ hàng --}}
    <button class="lx-header-btn cart-btn" onclick="window.location='/linxen/cart'">
        <img src="{{ asset('themes/luxe/assets/icons/icon-cart.svg') }}" alt="Cart">
        <span class="cart-count">0</span>
    </button>

</header>

<style>
/* -----------------------------------------------
   LUXE HEADER — Mobile First
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
    top: 0;
    z-index: 20; /* thấp hơn hero video overlay nếu cần */
}

.lx-header-btn {
    background: none;
    border: none;
    padding: 0;
}

.lx-header-btn img {
    width: 22px;
    height: 22px;
}

.lx-header-logo {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

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

.lx-header--transparent {
    background: transparent !important;
    border-bottom: none !important;

    position: absolute !important;
    top: 20;
    left: 0;
    width: 100%;
    z-index: 30;

    color: #fff; /* icon + text trắng */
}

.lx-header--transparent .lx-header-btn {
    background: transparent;
    opacity: 1;
    pointer-events: auto;
}

.lx-header--transparent .lx-header-logo {
    color: #111;
}

.lx-header--transparent .cart-count {
    background: #fff;
    color: #000;
}

.lx-header-logo-link{
    text-decoration:none;
    color:inherit;
    font-weight:700;
    letter-spacing:2px;
}
.lx-header-logo-link:hover{
    opacity:.85;
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
