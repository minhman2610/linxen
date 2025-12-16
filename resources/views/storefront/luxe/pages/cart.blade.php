@extends('storefront.luxe.layouts.master')

@section('content')

<section class="lx-cart-page">

    {{-- =========================
        CART HEADER
    ========================= --}}
    <div class="lx-cart-header">
        <h1>Giỏ hàng</h1>
        <a href="{{ route('linxen.home') }}" class="lx-cart-back">
            ← Tiếp tục mua sắm
        </a>
    </div>

    {{-- =========================
        CART CONTENT
    ========================= --}}
    <div class="lx-cart-content">

        {{-- LEFT: CART ITEMS --}}
        <div class="lx-cart-items">

            {{-- EMPTY STATE --}}
            @if(empty($cartItems))
                <div class="lx-cart-empty">
                    <p>Giỏ hàng của bạn đang trống.</p>
                    <a href="{{ route('linxen.home') }}" class="lx-btn-primary">
                        Khám phá sản phẩm
                    </a>
                </div>
            @else

                @foreach($cartItems as $item)
                    <div class="lx-cart-item">

                        {{-- IMAGE --}}
                        <div class="lx-cart-item-image">
                            <img src="{{ $item['image'] ?? asset('images/no-image.png') }}"
                                 alt="{{ $item['name'] }}">
                        </div>

                        {{-- INFO --}}
                        <div class="lx-cart-item-info">
                            <div class="lx-cart-item-title">
                                {{ $item['name'] }}
                            </div>

                            @if(!empty($item['attrs']))
                                <div class="lx-cart-item-variant">
                                    @foreach($item['attrs'] as $k => $v)
                                        {{ $k }}: {{ $v }}@if(!$loop->last) · @endif
                                    @endforeach
                                </div>
                            @endif

                            <div class="lx-cart-item-price">
                                {{ number_format($item['price']) }}₫
                            </div>
                        </div>

                        {{-- QTY --}}
                        <div class="lx-cart-item-qty">
                            <button type="button">−</button>
                            <input type="number"
                                   value="{{ $item['qty'] }}"
                                   min="1">
                            <button type="button">+</button>
                        </div>

                        {{-- TOTAL --}}
                        <div class="lx-cart-item-total">
                            {{ number_format($item['price'] * $item['qty']) }}₫
                        </div>

                        {{-- REMOVE --}}
                        <button class="lx-cart-item-remove" title="Xóa">
                            ✕
                        </button>

                    </div>
                @endforeach

            @endif
        </div>

        {{-- RIGHT: SUMMARY --}}
        <div class="lx-cart-summary">

            <h3>Đơn hàng</h3>

            <div class="lx-cart-summary-row">
                <span>Tạm tính</span>
                <span>{{ number_format($subtotal) }}₫</span>
            </div>

            <div class="lx-cart-summary-row">
                <span>Phí vận chuyển</span>
                <span>
                    {{ $shippingFee > 0 ? number_format($shippingFee).'₫' : 'Miễn phí' }}
                </span>
            </div>

            <div class="lx-cart-summary-total">
                <span>Tổng cộng</span>
                <span>{{ number_format($total) }}₫</span>
            </div>

            <button class="lx-btn-primary lx-btn-full">
                TIẾN HÀNH THANH TOÁN
            </button>

            <div class="lx-cart-note">
                ✔ Miễn phí đổi trả trong 7 ngày<br>
                ✔ Giao hàng toàn quốc
            </div>

        </div>

    </div>

    {{-- =========================
        SUGGESTED PRODUCTS
    ========================= --}}
    @if(!empty($suggestedProducts) && count($suggestedProducts))
        <div class="lx-cart-suggested">

            <h2>Có thể bạn sẽ thích</h2>

            <div class="lx-suggested-grid">
                @foreach($suggestedProducts as $p)
                    @php
                        $media = $p->getMedia();
                        $thumb = $media['thumb'] ?? asset('images/no-image.png');
                    @endphp

                    <a href="{{ route('linxen.product', $p->code) }}"
                       class="lx-suggested-item">

                        <img src="{{ $thumb }}" alt="{{ $p->name }}">

                        <div class="lx-suggested-name">
                            {{ $p->name }}
                        </div>

                        <div class="lx-suggested-price">
                            {{ number_format($p->retail_price ?? $p->base_price ?? 0) }}₫
                        </div>

                    </a>
                @endforeach
            </div>

        </div>
    @endif

