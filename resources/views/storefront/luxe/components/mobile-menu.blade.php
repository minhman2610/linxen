{{-- OVERLAY --}}
<div class="lx-menu-overlay" id="lxMenuOverlay" aria-hidden="true"></div>

{{-- MOBILE MENU --}}
<div class="lx-mobile-menu" id="lxMobileMenu" aria-hidden="true">

    <!-- Brand Highlight Zone -->
    <div class="lx-menu-brand-zone">
        <div class="lx-brand-logo">LIN XÉN</div>
        <div class="lx-brand-tagline">Thời trang cao cấp</div>

        <button class="close-btn" id="lxMenuClose" type="button">✕</button>
    </div>

    <!-- Menu list -->
    <ul class="lx-menu-list">

        {{-- ✅ HOME --}}
        <li class="lx-menu-item">
            <a href="{{ route('linxen.home') }}" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-home.svg" class="menu-icon" alt="">
                <span>Trang chủ</span>
            </a>
        </li>

        {{-- 🟡 CATEGORIES (DEMO) --}}
        <li class="lx-menu-item has-sub">
            <button type="button" class="lx-menu-link lx-menu-toggle">
                <img src="/themes/luxe/assets/icons/icon-categories.svg" class="menu-icon" alt="">
                <span>Danh mục</span>
            </button>
            <ul class="lx-submenu">
                {{-- DEMO – cần map slug thật --}}
                <li><a href="#">Váy</a></li>
                <li><a href="#">Áo</a></li>
                <li><a href="#">Quần</a></li>
                <li><a href="#">Hàng mới</a></li>
                <li><a href="#">Khuyến mãi</a></li>
            </ul>
        </li>

        {{-- 🟡 NEW ARRIVALS (DEMO – cần collection slug thật) --}}
        <li class="lx-menu-item">
            <a href="#" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-star.svg" class="menu-icon" alt="">
                <span>Hàng mới về</span>
            </a>
        </li>

        {{-- 🟡 HIGH FASHION COLLECTION (DEMO) --}}
        <li class="lx-menu-item">
            <a href="#" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-fire.svg" class="menu-icon" alt="">
                <span>Bộ sưu tập cao cấp</span>
            </a>
        </li>

        {{-- 🟡 WISHLIST (CHƯA CÓ) --}}
        <li class="lx-menu-item">
            <a href="#" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-heart.svg" class="menu-icon" alt="">
                <span>Yêu thích</span>
            </a>
        </li>

        {{-- 🟡 SUPPORT (CHƯA CÓ) --}}
        <li class="lx-menu-item">
            <a href="#" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-info.svg" class="menu-icon" alt="">
                <span>Trợ giúp</span>
            </a>
        </li>

        {{-- ✅ ACCOUNT --}}
        <li class="lx-menu-item">
            <a href="{{ route('linxen.account.index') }}" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-account.svg" class="menu-icon" alt="">
                <span>Tài khoản</span>
            </a>
        </li>

        {{-- ✅ ORDERS --}}
        <li class="lx-menu-item">
            <a href="{{ route('linxen.account.orders') }}" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-order.svg" class="menu-icon" alt="">
                <span>Đơn hàng</span>
            </a>
        </li>

    </ul>

</div>
