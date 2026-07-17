import '../core.js';

const root = document.querySelector('[data-pdp-variant="atelier_editorial_v1"]');
const productNode = document.getElementById('lxv2ProductData');

if (root && productNode) {
    let product = {};

    try {
        product = JSON.parse(productNode.textContent || '{}');
    } catch (error) {
        console.error('Không đọc được PDP payload cho Atelier Editorial.', error);
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

    const safeHex = (value) => /^#[0-9a-f]{3,8}$/i.test(String(value || ''))
        ? String(value)
        : '#6e2430';

    const activeColor = () => {
        const button = root.querySelector('[data-lxpdp-color].is-active');
        const id = String(button?.dataset.colorId || '');

        return colors.find((color) => String(color.id) === id)
            || colors.find((color) => String(color.id) === String(product.default_color_id || ''))
            || colors.find((color) => color.sellable)
            || colors[0]
            || null;
    };

    const imageUrl = (media) => String(media?.url || media?.thumb_url || '');

    const createFilmItem = (media, index) => {
        const figure = document.createElement('figure');
        figure.className = `lxa-film__item${index === 0 ? ' is-lead' : ''}`;
        figure.dataset.lxaFilmItem = '';

        const image = document.createElement('img');
        image.src = imageUrl(media);
        image.alt = `${product.name || 'Sản phẩm'} — ${roleLabels[media?.role] || 'Hình ảnh sản phẩm'}`;
        image.loading = index < 2 ? 'eager' : 'lazy';
        image.decoding = 'async';

        const caption = document.createElement('figcaption');
        const count = document.createElement('span');
        const label = document.createElement('strong');
        count.textContent = String(index + 1).padStart(2, '0');
        label.textContent = roleLabels[media?.role] || 'Chi tiết sản phẩm';
        caption.append(count, label);
        figure.append(image, caption);

        return figure;
    };

    const renderFilm = (color) => {
        const film = root.querySelector('[data-lxa-film]');
        if (!film) return;

        const media = Array.isArray(color?.media)
            ? color.media.slice(0, 5).filter((item) => imageUrl(item))
            : [];

        if (!media.length) {
            film.hidden = true;
            return;
        }

        const fragment = document.createDocumentFragment();
        media.forEach((item, index) => fragment.append(createFilmItem(item, index)));
        film.replaceChildren(fragment);
        film.hidden = false;
    };

    const updateFinale = (color) => {
        const finale = root.querySelector('[data-lxa-finale-image]');
        if (!finale) return;

        const media = Array.isArray(color?.media) ? color.media.filter((item) => imageUrl(item)) : [];
        const preferred = media.find((item) => item.role === 'lifestyle')
            || media.find((item) => item.role === 'back')
            || media.at(-1)
            || media[0];

        if (preferred) {
            finale.src = imageUrl(preferred);
            finale.alt = `${product.name || 'Sản phẩm'} — ${color?.label || ''}`.trim();
        }
    };

    const applyColorAtmosphere = (color) => {
        root.style.setProperty('--lxa-current-color', safeHex(color?.hex));
        renderFilm(color);
        updateFinale(color);
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

    root.querySelectorAll('[data-pdp-scroll-to-purchase]').forEach((button) => {
        button.addEventListener('click', () => {
            root.querySelector('#lxaPurchasePanel')?.scrollIntoView({
                behavior: reducedMotion ? 'auto' : 'smooth',
                block: 'center',
            });
        });
    });

    const revealItems = Array.from(root.querySelectorAll('[data-lxa-reveal]'));

    if (!('IntersectionObserver' in window) || reducedMotion) {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    } else {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -7% 0px',
        });

        revealItems.forEach((item) => revealObserver.observe(item));
    }

    const progress = document.createElement('div');
    const progressBar = document.createElement('i');
    progress.className = 'lxa-scroll-progress';
    progress.setAttribute('aria-hidden', 'true');
    progress.append(progressBar);
    root.prepend(progress);

    let scrollQueued = false;
    const updateProgress = () => {
        scrollQueued = false;
        const rect = root.getBoundingClientRect();
        const total = Math.max(1, root.scrollHeight - window.innerHeight);
        const passed = Math.min(total, Math.max(0, -rect.top));
        progressBar.style.width = `${(passed / total) * 100}%`;
    };

    window.addEventListener('scroll', () => {
        if (scrollQueued) return;
        scrollQueued = true;
        window.requestAnimationFrame(updateProgress);
    }, { passive: true });
    updateProgress();

    if (!reducedMotion && window.matchMedia('(pointer: fine)').matches) {
        const hero = root.querySelector('.lxa-hero__media');
        const heroImage = root.querySelector('[data-lxpdp-main-image]');

        hero?.addEventListener('pointermove', (event) => {
            if (!heroImage) return;
            const rect = hero.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width - .5) * 12;
            const y = ((event.clientY - rect.top) / rect.height - .5) * 8;
            heroImage.style.setProperty('--lxa-shift-x', `${x}px`);
            heroImage.style.setProperty('--lxa-shift-y', `${y}px`);
        });

        hero?.addEventListener('pointerleave', () => {
            heroImage?.style.setProperty('--lxa-shift-x', '0px');
            heroImage?.style.setProperty('--lxa-shift-y', '0px');
        });
    }

    if ('IntersectionObserver' in window) {
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting || entry.target.dataset.lxaSeen === '1') return;
                entry.target.dataset.lxaSeen = '1';
                root.dispatchEvent(new CustomEvent('linxen:pdp:section-viewed', {
                    bubbles: true,
                    detail: {
                        variant: 'atelier_editorial_v1',
                        section: entry.target.dataset.pdpSection || null,
                        product_id: product.id || null,
                    },
                }));
            });
        }, { threshold: .32 });

        root.querySelectorAll('[data-pdp-section]').forEach((section) => {
            sectionObserver.observe(section);
        });
    }

    root.dispatchEvent(new CustomEvent('linxen:pdp:atelier-ready', {
        bubbles: true,
        detail: {
            variant: 'atelier_editorial_v1',
            product_id: product.id || null,
        },
    }));
}
