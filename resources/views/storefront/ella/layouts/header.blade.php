<header id="lx-header" class="header header--minimal-highfashion">
    <div class="lx-header-inner">

        {{-- HÀNG TRÊN: MOBILE BAR --}}
        <div class="lx-header-bar lx-header-bar--mobile">
            {{-- Nút menu trái --}}
            <button class="lx-icon-button lx-menu-toggle" aria-label="Mở menu">
                <img src="{{ asset('themes/ella/assets/icon-hamburger.svg') }}" alt="Menu">
            </button>

            {{-- Logo giữa --}}
            <a href="{{ url('/') }}" class="lx-header-logo">
                {{-- Anh thay logo thật của LINXEN vào đây --}}
                <img src="{{ asset('themes/ella/assets/logo-highfashion.png') }}"
                     alt="LINXEN"
                     class="lx-header-logo-img">
            </a>

            {{-- Icon giỏ hàng phải --}}
            <a href="{{ url('/cart') }}" class="lx-icon-button lx-cart-button" aria-label="Giỏ hàng">
                <img src="{{ asset('themes/ella/assets/icon-cart.svg') }}" alt="Cart">
                <span class="lx-cart-count">0</span>
            </a>
        </div>

        {{-- HÀNG DƯỚI: NAV (ẩn trên mobile, hiện trên tablet/desktop) --}}
        <nav class="lx-header-nav">
            <ul class="lx-nav-list">
                <li class="lx-nav-item"><a href="{{ url('/c/dress') }}">ĐẦM</a></li>
                <li class="lx-nav-item"><a href="{{ url('/c/top') }}">ÁO</a></li>
                <li class="lx-nav-item"><a href="{{ url('/c/bottom') }}">QUẦN / CHÂN VÁY</a></li>
                <li class="lx-nav-item"><a href="{{ url('/c/new') }}">NEW IN</a></li>
                <li class="lx-nav-item"><a href="{{ url('/c/sale') }}">SALE</a></li>
            </ul>
        </nav>

        {{-- MENU MOBILE DRAWER (sẽ toggle bằng JS sau) --}}
        <div class="lx-mobile-menu" hidden>
            <nav class="lx-mobile-menu-inner">
                <a href="{{ url('/c/dress') }}">ĐẦM</a>
                <a href="{{ url('/c/top') }}">ÁO</a>
                <a href="{{ url('/c/bottom') }}">QUẦN / CHÂN VÁY</a>
                <a href="{{ url('/c/new') }}">HÀNG MỚI</a>
                <a href="{{ url('/c/sale') }}">SALE</a>
                <a href="{{ url('/lookbook') }}">LOOKBOOK</a>
            </nav>
        </div>
    </div>
</header>

@push('styles')
<style>
    /* MOBILE FIRST */
    .lx-header-inner {
        width: 100%;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        background-color: #fff;
    }

    .lx-header-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
    }

    .lx-icon-button {
        background: none;
        border: none;
        padding: 0;
        display: flex;
        align-items: center;
        position: relative;
    }

    .lx-icon-button img {
        width: 22px;
        height: 22px;
        display: block;
    }

    .lx-header-logo-img {
        height: 26px;
        width: auto;
        display: block;
    }

    .lx-cart-count {
        position: absolute;
        top: -4px;
        right: -8px;
        min-width: 16px;
        height: 16px;
        border-radius: 999px;
        background: #000;
        color: #fff;
        font-size: 10px;
        line-height: 16px;
        text-align: center;
    }

    /* NAV dưới: ẩn trên mobile, hiện từ >=768px (anh chỉnh breakpoint theo CSS của Ella nếu muốn) */
    .lx-header-nav {
        display: none;
        padding: 6px 18px 10px;
    }

    .lx-nav-list {
        display: flex;
        justify-content: center;
        gap: 24px;
        list-style: none;
        margin: 0;
        padding: 0;
        font-size: 13px;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .lx-nav-item a {
        text-decoration: none;
        color: #111;
    }

    .lx-nav-item a:hover {
        opacity: .7;
    }

    /* MOBILE MENU DRAWER basic (chưa có JS toggle) */
    .lx-mobile-menu {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.7);
        z-index: 1000;
    }

    .lx-mobile-menu-inner {
        background: #fff;
        width: 75%;
        max-width: 320px;
        height: 100%;
        padding: 24px 18px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .lx-mobile-menu-inner a {
        text-decoration: none;
        color: #111;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    /* DESKTOP: hiện nav, chỉnh lại padding header-bar */
    @media (min-width: 768px) {
        .lx-header-bar {
            padding: 16px 32px;
        }

        .lx-header-nav {
            display: block;
        }

        .lx-header-logo-img {
            height: 32px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // JS cực đơn giản để toggle menu mobile
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.querySelector('.lx-menu-toggle');
        const drawer = document.querySelector('.lx-mobile-menu');

        if (!toggle || !drawer) return;

        toggle.addEventListener('click', () => {
            const isHidden = drawer.hasAttribute('hidden');
            if (isHidden) {
                drawer.removeAttribute('hidden');
            } else {
                drawer.setAttribute('hidden', 'hidden');
            }
        });

        drawer.addEventListener('click', (e) => {
            if (e.target === drawer) {
                drawer.setAttribute('hidden', 'hidden');
            }
        });
    });
</script>
@endpush
