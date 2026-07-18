@extends('commerce_v2.layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
{{-- AI_PATCH_LINXEN_LUXE_COMMERCE_THEME_V1_PAGE_SWITCH --}}
@if(
    app(\App\Services\CommerceV2\CommerceThemePreviewService::class)
        ->active(session())
    === \App\Services\CommerceV2\CommerceThemePreviewService::THEME
)
@include('commerce_v2.themes.luxe_commerce_v1.pages.order')
@else
@php
    $items = collect((array) data_get($order, 'items', []));
    $totals = (array) data_get($order, 'totals', []);
    $address = (array) data_get($order, 'address', []);
    $status = (string) data_get($order, 'status');
    $providerStatus = (string) data_get(
        $order,
        'provider_status'
    );
    $customerStatus = match (true) {
        $status === 'canceled' => 'Đã hủy',
        $providerStatus === 'created' => 'Đã chuyển sang xử lý',
        $providerStatus === 'unknown' => 'Đang xác minh',
        default => 'Đã tiếp nhận',
    };
@endphp

<section class="lxv2-page-head">
    <p class="lxv2-eyebrow">
        {{ data_get($order, 'order_code') }}
    </p>
    <h1>Chi tiết đơn hàng</h1>
    <p>
        Trạng thái:
        <strong>{{ $customerStatus }}</strong>
    </p>
</section>

<div class="lxv2-checkout-grid">
    <section class="lxv2-checkout-card">
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

        @if($providerStatus === 'unknown')
            <div class="lxv2-alert">
                Hệ thống đang xác minh trạng thái xử lý.
                LIN XÉN không tạo lại đơn để tránh trùng.
            </div>
        @elseif($providerStatus === 'created')
            <div class="lxv2-alert lxv2-alert--success">
                Đơn đã được chuyển sang hệ thống xử lý bán hàng.
            </div>
        @else
            <div class="lxv2-alert">
                LIN XÉN đã tiếp nhận đơn và đang chuẩn bị bước xử lý tiếp theo.
            </div>
        @endif

        @if(data_get($order, 'can_cancel'))
            <form
                method="post"
                action="{{ route(
                    'commerce.v2.orders.cancel',
                    ['order' => data_get($order, 'order_id')]
                ) }}"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="lxv2-link-button">
                    Hủy đơn hàng
                </button>
            </form>
        @endif
    </section>

    <aside class="lxv2-checkout-card lxv2-checkout-summary">
        <div class="lxv2-checkout-lines">
            @foreach($items as $item)
                <article>
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
@endif
@endsection
