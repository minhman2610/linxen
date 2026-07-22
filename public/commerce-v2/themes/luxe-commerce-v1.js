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

    const searchPanel = body.querySelector('[data-lxcv1-search-panel]');
    const searchOpen = body.querySelector('[data-lxcv1-search-open]');
    const searchInput = body.querySelector('[data-lxcv1-search-input]');
    const siteHeader = body.querySelector('[data-lxcv1-header]');

    const setSearch = (open) => {
        if (!searchPanel) {
            return;
        }

        searchPanel.classList.toggle('is-open', open);
        searchPanel.setAttribute('aria-hidden', String(!open));
        searchOpen?.setAttribute('aria-expanded', String(open));

        if (open) {
            searchPanel.style.setProperty(
                '--lxcv1-search-top',
                `${Math.max(0, siteHeader?.getBoundingClientRect().bottom || 0)}px`
            );
            window.requestAnimationFrame(() => searchInput?.focus());
        } else {
            searchOpen?.focus({ preventScroll: true });
        }
    };

    searchOpen?.addEventListener('click', () => setSearch(true));
    searchPanel?.querySelector('[data-lxcv1-search-close]')
        ?.addEventListener('click', () => setSearch(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && searchPanel?.classList.contains('is-open')) {
            setSearch(false);
        }
    });

    const heroVideo = body.querySelector('[data-lxhome-hero-video]');

    if (heroVideo) {
        const loadHeroVideo = () => {
            if (heroVideo.dataset.loaded === 'true') {
                return;
            }

            const source = heroVideo.querySelector('source[data-src]');
            if (!source?.dataset.src) {
                return;
            }

            source.src = source.dataset.src;
            heroVideo.dataset.loaded = 'true';
            heroVideo.load();
            heroVideo.play().catch(() => {});
        };

        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(loadHeroVideo, { timeout: 1600 });
        } else {
            window.setTimeout(loadHeroVideo, 700);
        }
    }

    const autoMediaTimers = new WeakMap();
    let autoCardSequence = 0;

    const stopAutoMedia = (card) => {
        const timer = autoMediaTimers.get(card);
        if (timer) {
            window.clearTimeout(timer);
            autoMediaTimers.delete(card);
        }
    };

    const scheduleAutoMedia = (card, initial = false) => {
        stopAutoMedia(card);

        if (
            reducedMotion
            || card.dataset.lxcv1InViewport !== 'true'
            || !card.lxcv1SelectNextMedia
        ) {
            return;
        }

        const sequence = Number(card.dataset.lxcv1AutoSequence || 0);
        const manualUntil = Number(card.dataset.lxcv1ManualUntil || 0);
        const manualDelay = Math.max(0, manualUntil - Date.now());
        const viewportDelay = initial
            ? 2400 + ((sequence % 4) * 620)
            : 6200 + ((sequence % 3) * 480);
        const delay = Math.max(viewportDelay, manualDelay + 180);

        const timer = window.setTimeout(() => {
            autoMediaTimers.delete(card);

            if (
                document.hidden
                || card.dataset.lxcv1InViewport !== 'true'
            ) {
                scheduleAutoMedia(card, false);
                return;
            }

            if (Number(card.dataset.lxcv1ManualUntil || 0) <= Date.now()) {
                card.lxcv1SelectNextMedia();
            }

            scheduleAutoMedia(card, false);
        }, delay);

        autoMediaTimers.set(card, timer);
    };

    const autoMediaObserver = 'IntersectionObserver' in window
        ? new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                const card = entry.target;
                if (entry.isIntersecting && entry.intersectionRatio >= .55) {
                    card.dataset.lxcv1InViewport = 'true';
                    card.querySelectorAll('[data-lxcv1-color-image]')
                        .forEach((option) => {
                            if (!option.dataset.image) {
                                return;
                            }
                            const preload = new Image();
                            preload.src = option.dataset.image;
                        });
                    scheduleAutoMedia(card, true);
                } else {
                    card.dataset.lxcv1InViewport = 'false';
                    stopAutoMedia(card);
                }
            });
        }, {
            threshold: [.3, .55, .8],
            rootMargin: '0px 0px -8% 0px',
        })
        : null;

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
                const sizeItems = Array.from(card.querySelectorAll(
                    '[data-lxcv1-size]'
                ));

                if (!image || options.length === 0) {
                    card.dataset.lxcv1ColorReady = 'true';
                    return;
                }

                image.dataset.baseAlt = image.alt;
                card.dataset.lxcv1AutoSequence = String(autoCardSequence++);

                let activeIndex = Math.max(
                    0,
                    options.findIndex(
                        (option) => option.getAttribute('aria-pressed') === 'true'
                    )
                );
                let imageSwapToken = 0;

                const updateSizes = (option) => {
                    const availableSizes = new Set(
                        String(option.dataset.sizes || '')
                            .split(',')
                            .map((size) => size.trim().toUpperCase())
                            .filter(Boolean)
                    );

                    sizeItems.forEach((sizeItem) => {
                        const size = String(
                            sizeItem.dataset.size || ''
                        ).toUpperCase();
                        const available = availableSizes.has(size);

                        sizeItem.classList.toggle(
                            'is-unavailable',
                            !available
                        );
                        sizeItem.setAttribute(
                            'aria-disabled',
                            String(!available)
                        );
                        sizeItem.title = `Size ${size} ${
                            available ? 'còn hàng' : 'đã hết'
                        }`;
                    });
                };

                const selectOption = (index, manual = false) => {
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

                    const nextImage = option.dataset.image || '';

                    if (
                        nextImage
                        && image.getAttribute('src') !== nextImage
                    ) {
                        const token = ++imageSwapToken;
                        const commitImage = () => {
                            if (token !== imageSwapToken) {
                                return;
                            }

                            image.src = nextImage;
                            window.requestAnimationFrame(() => {
                                window.requestAnimationFrame(() => {
                                    card.classList.remove(
                                        'is-media-changing'
                                    );
                                });
                            });
                        };

                        if (reducedMotion) {
                            commitImage();
                        } else {
                            card.classList.add('is-media-changing');
                            window.setTimeout(commitImage, 150);
                        }
                    }
                    if (option.dataset.label) {
                        image.alt = `${image.dataset.baseAlt} · ${option.dataset.label}`;
                        if (label) {
                            label.textContent = option.dataset.label;
                        }
                    }
                    updateSizes(option);

                    card.dataset.lxcv1MediaIndex = String(activeIndex);
                    if (manual) {
                        card.dataset.lxcv1ManualUntil = String(
                            Date.now() + 12000
                        );
                    }
                };

                options.forEach((option, index) => {
                    option.addEventListener('click', () => {
                        selectOption(index, true);
                        scheduleAutoMedia(card, false);
                    });
                });

                card.lxcv1SelectNextMedia = () => {
                    selectOption(activeIndex + 1);
                };

                if (
                    options.length > 1
                    && card.hasAttribute('data-lxcv1-auto-media')
                ) {
                    if (autoMediaObserver) {
                        autoMediaObserver.observe(card);
                    }
                }

                updateSizes(options[activeIndex]);
                card.dataset.lxcv1ColorReady = 'true';
            });
    };

    initProductCards();

    const reelRoot = document.createElement('section');
    reelRoot.className = 'lxreel';
    reelRoot.setAttribute('aria-hidden', 'true');
    reelRoot.setAttribute('aria-label', 'Xem nhanh sản phẩm LIN XÉN');
    reelRoot.setAttribute('role', 'dialog');
    reelRoot.setAttribute('aria-modal', 'true');
    reelRoot.innerHTML = '<div class="lxreel__scroller" data-lxreel-scroller></div>';
    document.body.appendChild(reelRoot);

    const reelScroller = reelRoot.querySelector('[data-lxreel-scroller]');
    let reelObserver = null;
    let reelPreviouslyFocused = null;

    const getReelEntries = () => Array.from(
        body.querySelectorAll('[data-lxreel-product]')
    ).map((card) => {
        const image = card.querySelector('[data-lxcv1-product-image]');
        const color = card.querySelector('[data-lxcv1-color-label]');

        return {
            image: image?.currentSrc || image?.src || '',
            imageAlt: image?.alt || card.dataset.lxreelName || '',
            name: card.dataset.lxreelName || '',
            originalPrice: card.dataset.lxreelOriginalPrice || '',
            price: card.dataset.lxreelPrice || '',
            url: card.dataset.lxreelUrl || '',
            color: color?.textContent?.trim() || '',
        };
    }).filter((entry) => entry.image && entry.url && entry.name);

    const closeReel = () => {
        reelObserver?.disconnect();
        reelObserver = null;
        reelRoot.classList.remove('is-open');
        reelRoot.setAttribute('aria-hidden', 'true');
        reelScroller.replaceChildren();
        body.classList.remove('lxreel-open');
        reelPreviouslyFocused?.focus({ preventScroll: true });
        reelPreviouslyFocused = null;
    };

    const makeReelSlide = (entry, index) => {
        const slide = document.createElement('article');
        slide.className = 'lxreel__slide';
        slide.dataset.lxreelSlide = String(index);

        const media = document.createElement('figure');
        media.className = 'lxreel__media';
        const image = new Image();
        image.src = entry.image;
        image.alt = entry.imageAlt;
        image.decoding = 'async';
        image.loading = index === 0 ? 'eager' : 'lazy';
        media.appendChild(image);

        const details = document.createElement('div');
        details.className = 'lxreel__details';

        const eyebrow = document.createElement('p');
        eyebrow.className = 'lxreel__eyebrow';
        eyebrow.textContent = entry.color
            ? `LIN XÉN · ${entry.color}`
            : 'LIN XÉN · Quick look';

        const title = document.createElement('h1');
        title.textContent = entry.name;

        const priceRow = document.createElement('div');
        priceRow.className = 'lxreel__price-row';
        const price = document.createElement('strong');
        price.textContent = entry.price;
        priceRow.appendChild(price);
        if (entry.originalPrice) {
            const originalPrice = document.createElement('del');
            originalPrice.textContent = entry.originalPrice;
            priceRow.appendChild(originalPrice);
        }

        const hint = document.createElement('p');
        hint.className = 'lxreel__hint';
        hint.textContent = index === 0
            ? 'Vuốt lên để xem thiết kế tiếp theo.'
            : 'Vuốt để tiếp tục khám phá các thiết kế khác.';

        const actions = document.createElement('div');
        actions.className = 'lxreel__actions';
        const close = document.createElement('button');
        close.className = 'lxreel__close';
        close.type = 'button';
        close.textContent = 'Đóng';
        close.dataset.lxreelClose = 'true';
        const detailsLink = document.createElement('a');
        detailsLink.className = 'lxreel__details-link';
        detailsLink.href = entry.url;
        detailsLink.textContent = 'Xem chi tiết';
        actions.append(close, detailsLink);
        details.append(eyebrow, title, priceRow, hint, actions);
        slide.append(media, details);

        return slide;
    };

    const openReel = (selectedIndex) => {
        const entries = getReelEntries();
        if (!entries.length || !reelScroller) {
            return;
        }

        reelPreviouslyFocused = document.activeElement;
        reelScroller.replaceChildren(
            ...entries.map(makeReelSlide)
        );
        reelRoot.classList.add('is-open');
        reelRoot.setAttribute('aria-hidden', 'false');
        body.classList.add('lxreel-open');

        const slides = Array.from(reelScroller.querySelectorAll(
            '[data-lxreel-slide]'
        ));
        const safeIndex = Math.max(0, Math.min(selectedIndex, slides.length - 1));
        reelScroller.scrollTop = slides[safeIndex]?.offsetTop || 0;
        slides[safeIndex]?.classList.add('is-active');
        slides[safeIndex]?.querySelector('[data-lxreel-close]')?.focus({
            preventScroll: true,
        });

        if ('IntersectionObserver' in window) {
            reelObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    entry.target.classList.toggle(
                        'is-active',
                        entry.isIntersecting && entry.intersectionRatio >= .62
                    );
                });
            }, {
                root: reelScroller,
                threshold: [.25, .62, .9],
            });
            slides.forEach((slide) => reelObserver.observe(slide));
        }
    };

    body.addEventListener('click', (event) => {
        const productLink = event.target.closest(
            '[data-lxreel-product] a[href]'
        );
        if (
            !productLink
            || event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
        ) {
            return;
        }

        const card = productLink.closest('[data-lxreel-product]');
        const cards = Array.from(
            body.querySelectorAll('[data-lxreel-product]')
        );
        const index = cards.indexOf(card);
        if (index < 0) {
            return;
        }

        event.preventDefault();
        openReel(index);
    });

    reelRoot.addEventListener('click', (event) => {
        if (event.target.closest('[data-lxreel-close]')) {
            closeReel();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && reelRoot.classList.contains('is-open')) {
            closeReel();
        }
    });

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
                grid.querySelector('[data-lxhome-empty]')?.remove();
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

        if (
            hasMore
            && grid
            && !grid.querySelector('[data-lxcv1-product-card]')
        ) {
            loadNextPage();
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
