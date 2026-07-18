@extends('commerce_v2.layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
{{-- AI_PATCH_LINXEN_LUXE_COMMERCE_THEME_V1_PAGE_SWITCH --}}
@if(
    app(\App\Services\CommerceV2\CommerceThemePreviewService::class)
        ->active(session())
    === \App\Services\CommerceV2\CommerceThemePreviewService::THEME
)
@include('commerce_v2.themes.luxe_commerce_v1.pages.orders')
@else
@php
    $verifiedHistory = (bool) ($verifiedHistory ?? false);
    $guestHistoryNotice = (bool) (
        $guestHistoryNotice ?? ! $verifiedHistory
    );
    $items = collect(
        (array) data_get(
            $orders ?? [],
            'items',
            []
        )
    );
@endphp

<section class="lxv2-page-head">
    <p class="lxv2-eyebrow">Đơn hàng</p>
    <h1>
        {{
            $verifiedHistory
                ? 'Lịch sử đơn hàng'
                : 'Đơn trong phiên hiện tại'
        }}
    </h1>
    <p>
        @if($verifiedHistory)
            Các đơn thuộc customer đã xác minh.
        @else
            Guest checkout chỉ hiển thị đơn được tạo trong phiên
            trình duyệt này.
        @endif
    </p>
</section>

@if(!empty($guestHistoryNotice))
    <section class="lxv2-account-card">
        <h2>Xem toàn bộ lịch sử đơn</h2>
        <p>
            Để bảo vệ thông tin khách hàng, LIN XÉN không tra cứu đơn
            chỉ bằng cách nhập số điện thoại. Anh cần xác minh qua
            magic link hoặc OTP khi kênh SMS được kết nối.
        </p>
    </section>
@endif

@if($items->isEmpty())
    <section class="lxv2-empty">
        <h2>Chưa có đơn hàng trong phạm vi được phép xem</h2>
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
                        {{ (int) data_get(
                            $order,
                            'quantity_total'
                        ) }} sản phẩm
                    </strong>
                    <small>{{ data_get($order, 'created_at') }}</small>
                </div>
                <div>
                    <b>
                        {{ number_format(
                            (float) data_get(
                                $order,
                                'grand_total',
                                data_get(
                                    $order,
                                    'totals.grand_total'
                                )
                            ),
                            0,
                            ',',
                            '.'
                        ) }}₫
                    </b>
                    <span class="lxv2-order-status">
                        {{ data_get($order, 'status') }}
                    </span>
                </div>
            </a>
        @endforeach
    </section>
@endif
@endif
@endsection
