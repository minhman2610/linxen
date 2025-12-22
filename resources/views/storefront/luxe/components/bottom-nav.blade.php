<div class="lx-bottom-nav">

    {{-- HOME --}}
    <a href="{{ route('linxen.home') }}"
       class="lx-nav-item {{ request()->routeIs('linxen.home') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-home.svg" class="icon">
        <span>Trang chủ</span>
    </a>

    {{-- SEARCH --}}
    <a href="/search"
       class="lx-nav-item {{ request()->is('search') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-search.svg" class="icon">
        <span>Tìm kiếm</span>
    </a>

    {{-- WISHLIST --}}
    <a href="/wishlist"
       class="lx-nav-item {{ request()->is('wishlist') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-heart.svg" class="icon">
        <span>Yêu thích</span>
    </a>

    {{-- ACCOUNT --}}
    <a href="/account"
       class="lx-nav-item {{ request()->is('account') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-account.svg" class="icon">
        <span>Tài khoản</span>
    </a>

    {{-- CART --}}
    <a href="{{ route('linxen.cart') }}"
       class="lx-nav-item cart-btn {{ request()->routeIs('linxen.cart') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-cart.svg" class="icon">
        <span>Giỏ hàng</span>

        <span class="cart-count" id="lxCartCount">
            {{ array_sum(array_column(session('cart', []), 'qty')) }}
        </span>
    </a>

</div>

<style>
/* -----------------------------------------------------
   LUXE — Mobile Bottom Navigation (Refined)
------------------------------------------------------ */

.lx-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;

    height: 62px;
    background: #f9f6f3; /* ivory nhẹ */
    border-top: 1px solid rgba(0,0,0,.08);

    display: flex;
    justify-content: space-around;
    align-items: center;

    z-index: 999;

    pointer-events: none;
}

/* Chỉ mobile mới cho click */
@media (max-width: 767px) {
    .lx-bottom-nav {
        pointer-events: auto;
    }
}

.lx-nav-item {
    flex: 1;
    text-align: center;
    text-decoration: none;

    font-size: 11px;
    color: #6f665f; /* xám nâu trung tính */

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    padding-top: 6px;
    gap: 2px;
}

.lx-nav-item .icon {
    width: 20px;
    height: 20px;
    opacity: .7;
    transition: opacity .2s ease;
}

/* ACTIVE STATE */
.lx-nav-item.active {
    color: #3b2a22; /* deep brown LIN XÉN */
}

.lx-nav-item.active .icon {
    opacity: 1;
}

/* CART BADGE */
.cart-btn {
    position: relative;
}

.cart-count {
    position: absolute;
    top: 4px;
    right: 22px;

    background: #3b2a22;
    color: #fff;

    font-size: 10px;
    width: 16px;
    height: 16px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;
}

/* Desktop: ẩn */
@media (min-width: 768px) {
    .lx-bottom-nav {
        display: none;
    }
}
</style>
