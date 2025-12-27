@extends('storefront.luxe.layouts.app')

@section('content')
<section class="lx-account-page lx-profile-page">

    <div class="lx-account-container">

        {{-- HEADER --}}
        <header class="lx-account-head">
            <h1>Thông tin cá nhân</h1>
            <p>Cập nhật thông tin tài khoản của bạn</p>
        </header>

        {{-- SUCCESS --}}
        @if(session('success'))
            <div class="lx-alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- WARNING --}}
        @if(!empty($warning))
            <div class="lx-alert-error">
                {{ $warning }}
            </div>
        @endif

        {{-- ERRORS --}}
        @if ($errors->any())
            <div class="lx-alert-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- PROFILE CARD --}}
        <div class="lx-profile-card">

            <form method="POST"
                  action="{{ route('linxen.account.profile.update') }}"
                  class="lx-profile-form">
                @csrf

                {{-- NAME --}}
                <div class="lx-field">
                    <label>Họ và tên</label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', data_get($user, 'name')) }}"
                           placeholder="Nhập họ và tên"
                           required>
                </div>

                {{-- EMAIL --}}
                <div class="lx-field">
                    <label>Email</label>
                    <input type="email"
                           name="email"
                           value="{{ old('email', data_get($user, 'email')) }}"
                           placeholder="email@example.com">
                </div>

                {{-- PHONE (READ ONLY) --}}
                <div class="lx-field lx-field-readonly">
                    <label>Số điện thoại</label>
                    <input type="text"
                           value="{{ data_get($user, 'phone') }}"
                           disabled>
                    <small class="lx-field-hint">
                        Số điện thoại được đồng bộ từ hệ thống và không thể thay đổi
                    </small>
                </div>

                {{-- ACTION --}}
                <div class="lx-form-actions">
                    <button type="submit" class="lx-btn lx-btn-primary">
                        Lưu thay đổi
                    </button>
                </div>

            </form>

        </div>

    </div>

</section>
@endsection
