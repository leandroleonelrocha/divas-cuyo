const initializeAccountMobileMenu = () => {
    const dialog = document.getElementById('dashboard-mobile-menu');
    const toggle = document.querySelector('.dashboard-mobile-toggle');
    const sidebar = document.querySelector('.account-app-shell > .account-sidebar');
    if (!dialog || !toggle || !sidebar || toggle.dataset.menuInitialized) return;
    toggle.dataset.menuInitialized = 'true';
    document.body.append(dialog);
    const marker = document.createComment('sidebar location');
    sidebar.before(marker);
    const mobile = matchMedia('(max-width: 767px)');
    const close = () => dialog.close();
    toggle.addEventListener('click', () => {
        if (!mobile.matches || dialog.open) return;
        dialog.querySelector('[data-mobile-menu-content]').append(sidebar);
        dialog.showModal();
        toggle.setAttribute('aria-expanded', 'true');
        dialog.querySelector('.mobile-menu-close').focus();
    });
    dialog.querySelector('.mobile-menu-close').addEventListener('click', close);
    dialog.addEventListener('click', event => {
        if (event.target === dialog) {
            const bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) close();
        }
        if (event.target.closest('a')) close();
    });
    dialog.addEventListener('close', () => {
        marker.after(sidebar);
        toggle.setAttribute('aria-expanded', 'false');
        if (mobile.matches) toggle.focus();
    });
    mobile.addEventListener('change', () => { if (!mobile.matches && dialog.open) close(); });
};
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAccountMobileMenu, { once: true });
} else {
    initializeAccountMobileMenu();
}
window.addEventListener('pageshow', initializeAccountMobileMenu);
