/**
 * =====================================================
 * LIN XÉN – REELS (FINAL SAFE FIX)
 * =====================================================
 * ✔ KHÔNG ép height swiper-slide
 * ✔ CHỈ khóa image frame theo viewport
 * ✔ Không còn nửa ảnh / nửa ảnh
 */

(function () {

    if (window.__LX_REELS_INITED__) return;
    window.__LX_REELS_INITED__ = true;

    let reelsVertical = null;

    /* =====================================================
       VIEWPORT HEIGHT (CHUẨN)
       ===================================================== */
    function getImageViewportHeight() {
        const header = document.querySelector('.lx-reels-header');
        const bar    = document.querySelector('.lx-reels-product-bar');

        const headerH = header ? header.offsetHeight : 0;
        const barH    = bar ? bar.offsetHeight : 0;

        return window.innerHeight - headerH - barH;
    }

    /* =====================================================
       🔒 CHỈ KHÓA IMAGE – KHÔNG ĐỤNG SLIDE
       ===================================================== */
    function lockImageHeightOnly() {
        const h = getImageViewportHeight();
        if (!h || h < 200) return;

        document.querySelectorAll('.reels-image-frame').forEach(frame => {
            frame.style.height = h + 'px';
            frame.style.maxHeight = h + 'px';
        });
    }

    function bindImageLoad() {
        document.querySelectorAll('.reels-image-frame img').forEach(img => {
            if (img.complete) {
                lockImageHeightOnly();
            } else {
                img.addEventListener('load', lockImageHeightOnly, { once: true });
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
                    lockImageHeightOnly();
                    bindImageLoad();
                },
                slideChangeTransitionEnd(sw) {
                    updateProductBar(sw);
                    lockImageHeightOnly();
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
                        lockImageHeightOnly();
                    },
                    slideChangeTransitionEnd() {
                        lockImageHeightOnly();
                    }
                }
            });
        });

        window.addEventListener('resize', () => {
            setTimeout(lockImageHeightOnly, 80);
        });
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', init)
        : init();

})();
