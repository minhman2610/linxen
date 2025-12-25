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

    // nếu bị disable thì không cho chọn
    if (option.classList.contains('disabled')) return;

    // bỏ active trong cùng attribute
    row.querySelectorAll('.variant-option.active')
        .forEach(btn => btn.classList.remove('active'));

    // set active cho option được chọn
    option.classList.add('active');

    // lưu giá trị đã chọn
    row.dataset.selected = option.dataset.value;

    syncSelectedVariants();
});

/* =====================================================
   SYNC VARIANTS → ADD TO CART DATA
===================================================== */
function syncSelectedVariants() {

    const attrs = {};

    document.querySelectorAll('.lx-variant-row').forEach(row => {
        const key = row.dataset.attrKey;
        const val = row.dataset.selected;

        if (key && val) {
            attrs[key] = val;
        }
    });

    const btn = document.getElementById('lxAddToCartBtn');
    if (btn) {
        btn.dataset.attrs = JSON.stringify(attrs);
    }
}
