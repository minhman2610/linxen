(() => {
    'use strict';

    const body = document.querySelector(
        '.lxv2-theme--luxe-commerce-v1'
    );

    if (!body) {
        return;
    }

    body.querySelectorAll(
        '[data-lxcv1-quantity-form]'
    ).forEach((form) => {
        const input = form.querySelector(
            '[data-lxcv1-qty-input]'
        );

        if (!input) {
            return;
        }

        form.querySelectorAll(
            '[data-lxcv1-qty-step]'
        ).forEach((button) => {
            button.addEventListener('click', () => {
                const step = Number(
                    button.dataset.lxcv1QtyStep || 0
                );
                const min = Number(input.min || 0);
                const max = Number(input.max || 20);
                const current = Number.parseInt(
                    input.value || '1',
                    10
                ) || 1;

                input.value = String(
                    Math.max(
                        min,
                        Math.min(max, current + step)
                    )
                );
            });
        });
    });

    const reveal = Array.from(
        body.querySelectorAll('[data-lxcv1-reveal]')
    );
    const reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;

    if (
        reducedMotion
        || !('IntersectionObserver' in window)
    ) {
        reveal.forEach((node) => {
            node.classList.add('is-visible');
        });
    } else {
        const observer = new IntersectionObserver(
            (entries, instance) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add(
                        'is-visible'
                    );
                    instance.unobserve(entry.target);
                });
            },
            {
                threshold: 0.08,
                rootMargin: '0px 0px -5% 0px',
            }
        );

        reveal.forEach((node) => observer.observe(node));
    }

    body.dispatchEvent(new CustomEvent(
        'linxen:commerce:luxe-theme-ready',
        {
            bubbles: true,
            detail: {
                theme: 'luxe_commerce_v1',
                page: body.querySelector(
                    '[data-lxcv1-page]'
                )?.dataset.lxcv1Page || null,
            },
        }
    ));
})();
