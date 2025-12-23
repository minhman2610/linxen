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

    const endTime = new Date(el.dataset.endTime.replace(' ', 'T')).getTime();

    const d = el.querySelector('[data-days]');
    const h = el.querySelector('[data-hours]');
    const m = el.querySelector('[data-minutes]');
    const s = el.querySelector('[data-seconds]');

    const tick = () => {
        const now = Date.now();
        let diff = Math.max(0, endTime - now);

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        diff %= 1000 * 60 * 60 * 24;

        const hours = Math.floor(diff / (1000 * 60 * 60));
        diff %= 1000 * 60 * 60;

        const minutes = Math.floor(diff / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        d.textContent = String(days).padStart(2, '0');
        h.textContent = String(hours).padStart(2, '0');
        m.textContent = String(minutes).padStart(2, '0');
        s.textContent = String(seconds).padStart(2, '0');
    };

    tick();
    setInterval(tick, 1000);
});

