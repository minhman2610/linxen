@extends('storefront.luxe.layouts.app')

@section('content')

@php
    $cartItems = $cart ?? [];

    $subtotal = collect($cartItems)->sum(function ($item) {
        return ($item['price'] ?? 0) * ($item['qty'] ?? 0);
    });

    // 🚚 CHƯA CHỌN ĐỊA CHỈ / PHƯƠNG THỨC → CHƯA TÍNH SHIP
    $shippingFee = null;

    // 👉 Tổng tiền hiện tại chỉ là TẠM TÍNH
    $total = $subtotal;

    $customer = session('customer') ?? null;
@endphp

<div class="lx-checkout-container">

    {{-- =========================
        LEFT: SHIPPING + INFO
    ========================== --}}
    <div class="lx-checkout-left">

        <h3>Thông tin giao hàng</h3>

        {{-- LOGIN BADGE --}}
        @if($customer)
            <div class="lx-login-badge">
                👋 Xin chào
                <strong>{{ $customer['name'] ?? $customer['phone'] }}</strong>
                <span class="lx-login-note">
                    Bạn đang mua hàng với tài khoản thành viên
                </span>
            </div>
        @endif

        {{-- (Các block địa chỉ / form giao hàng giữ nguyên của anh) --}}

    </div>

    {{-- =========================
        RIGHT: ORDER SUMMARY
    ========================== --}}
    <div class="lx-checkout-right">

        <div class="lx-summary-box">

            <h4>Đơn hàng của bạn</h4>

            {{-- ITEMS --}}
            <div class="lx-summary-items">
                @foreach($cartItems as $item)
                    <div class="lx-summary-item">
                        <div class="lx-summary-item-name">
                            {{ $item['name'] }}
                            <span class="lx-summary-qty">
                                × {{ $item['qty'] }}
                            </span>
                        </div>
                        <div class="lx-summary-item-price">
                            {{ number_format($item['price'] * $item['qty']) }}₫
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- SUBTOTAL --}}
            <div class="lx-summary-row">
                <span>Tạm tính</span>
                <strong>{{ number_format($subtotal) }}₫</strong>
            </div>

            {{-- SHIPPING (PENDING) --}}
            <div class="lx-summary-row lx-summary-row--pending">
                <span>Vận chuyển</span>
                <strong class="lx-muted">Chưa xác định</strong>
            </div>

            <div class="lx-summary-note--inline">
                Phí vận chuyển sẽ được tính sau khi bạn chọn
                <strong>địa chỉ nhận hàng</strong> và
                <strong>phương thức giao hàng</strong>.
            </div>

            {{-- TOTAL --}}
            <div class="lx-summary-total">
                <span>Tạm tính</span>
                <strong>{{ number_format($total) }}₫</strong>
            </div>

            {{-- PLACE ORDER --}}
            <button type="submit"
                    form="checkoutForm"
                    class="lx-btn-primary lx-btn-full lx-btn-checkout">
                <span class="lx-btn-main">ĐẶT HÀNG</span>
                <span class="lx-btn-sub">
                    Xác nhận đơn • Thanh toán COD
                </span>
            </button>

        </div>

    </div>

</div>

@endsection
