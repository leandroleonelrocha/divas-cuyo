<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Descubrí modelos y portfolios de la región en Divas Cuyo. Un espacio para el talento, la moda y la expresión personal.">
  <meta name="theme-color" content="#101216">
  <title>Divas Cuyo — Modelos de la región</title>
  @include('components.front-header-styles')
  <link rel="stylesheet" href="{{ asset('css/model-home.css') }}?v={{ filemtime(public_path('css/model-home.css')) }}">
  <script src="{{ asset('js/model-home.js') }}" defer></script>
  <script src="{{ asset('js/home-fire.js') }}" defer></script>
</head>
<body class="model-home">
  <a class="skip-link" href="#modelos">Saltar a los perfiles</a>
  <x-front-header :on-home="true" />
  <main>
    <section class="hero" aria-labelledby="hero-title">
      <canvas id="fuego" class="hero-fire" aria-hidden="true"></canvas>
      <div class="shell hero-content">
        <h1 id="hero-title">LAS MÁS CALIENTES <br><span>DE LA REGIÓN</span></h1>
        <p class="hero-tagline">ENCUENTRA · CONECTA · DISFRUTA</p>
        <div class="hero-values">
          <div><span class="value-icon" aria-hidden="true">♛</span><p><strong>PERFILES REALES </strong><span>Verificados </span></p></div>
          <div><span class="value-icon" aria-hidden="true">◇</span><p><strong>DISCRECIÓN</strong><span>Totalmente privada  </span></p></div>
          <div><span class="value-icon" aria-hidden="true">ϟ</span><p><strong>EXPERIENCIAS</strong><span>Únicas en tu ciudad</span></p></div>
        </div>
      </div>
    </section>

    @php
      $provinces = \App\Models\Province::query()->active()->with(['localities' => fn ($query) => $query->active()->orderBy('name')])->orderBy('name')->get();
    @endphp
    <section class="filter-bar" aria-label="Buscar modelos">
      <div class="shell filter-inner">
        <label class="control location-control"><span aria-hidden="true">⌖</span>
          <select id="province-filter" aria-label="Provincia">
            <option value="">Toda la región</option>
            @foreach ($provinces as $province)
              <option value="{{ $province->slug }}">{{ $province->name }}</option>
            @endforeach
          </select>
        </label>
        <label class="control">
          <select id="locality-filter" disabled aria-label="Localidad">
            <option value="">Todas las ciudades</option>
            @foreach ($provinces as $province)
              @foreach ($province->localities as $locality)
                <option value="{{ $locality->slug }}" data-province="{{ $province->slug }}" hidden>{{ $locality->name }}</option>
              @endforeach
            @endforeach
          </select>
        </label>
        <label class="control search-control"><span aria-hidden="true">⌕</span><input id="name-filter" type="search" placeholder="Buscar modelo" aria-label="Buscar modelo por nombre" autocomplete="off"></label>
        <div class="filter-tabs" role="group" aria-label="Perfiles a mostrar">
          <button type="button" class="pill active" data-view="all" aria-pressed="true">Escorts</button>
          <button type="button" class="pill " data-view="all" aria-pressed="true">Virtual</button>
          {{-- <button type="button" class="pill" data-view="favorites" aria-pressed="false">♡ Favoritos <span id="favorite-count">0</span></button> --}}
        </div>
        <a class="explore-link" href="#modelos">EXPLORAR <span aria-hidden="true">↗</span></a>
      </div>
    </section>

    <section class="spotlight" id="destacadas" aria-labelledby="spotlight-title">
      <div class="shell spotlight-inner">
        <div class="spotlight-heading"><p class="eyebrow">EN EL RADAR</p><h2 id="spotlight-title">Destacadas hoy</h2><p>Conocé a las divas más activas de la región.</p></div>
        <div class="avatar-scroller" aria-label="Perfiles destacados" tabindex="0">
          @foreach ($publicProfiles as $profile)
            <a class="avatar-card" href="{{ $profile['url'] }}">
              <span class="avatar-ring"><img src="{{ $profile['photoUrl'] }}" alt="{{ $profile['photoAlt'] }}" loading="lazy" width="88" height="88"></span>
              <strong>{{ $profile['name'] }}</strong><span>{{ $profile['location'] }}</span>
            </a>
          @endforeach
          @if (count($publicProfiles) === 0)
            <p class="spotlight-empty">Todavía no hay perfiles destacados. Volvé pronto a descubrir a las primeras divas.</p>
          @endif
        </div>
        <a class="see-all" href="#modelos"><span aria-hidden="true">•••</span>Ver todas<br>las divas</a>
      </div>
    </section>

    <section class="shell portfolio-section" id="modelos" aria-labelledby="profiles-title">
      <div class="section-heading"><div><h2 id="profiles-title">Modelos de la región</h2><p>Las mejores experiencias, reales y verificadas.</p></div>
        <label class="control sort-control"><span aria-hidden="true">↕</span><select id="sort-order" aria-label="Ordenar perfiles"><option value="recent">Más recientes</option><option value="name">Nombre: A a Z</option></select></label>
      </div>
      <p class="results-count" id="result-count" role="status" aria-live="polite">{{ count($publicProfiles) }} {{ count($publicProfiles) === 1 ? 'perfil' : 'perfiles' }} para descubrir</p>
      <div class="card-grid" id="profile-grid">
        @foreach ($publicProfiles as $profile)
          <article class="profile-card" data-name="{{ $profile['name'] }}" data-province="{{ $profile['province'] }}" data-locality="{{ $profile['locality'] }}" data-key="{{ $profile['url'] }}" data-order="{{ $loop->index }}">
            <a class="profile-photo" href="{{ $profile['url'] }}" aria-label="Ver portfolio de {{ $profile['name'] }}"><img src="{{ $profile['photoUrl'] }}" alt="{{ $profile['photoAlt'] }}" loading="lazy" width="360" height="480"></a>
            <span class="profile-label">PORTFOLIO</span>
            <button class="favorite-button" type="button" aria-label="Guardar a {{ $profile['name'] }} en favoritos" aria-pressed="false" data-name="{{ $profile['name'] }}"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg></button>
            <div class="profile-info"><a href="{{ $profile['url'] }}"><h3>{{ $profile['name'] }}</h3></a><p><span aria-hidden="true">⌖</span> {{ $profile['location'] }}</p><a class="portfolio-link" href="{{ $profile['url'] }}">Ver portfolio <span aria-hidden="true">↗</span></a></div>
          </article>
        @endforeach
      </div>
      <div class="empty-state" id="empty-state" @if (count($publicProfiles) > 0) hidden @endif>
        <div class="empty-icon" aria-hidden="true">♡</div>
        <h3 id="empty-title">{{ count($publicProfiles) === 0 ? 'Todavía no hay perfiles publicados' : 'No encontramos coincidencias' }}</h3>
        <p id="empty-message">{{ count($publicProfiles) === 0 ? 'Cuando las modelos verifiquen su cuenta y publiquen su portfolio, aparecerán en esta grilla.' : 'Probá otra ciudad o buscá un nombre diferente.' }}</p>
        @if (count($publicProfiles) === 0)
          <a class="primary-button" id="empty-create" href="{{ auth()->check() ? route('account.dashboard') : route('register.show') }}">Ser la primera diva <span aria-hidden="true">↗</span></a>
        @endif
        <button type="button" class="pill" id="reset-filters" @if (count($publicProfiles) === 0) hidden @endif>Limpiar filtros</button>
      </div>

      <p class="storage-note" id="storage-note" role="status" hidden>Los favoritos se conservarán solo mientras esta página esté abierta: el almacenamiento del navegador no está disponible.</p>
    </section>
    <section class="shell join-banner"><div><p class="eyebrow">CONVIERTETE EN DIVA</p><h2>Tu próximo capítulo empieza acá.</h2><p>Compartí tu portfolio. Mostrá lo que te hace única.</p></div><a class="primary-button" href="{{ auth()->check() ? route('account.dashboard') : route('register.show') }}">{{ auth()->check() ? 'Mi cuenta' : 'Sumarme a Divas Cuyo' }} <span aria-hidden="true">↗</span></a></section>
    <section class="sponsor-section" aria-labelledby="sponsor-title">
      <div class="shell sponsor-inner">
        <h2 class="eyebrow" id="sponsor-title">EXPERIENCIAS DE NUESTRAS CHICAS PUBLICADAS</h2>
        <p class="sponsor-heading">SPONSOR AUSPICIANTE</p>
        <figure class="sponsor-preview">
          <a href="https://www.foroescortsar.com/" target="_blank" rel="noopener noreferrer">
            <div class="sponsor-banner">
              <img
                src="{{ asset('images/foro-banner.png') }}"
                alt="Visitar sitio del patrocinador"
                loading="lazy"
                width="500"
                height="680"
              >
            </div>
          </a>
        </figure>
        <div class="sponsor-highlights">
          <div><strong>+ 600 mil</strong><span>Miembros</span></div>
          <div><strong>22</strong><span>Áños online</span></div>
        </div>
      </div>
    </section>
  </main>
  <footer class="shell"><span>© {{ date('Y') }} <strong>DIVAS CUYO</strong> · Comunidad de modelos</span><a href="{{ route('terms.show') }}">Términos y condiciones ↗</a></footer>
</body>
</html>
