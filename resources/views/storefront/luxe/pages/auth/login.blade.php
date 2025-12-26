@extends('storefront.luxe.layouts.app')

@section('content')

<div class="lx-auth-container">

    <div class="lx-auth-box">

        {{-- HEADER --}}
        <div class="lx-auth-header">
            <h2>Đăng nhập</h2>
            <p>Đăng nhập để theo dõi đơn hàng và quyền lợi thành viên</p>
        </div>

        {{-- FORM --}}
        <form id="lxLoginForm" class="lx-auth-form">

            @csrf

            {{-- PHONE --}}
            <div class="lx-input-group">
                <label>Số điện thoại</label>
                <input type="tel"
                       name="phone"
                       id="lxLoginPhone"
                       placeholder="Nhập số điện thoại"
                       required>
            </div>

            {{-- PASSWORD --}}
            <div class="lx-input-group">
                <label>Mật khẩu</label>
                <input type="password"
                       name="password"
                       id="lxLoginPassword"
                       placeholder="Nhập mật khẩu"
                       required>
            </div>

            {{-- ERROR --}}
            <div id="lxLoginError" class="lx-auth-error" style="display:none"></div>

            {{-- SUBMIT --}}
            <button type="submit" class="lx-btn-primary lx-btn-full lx-btn-auth">
                Đăng nhập
            </button>

        </form>

        {{-- FOOTER --}}
        <div class="lx-auth-footer">
            <p>
                Chưa có tài khoản?
                <a href="{{ route('linxen.checkout') }}">Đăng ký nhanh khi mua hàng</a>
            </p>

            <a href="{{ route('linxen.home') }}" class="lx-auth-back">
                ← Quay lại trang chủ
            </a>
        </div>

    </div>

</div>

@endsection
