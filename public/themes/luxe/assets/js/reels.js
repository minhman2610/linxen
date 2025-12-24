/**
 * =====================================================
 * LIN XÉN – PRODUCT REELS (FINAL ABSOLUTE FIX)
 * =====================================================
 * ✔ Khóa height theo viewport thật
 * ✔ Ép lại swiper-slide (chặn Swiper tự set 880px)
 * ✔ Không tràn product bar – 100%
 */

(function () {

    if (window.__LX_REELS_INITED__) return;
    window.__LX_REELS_INITED__ = true;

    let reelsVertical = null;

    /* =====================================================
       VIEWPORT HEIGHT (SINGLE SOURCE OF TRUTH)
       ===================================================== */
    function getViewportHeight() {
        const header = document.querySelector('.lx-reels-header');
        const bar    = document.querySelector('.lx-reels-product-bar');

        const headerH = header ? header.offsetHeight : 0;
        const barH    = bar ? bar.offsetHeight : 0;

        return window.innerHeight - headerH - barH;
    }

    /* =====================================================
       🔒 LOCK SLIDE + IMAGE HEIGHT (CORE FIX)
       ===================================================== */
    function lockAllHeights() {
        const h = getViewportHeight();
        if (!h || h < 300) return;

        /* 1️⃣ Ép lại swiper-slide */
        document.querySelectorAll('.reels-slide').forEach(slide => {
            slide.style.height = h + 'px';
            slide.style.maxHeight = h + 'px';
            slide.style.overflow = 'hidden';
        });

        /* 2️⃣ Ép image frame */
        document.querySelectorAll('.reels-image-frame').forEach(frame => {
            frame.style.height = h + 'px';
            frame.style.maxHeight = h + 'px';
        });
    }

    /* =====================================================
       IMAGE LOAD BIND
       ===================================================== */
    function bindImageLoad() {
        document.querySelectorAll('.reels-image-frame img').forEach(img => {
            if (img.complete) {
                lockAllHeights();
            } else {
                img.addEventListener('load', lockAllHeights, { once: true });
            }
        });
    }

    /* =====================================================
       PRODUCT BAR SYNC
       ===================================================== */
    function updateProductBar(swiper) {
        const slide = swiper?.slides?.[swiper.activeIndex];
        if (!slide) return;

        const d = slide.dataset;

        const set = (id, val, isImg = false) => {
            const el = document.getElementById(id);
            if (!el || val === undefined) return;
            isImg ? el.src = val : el.textContent = val;
        };

        set('lxReelsThumb', d.thumb, true);
        set('lxReelsName', d.name);
        set('lxReelsDesc', d.desc);

        const priceEl = document.getElementById('lxReelsPrice');
        if (priceEl && d.price) {
            priceEl.textContent =
                Number(d.price).toLocaleString('vi-VN') + '₫';
        }

        const tagEl = document.getElementById('lxReelsTag');
        if (tagEl) {
            tagEl.textContent = d.tag || '';
            tagEl.style.display = d.tag ? 'inline-block' : 'none';
        }

        const addBtn = document.getElementById('lxReelsAddCart');
        if (addBtn) {
            Object.assign(addBtn.dataset, {
                id: d.id || '',
                sku: d.sku || '',
                name: d.name || '',
                price: d.price || '',
                image: d.thumb || ''
            });
            addBtn.disabled = Number(d.available) <= 0;
        }

        const link = document.getElementById('lxReelsDetailLink');
        if (link) link.href = d.url || '#';
    }

    /* =====================================================
       INIT
       ===================================================== */
    function init() {
        if (!window.Swiper) {
            setTimeout(init, 50);
            return;
        }

        const verticalEl = document.querySelector('.reels-vertical');
        if (!verticalEl) return;

        reelsVertical = new Swiper(verticalEl, {
            direction: 'vertical',
            slidesPerView: 1,
            resistance: true,
            resistanceRatio: 0.85,

            on: {
                init(sw) {
                    updateProductBar(sw);
                    lockAllHeights();
                    bindImageLoad();
                },
                slideChangeTransitionEnd(sw) {
                    updateProductBar(sw);
                    lockAllHeights();
                }
            }
        });

        window.reelsVertical = reelsVertical;

        /* Horizontal swipers */
        document.querySelectorAll('.reels-images').forEach(el => {
            if (el.classList.contains('swiper-initialized')) return;

            new Swiper(el, {
                direction: 'horizontal',
                slidesPerView: 1,
                loop: false,
                nested: true,
                watchOverflow: true,

                on: {
                    init() {
                        lockAllHeights();
                    },
                    slideChangeTransitionEnd() {
                        lockAllHeights();
                    }
                }
            });
        });

        /* Resize / rotate */
        window.addEventListener('resize', () => {
            setTimeout(lockAllHeights, 100);
        });
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', init)
        : init();

})();
