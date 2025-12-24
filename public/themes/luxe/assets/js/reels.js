/**
 * =====================================================
 * LIN XÉN – PRODUCT REELS (FINAL – CORRECT HEIGHT LOCK)
 * =====================================================
 * - KHÓA HEIGHT THEO .reels-images (KHÔNG PHẢI wrapper)
 * - KHÔNG TRÀN PRODUCT BAR
 * - ỔN ĐỊNH iOS / ANDROID
 */

(function () {

    if (window.__LX_REELS_INITED__) return;
    window.__LX_REELS_INITED__ = true;

    let reelsVertical = null;

    /* =====================================================
       PRODUCT BAR SYNC
       ===================================================== */
    function updateProductBarFromIndex(swiper) {
        const slideEl = swiper?.slides?.[swiper.activeIndex];
        if (!slideEl) return;

        const d = slideEl.dataset;

        const map = {
            lxReelsThumb: d.thumb,
            lxReelsName: d.name,
            lxReelsDesc: d.desc
        };

        Object.entries(map).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el && val !== undefined) {
                if (el.tagName === 'IMG') el.src = val;
                else el.textContent = val;
            }
        });

        const priceEl = document.getElementById('lxReelsPrice');
        if (priceEl && d.price) {
            priceEl.textContent = Number(d.price).toLocaleString('vi-VN') + '₫';
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

        const linkEl = document.getElementById('lxReelsDetailLink');
        if (linkEl) linkEl.href = d.url || '#';
    }

    /* =====================================================
       🔒 CORRECT HEIGHT LOCK
       ===================================================== */
    function lockImageHeight() {
        document.querySelectorAll('.reels-images').forEach(images => {
            const rect = images.getBoundingClientRect();
            const h = Math.floor(rect.height);

            if (!h || h < 100) return;

            images.querySelectorAll('.reels-image-frame').forEach(frame => {
                frame.style.height = h + 'px';
                frame.style.maxHeight = h + 'px';
            });
        });
    }

    function bindImageLoad() {
        document.querySelectorAll('.reels-image-frame img').forEach(img => {
            if (img.complete) {
                lockImageHeight();
            } else {
                img.addEventListener('load', lockImageHeight, { once: true });
            }
        });
    }

    /* =====================================================
       INIT
       ===================================================== */
    function initReels() {

        if (!window.Swiper) {
            setTimeout(initReels, 50);
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
                    updateProductBarFromIndex(sw);
                    lockImageHeight();
                    bindImageLoad();
                },
                slideChangeTransitionEnd(sw) {
                    updateProductBarFromIndex(sw);
                    lockImageHeight();
                }
            }
        });

        window.reelsVertical = reelsVertical;

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
                        lockImageHeight();
                    },
                    slideChangeTransitionEnd() {
                        lockImageHeight();
                    }
                }
            });
        });

        window.addEventListener('resize', () => {
            setTimeout(lockImageHeight, 100);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReels);
    } else {
        initReels();
    }

})();
