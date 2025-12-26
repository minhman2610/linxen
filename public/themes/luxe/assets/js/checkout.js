document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
     * ELEMENTS
     * ===================================================== */
    const form        = document.getElementById('lx-checkout-form');
    const errBox      = document.getElementById('lx-checkout-error');
    const phoneInput  = document.getElementById('lx-phone');
    const phoneStatus = document.getElementById('lx-phone-status');
    const nameInput   = document.getElementById('lx-name');

    const memberActionInput   = document.getElementById('member_action');
    const memberEmailInput    = document.getElementById('member_email');
    const memberPasswordInput = document.getElementById('member_password');

    if (!form || !phoneInput) return;

    let phoneChecked = false;
    let phoneState   = null; // existing | new
    let phoneTimer   = null;

    /* =====================================================
     * LOCATION → WARD (GIỮ NGUYÊN LOGIC CŨ)
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

        locSel.addEventListener('change', () => {
            const id = locSel.value;
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
     * MODAL HELPERS
     * ===================================================== */
    function getModal() {
        return document.getElementById('lx-member-modal');
    }

    function closeMemberModal() {
        const modal = getModal();
        if (modal) modal.classList.remove('is-active');
    }

    /* =====================================================
     * PHONE INPUT → CHECK ERP
     * ===================================================== */
    phoneInput.addEventListener('input', () => {
        phoneChecked = false;
        phoneState   = null;

        memberActionInput.value = '';
        memberEmailInput.value = '';
        memberPasswordInput.value = '';

        clearTimeout(phoneTimer);
        phoneStatus.style.display = 'none';
        closeMemberModal();

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
            console.log('[check-phone]', json);

            phoneChecked = true;

            const hasHistory = !!json.has_erp_history;
            const hasAccount = !!json.has_account;

            // KHÁCH CŨ + CÓ ACCOUNT
            if (hasHistory && hasAccount) {
                phoneState = 'existing';

                phoneStatus.className = 'lx-phone-status success';
                phoneStatus.innerText =
                    'Chào mừng bạn quay lại! Đăng nhập để tích lũy điểm.';

                showMemberModal('existing');
                return;
            }

            // KHÁCH MỚI
            phoneState = 'new';

            phoneStatus.className = 'lx-phone-status neutral';
            phoneStatus.innerText =
                'Tạo tài khoản để nhận ưu đãi, hoặc mua nhanh không đăng nhập.';

            if (json.name && !nameInput.value) {
                nameInput.value = json.name;
            }

            showMemberModal('new');

        } catch (e) {
            console.error('❌ check-phone error:', e);
            phoneStatus.className = 'lx-phone-status error';
            phoneStatus.innerText = 'Không kiểm tra được số điện thoại.';
        }
    }

    /* =====================================================
     * MEMBER MODAL
     * ===================================================== */
    function showMemberModal(type) {
        const modal = getModal();
        if (!modal) return;

        const modalTitle  = document.getElementById('lx-member-title');
        const modalDesc   = document.getElementById('lx-member-desc');
        const loginBox    = document.getElementById('lx-member-login');
        const registerBox = document.getElementById('lx-member-register');

        loginBox.style.display    = 'none';
        registerBox.style.display = 'none';

        if (type === 'existing') {
            modalTitle.innerText = 'Chào mừng bạn quay lại';
            modalDesc.innerText =
                'Đăng nhập để tích lũy điểm thành viên và nhận ưu đãi.';
            loginBox.style.display = 'block';
        }

        if (type === 'new') {
            modalTitle.innerText = 'Trở thành thành viên LIN XÉN';
            modalDesc.innerText =
                'Tạo tài khoản nhanh để nhận ưu đãi & tích điểm.';
            registerBox.style.display = 'block';
        }

        modal.classList.add('is-active');
    }

    /* =====================================================
     * MODAL ACTIONS
     * ===================================================== */
    document.getElementById('lx-member-confirm')?.addEventListener('click', async () => {

        // KHÁCH CŨ → LOGIN
        if (phoneState === 'existing') {
            const pwd = document.getElementById('lx-member-password').value;
            if (!pwd) {
                alert('Vui lòng nhập mật khẩu');
                return;
            }

            memberActionInput.value   = 'login';
            memberPasswordInput.value = pwd;
            closeMemberModal();
            return;
        }

        // KHÁCH MỚI → REGISTER + AUTO LOGIN
        if (phoneState === 'new') {
            const email = document.getElementById('lx-member-email').value;
            const pwd   = document.getElementById('lx-member-new-password').value;
            const pwd2  = document.getElementById('lx-member-new-password-confirm').value;

            if (!pwd || pwd.length < 6) {
                alert('Mật khẩu cần ít nhất 6 ký tự');
                return;
            }
            if (pwd !== pwd2) {
                alert('Mật khẩu nhập lại không khớp');
                return;
            }

            try {
                const res = await fetch('/ajax/register-inline', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content'),
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        phone: phoneInput.value.trim(),
                        email,
                        password: pwd,
                    }),
                });

                const json = await res.json();

                if (!json.success) {
                    alert(json.message || 'Không tạo được tài khoản');
                    return;
                }

                // ERP ĐÃ LOGIN → RELOAD CHECKOUT
                window.location.reload();

            } catch (e) {
                alert('Lỗi kết nối server');
            }
        }
    });

    document.getElementById('lx-member-skip')?.addEventListener('click', () => {
        memberActionInput.value = 'skip';
        closeMemberModal();
    });

    /* =====================================================
     * SUBMIT CHECKOUT
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
            errBox.innerText = 'Giỏ hàng không hợp lệ.';
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

            member: {
                action:   fd.get('member_action') || 'skip',
                email:    fd.get('member_email') || null,
                password: fd.get('member_password') || null,
            },

            items: items,
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
