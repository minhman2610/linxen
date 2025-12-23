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
