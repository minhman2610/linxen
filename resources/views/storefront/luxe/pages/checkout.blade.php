@extends('storefront.luxe.layouts.app')

@section('content')

<link rel="stylesheet" href="/themes/luxe/assets/css/checkout.css">

@php
    $cartItems = $cart ?? [];
    $subtotal = collect($cartItems)->sum(fn($i) => ($i['price'] ?? 0) * ($i['qty'] ?? 0));
    $shippingFee = $subtotal >= 500000 ? 0 : 30000;
    $total = $subtotal + $shippingFee;
@endphp

<section class="lx-checkout-page">

    {{-- HEADER --}}
    <div class="lx-checkout-header">
        <h1>Thanh toán</h1>
        <a href="{{ route('linxen.cart') }}" class="lx-checkout-back">
            ← Quay lại giỏ hàng
        </a>
    </div>

    @if(empty($cartItems))
        <div class="lx-checkout-empty">
            <p>Giỏ hàng của bạn đang trống.</p>
            <a href="{{ route('linxen.home') }}" class="lx-btn-primary">
                Quay về trang chủ
            </a>
        </div>
    @else

    <form id="lx-checkout-form" class="lx-checkout-content">
        @csrf

        {{-- LEFT --}}
        <div class="lx-checkout-left">
            <h3>Thông tin giao hàng</h3>

            <div class="lx-form-group">
                <label>Họ và tên</label>
                <input type="text" name="name" required>
            </div>

            <div class="lx-form-group">
                <label>Số điện thoại</label>
                <input type="tel" name="phone" required>
            </div>

            <div class="lx-form-row">
                <div class="lx-form-group">
                    <label>Khu vực</label>
                    <select name="location_id" id="lx-location" required>
                        <option value="">-- Chọn khu vực --</option>
                    </select>
                </div>

                <div class="lx-form-group">
                    <label>Phường / Xã</label>
                    <select name="ward_id" id="lx-ward" required disabled>
                        <option value="">-- Chọn phường / xã --</option>
                    </select>
                </div>
            </div>

            <div class="lx-form-group">
                <label>Số nhà, tên đường</label>
                <input type="text" name="street" required>
            </div>

            <div class="lx-form-group">
                <label>Ghi chú</label>
                <textarea name="note" rows="2"></textarea>
            </div>
        </div>

        {{-- RIGHT --}}
        <aside class="lx-checkout-right">
            <h3>Đơn hàng của bạn</h3>

            <div class="lx-checkout-items">
                @foreach($cartItems as $item)
                    <div class="lx-checkout-item">
                        <div>
                            {{ $item['name'] }}
                            <span>× {{ $item['qty'] }}</span>
                        </div>
                        <div>
                            {{ number_format(($item['price'] ?? 0) * $item['qty']) }}₫
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="lx-checkout-summary-row">
                <span>Tạm tính</span>
                <span>{{ number_format($subtotal) }}₫</span>
            </div>

            <div class="lx-checkout-summary-row">
                <span>Vận chuyển</span>
                <span>{{ $shippingFee ? number_format($shippingFee).'₫' : 'Miễn phí' }}</span>
            </div>

            <div class="lx-checkout-summary-total">
                <span>Tổng cộng</span>
                <span>{{ number_format($total) }}₫</span>
            </div>

            <button type="submit" class="lx-btn-primary lx-btn-full">
                ĐẶT HÀNG
            </button>

            <div id="lx-checkout-error" class="lx-checkout-error" style="display:none"></div>
        </aside>

    </form>
    @endif
</section>
@endsection

