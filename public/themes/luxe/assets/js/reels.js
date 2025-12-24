/**
 * =====================================================
 * LIN XÉN – REELS JS (SAFE VERSION – CSS DRIVEN)
 * =====================================================
 * ✔ KHÔNG can thiệp height
 * ✔ KHÔNG đo viewport
 * ✔ KHÔNG ép swiper-slide
 * ✔ Chỉ sync data + quản lý swipe
 */

(function () {

    if (window.__LX_REELS_INITED__) return;
    window.__LX_REELS_INITED__ = true;

    let reelsVertical = null;

    /* =====================================================
       PRODUCT BAR SYNC
       ===================================================== */
    function updateProductBar(swiper) {
        const slide = swiper?.slides?.[swiper.activeIndex];
        if (!slide) return;

        const d = slide.dataset;

        const setText = (id, val) => {
            const el = document.getElementById(id);
            if (el && val !== undefined) el.textContent = val;
        };

        const setImg = (id, src) => {
            const el = document.getElementById(id);
            if (el && src) el.src = src;
        };

        setImg('lxReelsThumb', d.thumb);
        setText('lxReelsName', d.name);
        setText('lxReelsDesc', d.desc);

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
            addBtn.dataset.id    = d.id || '';
            addBtn.dataset.sku   = d.sku || '';
            addBtn.dataset.name  = d.name || '';
            addBtn.dataset.price = d.price || '';
            addBtn.dataset.image = d.thumb || '';
            addBtn.disabled = Number(d.available) <= 0;
        }

        const detailLink = document.getElementById('lxReelsDetailLink');
        if (detailLink && d.url) {
            detailLink.href = d.url;
        }
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

        /* ---------- Vertical Swiper ---------- */
        reelsVertical = new Swiper(verticalEl, {
            direction: 'vertical',
            slidesPerView: 1,
            resistance: true,
            resistanceRatio: 0.85,
            nested: false,

            on: {
                init(sw) {
                    updateProductBar(sw);
                },
                slideChangeTransitionEnd(sw) {
                    updateProductBar(sw);
                }
            }
        });

        window.reelsVertical = reelsVertical;

        /* ---------- Horizontal Image Swipers ---------- */
        document.querySelectorAll('.reels-images').forEach(el => {

            if (el.classList.contains('swiper-initialized')) return;

            const slideCount =
                el.querySelectorAll('.swiper-slide').length;

            const swiper = new Swiper(el, {
                direction: 'horizontal',
                slidesPerView: 1,
                loop: false,
                nested: true,
                watchOverflow: true,

                resistance: true,
                resistanceRatio: 0.85,
                touchReleaseOnEdges: false,

                allowTouchMove: slideCount > 1,

                on: {
                    init(sw) {
                        if (sw.slides.length <= 1) {
                            sw.allowTouchMove = false;
                        }
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
                        const countEl =
                            document.getElementById('lxCartCount');
                        if (countEl && res.cart_count !== undefined) {
                            countEl.textContent = res.cart_count;
                        }
                    }
                })
                .catch(() => {});
            });
        }
    }

    /* =====================================================
       BOOT
       ===================================================== */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReels);
    } else {
        initReels();
    }

})();
