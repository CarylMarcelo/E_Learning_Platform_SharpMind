import { openDialog } from './interface';

export function initializeCatalog(label) {
    const grid = document.querySelector('[data-catalog-grid]');
    const search = document.querySelector('[data-catalog-search]');
    const sort = document.querySelector('[data-catalog-sort]');
    const cards = [...document.querySelectorAll('[data-catalog-item]')];
    const count = document.querySelector('[data-result-count]');
    const empty = document.querySelector('[data-catalog-empty]');

    function updateCatalog() {
        const term = search?.value.trim().toLocaleLowerCase() ?? '';
        const ordered = [...cards].sort((first, second) => {
            if (sort.value === 'name') {
                return first.dataset.name.localeCompare(second.dataset.name);
            }
            if (sort.value === 'fee') {
                return Number(first.dataset.fee) - Number(second.dataset.fee);
            }
            const key = sort.value === 'members' ? 'members' : 'created';
            return Number(second.dataset[key]) - Number(first.dataset[key]);
        });
        let visible = 0;
        ordered.forEach(card => {
            card.hidden = !card.dataset.search.toLocaleLowerCase().includes(term);
            if (!card.hidden) {
                visible += 1;
            }
            grid.append(card);
        });
        if (count) {
            count.textContent = `${visible} ${label}`;
        }
        if (empty) {
            empty.hidden = visible > 0 || cards.length === 0;
        }
    }

    search?.addEventListener('input', updateCatalog);
    sort?.addEventListener('change', updateCatalog);
    updateCatalog();
}

export function initializeEditor({ dialogId, formId, createTitle, editTitle, fields }) {
    const dialog = document.getElementById(dialogId);
    const form = document.getElementById(formId);
    if (!dialog || !form) {
        return;
    }

    function prepareEditor(button = null) {
        const isEditing = Boolean(button);
        form.querySelectorAll('.alert').forEach(alert => alert.hidden = true);
        form.action = isEditing ? button.dataset.action : form.dataset.createAction;
        form.elements._method.disabled = !isEditing;
        form.elements._record.value = isEditing ? button.dataset.record : '';
        dialog.querySelector('[data-dialog-title]').textContent = isEditing ? editTitle : createTitle;
        Object.entries(fields).forEach(([field, defaultValue]) => {
            form.elements.namedItem(field).value = isEditing ? button.dataset[field] ?? '' : defaultValue;
        });
        openDialog(dialog);
    }

    document.querySelectorAll('[data-new-record]').forEach(button => {
        button.addEventListener('click', () => prepareEditor());
    });
    document.querySelectorAll('[data-edit-record]').forEach(button => {
        button.addEventListener('click', () => prepareEditor(button));
    });
    if (dialog.dataset.reopen === 'true') {
        openDialog(dialog);
    }
}
