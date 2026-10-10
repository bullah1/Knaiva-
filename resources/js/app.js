import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Checkout: live delivery/total preview. The server always recalculates.
Alpine.data('checkout', (cfg) => ({
    zone: cfg.zone,
    payment: cfg.payment,
    get deliveryCharge() {
        if (cfg.threshold > 0 && cfg.subtotal - cfg.discount >= cfg.threshold) return 0;
        return this.zone === 'inside_dhaka' ? cfg.inside : cfg.outside;
    },
    get total() {
        return cfg.subtotal - cfg.discount + this.deliveryCharge;
    },
    bdt(n) {
        return '৳' + Math.round(n).toLocaleString('en-US');
    },
}));

// Product page: variant, quantity and gallery.
Alpine.data('productPage', (variants) => ({
    variants,
    variantId: variants.length ? variants[0].id : null,
    qty: 1,
    image: 0,
    get variant() {
        return this.variants.find((v) => v.id === this.variantId) || this.variants[0] || null;
    },
    get available() {
        return this.variant && this.variant.stock > 0;
    },
    get savePercent() {
        const v = this.variant;
        return v && v.mrp && v.mrp > v.price ? Math.round(((v.mrp - v.price) / v.mrp) * 100) : 0;
    },
    bdt(n) {
        return '৳' + Math.round(n).toLocaleString('en-US');
    },
}));

Alpine.start();

// Auto-hide flash toasts.
document.querySelectorAll('[data-toast]').forEach((el) => {
    setTimeout(() => el.remove(), 4500);
});
