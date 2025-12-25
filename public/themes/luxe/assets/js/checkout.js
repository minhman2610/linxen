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