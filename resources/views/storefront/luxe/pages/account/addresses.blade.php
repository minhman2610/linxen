@extends('storefront.luxe.layouts.app')

@section('content')
<section class="lx-account-page">

    <header class="lx-account-head">
        <h1>Địa chỉ nhận hàng</h1>
        <p>Quản lý địa chỉ giao hàng của bạn</p>
    </header>

    {{-- LIST ADDRESSES --}}
    @if(!empty($addresses))
        <div class="lx-address-list">
            @foreach($addresses as $addr)
                <div class="lx-address-card">
                    <strong>
                        {{ $addr['name'] }} – {{ $addr['phone'] }}
                        @if(!empty($addr['is_default']))
                            <span class="lx-address-default">Mặc định</span>
                        @endif
                    </strong>
                    <p>{{ $addr['address'] }}</p>
                </div>
            @endforeach
        </div>
    @else
        <p class="lx-empty-note">
            Bạn chưa có địa chỉ nhận hàng nào
        </p>
    @endif

    <hr>

    {{-- ADD NEW ADDRESS --}}
    <h3 class="lx-section-title">Thêm địa chỉ mới</h3>

    <form method="POST" class="lx-form">
        @csrf

        <div class="lx-form-group">
            <label>Tên người nhận</label>
            <input name="name" required>
        </div>

        <div class="lx-form-group">
            <label>Số điện thoại</label>
            <input name="phone" required>
        </div>

        <div class="lx-form-group">
            <label>Địa chỉ chi tiết</label>
            <input name="address" required>
        </div>

        <button class="lx-btn-primary">
            Thêm địa chỉ
        </button>

    </form>

</section>
@endsection
