@php
    $items = collect((array) data_get($order ?? [], 'items', []));
    $totals = (array) data_get($order ?? [], 'totals', []);
    $address = (array) data_get($order ?? [], 'address', []);
@endphp

<div class="lxcv1-page" data-lxcv1-page="order-success">
    <section class="lxcv1-success-hero">
        <div class="lxcv1-success-hero__mark">✓</div>
        <p class="lxcv1-kicker">ĐÃ TIẾP NHẬN</p>
        <h1>Đặt hàng thành công</h1>
        <p>
            Mã đơn <strong>{{ data_get($order, 'order_code') }}</strong>.
            LIN XÉN đã ghi nhận đơn an toàn.
        </p>
    </section>

    <div class="lxcv1-receipt-layout">
        <section class="lxcv1-receipt-card">
            <p class="lxcv1-kicker">GIAO HÀNG</p>
            <h2>{{ data_get($address, 'receiver_name') }}</h2>
            <p>{{ data_get($address, 'receiver_phone') }}</p>
            <p>
                {{ data_get($address, 'street') }},
                {{ data_get($address, 'ward_name') }},
                {{ data_get($address, 'location_name') }}
            </p>

            <dl>
                <div><dt>Thanh toán</dt><dd>COD</dd></div>
                <div><dt>Trạng thái</dt><dd>{{ data_get($order, 'status') }}</dd></div>
                <div><dt>Tiếp nhận</dt><dd>{{ data_get($order, 'created_at') }}</dd></div>
            </dl>

            <div class="lxcv1-actions">
                <a
                    class="lxcv1-button lxcv1-button--dark"
                    href="{{ route('commerce.v2.orders.show', ['order' => data_get($order, 'order_id')]) }}"
                >
                    Xem chi tiết đơn
                </a>
                <a class="lxcv1-button lxcv1-button--text" href="{{ route('commerce.v2.shop') }}">
                    Tiếp tục mua sắm
                </a>
            </div>
        </section>

        <aside class="lxcv1-order-summary">
            <header>
                <div>
                    <p class="lxcv1-kicker">BIÊN NHẬN</p>
                    <h2>{{ $items->sum(fn ($item) => (int) data_get($item, 'quantity')) }} sản phẩm</h2>
                </div>
            </header>

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
