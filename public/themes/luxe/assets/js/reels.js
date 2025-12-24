/**
 * =====================================================
 * LIN XÉN – PRODUCT REELS (FINAL NESTED SWIPER FIX)
 * =====================================================
 * - Vertical reels + horizontal images
 * - NO black frame
 * - NO stuck on edge
 * - Stable nested swipe (iOS / Android)
 */

(function () {

    /* =====================================================
       GLOBAL GUARD
       ===================================================== */
    if (window.__LX_REELS_INITED__) return;
    window.__LX_REELS_INITED__ = true;

    let reelsVertical = null;

    /* =====================================================
       PRODUCT BAR SYNC
       ===================================================== */
    function updateProductBarFromIndex(swiper) {
        if (!swiper || !swiper.slides?.length) return;

        const slideEl = swiper.slides[swiper.activeIndex];
        if (!slideEl) return;

        const {
            id,
            sku,
            name,
            price,
            thumb,
            tag,
            available
        } = slideEl.dataset;

        const thumbEl = document.getElementById('lxReelsThumb');
        const nameEl  = document.getElementById('lxReelsName');
        const priceEl = document.getElementById('lxReelsPrice');
        const tagEl   = document.getElementById('lxReelsTag');
        const addBtn  = document.getElementById('lxReelsAddCart');

        if (thumbEl && thumb) thumbEl.src = thumb;
        if (nameEl) nameEl.textContent = name || '';
        if (priceEl && price) {
            priceEl.textContent = Number(price).toLocaleString('vi-VN') + '₫';
        }

        if (tagEl) {
            tagEl.textContent = tag || '';
            tagEl.style.display = tag ? 'inline-block' : 'none';
        }

        if (addBtn) {
            addBtn.dataset.id    = id;
            addBtn.dataset.sku   = sku;
            addBtn.dataset.name  = name;
            addBtn.dataset.price = price;
            addBtn.dataset.image = thumb;
            addBtn.disabled = Number(available) <= 0;
        }
    }

    /* =====================================================
       INIT
       ===================================================== */
    function initReels() {

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
            resistance: true,
            resistanceRatio: 0.85,

            on: {
                init: updateProductBarFromIndex,
                slideChange: updateProductBarFromIndex
            }
        });

        window.reelsVertical = reelsVertical;

        /* ---------- Horizontal image swipers (FIX TRIỆT ĐỂ) ---------- */
        document.querySelectorAll('.reels-images').forEach(el => {
            if (el.classList.contains('swiper-initialized')) return;

            const swiper = new Swiper(el, {
                direction: 'horizontal',
                slidesPerView: 1,
                nested: true,
                loop: false,

                /* 🔑 CHÌA KHOÁ FIX */
                resistance: true,
                resistanceRatio: 0.85,
                touchReleaseOnEdges: false,
                freeMode: false,
                watchOverflow: true,

                on: {
                    init(sw) {
                        if (sw.slides.length <= 1) {
                            sw.allowTouchMove = false;
                        }
                    },
                    reachEnd(sw) {
                        sw.setTranslate(sw.maxTranslate());
                        sw.allowTouchMove = true;
                    },
                    reachBeginning(sw) {
                        sw.setTranslate(sw.minTranslate());
                        sw.allowTouchMove = true;
                    }
                }
            });

            el.__swiper = swiper;
        });

        /* ---------- ADD TO CART ---------- */
        document.getElementById('lxReelsAddCart')
            ?.addEventListener('click', function () {

                const btn = this;
                if (btn.disabled) return;

                fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        sku: btn.dataset.sku,
                        name: btn.dataset.name,
                        price: btn.dataset.price,
                        image: btn.dataset.image,
                        qty: 1
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res?.success && document.getElementById('lxCartCount')) {
                        document.getElementById('lxCartCount').textContent =
                            res.cart_count;
                    }
                });
            });
    }

    /* =====================================================
       DOM READY
       ===================================================== */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReels);
    } else {
        initReels();
    }

})();
