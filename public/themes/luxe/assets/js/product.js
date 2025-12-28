/* =====================================================
   PRODUCT GALLERY – SWIPER PRO (LIN XÉN)
   Scope: Gallery + Variant only (SAFE)
===================================================== */

document.addEventListener('DOMContentLoaded', () => {

    const mainEl  = document.querySelector('.lx-product-main-swiper');
    const thumbEl = document.querySelector('.lx-product-thumb-swiper');

    if (!mainEl) return;

    /* =====================================================
       THUMB SWIPER
    ===================================================== */
    let thumbSwiper = null;

    if (thumbEl) {
        thumbSwiper = new Swiper(thumbEl, {
            slidesPerView: 'auto',
            spaceBetween: 8,
            freeMode: true,
            watchSlidesProgress: true,
            watchOverflow: true,
            resistanceRatio: 0.85,
        });
    }

    /* =====================================================
       MAIN SWIPER
    ===================================================== */
    const mainSwiper = new Swiper(mainEl, {
        slidesPerView: 1,
        spaceBetween: 4,
        speed: 450,
        loop: false,
        effect: 'slide',

        followFinger: true,
        touchRatio: 1,
        resistanceRatio: 0.85,

        watchSlidesProgress: true,

        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },

        navigation: {
            nextEl: '.lx-gallery-next',
            prevEl: '.lx-gallery-prev',
        },

        thumbs: thumbSwiper ? { swiper: thumbSwiper } : undefined,
    });

    /* =====================================================
       PROGRESSIVE LOAD – MOBILE/THUMB → FULL
    ===================================================== */
    function progressiveLoad(swiper) {
        const slide = swiper.slides[swiper.activeIndex];
        if (!slide) return;

        const img = slide.querySelector('img');
        if (!img) return;

        const full = img.dataset.full;
        if (!full || img.dataset.loaded === '1') return;

        const loader = new Image();
        loader.src = full;

        loader.onload = () => {
            img.src = full;
            img.dataset.loaded = '1';
        };
    }

    /* =====================================================
       PRELOAD NEXT / PREV (ANTI JITTER)
    ===================================================== */
    function preloadNearby(swiper) {
        [swiper.activeIndex + 1, swiper.activeIndex - 1].forEach(i => {
            const slide = swiper.slides[i];
            if (!slide) return;

            const img = slide.querySelector('img');
            if (!img) return;

            const full = img.dataset.full;
            if (!full || img.dataset.preloaded === '1') return;

            const preload = new Image();
            preload.src = full;
            img.dataset.preloaded = '1';
        });
    }

    mainSwiper.on('slideChange', () => {
        progressiveLoad(mainSwiper);
        preloadNearby(mainSwiper);
    });

    progressiveLoad(mainSwiper);
    preloadNearby(mainSwiper);
});


/* =====================================================
   VARIANT SELECTOR – LIN XÉN
   One attribute = one active option
===================================================== */

document.addEventListener('click', function (e) {

    const option = e.target.closest('.variant-option');
    if (!option) return;

    const row = option.closest('.lx-variant-row');
    if (!row) return;

    // nếu option bị disable → không cho chọn
    if (option.classList.contains('disabled')) return;

    // clear trạng thái error (🔥 FIX KẸT SAU VALIDATE)
    row.classList.remove('lx-variant-error');

    // bỏ active cũ trong cùng attribute
    row.querySelectorAll('.variant-option.active')
        .forEach(btn => btn.classList.remove('active'));

    // set active mới
    option.classList.add('active');

    // lưu giá trị đã chọn để validate
    row.dataset.selected = option.dataset.value;

    // sync attrs cho Add to Cart
    syncSelectedVariants();
});


function syncSelectedVariants() {

    const attrs = {};
    let resolvedProductId = null;

    document.querySelectorAll('.lx-variant-row').forEach(row => {
        const key = row.dataset.attrKey;
        const val = row.dataset.selected;

        if (key && val) {
            attrs[key] = val;
        }

        // 🔥 CHỈ ROW SIZE MỚI QUYẾT ĐỊNH SKU
        if (key && key.toLowerCase() === 'size') {
            const activeOption = row.querySelector('.variant-option.active');
            if (activeOption && activeOption.dataset.productId) {
                resolvedProductId = activeOption.dataset.productId;
            }
        }
    });

    const btn = document.getElementById('lxAddToCartBtn');
    if (!btn) return;

    // attrs chỉ để hiển thị
    btn.dataset.attrs = JSON.stringify(attrs);

    // 🔑 SKU CON (SIZE)
    if (resolvedProductId) {
        btn.dataset.productId = resolvedProductId;
    } else {
        // 🚨 không có size → xoá product_id để BE chặn
        delete btn.dataset.productId;
    }
}


