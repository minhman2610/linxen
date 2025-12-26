@extends('storefront.luxe.layouts.app')

@section('content')
<section class="lx-account-page lx-address-page">

    {{-- HEADER --}}
    <header class="lx-account-head">
        <h1>Địa chỉ nhận hàng</h1>
        <p>Quản lý và cập nhật địa chỉ giao hàng của bạn</p>
    </header>

    {{-- ADDRESS LIST --}}
    <div class="lx-address-section">

        @if(!empty($addresses))
            <div class="lx-address-list">
                @foreach($addresses as $addr)
                    <div class="lx-address-card {{ !empty($addr['is_default']) ? 'is-default' : '' }}">
                        <div class="lx-address-main">
                            <div class="lx-address-name">
                                <strong>{{ $addr['name'] }}</strong>
                                <span class="lx-address-phone">{{ $addr['phone'] }}</span>

                                @if(!empty($addr['is_default']))
                                    <span class="lx-address-badge">Mặc định</span>
                                @endif
                            </div>

                            <p class="lx-address-text">
                                {{ $addr['address'] }}
                            </p>
                        </div>

                        {{-- future actions --}}
                        <div class="lx-address-actions">
                            <span class="lx-address-hint">
                                Dùng cho giao hàng
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="lx-address-empty">
                <p>Bạn chưa có địa chỉ nhận hàng nào.</p>
            </div>
        @endif

    </div>

    {{-- DIVIDER --}}
    <hr class="lx-divider">

    {{-- ADD NEW ADDRESS --}}
    <div class="lx-address-form-wrap">
        <h3 class="lx-section-title">Thêm địa chỉ mới</h3>

        <form method="POST" class="lx-form lx-address-form">
            @csrf

            <div class="lx-form-group">
                <label>Tên người nhận</label>
                <input name="name" placeholder="Nguyễn Văn A" required>
            </div>

            <div class="lx-form-group">
                <label>Số điện thoại</label>
                <input name="phone" placeholder="097xxxxxxx" required>
            </div>

            <div class="lx-form-group">
                <label>Địa chỉ chi tiết</label>
                <input name="address" placeholder="Số nhà, đường, phường/xã, quận/huyện…" required>
            </div>

            <button class="lx-btn-primary lx-btn-wide">
                Thêm địa chỉ
            </button>
        </form>
    </div>

</section>
@endsection
