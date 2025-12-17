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

        <li class="lx-menu-item">
            <a href="/linxen" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-home.svg" class="menu-icon" alt="">
                <span>Trang chủ</span>
            </a>
        </li>

        <li class="lx-menu-item has-sub">
            <button type="button" class="lx-menu-link lx-menu-toggle">
                <img src="/themes/luxe/assets/icons/icon-categories.svg" class="menu-icon" alt="">
                <span>Danh mục</span>
            </button>
            <ul class="lx-submenu">
                <li><a href="/linxen/c/dresses">Váy</a></li>
                <li><a href="/linxen/c/tops">Áo</a></li>
                <li><a href="/linxen/c/bottoms">Quần</a></li>
                <li><a href="/linxen/c/new">Hàng mới</a></li>
                <li><a href="/linxen/c/sale">Khuyến mãi</a></li>
            </ul>
        </li>

        <li class="lx-menu-item">
            <a href="/linxen/c/new" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-star.svg" class="menu-icon" alt="">
                <span>Hàng mới về</span>
            </a>
        </li>

        <li class="lx-menu-item">
            <a href="/linxen/c/highfashion" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-fire.svg" class="menu-icon" alt="">
                <span>Bộ sưu tập cao cấp</span>
            </a>
        </li>

        <li class="lx-menu-item">
            <a href="/linxen/wishlist" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-heart.svg" class="menu-icon" alt="">
                <span>Yêu thích</span>
            </a>
        </li>

        <li class="lx-menu-item">
            <a href="/linxen/support" class="lx-menu-link">
                <img src="/themes/luxe/assets/icons/icon-info.svg" class="menu-icon" alt="">
                <span>Trợ giúp</span>
            </a>
        </li>

    </ul>

</div>

<style>
/* -----------------------------------------------------
   LUXE — Mobile Menu & Overlay (SAFE CLICK)
------------------------------------------------------ */

/* ❗ OVERLAY
   - Mặc định KHÔNG ăn click
   - Chỉ ăn click khi menu mở
*/
.lx-menu-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.35);
    z-index: 900;

    pointer-events: none;
    opacity: 0;
    transition: opacity .25s ease;
}

.lx-menu-overlay.is-active {
    pointer-events: auto;
    opacity: 1;
}

/* ❗ MOBILE MENU
   - Mặc định KHÔNG ăn click
   - Chỉ ăn click khi mở
*/
.lx-mobile-menu {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 82%;
    max-width: 320px;
    background: #fff;

    z-index: 901;

    transform: translateX(-100%);
    transition: transform .3s ease;

    pointer-events: none;
}

.lx-mobile-menu.is-open {
    transform: translateX(0);
    pointer-events: auto;
}

/* BASIC MENU STYLE (GIỮ NGUYÊN GU) */
.lx-menu-brand-zone {
    padding: 16px;
    border-bottom: 1px solid #eee;
}

.lx-menu-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.lx-menu-item {
    border-bottom: 1px solid #f2f2f2;
}

.lx-menu-link {
    width: 100%;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 10px;

    background: none;
    border: none;
    text-align: left;
    text-decoration: none;
    color: #000;
}

.lx-submenu {
    display: none;
    padding-left: 16px;
}

.lx-menu-item.has-sub.open > .lx-submenu {
    display: block;
}
</style>
