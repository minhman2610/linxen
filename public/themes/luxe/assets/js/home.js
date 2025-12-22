
document.addEventListener("DOMContentLoaded", () => {
    const section = document.querySelector(".lx-exchange");
    const steps = section?.querySelectorAll(".lx-ex-step");

    if (!section || !steps) return;

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                section.classList.add("is-visible");
                steps.forEach((step, index) => {
                    setTimeout(() => {
                        step.classList.add("is-active");
                    }, index * 150);
                });
            }
        });
    }, { threshold: 0.3 });

    observer.observe(section);
});

