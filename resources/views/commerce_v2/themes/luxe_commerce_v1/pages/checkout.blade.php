@php
    $items = collect((array) data_get($cart ?? [], 'items', []));
    $summary = (array) data_get($cart ?? [], 'summary', []);
    $shipping = (array) data_get($capabilities ?? [], 'shipping', []);
    $subtotal = (float) data_get($summary, 'subtotal', 0);
    $shippingFee = (float) data_get($shipping, 'fee_amount', 0);
    $freeThreshold = data_get($shipping, 'free_shipping_threshold');
    $effectiveShippingFee = (
        $freeThreshold !== null
        && $subtotal >= (float) $freeThreshold
    ) ? 0 : $shippingFee;
    $estimatedTotal = $subtotal + $effectiveShippingFee;
    $orderAcceptEnabled = data_get($capabilities, 'order_accept_enabled') === true;
    $guestCheckoutEnabled = data_get($capabilities, 'guest_checkout_enabled') === true;
    $canSubmit = $orderAcceptEnabled
        && ($isVerifiedCustomer || $guestCheckoutEnabled);
@endphp

<div class="lxcv1-page" data-lxcv1-page="checkout">
    <section class="lxcv1-checkout-heading">
        <div>
            <p class="lxcv1-kicker">THANH TOÁN AN TOÀN</p>
            <h1>Hoàn tất đơn hàng</h1>
            <p>Giá, tồn kho và phí giao hàng được kiểm tra lại trước khi ghi nhận đơn.</p>
        </div>
        <ol class="lxcv1-checkout-steps" aria-label="Tiến trình thanh toán">
            <li class="is-done">Giỏ hàng</li>
            <li class="is-active">Giao hàng</li>
            <li>Xác nhận</li>
        </ol>
    </section>

    <div class="lxcv1-checkout-layout">
        <form
            method="post"
            action="{{ route('commerce.v2.checkout.place_order') }}"
            class="lxcv1-checkout-form"
            data-lxv2-one-page-checkout
            data-lxcv1-checkout
            data-wards-url-template="{{ route(
                'commerce.v2.checkout.wards',
                ['location' => '__LOCATION__']
            ) }}"
        >
            @csrf

            <section class="lxcv1-checkout-card">
                <header>
                    <span>01</span>
                    <div>
                        <h2>Thông tin nhận hàng</h2>
                        <p>Nhập thông tin để LIN XÉN giao đúng người, đúng địa chỉ.</p>
                    </div>
                </header>

                <div class="lxcv1-checkout-fields">
                    <label class="is-wide">
                        <span>Họ tên người nhận</span>
                        <input
                            name="receiver_name"
                            required
                            maxlength="191"
                            autocomplete="name"
                            value="{{ data_get($identity, 'receiver_name') }}"
                            placeholder="Nguyễn Văn A"
                        >
                    </label>

                    <label>
                        <span>Số điện thoại</span>
                        <input
                            name="phone"
                            required
                            maxlength="20"
                            inputmode="tel"
                            autocomplete="tel"
                            value="{{ data_get($identity, 'phone') }}"
                            placeholder="09xxxxxxxx"
                        >
                    </label>

                    <label>
                        <span>Email <small>không bắt buộc</small></span>
                        <input
                            type="email"
                            name="email"
                            maxlength="191"
                            autocomplete="email"
                            value="{{ data_get($identity, 'email') }}"
                            placeholder="email@example.com"
                        >
                    </label>

                    <label>
                        <span>Tỉnh / Thành phố</span>
                        <select name="location_id" required data-lxv2-checkout-location>
                            <option value="">Chọn Tỉnh / Thành phố</option>
                            @foreach($locations as $location)
                                <option
                                    value="{{ data_get($location, 'id') }}"
                                    @selected(
                                        (string) data_get($identity, 'location_id')
                                        === (string) data_get($location, 'id')
                                    )
                                >
                                    {{ data_get($location, 'name') }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Phường / Xã</span>
                        <select
                            name="ward_id"
                            required
                            data-lxv2-checkout-ward
                            data-selected-ward="{{ data_get($identity, 'ward_id') }}"
                        >
                            @if(data_get($identity, 'ward_id'))
                                <option value="{{ data_get($identity, 'ward_id') }}" selected>
                                    {{ data_get($identity, 'ward_name') ?: 'Phường / Xã đã lưu' }}
                                </option>
                            @else
                                <option value="">Chọn Tỉnh / Thành phố trước</option>
                            @endif
                        </select>
                    </label>

                    <label class="is-wide">
                        <span>Số nhà, tên đường</span>
                        <input
                            name="street"
                            required
                            maxlength="255"
                            autocomplete="street-address"
                            value="{{ data_get($identity, 'street') }}"
                            placeholder="Số nhà, tên đường, tòa nhà..."
                        >
                    </label>
                </div>
            </section>

            <section class="lxcv1-checkout-card">
                <header>
                    <span>02</span>
                    <div>
                        <h2>Giao hàng</h2>
                        <p>Phí cuối cùng được ERP xác nhận khi đặt hàng.</p>
                    </div>
                </header>

                <label class="lxcv1-choice">
                    <input type="radio" name="shipping_method" value="standard" checked>
                    <span>
                        <strong>{{ data_get($shipping, 'name', 'Giao hàng tiêu chuẩn') }}</strong>
                        <small>
                            {{
                                $effectiveShippingFee > 0
                                    ? number_format($effectiveShippingFee, 0, ',', '.') . '₫'
                                    : 'Miễn phí'
                            }}
                        </small>
                    </span>
                    <i>✓</i>
                </label>
            </section>

            <section class="lxcv1-checkout-card">
                <header>
                    <span>03</span>
                    <div>
                        <h2>Thanh toán</h2>
                        <p>Thanh toán khi nhận hàng.</p>
                    </div>
                </header>

                <label class="lxcv1-choice">
                    <input type="radio" name="payment_method" value="cod" checked>
                    <span>
                        <strong>Thanh toán COD</strong>
                        <small>Thanh toán cho đơn vị giao hàng khi nhận sản phẩm.</small>
                    </span>
                    <i>COD</i>
                </label>
            </section>

            @if(!$orderAcceptEnabled)
                <div class="lxcv1-alert">
                    Website đang ở chế độ UAT. Form đã sẵn sàng nhưng hệ thống chưa mở nhận đơn thật.
                </div>
            @elseif(!$isVerifiedCustomer && !$guestCheckoutEnabled)
                <div class="lxcv1-alert lxcv1-alert--error">
                    Guest checkout chưa được bật. Bạn cần đăng nhập bằng magic link để đặt hàng.
                </div>
            @elseif(!$isVerifiedCustomer)
                <div class="lxcv1-account-note">
                    Bạn đang mua không cần tài khoản. Biên nhận được giữ trong phiên trình duyệt hiện tại.
                </div>
            @endif

            <button
                type="submit"
                class="lxcv1-place-order"
                @disabled(!$canSubmit)
            >
                <span>{{ $canSubmit ? 'Đặt hàng COD' : 'Chưa mở nhận đơn' }}</span>
                <strong>{{ number_format($estimatedTotal, 0, ',', '.') }}₫</strong>
            </button>

            <p class="lxcv1-checkout-legal">
                Khi bấm Đặt hàng, bạn xác nhận thông tin nhận hàng là đúng. Hệ thống chống tạo đơn trùng bằng idempotency phía server.
            </p>
        </form>

        <aside class="lxcv1-order-summary">
            <header>
                <div>
                    <p class="lxcv1-kicker">ĐƠN HÀNG</p>
                    <h2>{{ (int) data_get($summary, 'quantity_total', 0) }} sản phẩm</h2>
                </div>
                <a href="{{ route('commerce.v2.cart.index') }}">Sửa giỏ</a>
            </header>

            <div class="lxcv1-order-lines">
                @foreach($items as $item)
                    <article>
                        <img
                            src="{{ data_get($item, 'cover_url') }}"
                            alt=""
                            width="144"
                            height="180"
                        >
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
                <div>
                    <dt>Tạm tính</dt>
                    <dd>{{ number_format($subtotal, 0, ',', '.') }}₫</dd>
                </div>
                <div>
                    <dt>Phí giao hàng dự kiến</dt>
                    <dd>{{ number_format($effectiveShippingFee, 0, ',', '.') }}₫</dd>
                </div>
                <div class="is-grand">
                    <dt>Tổng dự kiến</dt>
                    <dd>{{ number_format($estimatedTotal, 0, ',', '.') }}₫</dd>
                </div>
            </dl>

            <p>Tổng chính thức được ERP kiểm tra lại ngay khi bạn bấm Đặt hàng.</p>
        </aside>
    </div>
</div>
