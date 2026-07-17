@extends('commerce_v2.layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
@php
    $items = collect((array) data_get($order, 'items', []));
    $totals = (array) data_get($order, 'totals', []);
    $address = (array) data_get($order, 'address', []);
@endphp

<section class="lxv2-order-success">
    <div class="lxv2-order-success__mark">✓</div>
    <p class="lxv2-eyebrow">Đã tiếp nhận</p>
    <h1>Đặt hàng thành công</h1>
    <p>
        Mã đơn:
        <strong>{{ data_get($order, 'order_code') }}</strong>
    </p>
    <p>
        LIN XÉN đã ghi nhận đơn an toàn.
        Đồng bộ KiotViet được xử lý riêng qua outbox.
    </p>
</section>

<div class="lxv2-checkout-grid">
    <section class="lxv2-checkout-card">
        <h2>Thông tin nhận hàng</h2>
        <p>
            <strong>
                {{ data_get($address, 'receiver_name') }}
                · {{ data_get($address, 'receiver_phone') }}
            </strong>
        </p>
        <p>
            {{ data_get($address, 'street') }},
            {{ data_get($address, 'ward_name') }},
            {{ data_get($address, 'location_name') }}
        </p>

        <dl class="lxv2-quote-details">
            <div>
                <dt>Thanh toán</dt>
                <dd>COD</dd>
            </div>
            <div>
                <dt>Trạng thái</dt>
                <dd>{{ data_get($order, 'status') }}</dd>
            </div>
            <div>
                <dt>Tiếp nhận</dt>
                <dd>{{ data_get($order, 'created_at') }}</dd>
            </div>
        </dl>

        <div class="lxv2-order-success__actions">
            <a
                class="lxv2-button"
                href="{{ route(
                    'commerce.v2.orders.show',
                    ['order' => data_get($order, 'order_id')]
                ) }}"
            >
                Xem chi tiết đơn
            </a>
            <a
                class="lxv2-button lxv2-button--soft"
                href="{{ route('commerce.v2.shop') }}"
            >
                Tiếp tục mua sắm
            </a>
        </div>

        <p class="lxv2-account-note">
            Anh đang mua bằng guest checkout thì biên nhận này chỉ
            được giữ trong phiên trình duyệt hiện tại. Xác minh số
            điện thoại bằng magic link hoặc OTP sau này để xem lịch sử
            trên thiết bị khác.
        </p>
    </section>

    <aside class="lxv2-checkout-card lxv2-checkout-summary">
        <div class="lxv2-checkout-lines">
            @foreach($items as $item)
                <article>
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

        <dl class="lxv2-quote-totals">
            <div>
                <dt>Tạm tính</dt>
                <dd>
                    {{ number_format(
                        (float) data_get($totals, 'subtotal'),
                        0,
                        ',',
                        '.'
                    ) }}₫
                </dd>
            </div>
            <div>
                <dt>Phí giao hàng</dt>
                <dd>
                    {{ number_format(
                        (float) data_get(
                            $totals,
                            'shipping_fee'
                        ),
                        0,
                        ',',
                        '.'
                    ) }}₫
                </dd>
            </div>
            <div class="lxv2-quote-totals__grand">
                <dt>Tổng COD</dt>
                <dd>
                    {{ number_format(
                        (float) data_get(
                            $totals,
                            'grand_total'
                        ),
                        0,
                        ',',
                        '.'
                    ) }}₫
                </dd>
            </div>
        </dl>
    </aside>
</div>
@endsection
