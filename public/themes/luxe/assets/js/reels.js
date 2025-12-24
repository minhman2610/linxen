/**
 * =====================================================
 * LIN XÉN – PRODUCT REELS (BUGFIX VERSION)
 * =====================================================
 * Reload ONLY when:
 * 1. Touch STARTS at first slide (index 0)
 * 2. Swipe UP strongly
 * 3. Swiper is NOT animating
 */

(function () {

    if (window.__LX_REELS_INITED__) return;
    window.__LX_REELS_INITED__ = true;

    let reelsVertical = null;
    let isReloading = false;

    let touchStartY = 0;
    let startedAtFirstSlide = false;

    /* ===============================
       MINI LOADING
       =============================== */
    function showMiniLoading() {
        const el = document.getElementById('lxReelsMiniLoading');
        if (el) el.classList.add('active');
    }

    /* ===============================
       INIT SWIPERS
       =============================== */
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
                resistanceRatio: 0.6,
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReels);
    } else {
        initReels();
    }

    /* ===============================
       SMART RELOAD LOGIC (FIXED)
       =============================== */
    document.addEventListener('touchstart', e => {
        if (!reelsVertical || isReloading) return;

        touchStartY = e.touches[0].clientY;
        startedAtFirstSlide = reelsVertical.activeIndex === 0;
    }, { passive: true });

    document.addEventListener('touchmove', e => {
        if (
            !reelsVertical ||
            isReloading ||
            !startedAtFirstSlide ||
            reelsVertical.animating
        ) return;

        const currentY = e.touches[0].clientY;
        const deltaY = currentY - touchStartY;

        // CHỈ reload nếu vuốt LÊN rất mạnh
        if (deltaY < -120) {
            isReloading = true;
            showMiniLoading();

            setTimeout(() => {
                window.location.reload();
            }, 300);
        }

    }, { passive: true });

})();
