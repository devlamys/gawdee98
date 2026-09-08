(() => {
    'use strict';

    const app = document.querySelector('[data-admin-app]');
    const menuToggle = document.querySelector('[data-admin-menu]');
    const setMenuOpen = open => {
        app?.classList.toggle('is-menu-open', open);
        menuToggle?.setAttribute('aria-expanded', String(open));
    };
    menuToggle?.addEventListener('click', () => {
        const open = !app?.classList.contains('is-menu-open');
        setMenuOpen(open);
    });
    document.querySelectorAll('.admin-nav a').forEach(link => link.addEventListener('click', () => setMenuOpen(false)));
    document.addEventListener('click', event => {
        if (window.innerWidth > 800 || !app?.classList.contains('is-menu-open')) return;
        if (event.target.closest('.admin-sidebar') || event.target.closest('[data-admin-menu]')) return;
        setMenuOpen(false);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            setMenuOpen(false);
        }
    });

    const commandForm = document.querySelector('[data-admin-command-search]');
    const commandInput = document.querySelector('[data-admin-command-input]');
    const commandRoutes = {
        order: 'orders', orders: 'orders', fulfilment: 'orders', shipment: 'orders',
        product: 'products', products: 'products', catalogue: 'products', catalog: 'products',
        stock: 'inventory', inventory: 'inventory', customer: 'customers', customers: 'customers',
        banner: 'banners', banners: 'banners', homepage: 'cms', content: 'cms', cms: 'cms',
        testimonial: 'testimonials', testimonials: 'testimonials', video: 'video-testimonials',
        review: 'reviews', reviews: 'reviews', media: 'media', blog: 'blog', article: 'blog',
        ai: 'ai', integration: 'integrations', integrations: 'integrations', payment: 'integrations',
        razorpay: 'integrations', delhivery: 'integrations', whatsapp: 'integrations', settings: 'settings'
    };
    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            commandInput?.focus();
        }
    });
    commandForm?.addEventListener('submit', event => {
        event.preventDefault();
        const query = String(commandInput?.value || '').trim().toLowerCase();
        if (!query) return;
        const keyword = Object.keys(commandRoutes).find(key => query.includes(key));
        if (keyword) window.location.href = `?view=${encodeURIComponent(commandRoutes[keyword])}`;
    });

    const builder = document.querySelector('[data-order-builder]');
    if (!builder) return;

    const lines = builder.querySelector('[data-order-lines]');
    const firstLine = lines?.querySelector('.order-builder-line');
    const formatMoney = value => new Intl.NumberFormat('en-IN', {
        style: 'currency', currency: 'INR', maximumFractionDigits: 0
    }).format(Number(value || 0));

    const calculate = () => {
        let subtotal = 0;
        lines?.querySelectorAll('.order-builder-line').forEach(line => {
            const select = line.querySelector('[data-order-product]');
            const quantityInput = line.querySelector('[data-order-quantity]');
            const option = select?.selectedOptions?.[0];
            const price = Number(option?.dataset.price || 0);
            const stock = Number(option?.dataset.stock || 10);
            if (quantityInput) {
                quantityInput.max = String(Math.min(10, Math.max(1, stock)));
                if (Number(quantityInput.value) > Number(quantityInput.max)) quantityInput.value = quantityInput.max;
            }
            const quantity = Math.max(1, Number(quantityInput?.value || 1));
            const lineTotal = price * quantity;
            subtotal += lineTotal;
            const totalNode = line.querySelector('[data-line-total]');
            if (totalNode) totalNode.textContent = formatMoney(lineTotal);
        });

        const coupon = String(builder.querySelector('[data-order-coupon]')?.value || '').trim().toUpperCase();
        const offerCode = String(builder.dataset.offerCode || '').toUpperCase();
        const discount = coupon !== '' && coupon === offerCode
            ? Math.floor(subtotal * Number(builder.dataset.offerPercent || 0) / 100)
            : 0;
        const shipping = subtotal > 0 && subtotal < Number(builder.dataset.freeShipping || 0)
            ? Number(builder.dataset.shipping || 0)
            : 0;
        const total = Math.max(0, subtotal - discount + shipping);
        const values = {
            '[data-builder-subtotal]': subtotal,
            '[data-builder-discount]': discount ? -discount : 0,
            '[data-builder-shipping]': shipping,
            '[data-builder-total]': total
        };
        Object.entries(values).forEach(([selector, value]) => {
            const node = builder.querySelector(selector);
            if (node) node.textContent = formatMoney(value);
        });
    };

    builder.querySelector('[data-add-line]')?.addEventListener('click', () => {
        if (!firstLine || !lines || lines.children.length >= 20) return;
        const clone = firstLine.cloneNode(true);
        const select = clone.querySelector('select');
        const quantity = clone.querySelector('input');
        if (select) select.value = '';
        if (quantity) quantity.value = '1';
        const lineTotal = clone.querySelector('[data-line-total]');
        if (lineTotal) lineTotal.textContent = formatMoney(0);
        lines.appendChild(clone);
        select?.focus();
        calculate();
    });

    lines?.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-line]');
        if (!button) return;
        const allLines = lines.querySelectorAll('.order-builder-line');
        if (allLines.length === 1) {
            const line = allLines[0];
            line.querySelector('select').value = '';
            line.querySelector('input').value = '1';
        } else {
            button.closest('.order-builder-line')?.remove();
        }
        calculate();
    });

    builder.addEventListener('input', calculate);
    builder.addEventListener('change', calculate);
    calculate();
})();
