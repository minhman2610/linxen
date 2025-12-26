@extends('storefront.luxe.layouts.app')

@section('content')

<div class="lx-account-container">

    <div class="lx-account-header">
        <h2>Đơn hàng của tôi</h2>
        <p>
            Xin chào
            <strong>{{ $customer->name ?? $customer->phone }}</strong>
        </p>
    </div>

    @if(empty($orders))
        <div class="lx-empty-state">
            <p>Bạn chưa có đơn hàng nào.</p>
            <a href="{{ route('linxen.home') }}"
               class="lx-btn-primary lx-btn-light">
                Tiếp tục mua sắm
            </a>
        </div>
    @else
        <div class="lx-order-list">

            @foreach($orders as $order)
                <a href="{{ route('linxen.account.orders.show', $order['code']) }}"
                   class="lx-order-card">

                    <div class="lx-order-top">
                        <span class="lx-order-code">
                            #{{ $order['code'] }}
                        </span>

                        <span class="lx-order-status status-{{ $order['status'] }}">
                            {{ $order['status_label'] }}
                        </span>
                    </div>

                    <div class="lx-order-bottom">
                        <span class="lx-order-date">
                            {{ $order['created_at'] }}
                        </span>

                        <span class="lx-order-total">
                            {{ number_format($order['total']) }}đ
                        </span>
                    </div>

                </a>
            @endforeach

        </div>
    @endif

</div>

@endsection
