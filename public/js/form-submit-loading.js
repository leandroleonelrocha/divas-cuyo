(() => {
    const states = [];

    document.querySelectorAll('form[data-submit-loading]').forEach((form) => {
        const button = form.querySelector('button[type="submit"]');
        if (!button) return;

        const status = document.createElement('span');
        status.className = 'submit-loading-status';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');
        form.append(status);

        const state = { button, status, content: button.innerHTML, disabled: button.disabled, busy: false };
        states.push(state);

        // Native validation runs before submit, so invalid forms keep their normal button.
        form.addEventListener('submit', (event) => {
            if (state.busy) {
                event.preventDefault();
                return;
            }
            if (event.defaultPrevented || button.disabled) return;

            state.busy = true;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.classList.add('is-submitting');

            const spinner = document.createElement('span');
            spinner.className = 'submit-loading-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            const label = document.createElement('span');
            label.textContent = form.dataset.submitLoading;
            button.replaceChildren(spinner, label);
            status.textContent = label.textContent;
        });
    });

    // Browsers may restore the disabled button when navigating back from another page.
    window.addEventListener('pageshow', () => {
        states.forEach((state) => {
            if (!state.busy) return;
            state.button.innerHTML = state.content;
            state.button.disabled = state.disabled;
            state.button.removeAttribute('aria-busy');
            state.button.classList.remove('is-submitting');
            state.status.textContent = '';
            state.busy = false;
        });
    });
})();
