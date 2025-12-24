/**
 * =====================================================
 * LIN XÉN – PRODUCT REELS (FINAL)
 * =====================================================
 * RULE:
 * - Normal swipe: browse products
 * - Swipe to first product (index 0): OK
 * - Try to swipe ABOVE first product → SHOW LOADING → RELOAD
 */

(function () {

    /* =====================================================
       GLOBAL GUARD
       ===================================================== */
    if (window.__LX_REELS_INITED__) return;
    window.__LX_REELS_INITED__ = true;

    let reelsVertical = null;
    let isReloading   = false;

    /* =====================================================
       LOADING
       ===================================================== */
    function showLoading() {
        const el = document.getElementById('lxReelsLoading');
        if (el) el.classList.add('active');
    }

    /* =====================================================
       INIT SWIPERS
       ===================================================== */
    function initReels() {

        // Chờ Swiper load
        if (typeof window.Swiper === 'undefined') {
            setTimeout(initReels, 50);
            return;
        }

        const verticalEl = document.querySelector('.reels-vertical');
        if (!verticalEl) return;

        /* ---------- Vertical reels ---------- */
        reelsVertical = new Swiper(verticalEl, {
            direction: 'vertical',
            slidesPerView: 1,
            resistanceRatio: 0, // không bounce
        });

        // expose (debug / future use)
        window.reelsVertical = reelsVertical;

        /* ---------- Horizontal images ---------- */
        document.querySelectorAll('.reels-images').forEach(el => {
            if (el.classList.contains('swiper-initialized')) return;

            new Swiper(el, {
                direction: 'horizontal',
                slidesPerView: 1,
                nested: true,
                resistanceRatio: 0.6,
            });
        });
    }

    // DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReels);
    } else {
        initReels();
    }

    /* =====================================================
       RELOAD LOGIC – ONLY WHEN TRYING TO GO ABOVE FIRST
       ===================================================== */
    let startY = 0;

    document.addEventListener('touchstart', e => {
        if (!reelsVertical || isReloading) return;
        startY = e.touches[0].clientY;
    }, { passive: true });

    document.addEventListener('touchmove', e => {
        if (!reelsVertical || isReloading) return;

        const currentY = e.touches[0].clientY;
        const deltaY   = currentY - startY;

        /**
         * deltaY < 0  → vuốt LÊN
         * activeIndex === 0 → đang ở sản phẩm đầu
         * deltaY < -80 → cố tình vuốt ngược
         */
        if (
            reelsVertical.activeIndex === 0 &&
            deltaY < -80
        ) {
            isReloading = true;

            showLoading();

            // delay nhẹ cho UX
            setTimeout(() => {
                window.location.reload();
            }, 300);
        }

    }, { passive: true });

})();
