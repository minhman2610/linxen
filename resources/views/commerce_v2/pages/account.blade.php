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

        @if(!empty($guestIdentity))
            @php
                $guestCustomer = (array) data_get(
                    $guestIdentity,
                    'customer',
                    []
                );
                $guestAddress = (array) data_get(
                    $guestIdentity,
                    'shipping_address',
                    []
                );
            @endphp

            <h2>Guest checkout đang hoạt động</h2>
            <p>
                Số điện thoại:
                <strong>{{ data_get($guestCustomer, 'phone') }}</strong>
            </p>
            <p>
                Địa chỉ gần nhất:
                {{ data_get($guestAddress, 'street') }},
                {{ data_get($guestAddress, 'ward_name') }},
                {{ data_get($guestAddress, 'location_name') }}
            </p>
            <div class="lxv2-account-note">
                Phiên guest chỉ được dùng để đặt hàng và xem biên nhận
                vừa tạo. Muốn xem toàn bộ lịch sử đơn, anh cần xác minh
                bằng magic link hoặc OTP khi SMS provider được kết nối.
            </div>

            <form
                method="post"
                action="{{ route('commerce.v2.account.logout') }}"
            >
                @csrf
                @method('DELETE')
                <button type="submit">Xóa phiên guest</button>
            </form>
        @else
            <h2>Mua hàng không cần tài khoản</h2>
            <p>
                Anh có thể nhập tên, số điện thoại và địa chỉ trực tiếp
                tại checkout. Không cần đăng ký trước.
            </p>
            <p>
                Magic link dùng một lần vẫn được giữ cho customer cần
                xem đầy đủ lịch sử đơn. OTP chưa bật vì chưa có SMS
                provider contract chính thức.
            </p>
            <a
                class="lxv2-button"
                href="{{ route('commerce.v2.shop') }}"
            >
                Tiếp tục mua sắm
            </a>
        @endif
    </section>
@else
    @php
        $customer = (array) data_get(
            $account,
            'customer',
            []
        );
        $addresses = collect((array) data_get(
            $account,
            'addresses',
            []
        ));
    @endphp

    <section class="lxv2-account-card">
        <h2>Thông tin khách hàng đã xác minh</h2>
        <p><strong>ID:</strong> {{ data_get($customer, 'id') }}</p>
        <p>
            <strong>Số điện thoại:</strong>
            {{ data_get($customer, 'phone') }}
        </p>
        <p>
            <strong>Email:</strong>
            {{ data_get($customer, 'email') ?: '—' }}
        </p>
        <p>
            <strong>Mức xác minh:</strong>
            {{ $assurance ?? 'verified' }}
        </p>

        <a
            class="lxv2-button"
            href="{{ route('commerce.v2.orders.index') }}"
        >
            Xem lịch sử đơn
        </a>

        <form
            method="post"
            action="{{ route('commerce.v2.account.logout') }}"
        >
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
