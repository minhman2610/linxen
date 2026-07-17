import '../core.js';

const root = document.querySelector('[data-pdp-variant="editorial_guided_v1"]');
const productNode = document.getElementById('lxv2ProductData');

if (root && productNode) {
    let product = {};

    try {
        product = JSON.parse(productNode.textContent || '{}');
    } catch (error) {
        console.error('Không đọc được PDP payload cho recently viewed.', error);
    }

    const historyKey = 'linxen_pdp_recently_viewed_v1';
    const current = {
        id: String(product.id || ''),
        name: String(product.name || ''),
        url: window.location.pathname,
        cover_url: String(product.cover_url || product.colors?.[0]?.media?.[0]?.url || ''),
        price_min: Number(product.price_min || 0),
        viewed_at: new Date().toISOString(),
    };

    const escapeHtml = (value) => String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    let history = [];

    try {
        history = JSON.parse(localStorage.getItem(historyKey) || '[]');
        history = Array.isArray(history) ? history : [];
    } catch {
        history = [];
    }

    const previous = history
        .filter((item) => String(item.id || '') !== current.id)
        .slice(0, 7);

    if (current.id && current.name) {
        try {
            localStorage.setItem(historyKey, JSON.stringify([
                current,
                ...previous,
            ].slice(0, 8)));
        } catch {
            // Browser privacy mode may block storage; PDP remains functional.
        }
    }

    const list = root.querySelector('[data-pdp-recently-viewed-list]');
    const empty = root.querySelector('[data-pdp-recently-viewed-empty]');

    if (list && previous.length) {
        empty?.remove();
        list.innerHTML = previous.slice(0, 4).map((item) => {
            const price = Number(item.price_min || 0).toLocaleString('vi-VN');
            const image = item.cover_url
                ? `<img src="${escapeHtml(item.cover_url)}" alt="${escapeHtml(item.name)}" loading="lazy">`
                : '';

            return `<a href="${escapeHtml(item.url)}">${image}<strong>${escapeHtml(item.name)}</strong><span>${price ? `${price}₫` : ''}</span></a>`;
        }).join('');
    }

    root.querySelectorAll('[data-pdp-scroll-to-purchase]').forEach((button) => {
        button.addEventListener('click', () => {
            root.querySelector('[data-pdp-section="editorial_hero_purchase"]')
                ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    const observer = 'IntersectionObserver' in window
        ? new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting || entry.target.dataset.pdpSeen === '1') {
                    return;
                }

                entry.target.dataset.pdpSeen = '1';
                root.dispatchEvent(new CustomEvent('linxen:pdp:section-viewed', {
                    bubbles: true,
                    detail: {
                        variant: 'editorial_guided_v1',
                        section: entry.target.dataset.pdpSection,
                        product_id: product.id || null,
                    },
                }));
            });
        }, { threshold: 0.3 })
        : null;

    observer && root.querySelectorAll('[data-pdp-section]').forEach((section) => {
        observer.observe(section);
    });
}
