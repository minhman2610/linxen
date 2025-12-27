@extends('storefront.luxe.layouts.app')

@section('content')
<section class="lx-address-page-v2">

    {{-- ======================
        HEADER
    ======================= --}}
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
                     id="address-card-{{ $addr['id'] }}">

                    {{-- ======================
                        VIEW MODE
                    ======================= --}}
                    <div class="lx-address-view">

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

                        <div class="lx-address-actions">
                            <button type="button"
        class="lx-btn lx-btn-yellow lx-btn-sm"
        onclick="openEditAddress({{ $addr['id'] }})">
    Sửa
</button>

@if(empty($addr['is_default']))
    <form method="POST"
          action="{{ route('linxen.account.addresses.setDefault', $addr['id']) }}">
        @csrf
        <button class="lx-btn lx-btn-outline lx-btn-sm">
            Đặt mặc định
        </button>
    </form>
@endif

<form method="POST"
      action="{{ route('linxen.account.addresses.delete', $addr['id']) }}"
      onsubmit="return confirm('Bạn chắc chắn muốn xóa địa chỉ này?')">
    @csrf
    <button class="lx-btn lx-btn-danger lx-btn-sm">
        Xóa
    </button>
</form>

                        </div>
                    </div>

                    {{-- ======================
                        EDIT MODE (INLINE)
                    ======================= --}}
                    <div class="lx-address-edit" style="display:none">

                        <form method="POST"
                              action="{{ route('linxen.account.addresses.update', $addr['id']) }}"
                              class="lx-address-edit-form">
                            @csrf

                            <div class="lx-field">
                                <label>Tên người nhận</label>
                                <input name="receiver_name"
                                       value="{{ $addr['receiver_name'] ?? $addr['name'] }}"
                                       required>
                            </div>

                            <div class="lx-field">
                                <label>Số điện thoại</label>
                                <input name="receiver_phone"
                                       value="{{ $addr['receiver_phone'] ?? $addr['phone'] }}"
                                       required>
                            </div>

                            {{-- LOCATION + WARD --}}
                            <div class="lx-field-row">
                                <div class="lx-field">
                                    <label>Khu vực</label>
                                    <select name="location_id"
                                            class="lx-location-select"
                                            data-selected="{{ $addr['location_id'] ?? '' }}"
                                            required></select>
                                </div>

                                <div class="lx-field">
                                    <label>Phường / Xã</label>
                                    <select name="ward_id"
                                            class="lx-ward-select"
                                            data-selected="{{ $addr['ward_id'] ?? '' }}"
                                            required></select>
                                </div>
                            </div>

                            <div class="lx-field">
                                <label>Số nhà, tên đường</label>
                                <input name="street"
                                       value="{{ $addr['street'] ?? '' }}"
                                       required>
                            </div>

                            {{-- ERP FRIENDLY --}}
                            <input type="hidden" name="location_name">
                            <input type="hidden" name="ward_name">

                            <div class="lx-btn-group right">
    <button type="submit" class="lx-btn lx-btn-secondary">
        Cập nhật
    </button>

    <button type="button"
            class="lx-btn lx-btn-outline"
            onclick="closeEditAddress({{ $addr['id'] }})">
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
                <input name="receiver_name"
                       value="{{ old('receiver_name') }}"
                       required>
            </div>

            <div class="lx-field">
                <label>Số điện thoại</label>
                <input name="receiver_phone"
                       value="{{ old('receiver_phone') }}"
                       required>
            </div>

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

            <div class="lx-field">
                <label>Số nhà, tên đường</label>
                <input name="street"
                       value="{{ old('street') }}"
                       required>
            </div>

            <input type="hidden" name="location_name" id="lx-location-name">
            <input type="hidden" name="ward_name" id="lx-ward-name">

            <button class="lx-btn lx-btn-primary lx-btn-block">
    Thêm địa chỉ
</button>

        </form>
    </div>

</section>
<script>
/* =====================================================
   GLOBAL: LOCATION / WARD HELPERS
   → dùng chung cho ADD + EDIT
===================================================== */

/**
 * Load danh sách Tỉnh / Thành
 */
function loadLocations(selectEl, callback) {
    fetch('/api/storefront/locations?mode=raw')
        .then(r => r.json())
        .then(res => {
            if (!res.success || !Array.isArray(res.data)) return;

            selectEl.innerHTML = '<option value="">-- Chọn khu vực --</option>';

            res.data.forEach(l => {
                selectEl.insertAdjacentHTML(
                    'beforeend',
                    `<option value="${l.id}">${l.name}</option>`
                );
            });

            if (typeof callback === 'function') callback();
        })
        .catch(() => {});
}

