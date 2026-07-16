@extends('commerce_v2.layouts.app')

@section('content')
<section class="lxv2-page-head">
    <p class="lxv2-eyebrow">Giỏ hàng</p>
    <h1>Sản phẩm đã chọn</h1>
    <p>Giá và tồn kho được ERP kiểm tra lại mỗi lần mở trang.</p>
</section>

@if(!empty($cartError))
    <div class="lxv2-alert lxv2-alert--error">{{ $cartError }}</div>
@endif

@php
    $items = collect((array) data_get($cart, 'items', []));
    $summary = (array) data_get($cart, 'summary', []);
@endphp

@if($items->isEmpty())
    <section class="lxv2-empty">
        <h2>Giỏ hàng đang trống</h2>
        <a class="lxv2-button" href="{{ route('commerce.v2.shop') }}">
            Xem sản phẩm
        </a>
    </section>
@else
    <section class="lxv2-cart">
        <div class="lxv2-cart__items">
            @foreach($items as $item)
                <article class="lxv2-cart-item">
                    <a href="{{ data_get($item, 'product_url') }}">
                        <img
                            src="{{ data_get($item, 'cover_url') }}"
                            alt="{{ data_get($item, 'product_name') }}"
                            width="120"
                            height="150"
                        >
                    </a>

                    <div>
                        <p class="lxv2-eyebrow">{{ data_get($item, 'sku') }}</p>
                        <h2>{{ data_get($item, 'product_name') }}</h2>
                        <p>
                            {{ data_get($item, 'color_name') }}
                            · Size {{ data_get($item, 'size') }}
                        </p>

                        @if(!data_get($item, 'valid'))
                            <div class="lxv2-alert lxv2-alert--error">
                                {{ data_get($item, 'message') }}
                            </div>
                        @endif

                        <form
                            method="post"
                            action="{{ route(
                                'commerce.v2.cart.items.update',
                                ['sellableSkuId' => data_get($item, 'sellable_sku_id')]
                            ) }}"
                        >
                            @csrf
                            @method('PATCH')
                            <input
                                type="number"
                                name="quantity"
                                min="0"
                                max="20"
                                value="{{ (int) data_get($item, 'quantity', 1) }}"
                            >
                            <button type="submit">Cập nhật</button>
                        </form>

                        <form
                            method="post"
                            action="{{ route(
                                'commerce.v2.cart.items.destroy',
                                ['sellableSkuId' => data_get($item, 'sellable_sku_id')]
                            ) }}"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit">Xóa</button>
                        </form>
                    </div>

                    <strong>
                        {{ number_format(
                            (float) data_get($item, 'line_total', 0),
                            0,
                            ',',
                            '.'
                        ) }}₫
                    </strong>
                </article>
            @endforeach
        </div>

        <aside class="lxv2-cart-summary">
            <h2>Tạm tính</h2>
            <p>
                {{ (int) data_get($summary, 'quantity_total', 0) }}
                sản phẩm
            </p>
            <strong>
                {{ number_format(
                    (float) data_get($summary, 'subtotal', 0),
                    0,
                    ',',
                    '.'
                ) }}₫
            </strong>
            <p>Checkout và phí giao hàng sẽ được mở ở bundle tiếp theo.</p>

            <form method="post" action="{{ route('commerce.v2.cart.clear') }}">
                @csrf
                @method('DELETE')
                <button type="submit">Xóa toàn bộ giỏ</button>
            </form>
        </aside>
    </section>
@endif
@endsection
