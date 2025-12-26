@extends('storefront.luxe.layouts.app')

@section('content')

<div class="lx-account-container">

    @if(!empty($customer))
        {{-- =========================
            ĐÃ ĐĂNG NHẬP
        ========================== --}}

        {{-- HEADER --}}
        <div class="lx-account-header">
            <h2>Tài khoản của tôi</h2>
            <p>
                Xin chào
                <strong>{{ $customer->name ?? $customer->phone }}</strong>
            </p>
        </div>

        {{-- ACCOUNT MENU --}}
        <div class="lx-account-menu">

            {{-- ORDERS --}}
            <a href="{{ route('linxen.account.orders') }}"
               class="lx-account-card">
                <div class="lx-account-card-left">
                    <span class="lx-account-icon">📦</span>
                    <div>
                        <strong>Đơn hàng</strong>
                        <span>Theo dõi trạng thái đơn đã mua</span>
                    </div>
                </div>
                <span class="lx-account-arrow">›</span>
            </a>

            {{-- ADDRESSES --}}
<a href="{{ route('linxen.account.addresses') }}"
   class="lx-account-card">
    <div class="lx-account-card-left">
        <span class="lx-account-icon">📍</span>
        <div>
            <strong>Địa chỉ nhận hàng</strong>
            <span>Quản lý địa chỉ giao hàng</span>
        </div>
    </div>
    <span class="lx-account-arrow">›</span>
</a>

{{-- PROFILE --}}
<a href="{{ route('linxen.account.profile') }}"
   class="lx-account-card">
    <div class="lx-account-card-left">
        <span class="lx-account-icon">👤</span>
        <div>
            <strong>Thông tin cá nhân</strong>
            <span>Tên, email, thông tin liên hệ</span>
        </div>
    </div>
    <span class="lx-account-arrow">›</span>
</a>


            {{-- SECURITY --}}
<div class="lx-account-card lx-account-card--danger"
     onclick="openLogoutModal()"
     role="button"
     tabindex="0">

    <div class="lx-account-card-left">
        <span class="lx-account-icon">🚪</span>
        <div>
            <strong>Đăng xuất</strong>
            <span>Thoát khỏi tài khoản hiện tại</span>
        </div>
    </div>

    <span class="lx-account-arrow">›</span>
</div>


            {{-- SUPPORT --}}
            <a href="https://zalo.me/your-zalo-id"
               target="_blank"
               class="lx-account-card">
                <div class="lx-account-card-left">
                    <span class="lx-account-icon">💬</span>
                    <div>
                        <strong>Hỗ trợ</strong>
                        <span>Liên hệ CSKH LIN XÉN</span>
                    </div>
                </div>
                <span class="lx-account-arrow">›</span>
            </a>

        </div>

    @else
        {{-- =========================
            CHƯA ĐĂNG NHẬP
        ========================== --}}

        <div class="lx-empty-state">

            <h2>Tài khoản LIN XÉN</h2>

            <p>
                Đăng nhập hoặc tạo tài khoản để:
                <br>
                • Theo dõi đơn hàng<br>
                • Lưu địa chỉ nhận hàng<br>
                • Nhận ưu đãi thành viên
            </p>

            <div class="lx-account-cta">

                {{-- LOGIN --}}
                <a href="{{ route('linxen.login') }}"
                   class="lx-btn-primary lx-btn-full">
                    Đăng nhập
                </a>

                {{-- REGISTER --}}
                <a href="{{ route('linxen.register') }}"
                   class="lx-btn-secondary lx-btn-full">
                    Đăng ký
                </a>

                <a href="{{ route('linxen.home') }}"
                   class="lx-btn-link">
                    Tiếp tục mua sắm →
                </a>

            </div>

        </div>
    @endif

</div>
{{-- =========================
    LOGOUT CONFIRM MODAL
========================== --}}
<div id="lxLogoutModal" class="lx-modal" style="display:none">

    <div class="lx-modal-overlay" onclick="closeLogoutModal()"></div>

    <div class="lx-modal-content lx-modal-confirm">

        <div class="lx-modal-head">
            <div class="lx-modal-icon">🚪</div>
            <h3>Đăng xuất tài khoản?</h3>
            <p>
                Bạn sẽ cần đăng nhập lại để xem đơn hàng<br>
                và quyền lợi thành viên.
            </p>
        </div>

        <div class="lx-modal-actions">

            <button type="button"
                    class="lx-btn-secondary"
                    onclick="closeLogoutModal()">
                Ở lại
            </button>

            <form method="POST"
                  action="{{ route('linxen.logout') }}">
                @csrf
                <button type="submit"
                        class="lx-btn-danger">
                    Đăng xuất
                </button>
            </form>

        </div>

    </div>
</div>

@endsection
<script>
function openLogoutModal() {
    const modal = document.getElementById('lxLogoutModal');
    if (modal) modal.style.display = 'block';
}

function closeLogoutModal() {
    const modal = document.getElementById('lxLogoutModal');
    if (modal) modal.style.display = 'none';
}
</script>
