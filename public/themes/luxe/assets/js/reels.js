/**
 * =====================================================
 * LIN XÉN – PRODUCT REELS (FINAL – HEIGHT LOCK)
 * =====================================================
 * - Vertical reels + horizontal images
 * - Fix image overflow under product bar (runtime)
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
        if (!swiper || !swiper.slides || !swiper.slides.length) return;

        const slideEl = swiper.slides[swiper.activeIndex];
        if (!slideEl) return;

        const {
            id,
            sku,
            name,
            price,
            thumb,
            tag,
            available,
            desc,
            url
        } = slideEl.dataset;

        const thumbEl  = document.getElementById('lxReelsThumb');
        const nameEl   = document.getElementById('lxReelsName');
        const priceEl  = document.getElementById('lxReelsPrice');
        const tagEl    = document.getElementById('lxReelsTag');
        const descEl   = document.getElementById('lxReelsDesc');
        const addBtn   = document.getElementById('lxReelsAddCart');
        const linkEl   = document.getElementById('lxReelsDetailLink');

        if (thumbEl && thumb) thumbEl.src = thumb;
        if (nameEl)  nameEl.textContent  = name || '';
        if (descEl)  descEl.textContent  = desc || '';

        if (priceEl && price) {
            priceEl.textContent =
                Number(price).toLocaleString('vi-VN') + '₫';
        }

        if (tagEl) {
            tagEl.textContent = tag || '';
            tagEl.style.display = tag ? 'inline-block' : 'none';
        }

        if (addBtn) {
            addBtn.dataset.id    = id || '';
            addBtn.dataset.sku   = sku || '';
            addBtn.dataset.name  = name || '';
            addBtn.dataset.price = price || '';
            addBtn.dataset.image = thumb || '';
            addBtn.disabled = Number(available) <= 0;
        }

        if (linkEl) {
            linkEl.href = url || '#';
        }
    }

    /* =====================================================
       🔒 LOCK IMAGE HEIGHT – CORE FIX
       ===================================================== */
    function lockReelsImageHeight() {
        const wrapper = document.querySelector('.lx-reels-wrapper');
        if (!wrapper) return;

        const rect = wrapper.getBoundingClientRect();
        const maxHeight = Math.round(rect.height);

        document.querySelectorAll('.reels-image-frame').forEach(frame => {
            frame.style.height = maxHeight + 'px';
            frame.style.maxHeight = maxHeight + 'px';
        });
    }

    function bindImageLoadLock() {
        document.querySelectorAll('.reels-image-frame img').forEach(img => {
            if (img.complete) {
                lockReelsImageHeight();
            } else {
                img.addEventListener('load', lockReelsImageHeight, { once: true });
            }
        });
    }

    /* =====================================================
       INIT REELS
       ===================================================== */
    function initReels() {

        if (typeof window.Swiper === 'undefined') {
            setTimeout(initReels, 50);
            return;
        }

        const verticalEl = document.querySelector('.reels-vertical');
        if (!verticalEl) return;

        /* ---------- Vertical Swiper ---------- */
        reelsVertical = new Swiper(verticalEl, {
            direction: 'vertical',
            slidesPerView: 1,
            resistance: true,
            resistanceRatio: 0.85,
            nested: false,

            on: {
                init(sw) {
                    updateProductBarFromIndex(sw);
                    lockReelsImageHeight();
                    bindImageLoadLock();
                },
                slideChangeTransitionEnd(sw) {
                    updateProductBarFromIndex(sw);
                    lockReelsImageHeight();
                }
            }
        });

        window.reelsVertical = reelsVertical;

        /* ---------- Horizontal Image Swipers ---------- */
        document.querySelectorAll('.reels-images').forEach(el => {
            if (el.classList.contains('swiper-initialized')) return;

            const swiper = new Swiper(el, {
                direction: 'horizontal',
                slidesPerView: 1,
                loop: false,
                nested: true,

                resistance: true,
                resistanceRatio: 0.85,
                touchReleaseOnEdges: false,
                watchOverflow: true,
                freeMode: false,

                on: {
                    init(sw) {
                        if (sw.slides.length <= 1) {
                            sw.allowTouchMove = false;
                        }
                        lockReelsImageHeight();
                    },
                    slideChangeTransitionEnd() {
                        lockReelsImageHeight();
                    }
                }
            });

            el.__swiper = swiper;
        });

        /* ---------- ADD TO CART ---------- */
        const addCartBtn = document.getElementById('lxReelsAddCart');
        if (addCartBtn) {
            addCartBtn.addEventListener('click', function () {

                if (this.disabled) return;

                fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        sku:   this.dataset.sku,
                        name:  this.dataset.name,
                        price: this.dataset.price,
                        image: this.dataset.image,
                        qty:   1
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res?.success) {
                        const countEl = document.getElementById('lxCartCount');
                        if (countEl && res.cart_count !== undefined) {
                            countEl.textContent = res.cart_count;
                        }
                    }
                })
                .catch(() => {});
            });
        }

        /* ---------- RESIZE / ORIENTATION ---------- */
        window.addEventListener('resize', () => {
            setTimeout(lockReelsImageHeight, 80);
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
