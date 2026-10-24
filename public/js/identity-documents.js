(() => {
    const cards = Array.from(document.querySelectorAll('[data-identity-document]'));
    if (!cards.length) return;

    const counter = document.querySelector('[data-doc-counter]');
    const progressBar = document.querySelector('[data-doc-progress-bar]');
    const submitForm = document.querySelector('[data-identity-submit]');
    const submitButton = submitForm ? submitForm.querySelector('[data-submit-button]') : null;
    const submitHint = submitForm ? submitForm.querySelector('[data-submit-hint]') : null;
    const submitError = submitForm ? submitForm.querySelector('[data-submit-error]') : null;

    const allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    const allowedExtensions = /\.(jpe?g|png|webp|pdf)$/i;
    const maxBytes = 5120 * 1024;
    let submitting = false;

    const missingLabels = () => cards
        .filter((card) => card.dataset.loaded !== 'true')
        .map((card) => card.dataset.label);

    const setStatus = (card, state, text) => {
        const badge = card.querySelector('[data-doc-badge]');
        if (!badge) return;
        badge.textContent = text;
        badge.className = `identity-doc-badge identity-doc-badge-${state}`;
    };

    const showError = (card, message) => {
        const error = card.querySelector('[data-doc-error]');
        if (error) {
            error.textContent = message;
            error.hidden = !message;
        }
        if (message) setStatus(card, 'error', 'Error');
    };

    const clearError = (card) => {
        const error = card.querySelector('[data-doc-error]');
        if (error) {
            error.textContent = '';
            error.hidden = true;
        }
    };

    const refreshProgress = () => {
        const total = cards.length;
        const loaded = total - missingLabels().length;
        const percent = Math.round((loaded / total) * 100);

        if (counter) counter.textContent = `${loaded} de ${total} documentos cargados`;
        if (progressBar) progressBar.style.width = `${percent}%`;
        if (!submitButton) return;

        const missing = missingLabels();
        const complete = missing.length === 0;

        submitButton.disabled = !complete || submitting;
        if (submitHint) {
            submitHint.textContent = complete
                ? `Los ${total} documentos están cargados. Podés enviarlos a revisión.`
                : `Faltan ${missing.join(', ')} para poder enviar.`;
        }
    };

    const validate = (file) => {
        if (!file) return 'No se pudo leer el archivo seleccionado.';
        const typeOk = allowedMimes.includes(file.type) || allowedExtensions.test(file.name);
        if (!typeOk) return 'El formato no está permitido. Usá JPG, PNG, WEBP o PDF.';
        if (file.size > maxBytes) return 'El archivo supera los 5 MB. Elegí uno más liviano.';
        return null;
    };

    const finishUpload = (card, { loaded, message, url }) => {
        const input = card.querySelector('[data-doc-input]');
        const pick = card.querySelector('[data-doc-pick]');
        const view = card.querySelector('[data-doc-view]');
        const hint = card.querySelector('[data-doc-hint]');
        const progress = card.querySelector('[data-doc-progress]');

        if (progress) progress.hidden = true;
        if (input) input.disabled = false;
        card.dataset.uploading = 'false';

        if (loaded) {
            card.dataset.loaded = 'true';
            setStatus(card, 'loaded', 'Cargado');
            if (hint) hint.textContent = 'Tu documento está cargado y protegido.';
            if (pick) pick.textContent = 'Cambiar imagen';
            if (view && url) {
                view.href = url;
                view.hidden = false;
            }
        } else {
            card.dataset.loaded = 'false';
            if (hint) hint.textContent = 'La carga fallió. Reintentá eligiendo la imagen de nuevo.';
            if (pick) pick.textContent = 'Reintentar';
        }

        if (message) showError(card, message);
        else clearError(card);

        refreshProgress();
    };

    const upload = (card, file) => {
        const input = card.querySelector('[data-doc-input]');
        if (!input) return;

        const invalid = validate(file);
        if (invalid) {
            const pick = card.querySelector('[data-doc-pick]');
            if (pick) pick.textContent = card.dataset.loaded === 'true' ? 'Cambiar imagen' : 'Elegir imagen';
            showError(card, invalid);
            return;
        }

        const progress = card.querySelector('[data-doc-progress]');
        const bar = card.querySelector('[data-doc-bar]');
        const percent = card.querySelector('[data-doc-percent]');
        const pick = card.querySelector('[data-doc-pick]');

        card.dataset.uploading = 'true';
        if (progress) progress.hidden = false;
        if (bar) bar.style.width = '0%';
        if (percent) percent.textContent = '0%';
        if (pick) pick.textContent = 'Subiendo…';
        input.disabled = true;
        setStatus(card, 'loading', 'Subiendo');

        const data = new FormData();
        data.append('type', input.dataset.type);
        data.append('document', file);
        data.append('_token', document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value
            || '');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', input.dataset.uploadUrl);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.responseType = 'text';

        xhr.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) return;
            const value = Math.round((event.loaded / event.total) * 100);
            if (bar) bar.style.width = `${value}%`;
            if (percent) percent.textContent = `${value}%`;
        });

        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300 && !xhr.responseURL.includes('/login')) {
                try {
                    const payload = JSON.parse(xhr.responseText);
                    finishUpload(card, { loaded: true, message: '', url: payload.download_url });
                } catch (_) {
                    finishUpload(card, { loaded: false, message: 'No se pudo guardar el documento. Reintentá.' });
                }
                return;
            }

            let message = 'No se pudo subir el documento. Revisá el formato y el tamaño del archivo.';
            if (xhr.status === 419) message = 'Tu sesión expiró. Volvé a ingresar para continuar.';
            else if (xhr.responseURL.includes('/login')) message = 'Tu sesión expiró. Volvé a ingresar para continuar.';
            else {
                try {
                    const payload = JSON.parse(xhr.responseText);
                    message = Object.values(payload.errors || {}).flat().join(' ') || payload.message || message;
                } catch (_) {}
            }
            finishUpload(card, { loaded: card.dataset.loaded === 'true', message });
        });

        xhr.addEventListener('error', () => {
            finishUpload(card, {
                loaded: card.dataset.loaded === 'true',
                message: 'Se interrumpió la conexión. Volvé a intentar la carga.',
            });
        });

        xhr.send(data);
    };

    cards.forEach((card) => {
        const input = card.querySelector('[data-doc-input]');
        if (!input) return;

        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (file) upload(card, file);
            input.value = '';
        });
    });

    if (submitForm && submitButton) {
        submitForm.addEventListener('submit', (event) => {
            const missing = missingLabels();
            if (missing.length) {
                event.preventDefault();
                if (submitError) {
                    submitError.textContent = `Faltan documentos: ${missing.join(', ')}.`;
                    submitError.hidden = false;
                }
                refreshProgress();
                return;
            }

            if (submitting) {
                event.preventDefault();
                return;
            }

            const confirmed = window.confirm(
                '¿Enviar la documentación a revisión? Ya no vas a poder modificar los documentos hasta que el equipo la revise.'
            );
            if (!confirmed) {
                event.preventDefault();
                return;
            }

            submitting = true;
            if (submitError) submitError.hidden = true;
            submitButton.disabled = true;
            submitButton.textContent = 'Enviando…';
        });
    }

    refreshProgress();
})();
