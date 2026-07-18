<div class="lxcv1-page" data-lxcv1-page="account">
    <section class="lxcv1-checkout-heading">
        <div>
            <p class="lxcv1-kicker">TÀI KHOẢN</p>
            <h1>Không gian mua sắm của bạn</h1>
        </div>
    </section>

    @if(empty($account))
        <section class="lxcv1-receipt-card lxcv1-account-card">
            @if(!empty($accountError))
                <div class="lxcv1-alert lxcv1-alert--error">{{ $accountError }}</div>
            @endif

            @if(!empty($guestIdentity))
                @php
                    $guestCustomer = (array) data_get($guestIdentity, 'customer', []);
                    $guestAddress = (array) data_get($guestIdentity, 'shipping_address', []);
                @endphp
                <p class="lxcv1-kicker">GUEST CHECKOUT</p>
                <h2>Phiên mua hàng đang hoạt động</h2>
                <p>Số điện thoại: <strong>{{ data_get($guestCustomer, 'phone') }}</strong></p>
                <p>
                    {{ data_get($guestAddress, 'street') }},
                    {{ data_get($guestAddress, 'ward_name') }},
                    {{ data_get($guestAddress, 'location_name') }}
                </p>
                <form method="post" action="{{ route('commerce.v2.account.logout') }}">
                    @csrf
                    @method('DELETE')
                    <button class="lxcv1-text-button lxcv1-text-button--danger" type="submit">Xóa phiên guest</button>
                </form>
            @else
                <p class="lxcv1-kicker">MUA NHANH</p>
                <h2>Không cần đăng ký trước</h2>
                <p>Bạn có thể nhập tên, số điện thoại và địa chỉ trực tiếp tại checkout.</p>
                <a class="lxcv1-button lxcv1-button--dark" href="{{ route('commerce.v2.shop') }}">Tiếp tục mua sắm</a>
            @endif
        </section>
    @else
        @php
            $customer = (array) data_get($account, 'customer', []);
            $addresses = collect((array) data_get($account, 'addresses', []));
        @endphp

        <div class="lxcv1-account-layout">
            <section class="lxcv1-receipt-card">
                <p class="lxcv1-kicker">ĐÃ XÁC MINH</p>
                <h2>{{ data_get($customer, 'phone') }}</h2>
                <p>{{ data_get($customer, 'email') ?: 'Chưa có email' }}</p>
                <a class="lxcv1-button lxcv1-button--dark" href="{{ route('commerce.v2.orders.index') }}">Xem lịch sử đơn</a>
                <form method="post" action="{{ route('commerce.v2.account.logout') }}">
                    @csrf
                    @method('DELETE')
                    <button class="lxcv1-text-button" type="submit">Đăng xuất</button>
                </form>
            </section>

            <section class="lxcv1-address-grid">
                @forelse($addresses as $address)
                    <article>
                        <strong>{{ data_get($address, 'receiver_name') }}</strong>
                        @if(data_get($address, 'is_default'))<span>Mặc định</span>@endif
                        <p>{{ data_get($address, 'receiver_phone') }}</p>
                        <p>
                            {{ data_get($address, 'street') }},
                            {{ data_get($address, 'ward_name') }},
                            {{ data_get($address, 'location_name') }}
                        </p>
                    </article>
                @empty
                    <div class="lxcv1-empty">Chưa có địa chỉ nhận hàng.</div>
                @endforelse
            </section>
        </div>
    @endif
</div>
