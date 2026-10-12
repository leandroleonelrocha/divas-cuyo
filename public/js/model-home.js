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
    locality.value = '';
    locality.disabled = !province.value;
    [...locality.options].slice(1).forEach(option => {
      option.hidden = option.dataset.province !== province.value;
      option.disabled = option.hidden;
    });
  }
  function render() {
    let count = 0;
    const serviceMatch = service => serviceView === 'all' || (service || 'escort') === serviceView;
    avatarCards.forEach(avatar => {
      avatar.hidden = !serviceMatch(avatar.dataset.service);
    });
    cards.forEach(card => {
      const saved = favorites.has(card.dataset.key);
      const button = card.querySelector('.favorite-button');
      if (button) {
        button.setAttribute('aria-pressed', String(saved));
        button.setAttribute('aria-label', `${saved ? 'Quitar a' : 'Guardar a'} ${card.dataset.name} ${saved ? 'de' : 'en'} favoritos`);
      }
      card.hidden = !((!province.value || card.dataset.province === province.value) &&
        (!locality.value || card.dataset.locality === locality.value) &&
        serviceMatch(card.dataset.service) &&
        normalize(card.dataset.name).includes(normalize(search.value.trim())) && (!onlyFavorites || saved));
      if (!card.hidden) count++;
    });
    groups.forEach(group => {
      const visible = [...group.querySelectorAll('.profile-card')].some(card => !card.hidden);
      group.hidden = !visible;
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
      const place = province.value ? province.selectedOptions[0].textContent : 'la región';
      if (onlyFavorites) profilesTitle.textContent = 'Tus modelos favoritos';
      else if (serviceView === 'virtual') profilesTitle.textContent = `Modelos virtuales de ${place}`;
      else if (serviceView === 'escort') profilesTitle.textContent = `Escorts de ${place}`;
      else profilesTitle.textContent = province.value ? `Modelos en ${place}` : 'Modelos de la región';
    }
    const emptyState = document.getElementById('empty-state');
    if (emptyState) emptyState.hidden = count > 0;
    if (cards.length) {
      const emptyTitle = document.getElementById('empty-title');
      if (emptyTitle) emptyTitle.textContent = onlyFavorites ? 'Tu selección empieza acá' : 'No encontramos coincidencias';
      const emptyMessage = document.getElementById('empty-message');
      if (emptyMessage) emptyMessage.textContent = onlyFavorites ? 'Guardá los portfolios que te inspiran con el corazón de cada perfil, o probá cambiar los filtros.' : 'Probá otra ciudad o buscá un nombre diferente.';
      const emptyCreate = document.getElementById('empty-create');
      if (emptyCreate) emptyCreate.hidden = true;
      const resetFilters = document.getElementById('reset-filters');
      if (resetFilters) resetFilters.hidden = false;
    }
  }
  province.addEventListener('change', () => { updateCities(); render(); });
  locality.addEventListener('change', render);
  search.addEventListener('input', render);
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
    province.value = ''; search.value = ''; onlyFavorites = false; serviceView = 'all'; updateCities(); render();
  });
  document.querySelector('.dc-front-star')?.addEventListener('click', event => {
    event.preventDefault(); onlyFavorites = true; render();
    document.getElementById('modelos').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
  });
  if (onlyFavorites) document.getElementById('modelos').scrollIntoView();
  const params = new URLSearchParams(location.search);
  const requestedService = params.get('tipo');
  if (requestedService === 'virtual' || requestedService === 'escort' || requestedService === 'all') serviceView = requestedService;
  const requestedProvince = params.get('provincia');
  if ([...province.options].some(option => option.value === requestedProvince)) province.value = requestedProvince;
  updateCities(); render();
})();
