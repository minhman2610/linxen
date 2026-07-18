@php
    $items = collect((array) data_get($order ?? [], 'items', []));
    $totals = (array) data_get($order ?? [], 'totals', []);
    $address = (array) data_get($order ?? [], 'address', []);
    $status = (string) data_get($order, 'status');
    $providerStatus = (string) data_get($order, 'provider_status');
    $customerStatus = match (true) {
        $status === 'canceled' => 'Đã hủy',
        $providerStatus === 'created' => 'Đã chuyển sang xử lý',
        $providerStatus === 'unknown' => 'Đang xác minh',
        default => 'Đã tiếp nhận',
    };
@endphp

<div class="lxcv1-page" data-lxcv1-page="order">
    <section class="lxcv1-checkout-heading">
        <div>
            <p class="lxcv1-kicker">{{ data_get($order, 'order_code') }}</p>
            <h1>Chi tiết đơn hàng</h1>
            <p>Trạng thái: <strong>{{ $customerStatus }}</strong></p>
        </div>
    </section>

    <div class="lxcv1-receipt-layout">
        <section class="lxcv1-receipt-card">
            <p class="lxcv1-kicker">ĐỊA CHỈ NHẬN HÀNG</p>
            <h2>{{ data_get($address, 'receiver_name') }}</h2>
            <p>{{ data_get($address, 'receiver_phone') }}</p>
            <p>
                {{ data_get($address, 'street') }},
                {{ data_get($address, 'ward_name') }},
                {{ data_get($address, 'location_name') }}
            </p>

            @if($providerStatus === 'unknown')
                <div class="lxcv1-alert">Hệ thống đang xác minh trạng thái và không tạo lại đơn để tránh trùng.</div>
            @elseif($providerStatus === 'created')
                <div class="lxcv1-alert lxcv1-alert--success">Đơn đã được chuyển sang hệ thống xử lý bán hàng.</div>
            @else
                <div class="lxcv1-alert">LIN XÉN đã tiếp nhận đơn và đang chuẩn bị xử lý.</div>
            @endif

            @if(data_get($order, 'can_cancel'))
                <form method="post" action="{{ route('commerce.v2.orders.cancel', ['order' => data_get($order, 'order_id')]) }}">
                    @csrf
                    @method('DELETE')
                    <button class="lxcv1-text-button lxcv1-text-button--danger" type="submit">Hủy đơn hàng</button>
                </form>
            @endif
        </section>

        <aside class="lxcv1-order-summary">
            <div class="lxcv1-order-lines">
                @foreach($items as $item)
                    <article class="is-text-only">
                        <div>
                            <strong>{{ data_get($item, 'product_name') }}</strong>
                            <small>
                                {{ data_get($item, 'color_name') }}
                                · Size {{ data_get($item, 'size') }}
                                · SL {{ (int) data_get($item, 'quantity') }}
                            </small>
                        </div>
                        <b>{{ number_format((float) data_get($item, 'line_total'), 0, ',', '.') }}₫</b>
                    </article>
                @endforeach
            </div>

            <dl class="lxcv1-order-totals">
                <div><dt>Tạm tính</dt><dd>{{ number_format((float) data_get($totals, 'subtotal'), 0, ',', '.') }}₫</dd></div>
                <div><dt>Phí giao hàng</dt><dd>{{ number_format((float) data_get($totals, 'shipping_fee'), 0, ',', '.') }}₫</dd></div>
                <div class="is-grand"><dt>Tổng COD</dt><dd>{{ number_format((float) data_get($totals, 'grand_total'), 0, ',', '.') }}₫</dd></div>
            </dl>
        </aside>
    </div>
</div>