</section>

@endsection

{{-- =========================
    STYLE
========================= --}}
<style>
.lx-cart-page{
    max-width:1200px;
    margin:0 auto;
    padding:40px 16px 80px;
}
.lx-cart-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:32px;
}
.lx-cart-header h1{
    font-size:28px;
    font-weight:700;
}
.lx-cart-back{
    font-size:14px;
    color:#555;
    text-decoration:none;
}

/* CONTENT */
.lx-cart-content{
    display:grid;
    grid-template-columns:1fr;
    gap:32px;
}
@media(min-width:992px){
    .lx-cart-content{
        grid-template-columns:2fr 1fr;
        gap:48px;
    }
}

/* CART ITEM */
.lx-cart-item{
    display:grid;
    grid-template-columns:120px 1fr auto auto 24px;
    gap:16px;
    align-items:center;
    padding:20px 0;
    border-bottom:1px solid #eee;
}
.lx-cart-item-image img{
    width:120px;
    height:160px;
    object-fit:cover;
    background:#f5f5f5;
}
.lx-cart-item-title{
    font-size:16px;
    font-weight:600;
}
.lx-cart-item-variant{
    font-size:13px;
    color:#777;
    margin:6px 0;
}
.lx-cart-item-price{
    font-size:14px;
    color:#444;
}

/* QTY */
.lx-cart-item-qty{
    display:flex;
    border:1px solid #ddd;
}
.lx-cart-item-qty button{
    width:32px;
    border:none;
    background:#fff;
}
.lx-cart-item-qty input{
    width:48px;
    border:none;
    text-align:center;
}

/* TOTAL */
.lx-cart-item-total{
    font-weight:600;
}

/* REMOVE */
.lx-cart-item-remove{
    border:none;
    background:none;
    font-size:16px;
    cursor:pointer;
    color:#999;
}

/* SUMMARY */
.lx-cart-summary{
    border:1px solid #eee;
    padding:24px;
}
.lx-cart-summary h3{
    font-size:18px;
    font-weight:700;
    margin-bottom:20px;
}
.lx-cart-summary-row,
.lx-cart-summary-total{
    display:flex;
    justify-content:space-between;
    margin-bottom:12px;
}
.lx-cart-summary-total{
    font-weight:700;
    font-size:16px;
    border-top:1px solid #eee;
    padding-top:16px;
}
.lx-cart-note{
    font-size:13px;
    color:#666;
    margin-top:16px;
}

/* EMPTY */
.lx-cart-empty{
    text-align:center;
    padding:60px 0;
}
.lx-cart-empty p{
    font-size:16px;
    margin-bottom:20px;
}

/* SUGGESTED */
.lx-cart-suggested{
    margin-top:80px;
}
.lx-cart-suggested h2{
    font-size:22px;
    font-weight:700;
    margin-bottom:24px;
}
.lx-suggested-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:16px;
}
@media(min-width:768px){
    .lx-suggested-grid{
        grid-template-columns:repeat(4,1fr);
    }
}
.lx-suggested-item{
    text-decoration:none;
    color:#111;
}
.lx-suggested-item img{
    width:100%;
    aspect-ratio:3/4;
    object-fit:cover;
    background:#f5f5f5;
}
.lx-suggested-name{
    font-size:14px;
    margin-top:8px;
}
.lx-suggested-price{
    font-size:14px;
    font-weight:600;
    margin-top:4px;
}
</style>
