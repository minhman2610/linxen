(function () {
    'use strict';

    const root = document.querySelector('[data-lxv2-product]');

    if (!root) {
        return;
    }

    const mainImage = root.querySelector('[data-lxv2-main-image]');
    const colorLabel = root.querySelector('[data-lxv2-color-label]');
    const sizeLabel = root.querySelector('[data-lxv2-size-label]');
    const sizesRoot = root.querySelector('[data-lxv2-sizes]');
    const summary = root.querySelector('[data-lxv2-selection]');
    const selectedText = root.querySelector('[data-lxv2-selected-text]');
    const selectedStock = root.querySelector('[data-lxv2-selected-stock]');
    const buyButton = root.querySelector('[data-lxv2-buy]');
    const priceRoot = root.querySelector('[data-lxv2-price]');

    let selectedColor = null;
    let selectedSize = null;

    function money(value) {
        const amount = Number(value || 0);

        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0
        }).format(amount);
    }

    function setMainImage(url) {
        if (!mainImage || !url) {
            return;
        }

        mainImage.src = url;
    }

    function activateThumb(button) {
        root.querySelectorAll('[data-lxv2-thumb]').forEach((item) => {
            item.classList.toggle('active', item === button);
        });
    }

    function updateSelection() {
        const ready = selectedColor && selectedSize;

        if (summary) {
            summary.hidden = !ready;
        }

        if (ready) {
            selectedText.textContent = selectedColor.label + ' · Size ' + selectedSize.size;
            selectedStock.textContent = selectedSize.available > 0
                ? 'Còn ' + Math.floor(selectedSize.available) + ' sản phẩm'
                : 'Tạm hết hàng';

            if (priceRoot && selectedSize.price_current > 0) {
                priceRoot.innerHTML = '<strong>' + money(selectedSize.price_current) + '</strong>'
                    + (
                        selectedSize.price_original > selectedSize.price_current
                            ? '<del>' + money(selectedSize.price_original) + '</del>'
                            : ''
                    );
            }
        }

        if (buyButton) {
            buyButton.disabled = true;
            buyButton.textContent = ready
                ? 'Giỏ hàng sẽ mở ở giai đoạn tiếp theo'
                : 'Chọn màu và kích thước';
        }
    }

    function renderSizes(sizes) {
        if (!sizesRoot) {
            return;
        }

        selectedSize = null;
        sizesRoot.innerHTML = '';

        if (!Array.isArray(sizes) || sizes.length === 0) {
            if (sizeLabel) {
                sizeLabel.textContent = 'Chưa có kích thước khả dụng';
            }

            updateSelection();
            return;
        }

        if (sizeLabel) {
            sizeLabel.textContent = 'Chọn kích thước';
        }

        sizes.forEach((size) => {
            const button = document.createElement('button');
            const sellable = Boolean(size.sellable) && Number(size.available || 0) > 0;

            button.type = 'button';
            button.className = 'lxv2-size-option';
            button.textContent = size.size || '—';
            button.disabled = !sellable;
            button.setAttribute('aria-label', 'Size ' + (size.size || ''));

            button.addEventListener('click', () => {
                sizesRoot.querySelectorAll('.lxv2-size-option').forEach((item) => {
                    item.classList.toggle('active', item === button);
                });

                selectedSize = size;
                if (sizeLabel) {
                    sizeLabel.textContent = 'Size ' + size.size;
                }
                updateSelection();
            });

            sizesRoot.appendChild(button);
        });

        updateSelection();
    }

    root.querySelectorAll('[data-lxv2-thumb]').forEach((button) => {
        button.addEventListener('click', () => {
            activateThumb(button);
            setMainImage(button.dataset.image);
        });
    });

    root.querySelectorAll('[data-lxv2-color]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.disabled) {
                return;
            }

            root.querySelectorAll('[data-lxv2-color]').forEach((item) => {
                item.classList.toggle('active', item === button);
            });

            let sizes = [];

            try {
                sizes = JSON.parse(button.dataset.sizes || '[]');
            } catch (error) {
                sizes = [];
            }

            selectedColor = {
                code: button.dataset.code || '',
                label: button.dataset.label || ''
            };

            if (colorLabel) {
                colorLabel.textContent = selectedColor.label;
            }

            setMainImage(button.dataset.cover);
            renderSizes(sizes);

            const matchingThumb = root.querySelector(
                '[data-lxv2-thumb][data-color="' + CSS.escape(selectedColor.code) + '"]'
            );

            if (matchingThumb) {
                activateThumb(matchingThumb);
            }
        });
    });
})();
