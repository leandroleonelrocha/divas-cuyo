(() => {
    const form = document.getElementById('video-upload');
    if (!form) return;
    const input = document.getElementById('video-file');
    const preview = document.getElementById('video-preview');
    const message = document.getElementById('video-upload-message');
    const progress = document.getElementById('video-upload-progress');
    const button = form.querySelector('button[type="submit"]');
    let objectUrl;
    input.addEventListener('change', () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        preview.hidden = !input.files.length;
        if (input.files.length) {
            objectUrl = URL.createObjectURL(input.files[0]);
            preview.src = objectUrl;
        } else preview.removeAttribute('src');
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const data = new FormData(form);
        button.disabled = true;
        input.disabled = true;
        progress.hidden = false;
        progress.value = 0;
        message.textContent = 'Subiendo video…';
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) progress.value = event.loaded / event.total * 100;
            if (progress.value === 100) message.textContent = 'Subida completada. Guardando video…';
        });
        const failure = (text) => {
            message.textContent = text;
            button.disabled = false;
            input.disabled = false;
            progress.hidden = true;
        };
        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300 && !xhr.responseURL.includes('/login')) {
                window.location.assign(form.action);
                return;
            }
            let text = 'No se pudo subir el video. Revisá tu sesión, el formato y el tamaño del archivo.';
            try { text = Object.values(JSON.parse(xhr.responseText).errors || {}).flat().join(' ') || text; } catch (_) {}
            failure(text);
        });
        xhr.addEventListener('error', () => failure('Se interrumpió la conexión. Volvé a intentar la subida.'));
        xhr.send(data);
    });
})();
