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

    const drawer = body.querySelector('[data-lxcv1-drawer]');
    const drawerOpen = body.querySelector('[data-lxcv1-drawer-open]');
    const drawerBackdrop = body.querySelector(
        '[data-lxcv1-drawer-backdrop]'
    );

    const setDrawer = (open) => {
        if (!drawer) {
            return;
        }

        drawer.classList.toggle('is-open', open);
        drawerBackdrop?.classList.toggle('is-open', open);
        body.classList.toggle('lxcv1-drawer-open', open);
        drawer.setAttribute('aria-hidden', String(!open));
        drawerOpen?.setAttribute('aria-expanded', String(open));

        if (open) {
            drawer.querySelector('a, button')?.focus();
        } else {
            drawerOpen?.focus({ preventScroll: true });
        }
    };

    drawerOpen?.addEventListener('click', () => setDrawer(true));
    drawerBackdrop?.addEventListener('click', () => setDrawer(false));
    drawer?.querySelector('[data-lxcv1-drawer-close]')
        ?.addEventListener('click', () => setDrawer(false));
    drawer?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setDrawer(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && drawer?.classList.contains('is-open')) {
            setDrawer(false);
        }
    });

    const heroVideo = body.querySelector('[data-lxhome-hero-video]');
    const soundButton = body.querySelector('[data-lxhome-video-sound]');

    soundButton?.addEventListener('click', async () => {
        if (!heroVideo) {
            return;
        }

        heroVideo.muted = !heroVideo.muted;
        soundButton.setAttribute(
            'aria-pressed',
            String(!heroVideo.muted)
        );
        soundButton.setAttribute(
            'aria-label',
            heroVideo.muted
                ? 'Bật âm thanh video'
                : 'Tắt âm thanh video'
        );

        const label = soundButton.querySelector(
            '[data-lxhome-sound-icon]'
        );
        if (label) {
            label.textContent = heroVideo.muted
                ? 'Âm thanh tắt'
                : 'Âm thanh bật';
        }

        try {
            await heroVideo.play();
        } catch (error) {
            heroVideo.muted = true;
            soundButton.setAttribute('aria-pressed', 'false');
            if (label) {
                label.textContent = 'Âm thanh tắt';
            }
        }
    });

    const initProductCards = (scope = body) => {
        scope.querySelectorAll('[data-lxcv1-product-card]')
            .forEach((card) => {
                if (card.dataset.lxcv1ColorReady === 'true') {
                    return;
                }

                const image = card.querySelector(
                    '[data-lxcv1-product-image]'
                );
                const label = card.querySelector(
                    '[data-lxcv1-color-label]'
                );
                const options = Array.from(card.querySelectorAll(
                    '[data-lxcv1-color-image]'
                ));

                if (!image || options.length === 0) {
                    card.dataset.lxcv1ColorReady = 'true';
                    return;
                }

                let activeIndex = Math.max(
                    0,
                    options.findIndex(
                        (option) => option.getAttribute('aria-pressed') === 'true'
                    )
                );

                const selectOption = (index) => {
                    activeIndex = (
                        index + options.length
                    ) % options.length;
                    const option = options[activeIndex];

                    options.forEach((candidate, candidateIndex) => {
                        const selected = candidateIndex === activeIndex;
                        candidate.classList.toggle('is-active', selected);
                        candidate.setAttribute(
                            'aria-pressed',
                            String(selected)
                        );
                    });

                    if (option.dataset.image) {
                        image.src = option.dataset.image;
                    }
                    if (option.dataset.label) {
                        image.alt = `${image.alt.split(' · ')[0]} · ${option.dataset.label}`;
                        if (label) {
                            label.textContent = option.dataset.label;
                        }
                    }
                };

                options.forEach((option, index) => {
                    option.addEventListener('click', () => {
                        selectOption(index);
                    });
                });

                card.querySelectorAll('[data-lxcv1-color-step]')
                    .forEach((button) => {
                        button.addEventListener('click', () => {
                            selectOption(
                                activeIndex + Number(
                                    button.dataset.lxcv1ColorStep || 0
                                )
                            );
                        });
                    });

                card.dataset.lxcv1ColorReady = 'true';
            });
    };

    initProductCards();

    const homeFeed = body.querySelector('[data-lxhome-feed]');

    if (homeFeed) {
        const grid = homeFeed.querySelector('[data-lxhome-grid]');
        const sentinel = homeFeed.querySelector('[data-lxhome-sentinel]');
        const loadButton = homeFeed.querySelector('[data-lxhome-load-more]');
        const loaderText = homeFeed.querySelector(
            '[data-lxhome-loader-text]'
        );
        const endMessage = homeFeed.querySelector('[data-lxhome-end]');
        const count = homeFeed.querySelector('[data-lxhome-count]');
        let nextCursor = homeFeed.dataset.nextCursor || '';
        let hasMore = homeFeed.dataset.hasMore === 'true';
        let loading = false;

        const completeFeed = () => {
            hasMore = false;
            homeFeed.dataset.hasMore = 'false';
            if (sentinel) {
                sentinel.hidden = true;
            }
            if (endMessage) {
                endMessage.hidden = false;
            }
        };

        const loadNextPage = async () => {
            if (
                loading
                || !hasMore
                || !grid
                || !homeFeed.dataset.endpoint
            ) {
                return;
            }

            loading = true;
            loadButton?.setAttribute('disabled', 'disabled');
            if (loaderText) {
                loaderText.textContent = 'Đang tải sản phẩm…';
            }

            try {
                const endpoint = new URL(
                    homeFeed.dataset.endpoint,
                    window.location.origin
                );
                if (nextCursor) {
                    endpoint.searchParams.set('cursor', nextCursor);
                }

                const response = await window.fetch(endpoint, {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = await response.json();
                const batch = document.createElement('template');
                batch.innerHTML = payload.html || '';
                grid.appendChild(batch.content);
                initProductCards(grid);

                nextCursor = payload.next_cursor || '';
                hasMore = payload.has_more === true;
                homeFeed.dataset.nextCursor = nextCursor;
                homeFeed.dataset.hasMore = String(hasMore);

                if (count) {
                    count.textContent = `${grid.querySelectorAll(
                        '[data-lxcv1-product-card]'
                    ).length} thiết kế`;
                }

                if (!hasMore) {
                    completeFeed();
                }
            } catch (error) {
                if (loaderText) {
                    loaderText.textContent = 'Tải chưa thành công';
                }
                if (loadButton) {
                    loadButton.textContent = 'Thử lại';
                }
            } finally {
                loading = false;
                loadButton?.removeAttribute('disabled');
                if (hasMore && loaderText) {
                    loaderText.textContent = 'Cuộn để tải thêm';
                }
            }
        };

        loadButton?.addEventListener('click', loadNextPage);

        if (
            hasMore
            && sentinel
            && 'IntersectionObserver' in window
        ) {
            const feedObserver = new IntersectionObserver(
                (entries) => {
                    if (entries.some((entry) => entry.isIntersecting)) {
                        loadNextPage();
                    }
                },
                {
                    rootMargin: '700px 0px',
                    threshold: .01,
                }
            );
            feedObserver.observe(sentinel);
        }
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
