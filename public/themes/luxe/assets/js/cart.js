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
                'Accept': 'application/json',          // 🔥 CỰC KỲ QUAN TRỌNG
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
     * REMOVE ITEM
     * =================================================
     */
    window.removeItem = async function (sku) {

        if (!confirm('Bạn muốn xoá sản phẩm này khỏi giỏ hàng?')) {
            return;
        }

        const card = document.querySelector(`.lx-cart-card[data-sku="${sku}"]`);
        if (!card) return;

        setCardLoading(card, true);

        try {
            await post('/cart/remove', {
                sku: sku
            });

            window.location.reload();

        } catch (e) {
            console.error('❌ Remove item error:', e);
            alert('Không thể xoá sản phẩm. Vui lòng thử lại.');
            setCardLoading(card, false);
        }
    };

})();
