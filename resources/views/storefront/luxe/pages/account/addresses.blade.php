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
                <div class="lx-address-item {{ !empty($addr['is_default']) ? 'is-default' : '' }}">
                    <div class="lx-address-info">

                        <div class="lx-address-line-1">
                            <span class="lx-address-name">
                                {{ $addr['receiver_name'] ?? $addr['name'] }}
                            </span>

                            <span class="lx-address-phone">
                                {{ $addr['receiver_phone'] ?? $addr['phone'] }}
                            </span>

                            @if(!empty($addr['is_default']))
                                <span class="lx-address-tag">Mặc định</span>
                            @endif
                        </div>

                        <div class="lx-address-line-2">
                            {{ $addr['street'] ?? '' }}
                            @if(!empty($addr['ward_name'])), {{ $addr['ward_name'] }}@endif
                            @if(!empty($addr['location_name'])), {{ $addr['location_name'] }}@endif
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

    {{-- ======================
        ADD NEW ADDRESS
    ======================= --}}
    <div class="lx-address-form-box">
        <h2>Thêm địa chỉ mới</h2>

        {{-- ERRORS --}}
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

            {{-- RECEIVER NAME --}}
            <div class="lx-field">
                <label>Tên người nhận</label>
                <input name="receiver_name"
                       value="{{ old('receiver_name') }}"
                       placeholder="Nguyễn Văn A"
                       required>
            </div>

            {{-- RECEIVER PHONE --}}
            <div class="lx-field">
                <label>Số điện thoại</label>
                <input name="receiver_phone"
                       value="{{ old('receiver_phone') }}"
                       placeholder="097xxxxxxx"
                       required>
            </div>

            {{-- LOCATION + WARD --}}
            <div class="lx-field-row">
                <div class="lx-field">
                    <label>Khu vực</label>
                    <select name="location_id" id="lx-location" required>
                        <option value="">-- Chọn khu vực --</option>
                    </select>
                </div>

                <div class="lx-field">
                    <label>Phường / Xã</label>
                    <select name="ward_id" id="lx-ward" required disabled>
                        <option value="">-- Chọn phường / xã --</option>
                    </select>
                </div>
            </div>

            {{-- STREET --}}
            <div class="lx-field">
                <label>Số nhà, tên đường</label>
                <input name="street"
                       value="{{ old('street') }}"
                       placeholder="Ví dụ: 12 Nguyễn Trãi"
                       required>
            </div>

            {{-- HIDDEN (ERP FRIENDLY) --}}
            <input type="hidden"
                   name="location_name"
                   id="lx-location-name"
                   value="{{ old('location_name') }}">

            <input type="hidden"
                   name="ward_name"
                   id="lx-ward-name"
                   value="{{ old('ward_name') }}">

            <button class="lx-btn-primary lx-btn-block">
                Thêm địa chỉ
            </button>
        </form>
    </div>

</section>

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