/**
 * =====================================================
 * 🛒 CART – LIN XÉN (FINAL NO-LOCK)
 * - Qty control
 * - Variant validate (KHÔNG khoá UI)
 * - AJAX add to cart
 * - Rich toast
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
     * ✅ VALIDATE VARIANTS – KHÔNG DISABLE GÌ CẢ
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
            }, 600);

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

    /* ================= TOAST ================= */

function ensureToastBox() {
    let box = document.querySelector('.lx-toast-container');
    if (!box) {
        box = document.createElement('div');
        box.className = 'lx-toast-container';
        document.body.appendChild(box);
    }
    return box;
}

function showAddToCartToast(product) {

    const box = ensureToastBox();

    const variantText = product.attrs && Object.keys(product.attrs).length
        ? Object.values(product.attrs).join(' · ')
        : '';

    const toast = document.createElement('div');
    toast.className = 'lx-toast-product';

    toast.innerHTML = `
        <!-- HEADER -->
        <div class="lx-toast-head">
            <div class="lx-toast-head-left">
                <span class="lx-toast-icon">✓</span>
                <span class="lx-toast-title">Đã thêm vào giỏ hàng</span>
            </div>

            <button class="lx-toast-close" aria-label="Đóng thông báo">✕</button>
        </div>

        <!-- BODY -->
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
                    <span class="lx-toast-qty">Số lượng: x${product.qty}</span>
                    <strong class="lx-toast-price">
                        ${product.total.toLocaleString()}₫
                    </strong>
                </div>
            </div>
        </div>

        <!-- FOOTER ACTIONS -->
        <div class="lx-toast-actions">
            <a href="/cart" class="lx-toast-btn lx-toast-btn-primary">
                Xem giỏ hàng
            </a>
            <button class="lx-toast-btn lx-toast-btn-ghost" data-toast-close>
                Tiếp tục mua
            </button>
        </div>
    `;

    box.appendChild(toast);

    /* ===== SHOW ANIMATION ===== */
    requestAnimationFrame(() => toast.classList.add('show'));

    /* ===== CLOSE HANDLERS ===== */
    const closeToast = () => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    };

    toast.querySelector('.lx-toast-close')
        ?.addEventListener('click', closeToast);

    toast.querySelector('[data-toast-close]')
        ?.addEventListener('click', closeToast);

    /* ===== AUTO CLOSE ===== */
    setTimeout(() => {
        if (!toast.isConnected) return;
        closeToast();
    }, 6000);
}



    function showErrorToast(message) {

    const box = ensureToastBox();

    const toast = document.createElement('div');
    toast.className = 'lx-toast-product lx-toast-error';

    toast.innerHTML = `
        <div class="lx-toast-head lx-toast-head-error">
            <div class="lx-toast-head-left">
                <span class="lx-toast-icon">!</span>
                <span class="lx-toast-title">${message.toUpperCase()}</span>
            </div>
        </div>
    `;

    box.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}


    /* ================= ADD TO CART (FIX SKU – FINAL) ================= */
document.addEventListener('click', function (e) {

    const btn = e.target.closest('[data-add-to-cart]');
    if (!btn) return;

    e.preventDefault();

    // Validate biến thể (UI)
    if (!validateVariants()) {
        btn.disabled = false;
        btn.classList.remove('is-loading');
        return;
    }

    // 🔥 FIX ĐÚNG: LẤY SKU CON TỪ OPTION ACTIVE CÓ data-product-id
    let productId = null;

    document.querySelectorAll('.variant-option.active').forEach(option => {
        if (option.dataset.productId) {
            productId = option.dataset.productId;
        }
    });

    // 🔴 BẮT BUỘC PHẢI CÓ SKU CON
    if (!productId || isNaN(productId)) {
        showErrorToast('Vui lòng chọn size trước khi thêm vào giỏ');
        return;
    }

    const payload = {
        sku:        btn.dataset.sku,
        product_id: Number(productId),     // ✅ SKU CON
        name:       btn.dataset.name,
        price:      Number(btn.dataset.price || 0),
        image:      btn.dataset.image || null,
        qty:        Number(btn.dataset.qty || 1),
        attrs:      btn.dataset.attrs
            ? JSON.parse(btn.dataset.attrs)
            : {}
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
                ?.getAttribute('content')
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
