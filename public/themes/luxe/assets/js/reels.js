/**
 * =====================================================
 * LIN XÉN – PRODUCT REELS
 * SAFE INIT + SMART RELOAD + LOADING
 * =====================================================
 */

(function () {

    if (window.__LX_REELS_INITED__) return;
    window.__LX_REELS_INITED__ = true;

    let reelsVertical = null;
    let isReloading = false;

    function showLoading() {
        const el = document.getElementById('lxReelsLoading');
        if (el) el.classList.add('active');
    }

    function initReels() {

        if (typeof window.Swiper === 'undefined') {
            setTimeout(initReels, 50);
            return;
        }

        const verticalEl = document.querySelector('.reels-vertical');
        if (!verticalEl) return;

        reelsVertical = new Swiper(verticalEl, {
            direction: 'vertical',
            slidesPerView: 1,
            resistanceRatio: 0,
        });

        window.reelsVertical = reelsVertical;

        document.querySelectorAll('.reels-images').forEach(el => {
            if (el.classList.contains('swiper-initialized')) return;

            new Swiper(el, {
                direction: 'horizontal',
                slidesPerView: 1,
                nested: true,
            });
        });

        // ADD TO CART
        document.getElementById('lxReelsAddCart')
            ?.addEventListener('click', function () {

                const btn = this;

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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReels);
    } else {
        initReels();
    }

    /* ===============================
       SMART PULL DOWN
       =============================== */
    let startY = 0;
    let pulling = false;
    let backToTopTriggered = false;

    document.addEventListener('touchstart', e => {
        if (!reelsVertical || isReloading) return;
        startY = e.touches[0].clientY;
        pulling = true;
        backToTopTriggered = false;
    }, { passive: true });

    document.addEventListener('touchmove', e => {
        if (!pulling || !reelsVertical || isReloading) return;

        const delta = e.touches[0].clientY - startY;
        if (delta < 80) return;

        // 1️⃣ Chưa ở đầu → kéo về đầu
        if (reelsVertical.activeIndex > 0 && !backToTopTriggered) {
            backToTopTriggered = true;
            reelsVertical.slideTo(0, 300);
            return;
        }

        // 2️⃣ Ở đầu → reload + loading
        if (reelsVertical.activeIndex === 0 && delta > 140) {
            isReloading = true;
            showLoading();

            setTimeout(() => {
                window.location.reload();
            }, 300);
        }
    }, { passive: true });

    document.addEventListener('touchend', () => {
        pulling = false;
    });

})();
