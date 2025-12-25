/**
 * =====================================================
 * 🛒 CART – LIN XÉN
 * Add to cart (AJAX) + Qty control + Toast
 * =====================================================
 */
(function () {

    /* =====================================================
     * QTY CONTROL
     * ===================================================== */
    window.changeQty = function (delta) {
        const input = document.getElementById('lxQty');
        const btn   = document.getElementById('lxAddToCartBtn');
        if (!input || !btn) return;

        let value = parseInt(input.value || 1, 10);
        value = Math.max(1, value + delta);

        input.value = value;
        btn.dataset.qty = value;
    };

    // sync when user types manually
    document.addEventListener('input', function (e) {
        if (e.target.id !== 'lxQty') return;

        let value = parseInt(e.target.value || 1, 10);
        value = Math.max(1, value);

        e.target.value = value;

        const btn = document.getElementById('lxAddToCartBtn');
        if (btn) btn.dataset.qty = value;
    });

    /* =====================================================
     * TOAST NOTIFICATION (TOP RIGHT)
     * ===================================================== */
    function showToast(message, type = 'success') {

        let container = document.querySelector('.lx-toast-container');

        if (!container) {
            container = document.createElement('div');
            container.className = 'lx-toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `lx-toast lx-toast-${type}`;
        toast.innerHTML = `
            <span class="lx-toast-icon">✓</span>
            <span class="lx-toast-text">${message}</span>
        `;

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 2200);
    }

    /* =====================================================
     * UPDATE CART COUNT (BADGE)
     * ===================================================== */
    function updateCartCount(count) {
        document.querySelectorAll('.cart-count').forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
        });
    }

    /* =====================================================
     * ADD TO CART (AJAX)
     * ===================================================== */
    document.addEventListener('click', function (e) {

        const btn = e.target.closest('[data-add-to-cart]');
        if (!btn) return;

        e.preventDefault();

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
    credentials: 'same-origin', // ⚠️ rất quan trọng
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content')
    },
    body: JSON.stringify(payload)
})
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                updateCartCount(res.cart_count);
                showToast('Đã thêm vào giỏ hàng');
            } else {
                showToast('Không thể thêm sản phẩm', 'error');
            }
        })
        .catch(() => {
            showToast('Có lỗi xảy ra, vui lòng thử lại', 'error');
        })
        .finally(() => {
            btn.classList.remove('is-loading');
            btn.disabled = false;
        });
    });

})();
