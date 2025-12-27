@extends('storefront.luxe.layouts.app')

@section('content')

@php
    $cartItems   = $cart ?? [];
    $subtotal    = collect($cartItems)->sum(fn($i) => ($i['price'] ?? 0) * ($i['qty'] ?? 0));
    $shippingFee = $subtotal >= 1000000 ? 0 : 30000;
    $total       = $subtotal + $shippingFee;

    /*
    |--------------------------------------------------------------------------
    | 🔒 SNAPSHOT CART CHO CHECKOUT.JS (ERP REQUIRE)
    |--------------------------------------------------------------------------
    */
    $checkoutCart = [];
    foreach ($cartItems as $item) {
        $checkoutCart[] = [
            'product_id' => $item['product_id'] ?? null,
            'qty'        => (int) $item['qty'],
            'price'      => (float) $item['price'],
            'note'       => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 👤 CUSTOMER SESSION (NORMALIZE)
    |--------------------------------------------------------------------------
    | - session có thể là array (từ ERP auto-login)
    | - ép về object để Blade dùng thống nhất
    |--------------------------------------------------------------------------
    */
    $customerSession = session('customer');
    $customer = $customerSession ? (object) $customerSession : null;

@endphp


<section class="lx-checkout-page">

    {{-- HEADER --}}
    <div class="lx-checkout-header">
        <h1>Thanh toán</h1>
        <a href="{{ route('linxen.cart') }}" class="lx-checkout-back">
            ← Quay lại giỏ hàng
        </a>
    </div>
    @if(($justRegistered ?? false) && $customer)
    <div class="lx-member-success">
        <div class="lx-member-success-icon">🎉</div>
        <div class="lx-member-success-content">
            <strong>Đăng ký thành công!</strong>
            <div>
                Chào mừng <b>{{ $customer->name ?? $customer->phone }}</b>
                trở thành <b>thành viên LIN XÉN</b>.
            </div>
            <div class="lx-member-success-sub">
                Bạn đã được đăng nhập và có thể tiếp tục đặt hàng.
            </div>
        </div>
    </div>
@endif


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

    <h3 class="lx-checkout-title">Thông tin giao hàng</h3>

    {{-- LOGIN BADGE (NHẸ – KHÔNG LẤN FLOW) --}}
    @if($customer)
        <div class="lx-login-badge">
            👋 <strong>{{ $customer->name ?? $customer->phone }}</strong>
            <span class="lx-login-note">Đang mua với tài khoản thành viên</span>
        </div>
    @endif

    {{-- =========================
    SHIPPING ADDRESS (DEFAULT)
========================== --}}
<div class="lx-checkout-section lx-checkout-address">

    <div class="lx-section-head">
        <strong>Địa chỉ nhận hàng</strong>

        @if(!empty($addresses))
            <button type="button"
                    class="lx-btn lx-btn-outline lx-btn-sm lx-btn-change-address"
                    onclick="openAddressPopup()">
                Thay đổi
            </button>
        @else
            <a href="{{ route('linxen.account.index') }}"
               class="lx-address-config-link">
                ⚙️ Thêm địa chỉ
            </a>
        @endif
    </div>

    @php
        $defaultAddress = collect($addresses ?? [])
            ->firstWhere('is_default', true)
            ?? ($addresses[0] ?? null);
    @endphp

    @if($defaultAddress)
        <div class="lx-address-default">
            <input type="hidden"
                   name="shipping_address_id"
                   value="{{ $defaultAddress['id'] }}">

            <div class="lx-address-info">
                <div class="lx-address-head">
                    <strong>{{ $defaultAddress['name'] }}</strong>
                    <span class="lx-address-phone">{{ $defaultAddress['phone'] }}</span>
                    <span class="lx-badge-default">Mặc định</span>
                </div>

                <div class="lx-address-text">
                    {{ $defaultAddress['address'] }}
                </div>
            </div>
        </div>
    @else
        <div class="lx-address-empty">
            <span>Bạn chưa có địa chỉ nhận hàng.</span>
        </div>
    @endif

</div>



            {{-- PHONE – PRIMARY IDENTITY --}}
            <div class="lx-form-group lx-form-phone">
                <label>Số điện thoại</label>
                <input type="tel"
                       name="phone"
                       id="lx-phone"
                       placeholder="Nhập số điện thoại"
                       autocomplete="tel"
                       value="{{ $customer->phone ?? '' }}"
                       @if($customer) readonly @endif
                       required>

                {{-- STATUS MESSAGE (AJAX) --}}
                <div id="lx-phone-status"
                     class="lx-phone-status"
                     style="display:none"></div>
            </div>

            {{-- NAME --}}
            <div class="lx-form-group">
                <label>Họ và tên</label>
                <input type="text"
                       name="name"
                       id="lx-name"
                       placeholder="Họ và tên người nhận"
                       value="{{ $customer->name ?? '' }}"
                       required>
            </div>

            {{-- LOCATION --}}
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

            {{-- ADDRESS --}}
            <div class="lx-form-group">
                <label>Số nhà, tên đường</label>
                <input type="text"
                       name="street"
                       placeholder="Ví dụ: 12 Nguyễn Trãi"
                       required>
            </div>

            {{-- NOTE --}}
            <div class="lx-form-group">
                <label>Ghi chú</label>
                <textarea name="note"
                          rows="2"
                          placeholder="Ghi chú cho đơn hàng (nếu có)"></textarea>
            </div>

            {{-- MEMBER HIDDEN FIELDS (JS CONTROL) --}}
            <input type="hidden" name="member_action" id="member_action">
            <input type="hidden" name="member_email" id="member_email">
            <input type="hidden" name="member_password" id="member_password">

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

            {{-- PAYMENT METHOD --}}
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

            {{-- ACTIONS --}}
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
            SNAPSHOT CART → CHECKOUT.JS
        ========================== --}}
        <script>
            window.__CHECKOUT_CART__ = {!! json_encode($checkoutCart, JSON_UNESCAPED_UNICODE) !!};
        </script>

    </form>
    @endif

    {{-- =========================
        MEMBER PROMPT MODAL
    ========================== --}}
    @if(!$customer)
    <div id="lx-member-modal" class="lx-modal" style="display:none">
        <div class="lx-modal-overlay"></div>

        <div class="lx-modal-content lx-member-box">

            <div class="lx-member-head">
                <div class="lx-member-icon">✨</div>
                <h3 id="lx-member-title"></h3>
                <p id="lx-member-desc"></p>
            </div>
            {{-- ERROR INLINE --}}
