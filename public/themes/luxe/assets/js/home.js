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
