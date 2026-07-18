@php
    $items = collect((array) data_get($cart ?? [], 'items', []));
    $summary = (array) data_get($cart ?? [], 'summary', []);
@endphp

<div class="lxcv1-page" data-lxcv1-page="cart" data-lxcv1-cart>
    <section class="lxcv1-checkout-heading">
        <div>
            <p class="lxcv1-kicker">GIỎ HÀNG</p>
            <h1>Sản phẩm đã chọn</h1>
            <p>Giá và tồn kho được ERP kiểm tra lại mỗi lần mở trang.</p>
        </div>
        <span>{{ (int) data_get($summary, 'quantity_total', 0) }} sản phẩm</span>
    </section>

    @if(!empty($cartError))
        <div class="lxcv1-alert lxcv1-alert--error">{{ $cartError }}</div>
    @endif

    @if($items->isEmpty())
        <section class="lxcv1-empty lxcv1-empty--large">
            <span>0</span>
            <h2>Giỏ hàng đang trống</h2>
            <p>Khám phá thiết kế phù hợp rồi quay lại đây để thanh toán.</p>
            <a class="lxcv1-button lxcv1-button--dark" href="{{ route('commerce.v2.shop') }}">
                Xem sản phẩm
            </a>
        </section>
    @else
        <section class="lxcv1-cart-layout">
            <div class="lxcv1-cart-items">
                @foreach($items as $item)
                    <article class="lxcv1-cart-item">
                        <a class="lxcv1-cart-item__media" href="{{ data_get($item, 'product_url') }}">
                            <img
                                src="{{ data_get($item, 'cover_url') }}"
                                alt="{{ data_get($item, 'product_name') }}"
                                width="240"
                                height="300"
                            >
                        </a>

                        <div class="lxcv1-cart-item__info">
                            <p class="lxcv1-kicker">{{ data_get($item, 'sku') }}</p>
                            <h2>{{ data_get($item, 'product_name') }}</h2>
                            <p>{{ data_get($item, 'color_name') }} · Size {{ data_get($item, 'size') }}</p>

                            @if(!data_get($item, 'valid'))
                                <div class="lxcv1-alert lxcv1-alert--error">
                                    {{ data_get($item, 'message') }}
                                </div>
                            @endif

                            <div class="lxcv1-cart-item__actions">
                                <form
                                    method="post"
                                    action="{{ route(
                                        'commerce.v2.cart.items.update',
                                        ['sellableSkuId' => data_get($item, 'sellable_sku_id')]
                                    ) }}"
                                    data-lxcv1-quantity-form
                                >
                                    @csrf
                                    @method('PATCH')
                                    <div class="lxcv1-cart-quantity">
                                        <button type="button" data-lxcv1-qty-step="-1" aria-label="Giảm số lượng">−</button>
                                        <input
                                            type="number"
                                            name="quantity"
                                            min="0"
                                            max="20"
                                            value="{{ (int) data_get($item, 'quantity', 1) }}"
                                            data-lxcv1-qty-input
                                        >
                                        <button type="button" data-lxcv1-qty-step="1" aria-label="Tăng số lượng">+</button>
                                    </div>
                                    <button class="lxcv1-text-button" type="submit">Cập nhật</button>
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
                                    <button class="lxcv1-text-button lxcv1-text-button--danger" type="submit">Xóa</button>
                                </form>
                            </div>
                        </div>

                        <strong class="lxcv1-cart-item__total">
                            {{ number_format((float) data_get($item, 'line_total', 0), 0, ',', '.') }}₫
                        </strong>
                    </article>
                @endforeach
            </div>

            <aside class="lxcv1-cart-summary">
                <p class="lxcv1-kicker">TÓM TẮT</p>
                <h2>Đơn hàng của bạn</h2>

                <dl>
                    <div>
                        <dt>Số lượng</dt>
                        <dd>{{ (int) data_get($summary, 'quantity_total', 0) }} sản phẩm</dd>
                    </div>
                    <div>
                        <dt>Tạm tính</dt>
                        <dd>{{ number_format((float) data_get($summary, 'subtotal', 0), 0, ',', '.') }}₫</dd>
                    </div>
                    <div class="is-grand">
                        <dt>Tổng hiện tại</dt>
                        <dd>{{ number_format((float) data_get($summary, 'subtotal', 0), 0, ',', '.') }}₫</dd>
                    </div>
                </dl>

                <p>Phí giao hàng và tổng COD được ERP xác nhận ở bước thanh toán.</p>

                @if(data_get($summary, 'valid') === true)
                    <a class="lxcv1-button lxcv1-button--dark lxcv1-button--wide" href="{{ route('commerce.v2.checkout.index') }}">
                        Tiến hành thanh toán
                    </a>
                @endif

                <form method="post" action="{{ route('commerce.v2.cart.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button class="lxcv1-text-button lxcv1-text-button--danger" type="submit">
                        Xóa toàn bộ giỏ
                    </button>
                </form>
            </aside>
        </section>

        @if(data_get($summary, 'valid') === true)
            <div class="lxcv1-cart-mobile-cta">
                <div>
                    <small>Tạm tính</small>
                    <strong>{{ number_format((float) data_get($summary, 'subtotal', 0), 0, ',', '.') }}₫</strong>
                </div>
                <a href="{{ route('commerce.v2.checkout.index') }}">Thanh toán</a>
            </div>
        @endif
    @endif
</div>
