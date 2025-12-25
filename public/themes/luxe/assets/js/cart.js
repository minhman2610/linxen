/**
 * =====================================================
 * CART PAGE JS – LIN XÉN
 * Scope: /cart only
 * =====================================================
 */

(function () {

    /**
     * Helper: POST JSON với CSRF (ANTI 302)
     */
    async function post(url, payload = {}) {
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json', // 🔥 BẮT BUỘC
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok) {
            throw new Error(`Request failed: ${res.status}`);
        }

        return res.json();
    }

    /**
     * Disable buttons trong card (tránh double click)
     */
    function setCardLoading(card, loading = true) {
        if (!card) return;

        card.classList.toggle('is-loading', loading);
        card.querySelectorAll('button').forEach(btn => {
            btn.disabled = loading;
        });
    }

    /**
     * =================================================
     * UPDATE QTY (+ / -)
     * =================================================
     */
    window.updateQty = async function (sku, delta) {

        const card = document.querySelector(`.lx-cart-card[data-sku="${sku}"]`);
        if (!card) return;

        setCardLoading(card, true);

        try {
            await post('/cart/update', {
                sku: sku,
                delta: delta
            });

            // Session là source of truth → reload an toàn
            window.location.reload();

        } catch (e) {
            console.error('❌ Update qty error:', e);
            alert('Không thể cập nhật số lượng. Vui lòng thử lại.');
            setCardLoading(card, false);
        }
    };

    /**
 * =================================================
 * REMOVE ITEM – CONFIRM POPUP (SAFE)
 * =================================================
 */

let pendingRemoveSku = null;

/**
 * Show confirm popup
 */
function showConfirmRemove(sku) {
    if (!sku || typeof sku !== 'string') {
        console.warn('❌ showConfirmRemove called with invalid SKU:', sku);
        alert('Lỗi: Không xác định được sản phẩm cần xoá.');
        return;
    }

    pendingRemoveSku = sku;

    document.getElementById('lxConfirmOverlay')
        ?.classList.add('show');
}

/**
 * Hide confirm popup
 */
function hideConfirmRemove() {
    pendingRemoveSku = null;

    document.getElementById('lxConfirmOverlay')
        ?.classList.remove('show');
}

/**
 * =================================================
 * EVENT BINDINGS
 * =================================================
 */

// Click nút xoá trên từng item (event delegation)
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.lx-cart-remove');
    if (!btn) return;

    const sku = btn.dataset.sku;

    if (!sku) {
        console.error('❌ Missing data-sku on remove button', btn);
        alert('Lỗi dữ liệu sản phẩm. Vui lòng tải lại trang.');
        return;
    }

    showConfirmRemove(sku);
});

// Cancel
document.getElementById('lxConfirmCancel')
    ?.addEventListener('click', hideConfirmRemove);

// OK – thực sự xoá
document.getElementById('lxConfirmOk')
    ?.addEventListener('click', async () => {

        if (!pendingRemoveSku) {
            console.warn('❌ Confirm clicked but pendingRemoveSku is empty');
            return;
        }

        const skuToRemove = pendingRemoveSku;
        hideConfirmRemove();

        try {
            await post('/cart/remove', {
                sku: skuToRemove
            });

            // Session là source of truth
            window.location.reload();

        } catch (e) {
            console.error('❌ Remove item error:', e);
            alert('Không thể xoá sản phẩm. Vui lòng thử lại.');
        }
    });



})();
