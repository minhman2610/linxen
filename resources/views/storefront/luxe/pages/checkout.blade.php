@extends('storefront.luxe.layouts.app')

@section('content')

@php
    $cartItems = $cart ?? [];
    $subtotal = 0;

    foreach ($cartItems as $item) {
        $subtotal += ($item['price'] ?? 0) * ($item['qty'] ?? 0);
    }

    $shippingFee = $subtotal >= 500000 ? 0 : 30000;
    $total = $subtotal + $shippingFee;
@endphp

<section class="lx-checkout-page">

    {{-- HEADER --}}
    <div class="lx-checkout-header">
        <h1>Thanh toán</h1>
        <a href="{{ route('linxen.cart') }}" class="lx-checkout-back">
            ← Quay lại giỏ hàng
        </a>
    </div>

    @if(empty($cartItems))
        <div class="lx-checkout-empty">
            <p>Giỏ hàng của bạn đang trống.</p>
            <a href="{{ route('linxen.home') }}" class="lx-btn-primary">
                Quay về trang chủ
            </a>
        </div>
    @else

    <form id="lx-checkout-form" class="lx-checkout-content">
    @csrf

        {{-- ================= LEFT: SHIPPING INFO ================= --}}
        <div class="lx-checkout-left">

            <h3>Thông tin giao hàng</h3>

            <div class="lx-form-group">
                <label>Họ và tên</label>
                <input type="text" name="name" required placeholder="Nguyễn Văn A">
            </div>

            <div class="lx-form-row">
                <div class="lx-form-group">
                    <label>Số điện thoại</label>
                    <input type="tel" name="phone" required placeholder="09xxxxxxxx">
                </div>

                <div class="lx-form-group">
                    <label>Email (không bắt buộc)</label>
                    <input type="email" name="email" placeholder="email@example.com">
                </div>
            </div>

            {{-- ADDRESS (2-level: Location → Ward) --}}
<div class="lx-form-row">
    <div class="lx-form-group">
        <label>Khu vực (Tỉnh / Quận)</label>
        <select
            name="location_id"
            id="lx-location"
            required
        >
            <option value="">-- Chọn Khu vực --</option>
        </select>
    </div>

    <div class="lx-form-group">
        <label>Phường / Xã</label>
        <select
            name="ward_id"
            id="lx-ward"
            required
            disabled
        >
            <option value="">-- Chọn Phường / Xã --</option>
        </select>
    </div>
</div>

<div class="lx-form-row">
    <div class="lx-form-group">
        <label>Số nhà, tên đường</label>
        <input
            type="text"
            name="street"
            required
            placeholder="Số nhà, tên đường"
        >
    </div>
</div>

<div class="lx-form-group">
    <label>Ghi chú đơn hàng</label>
    <textarea
        name="note"
        rows="2"
        placeholder="Ghi chú cho shop (nếu có)"
    ></textarea>
</div>



        </div>

        {{-- ================= RIGHT: ORDER SUMMARY ================= --}}
        <div class="lx-checkout-right">

            <h3>Đơn hàng của bạn</h3>

            <div class="lx-checkout-items">

                @foreach($cartItems as $item)
                    <div class="lx-checkout-item">

                        {{-- IMAGE --}}
                        <div class="lx-checkout-item-thumb">
                            <img
                                src="{{ $item['image'] ?? '/themes/luxe/assets/images/placeholder-product.jpg' }}"
                                alt="{{ $item['name'] }}">
                        </div>

                        {{-- INFO --}}
                        <div class="lx-checkout-item-info">
                            <div class="lx-checkout-item-name">
                                {{ $item['name'] }}
                            </div>

                            @if(!empty($item['variant']))
                                <div class="lx-checkout-item-variant">
                                    {{ $item['variant'] }}
                                </div>
                            @endif

                            <div class="lx-checkout-item-qty">
                                Số lượng: {{ $item['qty'] }}
                            </div>
                        </div>

                        {{-- PRICE --}}
                        <div class="lx-checkout-item-price">
                            {{ number_format($item['price'] * $item['qty']) }}₫
                        </div>

                    </div>
                @endforeach

            </div>

            {{-- SUMMARY --}}
            <div class="lx-checkout-summary-row">
                <span>Tạm tính</span>
                <span>{{ number_format($subtotal) }}₫</span>
            </div>

            <div class="lx-checkout-summary-row">
                <span>Phí vận chuyển</span>
                <span>
                    {{ $shippingFee > 0 ? number_format($shippingFee).'₫' : 'Miễn phí' }}
                </span>
            </div>

            <div class="lx-checkout-summary-total">
                <span>Tổng cộng</span>
                <span>{{ number_format($total) }}₫</span>
            </div>

            {{-- PAYMENT --}}
            <div class="lx-checkout-payment">
                <label class="lx-radio">
                    <input type="radio" name="payment_method" value="cod" checked>
                    <span>Thanh toán khi nhận hàng (COD)</span>
                </label>
            </div>

            <button type="submit"
                    class="lx-btn-primary lx-btn-full">
                ĐẶT HÀNG
            </button>

            <div class="lx-checkout-note">
                ✔ Xác nhận đơn trong giờ hành chính<br>
                ✔ Giao hàng toàn quốc
            </div>

        </div>

    </form>

    @endif

