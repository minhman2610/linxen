import '../core.js';

const root = document.querySelector('[data-pdp-variant="studio_signal_v1"]');
const productNode = document.getElementById('lxv2ProductData');

if (root && productNode) {
    document.body.classList.add('lx-pdp-studio-signal');

    let product = {};

    try {
        product = JSON.parse(productNode.textContent || '{}');
    } catch (error) {
        console.error('Không đọc được PDP payload cho Studio Signal.', error);
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const colors = Array.isArray(product.colors) ? product.colors : [];
    const roleLabels = {
        hero: 'Tổng thể',
        front: 'Mặt trước',
        side: 'Góc nghiêng',
        back: 'Mặt sau',
        detail: 'Chi tiết',
        lifestyle: 'Trên người mẫu',
    };

    const escapeHtml = (value) => String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const activeColor = () => {
        const button = root.querySelector('[data-lxpdp-color].is-active');
        const id = String(button?.dataset.colorId || '');
        const requested = new URL(window.location.href)
            .searchParams
            .get('color');
        const normalize = (value) => String(value || '')
            .trim()
            .toLocaleLowerCase('vi');

        return colors.find((color) => String(color.id) === id)
            || colors.find((color) => requested && [
                color.id,
                color.code,
                color.key,
            ].map(normalize).includes(normalize(requested)))
            || colors.find((color) => String(color.id) === String(product.default_color_id || ''))
            || colors.find((color) => color.sellable && Number(color.available || 0) > 0)
            || colors[0]
            || null;
    };

    const roleLabel = (role) => roleLabels[String(role || '')] || 'Hình ảnh sản phẩm';
    const mediaUrl = (item) => String(item?.url || item?.thumb_url || '');

    const normalizeSizeButtons = () => {
        root.querySelectorAll('[data-lxpdp-size]').forEach((button) => {
            const label = String(button.textContent || '').trim();

            if (button.disabled) {
                button.setAttribute('aria-label', `Size ${label} — hết hàng ở màu đang chọn`);
                button.title = `Size ${label} — hết hàng`;
            } else {
                button.setAttribute('aria-label', `Chọn size ${label}`);
                button.title = `Chọn size ${label}`;
            }
        });
    };

    const renderCampaignGrid = (color) => {
        const panel = root.querySelector('[data-lxs-campaign-grid]');

        if (!panel) {
            return;
        }

        const items = Array.isArray(color?.media)
            ? color.media.slice(0, 6).filter((item) => mediaUrl(item))
            : [];

        if (!items.length) {
            panel.hidden = true;
            return;
        }

        const fragment = document.createDocumentFragment();

        items.forEach((item, index) => {
            const figure = document.createElement('figure');
            figure.className = `lxs-media-grid__item lxs-media-grid__item--${(index % 5) + 1}`;

            const image = document.createElement('img');
            image.src = mediaUrl(item);
            image.alt = `${product.name || 'Sản phẩm'} — ${color?.label || ''} — ${roleLabel(item?.role)}`;
            image.loading = 'lazy';
            image.decoding = 'async';

            const caption = document.createElement('figcaption');
            caption.textContent = roleLabel(item?.role);

            figure.append(image, caption);
            fragment.append(figure);
        });

        panel.replaceChildren(fragment);
        panel.hidden = false;
    };

    const updateDesignVisual = (color) => {
        const image = root.querySelector('[data-lxs-design-image]');
        const items = Array.isArray(color?.media)
            ? color.media.filter((item) => mediaUrl(item))
            : [];
        const preferred = items[1] || items[0];

        if (image && preferred) {
            image.src = mediaUrl(preferred);
            image.alt = `${product.name || 'Sản phẩm'} — ${color?.label || ''} — chi tiết thiết kế`;
        }
    };

    const applyColorAtmosphere = (color) => {
        const hex = /^#[0-9a-f]{3,8}$/i.test(String(color?.hex || ''))
            ? String(color.hex)
            : '#5b5ff2';

        root.style.setProperty('--lxs-current-color', hex);
        renderCampaignGrid(color);
        updateDesignVisual(color);

        window.requestAnimationFrame(normalizeSizeButtons);
    };

    root.querySelectorAll('[data-lxpdp-color]').forEach((button) => {
        button.addEventListener('click', () => {
            const color = colors.find(
                (item) => String(item.id) === String(button.dataset.colorId)
            );

            if (color) {
                window.requestAnimationFrame(() => applyColorAtmosphere(color));
            }
        });
    });

    applyColorAtmosphere(activeColor());

    /* Design hotspots */
    const activateHotspot = (index) => {
        root.querySelectorAll('[data-lxs-hotspot]').forEach((button) => {
            const active = Number(button.dataset.lxsHotspot) === index;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        root.querySelectorAll('[data-lxs-hotspot-card]').forEach((card) => {
            card.classList.toggle(
                'is-active',
                Number(card.dataset.lxsHotspotCard) === index
            );
        });
    };

    root.querySelectorAll('[data-lxs-hotspot]').forEach((button) => {
        button.addEventListener('click', () => {
            activateHotspot(Number(button.dataset.lxsHotspot || 0));
        });
    });

    root.querySelectorAll('[data-lxs-hotspot-card]').forEach((card) => {
        card.addEventListener('click', () => {
            activateHotspot(Number(card.dataset.lxsHotspotCard || 0));
        });
    });

    /* Campaign / real product tabs */
    root.querySelectorAll('[data-lxs-media-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            const key = String(button.dataset.lxsMediaTab || '');

            root.querySelectorAll('[data-lxs-media-tab]').forEach((tab) => {
                const active = tab === button;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            root.querySelectorAll('[data-lxs-media-panel]').forEach((panel) => {
                const active = String(panel.dataset.lxsMediaPanel || '') === key;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        });
    });

    /* Size Studio */
    const sizeDataNode = root.querySelector('[data-lxs-size-chart-data]');
    let sizeChart = null;

    if (sizeDataNode) {
        try {
            sizeChart = JSON.parse(sizeDataNode.textContent || '{}');
        } catch (error) {
            console.error('Không đọc được dữ liệu Size Studio.', error);
        }
    }

    const sizePoint = (key) => {
        if (!sizeChart || !Array.isArray(sizeChart.points)) {
            return null;
        }

        const aliases = {
            bust: ['bust', 'ngực'],
            waist: ['waist', 'eo'],
            hip: ['hip', 'mông', 'hông'],
            length: ['length', 'dài'],
        }[key] || [key];

        return sizeChart.points.find((point) => {
            const blob = `${point?.code || ''} ${point?.label || ''}`.toLocaleLowerCase('vi');
            return aliases.some((alias) => blob.includes(alias));
        }) || null;
    };

    const displayPointValue = (point, size) => {
        if (!point) {
            return '—';
        }

        const display = point.display_values?.[size];
        const raw = point.values?.[size];
        return String(display ?? raw ?? '—');
    };

    const selectStudioSize = (size) => {
        root.querySelectorAll('[data-lxs-size-card]').forEach((button) => {
            const active = String(button.dataset.lxsSizeCard || '') === String(size);
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        const activeSize = root.querySelector('[data-lxs-active-size]');
        if (activeSize) {
            activeSize.textContent = String(size);
        }

        root.querySelectorAll('[data-lxs-measure-value]').forEach((node) => {
            const key = String(node.dataset.lxsMeasureValue || '');
            node.textContent = displayPointValue(sizePoint(key), size);
        });
    };

    root.querySelectorAll('[data-lxs-size-card]').forEach((button) => {
        button.addEventListener('click', () => {
            selectStudioSize(String(button.dataset.lxsSizeCard || ''));
        });
    });

    const activateMeasure = (key) => {
        root.querySelectorAll('[data-lxs-measure-row]').forEach((button) => {
            button.classList.toggle(
                'is-active',
                String(button.dataset.lxsMeasureRow || '') === key
            );
        });

        root.querySelectorAll('[data-lxs-diagram-measure]').forEach((group) => {
            group.classList.toggle(
                'is-active',
                String(group.dataset.lxsDiagramMeasure || '') === key
            );
        });
    };

    root.querySelectorAll('[data-lxs-measure-row]').forEach((button) => {
        button.addEventListener('click', () => {
            activateMeasure(String(button.dataset.lxsMeasureRow || ''));
        });
    });

    const sizeDialog = root.querySelector('[data-lxs-size-table-dialog]');
    root.querySelector('[data-lxs-size-table-open]')?.addEventListener('click', () => {
        if (sizeDialog?.showModal) {
            sizeDialog.showModal();
        } else {
            sizeDialog?.setAttribute('open', '');
        }
    });

    /* Mobile commerce dock */
    const buyButton = root.querySelector('[data-lxpdp-buy]');
    const cartForm = root.querySelector('[data-lxpdp-cart-form]');
    const dockButton = root.querySelector('[data-lxs-dock-submit]');
    const dockLabel = root.querySelector('[data-lxs-dock-label]');
    const selector = root.querySelector('.lxs-selector--size');

    const syncDock = () => {
        if (!dockButton || !dockLabel) {
            return;
        }

        const productInStock = Boolean(product.in_stock);
        const ready = buyButton && !buyButton.disabled;
        const buyText = String(buyButton?.textContent || '').trim();

        if (ready) {
            dockButton.disabled = false;
            dockButton.dataset.mode = 'submit';
            dockLabel.textContent = 'Thêm vào giỏ';
            return;
        }

        if (!productInStock || /hết hàng/i.test(buyText)) {
            dockButton.disabled = true;
            dockButton.dataset.mode = 'soldout';
            dockLabel.textContent = 'Tạm hết hàng';
            return;
        }

        dockButton.disabled = false;
        dockButton.dataset.mode = 'guide';
        dockLabel.textContent = /kích thước|size/i.test(buyText)
            ? 'Chọn size'
            : 'Chọn màu & size';
    };

    dockButton?.addEventListener('click', () => {
        if (dockButton.dataset.mode === 'submit' && buyButton && !buyButton.disabled) {
            cartForm?.requestSubmit();
            return;
        }

        selector?.scrollIntoView({
            behavior: reducedMotion ? 'auto' : 'smooth',
            block: 'center',
        });
        selector?.animate(
            [
                { boxShadow: '0 0 0 0 rgba(91,95,242,0)' },
                { boxShadow: '0 0 0 8px rgba(91,95,242,.15)' },
                { boxShadow: '0 0 0 0 rgba(91,95,242,0)' },
            ],
            { duration: 850, easing: 'ease-out' }
        );
    });

    const sizeList = root.querySelector('[data-lxpdp-sizes]');
    if (sizeList && 'MutationObserver' in window) {
        new MutationObserver(() => {
            normalizeSizeButtons();
            syncDock();
        }).observe(sizeList, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['disabled', 'class'],
        });
    }

    if (buyButton && 'MutationObserver' in window) {
        new MutationObserver(syncDock).observe(buyButton, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['disabled'],
        });
    }

    root.addEventListener('click', (event) => {
        if (event.target.closest('[data-lxpdp-color], [data-lxpdp-size]')) {
            window.requestAnimationFrame(() => {
                normalizeSizeButtons();
                syncDock();
            });
        }
    });

    normalizeSizeButtons();
    syncDock();

    /* Scroll to purchase */
    root.querySelectorAll('[data-pdp-scroll-to-purchase]').forEach((button) => {
        button.addEventListener('click', () => {
            root.querySelector('[data-lxs-purchase]')?.scrollIntoView({
                behavior: reducedMotion ? 'auto' : 'smooth',
                block: 'center',
            });
        });
    });

    /* Recently viewed */
    const historyKey = 'linxen_pdp_recently_viewed_v1';
    const current = {
        id: String(product.id || ''),
        name: String(product.name || ''),
        url: window.location.pathname,
        cover_url: String(product.cover_url || product.colors?.[0]?.media?.[0]?.url || ''),
        price_min: Number(product.price_min || 0),
        viewed_at: new Date().toISOString(),
    };
    let history = [];

    try {
        history = JSON.parse(localStorage.getItem(historyKey) || '[]');
        history = Array.isArray(history) ? history : [];
    } catch {
        history = [];
    }

    const previous = history
        .filter((item) => String(item.id || '') !== current.id)
        .slice(0, 8);

    if (current.id && current.name) {
        try {
            localStorage.setItem(
                historyKey,
                JSON.stringify([current, ...previous].slice(0, 8))
            );
        } catch {
            // Private browsing may block storage. The PDP remains functional.
        }
    }

    const recentList = root.querySelector('[data-lxs-recent-list]');
    const recentEmpty = root.querySelector('[data-lxs-recent-empty]');

    if (recentList && previous.length) {
        recentEmpty?.remove();
        recentList.innerHTML = previous.slice(0, 4).map((item) => {
            const price = Number(item.price_min || 0).toLocaleString('vi-VN');
            const image = item.cover_url
                ? `<span><img src="${escapeHtml(item.cover_url)}" alt="${escapeHtml(item.name)}" loading="lazy"></span>`
                : '<span></span>';

            return `<a class="lxs-product-card" href="${escapeHtml(item.url)}">${image}<strong>${escapeHtml(item.name)}</strong><small>${price ? `${price}₫` : ''}</small></a>`;
        }).join('');
    }

    /* Viewport reveal */
    const revealItems = Array.from(root.querySelectorAll('[data-lxs-reveal]'));

    if (!('IntersectionObserver' in window) || reducedMotion) {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    } else {
        const observer = new IntersectionObserver((entries, instance) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                instance.unobserve(entry.target);
            });
        }, {
            threshold: .1,
            rootMargin: '0px 0px -5% 0px',
        });

        revealItems.forEach((item) => observer.observe(item));
    }

    if ('IntersectionObserver' in window) {
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting || entry.target.dataset.lxsSeen === '1') {
                    return;
                }

                entry.target.dataset.lxsSeen = '1';
                root.dispatchEvent(new CustomEvent('linxen:pdp:section-viewed', {
                    bubbles: true,
                    detail: {
                        variant: 'studio_signal_v1',
                        section: entry.target.dataset.pdpSection || null,
                        product_id: product.id || null,
                    },
                }));
            });
        }, { threshold: .28 });

        root.querySelectorAll('[data-pdp-section]').forEach((section) => {
            sectionObserver.observe(section);
        });
    }

    root.dispatchEvent(new CustomEvent('linxen:pdp:studio-signal-ready', {
        bubbles: true,
        detail: {
            variant: 'studio_signal_v1',
            product_id: product.id || null,
        },
    }));
}
