@extends('storefront.luxe.layouts.app')

@section('content')
<section class="lx-address-page-v2">

    {{-- HEADER --}}
    <header class="lx-address-head">
        <h1>Địa chỉ nhận hàng</h1>
        <p>Quản lý và sử dụng địa chỉ cho giao hàng</p>
    </header>

    {{-- ADDRESS LIST --}}
    <div class="lx-address-block">

        @if(!empty($addresses))
            @foreach($addresses as $addr)
                <div class="lx-address-item {{ !empty($addr['is_default']) ? 'is-default' : '' }}">

                    <div class="lx-address-info">
                        <div class="lx-address-line-1">
                            <span class="lx-address-name">{{ $addr['name'] }}</span>
                            <span class="lx-address-phone">{{ $addr['phone'] }}</span>

                            @if(!empty($addr['is_default']))
                                <span class="lx-address-tag">Mặc định</span>
                            @endif
                        </div>

                        <div class="lx-address-line-2">
                            {{ $addr['address'] }}
                        </div>
                    </div>

                </div>
            @endforeach
        @else
            <div class="lx-address-empty-v2">
                Bạn chưa có địa chỉ nhận hàng nào.
            </div>
        @endif

    </div>

    {{-- FORM --}}
    <div class="lx-address-form-box">
        <h2>Thêm địa chỉ mới</h2>

        <form method="POST" class="lx-address-form">
            @csrf

            <div class="lx-field">
                <label>Tên người nhận</label>
                <input name="name" placeholder="Nguyễn Văn A" required>
            </div>

            <div class="lx-field">
                <label>Số điện thoại</label>
                <input name="phone" placeholder="097xxxxxxx" required>
            </div>

            <div class="lx-field">
                <label>Địa chỉ chi tiết</label>
                <textarea name="address"
                          rows="3"
                          placeholder="Số nhà, đường, phường/xã, quận/huyện"
                          required></textarea>
            </div>

            <button class="lx-btn-primary lx-btn-block">
                Thêm địa chỉ
            </button>
        </form>
    </div>

</section>
@endsection