</section>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const locationSelect = document.getElementById('lx-location');
    const wardSelect     = document.getElementById('lx-ward');

    // 🔗 JS chỉ gọi LIN XÉN (BE proxy sang ERP)
    const API_BASE = '/api/locations';

    /**
     * Reset select helper
     */
    function resetSelect(select, placeholder, disabled = true) {
        select.innerHTML = `<option value="">${placeholder}</option>`;
        select.disabled = disabled;
    }

    /**
     * Fetch JSON helper
     */
    async function fetchJSON(url) {
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });

        if (!res.ok) {
            throw new Error('Network error');
        }

        return res.json();
    }

    /**
     * =====================================================
     * 📍 Load Locations (City + District)
     * =====================================================
     */
    async function loadLocations() {
        resetSelect(locationSelect, '-- Đang tải khu vực --', true);
        resetSelect(wardSelect, '-- Chọn Phường / Xã --', true);

        try {
            const res = await fetchJSON(`${API_BASE}?mode=raw`);

            if (!res.success || !Array.isArray(res.data)) return;

            resetSelect(locationSelect, '-- Chọn Khu vực --', false);

            res.data.forEach(loc => {
                const opt = document.createElement('option');
                opt.value = loc.id;
                opt.textContent = loc.name;
                locationSelect.appendChild(opt);
            });

        } catch (e) {
            console.error('Load locations error:', e);
        }
    }

    /**
     * =====================================================
     * 🏠 Load Wards theo Location
     * ERP: /api/locations/wards/{locationId}
     * =====================================================
     */
    async function loadWards(locationId) {
        resetSelect(wardSelect, '-- Đang tải Phường / Xã --', true);

        try {
            const res = await fetchJSON(`${API_BASE}/${locationId}/wards?mode=raw`);

            // 🔴 Location đã sáp nhập
            if (res.error && res.merged_into) {
                alert(res.message);
                locationSelect.value = '';
                resetSelect(wardSelect, '-- Chọn Phường / Xã --', true);
                return;
            }

            if (!res.success || !Array.isArray(res.data)) return;

            resetSelect(wardSelect, '-- Chọn Phường / Xã --', false);

            res.data.forEach(w => {
                const opt = document.createElement('option');
                opt.value = w.id;
                opt.textContent = w.name;
                wardSelect.appendChild(opt);
            });

        } catch (e) {
            console.error('Load wards error:', e);
        }
    }

    /**
     * =====================================================
     * EVENTS
     * =====================================================
     */
    locationSelect.addEventListener('change', e => {
        const id = e.target.value;
        if (!id) return;
        loadWards(id);
    });

    /**
     * INIT
     */
    loadLocations();
});

</script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('lx-checkout-form');
    if (!form) return;

    const submitBtn = form.querySelector('button[type="submit"]');
    const csrfToken = document.querySelector('input[name="_token"]').value;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // 🔒 Chặn double submit
        submitBtn.disabled = true;
        submitBtn.innerText = 'ĐANG XỬ LÝ...';

        const fd = new FormData(form);

        // ===============================
        // Build payload gửi BE
        // ===============================
        const payload = {
            customer: {
                name: fd.get('name'),
                phone: fd.get('phone'),
                location_id: fd.get('location_id'),
                ward_id: fd.get('ward_id'),
                street: fd.get('street'),
                note: fd.get('note'),
            }
        };

        try {
            const res = await fetch('/api/storefront/orders', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const json = await res.json();

            if (!res.ok || !json.success) {
                alert(json.message || 'Không thể tạo đơn hàng.');
                submitBtn.disabled = false;
                submitBtn.innerText = 'ĐẶT HÀNG';
                return;
            }

            // ✅ Thành công → chuyển trang
            window.location.href =
                `/order-success?code=${json.order_code ?? ''}`;

        } catch (err) {
            console.error(err);
            alert('Lỗi kết nối hệ thống. Vui lòng thử lại.');
            submitBtn.disabled = false;
            submitBtn.innerText = 'ĐẶT HÀNG';
        }
    });

});
</script>

@endpush


