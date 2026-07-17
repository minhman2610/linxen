@extends('commerce_v2.layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
@php
    $items = collect((array) data_get($quote, 'items', []));
    $totals = (array) data_get($quote, 'totals', []);
    $address = (array) data_get($quote, 'address', []);
    $shipping = (array) data_get($quote, 'shipping', []);
    $ttlSeconds = max(
        0,
        (int) data_get($quote, 'ttl_remaining_seconds', 0)
    );
@endphp

<section class="lxv2-page-head">
    <p class="lxv2-eyebrow">Báo giá ERP</p>
    <h1>Xác nhận đặt hàng</h1>
    <p>
        Giá, tồn kho và phí giao hàng vừa được revalidate.
        Quote còn hiệu lực
        <strong>{{ max(1, (int) ceil($ttlSeconds / 60)) }} phút</strong>.
    </p>
</section>

<div class="lxv2-checkout-grid">
    <section class="lxv2-checkout-card">
        <div class="lxv2-quote-status">
            <span>Đang hiệu lực</span>
            <small>{{ data_get($quote, 'quote_id') }}</small>
        </div>

        <h2>Địa chỉ nhận hàng</h2>
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

        <h2>Phương thức</h2>
        <dl class="lxv2-quote-details">
            <div>
                <dt>Giao hàng</dt>
                <dd>{{ data_get($shipping, 'name') }}</dd>
            </div>
            <div>
                <dt>Thanh toán</dt>
                <dd>COD</dd>
            </div>
            <div>
                <dt>Local order</dt>
                <dd>Idempotency bảo vệ</dd>
            </div>
            <div>
                <dt>KiotViet</dt>
                <dd>Outbox bất đồng bộ</dd>
            </div>
        </dl>

        <div class="lxv2-alert">
            Mỗi lần bấm được gắn một idempotency key. Cùng một yêu cầu
            không thể tạo hai local order hoặc hai provider order.
        </div>

        <form
            method="post"
            action="{{ route('commerce.v2.orders.store') }}"
        >
            @csrf
            <button
                type="submit"
                class="lxv2-button lxv2-button--wide"
            >
                Xác nhận đặt hàng COD
            </button>
        </form>

        <form
            method="post"
            action="{{ route('commerce.v2.checkout.quote.requote') }}"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="lxv2-link-button">
                Tạo lại báo giá
            </button>
        </form>
    </section>

    <aside class="lxv2-checkout-card lxv2-checkout-summary">
        <p class="lxv2-eyebrow">Chi tiết quote</p>
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
                        <small>{{ data_get($item, 'sku') }}</small>
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
                <dd>{{ number_format((float) data_get($totals, 'subtotal'), 0, ',', '.') }}₫</dd>
            </div>
            <div>
                <dt>Phí giao hàng</dt>
                <dd>{{ number_format((float) data_get($totals, 'shipping_fee'), 0, ',', '.') }}₫</dd>
            </div>
            <div>
                <dt>Giảm giá</dt>
                <dd>{{ number_format((float) data_get($totals, 'discount_total'), 0, ',', '.') }}₫</dd>
            </div>
            <div class="lxv2-quote-totals__grand">
                <dt>Tổng</dt>
                <dd>{{ number_format((float) data_get($totals, 'grand_total'), 0, ',', '.') }}₫</dd>
            </div>
        </dl>

        <p class="lxv2-next-phase-note">
            Quote hết hạn lúc {{ data_get($quote, 'expires_at') }}.
        </p>
    </aside>
</div>
@endsection
