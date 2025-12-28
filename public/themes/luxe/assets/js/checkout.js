document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
     * ELEMENTS
     * ===================================================== */
    const form        = document.getElementById('lx-checkout-form');
    const errBox      = document.getElementById('lx-checkout-error');
    const phoneInput  = document.getElementById('lx-phone'); // ⚠️ có thể null
    const phoneStatus = document.getElementById('lx-phone-status');
    const nameInput   = document.getElementById('lx-name');

    const memberActionInput   = document.getElementById('member_action');
    const memberEmailInput    = document.getElementById('member_email');
    const memberPasswordInput = document.getElementById('member_password');

    // ❗ CHỈ CHECK FORM – KHÔNG CHECK phoneInput
    if (!form) return;

    /* =====================================================
     * STATE
     * ===================================================== */
    let phoneChecked = false;
    let phoneState   = null; // member | new | logged | address_mode
    let phoneTimer   = null;

    const hasAddressMode = !!document.getElementById('lx-shipping-address-id');

    /* =====================================================
     * AUTO VALIDATION MODE
     * ===================================================== */
    if (hasAddressMode) {
        // Có địa chỉ → bỏ qua phone hoàn toàn
        phoneChecked = true;
        phoneState   = 'address_mode';
    }

    if (phoneInput && phoneInput.hasAttribute('readonly') && phoneInput.value) {
        phoneChecked = true;
        phoneState   = 'logged';
        if (phoneStatus) phoneStatus.style.display = 'none';
    }

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
     * PHONE INPUT → CHECK ERP (CHỈ KHI CÓ PHONE INPUT)
     * ===================================================== */
    if (phoneInput) {
        phoneInput.addEventListener('input', () => {
            phoneChecked = false;
            phoneState   = null;

            memberActionInput.value   = '';
            memberEmailInput.value    = '';
            memberPasswordInput.value = '';

            clearTimeout(phoneTimer);
            if (phoneStatus) phoneStatus.style.display = 'none';
            closeMemberModal();

            const phone = phoneInput.value.trim();
            if (phone.length < 9) return;

            phoneTimer = setTimeout(() => checkPhone(phone), 500);
        });
    }

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

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const json = await res.json();
        phoneChecked = true;

        const hasAccount    = !!json.has_account;
        const hasErpHistory = !!json.has_erp_history;
        const customerType  = json.customer_type || (hasAccount ? 'member' : 'guest');

        /* =====================================================
         * CASE 1️⃣: MEMBER – BẬT NHANH MODAL LOGIN
         * ===================================================== */
        if (customerType === 'member' && hasAccount) {
            phoneState = 'member';

            phoneStatus.className = 'lx-phone-status success';
            phoneStatus.innerText =
                'Chào mừng bạn quay lại! Đăng nhập để tận hưởng đầy đủ quyền lợi thành viên.';

            // cấu hình modal
            document.getElementById('lx-member-title').innerText =
                '👋 Chào mừng bạn quay lại!';
            document.getElementById('lx-member-desc').innerText =
                'Đăng nhập để tích lũy điểm, theo dõi đơn hàng và hưởng ưu đãi dành riêng cho thành viên LIN XÉN.';

            // hiển thị login
            showMemberModal('existing');

            // ⚡ cho phép mua nhanh không login
            document.getElementById('lx-member-skip').style.display = 'inline-flex';
            document.getElementById('lx-member-skip').innerText =
                '⚡ Mua nhanh không cần đăng nhập';

            return;
        }

        /* =====================================================
         * CASE 2️⃣: KHÁCH CŨ – CHƯA CÓ ACCOUNT
         * ===================================================== */
        if (!hasAccount && hasErpHistory) {
            phoneState = 'guest_existing';

            phoneStatus.className = 'lx-phone-status info';
            phoneStatus.innerText =
                'Bạn đã từng mua hàng. Có thể tạo tài khoản để hưởng thêm quyền lợi.';

            if (json.name && nameInput && !nameInput.value) {
                nameInput.value = json.name;
            }

            showMemberModal('guest');
            return;
        }

        /* =====================================================
         * CASE 3️⃣: KHÁCH MỚI
         * ===================================================== */
        phoneState = 'new';

        phoneStatus.className = 'lx-phone-status neutral';
        phoneStatus.innerText =
            'Tạo tài khoản để nhận ưu đãi, hoặc mua nhanh không cần đăng nhập.';

        showMemberModal('new');

    } catch (e) {
        console.error('🔥 CHECK PHONE ERROR:', e);
        phoneChecked = false;
        phoneState = null;
        phoneStatus.className = 'lx-phone-status error';
        phoneStatus.innerText =
            'Không kiểm tra được số điện thoại. Vui lòng thử lại.';
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
            modalDesc.innerText = 'Đăng nhập để tiếp tục';
            loginBox.style.display = 'block';
        }

        if (type === 'new') {
            modalTitle.innerText = 'Trở thành thành viên LIN XÉN';
            modalDesc.innerText = 'Tạo tài khoản nhanh để nhận ưu đãi';
            registerBox.style.display = 'block';
        }

        modal.classList.add('is-active');
    }

    function showMemberError(msg) {
        const box = document.getElementById('lx-member-error');
        if (!box) return;
        box.innerText = msg;
        box.style.display = 'block';
    }

    function clearMemberError() {
        const box = document.getElementById('lx-member-error');
        if (!box) return;
        box.innerText = '';
        box.style.display = 'none';
    }

    /* =====================================================
     * MODAL ACTIONS
     * ===================================================== */
    document.getElementById('lx-member-confirm')?.addEventListener('click', async () => {

        clearMemberError();

        if (phoneState === 'member') {
            const pwd = document.getElementById('lx-member-password')?.value;
            if (!pwd) {
                showMemberError('⚠️ Vui lòng nhập mật khẩu');
                return;
            }
            memberActionInput.value   = 'login';
            memberPasswordInput.value = pwd;
            closeMemberModal();
            return;
        }

        if (phoneState === 'new') {
            const pwd  = document.getElementById('lx-member-new-password')?.value;
            const pwd2 = document.getElementById('lx-member-new-password-confirm')?.value;

            if (!pwd || pwd.length < 6) {
                showMemberError('⚠️ Mật khẩu ít nhất 6 ký tự');
                return;
            }
            if (pwd !== pwd2) {
                showMemberError('⚠️ Mật khẩu không khớp');
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
                        phone: phoneInput?.value?.trim(),
                        password: pwd,
                    }),
                });

                const json = await res.json();
                if (!json.success) {
                    showMemberError(json.message || 'Không tạo được tài khoản');
                    return;
                }

                window.location.href = '/checkout?registered=1';

            } catch {
                showMemberError('Lỗi kết nối server');
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

    const fd = new FormData(form);
    const shippingAddressId = fd.get('shipping_address_id');

    // =========================
    // PHONE VALIDATION – CHỈ KHI KHÔNG CÓ ADDRESS
    // =========================
    if (!shippingAddressId && !phoneChecked) {
        errBox.innerText = 'Vui lòng nhập số điện thoại hợp lệ.';
        errBox.style.display = 'block';
        return;
    }

    // =========================
    // CART CHECK
    // =========================
    const items = Array.isArray(window.__CHECKOUT_CART__)
        ? window.__CHECKOUT_CART__
        : [];

    if (!items.length) {
        errBox.innerText = 'Giỏ hàng không hợp lệ.';
        errBox.style.display = 'block';
        return;
    }

    // =========================
    // BUILD PAYLOAD
    // =========================
    const payload = {
        storefront: 'linxen',
        customer: shippingAddressId
            ? {
                shipping_address_id: shippingAddressId,
                note: fd.get('note') || null,
            }
            : {
                name: fd.get('name'),
                phone: fd.get('phone'),
                street: fd.get('street'),
                location_id: locSel?.value || null,
                ward_id: wardSel?.value || null,
                location_name: locSel?.selectedOptions[0]?.text || '',
                ward_name: wardSel?.selectedOptions[0]?.text || '',
                note: fd.get('note') || null,
            },
        member: {
            action: fd.get('member_action') || 'skip',
            email: fd.get('member_email') || null,
            password: fd.get('member_password') || null,
        },
        items,
    };

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn?.setAttribute('disabled', 'disabled');

    // =========================
    // SAFE FETCH (HANDLE 500 / NON-JSON)
    // =========================
    try {
        setCheckoutLoading(true);
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

        let json = null;
        try {
            json = text ? JSON.parse(text) : null;
        } catch {
            json = null;
        }

        // ❗ BẮT MỌI LỖI HTTP (500, 502, 403...)
        if (!res.ok) {
            throw new Error(
                json?.message ||
                `Hệ thống đang bận (HTTP ${res.status}). Vui lòng thử lại.`
            );
        }

        if (!json || !json.success) {
            throw new Error(
                json?.message || 'Tạo đơn hàng thất bại.'
            );
        }

        // =========================
        // SUCCESS
        // =========================
        window.location.href =
            `/checkout/place-order?order_code=${json.order_code}`;

    } catch (err) {
        setCheckoutLoading(false);
        console.error('🔥 CHECKOUT ERROR:', err);

        errBox.innerText =
            err?.message ||
            'Không thể kết nối server. Vui lòng thử lại sau.';

        errBox.style.display = 'block';
        submitBtn?.removeAttribute('disabled');
    }
});
function setCheckoutLoading(isLoading) {
    const btn = document.getElementById('lx-checkout-submit');
    if (!btn) return;

    const spinner = btn.querySelector('.lx-btn-spinner');
    const text    = btn.querySelector('.lx-btn-text');

    if (isLoading) {
        btn.setAttribute('disabled', 'disabled');
        spinner.style.display = 'inline-block';
        text.innerText = btn.dataset.textLoading || 'Đang xử lý…';
    } else {
        btn.removeAttribute('disabled');
        spinner.style.display = 'none';
        text.innerText = btn.dataset.textDefault || 'ĐẶT HÀNG';
    }
}

});

/* =====================================================
 * ADDRESS PICKER MODAL
 * ===================================================== */
function openAddressPopup() {
    document.getElementById('lx-address-modal').style.display = 'block';
}

function closeAddressPopup() {
    document.getElementById('lx-address-modal').style.display = 'none';
}

function confirmAddressPick() {
    const selected = document.querySelector('input[name="address_pick"]:checked');
    if (!selected) return;

    document.querySelector('input[name="shipping_address_id"]').value = selected.value;

    document.querySelector('.lx-address-default .lx-address-head strong').innerText =
        selected.dataset.name;

    document.querySelector('.lx-address-default .lx-address-head span').innerText =
        selected.dataset.phone;

    document.querySelector('.lx-address-default .lx-address-text').innerText =
        selected.dataset.address;

    closeAddressPopup();
}
