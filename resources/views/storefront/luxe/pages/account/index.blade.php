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

            {{-- ADDRESSES (DEMO / FUTURE) --}}
            <a href="#"
               class="lx-account-card"
               title="Sẽ triển khai">
                <div class="lx-account-card-left">
                    <span class="lx-account-icon">📍</span>
                    <div>
                        <strong>Địa chỉ nhận hàng</strong>
                        <span>Quản lý địa chỉ giao hàng</span>
                    </div>
                </div>
                <span class="lx-account-arrow">›</span>
            </a>

            {{-- PROFILE (DEMO / FUTURE) --}}
            <a href="#"
               class="lx-account-card"
               title="Sẽ triển khai">
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
            <div class="lx-account-card lx-account-card--danger">

                <div class="lx-account-card-left">
                    <span class="lx-account-icon">🚪</span>
                    <div>
                        <strong>Đăng xuất</strong>
                        <span>Thoát khỏi tài khoản hiện tại</span>
                    </div>
                </div>

                <form method="POST"
                      action="{{ route('linxen.logout') }}"
                      onsubmit="return confirm('Bạn chắc chắn muốn đăng xuất?')">
                    @csrf
                    <button type="submit"
                            class="lx-account-logout-btn">
                        Đăng xuất
                    </button>
                </form>

            </div>

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
            CHƯA ĐĂNG NHẬP
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

                {{-- LOGIN --}}
                <a href="{{ route('linxen.login') }}"
                   class="lx-btn-primary lx-btn-full">
                    Đăng nhập
                </a>

                {{-- REGISTER --}}
                <a href="{{ route('linxen.register') }}"
                   class="lx-btn-secondary lx-btn-full">
                    Đăng ký
                </a>

                <a href="{{ route('linxen.home') }}"
                   class="lx-btn-link">
                    Tiếp tục mua sắm →
                </a>

            </div>

        </div>
    @endif

</div>

@endsection
