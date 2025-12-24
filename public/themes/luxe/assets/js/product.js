/**
 * =====================================================
 * PRODUCT PAGE JS – LIN XÉN (LUXE THEME)
 * =====================================================
 * Author: Kai
 * Scope: Product Detail Page
 */

(function () {
    'use strict';

    /* =====================================================
     * 1️⃣ SWIPER – PRODUCT GALLERY
     * ===================================================== */
    function initProductSwiper() {
        if (typeof Swiper === 'undefined') return;

        const el = document.querySelector('.lx-product-swiper');
        if (!el) return;

        new Swiper(el, {
            slidesPerView: 1,
            spaceBetween: 0,
            loop: false,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
        });
    }

    /* =====================================================
     * 2️⃣ VARIANT SELECTION
     * ===================================================== */
    const selectedAttrs = {};

    function initVariants() {
        const options = document.querySelectorAll('.variant-option');

        options.forEach(option => {
            option.addEventListener('click', () => {
                const attrKey = option.dataset.attrKey;
                const value   = option.dataset.value;

                // Remove active in same group
                document
                    .querySelectorAll(`.variant-option[data-attr-key="${attrKey}"]`)
                    .forEach(el => el.classList.remove('active'));

                option.classList.add('active');
                selectedAttrs[attrKey] = value;

                updateStockInfo();
            });
        });
    }

    function updateStockInfo() {
        const stockEl = document.getElementById('lxStock');
        if (!stockEl) return;

        const selectedCount = Object.keys(selectedAttrs).length;
        const totalAttrs    = document.querySelectorAll('.lx-attr-group').length;

        if (selectedCount < totalAttrs) {
            stockEl.textContent = 'Vui lòng chọn đầy đủ biến thể';
            stockEl.classList.remove('in-stock');
            return;
        }

        // Hiện tại mock – sau này map variant thật từ ERP
        stockEl.textContent = '✔ Còn hàng – Giao nhanh';
        stockEl.classList.add('in-stock');
    }

    /* =====================================================
     * 3️⃣ QTY CONTROL
     * ===================================================== */
    window.changeQty = function (delta) {
        const input = document.getElementById('lxQty');
        if (!input) return;

        let val = parseInt(input.value || 1, 10);
        val += delta;

        if (val < 1) val = 1;
        input.value = val;
    };

    /* =====================================================
     * 4️⃣ ADD TO CART (AJAX)
     * ===================================================== */
    function initAddToCart() {
        const btn = document.getElementById('lxAddToCartBtn');
        if (!btn) return;

        btn.addEventListener('click', async () => {
            const qtyInput = document.getElementById('lxQty');
            const stockEl  = document.getElementById('lxStock');

            if (stockEl && !stockEl.classList.contains('in-stock')) {
                showToast('Vui lòng chọn đầy đủ biến thể');
                return;
            }

            const payload = {
                sku: buildSku(),
                name: document.querySelector('.lx-product-title')?.textContent?.trim(),
                price: extractPrice(),
                image: document.querySelector('.lx-product-swiper img')?.src || null,
                qty: parseInt(qtyInput?.value || 1, 10),
                attrs: selectedAttrs,
            };

            try {
                const res = await fetch('/linxen/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();

                if (data.success) {
                    showToast('Đã thêm vào giỏ hàng');
                    updateCartBadge(data.cart_count);
                } else {
                    showToast('Không thể thêm sản phẩm');
                }
            } catch (e) {
                showToast('Lỗi kết nối, vui lòng thử lại');
            }
        });
    }

    function buildSku() {
        // SKU giả lập: NAME + ATTRS
        const base = document.querySelector('.lx-product-title')?.textContent?.trim() || 'SKU';
        const attrs = Object.values(selectedAttrs).join('-');
        return attrs ? `${base}-${attrs}` : base;
    }

    function extractPrice() {
        const el = document.querySelector('.lx-product-price');
        if (!el) return 0;
        return parseInt(el.textContent.replace(/[^\d]/g, ''), 10) || 0;
    }

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    function updateCartBadge(count) {
        const badge = document.querySelector('[data-cart-count]');
        if (badge) badge.textContent = count;
    }

    /* =====================================================
     * 5️⃣ TOAST
     * ===================================================== */
    function showToast(message) {
        const toast = document.getElementById('lxToast');
        if (!toast) return;

        toast.textContent = message;
        toast.classList.add('show');

        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => {
            toast.classList.remove('show');
        }, 2200);
    }

    /* =====================================================
     * INIT ALL
     * ===================================================== */
    document.addEventListener('DOMContentLoaded', () => {
        initProductSwiper();
        initVariants();
        initAddToCart();
    });

})();
