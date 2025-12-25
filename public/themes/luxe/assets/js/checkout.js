document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
     * LOCATION → WARD
     * ===================================================== */
    const locSel  = document.getElementById('lx-location');
    const wardSel = document.getElementById('lx-ward');

    if (locSel && wardSel) {
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
                        wardSel.insertAdjacentHTML(
                            'beforeend',
                            `<option value="${w.id}">${w.name}</option>`
                        );
                    });
                });
        });
    }

    /* =====================================================
     * SUBMIT CHECKOUT (AJAX → ERP)
     * ===================================================== */
    const form   = document.getElementById('lx-checkout-form');
    const errBox = document.getElementById('lx-checkout-error');

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errBox.style.display = 'none';

        const fd = new FormData(form);

        // Resolve location / ward name (ERP cần NAME, không chỉ ID)
        const locationName = locSel?.selectedOptions[0]?.text || '';
        const wardName     = wardSel?.selectedOptions[0]?.text || '';

        // Snapshot cart được inject từ Blade
        const items = Array.isArray(window.__CHECKOUT_CART__)
            ? window.__CHECKOUT_CART__
            : [];

        if (!items.length) {
            errBox.innerText = 'Giỏ hàng không hợp lệ. Vui lòng quay lại giỏ hàng.';
            errBox.style.display = 'block';
            return;
        }

        const payload = {
            storefront: 'linxen',

            customer: {
                name: fd.get('name'),
                phone: fd.get('phone'),
                street: fd.get('street'),
                location_name: locationName,
                ward_name: wardName,
            },

            items: items,
        };

        try {
            // Disable submit để tránh double click
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn?.setAttribute('disabled', 'disabled');

            const res = await fetch('/api/storefront/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content'),
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const text = await res.text();
            let json;

            try {
                json = JSON.parse(text);
            } catch (e) {
                console.error('❌ API trả về không phải JSON:', text);
                errBox.innerText = 'Server lỗi (response không hợp lệ).';
                errBox.style.display = 'block';
                submitBtn?.removeAttribute('disabled');
                return;
            }

            if (!res.ok || !json.success) {
                errBox.innerText = json.message || `Lỗi server (${res.status})`;
                errBox.style.display = 'block';
                submitBtn?.removeAttribute('disabled');
                return;
            }

            // ✅ Thành công → sang trang chi tiết đơn hàng
            window.location.href = `/account/orders/${json.order_code}`;

        } catch (err) {
            console.error('🔥 Fetch error:', err);
            errBox.innerText = 'Không kết nối được server.';
            errBox.style.display = 'block';

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn?.removeAttribute('disabled');
        }
    });
});
