/**
 * =====================================================
 * 🛒 CART – LIN XÉN
 * Add to cart (AJAX)
 * + Qty control
 * + Variant validate
 * + Rich product toast (bottom-right)
 * =====================================================
 */
(function () {

    /* ================= QTY CONTROL ================= */
    window.changeQty = function (delta) {
        const input = document.getElementById('lxQty');
        const btn   = document.getElementById('lxAddToCartBtn');
        if (!input || !btn) return;

        let value = parseInt(input.value || 1, 10);
        value = Math.max(1, value + delta);

        input.value = value;
        btn.dataset.qty = value;
    };

    document.addEventListener('input', function (e) {
        if (e.target.id !== 'lxQty') return;

        let value = parseInt(e.target.value || 1, 10);
        value = Math.max(1, value);
        e.target.value = value;

        const btn = document.getElementById('lxAddToCartBtn');
        if (btn) btn.dataset.qty = value;
    });

    /* ================= CART COUNT ================= */
    function updateCartCount(count) {
        document.querySelectorAll('.cart-count').forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
        });
    }

    /* =====================================================
     * ✅ VALIDATE VARIANTS
     * ===================================================== */
    function validateVariants() {

        const rows = document.querySelectorAll('.lx-variant-row');
        if (!rows.length) return true;

        let invalidRow = null;

        rows.forEach(row => {
            if (!row.dataset.selected && !invalidRow) {
                invalidRow = row;
            }
        });

        if (invalidRow) {

            invalidRow.classList.add('lx-variant-error');
            setTimeout(() => {
                invalidRow.classList.remove('lx-variant-error');
            }, 1200);

            invalidRow.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            const label =
                invalidRow.querySelector('.lx-variant-label')?.textContent
                || 'biến thể';

            showErrorToast(`Vui lòng chọn ${label.toLowerCase()}`);
            return false;
        }

        return true;
    }

    /* =====================================================
     * 🟢 RICH ADD-TO-CART TOAST
     * ===================================================== */
    function showAddToCartToast(product) {

        let box = document.querySelector('.lx-toast-container');
        if (!box) {
            box = document.createElement('div');
            box.className = 'lx-toast-container';
            document.body.appendChild(box);
        }

        const variantText = product.attrs && Object.keys(product.attrs).length
            ? Object.entries(product.attrs)
                .map(([k, v]) => `${v}`)
                .join(' · ')
            : '';

        const toast = document.createElement('div');
        toast.className = 'lx-toast-product';

        toast.innerHTML = `
            <div class="lx-toast-head">
                <span class="lx-toast-icon">✓</span>
                <span class="lx-toast-title">Đã thêm vào giỏ hàng</span>
            </div>

            <div class="lx-toast-body">
                <div class="lx-toast-thumb">
                    <img src="${product.image || '/images/no-image.png'}" alt="">
                </div>

                <div class="lx-toast-info">
                    <div class="lx-toast-name">${product.name}</div>

                    ${variantText ? `
                        <div class="lx-toast-variant">${variantText}</div>
                    ` : ''}

                    <div class="lx-toast-meta">
                        <span>x${product.qty}</span>
                        <strong>${product.total.toLocaleString()}₫</strong>
                    </div>
                </div>
            </div>
        `;

        box.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('show'));

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function showErrorToast(message) {
        let box = document.querySelector('.lx-toast-container');
        if (!box) {
            box = document.createElement('div');
            box.className = 'lx-toast-container';
            document.body.appendChild(box);
        }

        const toast = document.createElement('div');
        toast.className = 'lx-toast-product lx-toast-error';

        toast.innerHTML = `
            <div class="lx-toast-head">
                <span class="lx-toast-icon">!</span>
                <span class="lx-toast-title">${message}</span>
            </div>
        `;

        box.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('show'));

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 2200);
    }

    /* ================= ADD TO CART ================= */
    document.addEventListener('click', function (e) {

        const btn = e.target.closest('[data-add-to-cart]');
        if (!btn) return;

        e.preventDefault();

        if (!validateVariants()) return;

        const payload = {
            sku:   btn.dataset.sku,
            name:  btn.dataset.name,
            price: Number(btn.dataset.price || 0),
            image: btn.dataset.image || null,
            qty:   Number(btn.dataset.qty || 1),
            attrs: btn.dataset.attrs ? JSON.parse(btn.dataset.attrs) : {}
        };

        btn.classList.add('is-loading');
        btn.disabled = true;

        fetch('/cart/add', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {

                updateCartCount(res.cart_count);

                showAddToCartToast({
                    name:  payload.name,
                    image: payload.image,
                    qty:   payload.qty,
                    total: payload.price * payload.qty,
                    attrs: payload.attrs
                });

            } else {
                showErrorToast(res.message || 'Không thể thêm sản phẩm');
            }
        })
        .catch(() => {
            showErrorToast('Có lỗi xảy ra, vui lòng thử lại');
        })
        .finally(() => {
            btn.classList.remove('is-loading');
            btn.disabled = false;
        });
    });

})();
