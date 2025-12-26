@extends('storefront.luxe.layouts.app')

@section('content')

<div class="lx-auth-container">

    <div class="lx-auth-box lx-auth-box--premium">

        {{-- BRAND --}}
        <div class="lx-auth-brand">
            <div class="lx-auth-logo">LIN XÉN</div>
            <div class="lx-auth-tagline">Thời trang nữ cao cấp</div>
        </div>

        {{-- HEADER --}}
        <div class="lx-auth-header">
            <h2>Đăng nhập</h2>
            <p>Truy cập tài khoản để theo dõi đơn hàng & quyền lợi thành viên</p>
        </div>

        {{-- FORM LOGIN --}}
        <form id="lxLoginForm"
              class="lx-auth-form"
              method="POST"
              action="{{ route('linxen.login.submit') }}">

            @csrf

            {{-- PHONE --}}
            <div class="lx-input-group">
                <label for="phone">Số điện thoại</label>
                <input type="tel"
                       name="phone"
                       id="phone"
                       value="{{ old('phone') }}"
                       placeholder="Ví dụ: 0971 234 567"
                       required>
            </div>

            {{-- PASSWORD --}}
            <div class="lx-input-group">
                <label for="password">Mật khẩu</label>
                <input type="password"
                       name="password"
                       id="password"
                       placeholder="Nhập mật khẩu"
                       required>
            </div>

            {{-- ERROR --}}
            @if ($errors->has('login'))
                <div class="lx-auth-error">
                    {{ $errors->first('login') }}
                </div>
            @endif

            {{-- SUBMIT --}}
            <button type="submit"
                    class="lx-btn-primary lx-btn-full lx-btn-auth">
                <span class="lx-btn-main">Đăng nhập</span>
            </button>

        </form>

        {{-- FOOTER --}}
        <div class="lx-auth-footer">

            <p class="lx-auth-register">
                Chưa có tài khoản?
                <a href="{{ route('linxen.checkout') }}">
                    Đăng ký nhanh khi mua hàng
                </a>
            </p>

            <a href="{{ route('linxen.home') }}"
               class="lx-auth-back">
                ← Quay lại trang chủ
            </a>

        </div>

    </div>

</div>

@endsection
