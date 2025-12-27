@extends('storefront.luxe.layouts.app')

@section('content')
<section class="lx-account-page lx-profile-page">

    {{-- HEADER --}}
    <header class="lx-account-head">
        <h1>Thông tin cá nhân</h1>
        <p>Cập nhật thông tin tài khoản của bạn</p>
    </header>

    {{-- SUCCESS / ERROR --}}
    @if(session('success'))
        <div class="lx-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="lx-alert-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- CARD --}}
    <div class="lx-profile-card">

        <form method="POST" class="lx-profile-form">
            @csrf

            {{-- NAME --}}
            <div class="lx-field">
                <label>Họ và tên</label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $user['name'] ?? '') }}"
                       placeholder="Nhập họ và tên"
                       required>
            </div>

            {{-- EMAIL --}}
            <div class="lx-field">
                <label>Email</label>
                <input type="email"
                       name="email"
                       value="{{ old('email', $user['email'] ?? '') }}"
                       placeholder="email@example.com">
            </div>

            {{-- PHONE (READ ONLY) --}}
            <div class="lx-field lx-field-readonly">
                <label>Số điện thoại</label>
                <input type="text"
                       value="{{ $user['phone'] ?? '' }}"
                       disabled>
                <small class="lx-field-hint">
                    Số điện thoại được đồng bộ từ hệ thống và không thể thay đổi
                </small>
            </div>

            {{-- ACTION --}}
            <div class="lx-form-actions">
                <button class="lx-btn lx-btn-primary">
                    Lưu thay đổi
                </button>
            </div>

        </form>

    </div>

</section>
@endsection
