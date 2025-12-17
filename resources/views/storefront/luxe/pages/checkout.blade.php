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

        {{-- ================= LEFT: SHIPPING INFO ================= --}}
        <div class="lx-checkout-left">

            <h3>Thông tin giao hàng</h3>

            <div class="lx-form-group">
                <label>Họ và tên</label>
                <input type="text" name="name" required placeholder="Nguyễn Văn A">
            </div>

            <div class="lx-form-row">
                <div class="lx-form-group">
                    <label>Số điện thoại</label>
                    <input type="tel" name="phone" required placeholder="09xxxxxxxx">
                </div>

                <div class="lx-form-group">
                    <label>Email (không bắt buộc)</label>
                    <input type="email" name="email" placeholder="email@example.com">
                </div>
            </div>

            {{-- ADDRESS --}}
            <div class="lx-form-row">
                <div class="lx-form-group">
                    <label>Tỉnh / Thành phố</label>
                    <select name="province" required>
                        <option value="">-- Chọn Tỉnh / Thành --</option>
                        {{-- load bằng JS sau --}}
                    </select>
                </div>

                <div class="lx-form-group">
                    <label>Quận / Huyện</label>
                    <select name="district" required>
                        <option value="">-- Chọn Quận / Huyện --</option>
                    </select>
                </div>
            </div>

            <div class="lx-form-row">
                <div class="lx-form-group">
                    <label>Phường / Xã</label>
                    <select name="ward" required>
                        <option value="">-- Chọn Phường / Xã --</option>
                    </select>
                </div>

                <div class="lx-form-group">
                    <label>Số nhà, tên đường</label>
                    <input type="text" name="street" required placeholder="Số nhà, tên đường">
                </div>
            </div>

            <div class="lx-form-group">
                <label>Ghi chú đơn hàng</label>
                <textarea name="note" rows="2"
                          placeholder="Ghi chú cho shop (nếu có)"></textarea>
            </div>

        </div>

        {{-- ================= RIGHT: ORDER SUMMARY ================= --}}
        <div class="lx-checkout-right">

            <h3>Đơn hàng của bạn</h3>

            <div class="lx-checkout-items">

                @foreach($cartItems as $item)
                    <div class="lx-checkout-item">

                        {{-- IMAGE --}}
                        <div class="lx-checkout-item-thumb">
                            <img
                                src="{{ $item['image'] ?? '/themes/luxe/assets/images/placeholder-product.jpg' }}"
                                alt="{{ $item['name'] }}">
                        </div>

                        {{-- INFO --}}
                        <div class="lx-checkout-item-info">
                            <div class="lx-checkout-item-name">
                                {{ $item['name'] }}
                            </div>

                            @if(!empty($item['variant']))
                                <div class="lx-checkout-item-variant">
                                    {{ $item['variant'] }}
                                </div>
                            @endif

                            <div class="lx-checkout-item-qty">
                                Số lượng: {{ $item['qty'] }}
                            </div>
                        </div>

                        {{-- PRICE --}}
                        <div class="lx-checkout-item-price">
                            {{ number_format($item['price'] * $item['qty']) }}₫
                        </div>

                    </div>
                @endforeach

            </div>

            {{-- SUMMARY --}}
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
                <label class="lx-radio">
                    <input type="radio" name="payment_method" value="cod" checked>
                    <span>Thanh toán khi nhận hàng (COD)</span>
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
