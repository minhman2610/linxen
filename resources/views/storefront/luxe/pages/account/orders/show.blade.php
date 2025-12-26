@extends('storefront.luxe.layouts.app')

@section('content')

<div class="lx-account-container">

    <div class="lx-account-header">
        <a href="{{ route('linxen.account.orders') }}"
           class="lx-back-link">
            ← Quay lại danh sách đơn
        </a>

        <h2>Đơn hàng #{{ $order['code'] }}</h2>

        <span class="lx-order-status status-{{ $order['status'] }}">
            {{ $order['status_label'] }}
        </span>
    </div>

    {{-- PRODUCTS --}}
    <div class="lx-order-section">
        <h3>Sản phẩm</h3>

        <div class="lx-order-items">
            @foreach($order['items'] as $item)
                <div class="lx-order-item">
                    <div class="lx-item-name">
                        {{ $item['name'] }}
                    </div>

                    <div class="lx-item-meta">
                        SL: {{ $item['qty'] }}
                        × {{ number_format($item['price']) }}đ
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- SHIPPING --}}
    <div class="lx-order-section">
        <h3>Thông tin giao hàng</h3>

        <div class="lx-order-shipping">
            <div><strong>{{ $order['shipping']['name'] }}</strong></div>
            <div>{{ $order['shipping']['phone'] }}</div>
            <div>{{ $order['shipping']['address'] }}</div>
        </div>
    </div>

    {{-- TOTAL --}}
    <div class="lx-order-total-box">
        <span>Tổng thanh toán</span>
        <strong>{{ number_format($order['total']) }}đ</strong>
    </div>

</div>

@endsection
