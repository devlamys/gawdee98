(() => {
    'use strict';
    document.querySelectorAll('[data-collection-rows]').forEach(container => {
        const group = container.dataset.collectionRows;
        const renumber = () => {
            [...container.children].filter(row => !row.hasAttribute('data-collection-template')).forEach((row, index) => {
                row.querySelectorAll('[name]').forEach(input => {
                    if (input.dataset.collectionUpload) input.name = group + '_' + index + '_' + input.dataset.collectionUpload;
                    else input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
                });
            });
        };
        document.querySelector('[data-collection-add="' + group + '"]')?.addEventListener('click', () => {
            const template = container.querySelector('[data-collection-template]');
            if (!template || container.children.length > 30) return;
            const row = template.cloneNode(true);
            row.removeAttribute('data-collection-template');
            row.hidden = false;
            row.disabled = false;
            container.insertBefore(row, template);
            renumber();
            row.querySelector('input,select')?.focus();
        });
        container.addEventListener('click', event => {
            const row = event.target.closest('fieldset');
            if (!row) return;
            if (event.target.closest('[data-collection-up]') && row.previousElementSibling) row.previousElementSibling.before(row);
            if (event.target.closest('[data-collection-down]') && row.nextElementSibling && !row.nextElementSibling.hasAttribute('data-collection-template')) row.nextElementSibling.after(row);
            renumber();
        });
        container.closest('form')?.addEventListener('submit', renumber);
    });
})();
