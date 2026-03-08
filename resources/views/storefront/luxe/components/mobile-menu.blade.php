{{-- OVERLAY --}}
<div class="lx-menu-overlay" id="lxMenuOverlay" aria-hidden="true"></div>

{{-- MOBILE MENU --}}
<div class="lx-mobile-menu" id="lxMobileMenu" aria-hidden="true">

    {{-- BRAND ZONE --}}
    <div class="lx-menu-brand-zone">

        <div class="lx-brand-logo">LIN XÉN</div>
        <div class="lx-brand-tagline">Thời trang cao cấp</div>

        <button class="close-btn" id="lxMenuClose" type="button">✕</button>

    </div>


    <ul class="lx-menu-list">

        {{-- 🔥 FLASH SALE --}}
        <li class="lx-menu-item highlight-sale">
            <a href="/collections/sale" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-fire.svg" class="menu-icon" alt="">
                <span>SALE 30-50%</span>
            </a>
        </li>


        {{-- ⚡ BEST SELLER --}}
        <li class="lx-menu-item">
            <a href="/collections/best-seller" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-star.svg" class="menu-icon" alt="">
                <span>Bán chạy</span>
            </a>
        </li>


        {{-- 🆕 NEW ARRIVAL --}}
        <li class="lx-menu-item">
            <a href="/collections/new" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-new.svg" class="menu-icon" alt="">
                <span>Hàng mới</span>
            </a>
        </li>


        {{-- CATEGORIES --}}
        <li class="lx-menu-item has-sub">

            <button type="button" class="lx-menu-link lx-menu-toggle">

                <img src="/themes/luxe/assets/icons/icon-categories.svg" class="menu-icon" alt="">
                <span>Danh mục</span>

            </button>

            <ul class="lx-submenu">

                <li>
                    <a href="/collections/vay">Váy</a>
                </li>

                <li>
                    <a href="/collections/ao">Áo</a>
                </li>

                <li>
                    <a href="/collections/quan">Quần</a>
                </li>

                <li>
                    <a href="/collections/set">Set</a>
                </li>

            </ul>

        </li>


        {{-- COLLECTION --}}
        <li class="lx-menu-item">
            <a href="/collections/high-fashion" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-diamond.svg" class="menu-icon" alt="">
                <span>Bộ sưu tập cao cấp</span>
            </a>
        </li>


        {{-- WISHLIST --}}
        <li class="lx-menu-item">
            <a href="/wishlist" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-heart.svg" class="menu-icon" alt="">
                <span>Yêu thích</span>
            </a>
        </li>


        {{-- ACCOUNT --}}
        <li class="lx-menu-item">
            <a href="{{ route('linxen.account.index') }}" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-account.svg" class="menu-icon" alt="">
                <span>Tài khoản</span>
            </a>
        </li>


        {{-- ORDERS --}}
        <li class="lx-menu-item">
            <a href="{{ route('linxen.account.orders') }}" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-order.svg" class="menu-icon" alt="">
                <span>Đơn hàng</span>
            </a>
        </li>


        {{-- SUPPORT --}}
        <li class="lx-menu-item">
            <a href="/support" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-info.svg" class="menu-icon" alt="">
                <span>Trợ giúp</span>
            </a>
        </li>

    </ul>

</div>