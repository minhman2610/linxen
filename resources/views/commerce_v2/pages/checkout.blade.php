@extends('commerce_v2.layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
@php
    $customer = (array) data_get($account, 'customer', []);
    $addresses = collect((array) data_get($account, 'addresses', []));
    $items = collect((array) data_get($cart, 'items', []));
    $summary = (array) data_get($cart, 'summary', []);
    $defaultAddressId = (string) data_get(
        $addresses->firstWhere('is_default', true),
        'id',
        data_get($addresses->first(), 'id')
    );
@endphp

<section class="lxv2-page-head">
    <p class="lxv2-eyebrow">Checkout</p>
    <h1>Địa chỉ và giao hàng</h1>
    <p>
        ERP sẽ kiểm tra lại SKU, giá, tồn kho và phí giao hàng trước khi tạo báo giá.
    </p>
</section>

<div class="lxv2-checkout-grid">
    <section class="lxv2-checkout-card">
        <div class="lxv2-checkout-card__head">
            <div>
                <p class="lxv2-eyebrow">Người mua</p>
                <h2>{{ data_get($customer, 'phone') }}</h2>
            </div>
            <a href="{{ route('commerce.v2.account.index') }}">
                Xem tài khoản
            </a>
        </div>

        @if($addresses->isEmpty())
            <div class="lxv2-empty">
                <h3>Chưa có địa chỉ nhận hàng</h3>
                <p>
                    Phase hiện tại chỉ dùng địa chỉ đã được ERP xác minh quyền sở hữu.
                </p>
                <a
                    class="lxv2-button"
                    href="{{ route('commerce.v2.account.index') }}"
                >
                    Kiểm tra tài khoản
                </a>
            </div>
        @else
            <form
                method="post"
                action="{{ route('commerce.v2.checkout.quote.create') }}"
                class="lxv2-checkout-form"
            >
                @csrf

                <fieldset>
                    <legend>Địa chỉ nhận hàng</legend>

                    <div class="lxv2-address-options">
                        @foreach($addresses as $address)
                            <label class="lxv2-address-option">
                                <input
                                    type="radio"
                                    name="shipping_address_id"
                                    value="{{ data_get($address, 'id') }}"
                                    required
                                    @checked(
                                        old(
                                            'shipping_address_id',
                                            $defaultAddressId
                                        ) === data_get($address, 'id')
                                    )
                                >

                                <span>
                                    <strong>
                                        {{ data_get($address, 'receiver_name') }}
                                        · {{ data_get($address, 'receiver_phone') }}
                                    </strong>
                                    <small>
                                        {{ data_get($address, 'street') }},
                                        {{ data_get($address, 'ward_name') }},
                                        {{ data_get($address, 'location_name') }}
                                    </small>
                                    @if(data_get($address, 'is_default'))
                                        <em>Mặc định</em>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Phương thức giao hàng</legend>
                    <label class="lxv2-address-option">
                        <input
                            type="radio"
                            name="shipping_method"
                            value="standard"
                            checked
                        >
                        <span>
                            <strong>Giao hàng tiêu chuẩn</strong>
                            <small>
                                Phí chính xác được ERP tính từ shipping policy đang hiệu lực.
                            </small>
                        </span>
                    </label>
                </fieldset>

                <fieldset>
                    <legend>Thanh toán</legend>
                    <label class="lxv2-address-option">
                        <input
                            type="radio"
                            name="payment_method"
                            value="cod"
                            checked
                        >
                        <span>
                            <strong>Thanh toán khi nhận hàng</strong>
                            <small>COD — chưa tạo đơn ở phase này.</small>
                        </span>
                    </label>
                </fieldset>

                <button
                    type="submit"
                    class="lxv2-button lxv2-button--wide"
                >
                    Tạo báo giá
                </button>
            </form>
        @endif
    </section>

    <aside class="lxv2-checkout-card lxv2-checkout-summary">
        <p class="lxv2-eyebrow">Giỏ hàng</p>
        <h2>{{ (int) data_get($summary, 'quantity_total', 0) }} sản phẩm</h2>

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
                        <strong>{{ data_get($item, 'product_name') }}</strong>
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

        <div class="lxv2-checkout-total">
            <span>Tạm tính hiện tại</span>
            <strong>
                {{ number_format(
                    (float) data_get($summary, 'subtotal'),
                    0,
                    ',',
                    '.'
                ) }}₫
            </strong>
        </div>

        <p class="lxv2-next-phase-note">
            Con số cuối cùng chỉ có hiệu lực sau khi ERP tạo quote có TTL.
        </p>
    </aside>
</div>
@endsection
