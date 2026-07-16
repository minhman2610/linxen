@extends('commerce_v2.layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
<section class="lxv2-page-head">
    <p class="lxv2-eyebrow">Tài khoản</p>
    <h1>Tài khoản LIN XÉN</h1>
</section>

@if(empty($account))
    <section class="lxv2-account-card">
        @if(!empty($accountError))
            <div class="lxv2-alert lxv2-alert--error">
                {{ $accountError }}
            </div>
        @endif

        <h2>Đăng nhập bằng magic link</h2>
        <p>
            Magic link dùng một lần được ERP tạo cho customer đã tồn tại.
            Link hết hạn sau 15 phút và token chỉ lưu ở session server.
        </p>
        <p>
            Bundle này chưa tự gửi SMS/email vì provider chưa có contract chính thức.
        </p>
    </section>
@else
    @php
        $customer = (array) data_get($account, 'customer', []);
        $addresses = collect((array) data_get($account, 'addresses', []));
    @endphp

    <section class="lxv2-account-card">
        <h2>Thông tin khách hàng</h2>
        <p><strong>ID:</strong> {{ data_get($customer, 'id') }}</p>
        <p><strong>Số điện thoại:</strong> {{ data_get($customer, 'phone') }}</p>
        <p><strong>Email:</strong> {{ data_get($customer, 'email') ?: '—' }}</p>

        <form method="post" action="{{ route('commerce.v2.account.logout') }}">
            @csrf
            @method('DELETE')
            <button type="submit">Đăng xuất</button>
        </form>
    </section>

    <section class="lxv2-address-list">
        <h2>Địa chỉ nhận hàng</h2>

        @forelse($addresses as $address)
            <article class="lxv2-address-card">
                <strong>{{ data_get($address, 'receiver_name') }}</strong>
                @if(data_get($address, 'is_default'))
                    <span>Mặc định</span>
                @endif
                <p>{{ data_get($address, 'receiver_phone') }}</p>
                <p>
                    {{ data_get($address, 'street') }},
                    {{ data_get($address, 'ward_name') }},
                    {{ data_get($address, 'location_name') }}
                </p>
            </article>
        @empty
            <p>Chưa có địa chỉ nhận hàng.</p>
        @endforelse
    </section>
@endif
@endsection
