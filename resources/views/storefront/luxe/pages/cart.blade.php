@extends('storefront.luxe.layouts.app')

@section('content')

@php
    $cartItems = $cart ?? [];

    $subtotal = collect($cartItems)->sum(function ($item) {
        return ($item['price'] ?? 0) * ($item['qty'] ?? 0);
    });

    /*
    |--------------------------------------------------------------------------
    | 🚚 PHÍ VẬN CHUYỂN
    | Chưa chọn địa chỉ & phương thức → KHÔNG TÍNH
    |--------------------------------------------------------------------------
    */
    $shippingFee = null;

    // 👉 Tổng hiện tại chỉ là TẠM TÍNH
    $total = $subtotal;
@endphp

<div class="lx-cart">

    {{-- =========================
        CART ITEMS
    ========================== --}}
    <div class="lx-cart-items">

        <h2 class="lx-cart-title">Giỏ hàng</h2>

        @if(empty($cartItems))
            <div class="lx-cart-empty">
                <p>Giỏ hàng của bạn đang trống.</p>
                <a href="{{ route('linxen.home') }}" class="lx-btn-secondary">
                    Tiếp tục mua sắm
                </a>
            </div>
        @else

            @foreach($cartItems as $sku => $item)
                <div class="lx-cart-item">

                    <div class="lx-cart-item-image">
                        @if(!empty($item['image']))
                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}">
                        @endif
                    </div>

                    <div class="lx-cart-item-info">
                        <h3 class="lx-cart-item-name">
                            {{ $item['name'] }}
                        </h3>

                        @if(!empty($item['attrs']))
                            <div class="lx-cart-item-attrs">
                                @foreach($item['attrs'] as $key => $value)
                                    <span>{{ $value }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="lx-cart-item-price">
                            {{ number_format($item['price']) }}₫
                        </div>

                        <div class="lx-cart-item-qty">
                            <button type="button"
                                    onclick="updateCartQty('{{ $sku }}', -1)">−</button>
                            <input type="number"
                                   value="{{ $item['qty'] }}"
                                   readonly>
                            <button type="button"
                                    onclick="updateCartQty('{{ $sku }}', 1)">+</button>
                        </div>

                        <button type="button"
                                class="lx-cart-item-remove"
                                onclick="showConfirmRemove('{{ $sku }}')">
                            ✕
                        </button>
                    </div>

                </div>
            @endforeach

        @endif

    </div>

    {{-- =========================
        CART SUMMARY
    ========================== --}}
    @if(!empty($cartItems))
    <div class="lx-cart-summary">

        <div class="lx-summary-row">
            <span>Tạm tính</span>
            <strong>{{ number_format($subtotal) }}₫</strong>
        </div>

        <div class="lx-summary-row">
            <span>Vận chuyển</span>
            <strong class="lx-muted">Chưa xác định</strong>
        </div>

        <div class="lx-summary-total">
            <span>Tạm tính</span>
            <strong>{{ number_format($total) }}₫</strong>
        </div>

        <div class="lx-cart-actions">
            <a href="{{ route('linxen.checkout') }}"
               class="lx-btn-primary lx-btn-full">
                Thanh toán
            </a>
        </div>

    </div>
    @endif

</div>

@endsection
