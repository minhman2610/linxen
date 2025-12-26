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
                            {{ $addr['street'] ?? $addr['address'] }}
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

    {{-- FORM --}}
    <div class="lx-address-form-box">
        <h2>Thêm địa chỉ mới</h2>

        <form method="POST" class="lx-address-form">
            @csrf

            {{-- NAME --}}
            <div class="lx-field">
                <label>Tên người nhận</label>
                <input name="name" placeholder="Nguyễn Văn A" required>
            </div>

            {{-- PHONE --}}
            <div class="lx-field">
                <label>Số điện thoại</label>
                <input name="phone" placeholder="097xxxxxxx" required>
            </div>

            {{-- LOCATION + WARD (GIỐNG CHECKOUT) --}}
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
                       placeholder="Ví dụ: 12 Nguyễn Trãi"
                       required>
            </div>

            {{-- HIDDEN NAMES (ERP FRIENDLY) --}}
            <input type="hidden" name="location_name" id="lx-location-name">
            <input type="hidden" name="ward_name" id="lx-ward-name">

            <button class="lx-btn-primary lx-btn-block">
                Thêm địa chỉ
            </button>
        </form>
    </div>

</section>

{{-- =========================
    LOCATION → WARD SCRIPT
    (COPY TỪ CHECKOUT.JS)
========================== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

    const locSel  = document.getElementById('lx-location');
    const wardSel = document.getElementById('lx-ward');

    if (!locSel || !wardSel) return;

    fetch('/api/storefront/locations?mode=raw')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            res.data.forEach(l => {
                locSel.insertAdjacentHTML(
                    'beforeend',
                    `<option value="${l.id}">${l.name}</option>`
                );
            });
        });

    locSel.addEventListener('change', () => {
        const id = locSel.value;

        document.getElementById('lx-location-name').value =
            locSel.selectedOptions[0]?.text || '';

        wardSel.innerHTML = '<option value="">-- Chọn phường / xã --</option>';
        wardSel.disabled = true;

        if (!id) return;

        fetch(`/api/storefront/locations/${id}/wards?mode=raw`)
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                wardSel.disabled = false;
                res.data.forEach(w => {
                    wardSel.insertAdjacentHTML(
                        'beforeend',
                        `<option value="${w.id}">${w.name}</option>`
                    );
                });
            });
    });

    wardSel.addEventListener('change', () => {
        document.getElementById('lx-ward-name').value =
            wardSel.selectedOptions[0]?.text || '';
    });

});
</script>

@endsection
