(() => {
  const gallery = document.querySelector('.public-model-gallery');
  if (gallery) {
    const link = gallery.querySelector('[data-gallery-link]');
    const main = gallery.querySelector('[data-gallery-main]');
    const thumbs = [...gallery.querySelectorAll('[data-gallery-thumb]')];
    const indexEl = gallery.querySelector('[data-gallery-index]');
    const prev = gallery.querySelector('[data-gallery-prev]');
    const next = gallery.querySelector('[data-gallery-next]');
    let index = Math.max(0, thumbs.findIndex((b) => b.getAttribute('aria-pressed') === 'true'));

    const show = (nextIndex) => {
      if (!main || thumbs.length === 0) return;
      index = (nextIndex + thumbs.length) % thumbs.length;
      const button = thumbs[index];
      const image = button.querySelector('img');
      if (!image) return;
      main.src = image.src;
      main.alt = image.alt;
      main.width = image.width;
      main.height = image.height;
      if (link) link.href = button.getAttribute('data-gallery-thumb') || image.src;
      if (indexEl) indexEl.textContent = String(index + 1);
      thumbs.forEach((thumb, i) => thumb.setAttribute('aria-pressed', String(i === index)));
    };

    thumbs.forEach((button, i) => button.addEventListener('click', () => show(i)));
    prev?.addEventListener('click', () => show(index - 1));
    next?.addEventListener('click', () => show(index + 1));
    gallery.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') show(index - 1);
      if (event.key === 'ArrowRight') show(index + 1);
    });
  }

  const favKey = 'dv-favs';
  const readFavs = () => {
    try {
      const raw = localStorage.getItem(favKey);
      const parsed = raw ? JSON.parse(raw) : [];
      return new Set(Array.isArray(parsed) ? parsed : []);
    } catch {
      return new Set();
    }
  };
  const favs = readFavs();
  const persist = () => {
    try {
      localStorage.setItem(favKey, JSON.stringify([...favs]));
    } catch {
      /* almacenamiento no disponible: solo sesión */
    }
  };
  document.querySelectorAll('[data-favorite-name]').forEach((button) => {
    const name = button.getAttribute('data-favorite-name') || '';
    const paint = () => {
      const active = favs.has(name);
      button.setAttribute('aria-pressed', String(active));
      button.textContent = active ? '♥' : (button.classList.contains('dv-fav-mini') ? '♡' : '♥');
      button.style.opacity = active || !button.classList.contains('dv-fav-mini') ? '1' : '';
    };
    paint();
    button.addEventListener('click', () => {
      if (favs.has(name)) {
        favs.delete(name);
      } else {
        favs.add(name);
      }
      persist();
      paint();
    });
  });
})();
