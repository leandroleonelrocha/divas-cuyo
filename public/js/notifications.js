(() => {
    if (window.__divasNotify) return;
    window.__divasNotify = true;

    const TOAST_CONTAINER_ID = 'notify-toasts';

    const container = () => {
        let element = document.getElementById(TOAST_CONTAINER_ID);
        if (!element) {
            element = document.createElement('div');
            element.id = TOAST_CONTAINER_ID;
            document.body.appendChild(element);
        }
        return element;
    };

    const dismiss = (node) => {
        if (!node || node.dataset.notifyLeaving === '1') return;
        node.dataset.notifyLeaving = '1';
        node.classList.add('is-leaving');
        window.setTimeout(() => node.remove(), 180);
    };

    const hasDuplicateToast = (node) => {
        const key = node.dataset.notifyKey || '';
        return [...container().querySelectorAll('[data-notify]')].some(
            (other) => other !== node && other.dataset.notifyKey === key && other.dataset.notifyLeaving !== '1',
        );
    };

    const scheduleDismiss = (node) => {
        const base = Number.parseInt(node.dataset.notifyTimeout || '6000', 10) || 6000;
        let timer = window.setTimeout(() => dismiss(node), base);

        const restart = (delay) => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => dismiss(node), delay);
        };

        node.addEventListener('mouseenter', () => window.clearTimeout(timer));
        node.addEventListener('mouseleave', () => restart(2500));
        node.addEventListener('focusin', () => window.clearTimeout(timer));
        node.addEventListener('focusout', () => restart(2500));
    };

    const promoteToast = (node) => {
        if (node.dataset.notifyReady === '1') return;
        node.dataset.notifyReady = '1';

        if (hasDuplicateToast(node)) {
            node.remove();
            return;
        }

        container().appendChild(node);
        scheduleDismiss(node);
    };

    const initToasts = () => {
        document
            .querySelectorAll('[data-notify][data-notify-variant="toast"]')
            .forEach(promoteToast);
    };

    document.addEventListener('click', (event) => {
        const close = event.target.closest('[data-notify-close]');
        if (!close) return;
        event.preventDefault();
        dismiss(close.closest('[data-notify]'));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const openToast = document.querySelector('#notify-toasts [data-notify]');
        if (openToast) dismiss(openToast);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToasts, { once: true });
    } else {
        initToasts();
    }

    if (typeof MutationObserver === 'function') {
        new MutationObserver(initToasts).observe(document.body, { childList: true, subtree: true });
    }

    window.DivasNotify = { dismiss };
})();
