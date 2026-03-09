/**
 * HOME PAGE – Exchange animation
 * Theme: LUXE
 */

document.addEventListener("DOMContentLoaded", () => {

    const exchangeSection = document.querySelector(".lx-exchange");
    if (!exchangeSection) return;

    const steps = exchangeSection.querySelectorAll(".lx-ex-step");

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    exchangeSection.classList.add("is-visible");

                    steps.forEach((step, index) => {
                        setTimeout(() => {
                            step.classList.add("is-active");
                        }, index * 180);
                    });

                    observer.unobserve(exchangeSection); // chỉ chạy 1 lần
                }
            });
        },
        { threshold: 0.2 }
    );

    observer.observe(exchangeSection);

});
// =====================================================
// IMAGE ZOOM — TRUST VISUAL
// =====================================================
document.addEventListener('DOMContentLoaded', () => {

    const images = document.querySelectorAll('.lx-trust-image img');

    if (!images.length) return;

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'lx-image-zoom-overlay';

    const zoomImg = document.createElement('img');
    zoomImg.className = 'lx-image-zoom-img';

    overlay.appendChild(zoomImg);
    document.body.appendChild(overlay);

    images.forEach(img => {
        img.addEventListener('click', () => {
            zoomImg.src = img.src;
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    // Close on overlay click
    overlay.addEventListener('click', () => {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    });

    // Close on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

});

document.addEventListener('DOMContentLoaded', () => {

    const el = document.querySelector('.lx-flash-sale-countdown');
    if (!el) return;

    /*
    |--------------------------------------------------------------------------
    | END TIME
    |--------------------------------------------------------------------------
    */

    let endTime = el.dataset.endTime
        ? new Date(el.dataset.endTime.replace(' ', 'T')).getTime()
        : null;

    /*
    |--------------------------------------------------------------------------
    | Nếu endTime không hợp lệ → mặc định hết ngày
    |--------------------------------------------------------------------------
    */

    if (!endTime || isNaN(endTime)) {

        const now = new Date();
        now.setHours(23,59,59,999);
        endTime = now.getTime();

    }

    /*
    |--------------------------------------------------------------------------
    | ELEMENTS
    |--------------------------------------------------------------------------
    */

    const h = el.querySelector('[data-hours]');
    const m = el.querySelector('[data-minutes]');
    const s = el.querySelector('[data-seconds]');



    /*
    |--------------------------------------------------------------------------
    | FORMAT
    |--------------------------------------------------------------------------
    */

    const pad = n => String(n).padStart(2,'0');



    /*
    |--------------------------------------------------------------------------
    | TICK
    |--------------------------------------------------------------------------
    */

    const tick = () => {

        const now = Date.now();
        let diff = endTime - now;

        /*
        |--------------------------------------------------------------------------
        | Nếu countdown hết → reset về 24h (fake flash sale)
        |--------------------------------------------------------------------------
        */

        if (diff <= 0) {

            diff = 24 * 60 * 60 * 1000;
            endTime = now + diff;

        }

        const hours = Math.floor(diff / (1000 * 60 * 60));
        diff %= (1000 * 60 * 60);

        const minutes = Math.floor(diff / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);


        if (h) h.textContent = pad(hours);
        if (m) m.textContent = pad(minutes);
        if (s) s.textContent = pad(seconds);

    };



    /*
    |--------------------------------------------------------------------------
    | START
    |--------------------------------------------------------------------------
    */

    tick();
    setInterval(tick,1000);

});

