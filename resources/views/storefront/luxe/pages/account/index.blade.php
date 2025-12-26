@extends('storefront.luxe.layouts.app')

@section('content')

<div class="lx-account-container">

    {{-- HEADER --}}
    <div class="lx-account-header">
        <h2>Tài khoản của tôi</h2>
        <p>
            Xin chào
            <strong>{{ $customer->name ?? $customer->phone }}</strong>
        </p>
    </div>

    {{-- ACCOUNT MENU --}}
    <div class="lx-account-menu">

        {{-- ORDERS --}}
        <a href="{{ route('linxen.account.orders') }}"
           class="lx-account-card">
            <div class="lx-account-card-left">
                <span class="lx-account-icon">📦</span>
                <div>
                    <strong>Đơn hàng</strong>
                    <span>Theo dõi trạng thái đơn đã mua</span>
                </div>
            </div>
            <span class="lx-account-arrow">›</span>
        </a>

        {{-- ADDRESSES --}}
        <a href="{{ route('linxen.account.index') }}#addresses"
           class="lx-account-card">
            <div class="lx-account-card-left">
                <span class="lx-account-icon">📍</span>
                <div>
                    <strong>Địa chỉ nhận hàng</strong>
                    <span>Quản lý địa chỉ giao hàng</span>
                </div>
            </div>
            <span class="lx-account-arrow">›</span>
        </a>

        {{-- PROFILE --}}
        <a href="{{ route('linxen.account.index') }}#profile"
           class="lx-account-card">
            <div class="lx-account-card-left">
                <span class="lx-account-icon">👤</span>
                <div>
                    <strong>Thông tin cá nhân</strong>
                    <span>Tên, email, thông tin liên hệ</span>
                </div>
            </div>
            <span class="lx-account-arrow">›</span>
        </a>

        {{-- SECURITY --}}
        <a href="{{ route('linxen.account.index') }}#security"
           class="lx-account-card">
            <div class="lx-account-card-left">
                <span class="lx-account-icon">🔒</span>
                <div>
                    <strong>Bảo mật</strong>
                    <span>Đổi mật khẩu, đăng xuất</span>
                </div>
            </div>
            <span class="lx-account-arrow">›</span>
        </a>

        {{-- SUPPORT --}}
        <a href="https://zalo.me/your-zalo-id"
           target="_blank"
           class="lx-account-card">
            <div class="lx-account-card-left">
                <span class="lx-account-icon">💬</span>
                <div>
                    <strong>Hỗ trợ</strong>
                    <span>Liên hệ CSKH LIN XÉN</span>
                </div>
            </div>
            <span class="lx-account-arrow">›</span>
        </a>

    </div>

</div>

@endsection
