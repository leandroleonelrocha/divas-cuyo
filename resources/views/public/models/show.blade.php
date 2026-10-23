<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $publicProfile->title }}</title>
    <meta name="description" content="{{ $publicProfile->description }}">
    @include('components.front-header-styles')
    <meta name="theme-color" content="#0d1013">
    <link rel="stylesheet" href="{{ asset('css/model-home.css') }}?v={{ filemtime(public_path('css/model-home.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/model-profile.css') }}?v={{ filemtime(public_path('css/model-profile.css')) }}">
    <script src="{{ asset('js/model-profile.js') }}?v={{ filemtime(public_path('js/model-profile.js')) }}" defer></script>
    <noscript><style>.artist-panel[hidden] { display: grid; }</style></noscript>
    <script src="{{ asset('js/legal-access-navigation.js') }}" defer></script>
</head>
<body class="model-home model-profile-page">
<a class="public-profile-skip-link" href="#contenido-principal">Saltar al contenido principal</a>
<x-front-header />
<div class="shell public-profile-shell">
    <main id="contenido-principal" class="public-profile-main" tabindex="-1">
        @php
            $similar = $similarProfiles ?? [];
            $isAvailable = $publicProfile->availabilityLabel === 'Disponible';
            $galleryPhotos = array_values(array_filter(
                $publicProfile->photos,
                fn (array $photo): bool => $photo['url'] !== $publicProfile->primaryPhoto['url']
            ));
        @endphp
        <nav class="profile-breadcrumb artist-topbar" aria-label="Ruta de navegación">
            <span class="dv-crumb-left">
                <a href="{{ route('home') }}#modelos">← Volver</a>
                @if ($publicProfile->location !== [])
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('home') }}#modelos">{{ $publicProfile->location[0] }}</a>
                    <span aria-hidden="true">/</span>
                @endif
                <span aria-current="page" class="dv-crumb-current">{{ $publicProfile->stageName }}</span>
            </span>
            @if (count($similar) >= 2)
                <span class="dv-crumb-nav">
                    <a href="{{ $similar[0]['url'] }}">‹ Perfil anterior</a>
                    <span aria-hidden="true">|</span>
                    <a href="{{ $similar[1]['url'] }}">Perfil siguiente ›</a>
                </span>
            @endif
        </nav>

        <div class="dv-hero artist-hero">
            <div class="dv-hero-photo">
                <x-public-model.gallery
                    :primary-photo="$publicProfile->primaryPhoto"
                    :photos="$publicProfile->photos"
                    :online="$isAvailable"
                    :stage-name="$publicProfile->stageName" />
            </div>

            <div class="dv-hero-info">
                <div class="artist-title-line">
                    <h1 id="profile-name">{{ $publicProfile->stageName }}</h1>
                    <x-public-model.verified-badge :label="$publicProfile->verifiedLabel" />
                    @if ($publicProfile->whatsappUrl !== null)
                        <a class="dv-wa" href="{{ $publicProfile->whatsappUrl }}" target="_blank" rel="noopener" aria-label="Hablar con {{ $publicProfile->stageName }} por WhatsApp"><svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg></a>
                    @endif
                </div>
                <p class="dv-status-row">
                    <span class="dv-status {{ $isAvailable ? 'is-on' : 'is-off' }}">
                        <span class="dv-dot" aria-hidden="true"></span>{{ $publicProfile->availabilityLabel }}
                    </span>
                    @if ($publicProfile->location !== [])
                        <span class="dv-loc">⌖ {{ implode(' · ', $publicProfile->location) }}</span>
                    @endif
                </p>
                <div class="dv-cta-row artist-actions" role="tablist" aria-label="Contenido del perfil">
                    <button class="dv-btn dv-btn-primary artist-tab-btn is-active" type="button" role="tab" id="tab-galeria" aria-selected="true" aria-controls="panel-galeria" data-panel-btn="galeria">▧ &nbsp; Galería</button>
                    {{-- <button class="dv-btn dv-btn-ghost" type="button" disabled title="Próximamente">◯ &nbsp; Comentarios</button> --}}
                    <button class="dv-btn dv-btn-ghost artist-tab-btn" type="button" role="tab" id="tab-info" aria-selected="false" aria-controls="panel-info" data-panel-btn="info">ⓘ &nbsp; Info</button>
                    {{-- <button class="dv-fav" type="button" data-favorite-name="{{ $publicProfile->stageName }}" aria-pressed="false" aria-label="Guardar a {{ $publicProfile->stageName }} en favoritas">♡</button> --}}
                </div>
                @if ($publicProfile->availabilityLabel !== null || $publicProfile->rates !== [])
                    <div class="artist-quick-info" aria-label="Información rápida">
                        <div class="artist-quick-items">
                            @if ($publicProfile->scheduleLabel !== null || $publicProfile->availabilityLabel !== null)
                                <div class="artist-quick-item"><span class="artist-quick-icon" aria-hidden="true">◷</span><span><strong>Horario</strong><small>{{ $publicProfile->scheduleLabel ?? 'Full time' }}</small></span></div>
                            @endif
                            <div class="artist-quick-item"><span class="artist-quick-icon" aria-hidden="true">▣</span><span><strong>Tarifa</strong><small>{{ $publicProfile->rates !== [] ? implode(' · ', array_map(fn (array $rate): string => $rate['label'].' · '.$rate['price'], $publicProfile->rates)) : 'Consultar' }}</small></span></div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- <aside class="dv-aside" aria-label="Resumen del perfil">
                <div class="dv-aside-status {{ $isAvailable ? 'is-on' : 'is-off' }}">
                    <span class="dv-aside-dot {{ $isAvailable ? 'is-on' : 'is-off' }}" aria-hidden="true"></span>
                    <div>
                        <strong>{{ $isAvailable ? 'Disponible ahora' : 'No disponible' }}</strong>
                        <span>{{ $isAvailable ? 'Perfil activo en la plataforma' : 'Por el momento no recibe consultas' }}</span>
                    </div>
                </div>
                @if ($publicProfile->location !== [])
                    <a class="dv-aside-row" href="#gallery-title">
                        <span class="dv-aside-ico" aria-hidden="true">⌖</span>
                        <span><strong>Zona</strong><span>{{ implode(', ', $publicProfile->location) }}</span></span>
                        <span aria-hidden="true">›</span>
                    </a>
                @endif
                @if ($publicProfile->publicationTypeLabel !== null || $experienceNames !== [])
                    <a class="dv-aside-row" href="{{ $publicProfile->serviceGroups !== [] ? '#dv-servicios' : '#gallery-title' }}">
                        <span class="dv-aside-ico" aria-hidden="true">★</span>
                        <span><strong>Experiencias</strong><span>{{ implode(', ', array_filter([$publicProfile->publicationTypeLabel, ...array_slice($experienceNames, 0, 3)])) }}</span></span>
                        <span aria-hidden="true">›</span>
                    </a>
                @endif
                <a class="dv-aside-row" href="{{ $publicProfile->bio !== null ? '#dv-sobre-mi' : '#gallery-title' }}">
                    <span class="dv-aside-ico" aria-hidden="true">✓</span>
                    <span><strong>Verificada</strong><span>{{ $publicProfile->verifiedLabel }}</span></span>
                    <span aria-hidden="true">›</span>
                </a>
            </aside> --}}
        </div>

        {{-- <div class="artist-profile-tabs" aria-hidden="true">
            <span class="is-active">Galería</span><span>Comentarios</span><span>Info</span>
        </div> --}}
        
        <section class="artist-panel" id="panel-galeria" role="tabpanel" aria-labelledby="tab-galeria" data-panel="galeria">
            <h2 class="artist-panel-title" id="all-photos-title">Todas las fotos de {{ $publicProfile->stageName }} ({{ count($galleryPhotos) }})</h2>
            <ul class="photos-all-grid" aria-label="Todas las fotografías de {{ $publicProfile->stageName }}">
                @foreach ($galleryPhotos as $photo)
                    <li class="photo-all-card">
                        <a class="photo-preview profile-photo-all" href="{{ $photo['url'] }}" target="_blank" rel="noopener" aria-label="Ampliar fotografía {{ $loop->iteration }} de {{ $publicProfile->stageName }}">
                            <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}"
                                 width="{{ $photo['width'] }}" height="{{ $photo['height'] }}"
                                 loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        @if ($publicProfile->videos !== [])
            <section class="dv-videos" aria-labelledby="videos-title">
                <h2 id="videos-title">Videos de {{ $publicProfile->stageName }}</h2>
                <div class="dv-video-grid">
                    @foreach ($publicProfile->videos as $video)
                        <video controls playsinline preload="none" poster="{{ $video['poster'] }}" aria-label="Video {{ $loop->iteration }} de {{ $publicProfile->stageName }}">
                            <source src="{{ $video['url'] }}" type="video/mp4">
                            Tu navegador no permite reproducir este video.
                        </video>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="artist-info-grid artist-panel" id="panel-info" role="tabpanel" aria-labelledby="tab-info" data-panel="info" hidden>
            @if ($publicProfile->bio !== null || $publicProfile->serviceGroups !== [] || $publicProfile->characteristics !== [])
                <div class="artist-info-main">
                    @if ($publicProfile->bio !== null)
                        <section class="dv-card" id="dv-sobre-mi" aria-labelledby="bio-title">
                            <h2 id="bio-title"><span class="dv-card-ico" aria-hidden="true">✎</span> Sobre mí</h2>
                            <p class="profile-bio-preview">{!! nl2br(e($publicProfile->bio)) !!}</p>
                        </section>
                    @endif
                    @if ($publicProfile->serviceGroups !== [])
                        <section class="dv-card" id="dv-servicios" aria-labelledby="services-title">
                            <h2 id="services-title"><span class="dv-card-ico" aria-hidden="true">✧</span> Mis servicios</h2>
                            @if ($publicProfile->publicationTypeLabel !== null)
                                <p class="dv-modality"><span class="dv-modality-badge">★ {{ $publicProfile->publicationTypeLabel }}</span></p>
                            @endif
                            <dl class="dv-servicerows">
                                @foreach ($publicProfile->serviceGroups as $group => $services)
                                    <div>
                                        <dt>{{ $group }}</dt>
                                        <dd>
                                            @foreach ($services as $service)
                                                <span class="dv-service-badge">✧ {{ $service }}</span>
                                            @endforeach
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                            @if ($publicProfile->scheduleLabel !== null || $publicProfile->rates !== [])
                                <div class="dv-service-meta">
                                    @if ($publicProfile->scheduleLabel !== null)
                                        <span class="artist-pill">◷ &nbsp;{{ $publicProfile->scheduleLabel }}</span>
                                    @endif
                                    @foreach ($publicProfile->rates as $rate)
                                        <span class="artist-pill pill-gold">$ &nbsp;{{ $rate['label'] }} · {{ $rate['price'] }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endif
                    @if ($publicProfile->characteristics !== [])
                        <section class="dv-card" aria-labelledby="characteristics-title">
                            <h2 id="characteristics-title"><span class="dv-card-ico" aria-hidden="true">✦</span> Características</h2>
                            <dl class="dv-feats">
                                @foreach ($publicProfile->characteristics as $label => $value)
                                    <div class="dv-feat">
                                        <dt>{{ $label }}</dt>
                                        <dd>{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endif
                </div>
            @endif
            @if ($publicProfile->location !== [])
                <aside class="dv-card artist-map-card" aria-labelledby="map-title">
                    <h2 id="map-title"><span class="dv-card-ico" aria-hidden="true">⌖</span> Zona de atención</h2>
                    <p class="artist-map-caption">Zona aproximada: {{ implode(' · ', $publicProfile->location) }}</p>
                    <div class="artist-map-embed">
                        <iframe
                            title="Mapa de la zona aproximada: {{ implode(', ', $publicProfile->location) }}"
                            src="https://www.google.com/maps?q={{ urlencode(implode(', ', $publicProfile->location)) }}&output=embed"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen></iframe>
                    </div>
                </aside>
            @endif
        </div>

        {{-- @if ($publicProfile->location !== [])
            <section class="artist-location" aria-labelledby="location-title">
                <div class="artist-location-copy">
                    <h2 id="location-title">⌖ &nbsp;Ubicación</h2>
                    <div class="artist-pills"><span class="artist-pill">⌂ &nbsp; {{ $publicProfile->location[0] }}</span><span class="artist-pill">▣ &nbsp; Eventos</span></div>
                    <p>Me encuentro en {{ implode(', ', $publicProfile->location) }}. Coordiná visitas y encuentros creativos según disponibilidad.</p>
                    <a class="dv-btn dv-btn-ghost" href="{{ route('home') }}#modelos">⌖ &nbsp; Ver en el mapa</a>
                </div>
                <div class="artist-map" role="img" aria-label="Mapa de ubicación aproximada en {{ implode(', ', $publicProfile->location) }}"><span>⌖</span><b>{{ $publicProfile->location[0] }}</b></div>
            </section>
        @endif --}}

        {{-- @if ($similar !== [])
            <section class="dv-similar" aria-labelledby="similar-title">
                <h2 id="similar-title">Perfiles similares en {{ $publicProfile->location[0] ?? 'la región' }}</h2>
                <div class="dv-similar-grid">
                    @foreach ($similar as $card)
                        <article class="dv-similar-card">
                            <a class="dv-similar-photo" href="{{ $card['url'] }}" aria-label="Ver perfil de {{ $card['name'] }}">
                                <img src="{{ $card['photoUrl'] }}" alt="{{ $card['photoAlt'] }}" loading="lazy" width="360" height="480">
                            </a>
                            <button class="dv-fav dv-fav-mini" type="button" data-favorite-name="{{ $card['name'] }}" aria-pressed="false" aria-label="Guardar a {{ $card['name'] }} en favoritas">♡</button>
                            <div class="dv-similar-info">
                                <strong>{{ $card['name'] }}</strong>
                                <span class="dv-similar-status {{ $card['available'] ? 'is-on' : 'is-off' }}">
                                    <span class="dv-dot" aria-hidden="true"></span>{{ $card['available'] ? 'Disponible' : 'No disponible' }}
                                </span>
                                @if ($card['location'] !== '')
                                    <span class="dv-similar-loc">⌖ {{ $card['location'] }}</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif --}}
    </main>
</div>
<footer class="shell"><span>© {{ date('Y') }} <strong>DIVAS CUYO</strong> · Comunidad de modelos</span><a href="{{ route('terms.show') }}">Términos y condiciones ↗</a></footer>
</body>
</html>
