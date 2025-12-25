@extends('storefront.luxe.layouts.app')

@section('content')

<section class="lx-checkout-success">

    <div class="lx-success-card">

        {{-- ICON --}}
        <div class="lx-success-icon">
            ✓
        </div>

        {{-- TITLE --}}
        <h1 class="lx-success-title">
            Đặt hàng thành công
        </h1>

        {{-- MESSAGE --}}
        <p class="lx-success-message">
            Cảm ơn anh/chị đã tin tưởng và mua sắm tại <strong>LIN XÉN</strong>.<br>
            Đơn hàng của anh/chị đã được ghi nhận và đang được xử lý.
        </p>

        {{-- NOTE --}}
        <div class="lx-success-note">
            <p>
                📞 Đội ngũ LIN XÉN sẽ <strong>liên hệ xác nhận đơn hàng</strong>
                trong thời gian sớm nhất.
            </p>
            <p>
                Trong trường hợp cần hỗ trợ gấp, vui lòng liên hệ hotline:
                <strong>1900 xxxx</strong>
            </p>
        </div>

        {{-- ACTIONS --}}
        <div class="lx-success-actions">
            <a href="{{ route('linxen.home') }}" class="lx-btn-primary">
                Tiếp tục mua sắm
            </a>

            <a href="{{ route('linxen.account.orders') }}" class="lx-btn-secondary">
                Xem đơn hàng của tôi
            </a>
        </div>

    </div>

</section>

@endsection
