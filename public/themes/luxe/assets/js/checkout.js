document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
     * ELEMENTS
     * ===================================================== */
    const form        = document.getElementById('lx-checkout-form');
    const errBox      = document.getElementById('lx-checkout-error');
    const phoneInput  = document.getElementById('lx-phone');
    const phoneStatus = document.getElementById('lx-phone-status');
    const pwdWrap     = document.getElementById('lx-login-password');
    const nameInput   = document.getElementById('lx-name');

    if (!form || !phoneInput) return;

    let phoneChecked = false;
    let phoneState   = null; // has_account | has_profile | new

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
     * CHECK PHONE (AJAX – MODEL 2)
     * ===================================================== */
    let phoneTimer = null;

    phoneInput.addEventListener('input', () => {
        phoneChecked = false;
        phoneState   = null;
        phoneStatus.style.display = 'none';
        pwdWrap.style.display = 'none';
        clearTimeout(phoneTimer);

        const phone = phoneInput.value.trim();

        if (phone.length < 9) return;

        phoneTimer = setTimeout(() => checkPhone(phone), 500);
    });

    async function checkPhone(phone) {
        phoneStatus.style.display = 'block';
        phoneStatus.className = 'lx-phone-status info';
        phoneStatus.innerText = 'Đang kiểm tra số điện thoại…';

        try {
            const res = await fetch('/ajax/check-phone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content'),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ phone }),
            });

            const json = await res.json();

            phoneChecked = true;

            /* -------------------------------
             * CASE 1: ĐÃ CÓ ACCOUNT
             * ----------------------------- */
            if (json.has_account) {
                phoneState = 'has_account';

                phoneStatus.className = 'lx-phone-status success';
                phoneStatus.innerText =
                    'Số điện thoại này đã có tài khoản. Vui lòng nhập mật khẩu để đăng nhập.';

                pwdWrap.style.display = 'block';
                return;
            }

            /* -------------------------------
             * CASE 2: CÓ DỮ LIỆU CŨ (ERP)
             * ----------------------------- */
            if (json.has_profile || json.has_erp_history) {
                phoneState = 'has_profile';

                phoneStatus.className = 'lx-phone-status hint';
                phoneStatus.innerText =
                    'Chúng tôi đã tìm thấy thông tin mua hàng trước đây của bạn.';

                if (json.name && !nameInput.value) {
                    nameInput.value = json.name;
                }

                return;
            }

            /* -------------------------------
             * CASE 3: KHÁCH MỚI
             * ----------------------------- */
            phoneState = 'new';
            phoneStatus.className = 'lx-phone-status neutral';
            phoneStatus.innerText =
                'Bạn có thể tiếp tục đặt hàng nhanh, hoặc tạo tài khoản sau.';

        } catch (e) {
            console.error('❌ check-phone error:', e);
            phoneStatus.className = 'lx-phone-status error';
            phoneStatus.innerText = 'Không kiểm tra được số điện thoại.';
        }
    }

    /* =====================================================
     * SUBMIT CHECKOUT (AJAX → /api/storefront/orders)
     * ===================================================== */
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errBox.style.display = 'none';

        if (!phoneChecked) {
            errBox.innerText = 'Vui lòng nhập số điện thoại hợp lệ.';
            errBox.style.display = 'block';
            return;
        }

        const fd = new FormData(form);

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
                name:   fd.get('name'),
                phone:  fd.get('phone'),
                street: fd.get('street'),

                location_id: locSel?.value || null,
                ward_id:     wardSel?.value || null,

                location_name: locSel?.selectedOptions[0]?.text || '',
                ward_name:     wardSel?.selectedOptions[0]?.text || '',
                note: fd.get('note') || null,
            },

            auth: phoneState === 'has_account'
                ? { password: fd.get('password') }
                : null,

            items: items.map(i => ({
                product_id: i.product_id,
                qty:        i.qty,
                price:      i.price,
                note:       i.note || null,
            })),
        };

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn?.setAttribute('disabled', 'disabled');

        try {
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

            const json = await res.json();

            if (!res.ok || !json.success) {
                errBox.innerText = json.message || 'Lỗi xử lý đơn hàng.';
                errBox.style.display = 'block';
                submitBtn?.removeAttribute('disabled');
                return;
            }

            // ✅ Redirect success
            window.location.href =
                `/checkout/place-order?order_code=${json.order_code}`;

        } catch (err) {
            console.error('🔥 Checkout error:', err);
            errBox.innerText = 'Không kết nối được server.';
            errBox.style.display = 'block';
            submitBtn?.removeAttribute('disabled');
        }
    });
});