<div id="lx-member-error"
     class="lx-member-error"
     style="display:none">
</div>

            {{-- LOGIN (KHÁCH CŨ) --}}
            <div id="lx-member-login" class="lx-member-section" style="display:none">
                <div class="lx-input-group">
                    <input type="password"
                           id="lx-member-password"
                           placeholder="Mật khẩu đăng nhập">
                </div>
            </div>

            {{-- REGISTER (KHÁCH MỚI) --}}
            <div id="lx-member-register" class="lx-member-section" style="display:none">
                <div class="lx-input-group">
                    <input type="email"
                           id="lx-member-email"
                           placeholder="Email (nhận ưu đãi & thông báo)">
                </div>

                <div class="lx-input-group">
                    <input type="password"
                           id="lx-member-new-password"
                           placeholder="Tạo mật khẩu">
                </div>

                <div class="lx-input-group">
                    <input type="password"
                           id="lx-member-new-password-confirm"
                           placeholder="Nhập lại mật khẩu">
                </div>

                <div class="lx-member-hint">
                    🔒 Mật khẩu dùng để đăng nhập và tích lũy quyền lợi thành viên
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="lx-modal-actions lx-member-actions">
                <button id="lx-member-confirm"
                        class="lx-btn-primary lx-btn-icon">
                    <span class="lx-btn-icon-left">✔</span>
                    <span>Tiếp tục</span>
                </button>

                <button id="lx-member-skip"
                        class="lx-btn-secondary lx-btn-icon">
                    <span class="lx-btn-icon-left">⚡</span>
                    <span>Mua nhanh không đăng nhập</span>
                </button>
            </div>
        </div>
    </div>
    @endif
    {{-- =========================
    ADDRESS PICKER MODAL
========================== --}}
@if(!empty($addresses))
<div id="lx-address-modal" class="lx-modal-overlay" style="display:none">

    <div class="lx-modal lx-address-modal">

        <div class="lx-modal-head">
            <strong>Chọn địa chỉ giao hàng</strong>
            <button type="button"
                    class="lx-modal-close"
                    onclick="closeAddressPopup()">✕</button>
        </div>

        <div class="lx-modal-body lx-address-list">
            @foreach($addresses as $addr)
                <label class="lx-address-card">
                    <input type="radio"
                           name="address_pick"
                           value="{{ $addr['id'] }}"
                           data-name="{{ $addr['name'] }}"
                           data-phone="{{ $addr['phone'] }}"
                           data-address="{{ $addr['address'] }}"
                           {{ $addr['is_default'] ? 'checked' : '' }}>

                    <div class="lx-address-info">
                        <div class="lx-address-head">
                            <strong>{{ $addr['name'] }}</strong>
                            <span class="lx-address-phone">{{ $addr['phone'] }}</span>

                            @if($addr['is_default'])
                                <span class="lx-badge-default">Mặc định</span>
                            @endif
                        </div>

                        <div class="lx-address-text">
                            {{ $addr['address'] }}
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        <div class="lx-modal-actions">
            <button type="button"
                    class="lx-btn-primary lx-btn-full"
                    onclick="confirmAddressPick()">
                Chọn địa chỉ này
            </button>
        </div>

    </div>
</div>
@endif


</section>

@endsection
