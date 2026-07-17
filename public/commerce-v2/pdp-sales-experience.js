(() => {
    'use strict';

    const root = document.querySelector('[data-lxpdp]');
    const dataNode = document.getElementById('lxv2ProductData');

    if (!root || !dataNode) {
        return;
    }

    let product;

    try {
        product = JSON.parse(dataNode.textContent || '{}');
    } catch (error) {
        console.error('PDP payload không hợp lệ.', error);
        return;
    }

    const colors = Array.isArray(product.colors) ? product.colors : [];
    const defaultColorId = String(product.default_color_id || '');
    const mainImage = root.querySelector('[data-lxpdp-main-image]');
    const stageFigure = root.querySelector('.lxpdp-gallery__figure');
    const thumbs = root.querySelector('[data-lxpdp-thumbs]');
    const counter = root.querySelector('[data-lxpdp-image-counter]');
    const roleLabel = root.querySelector('[data-lxpdp-image-role]');
    const galleryNotice = root.querySelector('[data-lxpdp-gallery-notice]');
    const previousButton = root.querySelector('[data-lxpdp-gallery-prev]');
    const nextButton = root.querySelector('[data-lxpdp-gallery-next]');
    const colorLabel = root.querySelector('[data-lxpdp-color-label]');
    const sizeList = root.querySelector('[data-lxpdp-sizes]');
    const selection = root.querySelector('[data-lxpdp-selection]');
    const selectedText = root.querySelector('[data-lxpdp-selected-text]');
    const selectedStock = root.querySelector('[data-lxpdp-selected-stock]');
    const skuInput = root.querySelector('[data-lxpdp-sku-input]');
    const buyButton = root.querySelector('[data-lxpdp-buy]');
    const cartForm = root.querySelector('[data-lxpdp-cart-form]');
    const mobileSelection = root.querySelector('[data-lxpdp-mobile-selection]');
    const mobileSubmit = root.querySelector('[data-lxpdp-mobile-submit]');
    const dialog = root.querySelector('[data-lxpdp-size-advisor]');
    const sizeForm = root.querySelector('[data-lxpdp-size-form]');
    const sizeResult = root.querySelector('[data-lxpdp-size-result]');
    const clearSizeButton = root.querySelector('[data-lxpdp-size-clear]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const profileStorageKey = 'linxen_pdp_size_profile_v1';

    const state = {
        color: null,
        size: null,
        images: [],
        imageIndex: 0,
        touchStartX: null,
    };

    const normalize = (value) => String(value || '')
        .trim()
        .toLocaleLowerCase('vi');

    const requestedColor = new URL(window.location.href)
        .searchParams
        .get('color');

    const initialColor = colors.find((color) => {
        if (!requestedColor) {
            return String(color.id) === defaultColorId;
        }

        return [
            color.id,
            color.code,
            color.key,
        ].map(normalize).includes(normalize(requestedColor));
    }) || colors.find((color) => String(color.id) === defaultColorId)
        || colors.find((color) => color.sellable)
        || colors[0]
        || null;

    const roleName = (role) => ({
        hero: 'Ảnh chính',
        front: 'Mặt trước',
        side: 'Góc nghiêng',
        back: 'Mặt sau',
        detail: 'Chi tiết',
        lifestyle: 'Hình ảnh mặc',
    })[role] || 'Hình ảnh sản phẩm';

    const updateUrlColor = (color) => {
        const url = new URL(window.location.href);

        if (color?.code || color?.id) {
            url.searchParams.set('color', color.code || color.id);
        } else {
            url.searchParams.delete('color');
        }

        window.history.replaceState({}, '', url);
    };

    const renderImage = (index) => {
        if (!state.images.length || !mainImage) {
            if (mainImage) {
                mainImage.removeAttribute('src');
                mainImage.alt = state.color?.label
                    ? `Màu ${state.color.label} chưa có ảnh đã duyệt`
                    : 'Chưa có ảnh sản phẩm đã duyệt';
            }
            stageFigure?.classList.remove('is-loading');
            stageFigure?.classList.add('is-empty');
            if (counter) {
                counter.textContent = '';
            }
            if (roleLabel) {
                roleLabel.textContent = 'Chưa có ảnh màu này';
            }
            if (galleryNotice) {
                galleryNotice.hidden = false;
            }
            previousButton && (previousButton.disabled = true);
            nextButton && (nextButton.disabled = true);
            return;
        }

        stageFigure?.classList.remove('is-empty');

        state.imageIndex = Math.max(
            0,
            Math.min(index, state.images.length - 1)
        );
        const image = state.images[state.imageIndex];

        stageFigure?.classList.add('is-loading');

        const preload = new Image();
        preload.onload = () => {
            mainImage.src = image.url;
            mainImage.alt = `${product.name || 'Sản phẩm'} - ${state.color?.label || ''} - ${roleName(image.role)}`;
            stageFigure?.classList.remove('is-loading');
        };
        preload.onerror = () => {
            mainImage.src = image.url;
            stageFigure?.classList.remove('is-loading');
        };
        preload.src = image.url;

        if (counter) {
            counter.textContent = `${state.imageIndex + 1} / ${state.images.length}`;
        }

        if (roleLabel) {
            roleLabel.textContent = roleName(image.role);
        }

        thumbs?.querySelectorAll('[data-lxpdp-thumb]').forEach((button) => {
            const active = Number(button.dataset.index) === state.imageIndex;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-current', active ? 'true' : 'false');
        });

        previousButton && (previousButton.disabled = state.images.length < 2);
        nextButton && (nextButton.disabled = state.images.length < 2);
        galleryNotice && (galleryNotice.hidden = true);
    };

    const renderThumbs = () => {
        if (!thumbs) {
            return;
        }

        thumbs.innerHTML = '';

        state.images.forEach((image, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `lxpdp-gallery__thumb${index === 0 ? ' is-active' : ''}`;
            button.dataset.lxpdpThumb = '';
            button.dataset.index = String(index);
            button.setAttribute('aria-label', `Xem ảnh ${index + 1}`);
            button.setAttribute('aria-current', index === 0 ? 'true' : 'false');

            const img = document.createElement('img');
            img.src = image.thumb_url || image.url;
            img.alt = '';
            img.width = 96;
            img.height = 120;
            img.loading = 'lazy';
            img.decoding = 'async';

            button.appendChild(img);
            button.addEventListener('click', () => renderImage(index));
            thumbs.appendChild(button);
        });
    };

    const renderGallery = (color) => {
        state.images = Array.isArray(color?.media)
            ? color.media.slice(0, Number(product.gallery_limit || 6))
            : [];
        state.imageIndex = 0;
        renderThumbs();
        renderImage(0);
    };

    const resetSelection = () => {
        state.size = null;
        if (skuInput) {
            skuInput.value = '';
        }
        if (buyButton) {
            buyButton.disabled = true;
            buyButton.textContent = 'Chọn kích thước';
        }
        if (selection) {
            selection.hidden = true;
        }
        if (mobileSubmit) {
            mobileSubmit.disabled = true;
        }
        if (mobileSelection) {
            mobileSelection.textContent = state.color
                ? `${state.color.label} · Chọn size`
                : 'Chọn màu và size';
        }
    };

    const selectSize = (size, button) => {
        state.size = size;

        sizeList?.querySelectorAll('[data-lxpdp-size]').forEach((item) => {
            const active = item === button;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        if (skuInput) {
            skuInput.value = size.sellable_sku_id || '';
        }

        if (selection) {
            selection.hidden = false;
        }

        if (selectedText) {
            selectedText.textContent = `${state.color.label} · Size ${size.size}`;
        }

        if (selectedStock) {
            selectedStock.textContent = `${Math.floor(Number(size.available || 0))} sản phẩm khả dụng`;
        }

        if (buyButton) {
            buyButton.disabled = !size.sellable;
            buyButton.textContent = size.sellable
                ? 'Thêm vào giỏ hàng'
                : 'Tạm hết hàng';
        }

        if (mobileSubmit) {
            mobileSubmit.disabled = !size.sellable;
        }

        if (mobileSelection) {
            mobileSelection.textContent = `${state.color.label} · Size ${size.size}`;
        }
    };

    const renderSizes = (color) => {
        if (!sizeList) {
            return;
        }

        sizeList.innerHTML = '';
        resetSelection();

        const sizes = Array.isArray(color?.sizes) ? color.sizes : [];

        sizes.forEach((size) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'lxpdp-size-button';
            button.dataset.lxpdpSize = '';
            button.textContent = size.size || '—';
            button.disabled = !size.sellable;
            button.setAttribute('aria-pressed', 'false');
            button.setAttribute(
                'aria-label',
                size.sellable
                    ? `Chọn size ${size.size}, còn ${Math.floor(Number(size.available || 0))}`
                    : `Size ${size.size} tạm hết`
            );
            button.addEventListener('click', () => selectSize(size, button));
            sizeList.appendChild(button);
        });

        if (!sizes.length) {
            const empty = document.createElement('span');
            empty.textContent = 'Chưa có size khả dụng.';
            empty.className = 'lxv2-muted';
            sizeList.appendChild(empty);
        }
    };

    const selectColor = (color, updateUrl = true) => {
        state.color = color;

        root.querySelectorAll('[data-lxpdp-color]').forEach((button) => {
            const active = String(button.dataset.colorId) === String(color?.id);
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        if (colorLabel) {
            colorLabel.textContent = color?.label || 'Chọn màu';
        }

        renderGallery(color);
        renderSizes(color);

        if (updateUrl) {
            updateUrlColor(color);
        }
    };

    root.querySelectorAll('[data-lxpdp-color]').forEach((button) => {
        button.addEventListener('click', () => {
            const color = colors.find(
                (item) => String(item.id) === String(button.dataset.colorId)
            );

            if (color) {
                selectColor(color);
            }
        });
    });

    previousButton?.addEventListener('click', () => {
        const next = state.imageIndex <= 0
            ? state.images.length - 1
            : state.imageIndex - 1;
        renderImage(next);
    });

    nextButton?.addEventListener('click', () => {
        const next = state.imageIndex >= state.images.length - 1
            ? 0
            : state.imageIndex + 1;
        renderImage(next);
    });

    stageFigure?.addEventListener('touchstart', (event) => {
        state.touchStartX = event.touches[0]?.clientX ?? null;
    }, {passive: true});

    stageFigure?.addEventListener('touchend', (event) => {
        if (state.touchStartX === null || state.images.length < 2) {
            return;
        }

        const endX = event.changedTouches[0]?.clientX ?? state.touchStartX;
        const delta = endX - state.touchStartX;
        state.touchStartX = null;

        if (Math.abs(delta) < 45) {
            return;
        }

        if (delta < 0) {
            nextButton?.click();
        } else {
            previousButton?.click();
        }
    }, {passive: true});

    mobileSubmit?.addEventListener('click', () => {
        if (!mobileSubmit.disabled) {
            cartForm?.requestSubmit();
        }
    });

    const loadSavedProfile = () => {
        if (!sizeForm) {
            return;
        }

        try {
            const saved = JSON.parse(
                window.localStorage.getItem(profileStorageKey) || '{}'
            );

            Object.entries(saved).forEach(([key, value]) => {
                const field = sizeForm.elements.namedItem(key);

                if (field && value !== null && value !== '') {
                    field.value = value;
                }
            });
        } catch (error) {
            window.localStorage.removeItem(profileStorageKey);
        }
    };

    const saveProfile = (payload) => {
        const allowed = [
            'height_cm',
            'weight_kg',
            'bust_cm',
            'waist_cm',
            'hip_cm',
            'fit_preference',
        ];
        const saved = {};

        allowed.forEach((key) => {
            if (payload[key] !== undefined && payload[key] !== '') {
                saved[key] = payload[key];
            }
        });

        window.localStorage.setItem(
            profileStorageKey,
            JSON.stringify(saved)
        );
    };

    const openAdvisor = () => {
        if (!dialog) {
            return;
        }

        loadSavedProfile();

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
    };

    root.querySelectorAll('[data-lxpdp-size-advisor-open]').forEach((button) => {
        button.addEventListener('click', openAdvisor);
    });

    clearSizeButton?.addEventListener('click', () => {
        window.localStorage.removeItem(profileStorageKey);
        sizeForm?.reset();

        if (sizeResult) {
            sizeResult.hidden = true;
            sizeResult.innerHTML = '';
        }
    });

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const renderAdvice = (payload) => {
        if (!sizeResult) {
            return;
        }

        const recommendation = payload?.recommendation || {};
        const status = recommendation.status || 'unavailable';
        const reasons = Array.isArray(recommendation.reasons)
            ? recommendation.reasons
            : [];
        const alternatives = Array.isArray(recommendation.alternatives)
            ? recommendation.alternatives
            : [];
        const fitEvidence = Array.isArray(payload?.fit_evidence?.items)
            ? payload.fit_evidence.items
            : [];
        const profileSource = payload?.profile?.source_label || '';
        let title = 'Cần thêm thông tin';
        let body = 'Vui lòng nhập đủ số đo để nhận gợi ý.';

        if (status === 'recommended') {
            title = `Gợi ý size ${recommendation.recommended_size}`;
            body = `Độ tin cậy: ${recommendation.confidence === 'medium' ? 'Trung bình' : 'Tham khảo'}.`;
        } else if (status === 'recommended_size_unavailable') {
            title = `Size phù hợp theo profile: ${recommendation.profile_size}`;
            body = alternatives.length
                ? `Size này đang hết ở màu đã chọn. Size còn bán: ${alternatives.join(', ')}.`
                : 'Màu đã chọn chưa còn size thay thế.';
        } else if (status === 'outside_profile') {
            title = 'Cần tư vấn thủ công';
            body = 'Số đo nằm ngoài profile chung đang được cấu hình.';
        }

        sizeResult.innerHTML = `
            <h3>${escapeHtml(title)}</h3>
            <p>${escapeHtml(body)}</p>
            ${reasons.length ? `<ul>${reasons.map((reason) => `<li>${escapeHtml(reason)}</li>`).join('')}</ul>` : ''}
            ${fitEvidence.length ? `
                <div class="lxpdp-advisor__evidence">
                    <strong>Đối chiếu số đo thành phẩm size ${escapeHtml(payload?.fit_evidence?.size || '')}</strong>
                    <ul>${fitEvidence.map((item) => `
                        <li>
                            ${escapeHtml(item.label)}: cơ thể ${escapeHtml(item.body_cm)} cm · thành phẩm ${escapeHtml(item.garment_cm)} cm · chênh ${escapeHtml(item.difference_cm)} cm
                        </li>
                    `).join('')}</ul>
                    <small>${escapeHtml(payload?.fit_evidence?.message || '')}</small>
                </div>
            ` : ''}
            ${profileSource ? `<small>Nguồn gợi ý: ${escapeHtml(profileSource)}</small>` : ''}
            ${payload?.disclaimer ? `<small>${escapeHtml(payload.disclaimer)}</small>` : ''}
        `;
        sizeResult.hidden = false;
    };

    sizeForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(sizeForm);
        const payload = Object.fromEntries(formData.entries());
        payload.color_id = state.color?.id || '';

        Object.keys(payload).forEach((key) => {
            if (payload[key] === '') {
                delete payload[key];
            }
        });

        saveProfile(payload);

        const submit = sizeForm.querySelector('button[type="submit"]');
        const original = submit?.textContent || 'Kiểm tra size';

        if (submit) {
            submit.disabled = true;
            submit.textContent = 'Đang kiểm tra...';
        }

        try {
            const response = await fetch(root.dataset.sizeAdviceUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const json = await response.json();

            if (!response.ok || !json.ok) {
                const message = json.message
                    || json.error?.message
                    || 'Không thể tư vấn size lúc này.';
                throw new Error(message);
            }

            renderAdvice(json.data);
        } catch (error) {
            if (sizeResult) {
                sizeResult.hidden = false;
                sizeResult.innerHTML = `<h3>Chưa thể kiểm tra</h3><p>${escapeHtml(error.message)}</p>`;
            }
        } finally {
            if (submit) {
                submit.disabled = false;
                submit.textContent = original;
            }
        }
    });

    if (initialColor) {
        selectColor(initialColor, Boolean(requestedColor));
    } else {
        resetSelection();
        renderGallery(null);
    }
})();
