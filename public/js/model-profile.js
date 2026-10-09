(() => {
  const gallery = document.querySelector('.public-model-gallery');
  if (!gallery) return;
  const link = gallery.querySelector('.profile-main-photo');
  const main = link?.querySelector('img');
  if (!main) return;
  const thumbnails = [...gallery.querySelectorAll('.profile-thumbnail')];
  thumbnails.forEach(button => button.addEventListener('click', () => {
    const image = button.querySelector('img');
    main.src = image.src;
    main.alt = image.alt;
    main.width = image.width;
    main.height = image.height;
    link.href = image.src;
    thumbnails.forEach(thumbnail => thumbnail.setAttribute('aria-pressed', String(thumbnail === button)));
  }));
})();