/**
 * Load danh sách Phường / Xã theo location
 */
function loadWards(locationId, selectEl, callback) {
    if (!locationId) return;

    selectEl.innerHTML = '<option value="">-- Chọn phường / xã --</option>';
    selectEl.disabled = true;

    fetch(`/api/storefront/locations/${locationId}/wards?mode=raw`)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !Array.isArray(res.data)) return;

            selectEl.disabled = false;

            res.data.forEach(w => {
                selectEl.insertAdjacentHTML(
                    'beforeend',
                    `<option value="${w.id}">${w.name}</option>`
                );
            });

            if (typeof callback === 'function') callback();
        })
        .catch(() => {});
}

/* =====================================================
   INLINE EDIT ADDRESS
===================================================== */

function openEditAddress(id) {
    const card = document.getElementById(`address-card-${id}`);
    if (!card) return;

    card.querySelector('.lx-address-view').style.display = 'none';
    card.querySelector('.lx-address-edit').style.display = 'block';

    initEditLocationWard(card);
}

function closeEditAddress(id) {
    const card = document.getElementById(`address-card-${id}`);
    if (!card) return;

    card.querySelector('.lx-address-edit').style.display = 'none';
    card.querySelector('.lx-address-view').style.display = 'block';
}

/**
 * Init location / ward cho EDIT MODE
 */
function initEditLocationWard(card) {
    const locationSelect = card.querySelector('.lx-location-select');
    const wardSelect     = card.querySelector('.lx-ward-select');
    const hiddenLocName  = card.querySelector('input[name="location_name"]');
    const hiddenWardName = card.querySelector('input[name="ward_name"]');

    if (!locationSelect || !wardSelect) return;

    const selectedLocation = locationSelect.dataset.selected || '';
    const selectedWard     = wardSelect.dataset.selected || '';

    loadLocations(locationSelect, () => {

        if (selectedLocation) {
            locationSelect.value = selectedLocation;

            if (hiddenLocName) {
                hiddenLocName.value =
                    locationSelect.selectedOptions[0]?.text || '';
            }

            loadWards(selectedLocation, wardSelect, () => {
                if (selectedWard) {
                    wardSelect.value = selectedWard;
                    if (hiddenWardName) {
                        hiddenWardName.value =
                            wardSelect.selectedOptions[0]?.text || '';
                    }
                }
            });
        }
    });

    locationSelect.onchange = () => {
        if (hiddenLocName) {
            hiddenLocName.value =
                locationSelect.selectedOptions[0]?.text || '';
        }
        loadWards(locationSelect.value, wardSelect);
    };

    wardSelect.onchange = () => {
        if (hiddenWardName) {
            hiddenWardName.value =
                wardSelect.selectedOptions[0]?.text || '';
        }
    };
}

/* =====================================================
   ADD NEW ADDRESS FORM
===================================================== */

document.addEventListener('DOMContentLoaded', () => {

    const locSel  = document.getElementById('lx-location');
    const wardSel = document.getElementById('lx-ward');
    const hiddenLocName  = document.getElementById('lx-location-name');
    const hiddenWardName = document.getElementById('lx-ward-name');

    if (!locSel || !wardSel) return;

    const oldLocation = "{{ old('location_id') }}";
    const oldWard     = "{{ old('ward_id') }}";

    loadLocations(locSel, () => {
        if (oldLocation) {
            locSel.value = oldLocation;

            if (hiddenLocName) {
                hiddenLocName.value =
                    locSel.selectedOptions[0]?.text || '';
            }

            loadWards(oldLocation, wardSel, () => {
                if (oldWard) {
                    wardSel.value = oldWard;
                    if (hiddenWardName) {
                        hiddenWardName.value =
                            wardSel.selectedOptions[0]?.text || '';
                    }
                }
            });
        }
    });

    locSel.addEventListener('change', () => {
        if (hiddenLocName) {
            hiddenLocName.value =
                locSel.selectedOptions[0]?.text || '';
        }
        loadWards(locSel.value, wardSel);
    });

    wardSel.addEventListener('change', () => {
        if (hiddenWardName) {
            hiddenWardName.value =
                wardSel.selectedOptions[0]?.text || '';
        }
    });
});
</script>

@endsection
