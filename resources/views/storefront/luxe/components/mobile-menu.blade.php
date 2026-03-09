{{-- OVERLAY --}}
<div class="lx-menu-overlay" id="lxMenuOverlay"></div>

{{-- MOBILE MENU --}}
<div class="lx-mobile-menu" id="lxMobileMenu">

    {{-- BRAND --}}
    <div class="lx-menu-brand-zone">

        <div class="lx-brand-logo">LIN XÉN</div>
        <div class="lx-brand-tagline">Thời trang cao cấp</div>

        <button class="close-btn" id="lxMenuClose">✕</button>

    </div>


    <ul class="lx-menu-list">


        {{-- SALE --}}
        <li class="lx-menu-item highlight-sale">
            <a href="/collections/sale" class="lx-menu-link">

                {{-- fire icon --}}
                <svg class="menu-icon" viewBox="0 0 24 24">
                    <path d="M12 2s3 4 3 7c0 2-1 3-1 3s4-1 4-6c3 3 4 6 4 9a8 8 0 1 1-16 0c0-4 3-7 6-13z"/>
                </svg>

                <span>SALE 30-50%</span>
            </a>
        </li>


        {{-- BEST SELLER --}}
        <li class="lx-menu-item">
            <a href="/collections/best-seller" class="lx-menu-link">

                {{-- star icon --}}
                <svg class="menu-icon" viewBox="0 0 24 24">
                    <path d="M12 2l3 7h7l-5.5 4.2L18 21l-6-4-6 4 1.5-7.8L2 9h7z"/>
                </svg>

                <span>Bán chạy</span>
            </a>
        </li>


        {{-- NEW --}}
<li class="lx-menu-item">
    <a href="/collections/new" class="lx-menu-link">

        {{-- sparkle --}}
        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 3l1.8 4.8L18.6 9l-4.8 1.8L12 15.6l-1.8-4.8L5.4 9l4.8-1.2L12 3z"/>
            <path d="M19 3v4"/>
            <path d="M21 5h-4"/>
        </svg>

        <span>Hàng mới</span>
    </a>
</li>


        {{-- CATEGORY --}}
        <li class="lx-menu-item has-sub">

            <button type="button" class="lx-menu-link lx-menu-toggle">

                {{-- grid icon --}}
                <svg class="menu-icon" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                </svg>

                <span>Danh mục</span>

            </button>

            <ul class="lx-submenu">

                <li><a href="/collections/vay">Váy</a></li>
                <li><a href="/collections/ao">Áo</a></li>
                <li><a href="/collections/quan">Quần</a></li>
                <li><a href="/collections/set">Set</a></li>

            </ul>

        </li>


        {{-- COLLECTION --}}
        <li class="lx-menu-item">
            <a href="/collections/high-fashion" class="lx-menu-link">

                {{-- diamond --}}
                <svg class="menu-icon" viewBox="0 0 24 24">
                    <path d="M12 2l8 6-8 14L4 8z"/>
                </svg>

                <span>Bộ sưu tập cao cấp</span>
            </a>
        </li>


        {{-- WISHLIST --}}
        <li class="lx-menu-item">
            <a href="/wishlist" class="lx-menu-link">

                {{-- heart --}}
                <svg class="menu-icon" viewBox="0 0 24 24">
                    <path d="M12 21s-7-4.5-9-8a5 5 0 0 1 9-3 5 5 0 0 1 9 3c-2 3.5-9 8-9 8z"/>
                </svg>

                <span>Yêu thích</span>
            </a>
        </li>


        {{-- ACCOUNT --}}
        <li class="lx-menu-item">
            <a href="{{ route('linxen.account.index') }}" class="lx-menu-link">

                {{-- user --}}
                <svg class="menu-icon" viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 22c2-4 14-4 16 0"/>
                </svg>

                <span>Tài khoản</span>
            </a>
        </li>


        {{-- ORDERS --}}
        <li class="lx-menu-item">
            <a href="{{ route('linxen.account.orders') }}" class="lx-menu-link">

                {{-- box --}}
                <svg class="menu-icon" viewBox="0 0 24 24">
                    <rect x="3" y="7" width="18" height="14"/>
                    <path d="M3 7l9-5 9 5"/>
                </svg>

                <span>Đơn hàng</span>
            </a>
        </li>


        {{-- SUPPORT --}}
        <li class="lx-menu-item">
            <a href="/support" class="lx-menu-link">

                {{-- info --}}
                <svg class="menu-icon" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <circle cx="12" cy="8" r="1"/>
                </svg>

                <span>Trợ giúp</span>
            </a>
        </li>


    </ul>

</div>