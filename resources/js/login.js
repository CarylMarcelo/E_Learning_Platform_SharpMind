const toggle = document.querySelector('[data-toggle-password]');
toggle?.addEventListener('click', () => {
    const password = document.getElementById('password');
    const isVisible = password.type === 'password';
    password.type = isVisible ? 'text' : 'password';
    toggle.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
    toggle.setAttribute('aria-pressed', String(isVisible));
});
