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
    const mainWrap = document.getElementById('lxProductMain');
    const mainImg  = document.getElementById('lxMainImage');
    const thumbs   = document.querySelectorAll('#lxProductThumbs img');
    const btnPrev  = document.getElementById('lxGalleryPrev');
    const btnNext  = document.getElementById('lxGalleryNext');

    if (!mainImg || !thumbs.length) return;

    let currentIndex = 0;
    const total = thumbs.length;

    let startX = 0;
    let currentX = 0;
    let isDragging = false;

    function loadImage(index, animate = true) {
        if (index < 0 || index >= total) return;

        const thumb = thumbs[index];
        const fullSrc = thumb.dataset.full;

        thumbs.forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');

        const img = new Image();
        img.src = fullSrc;
        img.onload = () => {
            if (animate) {
                mainImg.style.transition = 'transform .25s ease, opacity .25s ease';
                mainImg.style.opacity = 0;
                mainImg.style.transform = 'translateX(20px)';
            }

            requestAnimationFrame(() => {
                mainImg.src = fullSrc;
                mainImg.dataset.index = index;
                currentIndex = index;

                mainImg.style.opacity = 1;
                mainImg.style.transform = 'translateX(0)';
            });
        };
    }

    /* CLICK THUMB */
    thumbs.forEach(thumb => {
        thumb.addEventListener('click', () => {
            loadImage(parseInt(thumb.dataset.index, 10));
        });
    });

    /* BUTTONS */
    btnPrev?.addEventListener('click', () => {
        loadImage(currentIndex - 1);
    });

    btnNext?.addEventListener('click', () => {
        loadImage(currentIndex + 1);
    });

    /* SWIPE WITH DRAG EFFECT */
    mainWrap.addEventListener('touchstart', e => {
        startX = e.touches[0].clientX;
        currentX = startX;
        isDragging = true;
        mainImg.style.transition = 'none';
    }, { passive: true });

    mainWrap.addEventListener('touchmove', e => {
        if (!isDragging) return;

        currentX = e.touches[0].clientX;
        const deltaX = currentX - startX;

        mainImg.style.transform = `translateX(${deltaX}px)`;
    }, { passive: true });

    mainWrap.addEventListener('touchend', () => {
        if (!isDragging) return;

        const deltaX = currentX - startX;
        isDragging = false;

        mainImg.style.transition = 'transform .25s ease';

        if (Math.abs(deltaX) > 60) {
            if (deltaX < 0) {
                loadImage(currentIndex + 1, false);
            } else {
                loadImage(currentIndex - 1, false);
            }
        } else {
            mainImg.style.transform = 'translateX(0)';
        }
    });

    /* INIT */
    loadImage(0, false);
}
function slideTo(index, direction = 1) {
    if (index < 0 || index >= total || index === currentIndex) return;

    const nextSrc = thumbs[index].dataset.full;

    const nextImg = document.createElement('img');
    nextImg.src = nextSrc;
    nextImg.className = 'lx-slide-img';
    nextImg.style.transform = `translateX(${direction * 100}%)`;

    mainWrap.appendChild(nextImg);

    requestAnimationFrame(() => {
        mainImg.style.transition = 'transform .35s cubic-bezier(.4,0,.2,1)';
        nextImg.style.transition = 'transform .35s cubic-bezier(.4,0,.2,1)';

        mainImg.style.transform = `translateX(${-direction * 100}%)`;
        nextImg.style.transform = 'translateX(0)';
    });

    nextImg.onload = () => {
        setTimeout(() => {
            mainImg.src = nextSrc;
            mainImg.style.transition = 'none';
            mainImg.style.transform = 'translateX(0)';

            mainWrap.removeChild(nextImg);
            currentIndex = index;

            thumbs.forEach(t => t.classList.remove('active'));
            thumbs[index].classList.add('active');
        }, 350);
    };
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
