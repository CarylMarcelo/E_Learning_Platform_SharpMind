let previousDialogFocus = null;

export function openDialog(dialog) {
    if (!dialog || dialog.open) {
        return;
    }

    previousDialogFocus = document.activeElement;
    dialog.showModal();
    document.body.classList.add('dialog-open');
}

export function initializeSharedInterface() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const sidebarTriggers = document.querySelectorAll('[data-toggle-sidebar]');
    const mobileTrigger = document.querySelector('.mobile-menu');

    function setSidebarOpen(isOpen) {
        sidebar?.classList.toggle('is-open', isOpen);
        overlay?.classList.toggle('is-open', isOpen);
        sidebar?.toggleAttribute('inert', !isOpen && window.innerWidth <= 800);
        mobileTrigger?.setAttribute('aria-expanded', String(isOpen));
        if (window.innerWidth <= 800) {
            document.body.classList.toggle('dialog-open', isOpen);
        }
        if (isOpen) {
            sidebar?.querySelector('.sidebar-close')?.focus();
        }
    }

    sidebarTriggers.forEach(button => button.addEventListener('click', () => {
        const isOpen = !sidebar?.classList.contains('is-open');
        setSidebarOpen(isOpen);
        if (!isOpen) {
            mobileTrigger?.focus();
        }
    }));
    overlay?.addEventListener('click', () => setSidebarOpen(false));
    window.addEventListener('resize', () => {
        if (window.innerWidth > 800) {
            setSidebarOpen(false);
            sidebar?.removeAttribute('inert');
            document.body.classList.remove('dialog-open');
        } else if (!sidebar?.classList.contains('is-open')) {
            sidebar?.setAttribute('inert', '');
        }
    });
    if (window.innerWidth <= 800) {
        sidebar?.setAttribute('inert', '');
    }

    document.addEventListener('click', event => {
        const dialogTrigger = event.target.closest('[data-open-dialog]');
        if (dialogTrigger) {
            openDialog(document.getElementById(dialogTrigger.dataset.openDialog));
        }
        event.target.closest('[data-close-dialog]')?.closest('dialog')?.close();
    });

    document.querySelectorAll('dialog').forEach(dialog => {
        dialog.addEventListener('close', () => {
            document.body.classList.remove('dialog-open');
            previousDialogFocus?.focus();
        });
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) {
                return;
            }
            const bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
                dialog.close();
            }
        });
    });

    let pendingForm = null;
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (form.dataset.confirmed === 'true') {
                return;
            }
            event.preventDefault();
            pendingForm = form;
            const dialog = document.getElementById('confirm-dialog');
            dialog.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
            const submit = dialog.querySelector('[data-confirm-submit]');
            submit.textContent = form.dataset.confirmLabel || 'Delete record';
            openDialog(dialog);
        });
    });
    document.querySelector('[data-confirm-submit]')?.addEventListener('click', () => {
        if (pendingForm) {
            pendingForm.dataset.confirmed = 'true';
            pendingForm.requestSubmit();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            if (sidebar?.classList.contains('is-open')) {
                setSidebarOpen(false);
                mobileTrigger?.focus();
            }
        }
        if (event.key === 'Tab' && sidebar?.classList.contains('is-open') && window.innerWidth <= 800) {
            const focusable = [...sidebar.querySelectorAll('a, button')];
            const first = focusable[0];
            const last = focusable.at(-1);
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    document.querySelectorAll('[data-auto-submit]').forEach(select => {
        select.addEventListener('change', () => select.form?.requestSubmit());
    });
    document.querySelectorAll('[data-print]').forEach(button => {
        button.addEventListener('click', () => window.print());
    });
}
