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

    const shop = body.querySelector('[data-lxcv1-shop]');

    if (shop) {
        const filterForm = shop.querySelector(
            '[data-lxcv1-shop-filters]'
        );
        const filterToggle = shop.querySelector(
            '[data-lxcv1-shop-filter-toggle]'
        );
        const filterCount = shop.querySelector(
            '[data-lxcv1-shop-count]'
        );
        const emptyState = shop.querySelector(
            '[data-lxcv1-shop-filter-empty]'
        );
        const cards = Array.from(shop.querySelectorAll(
            '[data-lxcv1-product-card]'
        ));

        const valuesFor = (selector) => Array.from(
            filterForm?.querySelectorAll(selector) || []
        )
            .filter((input) => input.checked)
            .map((input) => String(input.value || ''))
            .filter(Boolean);

        const splitValues = (value) => String(value || '')
            .split('|')
            .map((item) => item.trim())
            .filter(Boolean);

        const applyFilters = () => {
            if (!filterForm) {
                return;
            }

            const stockOnly = filterForm.querySelector(
                '[data-lxcv1-filter-stock]'
            )?.checked;
            const sizes = valuesFor('[data-lxcv1-filter-size]');
            const colors = valuesFor('[data-lxcv1-filter-color]');
            let visible = 0;

            cards.forEach((card) => {
                const matchesStock = !stockOnly
                    || card.dataset.lxcv1ProductStock === '1';
                const cardSizes = splitValues(
                    card.dataset.lxcv1ProductSizes
                );
                const cardColors = splitValues(
                    card.dataset.lxcv1ProductColors
                );
                const matchesSize = sizes.length === 0
                    || sizes.some((size) => cardSizes.includes(size));
                const matchesColor = colors.length === 0
                    || colors.some((color) => cardColors.includes(color));
                const visibleCard = matchesStock
                    && matchesSize
                    && matchesColor;

                card.hidden = !visibleCard;
                if (visibleCard) {
                    visible += 1;
                }
            });

            if (filterCount) {
                filterCount.textContent = `${visible} thiết kế đang hiển thị`;
            }
            if (emptyState) {
                emptyState.hidden = visible > 0;
            }
        };

        const resetFilters = () => {
            if (!filterForm) {
                return;
            }

            window.setTimeout(applyFilters, 0);
        };

        filterToggle?.addEventListener('click', () => {
            if (!filterForm) {
                return;
            }

            const willOpen = filterForm.hidden;
            filterForm.hidden = !willOpen;
            filterToggle.setAttribute(
                'aria-expanded',
                willOpen ? 'true' : 'false'
            );
        });

        filterForm?.addEventListener('change', applyFilters);
        filterForm?.addEventListener('reset', resetFilters);
        shop.querySelectorAll('[data-lxcv1-shop-filter-reset]').forEach(
            (button) => button.addEventListener('click', () => {
                filterForm?.reset();
                resetFilters();
            })
        );

        applyFilters();
    }

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
    const reelCartUrl = body.querySelector('[data-lxreel-cart-url]')?.dataset
        .lxreelCartUrl || '/v2/cart/items';
    const reelCartHref = body.querySelector('[data-lxcv1-cart-link]')?.href
        || '/v2/cart';
    let reelObserver = null;
    let reelPreviouslyFocused = null;
    let reelMotionTimer = null;
    let reelMotionSlide = null;
    let reelVerticalGesture = null;
    let reelSuppressMediaClickUntil = 0;
    let reelSuppressNavigationClickUntil = 0;

    const MAX_REEL_MEDIA = 6;

    const colorKey = (value) => String(value || '')
        .trim()
        .toLocaleLowerCase('vi');

    const isSalesInboxMedia = (value) => String(value || '')
        .toUpperCase()
        .includes('SALES_INBOX');

    const salesInboxColorsFromCard = (card) => {
        let items = [];
        try {
            items = JSON.parse(card.dataset.lxreelSaleInboxMedia || '[]');
        } catch (error) {
            items = [];
        }

        const colors = new Map();
        items.filter((item) => (
            item?.url && isSalesInboxMedia(item.job_category)
        )).forEach((item) => {
            const key = item.color_id || colorKey(item.label) || item.url;
            const color = colors.get(key) || {
                id: item.color_id || '',
                label: item.label || 'Màu sản phẩm',
                hex: item.hex || '#ead8cf',
                media: [],
            };

            if (!color.media.some((media) => media.url === item.url)) {
                color.media.push({
                    url: item.url,
                    thumb_url: item.thumb_url || item.url,
                });
            }
            colors.set(key, color);
        });

        return Array.from(colors.values()).map((color) => ({
            ...color,
            media: color.media.slice(0, MAX_REEL_MEDIA),
        })).filter((color) => color.media.length > 0);
    };

    const getReelEntries = () => Array.from(
        body.querySelectorAll('[data-lxreel-product]')
    ).map((card) => {
        const image = card.querySelector('[data-lxcv1-product-image]');
        const colorLabel = card.querySelector('[data-lxcv1-color-label]');
        const colors = salesInboxColorsFromCard(card);
        const currentLabel = colorKey(colorLabel?.textContent);
        const activeColorIndex = Math.max(0, colors.findIndex(
            (color) => colorKey(color.label) === currentLabel
        ));

        return {
            activeColorIndex,
            activeMediaIndex: 0,
            card,
            motionPaused: false,
            sizePickerOpen: false,
            selectedSizeId: '',
            colors,
            imageAlt: image?.alt || card.dataset.lxreelName || '',
            name: card.dataset.lxreelName || '',
            originalPrice: card.dataset.lxreelOriginalPrice || '',
            price: card.dataset.lxreelPrice || '',
            url: card.dataset.lxreelUrl || '',
        };
    }).filter((entry) => entry.colors.length && entry.url && entry.name);

    const closeReel = () => {
        reelObserver?.disconnect();
        reelObserver = null;
        stopReelMotion();
        reelRoot.classList.remove('is-open');
        reelRoot.setAttribute('aria-hidden', 'true');
        reelScroller.replaceChildren();
        body.classList.remove('lxreel-open');
        reelPreviouslyFocused?.focus({ preventScroll: true });
        reelPreviouslyFocused = null;
    };

    const selectedReelColor = (entry) => entry.colors[
        Math.max(0, Math.min(
            entry.activeColorIndex,
            entry.colors.length - 1
        ))
    ] || null;

    const showReelMediaFrame = (slide, entry, requestedIndex) => {
        const frames = Array.from(slide.querySelectorAll(
            '[data-lxreel-media-frame]'
        ));
        if (!frames.length) {
            return;
        }

        entry.activeMediaIndex = Math.max(0, Math.min(
            requestedIndex,
            frames.length - 1
        ));
        frames.forEach((frame, frameIndex) => {
            frame.classList.toggle(
                'is-active',
                frameIndex === entry.activeMediaIndex
            );
        });

        const galleryCount = slide.querySelector('[data-lxreel-count]');
        if (galleryCount) {
            galleryCount.textContent = `${String(
                entry.activeMediaIndex + 1
            ).padStart(2, '0')} / ${String(frames.length).padStart(2, '0')}`;
        }
        slide.querySelectorAll('[data-lxreel-thumbs] button').forEach(
            (button, buttonIndex) => {
                button.classList.toggle(
                    'is-active',
                    buttonIndex === entry.activeMediaIndex
                );
            }
        );
    };

    const stopReelMotion = () => {
        if (reelMotionTimer) {
            window.clearTimeout(reelMotionTimer);
        }
        reelMotionTimer = null;
        reelMotionSlide = null;
    };

    const startReelMotion = (slide, entry) => {
        stopReelMotion();
        if (
            !slide
            || !entry
            || reducedMotion
            || entry.motionPaused
            || !slide.classList.contains('is-active')
        ) {
            return;
        }

        const mediaTrack = slide.querySelector('[data-lxreel-media-track]');
        const frameCount = mediaTrack?.children.length || 0;
        if (!mediaTrack || frameCount < 2) {
            return;
        }

        reelMotionSlide = slide;
        const advance = () => {
            if (
                reelMotionSlide !== slide
                || !slide.classList.contains('is-active')
                || document.hidden
            ) {
                stopReelMotion();
                return;
            }

            const nextIndex = (entry.activeMediaIndex + 1) % frameCount;
            showReelMediaFrame(slide, entry, nextIndex);
            reelMotionTimer = window.setTimeout(advance, 4400);
        };

        reelMotionTimer = window.setTimeout(advance, 4400);
    };

    const setReelCommerceMessage = (slide, message, isError = false) => {
        const status = slide.querySelector('[data-lxreel-cart-status]');
        if (!status) {
            return;
        }
        status.textContent = message;
        status.classList.toggle('is-error', isError);
    };

    const setReelCartQuantity = (quantity) => {
        const safeQuantity = Math.max(0, Number(quantity) || 0);
        document.querySelectorAll(
            '[data-lxcv1-cart-count], [data-lxreel-cart-count]'
        ).forEach((badge) => {
            badge.textContent = String(safeQuantity);
            badge.hidden = safeQuantity < 1;
        });
    };

    const animateReelItemToCart = (slide) => {
        if (reducedMotion) {
            return;
        }

        const source = slide.querySelector(
            '.lxreel__media-frame.is-active img'
        );
        const target = slide.querySelector('[data-lxreel-cart-link]');
        if (!source || !target) {
            return;
        }

        const sourceRect = source.getBoundingClientRect();
        const targetRect = target.getBoundingClientRect();
        if (!sourceRect.width || !targetRect.width) {
            return;
        }

        const flyingImage = new Image();
        flyingImage.className = 'lxreel__flying-product';
        flyingImage.src = source.currentSrc || source.src;
        flyingImage.alt = '';
        flyingImage.setAttribute('aria-hidden', 'true');
        flyingImage.style.setProperty('--lxreel-fly-x', `${
            targetRect.left + targetRect.width / 2 - sourceRect.left - 22
        }px`);
        flyingImage.style.setProperty('--lxreel-fly-y', `${
            targetRect.top + targetRect.height / 2 - sourceRect.top - 28
        }px`);
        flyingImage.style.left = `${sourceRect.left + sourceRect.width / 2 - 34}px`;
        flyingImage.style.top = `${sourceRect.top + sourceRect.height / 2 - 42}px`;
        document.body.appendChild(flyingImage);
        target.classList.remove('is-cart-pulse');
        void target.offsetWidth;
        target.classList.add('is-cart-pulse');
        window.setTimeout(() => target.classList.remove('is-cart-pulse'), 720);
        flyingImage.addEventListener('animationend', () => flyingImage.remove(), {
            once: true,
        });
    };

    const addReelItemToCart = async (entry, slide) => {
        const color = selectedReelColor(entry);
        const size = color?.sizes?.find((candidate) => (
            candidate.sellable_sku_id === entry.selectedSizeId
        ));
        const button = slide.querySelector('[data-lxreel-add-cart]');
        if (!size?.sellable_sku_id || !size.sellable) {
            setReelCommerceMessage(slide, 'Vui lòng chọn size còn hàng.', true);
            return;
        }

        button.disabled = true;
        button.textContent = 'Đang kiểm tra tồn kho…';
        setReelCommerceMessage(slide, '');
        try {
            const response = await window.fetch(reelCartUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    sellable_sku_id: size.sellable_sku_id,
                    quantity: 1,
                }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.ok !== true) {
                throw new Error(payload.message || 'Chưa thể thêm vào giỏ.');
            }

            setReelCartQuantity(payload.cart_quantity_total);
            animateReelItemToCart(slide);
            button.textContent = 'Đã thêm vào giỏ ✓';
            setReelCommerceMessage(slide, payload.message || 'Đã thêm vào giỏ.');
            window.setTimeout(() => refreshReelSlide(slide, entry, Number(
                slide.dataset.lxreelSlide || 0
            )), 1500);
        } catch (error) {
            setReelCommerceMessage(
                slide,
                error.message || 'Chưa thể thêm vào giỏ.',
                true
            );
            refreshReelSlide(slide, entry, Number(
                slide.dataset.lxreelSlide || 0
            ));
        }
    };

    const refreshReelSlide = (slide, entry, index) => {
        const color = selectedReelColor(entry);
        if (!color) {
            return;
        }

        const mediaTrack = slide.querySelector('[data-lxreel-media-track]');
        const colorPicker = slide.querySelector('[data-lxreel-colors]');
        const eyebrow = slide.querySelector('[data-lxreel-eyebrow]');
        const galleryCount = slide.querySelector('[data-lxreel-count]');
        const galleryThumbs = slide.querySelector('[data-lxreel-thumbs]');
        const sizeLabel = slide.querySelector('[data-lxreel-size-label]');
        const sizeToggle = slide.querySelector('[data-lxreel-size-toggle]');
        const sizePicker = slide.querySelector('[data-lxreel-sizes]');
        const commerce = slide.querySelector('[data-lxreel-commerce]');
        const addCart = slide.querySelector('[data-lxreel-add-cart]');
        const media = color.media.slice(0, MAX_REEL_MEDIA);

        eyebrow.textContent = `LIN XÉN · ${color.label}`;
        mediaTrack.replaceChildren(...media.map((item, mediaIndex) => {
            const frame = document.createElement('figure');
            frame.className = 'lxreel__media-frame';
            frame.dataset.lxreelMediaFrame = 'true';
            frame.classList.toggle('is-active', mediaIndex === 0);
            const image = new Image();
            image.src = item.url;
            image.alt = `${entry.imageAlt} · ${color.label} · góc ${mediaIndex + 1}`;
            image.decoding = 'async';
            image.loading = index === 0 && mediaIndex === 0 ? 'eager' : 'lazy';
            frame.appendChild(image);
            return frame;
        }));

        colorPicker.replaceChildren(...entry.colors.map((candidate, colorIndex) => {
            const button = document.createElement('button');
            const selected = colorIndex === entry.activeColorIndex;
            button.type = 'button';
            button.className = 'lxreel__color-option';
            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-pressed', String(selected));
            button.setAttribute('aria-label', `Chọn màu ${candidate.label}`);
            const dot = document.createElement('i');
            dot.style.setProperty('--lxreel-swatch', candidate.hex || '#ead8cf');
            const label = document.createElement('span');
            label.textContent = candidate.label;
            button.append(dot, label);
            button.addEventListener('click', () => {
                entry.activeColorIndex = colorIndex;
                entry.activeMediaIndex = 0;
                entry.motionPaused = false;
                entry.sizePickerOpen = false;
                entry.selectedSizeId = '';
                refreshReelSlide(slide, entry, index);
            });
            return button;
        }));

        galleryThumbs.replaceChildren(...media.map((item, mediaIndex) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.setAttribute(
                'aria-label',
                `Xem góc ${mediaIndex + 1} của màu ${color.label}`
            );
            const thumb = new Image();
            thumb.src = item.thumb_url || item.url;
            thumb.alt = '';
            thumb.loading = 'lazy';
            thumb.decoding = 'async';
            button.appendChild(thumb);
            button.addEventListener('click', () => {
                entry.motionPaused = true;
                if (reelMotionSlide === slide) {
                    stopReelMotion();
                }
                showReelMediaFrame(slide, entry, mediaIndex);
            });
            return button;
        }));
        showReelMediaFrame(slide, entry, 0);

        const sizes = Array.isArray(color.sizes) ? color.sizes : [];
        const selectedSize = sizes.find((size) => (
            size.sellable_sku_id === entry.selectedSizeId
        ));
        if (!selectedSize) {
            entry.selectedSizeId = '';
        }
        const canSelectSize = sizes.length > 0;
        sizeLabel.hidden = true;
        sizeToggle.hidden = !canSelectSize;
        sizeToggle.setAttribute('aria-expanded', String(
            canSelectSize && entry.sizePickerOpen
        ));
        sizeToggle.textContent = selectedSize
            ? `Size ${selectedSize.size || ''}`
            : 'Chọn size';
        sizeToggle.onclick = () => {
            entry.sizePickerOpen = !entry.sizePickerOpen;
            refreshReelSlide(slide, entry, index);
        };
        sizePicker.hidden = !canSelectSize || !entry.sizePickerOpen;
        commerce.hidden = !canSelectSize || !entry.sizePickerOpen;
        addCart.hidden = !canSelectSize || !entry.sizePickerOpen;
        sizePicker.replaceChildren(...sizes.map((size) => {
            const button = document.createElement('button');
            const available = Boolean(size.sellable && size.sellable_sku_id);
            const selected = size.sellable_sku_id === entry.selectedSizeId;
            button.type = 'button';
            button.className = 'lxreel__size-option';
            button.classList.toggle('is-active', selected);
            button.classList.toggle('is-unavailable', !available);
            button.disabled = !available;
            button.setAttribute('aria-pressed', String(selected));
            button.textContent = size.size || '—';
            button.addEventListener('click', () => {
                entry.selectedSizeId = size.sellable_sku_id;
                refreshReelSlide(slide, entry, index);
            });
            return button;
        }));
        sizeLabel.textContent = sizes.length
            ? 'Chọn size'
            : 'Chọn size tại trang chi tiết';
        addCart.disabled = !entry.selectedSizeId;
        addCart.textContent = entry.selectedSizeId
            ? `Thêm size ${selectedSize?.size || ''} vào giỏ`
            : 'Chọn size để thêm giỏ';
        addCart.onclick = () => addReelItemToCart(entry, slide);
        startReelMotion(slide, entry);
    };

    const enrichReelEntry = async (entry, slide, index) => {
        if (entry.detailRequested) {
            return;
        }
        entry.detailRequested = true;

        try {
            const response = await window.fetch(entry.url, {
                credentials: 'same-origin',
                headers: { Accept: 'text/html' },
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const documentData = new DOMParser().parseFromString(
                await response.text(),
                'text/html'
            );
            const payload = documentData.querySelector(
                '#lxv2ProductData'
            )?.textContent;
            const product = payload ? JSON.parse(payload) : null;
            const detailColors = new Map((Array.isArray(product?.colors)
                ? product.colors
                : []).flatMap((color) => [
                [color.id, color],
                [colorKey(color.label), color],
            ]));
            const enrichedColors = entry.colors.map((color) => {
                const detail = detailColors.get(color.id)
                    || detailColors.get(colorKey(color.label));

                return {
                    ...color,
                    sizes: Array.isArray(detail?.sizes) ? detail.sizes.map((size) => ({
                        size: size.size || '',
                        sellable: Boolean(size.sellable),
                        sellable_sku_id: size.sellable_sku_id || '',
                    })) : [],
                };
            });

            if (enrichedColors.length) {
                entry.colors = enrichedColors;
                entry.activeMediaIndex = 0;
                entry.motionPaused = false;
                entry.sizePickerOpen = false;
                entry.selectedSizeId = '';
                refreshReelSlide(slide, entry, index);
            }
        } catch (error) {
            // Keep sale inbox media even when product commerce details are unavailable.
        }
    };

    const makeReelSlide = (entry, index) => {
        const slide = document.createElement('article');
        slide.className = 'lxreel__slide';
        slide.dataset.lxreelSlide = String(index);

        const media = document.createElement('figure');
        media.className = 'lxreel__media';
        const mediaLink = document.createElement('a');
        mediaLink.className = 'lxreel__media-link';
        mediaLink.href = entry.url;
        mediaLink.setAttribute(
            'aria-label',
            `Xem đầy đủ ${entry.name}`
        );
        const mediaTrack = document.createElement('div');
        mediaTrack.className = 'lxreel__media-track';
        mediaTrack.dataset.lxreelMediaTrack = 'true';
        mediaLink.appendChild(mediaTrack);
        media.appendChild(mediaLink);

        const details = document.createElement('div');
        details.className = 'lxreel__details';

        const detailsTop = document.createElement('div');
        detailsTop.className = 'lxreel__details-top';
        const eyebrow = document.createElement('p');
        eyebrow.className = 'lxreel__eyebrow';
        eyebrow.dataset.lxreelEyebrow = 'true';
        const close = document.createElement('button');
        close.className = 'lxreel__dismiss';
        close.type = 'button';
        close.setAttribute('aria-label', 'Đóng xem nhanh');
        close.dataset.lxreelClose = 'true';
        close.innerHTML = '<span aria-hidden="true">×</span>';
        const reelActions = document.createElement('div');
        reelActions.className = 'lxreel__top-actions';
        const reelCartLink = document.createElement('a');
        reelCartLink.className = 'lxreel__cart-link';
        reelCartLink.href = reelCartHref;
        reelCartLink.dataset.lxreelCartLink = 'true';
        reelCartLink.setAttribute('aria-label', 'Mở giỏ hàng');
        reelCartLink.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14l-1 13H6L5 7Z"></path><path d="M9 7a3 3 0 0 1 6 0"></path></svg><span data-lxreel-cart-count hidden>0</span>';
        reelActions.append(reelCartLink, close);
        detailsTop.append(eyebrow, reelActions);
        const title = document.createElement('h1');
        const titleLink = document.createElement('a');
        titleLink.href = entry.url;
        titleLink.textContent = entry.name;
        title.appendChild(titleLink);

        const priceRow = document.createElement('div');
        priceRow.className = 'lxreel__price-row';
        const priceLink = document.createElement('a');
        priceLink.href = entry.url;
        priceLink.setAttribute(
            'aria-label',
            `Xem đầy đủ ${entry.name}, giá ${entry.price}`
        );
        const price = document.createElement('strong');
        price.textContent = entry.price;
        priceLink.appendChild(price);
        if (entry.originalPrice) {
            const originalPrice = document.createElement('del');
            originalPrice.textContent = entry.originalPrice;
            priceLink.appendChild(originalPrice);
        }
        priceRow.appendChild(priceLink);

        const galleryMeta = document.createElement('div');
        galleryMeta.className = 'lxreel__gallery-meta';
        const galleryGuide = document.createElement('span');
        galleryGuide.textContent = 'Bộ ảnh sản phẩm';
        const galleryCount = document.createElement('strong');
        galleryCount.dataset.lxreelCount = 'true';
        galleryMeta.append(galleryGuide, galleryCount);

        const galleryThumbs = document.createElement('div');
        galleryThumbs.className = 'lxreel__gallery-thumbs';
        galleryThumbs.dataset.lxreelThumbs = 'true';

        const colorLabel = document.createElement('p');
        colorLabel.className = 'lxreel__color-label';
        colorLabel.textContent = 'Chọn màu';
        const colorPicker = document.createElement('div');
        colorPicker.className = 'lxreel__color-picker';
        colorPicker.dataset.lxreelColors = 'true';
        colorPicker.setAttribute('aria-label', 'Chọn màu sản phẩm');

        const sizeLabel = document.createElement('p');
        sizeLabel.className = 'lxreel__size-label';
        sizeLabel.dataset.lxreelSizeLabel = 'true';
        const sizeToggle = document.createElement('button');
        sizeToggle.className = 'lxreel__size-toggle';
        sizeToggle.type = 'button';
        sizeToggle.dataset.lxreelSizeToggle = 'true';
        sizeToggle.setAttribute('aria-expanded', 'false');
        const sizePicker = document.createElement('div');
        sizePicker.className = 'lxreel__size-picker';
        sizePicker.dataset.lxreelSizes = 'true';
        sizePicker.setAttribute('aria-label', 'Chọn kích thước');

        const commerce = document.createElement('div');
        commerce.className = 'lxreel__commerce';
        commerce.dataset.lxreelCommerce = 'true';
        const addCart = document.createElement('button');
        addCart.className = 'lxreel__add-cart';
        addCart.type = 'button';
        addCart.disabled = true;
        addCart.dataset.lxreelAddCart = 'true';
        const cartStatus = document.createElement('p');
        cartStatus.className = 'lxreel__cart-status';
        cartStatus.dataset.lxreelCartStatus = 'true';
        cartStatus.setAttribute('aria-live', 'polite');
        commerce.append(addCart, cartStatus);

        const selectors = document.createElement('div');
        selectors.className = 'lxreel__selectors';
        const colorSelection = document.createElement('div');
        colorSelection.className = 'lxreel__selection lxreel__selection--color';
        colorSelection.append(colorLabel, colorPicker);
        const sizeSelection = document.createElement('div');
        sizeSelection.className = 'lxreel__selection lxreel__selection--size';
        sizeSelection.append(sizeLabel, sizeToggle);
        selectors.append(colorSelection, sizeSelection);

        details.append(
            detailsTop,
            title,
            priceRow,
            galleryMeta,
            galleryThumbs,
            selectors,
            sizePicker,
            commerce
        );
        slide.append(media, details);
        refreshReelSlide(slide, entry, index);

        return slide;
    };

    const openReel = (selectedCard) => {
        const reelEntries = getReelEntries();
        if (!reelEntries.length || !reelScroller) {
            return;
        }
        const requestedIndex = reelEntries.findIndex(
            (entry) => entry.card === selectedCard
        );
        if (requestedIndex < 0) {
            return;
        }

        reelPreviouslyFocused = document.activeElement;
        reelScroller.replaceChildren(...reelEntries.map(makeReelSlide));
        setReelCartQuantity(
            body.querySelector('[data-lxcv1-cart-count]')?.textContent || 0
        );
        reelRoot.classList.add('is-open');
        reelRoot.setAttribute('aria-hidden', 'false');
        body.classList.add('lxreel-open');

        const slides = Array.from(reelScroller.querySelectorAll(
            '[data-lxreel-slide]'
        ));
        const safeIndex = Math.max(0, Math.min(
            requestedIndex,
            slides.length - 1
        ));
        reelScroller.scrollTop = slides[safeIndex]?.offsetTop || 0;
        slides[safeIndex]?.classList.add('is-active');
        slides[safeIndex]?.querySelector('[data-lxreel-close]')?.focus({
            preventScroll: true,
        });
        enrichReelEntry(
            reelEntries[safeIndex],
            slides[safeIndex],
            safeIndex
        );
        startReelMotion(slides[safeIndex], reelEntries[safeIndex]);

        if ('IntersectionObserver' in window) {
            reelObserver = new IntersectionObserver((observedEntries) => {
                observedEntries.forEach((observedEntry) => {
                    observedEntry.target.classList.toggle(
                        'is-active',
                        observedEntry.isIntersecting
                            && observedEntry.intersectionRatio >= .62
                    );
                    if (
                        observedEntry.isIntersecting
                        && observedEntry.intersectionRatio >= .62
                    ) {
                        const entryIndex = Number(
                            observedEntry.target.dataset.lxreelSlide
                        );
                        enrichReelEntry(
                            reelEntries[entryIndex],
                            observedEntry.target,
                            entryIndex
                        );
                        startReelMotion(
                            observedEntry.target,
                            reelEntries[entryIndex]
                        );
                    } else if (reelMotionSlide === observedEntry.target) {
                        stopReelMotion();
                    }
                });
            }, {
                root: reelScroller,
                threshold: [.25, .62, .9],
            });
            slides.forEach((slide) => reelObserver.observe(slide));
        }
    };

    const snapReelByGesture = (direction) => {
        const slides = Array.from(reelScroller.querySelectorAll(
            '[data-lxreel-slide]'
        ));
        if (!slides.length) {
            return;
        }

        const currentIndex = slides.reduce((closestIndex, slide, index) => (
            Math.abs(slide.offsetTop - reelScroller.scrollTop)
                < Math.abs(
                    slides[closestIndex].offsetTop - reelScroller.scrollTop
                ) ? index : closestIndex
        ), 0);
        const nextIndex = Math.max(0, Math.min(
            slides.length - 1,
            currentIndex + direction
        ));
        if (nextIndex !== currentIndex) {
            reelScroller.scrollTo({
                top: slides[nextIndex].offsetTop,
                behavior: reducedMotion ? 'auto' : 'smooth',
            });
        }
    };

    const isReelSwipeControl = (target) => Boolean(target.closest(
        'button, input, select, textarea, [role="button"], .lxreel__cart-link'
    ));

    reelScroller.addEventListener('pointerdown', (event) => {
        if (
            !event.target.closest('[data-lxreel-slide]')
            || isReelSwipeControl(event.target)
        ) {
            reelVerticalGesture = null;
            return;
        }
        reelVerticalGesture = {
            x: event.clientX,
            y: event.clientY,
            scrollTop: reelScroller.scrollTop,
        };
    });

    reelScroller.addEventListener('pointerup', (event) => {
        if (!reelVerticalGesture) {
            return;
        }

        const deltaX = event.clientX - reelVerticalGesture.x;
        const deltaY = event.clientY - reelVerticalGesture.y;
        const nativeScrollDistance = Math.abs(
            reelScroller.scrollTop - reelVerticalGesture.scrollTop
        );
        reelVerticalGesture = null;

        if (
            nativeScrollDistance < 24
            && Math.abs(deltaY) > 46
            && Math.abs(deltaY) > Math.abs(deltaX) * 1.25
        ) {
            reelSuppressMediaClickUntil = Date.now() + 450;
            reelSuppressNavigationClickUntil = Date.now() + 450;
            snapReelByGesture(deltaY > 0 ? -1 : 1);
        }
    });

    reelScroller.addEventListener('pointercancel', () => {
        reelVerticalGesture = null;
    });

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
        event.preventDefault();
        openReel(card);
    });

    reelRoot.addEventListener('click', (event) => {
        if (
            event.target.closest('.lxreel__media-link, .lxreel__details h1 a, .lxreel__price-row a')
            && Date.now() < reelSuppressNavigationClickUntil
        ) {
            event.preventDefault();
            return;
        }
        if (
            event.target.closest('.lxreel__media-link')
            && Date.now() < reelSuppressMediaClickUntil
        ) {
            event.preventDefault();
            return;
        }
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

            const hasLoadingSkeleton = Boolean(grid?.querySelector(
                '[data-lxhome-skeleton]'
            ));
            loading = true;
            loadButton?.setAttribute('disabled', 'disabled');
            if (hasLoadingSkeleton) {
                loadButton.hidden = true;
            }
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
                grid.querySelectorAll('[data-lxhome-empty], [data-lxhome-skeleton]')
                    .forEach((node) => node.remove());
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
                if (hasLoadingSkeleton) {
                    loadButton.hidden = false;
                }
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
