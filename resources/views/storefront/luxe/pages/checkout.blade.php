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

    <form method="POST"
          action="{{ route('linxen.checkout.place_order') }}"
          class="lx-checkout-content">

        @csrf

        {{-- LEFT: CUSTOMER INFO --}}
        <div class="lx-checkout-left">

            <h3>Thông tin giao hàng</h3>

            <div class="lx-form-group">
                <label>Họ và tên</label>
                <input type="text" name="name" required placeholder="Nguyễn Văn A">
            </div>

            <div class="lx-form-group">
                <label>Số điện thoại</label>
                <input type="tel" name="phone" required placeholder="09xxxxxxxx">
            </div>

            <div class="lx-form-group">
                <label>Email (không bắt buộc)</label>
                <input type="email" name="email" placeholder="email@example.com">
            </div>

            <div class="lx-form-group">
                <label>Địa chỉ giao hàng</label>
                <textarea name="address" rows="3"
                          required
                          placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành"></textarea>
            </div>

            <div class="lx-form-group">
                <label>Ghi chú đơn hàng</label>
                <textarea name="note" rows="2"
                          placeholder="Ghi chú cho shop (nếu có)"></textarea>
            </div>

        </div>

        {{-- RIGHT: ORDER SUMMARY --}}
        <div class="lx-checkout-right">

            <h3>Đơn hàng của bạn</h3>

            <div class="lx-checkout-items">
                @foreach($cartItems as $item)
                    <div class="lx-checkout-item">
                        <div class="lx-checkout-item-name">
                            {{ $item['name'] }}
                            <span class="lx-checkout-item-qty">
                                × {{ $item['qty'] }}
                            </span>
                        </div>
                        <div class="lx-checkout-item-price">
                            {{ number_format($item['price'] * $item['qty']) }}₫
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="lx-checkout-summary-row">
                <span>Tạm tính</span>
                <span>{{ number_format($subtotal) }}₫</span>
            </div>

            <div class="lx-checkout-summary-row">
                <span>Phí vận chuyển</span>
                <span>
                    {{ $shippingFee > 0 ? number_format($shippingFee).'₫' : 'Miễn phí' }}
                </span>
            </div>

            <div class="lx-checkout-summary-total">
                <span>Tổng cộng</span>
                <span>{{ number_format($total) }}₫</span>
            </div>

            {{-- PAYMENT --}}
            <div class="lx-checkout-payment">
                <label>
                    <input type="radio" name="payment_method" value="cod" checked>
                    Thanh toán khi nhận hàng (COD)
                </label>
            </div>

            <button type="submit"
                    class="lx-btn-primary lx-btn-full">
                ĐẶT HÀNG
            </button>

            <div class="lx-checkout-note">
                ✔ Xác nhận đơn trong giờ hành chính<br>
                ✔ Giao hàng toàn quốc
            </div>

        </div>

    </form>

    @endif

</section>
@endsection
