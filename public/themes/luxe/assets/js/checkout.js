document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
     * ELEMENTS
     * ===================================================== */
    const form        = document.getElementById('lx-checkout-form');
    const errBox      = document.getElementById('lx-checkout-error');
    const errText     = document.getElementById('lx-checkout-error-text');

    const phoneInput  = document.getElementById('lx-phone');
    const phoneStatus = document.getElementById('lx-phone-status');
    const nameInput   = document.getElementById('lx-name');

    const locSel  = document.getElementById('lx-location');
    const wardSel = document.getElementById('lx-ward');

    const memberActionInput   = document.getElementById('member_action');
    const memberEmailInput    = document.getElementById('member_email');
    const memberPasswordInput = document.getElementById('member_password');
    const memberConfirmInput  = document.getElementById('member_password_confirm');

    const memberConfirmBtn = document.getElementById('lx-member-confirm');
    const memberSkipBtn    = document.getElementById('lx-member-skip');
    const memberErrorBox   = document.getElementById('lx-member-error');

    const shippingAddressIdInput =
        document.getElementById('lx-shipping-address-id');

    const submitBtn = document.getElementById('lx-checkout-submit');

    if (!form) return;

    /* =====================================================
     * STATE
     * ===================================================== */
    let phoneChecked = false;
    let phoneState   = null; // member | guest_existing | new | logged | address_mode
    let phoneTimer   = null;

    const hasAddressMode = !!shippingAddressIdInput;

    /* =====================================================
     * INIT MODE
     * ===================================================== */
    if (hasAddressMode) {
        phoneChecked = true;
        phoneState   = 'address_mode';
    }

    if (phoneInput && phoneInput.hasAttribute('readonly') && phoneInput.value) {
        phoneChecked = true;
        phoneState   = 'logged';
        if (phoneStatus) phoneStatus.style.display = 'none';
    }

    /* =====================================================
     * LOCATION → WARD
     * ===================================================== */
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
     * MEMBER MODAL HELPERS
     * ===================================================== */
    function getModal() {
        return document.getElementById('lx-member-modal');
    }

    function showMemberModal(type) {
        const modal = getModal();
        if (!modal) return;

        const title  = document.getElementById('lx-member-title');
        const desc   = document.getElementById('lx-member-desc');
        const login  = document.getElementById('lx-member-login');
        const reg    = document.getElementById('lx-member-register');

        login.style.display = 'none';
        reg.style.display   = 'none';

        if (type === 'existing') {
            title.innerText = '👋 Chào mừng bạn quay lại';
            desc.innerText  = 'Đăng nhập để tích lũy điểm và hưởng quyền lợi thành viên LIN XÉN.';
            login.style.display = 'block';
        }

        if (type === 'guest' || type === 'new') {
            title.innerText = 'Trở thành thành viên LIN XÉN';
            desc.innerText  = 'Tạo tài khoản nhanh để nhận ưu đãi và tích lũy quyền lợi.';
            reg.style.display = 'block';
        }

        modal.classList.add('is-active');
        document.body.style.overflow = 'hidden';
    }

    function closeMemberModal() {
        const modal = getModal();
        if (!modal) return;
        modal.classList.remove('is-active');
        document.body.style.overflow = '';
        clearMemberError();
    }

    /* =====================================================
     * MEMBER ERROR HELPERS (GIỮ NGUYÊN CODE CŨ)
     * ===================================================== */
    function showMemberError(msg) {
        if (!memberErrorBox) return;
        memberErrorBox.innerText = msg;
        memberErrorBox.style.display = 'block';
    }

    function clearMemberError() {
        if (!memberErrorBox) return;
        memberErrorBox.innerText = '';
        memberErrorBox.style.display = 'none';
    }

    /* =====================================================
     * PHONE INPUT → CHECK ERP
     * ===================================================== */
    if (phoneInput) {
        phoneInput.addEventListener('input', () => {

            phoneChecked = false;

            memberActionInput.value   = '';
            memberEmailInput.value    = '';
            memberPasswordInput.value = '';
            memberConfirmInput && (memberConfirmInput.value = '');

            clearTimeout(phoneTimer);
            if (phoneStatus) phoneStatus.style.display = 'none';

            // 🔥 không đóng modal nếu đã là member
            if (phoneState !== 'member') {
                closeMemberModal();
            }

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

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const json = await res.json();
            phoneChecked = true;

            const hasAccount    = !!json.has_account;
            const hasErpHistory = !!json.has_erp_history;
            const customerType  =
                json.customer_type || (hasAccount ? 'member' : 'guest');
            console.log('CHECK PHONE RESULT:', json);
console.log('customerType:', customerType);
console.log('hasAccount:', hasAccount);

            /* ===== MEMBER ===== */
            if (customerType === 'member' && hasAccount) {
                phoneState = 'member';
                phoneInput.setAttribute('readonly', 'readonly');

                phoneStatus.className = 'lx-phone-status success';
                phoneStatus.innerText =
                    'Chào mừng bạn quay lại! Đăng nhập để hưởng quyền lợi thành viên.';

                showMemberModal('existing');
                return;
            }

            /* ===== KHÁCH CŨ CHƯA CÓ ACCOUNT ===== */
            if (!hasAccount && hasErpHistory) {
                phoneState = 'guest_existing';

                phoneStatus.className = 'lx-phone-status info';
                phoneStatus.innerText =
                    'Bạn đã từng mua hàng. Có thể tạo tài khoản hoặc mua nhanh.';

                if (json.name && nameInput && !nameInput.value) {
                    nameInput.value = json.name;
                }

                showMemberModal('guest');
                return;
            }

            /* ===== KHÁCH MỚI ===== */
            phoneState = 'new';
            phoneStatus.className = 'lx-phone-status neutral';
            phoneStatus.innerText =
                'Tạo tài khoản để nhận ưu đãi, hoặc mua nhanh không cần đăng nhập.';

            showMemberModal('new');

        } catch (e) {
            console.error('🔥 CHECK PHONE ERROR:', e);
            phoneChecked = false;
            phoneState   = null;
            phoneStatus.className = 'lx-phone-status error';
            phoneStatus.innerText =
                'Không kiểm tra được số điện thoại. Vui lòng thử lại.';
        }
    }

    /* =====================================================
     * MEMBER CONFIRM (LOGIN / REGISTER INLINE) – GIỮ 100%
     * ===================================================== */
    memberConfirmBtn?.addEventListener('click', async () => {
        clearMemberError();

        const email = memberEmailInput.value.trim();
        const pwd   = memberPasswordInput.value.trim();
        const pwd2  = memberConfirmInput?.value?.trim();

        if (!email || !pwd) {
            showMemberError('Vui lòng nhập đầy đủ thông tin.');
            return;
        }

        // REGISTER
        if (phoneState === 'new' || phoneState === 'guest_existing') {

            if (pwd.length < 6) {
                showMemberError('Mật khẩu phải từ 6 ký tự.');
                return;
            }

            if (memberConfirmInput && pwd !== pwd2) {
                showMemberError('Mật khẩu xác nhận không khớp.');
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

                if (!res.ok || !json.success) {
                    showMemberError(json.message || 'Không thể đăng ký.');
                    return;
                }

                memberActionInput.value = 'register';
                closeMemberModal();

            } catch (e) {
                showMemberError('Không kết nối được server.');
            }

            return;
        }

        // LOGIN
        if (phoneState === 'member') {
            memberActionInput.value   = 'login';
            closeMemberModal();
        }
    });

    memberSkipBtn?.addEventListener('click', () => {
        memberActionInput.value = 'skip';
        closeMemberModal();
    });

    /* =====================================================
     * LOADING STATE
     * ===================================================== */
    function setCheckoutLoading(isLoading) {
        if (!submitBtn) return;

        const spinner = submitBtn.querySelector('.lx-btn-spinner');
        const text    = submitBtn.querySelector('.lx-btn-text');

        if (isLoading) {
            submitBtn.setAttribute('disabled', 'disabled');
            if (spinner) spinner.style.display = 'inline-block';
            if (text) text.innerText =
                submitBtn.dataset.textLoading || 'Đang xử lý đơn hàng…';
        } else {
            submitBtn.removeAttribute('disabled');
            if (spinner) spinner.style.display = 'none';
            if (text) text.innerText =
                submitBtn.dataset.textDefault || 'ĐẶT HÀNG';
        }
    }

    /* =====================================================
     * SUBMIT CHECKOUT – GIỮ 100% LOGIC CŨ
     * ===================================================== */
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (errBox) errBox.style.display = 'none';

        const fd = new FormData(form);
        const shippingAddressId = fd.get('shipping_address_id');

        if (!shippingAddressId && !phoneChecked) {
            errText.innerText = 'Vui lòng nhập số điện thoại hợp lệ.';
            errBox.style.display = 'block';
            return;
        }

        const items = Array.isArray(window.__CHECKOUT_CART__)
            ? window.__CHECKOUT_CART__
            : [];

        if (!items.length) {
            errText.innerText = 'Giỏ hàng không hợp lệ.';
            errBox.style.display = 'block';
            return;
        }

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
                    location_name:
                        locSel?.selectedOptions[0]?.text || '',
                    ward_name:
                        wardSel?.selectedOptions[0]?.text || '',
                    note: fd.get('note') || null,
                },
            member: {
                action: fd.get('member_action') || 'skip',
                email: fd.get('member_email') || null,
                password: fd.get('member_password') || null,
            },
            items,
        };

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

            const json = await res.json();

            if (!res.ok || !json.success) {
                throw new Error(
                    json.message || 'ERP không tạo được đơn hàng.'
                );
            }

            window.location.href =
                `/checkout/place-order?order_code=${json.order_code}`;

        } catch (err) {
            console.error('🔥 CHECKOUT ERROR:', err);

            setCheckoutLoading(false);

            errText.innerText =
                err.message || 'Có lỗi xảy ra khi đặt hàng.';
            errBox.style.display = 'block';
            errBox.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }
    });

});
