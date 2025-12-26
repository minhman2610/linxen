@extends('storefront.luxe.layouts.app')

@section('content')

<div class="lx-account-container">

    @if(!empty($customer))
        {{-- =========================
            ĐÃ ĐĂNG NHẬP
        ========================== --}}

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

    @else
        {{-- =========================
            CHƯA ĐĂNG NHẬP / CHƯA CÓ TÀI KHOẢN
        ========================== --}}

        <div class="lx-empty-state">

            <h2>Tài khoản LIN XÉN</h2>

            <p>
                Đăng nhập hoặc tạo tài khoản để:
                <br>
                • Theo dõi đơn hàng<br>
                • Lưu địa chỉ nhận hàng<br>
                • Nhận ưu đãi thành viên
            </p>

            <div class="lx-account-cta">

                <a href="{{ route('linxen.checkout') }}"
                   class="lx-btn-primary lx-btn-full">
                    Đăng nhập / Đăng ký
                </a>

                <a href="{{ route('linxen.home') }}"
                   class="lx-btn-secondary lx-btn-full">
                    Tiếp tục mua sắm
                </a>

            </div>

        </div>
    @endif

</div>

@endsection
