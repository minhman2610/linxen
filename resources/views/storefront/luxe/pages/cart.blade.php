@extends('storefront.luxe.layouts.app')

@section('content')

@php
    $cartItems = $cart ?? [];
    $subtotal = 0;

    foreach ($cartItems as $item) {
        $subtotal += ($item['price'] ?? 0) * ($item['qty'] ?? 0);
    }

    $shippingFee = $subtotal >= 500000 ? 0 : 30000;
    $total = $subtotal + $shippingFee;
@endphp

<section class="lx-cart-page">

    {{-- HEADER --}}
    <div class="lx-cart-header">
        <h1>Giỏ hàng</h1>
        <a href="{{ route('linxen.home') }}" class="lx-cart-back">
            ← Tiếp tục mua sắm
        </a>
    </div>

    {{-- CONTENT --}}
    <div class="lx-cart-content">

        {{-- LEFT: ITEMS --}}
        <div class="lx-cart-items">

            @if(empty($cartItems))
                <div class="lx-cart-empty">
                    <p>Giỏ hàng của bạn đang trống.</p>
                    <a href="{{ route('linxen.home') }}" class="lx-btn-primary">
                        Khám phá sản phẩm
                    </a>
                </div>
            @else

                @foreach($cartItems as $sku => $item)
                    <div class="lx-cart-item" data-sku="{{ $sku }}">

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
                            <button type="button" onclick="updateQty('{{ $sku }}', -1)">−</button>
                            <input type="number" min="1" value="{{ $item['qty'] }}" readonly>
                            <button type="button" onclick="updateQty('{{ $sku }}', 1)">+</button>
                        </div>

                        {{-- TOTAL --}}
                        <div class="lx-cart-item-total">
                            {{ number_format($item['price'] * $item['qty']) }}₫
                        </div>

                        {{-- REMOVE --}}
                        <button class="lx-cart-item-remove"
                                onclick="removeItem('{{ $sku }}')"
                                title="Xóa">
                            ✕
                        </button>

                    </div>
                @endforeach

            @endif
        </div>

        {{-- RIGHT: SUMMARY --}}
        @if(!empty($cartItems))
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

            <a href="{{ route('linxen.checkout') }}"
               class="lx-btn-primary lx-btn-full">
                TIẾN HÀNH THANH TOÁN
            </a>

            <div class="lx-cart-note">
                ✔ Miễn phí đổi trả trong 7 ngày<br>
                ✔ Giao hàng toàn quốc
            </div>

        </div>
        @endif

    </div>

</section>
@endsection

{{-- =========================
    STYLE
========================= --}}
<style>
/* =====================================================
   CART PAGE – LIN XÉN
===================================================== */

.lx-cart-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 16px;
}

.lx-cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.lx-cart-header h1 {
    font-size: 22px;
    font-weight: 600;
}

.lx-cart-back {
    font-size: 14px;
    text-decoration: none;
    color: #555;
}

/* =====================================================
   CONTENT LAYOUT
===================================================== */

.lx-cart-content {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 24px;
}

/* =====================================================
   CART ITEMS
===================================================== */

.lx-cart-items {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.lx-cart-item {
    display: grid;
    grid-template-columns: 90px 1fr 120px 120px 40px;
    gap: 12px;
    align-items: center;

    padding: 12px;
    border: 1px solid #eee;
    border-radius: 8px;
    background: #fff;
}

.lx-cart-item-image img {
    width: 100%;
    border-radius: 6px;
}

.lx-cart-item-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.lx-cart-item-title {
    font-weight: 600;
    font-size: 14px;
}

.lx-cart-item-variant {
    font-size: 12px;
    color: #777;
}

.lx-cart-item-price {
    font-size: 13px;
    color: #333;
}

/* =====================================================
   QTY CONTROL
===================================================== */

.lx-cart-item-qty {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 6px;
    overflow: hidden;
    width: 100px;
}

.lx-cart-item-qty button {
    width: 32px;
    height: 32px;
    border: none;
    background: #f5f5f5;
    cursor: pointer;
}

.lx-cart-item-qty input {
    width: 36px;
    border: none;
    text-align: center;
    font-size: 14px;
}

/* =====================================================
   ITEM TOTAL & REMOVE
===================================================== */

.lx-cart-item-total {
    font-weight: 600;
    font-size: 14px;
    text-align: right;
}

.lx-cart-item-remove {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #999;
}

.lx-cart-item-remove:hover {
    color: #000;
}

/* =====================================================
   SUMMARY
===================================================== */

.lx-cart-summary {
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 16px;
    background: #fafafa;
    height: fit-content;
}

.lx-cart-summary h3 {
    font-size: 18px;
    margin-bottom: 16px;
}

.lx-cart-summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
}

.lx-cart-summary-total {
    display: flex;
    justify-content: space-between;
    font-weight: 600;
    font-size: 16px;
    margin: 16px 0;
}

.lx-cart-note {
    font-size: 12px;
    color: #666;
    margin-top: 12px;
}

/* =====================================================
   EMPTY CART
===================================================== */

.lx-cart-empty {
    padding: 48px 24px;
    text-align: center;
    border: 1px dashed #ddd;
    border-radius: 8px;
}

.lx-cart-empty p {
    margin-bottom: 16px;
    font-size: 15px;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 768px) {

    .lx-cart-content {
        grid-template-columns: 1fr;
    }

    .lx-cart-item {
        grid-template-columns: 70px 1fr;
        grid-template-rows: auto auto auto;
        gap: 10px;
    }

    .lx-cart-item-qty,
    .lx-cart-item-total,
    .lx-cart-item-remove {
        grid-column: 2;
    }

    .lx-cart-item-total {
        text-align: left;
    }

    .lx-cart-summary {
        margin-top: 16px;
    }
}

</style>
