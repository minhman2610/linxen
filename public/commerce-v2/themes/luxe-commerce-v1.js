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
    const reelCartUrl = body.querySelector('[data-lxreel-cart-url]')?.dataset
        .lxreelCartUrl || '/v2/cart/items';
    let reelObserver = null;
    let reelPreviouslyFocused = null;
    let reelMotionTimer = null;
    let reelMotionSlide = null;

    const MAX_REEL_MEDIA = 6;

    const colorKey = (value) => String(value || '')
        .trim()
        .toLocaleLowerCase('vi');

    const getReelEntries = () => Array.from(
        body.querySelectorAll('[data-lxreel-product]')
    ).map((card) => {
        const image = card.querySelector('[data-lxcv1-product-image]');
        const colorLabel = card.querySelector('[data-lxcv1-color-label]');
        const colors = Array.from(card.querySelectorAll(
            '[data-lxcv1-color-image]'
        )).map((option) => ({
            id: option.dataset.colorId || '',
            label: option.dataset.label || 'Màu sản phẩm',
            hex: option.dataset.colorHex || '#ead8cf',
            media: option.dataset.image ? [{
                url: option.dataset.image,
                thumb_url: option.dataset.image,
            }] : [],
        })).filter((color) => color.media.length > 0);
        const currentLabel = colorKey(colorLabel?.textContent);
        const activeColorIndex = Math.max(0, colors.findIndex(
            (color) => colorKey(color.label) === currentLabel
        ));

        if (colors.length === 0 && (image?.currentSrc || image?.src)) {
            colors.push({
                id: '',
                label: colorLabel?.textContent?.trim() || 'Màu sản phẩm',
                hex: '#ead8cf',
                media: [{
                    url: image.currentSrc || image.src,
                    thumb_url: image.currentSrc || image.src,
                }],
            });
        }

        return {
            activeColorIndex,
            activeMediaIndex: 0,
            motionPaused: false,
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
            mediaTrack.scrollTo({
                left: mediaTrack.clientWidth * nextIndex,
                behavior: 'smooth',
            });
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
        const sizePicker = slide.querySelector('[data-lxreel-sizes]');
        const addCart = slide.querySelector('[data-lxreel-add-cart]');
        const media = color.media.slice(0, MAX_REEL_MEDIA);

        eyebrow.textContent = `LIN XÉN · ${color.label}`;
        mediaTrack.replaceChildren(...media.map((item, mediaIndex) => {
            const frame = document.createElement('figure');
            frame.className = 'lxreel__media-frame';
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
                entry.selectedSizeId = '';
                refreshReelSlide(slide, entry, index);
            });
            return button;
        }));

        const updateGalleryPosition = () => {
            const viewportWidth = Math.max(1, mediaTrack.clientWidth);
            entry.activeMediaIndex = Math.min(
                media.length - 1,
                Math.max(0, Math.round(mediaTrack.scrollLeft / viewportWidth))
            );
            galleryCount.textContent = `${String(entry.activeMediaIndex + 1).padStart(2, '0')} / ${String(media.length).padStart(2, '0')}`;
            galleryThumbs.querySelectorAll('button').forEach((button, buttonIndex) => {
                button.classList.toggle(
                    'is-active',
                    buttonIndex === entry.activeMediaIndex
                );
            });
        };

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
                mediaTrack.scrollTo({
                    left: mediaTrack.clientWidth * mediaIndex,
                    behavior: reducedMotion ? 'auto' : 'smooth',
                });
            });
            return button;
        }));
        mediaTrack.scrollLeft = 0;
        mediaTrack.onscroll = updateGalleryPosition;
        mediaTrack.onpointerdown = () => {
            entry.motionPaused = true;
            if (reelMotionSlide === slide) {
                stopReelMotion();
            }
        };
        updateGalleryPosition();

        const sizes = Array.isArray(color.sizes) ? color.sizes : [];
        const selectedSize = sizes.find((size) => (
            size.sellable_sku_id === entry.selectedSizeId
        ));
        if (!selectedSize) {
            entry.selectedSizeId = '';
        }
        sizePicker.hidden = sizes.length === 0;
        addCart.hidden = sizes.length === 0;
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
            const fallbackById = new Map(entry.colors.map((color) => [
                color.id || colorKey(color.label),
                color,
            ]));
            const enrichedColors = Array.isArray(product?.colors)
                ? product.colors.map((color) => {
                    const fallback = fallbackById.get(
                        color.id || colorKey(color.label)
                    ) || fallbackById.get(colorKey(color.label));
                    const clarityMedia = color.clarity_media_exact_color === false
                        ? []
                        : (Array.isArray(color.clarity_media)
                            ? color.clarity_media
                            : []);
                    const media = [...clarityMedia, ...(
                        Array.isArray(color.media) ? color.media : []
                    )]
                        .filter((item) => item?.url)
                        .filter((item, mediaIndex, items) => (
                            items.findIndex((candidate) => (
                                candidate.url === item.url
                            )) === mediaIndex
                        ))
                        .slice(0, MAX_REEL_MEDIA);

                    return {
                        id: color.id || '',
                        label: color.label || fallback?.label || 'Màu sản phẩm',
                        hex: color.hex || fallback?.hex || '#ead8cf',
                        media: media.length ? media : (fallback?.media || []),
                        sizes: Array.isArray(color.sizes) ? color.sizes.map((size) => ({
                            size: size.size || '',
                            sellable: Boolean(size.sellable),
                            sellable_sku_id: size.sellable_sku_id || '',
                        })) : [],
                    };
                }).filter((color) => color.media.length > 0)
                : [];

            if (enrichedColors.length) {
                const priorColor = selectedReelColor(entry);
                entry.colors = enrichedColors;
                entry.activeColorIndex = Math.max(0, enrichedColors.findIndex(
                    (color) => (
                        color.id === priorColor?.id
                        || colorKey(color.label) === colorKey(priorColor?.label)
                    )
                ));
                entry.activeMediaIndex = 0;
                entry.motionPaused = false;
                entry.selectedSizeId = '';
                refreshReelSlide(slide, entry, index);
            }
        } catch (error) {
            // Keep the exact-color catalogue cover when detail media is unavailable.
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
        detailsTop.append(eyebrow, close);

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
        galleryGuide.textContent = 'Vuốt ngang xem góc ảnh';
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
        const sizePicker = document.createElement('div');
        sizePicker.className = 'lxreel__size-picker';
        sizePicker.dataset.lxreelSizes = 'true';
        sizePicker.setAttribute('aria-label', 'Chọn kích thước');

        const commerce = document.createElement('div');
        commerce.className = 'lxreel__commerce';
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

        const actions = document.createElement('div');
        actions.className = 'lxreel__actions';
        const detailsLink = document.createElement('a');
        detailsLink.className = 'lxreel__details-link';
        detailsLink.href = entry.url;
        detailsLink.innerHTML = '<span>Khám phá thiết kế</span><b aria-hidden="true">→</b>';
        actions.append(detailsLink);
        details.append(
            detailsTop,
            title,
            priceRow,
            galleryMeta,
            galleryThumbs,
            colorLabel,
            colorPicker,
            sizeLabel,
            sizePicker,
            commerce,
            actions
        );
        slide.append(media, details);
        refreshReelSlide(slide, entry, index);

        return slide;
    };

    const openReel = (selectedIndex) => {
        const reelEntries = getReelEntries();
        if (!reelEntries.length || !reelScroller) {
            return;
        }

        reelPreviouslyFocused = document.activeElement;
        reelScroller.replaceChildren(...reelEntries.map(makeReelSlide));
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
