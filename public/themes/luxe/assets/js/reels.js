/**
 * =====================================================
 * LIN XÉN – PRODUCT REELS (SAFE INIT)
 * =====================================================
 * - Avoid global variable collision
 * - Wait for Swiper to be available
 * - Prevent double initialization
 */

(function () {

    // Tránh init 2 lần
    if (window.__LX_REELS_INITED__) {
        return;
    }
    window.__LX_REELS_INITED__ = true;

    function initReels() {

        // Nếu Swiper chưa load → chờ
        if (typeof window.Swiper === 'undefined') {
            setTimeout(initReels, 50);
            return;
        }

        /* ===============================
         * VERTICAL – PRODUCT REELS
         * =============================== */
        const verticalEl = document.querySelector('.reels-vertical');
        if (!verticalEl) return;

        const reelsVertical = new Swiper(verticalEl, {
            direction: 'vertical',
            slidesPerView: 1,
            resistanceRatio: 0,
            watchSlidesProgress: true,
        });

        /* ===============================
         * HORIZONTAL – IMAGES
         * =============================== */
        document.querySelectorAll('.reels-images').forEach(el => {

            // Tránh init lại swiper cũ
            if (el.classList.contains('swiper-initialized')) return;

            new Swiper(el, {
                direction: 'horizontal',
                slidesPerView: 1,
                nested: true,
                resistanceRatio: 0.6,
            });
        });

        /* ===============================
         * ADD TO CART
         * =============================== */
        document.querySelectorAll('.lx-btn-add-cart').forEach(btn => {

            btn.addEventListener('click', function () {

                fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        sku: btn.dataset.id,
                        name: btn.dataset.name,
                        price: btn.dataset.price,
                        image: btn.dataset.image,
                        qty: 1
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res?.success && document.getElementById('lxCartCount')) {
                        document.getElementById('lxCartCount').innerText = res.cart_count;
                    }
                });
            });
        });
    }

    // DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReels);
    } else {
        initReels();
    }

})();

(function () {

    let startY = 0;
    let pulling = false;

    document.addEventListener('touchstart', e => {
        if (window.reelsVertical?.activeIndex === 0) {
            startY = e.touches[0].clientY;
            pulling = true;
        }
    }, { passive: true });

    document.addEventListener('touchmove', e => {
        if (!pulling) return;

        const currentY = e.touches[0].clientY;
        const delta = currentY - startY;

        // Kéo xuống đủ xa → reload
        if (delta > 120) {
            pulling = false;
            window.location.reload();
        }
    }, { passive: true });

    document.addEventListener('touchend', () => {
        pulling = false;
    });

})();

