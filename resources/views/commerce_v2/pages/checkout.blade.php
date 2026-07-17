@extends('commerce_v2.layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
@php
    $items = collect((array) data_get($cart, 'items', []));
    $summary = (array) data_get($cart, 'summary', []);
    $shipping = (array) data_get(
        $capabilities,
        'shipping',
        []
    );
    $subtotal = (float) data_get(
        $summary,
        'subtotal',
        0
    );
    $shippingFee = (float) data_get(
        $shipping,
        'fee_amount',
        0
    );
    $freeThreshold = data_get(
        $shipping,
        'free_shipping_threshold'
    );
    $effectiveShippingFee = (
        $freeThreshold !== null
        && $subtotal >= (float) $freeThreshold
    ) ? 0 : $shippingFee;
    $estimatedTotal = $subtotal + $effectiveShippingFee;
    $orderAcceptEnabled = data_get(
        $capabilities,
        'order_accept_enabled'
    ) === true;
    $guestCheckoutEnabled = data_get(
        $capabilities,
        'guest_checkout_enabled'
    ) === true;
    $canSubmit = (
        $orderAcceptEnabled
        && ($isVerifiedCustomer || $guestCheckoutEnabled)
    );
@endphp

<section class="lxv2-page-head lxv2-checkout-head">
    <p class="lxv2-eyebrow">Thanh toán</p>
    <h1>Giao hàng và đặt hàng</h1>
    <p>
        Không cần tạo tài khoản. LIN XÉN kiểm tra lại giá, tồn kho,
        phí giao hàng và chống tạo trùng trước khi ghi nhận đơn.
    </p>
</section>

<div class="lxv2-one-page-checkout">
    <form
        method="post"
        action="{{ route('commerce.v2.checkout.place_order') }}"
        class="lxv2-checkout-card lxv2-one-page-form"
        data-lxv2-one-page-checkout
        data-wards-url-template="{{ route(
            'commerce.v2.checkout.wards',
            ['location' => '__LOCATION__']
        ) }}"
    >
        @csrf

        <section class="lxv2-checkout-section">
            <div class="lxv2-checkout-section__head">
                <span>1</span>
                <div>
                    <h2>Thông tin nhận hàng</h2>
                    <p>
                        Số điện thoại được dùng để liên hệ và nhận diện
                        customer trong ERP.
                    </p>
                </div>
            </div>

            <div class="lxv2-checkout-fields">
                <label class="lxv2-field lxv2-field--wide">
                    <span>Họ tên người nhận</span>
                    <input
                        name="receiver_name"
                        required
                        maxlength="191"
                        autocomplete="name"
                        value="{{ data_get($identity, 'receiver_name') }}"
                        placeholder="Nguyễn Văn A"
                    >
                </label>

                <label class="lxv2-field">
                    <span>Số điện thoại</span>
                    <input
                        name="phone"
                        required
                        maxlength="20"
                        inputmode="tel"
                        autocomplete="tel"
                        value="{{ data_get($identity, 'phone') }}"
                        placeholder="09xxxxxxxx"
                    >
                </label>

                <label class="lxv2-field">
                    <span>Email <small>không bắt buộc</small></span>
                    <input
                        type="email"
                        name="email"
                        maxlength="191"
                        autocomplete="email"
                        value="{{ data_get($identity, 'email') }}"
                        placeholder="email@example.com"
                    >
                </label>

                <label class="lxv2-field">
                    <span>Tỉnh / Thành phố</span>
                    <select
                        name="location_id"
                        required
                        data-lxv2-checkout-location
                    >
                        <option value="">Chọn Tỉnh / Thành phố</option>
                        @foreach($locations as $location)
                            <option
                                value="{{ data_get($location, 'id') }}"
                                @selected(
                                    (string) data_get(
                                        $identity,
                                        'location_id'
                                    ) === (string) data_get(
                                        $location,
                                        'id'
                                    )
                                )
                            >
                                {{ data_get($location, 'name') }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="lxv2-field">
                    <span>Phường / Xã</span>
                    <select
                        name="ward_id"
                        required
                        data-lxv2-checkout-ward
                        data-selected-ward="{{ data_get($identity, 'ward_id') }}"
                    >
                        @if(data_get($identity, 'ward_id'))
                            <option
                                value="{{ data_get($identity, 'ward_id') }}"
                                selected
                            >
                                {{ data_get($identity, 'ward_name') ?: 'Phường / Xã đã lưu' }}
                            </option>
                        @else
                            <option value="">
                                Chọn Tỉnh / Thành phố trước
                            </option>
                        @endif
                    </select>
                </label>

                <label class="lxv2-field lxv2-field--wide">
                    <span>Số nhà, tên đường</span>
                    <input
                        name="street"
                        required
                        maxlength="255"
                        autocomplete="street-address"
                        value="{{ data_get($identity, 'street') }}"
                        placeholder="Số nhà, tên đường, tòa nhà..."
                    >
                </label>
            </div>
        </section>

        <section class="lxv2-checkout-section">
            <div class="lxv2-checkout-section__head">
                <span>2</span>
                <div>
                    <h2>Giao hàng</h2>
                    <p>
                        Phí cuối cùng được ERP xác nhận tại thời điểm đặt hàng.
                    </p>
                </div>
            </div>

            <label class="lxv2-choice-card">
                <input
                    type="radio"
                    name="shipping_method"
                    value="standard"
                    checked
                >
                <span>
                    <strong>
                        {{ data_get(
                            $shipping,
                            'name',
                            'Giao hàng tiêu chuẩn'
                        ) }}
                    </strong>
                    <small>
                        {{
                            $effectiveShippingFee > 0
                                ? number_format(
                                    $effectiveShippingFee,
                                    0,
                                    ',',
                                    '.'
                                ) . '₫'
                                : 'Miễn phí'
                        }}
                    </small>
                </span>
            </label>
        </section>

        <section class="lxv2-checkout-section">
            <div class="lxv2-checkout-section__head">
                <span>3</span>
                <div>
                    <h2>Thanh toán</h2>
                    <p>Thanh toán khi nhận hàng.</p>
                </div>
            </div>

            <label class="lxv2-choice-card">
                <input
                    type="radio"
                    name="payment_method"
                    value="cod"
                    checked
                >
                <span>
                    <strong>COD</strong>
                    <small>Thanh toán cho đơn vị giao hàng khi nhận sản phẩm.</small>
                </span>
            </label>
        </section>

        @if(!$orderAcceptEnabled)
            <div class="lxv2-alert">
                Website đang ở chế độ UAT. Form thanh toán đã sẵn sàng
                nhưng hệ thống chưa mở nhận đơn thật.
            </div>
        @elseif(!$isVerifiedCustomer && !$guestCheckoutEnabled)
            <div class="lxv2-alert lxv2-alert--error">
                Guest checkout chưa được bật. Anh cần đăng nhập bằng
                magic link để đặt hàng.
            </div>
        @elseif(!$isVerifiedCustomer)
            <div class="lxv2-account-note">
                Anh đang mua không cần tài khoản. Sau khi đặt hàng,
                biên nhận được giữ trong phiên trình duyệt này.
                Muốn xem toàn bộ lịch sử đơn trên thiết bị khác,
                anh cần xác minh số điện thoại bằng magic link hoặc OTP
                khi kênh SMS được kết nối.
            </div>
        @endif

        <button
            type="submit"
            class="lxv2-button lxv2-button--wide lxv2-place-order"
            @disabled(!$canSubmit)
        >
            {{ $canSubmit ? 'Đặt hàng COD' : 'Chưa mở nhận đơn' }}
        </button>

        <p class="lxv2-checkout-legal">
            Khi bấm Đặt hàng, anh xác nhận thông tin nhận hàng là đúng.
            Hệ thống sẽ tự tạo snapshot giá, phí ship và tồn kho,
            nhưng không hiển thị thuật ngữ kỹ thuật cho khách.
        </p>
    </form>

    <aside class="lxv2-checkout-card lxv2-checkout-summary">
        <div class="lxv2-checkout-card__head">
            <div>
                <p class="lxv2-eyebrow">Đơn hàng</p>
                <h2>
                    {{ (int) data_get(
                        $summary,
                        'quantity_total',
                        0
                    ) }} sản phẩm
                </h2>
            </div>
            <a href="{{ route('commerce.v2.cart.index') }}">
                Sửa giỏ
            </a>
        </div>

        <div class="lxv2-checkout-lines">
            @foreach($items as $item)
                <article>
                    <img
                        src="{{ data_get($item, 'cover_url') }}"
                        alt=""
                        width="72"
                        height="90"
                    >
                    <div>
                        <strong>
                            {{ data_get($item, 'product_name') }}
                        </strong>
                        <small>
                            {{ data_get($item, 'color_name') }}
                            · Size {{ data_get($item, 'size') }}
                            · SL {{ (int) data_get($item, 'quantity') }}
                        </small>
                    </div>
                    <b>
                        {{ number_format(
                            (float) data_get($item, 'line_total'),
                            0,
                            ',',
                            '.'
                        ) }}₫
                    </b>
                </article>
            @endforeach
        </div>

        <dl class="lxv2-quote-totals lxv2-customer-totals">
            <div>
                <dt>Tạm tính</dt>
                <dd>{{ number_format($subtotal, 0, ',', '.') }}₫</dd>
            </div>
            <div>
                <dt>Phí giao hàng dự kiến</dt>
                <dd>
                    {{ number_format(
                        $effectiveShippingFee,
                        0,
                        ',',
                        '.'
                    ) }}₫
                </dd>
            </div>
            <div class="lxv2-quote-totals__grand">
                <dt>Tổng dự kiến</dt>
                <dd>{{ number_format($estimatedTotal, 0, ',', '.') }}₫</dd>
            </div>
        </dl>

        <p class="lxv2-next-phase-note">
            Tổng chính thức được ERP kiểm tra lại ngay khi anh bấm
            Đặt hàng.
        </p>
    </aside>
</div>
@endsection
