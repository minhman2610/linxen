@extends('storefront.luxe.layouts.app')

@section('content')

@php
    $cartItems = $cart ?? [];
    $subtotal = collect($cartItems)->sum(fn($i) => ($i['price'] ?? 0) * ($i['qty'] ?? 0));
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

    {{-- ⚠️ KHÔNG DÙNG ACTION POST TRỰC TIẾP --}}
    <form id="lx-checkout-form" class="lx-checkout-content">

        @csrf

        {{-- LEFT --}}
        <div class="lx-checkout-left">
            <h3>Thông tin giao hàng</h3>

            <div class="lx-form-group">
                <label>Họ và tên</label>
                <input type="text" name="name" required>
            </div>

            <div class="lx-form-group">
                <label>Số điện thoại</label>
                <input type="tel" name="phone" required>
            </div>

            {{-- LOCATION --}}
            <div class="lx-form-row">
                <div class="lx-form-group">
                    <label>Khu vực</label>
                    <select name="location_id" id="lx-location" required>
                        <option value="">-- Chọn khu vực --</option>
                    </select>
                </div>

                <div class="lx-form-group">
                    <label>Phường / Xã</label>
                    <select name="ward_id" id="lx-ward" required disabled>
                        <option value="">-- Chọn phường / xã --</option>
                    </select>
                </div>
            </div>

            <div class="lx-form-group">
                <label>Số nhà, tên đường</label>
                <input type="text" name="street" required>
            </div>

            <div class="lx-form-group">
                <label>Ghi chú</label>
                <textarea name="note" rows="2"></textarea>
            </div>
        </div>

        {{-- RIGHT --}}
        <div class="lx-checkout-right">
            <h3>Đơn hàng</h3>

            <div class="lx-checkout-items">
                @foreach($cartItems as $item)
                    <div class="lx-checkout-item">
                        <div>
                            {{ $item['name'] }}
                            <span>× {{ $item['qty'] }}</span>
                        </div>
                        <div>
                            {{ number_format(($item['price'] ?? 0) * $item['qty']) }}₫
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="lx-checkout-summary-row">
                <span>Tạm tính</span>
                <span>{{ number_format($subtotal) }}₫</span>
            </div>

            <div class="lx-checkout-summary-row">
                <span>Vận chuyển</span>
                <span>{{ $shippingFee ? number_format($shippingFee).'₫' : 'Miễn phí' }}</span>
            </div>

            <div class="lx-checkout-summary-total">
                <span>Tổng cộng</span>
                <span>{{ number_format($total) }}₫</span>
            </div>

            <button type="submit" class="lx-btn-primary lx-btn-full">
                ĐẶT HÀNG
            </button>

            <div id="lx-checkout-error" class="lx-checkout-error" style="display:none"></div>
        </div>

    </form>
    @endif
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
     * LOCATION → WARD
     * ===================================================== */
    const locSel  = document.getElementById('lx-location');
    const wardSel = document.getElementById('lx-ward');

    fetch('/api/storefront/locations?mode=raw')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            res.data.forEach(l => {
                locSel.insertAdjacentHTML('beforeend',
                    `<option value="${l.id}">${l.name}</option>`
                );
            });
        });

    locSel.addEventListener('change', e => {
        const id = e.target.value;
        wardSel.innerHTML = '<option value="">-- Chọn phường / xã --</option>';
        wardSel.disabled = true;
        if (!id) return;

        fetch(`/api/storefront/locations/${id}/wards?mode=raw`)
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                wardSel.disabled = false;
                res.data.forEach(w => {
                    wardSel.insertAdjacentHTML('beforeend',
                        `<option value="${w.id}">${w.name}</option>`
                    );
                });
            });
    });

    /* =====================================================
     * SUBMIT CHECKOUT (AJAX)
     * ===================================================== */
    const form = document.getElementById('lx-checkout-form');
    const errBox = document.getElementById('lx-checkout-error');

    form.addEventListener('submit', async (e) => {
    e.preventDefault();
    errBox.style.display = 'none';

    const fd = new FormData(form);

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
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });

        const text = await res.text(); // 🔥 đọc raw trước
        let json;

        try {
            json = JSON.parse(text);
        } catch (e) {
            console.error('❌ API trả về không phải JSON:', text);
            errBox.innerText = 'Server lỗi (response không hợp lệ).';
            errBox.style.display = 'block';
            return;
        }

        if (!res.ok || !json.success) {
            errBox.innerText = json.message || `Lỗi server (${res.status})`;
            errBox.style.display = 'block';
            return;
        }

        // ✅ Thành công
        window.location.href = `/account/orders/${json.order_code}`;

    } catch (err) {
        console.error('🔥 Fetch error:', err);
        errBox.innerText = 'Không kết nối được server.';
        errBox.style.display = 'block';
    }
});

});
</script>
@endpush
