import '../core.js';

const root = document.querySelector('[data-pdp-variant="studio_clarity_v1"]');
const productNode = document.getElementById('lxv2ProductData');

if (root && productNode) {
    document.body.classList.add('lx-pdp-studio-clarity');

    let product = {};

    try {
        product = JSON.parse(productNode.textContent || '{}');
    } catch (error) {
        console.error('Không đọc được PDP payload cho Studio Clarity.', error);
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const colors = Array.isArray(product.colors) ? product.colors : [];

    const normalize = (value) => String(value || '')
        .trim()
        .toLocaleLowerCase('vi');

    const mediaUrl = (item) => String(item?.url || item?.thumb_url || '');

    const activeColor = () => {
        const button = root.querySelector('[data-lxpdp-color].is-active');
        const id = String(button?.dataset.colorId || '');
        const requested = new URL(window.location.href).searchParams.get('color');

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

    const clarityItems = (color) => {
        if (Array.isArray(color?.clarity_media)) {
            return color.clarity_media
                .filter((item) => mediaUrl(item))
                .slice(0, 8);
        }

        return (Array.isArray(color?.media) ? color.media : [])
            .filter((item) => {
                const category = String(item?.category_code || '').toUpperCase();
                return category.includes('PRODUCT_CLARITY') && mediaUrl(item);
            })
            .slice(0, 8);
    };

    const angleLabel = (item) => {
        const blob = `${item?.shot_angle || ''} ${item?.role || ''}`.toUpperCase();

        if (
            blob.includes('FRONT_3Q')
            || blob.includes('FRONT 3Q')
            || blob.includes('3/4 FRONT')
        ) {
            return 'Góc trước 3/4';
        }

        if (
            blob.includes('BACK_3Q')
            || blob.includes('BACK 3Q')
            || blob.includes('3/4 BACK')
        ) {
            return 'Góc sau 3/4';
        }

        if (
            blob.includes('LEFT_SIDE')
            || blob.includes('SIDE_LEFT')
            || blob.includes('LEFT PROFILE')
        ) {
            return 'Góc nghiêng trái';
        }

        if (
            blob.includes('RIGHT_SIDE')
            || blob.includes('SIDE_RIGHT')
            || blob.includes('RIGHT PROFILE')
        ) {
            return 'Góc nghiêng phải';
        }

        if (
            blob.includes('FULL_FRONT')
            || blob.includes('PRODUCT_FRONT')
            || blob.includes('FRONT')
        ) {
            return 'Mặt trước';
        }

        if (
            blob.includes('FULL_BACK')
            || blob.includes('PRODUCT_BACK')
            || blob.includes('BACK')
        ) {
            return 'Mặt sau';
        }

        if (blob.includes('SIDE') || blob.includes('PROFILE')) {
            return 'Góc nghiêng';
        }

        if (
            blob.includes('DETAIL')
            || blob.includes('CLOSE')
            || blob.includes('MACRO')
        ) {
            return 'Chi tiết sản phẩm';
        }

        if (blob.includes('LIFESTYLE') || blob.includes('MODEL')) {
            return 'Trên người mẫu';
        }

        return {
            front: 'Mặt trước',
            back: 'Mặt sau',
            side: 'Góc nghiêng',
            detail: 'Chi tiết sản phẩm',
            lifestyle: 'Trên người mẫu',
        }[String(item?.role || '')] || 'Góc nhìn sản phẩm';
    };

    const angleDescription = (label) => ({
        'Mặt trước': 'Quan sát toàn bộ đường nét và tỷ lệ phía trước.',
        'Mặt sau': 'Kiểm tra phom lưng, khóa và độ rơi của sản phẩm.',
        'Góc trước 3/4': 'Cảm nhận độ nổi khối và cách phom ôm cơ thể.',
        'Góc sau 3/4': 'Xem rõ chuyển tiếp từ lưng sang hông và gấu.',
        'Góc nghiêng trái': 'Đánh giá độ dày, chiều sâu và đường cong của phom.',
        'Góc nghiêng phải': 'Đánh giá độ dày, chiều sâu và đường cong của phom.',
        'Góc nghiêng': 'Đánh giá độ dày, chiều sâu và đường cong của phom.',
        'Chi tiết sản phẩm': 'Nhìn gần chất liệu và điểm nhấn thiết kế.',
        'Trên người mẫu': 'Hình dung tỷ lệ sản phẩm khi mặc thực tế.',
    }[label] || 'Một góc nhìn đã được chọn để làm rõ sản phẩm.');

    const makeAngleCard = (item, index, color) => {
        const label = angleLabel(item);
        const figure = document.createElement('figure');
        figure.className = `lxc-angle-card lxc-angle-card--${Math.min(index + 1, 8)}`;
        figure.dataset.lxcClarityItem = String(index);

        const media = document.createElement('div');
        media.className = 'lxc-angle-card__media';

        const image = document.createElement('img');
        image.src = mediaUrl(item);
        image.alt = `${product.name || 'Sản phẩm'} — ${color?.label || ''} — ${label}`;
        image.loading = index === 0 ? 'eager' : 'lazy';
        image.decoding = 'async';

        const number = document.createElement('span');
        number.textContent = String(index + 1).padStart(2, '0');

        media.append(image, number);

        const caption = document.createElement('figcaption');
        const kicker = document.createElement('small');
        kicker.textContent = 'Góc nhìn';
        const title = document.createElement('h3');
        title.textContent = label;
        const description = document.createElement('p');
        description.textContent = angleDescription(label);

        caption.append(kicker, title, description);
        figure.append(media, caption);

        return { figure, label };
    };

    const renderClarity = (color) => {
        const grid = root.querySelector('[data-lxc-clarity-grid]');
        const nav = root.querySelector('[data-lxc-angle-nav]');
        const empty = root.querySelector('[data-lxc-clarity-empty]');
        const colorLabel = root.querySelector('[data-lxc-clarity-color]');

        if (colorLabel) {
            colorLabel.textContent = color?.label || 'Màu đang chọn';
        }

        if (!grid || !nav || !empty) {
            return;
        }

        const items = clarityItems(color);

        if (!items.length) {
            grid.replaceChildren();
            nav.replaceChildren();
            grid.hidden = true;
            nav.hidden = true;
            empty.hidden = false;
            return;
        }

        const cards = document.createDocumentFragment();
        const chips = document.createDocumentFragment();

        items.forEach((item, index) => {
            const { figure, label } = makeAngleCard(item, index, color);
            cards.append(figure);

            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.lxcAngleJump = String(index);
            button.setAttribute('aria-label', `Đi tới ảnh ${label}`);

            const number = document.createElement('span');
            number.textContent = String(index + 1).padStart(2, '0');
            button.append(number, document.createTextNode(label));
            button.addEventListener('click', () => {
                figure.scrollIntoView({
                    behavior: reducedMotion ? 'auto' : 'smooth',
                    block: 'center',
                });
            });

            chips.append(button);
        });

        grid.replaceChildren(cards);
        nav.replaceChildren(chips);
        grid.hidden = false;
        nav.hidden = false;
        empty.hidden = true;

        window.requestAnimationFrame(() => {
            grid.classList.add('is-visible');
            grid.querySelectorAll('.lxc-angle-card').forEach((card, index) => {
                card.style.transitionDelay = reducedMotion
                    ? '0ms'
                    : `${Math.min(index * 55, 280)}ms`;
                card.classList.add('is-visible');
            });
        });
    };

    const normalizeSizeButtons = () => {
        root.querySelectorAll('[data-lxpdp-size]').forEach((button) => {
            const label = String(button.textContent || '').trim();

            if (button.disabled) {
                button.setAttribute(
                    'aria-label',
                    `Size ${label} — hết hàng ở màu đang chọn`
                );
                button.title = `Size ${label} — hết hàng`;
            } else {
                button.setAttribute('aria-label', `Chọn size ${label}`);
                button.title = `Chọn size ${label}`;
            }
        });
    };

    const buyButton = root.querySelector('[data-lxpdp-buy]');
    const cartForm = root.querySelector('[data-lxpdp-cart-form]');
    const dockButton = root.querySelector('[data-lxc-dock-submit]');
    const dockLabel = root.querySelector('[data-lxc-dock-label]');
    const dockSelection = root.querySelector('[data-lxc-dock-selection]');
    const sizeSelector = root.querySelector('.lxc-selector--size');
    const selectedText = root.querySelector('[data-lxpdp-selected-text]');
    const colorText = root.querySelector('[data-lxpdp-color-label]');

    const syncDock = () => {
        if (!dockButton || !dockLabel) {
            return;
        }

        const productInStock = Boolean(product.in_stock);
        const ready = Boolean(buyButton && !buyButton.disabled);
        const buyText = String(buyButton?.textContent || '').trim();
        const selectionText = String(selectedText?.textContent || '').trim();
        const selectedColorText = String(colorText?.textContent || '').trim();

        if (dockSelection) {
            dockSelection.textContent = selectionText
                || (selectedColorText
                    ? `${selectedColorText} · Chọn size`
                    : 'Chọn màu & size');
        }

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
        if (
            dockButton.dataset.mode === 'submit'
            && buyButton
            && !buyButton.disabled
        ) {
            cartForm?.requestSubmit();
            return;
        }

        sizeSelector?.scrollIntoView({
            behavior: reducedMotion ? 'auto' : 'smooth',
            block: 'center',
        });

        sizeSelector?.animate(
            [
                { boxShadow: '0 0 0 0 rgba(91,95,242,0)' },
                { boxShadow: '0 0 0 9px rgba(91,95,242,.16)' },
                { boxShadow: '0 0 0 0 rgba(91,95,242,0)' },
            ],
            {
                duration: reducedMotion ? 1 : 850,
                easing: 'ease-out',
            }
        );
    });

    const applyColor = (color) => {
        const hex = /^#[0-9a-f]{3,8}$/i.test(String(color?.hex || ''))
            ? String(color.hex)
            : '#5b5ff2';

        root.style.setProperty('--lxc-current-color', hex);
        renderClarity(color);

        window.requestAnimationFrame(() => {
            normalizeSizeButtons();
            syncDock();
        });
    };

    root.querySelectorAll('[data-lxpdp-color]').forEach((button) => {
        button.addEventListener('click', () => {
            const color = colors.find(
                (item) => String(item.id) === String(button.dataset.colorId)
            );

            if (color) {
                window.requestAnimationFrame(() => applyColor(color));
            }
        });
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

    [buyButton, selectedText, colorText]
        .filter(Boolean)
        .forEach((node) => {
            if (!('MutationObserver' in window)) {
                return;
            }

            new MutationObserver(syncDock).observe(node, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['disabled', 'hidden'],
            });
        });

    root.addEventListener('click', (event) => {
        if (
            event.target.closest(
                '[data-lxpdp-color], [data-lxpdp-size]'
            )
        ) {
            window.requestAnimationFrame(() => {
                normalizeSizeButtons();
                syncDock();
            });
        }
    });

    const revealItems = Array.from(
        root.querySelectorAll('[data-lxc-reveal]')
    );

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
            threshold: .08,
            rootMargin: '0px 0px -5% 0px',
        });

        revealItems.forEach((item) => observer.observe(item));
    }

    normalizeSizeButtons();
    syncDock();
    applyColor(activeColor());

    root.dispatchEvent(new CustomEvent('linxen:pdp:studio-clarity-ready', {
        bubbles: true,
        detail: {
            variant: 'studio_clarity_v1',
            product_id: product.id || null,
        },
    }));
}
