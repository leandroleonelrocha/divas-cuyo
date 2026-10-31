(() => {
    const panel = document.querySelector('[data-photos-panel]');
    if (!panel) return;

    const input = panel.querySelector('[data-photos-input]');
    const dropzone = panel.querySelector('[data-photos-dropzone]');
    const queue = panel.querySelector('[data-photos-queue]');
    const grid = panel.querySelector('[data-photos-grid]');
    const addCard = panel.querySelector('[data-photos-add]');
    const countLabel = document.querySelector('[data-photos-count]');
    const errorBox = panel.querySelector('[data-photos-errors]');
    const successBox = panel.querySelector('[data-photos-success]');
    const orderList = grid;
    const orderInputs = document.querySelector('#photo-order-inputs');
    const orderForm = document.querySelector('#photos-order-form');
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const maxPhotos = parseInt(panel.dataset.limit, 10) || 5;
    const maxSizeKb = parseInt(panel.dataset.maxSizeKb, 10) || 10240;
    const allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    const allowedExtensions = /\.(jpe?g|png|webp)$/i;

    const state = {
        count: parseInt(panel.dataset.count, 10) || 0,
        empty: panel.dataset.empty === 'true',
        full: panel.dataset.full === 'true',
    };

    const pending = [];
    let uploading = false;
    let dragDepth = 0;

    const truncate = (name) => (name.length > 32 ? `${name.slice(0, 29)}…` : name);

    const parseJsonResponse = async (response, fallbackMessage) => {
        const raw = await response.text();
        let payload = {};
        try {
            payload = raw ? JSON.parse(raw) : {};
        } catch (_) {}

        if (response.redirected || response.url.includes('/login')) {
            throw new Error('Tu sesión expiró. Volvé a ingresar para continuar.');
        }
        if (!response.ok) {
            throw new Error(Object.values(payload.errors || {}).flat().join(' ')
                || payload.message
                || fallbackMessage);
        }

        return payload;
    };

    const showError = (message) => {
        if (!errorBox) return;
        errorBox.textContent = message || '';
        errorBox.hidden = !message;
        if (message && successBox) successBox.hidden = true;
    };

    const showSuccess = (message) => {
        if (!successBox) return;
        successBox.textContent = message || '';
        successBox.hidden = !message;
        if (message && errorBox) errorBox.hidden = true;
    };

    const syncState = () => {
        state.empty = state.count === 0;
        state.full = state.count >= maxPhotos;
        panel.dataset.count = String(state.count);
        panel.dataset.empty = state.empty ? 'true' : 'false';
        panel.dataset.full = state.full ? 'true' : 'false';
        if (countLabel) countLabel.textContent = `${state.count} / ${maxPhotos}`;
    };

    const validate = (file) => {
        if (!file) return 'No se pudo leer el archivo seleccionado.';
        if (!allowedMimes.includes(file.type) && !allowedExtensions.test(file.name)) {
            return `${file.name}: sólo se permiten imágenes JPEG, PNG o WebP.`;
        }
        if (file.size > maxSizeKb * 1024) {
            return `${file.name}: la fotografía no puede superar 10 MB.`;
        }
        return null;
    };

    const setItemState = (item, status, message) => {
        item.dataset.state = status;
        const label = item.querySelector('[data-queue-state]');
        if (label) label.textContent = message;
        const retry = item.querySelector('[data-queue-retry]');
        if (retry) retry.hidden = status !== 'error';
        const bar = item.querySelector('[data-queue-bar]');
        if (bar && status === 'uploading') bar.style.width = '0%';
    };

    const removeItem = (item) => {
        const url = item.dataset.objectUrl;
        if (url) URL.revokeObjectURL(url);
        item.remove();
        if (queue && !queue.children.length) queue.hidden = true;
    };

    const createQueueItem = (file) => {
        const item = document.createElement('div');
        item.className = 'photos-upload-item';
        item.dataset.state = 'queued';
        item.innerHTML = `
            <span class="photos-upload-thumb"><img alt=""></span>
            <span class="photos-upload-meta">
                <span class="photos-upload-name" data-queue-name></span>
                <span class="photos-upload-state" data-queue-state>En cola…</span>
                <span class="photos-upload-track"><span class="photos-upload-bar" data-queue-bar></span></span>
            </span>
            <button type="button" class="photos-upload-retry" data-queue-retry hidden>Reintentar</button>`;

        const name = item.querySelector('[data-queue-name]');
        if (name) name.textContent = truncate(file.name);

        const thumb = item.querySelector('img');
        if (thumb) {
            const url = URL.createObjectURL(file);
            item.dataset.objectUrl = url;
            thumb.src = url;
        }

        const retry = item.querySelector('[data-queue-retry]');
        if (retry) {
            retry.addEventListener('click', () => {
                const invalid = validate(file);
                if (invalid) {
                    setItemState(item, 'error', invalid);
                    return;
                }
                if (state.count >= maxPhotos) {
                    setItemState(item, 'error', `Alcanzaste el máximo de ${maxPhotos} fotografías.`);
                    return;
                }
                setItemState(item, 'queued', 'En cola…');
                pending.push({ file, item });
                runNext();
            });
        }

        if (queue) {
            queue.hidden = false;
            queue.appendChild(item);
        }

        return item;
    };

    const insertCard = (html) => {
        if (!grid || !html) return null;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = String(html).trim();
        const card = wrapper.firstElementChild;
        if (!card) return null;
        if (addCard) grid.insertBefore(card, addCard);
        else grid.appendChild(card);
        syncOrder();
        return card;
    };

    const uploadJob = (job) => new Promise((resolve) => {
        const { file, item } = job;
        const bar = item.querySelector('[data-queue-bar]');
        const data = new FormData();
        data.append('photo', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', panel.dataset.uploadUrl);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', token);

        xhr.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable || !bar) return;
            const percent = Math.round((event.loaded / event.total) * 100);
            bar.style.width = `${percent}%`;
            setItemState(item, 'uploading', percent >= 100 ? 'Guardando…' : `Subiendo… ${percent}%`);
        });

        const fail = (message) => {
            setItemState(item, 'error', message);
            resolve();
        };

        xhr.addEventListener('load', () => {
            const loginExpired = xhr.responseURL.includes('/login');
            if (xhr.status >= 200 && xhr.status < 300 && !loginExpired) {
                try {
                    const payload = JSON.parse(xhr.responseText);
                    state.count = parseInt(payload.count, 10) || state.count + 1;
                    syncState();
                    insertCard(payload.card);
                    setItemState(item, 'done', 'Cargada ✓');
                    showSuccess(payload.status || 'La fotografía se cargó y quedó en revisión.');
                    window.setTimeout(() => removeItem(item), 700);
                    resolve();
                } catch (_) {
                    fail('No pudimos confirmar la carga. Revisá tu galería y volvé a intentar.');
                }
                return;
            }

            let message = 'No pudimos cargar la fotografía. Revisá el formato y el tamaño del archivo.';
            if (xhr.status === 419 || loginExpired) message = 'Tu sesión expiró. Volvé a ingresar para continuar.';
            else {
                try {
                    const payload = JSON.parse(xhr.responseText);
                    message = Object.values(payload.errors || {}).flat().join(' ') || payload.message || message;
                } catch (_) {}
            }
            fail(message);
        });

        xhr.addEventListener('error', () => fail('Se interrumpió la conexión. Volvé a intentar la carga.'));
        xhr.addEventListener('timeout', () => fail('La carga tardó demasiado. Volvé a intentarla.'));

        setItemState(item, 'uploading', 'Subiendo… 0%');
        xhr.send(data);
    });

    const runNext = async () => {
        if (uploading) return;
        const job = pending.shift();
        if (!job) return;

        if (state.count >= maxPhotos) {
            setItemState(job.item, 'error', `Alcanzaste el máximo de ${maxPhotos} fotografías.`);
            runNext();
            return;
        }

        uploading = true;
        await uploadJob(job);
        uploading = false;
        runNext();
    };

    const enqueue = (files) => {
        const list = Array.from(files || []);
        if (!list.length) return;

        showError('');
        showSuccess('');

        list.forEach((file) => {
            const item = createQueueItem(file);
            const invalid = validate(file);
            if (invalid) {
                setItemState(item, 'error', invalid);
                return;
            }
            pending.push({ file, item });
        });

        runNext();
    };

    if (input) {
        input.addEventListener('change', () => {
            enqueue(input.files);
            input.value = '';
        });
    }

    if (panel) {
        panel.addEventListener('dragenter', (event) => {
            event.preventDefault();
            dragDepth += 1;
            panel.classList.add('is-dragover');
        });
        panel.addEventListener('dragover', (event) => {
            event.preventDefault();
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'copy';
        });
        panel.addEventListener('dragleave', (event) => {
            event.preventDefault();
            dragDepth = Math.max(0, dragDepth - 1);
            if (dragDepth === 0) panel.classList.remove('is-dragover');
        });
        panel.addEventListener('drop', (event) => {
            event.preventDefault();
            dragDepth = 0;
            panel.classList.remove('is-dragover');
            enqueue(event.dataTransfer?.files);
        });
    }

    const updatePrimary = (id) => {
        grid.querySelectorAll('[data-photo-card]').forEach(card => {
            const primary = String(card.dataset.photoId) === String(id);
            card.querySelector('.photo-primary-badge')?.remove();
            if (primary) {
                const badge = document.createElement('span');
                badge.className = 'photo-primary-badge';
                badge.textContent = 'Principal';
                card.querySelector('.photo-card-body').append(badge);
            }
            const form = card.querySelector('.photo-primary-form');
            if (form) form.hidden = primary;
        });
    };
    grid.addEventListener('submit', async event => {
        const form = event.target.closest('.photo-primary-form');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('button');
        button.disabled = true;
        showSuccess('Actualizando foto principal…');
        try {
            const payload = await fetch(form.action, {
                method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token }
            }).then(response => parseJsonResponse(response, 'No pudimos cambiar la foto principal.'));
            updatePrimary(payload.primary_id);
            showSuccess(payload.status);
        } catch (error) { showError(error.message); }
        finally { button.disabled = false; }
    });

    if (grid) {
        grid.addEventListener('change', (event) => {
            const replaceInput = event.target.closest('[data-replace-input]');
            if (!replaceInput) return;

            const file = replaceInput.files && replaceInput.files[0];
            replaceInput.value = '';
            if (!file) return;

            const form = replaceInput.closest('[data-replace-form]');
            const card = replaceInput.closest('[data-photo-card]');
            if (!form || !card) return;

            const invalid = validate(file);
            if (invalid) {
                showError(invalid);
                return;
            }
            if (card.classList.contains('is-busy')) return;

            const data = new FormData();
            data.append('_token', token);
            data.append('photo', file);

            card.classList.add('is-busy');
            showError('');
            showSuccess('Reemplazando fotografía…');

            fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
                body: data,
            }).then((response) => parseJsonResponse(
                response,
                'No pudimos reemplazar la fotografía. Revisá el formato y el tamaño del archivo.'
            )).then((payload) => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = String(payload.card || '').trim();
                const replacement = wrapper.firstElementChild;
                if (replacement) card.replaceWith(replacement);
                showSuccess(payload.status || 'La nueva versión se cargó y quedó en revisión.');
                syncOrder();
            }).catch((error) => {
                showError(error.message || 'No pudimos reemplazar la fotografía.');
            }).finally(() => {
                const current = document.querySelector(`[data-photo-id="${CSS.escape(card.dataset.photoId || '')}"]`);
                if (current) current.classList.remove('is-busy');
            });
        });

        grid.addEventListener('submit', (event) => {
            const form = event.target.closest('[data-delete-form]');
            if (!form) return;

            event.preventDefault();
            const card = form.closest('[data-photo-card]');
            if (!card || card.classList.contains('is-busy')) return;

            const confirmed = window.confirm('¿Querés eliminar esta fotografía? Esta acción no se puede deshacer.');
            if (!confirmed) return;

            card.classList.add('is-busy');
            showError('');

            fetch(form.action, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
            }).then((response) => parseJsonResponse(response, 'No pudimos eliminar la fotografía.'))
                .then((payload) => {
                    card.remove();
                    updatePrimary(payload.primary_id);
                    state.count = parseInt(payload.count, 10) || Math.max(0, state.count - 1);
                    syncState();
                    syncOrder();
                    showSuccess(payload.status || 'La fotografía se eliminó correctamente.');

                }).catch((error) => {
                    card.classList.remove('is-busy');
                    showError(error.message || 'No pudimos eliminar la fotografía.');
                });
        });
    }

    const syncOrder = () => {
        if (!orderList || !orderInputs || !orderForm) return;

        orderInputs.replaceChildren(...Array.from(orderList.querySelectorAll('[data-photo-id]'), (card) => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'photo_ids[]';
            hidden.value = card.dataset.photoId;
            return hidden;
        }));

        orderList.querySelectorAll('[data-photo-position]').forEach((label, index) => {
            label.textContent = `Foto ${index + 1}`;
        });
        orderList.querySelectorAll('[data-order-move="up"]').forEach((button, index) => {
            button.disabled = index === 0;
        });
        orderList.querySelectorAll('[data-order-move="down"]').forEach((button, index, buttons) => {
            button.disabled = index === buttons.length - 1;
        });
    };

    if (orderList && orderForm) {
        orderList.addEventListener('click', (event) => {
            const button = event.target.closest('[data-order-move]');
            if (!button) return;
            const card = button.closest('[data-photo-id]');
            const sibling = button.dataset.orderMove === 'up' ? card.previousElementSibling : card.nextElementSibling;
            if (!sibling || !sibling.hasAttribute('data-photo-id')) return;
            if (button.dataset.orderMove === 'up') orderList.insertBefore(card, sibling);
            else orderList.insertBefore(sibling, card);
            syncOrder();
            saveOrder();
        });

        orderForm.addEventListener('submit', event => { event.preventDefault(); saveOrder(); });
    }

    let savingOrder = false;
    let dirtyOrder = false;
    const retryOrder = panel.querySelector('[data-order-retry]');
    async function saveOrder() {
        dirtyOrder = true;
        if (savingOrder) return;
        savingOrder = true;
        retryOrder.hidden = true;
        while (dirtyOrder) {
            dirtyOrder = false;
            showSuccess('Guardando orden…');
            try {
                const ids = [...grid.querySelectorAll('[data-photo-id]')].map(card => card.dataset.photoId);
                await fetch(orderForm.action, {
                    method: 'PATCH',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ photo_ids: ids })
                }).then(response => parseJsonResponse(response, 'No pudimos guardar el orden.'));
                showSuccess('Orden guardado');
            } catch (error) {
                dirtyOrder = true;
                showError(error.message);
                retryOrder.hidden = false;
                break;
            }
        }
        savingOrder = false;
    }
    retryOrder.addEventListener('click', saveOrder);
    window.addEventListener('beforeunload', event => {
        if (dirtyOrder || savingOrder || uploading) { event.preventDefault(); event.returnValue = ''; }
    });
    let dragging = null;

    grid.addEventListener('pointerdown', event => {
        const handle = event.target.closest('[data-photo-drag]');
        if (!handle || event.button !== 0) return;
        dragging = handle.closest('[data-photo-card]');

        handle.setPointerCapture(event.pointerId);
        dragging.classList.add('is-dragging');
    });
    grid.addEventListener('pointermove', event => {
        if (!dragging) return;
        const target = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-photo-card]');
        if (!target || target === dragging || target.parentElement !== grid) return;
        const cards = [...grid.querySelectorAll('[data-photo-card]')];
        if (cards.indexOf(target) > cards.indexOf(dragging)) target.after(dragging);
        else target.before(dragging);
        syncOrder();
    });
    const finishDrag = () => {
        if (!dragging) return;
        dragging.classList.remove('is-dragging');
        dragging = null;

        saveOrder();
    };
    grid.addEventListener('pointerup', finishDrag);
    grid.addEventListener('pointercancel', finishDrag);
    const viewer = document.getElementById('account-photo-viewer');
    grid.addEventListener('click', event => {
        const preview = event.target.closest('[data-photo-view]');
        if (!preview) return;
        viewer.querySelector('img').src = preview.dataset.photoView;
        viewer.showModal();
    });
    viewer.querySelector('[data-viewer-close]').addEventListener('click', () => viewer.close());
    syncState();
    syncOrder();
})();
