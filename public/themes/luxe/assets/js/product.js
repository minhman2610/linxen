/* =====================================================
   PRODUCT GALLERY – SWIPER PRO (LIN XÉN)
   Scope: Gallery only (SAFE)
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

        // 👇 Drag feel (rất quan trọng)
        followFinger: true,
        touchRatio: 1,
        resistanceRatio: 0.85,
        touchReleaseOnEdges: true,

        // 👇 GPU + progress
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

        on: {
            init(swiper) {
                progressiveLoad(swiper);
                preloadNearby(swiper);
            },

            slideChangeTransitionStart(swiper) {
                progressiveLoad(swiper);
                preloadNearby(swiper);
            },
        },
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
       PRELOAD NEXT / PREV (CHỐNG GIẬT)
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

});
