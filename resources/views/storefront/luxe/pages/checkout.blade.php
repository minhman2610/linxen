@extends('storefront.luxe.layouts.app')

@section('content')

@php
    $cartItems   = $cart ?? [];
    $subtotal    = collect($cartItems)->sum(fn($i) => ($i['price'] ?? 0) * ($i['qty'] ?? 0));
    $shippingFee = $subtotal >= 500000 ? 0 : 30000;
    $total       = $subtotal + $shippingFee;

    // 🔒 SNAPSHOT CART CHO CHECKOUT.JS (AN TOÀN BLADE)
    $checkoutCart = [];
    foreach ($cartItems as $item) {
        $checkoutCart[] = [
            'product_id' => $item['product_id'] ?? null,
            'qty'        => (int) $item['qty'],
            'price'      => (float) $item['price'],
            'note'       => null,
        ];
    }
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

        {{-- =========================
            LEFT – SHIPPING INFO
        ========================== --}}
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

        {{-- =========================
            RIGHT – ORDER SUMMARY
        ========================== --}}
        <aside class="lx-checkout-right">

            <h3 class="lx-checkout-title">Đơn hàng của bạn</h3>

            <div class="lx-checkout-items">
                @foreach($cartItems as $item)
                    <div class="lx-checkout-item">
                        <div class="lx-checkout-thumb">
                            <img
                                src="{{ $item['image'] ?? asset('images/no-image.png') }}"
                                alt="{{ $item['name'] }}"
                                loading="lazy"
                            >
                        </div>

                        <div class="lx-checkout-item-info">
                            <div class="lx-checkout-item-name">
                                {{ $item['name'] }}
                            </div>

                            @if(!empty($item['attrs']))
                                <div class="lx-checkout-item-variant">
                                    {{ implode(' · ', $item['attrs']) }}
                                </div>
                            @endif

                            <div class="lx-checkout-item-meta">
                                <span>SL: {{ $item['qty'] }}</span>
                                <strong>
                                    {{ number_format(($item['price'] ?? 0) * $item['qty']) }}₫
                                </strong>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <hr class="lx-checkout-divider">

            <div class="lx-checkout-summary">
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
            </div>

            <div class="lx-checkout-payment">
                <div class="lx-payment-badge">
                    <span class="lx-payment-icon">💵</span>
                    <div>
                        <strong>Thanh toán khi nhận hàng (COD)</strong>
                        <div class="lx-payment-hint">
                            Thanh toán cho nhân viên giao hàng
                        </div>
                    </div>
                </div>
            </div>

            <div class="lx-checkout-actions">
                <button type="submit"
                        class="lx-btn-primary lx-btn-full lx-btn-checkout">
                    <span class="lx-btn-main">ĐẶT HÀNG</span>
                    <span class="lx-btn-sub">Xác nhận đơn • Thanh toán COD</span>
                </button>

                <a href="{{ route('linxen.home') }}" class="lx-checkout-continue">
                    ← Tiếp tục mua sắm
                </a>
            </div>

            <div id="lx-checkout-error"
                 class="lx-checkout-error"
                 style="display:none"></div>
        </aside>

        {{-- =========================
            SNAPSHOT CART → JS (FIX LỖI BLADE)
        ========================== --}}
        <script>
            window.__CHECKOUT_CART__ = {!! json_encode($checkoutCart, JSON_UNESCAPED_UNICODE) !!};
        </script>

    </form>
    @endif
</section>
@endsection
