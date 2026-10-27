(() => {
  const groupsContainer = document.getElementById('profile-groups');
  if (!groupsContainer) return;
  const grids = [...groupsContainer.querySelectorAll('.card-grid')];
  const groups = [...groupsContainer.querySelectorAll('.province-group')];
  const cards = [...groupsContainer.querySelectorAll('.profile-card')];
  const province = document.getElementById('province-filter');
  const locality = document.getElementById('locality-filter');
  const search = document.getElementById('name-filter');
  const sort = document.getElementById('sort-order');
  const tabs = [...document.querySelectorAll('[data-view]')];
  const avatarCards = [...document.querySelectorAll('.avatar-card[data-service]')];
  const key = 'divas-cuyo:model-favorites:v1';
  let favorites = new Set();
  let onlyFavorites = location.hash === '#favoritos';
  let serviceView = 'all';
  try {
    const saved = JSON.parse(localStorage.getItem(key) || '[]');
    if (Array.isArray(saved)) favorites = new Set(saved.filter(value => typeof value === 'string'));
  } catch { const note = document.getElementById('storage-note'); if (note) note.hidden = false; }
  const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
  function updateCities() {
    if (!province || !locality) return;
    locality.value = '';
    locality.disabled = !province.value;
    [...locality.options].slice(1).forEach(option => {
      option.hidden = option.dataset.province !== province.value;
      option.disabled = option.hidden;
    });
  }
  function render() {
    let count = 0;
    const provinceValue = province?.value || '';
    const localityValue = locality?.value || '';
    const searchValue = search?.value || '';
    const serviceMatch = element => serviceView === 'all' ||
      (element.dataset.categories || element.dataset.service || 'escort').split(' ').includes(serviceView);
    avatarCards.forEach(avatar => {
      avatar.hidden = !serviceMatch(avatar);
    });
    cards.forEach(card => {
      const saved = favorites.has(card.dataset.key);
      const button = card.querySelector('.favorite-button');
      if (button) {
        button.setAttribute('aria-pressed', String(saved));
        button.setAttribute('aria-label', `${saved ? 'Quitar a' : 'Guardar a'} ${card.dataset.name} ${saved ? 'de' : 'en'} favoritos`);
      }
      card.hidden = !((!provinceValue || card.dataset.province === provinceValue) &&
        (!localityValue || card.dataset.locality === localityValue) &&
        serviceMatch(card) &&
        normalize(card.dataset.name).includes(normalize(searchValue.trim())) && (!onlyFavorites || saved));
      if (!card.hidden) count++;
    });
    groups.forEach(group => {
      const visible = [...group.querySelectorAll('.profile-card')].filter(card => !card.hidden).length;
      group.hidden = visible === 0;
      const counter = group.querySelector('.province-count');
      if (counter) counter.textContent = `${visible} ${visible === 1 ? 'perfil' : 'perfiles'}`;
    });
    tabs.forEach(tab => {
      const active = tab.dataset.view === 'favorites' ? onlyFavorites : (!onlyFavorites && tab.dataset.view === serviceView);
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-pressed', String(active));
    });
    const favoriteCount = document.getElementById('favorite-count');
    if (favoriteCount) favoriteCount.textContent = cards.filter(card => favorites.has(card.dataset.key)).length;
    const resultCount = document.getElementById('result-count');
    if (resultCount) resultCount.textContent = `${count} ${count === 1 ? 'perfil' : 'perfiles'} ${onlyFavorites ? 'en favoritos' : 'para descubrir'}`;
    const profilesTitle = document.getElementById('profiles-title');
    const virtualDisclaimer = document.getElementById('virtual-disclaimer');
    if (virtualDisclaimer) virtualDisclaimer.hidden = serviceView !== 'virtual';
    if (profilesTitle) {
      const place = provinceValue ? province.selectedOptions[0].textContent : 'la región';
      if (onlyFavorites) profilesTitle.textContent = 'Tus modelos favoritos';
      else if (serviceView === 'virtual') profilesTitle.textContent = `Modelos virtuales de ${place}`;
      else if (serviceView === 'escort') profilesTitle.textContent = `Escorts de ${place}`;
      else if (serviceView === 'masajistas') profilesTitle.textContent = `Masajistas de ${place}`;
      else if (serviceView === 'trans') profilesTitle.textContent = `Modelos trans de ${place}`;
      else profilesTitle.textContent = provinceValue ? `Modelos en ${place}` : 'Modelos de la región';
    }
    const emptyState = document.getElementById('empty-state');
    if (emptyState) emptyState.hidden = count > 0;
    if (cards.length) {
      const emptyTitle = document.getElementById('empty-title');
      if (emptyTitle) emptyTitle.textContent = onlyFavorites ? 'Tu selección empieza acá' : 'No encontramos coincidencias';
      const emptyMessage = document.getElementById('empty-message');
      if (emptyMessage) emptyMessage.textContent = onlyFavorites ? 'Guardá los portfolios que te inspiran con el corazón de cada perfil, o probá cambiar los filtros.' : 'Probá otra categoría o restablecé los filtros.';
      const emptyCreate = document.getElementById('empty-create');
      if (emptyCreate) emptyCreate.hidden = true;
      const resetFilters = document.getElementById('reset-filters');
      if (resetFilters) resetFilters.hidden = false;
    }
  }
  province?.addEventListener('change', () => { updateCities(); render(); });
  locality?.addEventListener('change', render);
  search?.addEventListener('input', render);
  tabs.forEach(tab => tab.addEventListener('click', () => {
    if (tab.dataset.view === 'favorites') {
      onlyFavorites = true;
    } else {
      serviceView = tab.dataset.view;
      onlyFavorites = false;
    }
    render();
  }));
  cards.forEach(card => card.querySelector('.favorite-button')?.addEventListener('click', () => {
    const id = card.dataset.key;
    favorites.has(id) ? favorites.delete(id) : favorites.add(id);
    try { localStorage.setItem(key, JSON.stringify([...favorites])); }
    catch { const note = document.getElementById('storage-note'); if (note) note.hidden = false; }
    render();
  }));
  sort?.addEventListener('change', () => {
    grids.forEach(grid => {
      [...grid.querySelectorAll('.profile-card')].sort((a, b) => sort.value === 'name' ? a.dataset.name.localeCompare(b.dataset.name, 'es') : Number(a.dataset.order) - Number(b.dataset.order)).forEach(card => grid.append(card));
    });
  });
  document.getElementById('reset-filters')?.addEventListener('click', () => {
    if (province) province.value = '';
    if (search) search.value = '';
    onlyFavorites = false; serviceView = 'all'; updateCities(); render();
  });
  document.querySelector('.dc-front-star')?.addEventListener('click', event => {
    event.preventDefault(); onlyFavorites = true; render();
    document.getElementById('modelos').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
  });
  if (onlyFavorites) document.getElementById('modelos').scrollIntoView();
  const params = new URLSearchParams(location.search);
  const requestedService = params.get('tipo');
  if (['all', 'escort', 'virtual', 'masajistas', 'trans'].includes(requestedService)) serviceView = requestedService;
  const requestedProvince = params.get('provincia');
  if (province && [...province.options].some(option => option.value === requestedProvince)) province.value = requestedProvince;
  updateCities(); render();
})();
