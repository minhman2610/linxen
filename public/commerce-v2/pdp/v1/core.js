import '/commerce-v2/pdp-sales-experience.js?v=4';

const root = document.querySelector('[data-pdp-engine]');

if (root) {
    root.dispatchEvent(new CustomEvent('linxen:pdp:engine-ready', {
        bubbles: true,
        detail: {
            engine: root.dataset.pdpEngine,
            variant: root.dataset.pdpVariant,
            version: root.dataset.pdpVariantVersion,
        },
    }));
}
