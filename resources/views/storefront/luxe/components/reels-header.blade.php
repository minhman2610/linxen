<header class="lx-reels-header">

    {{-- LEFT: MENU --}}
    <button class="lx-reels-icon" onclick="history.back()">
        ☰
    </button>

    {{-- CENTER: MODE --}}
    <div class="lx-reels-mode">
        <span class="active">Ảnh</span>
        <span>Video</span>
    </div>

    {{-- RIGHT: CART --}}
    <a href="{{ route('linxen.cart') }}" class="lx-reels-cart">
        🛒
        <em id="lxCartCount">
            {{ array_sum(array_column(session('cart', []), 'qty')) }}
        </em>
    </a>

</header>
