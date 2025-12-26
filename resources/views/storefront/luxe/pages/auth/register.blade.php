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
            <h2>Đăng ký tài khoản</h2>
            <p>Tạo tài khoản để theo dõi đơn hàng & quyền lợi thành viên</p>
        </div>

        {{-- FORM REGISTER --}}
        <form id="lxRegisterForm"
              class="lx-auth-form"
              method="POST"
              action="{{ route('linxen.register.submit') }}">

            @csrf

            {{-- ❌ ERROR GLOBAL (ERP / LOGIC) --}}
            @if ($errors->has('register'))
                <div class="lx-auth-error lx-auth-error--global">
                    {{ $errors->first('register') }}
                </div>
            @endif

            {{-- PHONE --}}
            <div class="lx-input-group">
                <label for="phone">Số điện thoại</label>
                <input type="tel"
                       name="phone"
                       id="phone"
                       value="{{ old('phone') }}"
                       placeholder="Ví dụ: 0971 234 567"
                       class="{{ $errors->has('phone') ? 'is-error' : '' }}"
                       required>

                @error('phone')
                    <div class="lx-input-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- EMAIL (OPTIONAL) --}}
            <div class="lx-input-group">
                <label for="email">Email (không bắt buộc)</label>
                <input type="email"
                       name="email"
                       id="email"
                       value="{{ old('email') }}"
                       placeholder="Nhận thông báo & ưu đãi"
                       class="{{ $errors->has('email') ? 'is-error' : '' }}">

                @error('email')
                    <div class="lx-input-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- PASSWORD --}}
            <div class="lx-input-group">
                <label for="password">Mật khẩu</label>
                <input type="password"
                       name="password"
                       id="password"
                       placeholder="Tối thiểu 6 ký tự"
                       class="{{ $errors->has('password') ? 'is-error' : '' }}"
                       required>

                @error('password')
                    <div class="lx-input-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- PASSWORD CONFIRM --}}
            <div class="lx-input-group">
                <label for="password_confirmation">Nhập lại mật khẩu</label>
                <input type="password"
                       name="password_confirmation"
                       id="password_confirmation"
                       placeholder="Nhập lại mật khẩu"
                       class="{{ $errors->has('password_confirmation') ? 'is-error' : '' }}"
                       required>

                @error('password_confirmation')
                    <div class="lx-input-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- SUBMIT --}}
            <button type="submit"
                    class="lx-btn-primary lx-btn-full lx-btn-auth lx-btn-auth--register">
                <span class="lx-btn-main">Tạo tài khoản</span>
                <span class="lx-btn-sub">Miễn phí • Chỉ 30 giây</span>
            </button>

        </form>

        {{-- FOOTER --}}
        <div class="lx-auth-footer">
            <p class="lx-auth-register">
                Đã có tài khoản?
                <a href="{{ route('linxen.login') }}">Đăng nhập</a>
            </p>

            <a href="{{ route('linxen.home') }}"
               class="lx-auth-back">
                ← Quay lại trang chủ
            </a>
        </div>

    </div>

</div>

@endsection
