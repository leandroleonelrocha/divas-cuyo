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

  const panelBtns = [...document.querySelectorAll('[data-panel-btn]')];
  const panels = [...document.querySelectorAll('[data-panel]')];
  if (panelBtns.length > 0 && panels.length > 0) {
    const showPanel = (name, scroll = true) => {
      panelBtns.forEach((btn) => {
        const active = btn.getAttribute('data-panel-btn') === name;
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-selected', String(active));
      });
      panels.forEach((panel) => {
        panel.hidden = panel.getAttribute('data-panel') !== name;
      });
      if (scroll) {
        const target = panels.find((p) => p.getAttribute('data-panel') === name);
        target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    };

    panelBtns.forEach((btn) => {
      btn.addEventListener('click', () => showPanel(btn.getAttribute('data-panel-btn') || 'galeria'));
    });

    const hash = window.location.hash.replace('#', '');
    if (['galeria', 'info'].includes(hash)) {
      showPanel(hash, false);
    } else if (hash === 'panel-info' || hash === 'dv-servicios' || hash === 'dv-sobre-mi') {
      showPanel('info', false);
    } else if (hash === 'panel-galeria' || hash === 'gallery-title') {
      showPanel('galeria', false);
    }
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
