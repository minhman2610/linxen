@extends('storefront.luxe.layouts.app')

@section('content')
<section class="lx-account-page">

    <header class="lx-account-head">
        <h1>Thông tin cá nhân</h1>
        <p>Cập nhật thông tin từ hệ thống</p>
    </header>

    <form method="POST" class="lx-form">
        @csrf

        <div class="lx-form-group">
            <label>Họ và tên</label>
            <input type="text"
                   name="name"
                   value="{{ old('name', $user['name'] ?? '') }}"
                   required>
        </div>

        <div class="lx-form-group">
            <label>Email</label>
            <input type="email"
                   name="email"
                   value="{{ old('email', $user['email'] ?? '') }}">
        </div>

        <div class="lx-form-group">
            <label>Số điện thoại</label>
            <input type="text"
                   value="{{ $user['phone'] ?? '' }}"
                   disabled>
            <small class="lx-form-hint">
                Số điện thoại được đồng bộ từ hệ thống
            </small>
        </div>

        <button class="lx-btn-primary">
            Lưu thay đổi
        </button>

    </form>

</section>
@endsection
