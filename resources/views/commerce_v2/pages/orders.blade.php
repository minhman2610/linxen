@extends('commerce_v2.layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
@php
    $items = collect((array) data_get($orders, 'items', []));
@endphp

<section class="lxv2-page-head">
    <p class="lxv2-eyebrow">Tài khoản</p>
    <h1>Đơn hàng của anh</h1>
    <p>Local order được ghi nhận trước, sau đó mới đồng bộ KiotViet qua outbox.</p>
</section>

@if($items->isEmpty())
    <section class="lxv2-empty">
        <h2>Chưa có đơn hàng</h2>
        <a class="lxv2-button" href="{{ route('commerce.v2.shop') }}">
            Xem sản phẩm
        </a>
    </section>
@else
    <section class="lxv2-order-list">
        @foreach($items as $order)
            <a
                class="lxv2-order-card"
                href="{{ route(
                    'commerce.v2.orders.show',
                    ['order' => data_get($order, 'order_id')]
                ) }}"
            >
                <div>
                    <p class="lxv2-eyebrow">
                        {{ data_get($order, 'order_code') }}
                    </p>
                    <strong>
                        {{ (int) data_get($order, 'quantity_total') }}
                        sản phẩm
                    </strong>
                    <small>{{ data_get($order, 'created_at') }}</small>
                </div>
                <div>
                    <b>
                        {{ number_format(
                            (float) data_get($order, 'grand_total'),
                            0,
                            ',',
                            '.'
                        ) }}₫
                    </b>
                    <span class="lxv2-order-status">
                        {{ data_get($order, 'provider_status') }}
                    </span>
                </div>
            </a>
        @endforeach
    </section>
@endif
@endsection
