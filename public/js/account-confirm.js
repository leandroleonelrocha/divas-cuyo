(() => {
    let active = false;
    window.accountConfirm = ({ title, message, confirmLabel = 'Confirmar', danger = false }) => {
        if (active) return Promise.resolve(false);
        active = true;
        return new Promise(resolve => {
            const previousFocus = document.activeElement;
            const dialog = document.createElement('dialog');
            dialog.className = 'account-confirm';
            dialog.setAttribute('aria-labelledby', 'account-confirm-title');
            dialog.setAttribute('aria-describedby', 'account-confirm-message');
            dialog.innerHTML = '<div class="account-confirm-icon" aria-hidden="true"></div><h2 id="account-confirm-title"></h2><p id="account-confirm-message"></p><div class="account-confirm-actions"><button type="button" data-cancel>Cancelar</button><button type="button" data-confirm></button></div>';
            dialog.querySelector('.account-confirm-icon').textContent = danger ? '!' : '✓';
            dialog.querySelector('h2').textContent = title;
            dialog.querySelector('p').textContent = message;
            dialog.querySelector('[data-confirm]').textContent = confirmLabel;
            let accepted = false;
            dialog.querySelector('[data-confirm]').addEventListener('click', () => {
                accepted = true;
                dialog.close();
            });
            dialog.querySelector('[data-cancel]').addEventListener('click', () => dialog.close());
            dialog.addEventListener('click', event => {
                const bounds = dialog.getBoundingClientRect();
                if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
            });
            dialog.addEventListener('close', () => {
                dialog.remove();
                active = false;
                if (previousFocus?.isConnected) previousFocus.focus();
                resolve(accepted);
            }, { once: true });
            document.body.append(dialog);
            dialog.showModal();
            dialog.querySelector('[data-cancel]').focus();
        });
    };
})();
