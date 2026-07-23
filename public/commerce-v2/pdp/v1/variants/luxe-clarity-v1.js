import '../core.js';

const root = document.querySelector('[data-pdp-variant="luxe_clarity_v1"]');
const productNode = document.getElementById('lxv2ProductData');

if (root && productNode) {
    document.body.classList.add('lx-pdp-luxe-clarity');

    let product = {};

    try {
        product = JSON.parse(productNode.textContent || '{}');
    } catch (error) {
        console.error('Không đọc được dữ liệu sản phẩm Luxe Clarity.', error);
    }

    const reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;
    const colors = Array.isArray(product.colors)
        ? product.colors
        : [];
    const studyNode = root.querySelector('[data-lxl-study-data]');
    let studies = [];

    try {
        studies = JSON.parse(studyNode?.textContent || '[]');
        studies = Array.isArray(studies) ? studies : [];
    } catch (error) {
        console.error('Không đọc được dữ liệu Product Study.', error);
        studies = [];
    }

    const normalize = (value) => String(value || '')
        .trim()
        .toLocaleLowerCase('vi');

    const activeColor = () => {
        const activeButton = root.querySelector(
            '[data-lxpdp-color].is-active'
        );
        const activeId = String(
            activeButton?.dataset.colorId || ''
        );
        const requested = new URL(window.location.href)
            .searchParams
            .get('color');

        return colors.find(
            (color) => String(color.id) === activeId
        ) || colors.find((color) => requested && [
            color.id,
            color.code,
            color.key,
        ].map(normalize).includes(normalize(requested)))
            || colors.find(
                (color) => String(color.id)
                    === String(product.default_color_id || '')
            )
            || colors.find(
                (color) => color.sellable
                    && Number(color.available || 0) > 0
            )
            || colors[0]
            || null;
    };

    const studyForColor = (color) => studies.find(
        (study) => String(study.color_id || '')
            === String(color?.id || '')
    ) || null;

    const makeStudyCard = (
        item,
        index,
        colorLabel
    ) => {
        const article = document.createElement('article');
        article.className = `lxl-study-card lxl-study-card--${(index % 4) + 1}`;
        article.dataset.lxlStudyItem = String(index);

        const figure = document.createElement('figure');
        figure.className = 'lxl-study-card__media';

        const image = document.createElement('img');
        image.src = String(
            item?.hero?.url
            || item?.hero?.thumb_url
            || ''
        );
        image.alt = [
            product.name || 'Sản phẩm',
            colorLabel || '',
            item?.angle_label || 'Góc nhìn sản phẩm',
        ].filter(Boolean).join(' — ');
        image.loading = index === 0 ? 'eager' : 'lazy';
        image.decoding = 'async';

        figure.appendChild(image);

        const copy = document.createElement('div');
        copy.className = 'lxl-study-card__copy';

        const title = document.createElement('h3');
        title.textContent = String(
            item?.angle_label || 'Góc nhìn sản phẩm'
        );

        copy.appendChild(title);

        const alternates = Array.isArray(item?.alternates)
            ? item.alternates.slice(0, 3)
            : [];

        if (alternates.length) {
            const list = document.createElement('div');
            list.className = 'lxl-study-card__alternates';
            list.setAttribute(
                'aria-label',
                'Ảnh bổ sung cùng góc'
            );

            alternates.forEach((alternate) => {
                const url = String(
                    alternate?.url
                    || alternate?.thumb_url
                    || ''
                );

                if (!url) {
                    return;
                }

                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                link.rel = 'noopener';
                link.setAttribute(
                    'aria-label',
                    `Mở ảnh bổ sung ${title.textContent}`
                );

                const thumb = document.createElement('img');
                thumb.src = String(
                    alternate?.thumb_url
                    || alternate?.url
                );
                thumb.alt = '';
                thumb.loading = 'lazy';
                thumb.decoding = 'async';

                link.appendChild(thumb);
                list.appendChild(link);
            });

            if (list.childElementCount) {
                copy.appendChild(list);
            }
        }

        article.append(figure, copy);

        return article;
    };

    const renderStudy = (color) => {
        const list = root.querySelector('[data-lxl-study-list]');
        const nav = root.querySelector('[data-lxl-study-nav]');
        const empty = root.querySelector('[data-lxl-study-empty]');
        const colorLabel = root.querySelector('[data-lxl-study-color]');

        if (!list || !empty) {
            return;
        }

        const study = studyForColor(color);
        const items = Array.isArray(study?.items)
            ? study.items
            : [];
        const label = String(
            study?.color_label
            || color?.label
            || 'Màu đang chọn'
        );

        if (colorLabel) {
            colorLabel.textContent = label;
        }

        list.replaceChildren();
        nav?.replaceChildren();

        if (!items.length) {
            list.hidden = true;
            if (nav) {
                nav.hidden = true;
            }
            empty.hidden = false;
            return;
        }

        const cards = document.createDocumentFragment();
        items.forEach((item, index) => {
            const card = makeStudyCard(item, index, label);
            cards.appendChild(card);
        });

        list.appendChild(cards);
        list.hidden = false;
        if (nav) {
            nav.hidden = true;
        }
        empty.hidden = true;
    };

    const bindInitialStudyNavigation = () => {
        root.querySelectorAll('[data-lxl-study-jump]').forEach(
            (button) => {
                button.addEventListener('click', () => {
                    const index = String(
                        button.dataset.lxlStudyJump || ''
                    );
                    const card = root.querySelector(
                        `[data-lxl-study-item="${index}"]`
                    );

                    card?.scrollIntoView({
                        behavior: reducedMotion ? 'auto' : 'smooth',
                        block: 'center',
                    });
                });
            }
        );
    };

    bindInitialStudyNavigation();

    root.querySelectorAll('[data-lxpdp-color]').forEach(
        (button) => {
            button.addEventListener('click', () => {
                const color = colors.find(
                    (item) => String(item.id)
                        === String(button.dataset.colorId)
                );

                if (color) {
                    window.requestAnimationFrame(
                        () => renderStudy(color)
                    );
                }
            });
        }
    );

    const quantityInput = root.querySelector(
        '[data-lxl-qty-input]'
    );
    const quantityField = root.querySelector(
        '[data-lxl-quantity-field]'
    );
    const minusButton = root.querySelector(
        '[data-lxl-qty-minus]'
    );
    const plusButton = root.querySelector(
        '[data-lxl-qty-plus]'
    );

    const quantity = () => Math.max(
        1,
        Math.min(
            9,
            Number.parseInt(quantityInput?.value || '1', 10)
            || 1
        )
    );

    const syncQuantity = (nextValue = quantity()) => {
        const value = Math.max(
            1,
            Math.min(9, Number(nextValue) || 1)
        );

        if (quantityInput) {
            quantityInput.value = String(value);
        }

        if (quantityField) {
            quantityField.value = String(value);
        }
    };

    minusButton?.addEventListener(
        'click',
        () => syncQuantity(quantity() - 1)
    );
    plusButton?.addEventListener(
        'click',
        () => syncQuantity(quantity() + 1)
    );
    quantityInput?.addEventListener(
        'input',
        () => syncQuantity()
    );
    quantityInput?.addEventListener(
        'change',
        () => syncQuantity()
    );

    syncQuantity();
    renderStudy(activeColor());

    root.dispatchEvent(new CustomEvent(
        'linxen:pdp:luxe-clarity-ready',
        {
            bubbles: true,
            detail: {
                variant: 'luxe_clarity_v1',
                product_id: product.id || null,
            },
        }
    ));
}
