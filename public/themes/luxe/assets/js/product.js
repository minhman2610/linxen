/**
 * =====================================================
 * PRODUCT PAGE JS – LIN XÉN (LUXE THEME)
 * =====================================================
 * Gallery: Progressive (thumb -> main)
 * No Swiper dependency
 */

(function () {
    'use strict';

    /* =====================================================
     * 1️⃣ GALLERY – MAIN IMAGE + THUMBS (PROGRESSIVE)
     * ===================================================== */
    function initGallery() {
    const mainImg = document.getElementById('lxMainImage');
    const thumbs  = document.querySelectorAll('#lxProductThumbs img');

    if (!mainImg || !thumbs.length) return;

    let currentIndex = 0;
    const total = thumbs.length;

    function loadImage(index) {
        if (index < 0 || index >= total) return;

        const thumb = thumbs[index];
        const fullSrc = thumb.dataset.full;

        thumbs.forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');

        const img = new Image();
        img.src = fullSrc;
        img.onload = () => {
            mainImg.src = fullSrc;
            mainImg.dataset.index = index;
            currentIndex = index;
        };
    }

    /* CLICK THUMB */
    thumbs.forEach(thumb => {
        thumb.addEventListener('click', () => {
            loadImage(parseInt(thumb.dataset.index, 10));
        });
    });

    /* SWIPE MAIN IMAGE */
    let startX = 0;
    let isSwiping = false;

    mainImg.addEventListener('touchstart', e => {
        startX = e.touches[0].clientX;
        isSwiping = true;
    }, { passive: true });

    mainImg.addEventListener('touchend', e => {
        if (!isSwiping) return;

        const endX = e.changedTouches[0].clientX;
        const diff = endX - startX;

        // threshold chống vuốt nhầm
        if (Math.abs(diff) < 40) return;

        if (diff < 0) {
            loadImage(currentIndex + 1); // swipe left
        } else {
            loadImage(currentIndex - 1); // swipe right
        }

        isSwiping = false;
    });

    /* preload ảnh đầu tiên */
    loadImage(0);
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

                // clear active cùng group
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

        const totalAttrs = document.querySelectorAll('.lx-attr-group').length;
        const selectedCount = Object.keys(selectedAttrs).length;

        if (selectedCount < totalAttrs) {
            stockEl.textContent = 'Vui lòng chọn đầy đủ biến thể';
            stockEl.classList.remove('in-stock');
            return;
        }

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
                image: document.getElementById('lxMainImage')?.src || null,
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
        const base =
            document.querySelector('.lx-product-title')?.textContent?.trim() || 'SKU';
        const attrs = Object.values(selectedAttrs).join('-');
        return attrs ? `${base}-${attrs}` : base;
    }

    function extractPrice() {
        const el = document.querySelector('.lx-product-price');
        if (!el) return 0;
        return parseInt(el.textContent.replace(/[^\d]/g, ''), 10) || 0;
    }

    function getCsrfToken() {
        return document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
    }

    function updateCartBadge(count) {
        const badge = document.getElementById('lxHeaderCartCount');
        if (badge && typeof count !== 'undefined') {
            badge.textContent = count;
        }
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
     * INIT
     * ===================================================== */
    document.addEventListener('DOMContentLoaded', () => {
        initGallery();
        initVariants();
        initAddToCart();
    });

})();
