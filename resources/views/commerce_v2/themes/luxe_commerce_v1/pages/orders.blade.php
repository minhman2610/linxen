@php
    $verifiedHistory = (bool) ($verifiedHistory ?? false);
    $guestHistoryNotice = (bool) ($guestHistoryNotice ?? ! $verifiedHistory);
    $items = collect((array) data_get($orders ?? [], 'items', []));
@endphp

<div class="lxcv1-page" data-lxcv1-page="orders">
    <section class="lxcv1-checkout-heading">
        <div>
            <p class="lxcv1-kicker">ĐƠN HÀNG</p>
            <h1>{{ $verifiedHistory ? 'Lịch sử đơn hàng' : 'Đơn trong phiên hiện tại' }}</h1>
            <p>{{ $verifiedHistory ? 'Các đơn thuộc customer đã xác minh.' : 'Guest checkout chỉ hiển thị đơn trong phiên trình duyệt này.' }}</p>
        </div>
    </section>

    @if($guestHistoryNotice)
        <div class="lxcv1-account-note">
            LIN XÉN không tra cứu đơn chỉ bằng số điện thoại để bảo vệ dữ liệu khách hàng.
        </div>
    @endif

    @if($items->isEmpty())
        <section class="lxcv1-empty lxcv1-empty--large">
            <span>0</span>
            <h2>Chưa có đơn hàng</h2>
            <a class="lxcv1-button lxcv1-button--dark" href="{{ route('commerce.v2.shop') }}">Xem sản phẩm</a>
        </section>
    @else
        <section class="lxcv1-order-list">
            @foreach($items as $order)
                <a class="lxcv1-order-card" href="{{ route('commerce.v2.orders.show', ['order' => data_get($order, 'order_id')]) }}">
                    <div>
                        <p class="lxcv1-kicker">{{ data_get($order, 'order_code') }}</p>
                        <strong>{{ (int) data_get($order, 'quantity_total') }} sản phẩm</strong>
                        <small>{{ data_get($order, 'created_at') }}</small>
                    </div>
                    <div>
                        <b>{{ number_format((float) data_get($order, 'grand_total', data_get($order, 'totals.grand_total')), 0, ',', '.') }}₫</b>
                        <span>{{ data_get($order, 'status') }}</span>
                    </div>
                </a>
            @endforeach
        </section>
    @endif
</div>
