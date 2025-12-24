/**
 * =====================================================
 * LIN XÉN – PRODUCT REELS (STABLE – NO RELOAD)
 * =====================================================
 * FEATURES:
 * - Sync product bar on slide change
 * - Vertical reels + horizontal images
 * - Add to cart from product bar
 * - NO reload on swipe (disabled completely)
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
    function updateProductBarFromSlide(slideEl) {
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
        if (nameEl) nameEl.innerText = name || '';

        if (priceEl && price) {
            priceEl.innerText =
                Number(price).toLocaleString('vi-VN') + '₫';
        }

        if (tagEl) {
            tagEl.innerText = tag || '';
            tagEl.style.display = tag ? 'inline-block' : 'none';
        }

        // Bind add-to-cart data
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
       INIT SWIPERS
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
            resistanceRatio: 0,

            on: {
                init() {
                    const firstSlide =
                        verticalEl.querySelector('.swiper-slide-active');
                    updateProductBarFromSlide(firstSlide);
                },
                slideChange() {
                    const activeSlide =
                        verticalEl.querySelector('.swiper-slide-active');
                    updateProductBarFromSlide(activeSlide);
                }
            }
        });

        window.reelsVertical = reelsVertical;

        /* ---------- Horizontal image swipers ---------- */
        document.querySelectorAll('.reels-images').forEach(el => {
            if (el.classList.contains('swiper-initialized')) return;

            new Swiper(el, {
                direction: 'horizontal',
                slidesPerView: 1,
                nested: true,
                resistanceRatio: 0.6,
            });
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
                        document.getElementById('lxCartCount').innerText =
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
