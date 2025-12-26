@extends('storefront.luxe.layouts.app')

@section('content')

@php
    $cartItems = $cart ?? [];
    $subtotal  = collect($cartItems)->sum(fn($i) => ($i['price'] ?? 0) * ($i['qty'] ?? 0));
@endphp

<section class="lx-cart">

    {{-- =====================
       CART HEADER
    ====================== --}}
    <header class="lx-cart-head">
        <div>
            <h1 class="lx-cart-title">Giỏ hàng của bạn</h1>
            <p class="lx-cart-subtitle">
                {{ count($cartItems) }} sản phẩm đang chờ thanh toán
            </p>
        </div>

        <a href="{{ route('linxen.home') }}" class="lx-cart-continue">
            ← Tiếp tục mua sắm
        </a>
    </header>

    {{-- =====================
       CART BODY
    ====================== --}}
    @if(empty($cartItems))

        {{-- EMPTY STATE --}}
        <div class="lx-cart-empty">
            <div class="lx-cart-empty-icon">🛍️</div>
            <h3>Giỏ hàng trống</h3>
            <p>Khám phá những thiết kế mới nhất từ LIN XÉN</p>

            <a href="{{ route('linxen.home') }}" class="lx-btn-primary">
                Khám phá sản phẩm
            </a>
        </div>

    @else

        <div class="lx-cart-body">

            {{-- =====================
               LEFT: ITEMS LIST
            ====================== --}}
            <div class="lx-cart-list">

                @foreach($cartItems as $sku => $item)
                    <article class="lx-cart-card" data-sku="{{ $sku }}">

                        <div class="lx-cart-thumb">
                            <img
                                src="{{ $item['image'] ?? asset('images/no-image.png') }}"
                                alt="{{ $item['name'] }}"
                            >
                        </div>

                        <div class="lx-cart-info">
                            <h3 class="lx-cart-name">
                                {{ $item['name'] }}
                            </h3>

                            @if(!empty($item['attrs']))
                                <div class="lx-cart-variant">
                                    @foreach($item['attrs'] as $k => $v)
                                        {{ $v }}@if(!$loop->last) · @endif
                                    @endforeach
                                </div>
                            @endif

                            <div class="lx-cart-unit-price">
                                {{ number_format($item['price']) }}₫
                            </div>
                        </div>

                        <div class="lx-cart-qty">
                            <button type="button" onclick="updateQty('{{ $sku }}', -1)">−</button>
                            <span>{{ $item['qty'] }}</span>
                            <button type="button" onclick="updateQty('{{ $sku }}', 1)">+</button>
                        </div>

                        <div class="lx-cart-line-total">
                            {{ number_format($item['price'] * $item['qty']) }}₫
                        </div>

                        <button
                            class="lx-cart-remove"
                            data-sku="{{ $sku }}"
                            aria-label="Xóa sản phẩm">
                            ✕
                        </button>

                    </article>
                @endforeach

            </div>

            {{-- =====================
               RIGHT: SUMMARY
            ====================== --}}
            <aside class="lx-cart-summary">

                <h3 class="lx-summary-title">Tóm tắt đơn hàng</h3>

                <div class="lx-summary-row">
                    <span>Tạm tính</span>
                    <strong>{{ number_format($subtotal) }}₫</strong>
                </div>

                {{-- ❗ Không tính phí vận chuyển tại giỏ hàng --}}
                <div class="lx-summary-row">
                    <span>Vận chuyển</span>
                    <strong class="lx-summary-muted">
                        Tính tại bước thanh toán
                    </strong>
                </div>

                <div class="lx-summary-total">
                    <span>Tạm tính</span>
                    <strong>{{ number_format($subtotal) }}₫</strong>
                </div>

                <a href="{{ route('linxen.checkout') }}"
                   class="lx-btn-primary lx-btn-full">
                    Tiến hành thanh toán
                </a>

                <ul class="lx-summary-note">
                    <li>Phí vận chuyển sẽ được tính sau khi chọn địa chỉ</li>
                    <li>Miễn phí đổi trả trong 7 ngày</li>
                    <li>Giao hàng toàn quốc</li>
                </ul>

            </aside>

        </div>
    @endif

    {{-- =====================
       CONFIRM REMOVE MODAL
    ====================== --}}
    <div class="lx-confirm-overlay" id="lxConfirmOverlay">
        <div class="lx-confirm-box">
            <div class="lx-confirm-title">Xóa sản phẩm?</div>
            <div class="lx-confirm-desc">
                Sản phẩm này sẽ bị xóa khỏi giỏ hàng.<br>
                Hành động này không thể hoàn tác.
            </div>

            <div class="lx-confirm-actions">
                <button class="lx-btn-cancel" id="lxConfirmCancel">Hủy</button>
                <button class="lx-btn-danger" id="lxConfirmOk">Xóa</button>
            </div>
        </div>
    </div>

</section>

@endsection
