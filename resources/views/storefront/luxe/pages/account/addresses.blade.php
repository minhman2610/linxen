@extends('storefront.luxe.layouts.app')

@section('content')
<section class="lx-address-page-v2">

    {{-- HEADER --}}
    <header class="lx-address-head">
        <h1>Địa chỉ nhận hàng</h1>
        <p>Quản lý và sử dụng địa chỉ cho giao hàng</p>
    </header>

    {{-- ======================
        ADDRESS LIST
    ======================= --}}
    <div class="lx-address-block">

        @if(!empty($addresses))
            @foreach($addresses as $addr)
                <div class="lx-address-item {{ !empty($addr['is_default']) ? 'is-default' : '' }}"
                     data-address-id="{{ $addr['id'] }}">

                    <div class="lx-address-info">

                        <div class="lx-address-line-1">
                            <span class="lx-address-name">
                                {{ $addr['receiver_name'] }}
                            </span>

                            <span class="lx-address-phone">
                                {{ $addr['receiver_phone'] }}
                            </span>

                            @if(!empty($addr['is_default']))
                                <span class="lx-address-tag">Mặc định</span>
                            @endif
                        </div>

                        <div class="lx-address-line-2">
                            {{ $addr['street'] }},
                            {{ $addr['ward_name'] ?? '' }},
                            {{ $addr['location_name'] ?? '' }}
                        </div>

                        {{-- ACTIONS --}}
                        <div class="lx-address-actions">

                            @if(empty($addr['is_default']))
                                <form method="POST"
                                      action="{{ route('linxen.account.addresses.setDefault', $addr['id']) }}">
                                    @csrf
                                    <button type="submit" class="lx-btn lx-btn-green">
                                        Đặt mặc định
                                    </button>
                                </form>
                            @endif

                            <button type="button"
                                    class="lx-btn lx-btn-yellow"
                                    onclick="editAddress({{ $addr['id'] }})">
                                Sửa
                            </button>

                            <button type="button"
                                    class="lx-btn lx-btn-red"
                                    onclick="confirmDelete({{ $addr['id'] }})">
                                Xóa
                            </button>
                        </div>

                    </div>

                    {{-- EDIT FORM (HIDDEN) --}}
                    <div class="lx-address-edit" id="edit-{{ $addr['id'] }}" style="display:none">
                        <form method="POST"
                              action="{{ route('linxen.account.addresses.update', $addr['id']) }}"
                              class="lx-address-form-inline">
                            @csrf

                            <input name="receiver_name"
                                   value="{{ $addr['receiver_name'] }}"
                                   required>

                            <input name="receiver_phone"
                                   value="{{ $addr['receiver_phone'] }}"
                                   required>

                            <input name="street"
                                   value="{{ $addr['street'] }}"
                                   required>

                            <div class="lx-inline-actions">
                                <button class="lx-btn lx-btn-green">Cập nhật</button>
                                <button type="button"
                                        class="lx-btn lx-btn-gray"
                                        onclick="cancelEdit({{ $addr['id'] }})">
                                    Hủy
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            @endforeach
        @else
            <div class="lx-address-empty-v2">
                Bạn chưa có địa chỉ nhận hàng nào.
            </div>
        @endif

    </div>

    {{-- ======================
        ADD NEW ADDRESS
    ======================= --}}
    <div class="lx-address-form-box">
        <h2>Thêm địa chỉ mới</h2>

        @if ($errors->any())
            <div class="lx-form-error-box">
                @foreach ($errors->all() as $error)
                    <div class="lx-form-error">{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST"
              action="{{ route('linxen.account.addresses.store') }}"
              class="lx-address-form">
            @csrf

            <div class="lx-field">
                <label>Tên người nhận</label>
                <input name="receiver_name" required>
            </div>

            <div class="lx-field">
                <label>Số điện thoại</label>
                <input name="receiver_phone" required>
            </div>

            <div class="lx-field">
                <label>Số nhà, tên đường</label>
                <input name="street" required>
            </div>

            <button class="lx-btn lx-btn-green lx-btn-block">
                Thêm địa chỉ
            </button>
        </form>
    </div>

</section>

{{-- ======================
    CONFIRM DELETE POPUP
====================== --}}
<div id="lx-confirm-overlay" style="display:none">
    <div class="lx-confirm-box">
        <p>Bạn có chắc muốn xóa địa chỉ này?</p>
        <div class="lx-confirm-actions">
            <form id="deleteForm" method="POST">
                @csrf
                <button class="lx-btn lx-btn-red">Xóa</button>
            </form>
            <button class="lx-btn lx-btn-gray" onclick="closeConfirm()">Hủy</button>
        </div>
    </div>
</div>

{{-- ======================
    JS
====================== --}}
<script>
function editAddress(id) {
    document.getElementById('edit-' + id).style.display = 'block';
}

function cancelEdit(id) {
    document.getElementById('edit-' + id).style.display = 'none';
}

function confirmDelete(id) {
    document.getElementById('lx-confirm-overlay').style.display = 'flex';
    document.getElementById('deleteForm').action =
        `/account/addresses/${id}/delete`;
}

function closeConfirm() {
    document.getElementById('lx-confirm-overlay').style.display = 'none';
}
</script>
@endsection


{{-- =================================================
    LOCATION → WARD SCRIPT (SYNC CHECKOUT)
================================================= --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

    const locSel  = document.getElementById('lx-location');
    const wardSel = document.getElementById('lx-ward');

    if (!locSel || !wardSel) return;

    const oldLocation = "{{ old('location_id') }}";
    const oldWard     = "{{ old('ward_id') }}";

    // Load locations
    fetch('/api/storefront/locations?mode=raw')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;

            res.data.forEach(l => {
                const selected = oldLocation == l.id ? 'selected' : '';
                locSel.insertAdjacentHTML(
                    'beforeend',
                    `<option value="${l.id}" ${selected}>${l.name}</option>`
                );
            });

            if (oldLocation) {
                loadWards(oldLocation);
                document.getElementById('lx-location-name').value =
                    locSel.selectedOptions[0]?.text || '';
            }
        });

    function loadWards(locationId) {
        wardSel.innerHTML = '<option value="">-- Chọn phường / xã --</option>';
        wardSel.disabled = true;

        fetch(`/api/storefront/locations/${locationId}/wards?mode=raw`)
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;

                wardSel.disabled = false;

                res.data.forEach(w => {
                    const selected = oldWard == w.id ? 'selected' : '';
                    wardSel.insertAdjacentHTML(
                        'beforeend',
                        `<option value="${w.id}" ${selected}>${w.name}</option>`
                    );
                });

                if (oldWard) {
                    document.getElementById('lx-ward-name').value =
                        wardSel.selectedOptions[0]?.text || '';
                }
            });
    }

    locSel.addEventListener('change', () => {
        const id = locSel.value;

        document.getElementById('lx-location-name').value =
            locSel.selectedOptions[0]?.text || '';

        if (id) loadWards(id);
    });

    wardSel.addEventListener('change', () => {
        document.getElementById('lx-ward-name').value =
            wardSel.selectedOptions[0]?.text || '';
    });

});
</script>
@endsection
