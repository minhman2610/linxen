@extends('storefront.luxe.layouts.app')

@section('content')

<section class="lx-checkout-success">

    <div class="lx-success-card">

        {{-- SUCCESS ICON --}}
        <div class="lx-success-icon-wrap">
            <span class="lx-success-icon">
                <svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true">
                    <path fill="currentColor"
                          d="M9.2 16.6L4.9 12.3l1.4-1.4 2.9 2.9 8-8 1.4 1.4z"/>
                </svg>
            </span>
        </div>

        {{-- TITLE --}}
        <h1 class="lx-success-title">
            Đơn hàng đã được ghi nhận
        </h1>

        {{-- MESSAGE --}}
        <p class="lx-success-message">
            Cảm ơn anh/chị đã lựa chọn <strong>LIN XÉN</strong> 💛<br>
            Đơn hàng của anh/chị đang được xử lý cẩn thận và chuẩn bị xác nhận.
        </p>

        {{-- ORDER INFO --}}
        @if(request('order_code'))
            <div class="lx-success-order-code">
                <span>Mã đơn hàng</span>
                <strong>#{{ request('order_code') }}</strong>
            </div>
        @endif

        {{-- NEXT STEPS --}}
        <div class="lx-success-steps">
            <div class="lx-step-item">
                <span class="lx-step-icon">📞</span>
                <p>Đội ngũ LIN XÉN sẽ <strong>liên hệ xác nhận</strong> trong thời gian sớm nhất</p>
            </div>

            <div class="lx-step-item">
                <span class="lx-step-icon">📦</span>
                <p>Sau xác nhận, đơn hàng sẽ được <strong>đóng gói & giao đi</strong></p>
            </div>

            <div class="lx-step-item">
                <span class="lx-step-icon">💬</span>
                <p>Cần hỗ trợ nhanh? Hotline: <strong>1900 xxxx</strong></p>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div class="lx-success-actions">

            <a href="{{ route('linxen.account.orders') }}" class="lx-btn-primary">
                <span class="lx-btn-icon">📋</span>
                <span>Theo dõi đơn hàng</span>
            </a>

            <a href="{{ route('linxen.home') }}" class="lx-btn-secondary">
                <span class="lx-btn-icon">🛍️</span>
                <span>Tiếp tục mua sắm</span>
            </a>

        </div>

        {{-- FOOTER NOTE --}}
        <div class="lx-success-footer">
            <p>
                LIN XÉN luôn trân trọng từng đơn hàng 💐<br>
                Mong rằng anh/chị sẽ hài lòng với trải nghiệm mua sắm.
            </p>
        </div>

    </div>

</section>

@endsection
