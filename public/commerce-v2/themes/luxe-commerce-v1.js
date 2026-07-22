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

    const storyFeed = body.querySelector(
        '[data-lxstory-feed]'
    );

    if (storyFeed) {
        const stories = Array.from(
            storyFeed.querySelectorAll('[data-lxstory-item]')
        );
        const requestedStory = Math.max(
            0,
            Math.min(
                stories.length - 1,
                Number.parseInt(
                    new URLSearchParams(window.location.search)
                        .get('start') || '1',
                    10
                ) - 1
            )
        );
        let activeStory = null;
        let storyTimer = null;

        const framesFor = (story) => Array.from(
            story.querySelectorAll('[data-lxstory-frame]')
        );
        const progressFor = (story) => Array.from(
            story.querySelectorAll('[data-lxstory-progress]')
        );

        const setFrame = (story, nextIndex) => {
            const frames = framesFor(story);
            const progress = progressFor(story);

            if (frames.length === 0) {
                return;
            }

            const index = (
                nextIndex + frames.length
            ) % frames.length;

            frames.forEach((frame, frameIndex) => {
                frame.classList.toggle(
                    'is-current',
                    frameIndex === index
                );
            });
            progress.forEach((bar, barIndex) => {
                bar.classList.toggle(
                    'is-current',
                    barIndex === index
                );
                bar.classList.toggle(
                    'is-complete',
                    barIndex < index
                );
            });
            story.dataset.frameIndex = String(index);
        };

        const stopTimer = () => {
            if (storyTimer !== null) {
                window.clearTimeout(storyTimer);
                storyTimer = null;
            }
        };

        const scheduleFrame = () => {
            stopTimer();

            if (
                !activeStory
                || reducedMotion
                || document.hidden
                || activeStory.classList.contains('is-paused')
                || framesFor(activeStory).length < 2
            ) {
                return;
            }

            storyTimer = window.setTimeout(() => {
                const current = Number.parseInt(
                    activeStory.dataset.frameIndex || '0',
                    10
                );
                setFrame(activeStory, current + 1);
                scheduleFrame();
            }, 4000);
        };

        const activateStory = (story) => {
            if (!story || story === activeStory) {
                return;
            }

            activeStory?.classList.remove('is-active');
            activeStory = story;
            activeStory.classList.add('is-active');
            setFrame(
                activeStory,
                Number.parseInt(
                    activeStory.dataset.frameIndex || '0',
                    10
                )
            );
            scheduleFrame();

            body.dispatchEvent(new CustomEvent(
                'linxen:commerce:image-story-view',
                {
                    bubbles: true,
                    detail: {
                        index: Number.parseInt(
                            activeStory.dataset.storyIndex || '0',
                            10
                        ),
                    },
                }
            ));
        };

        stories.forEach((story) => {
            story.dataset.frameIndex = '0';

            story.querySelector(
                '[data-lxstory-toggle]'
            )?.addEventListener('click', (event) => {
                const button = event.currentTarget;
                const paused = story.classList.toggle(
                    'is-paused'
                );

                button.setAttribute(
                    'aria-pressed',
                    String(paused)
                );
                button.setAttribute(
                    'aria-label',
                    paused
                        ? 'Tiếp tục chuyển ảnh'
                        : 'Tạm dừng chuyển ảnh'
                );

                const icon = button.querySelector(
                    '[data-lxstory-toggle-icon]'
                );
                if (icon) {
                    icon.textContent = paused ? '▶' : 'Ⅱ';
                }

                if (story === activeStory) {
                    scheduleFrame();
                }
            });

            story.querySelector(
                '.lxstory-media'
            )?.addEventListener('click', (event) => {
                if (story !== activeStory) {
                    return;
                }

                const bounds = event.currentTarget
                    .getBoundingClientRect();
                const current = Number.parseInt(
                    story.dataset.frameIndex || '0',
                    10
                );
                const direction = event.clientX
                    < bounds.left + bounds.width * .35
                    ? -1
                    : 1;

                setFrame(story, current + direction);
                scheduleFrame();
            });
        });

        if ('IntersectionObserver' in window) {
            const storyObserver = new IntersectionObserver(
                (entries) => {
                    entries
                        .filter((entry) => entry.isIntersecting)
                        .sort(
                            (left, right) =>
                                right.intersectionRatio
                                - left.intersectionRatio
                        )
                        .slice(0, 1)
                        .forEach((entry) => {
                            if (entry.intersectionRatio >= .55) {
                                activateStory(entry.target);
                            }
                        });
                },
                {
                    root: storyFeed,
                    threshold: [.55, .72, .9],
                }
            );

            stories.forEach((story) => {
                storyObserver.observe(story);
            });
        }

        const initialStory = stories[requestedStory]
            || stories[0];
        activateStory(initialStory);

        window.requestAnimationFrame(() => {
            storyFeed.scrollTo({
                top: requestedStory * storyFeed.clientHeight,
                behavior: 'auto',
            });
        });

        document.addEventListener('visibilitychange', () => {
            scheduleFrame();
        });
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
