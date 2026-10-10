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
    <script src="{{ asset('js/model-profile.js') }}" defer></script>
</head>
<body class="model-home model-profile-page">
<a class="public-profile-skip-link" href="#contenido-principal">Saltar al contenido principal</a>
<x-front-header />
<div class="shell public-profile-shell">
    <main id="contenido-principal" class="public-profile-main" tabindex="-1">
        @php
            $similar = $similarProfiles ?? [];
            $isAvailable = $publicProfile->availabilityLabel === 'Disponible';
            $tagServices = [];
            foreach ($publicProfile->serviceGroups as $groupServices) {
                foreach ($groupServices as $serviceName) {
                    if (count($tagServices) >= 4) break 2;
                    $tagServices[] = $serviceName;
                }
            }
            $facts = array_slice($publicProfile->characteristics, 0, 4, true);
            $experienceNames = $tagServices;
        @endphp
        <nav class="profile-breadcrumb" aria-label="Ruta de navegación">
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

        <div class="dv-hero">
            <div class="dv-hero-photo">
                <x-public-model.gallery
                    :primary-photo="$publicProfile->primaryPhoto"
                    :photos="$publicProfile->photos"
                    :online="$isAvailable"
                    :stage-name="$publicProfile->stageName" />
            </div>

            <div class="dv-hero-info">
                <x-public-model.verified-badge :label="$publicProfile->verifiedLabel" />
                <h1 id="profile-name">{{ $publicProfile->stageName }}</h1>
                <p class="dv-status-row">
                    <span class="dv-status {{ $isAvailable ? 'is-on' : 'is-off' }}">
                        <span class="dv-dot" aria-hidden="true"></span>{{ $publicProfile->availabilityLabel }}
                    </span>
                    @if ($publicProfile->location !== [])
                        <span class="dv-loc">⌖ {{ implode(' · ', $publicProfile->location) }}</span>
                    @endif
                </p>
                @if ($publicProfile->publicationTypeLabel !== null || $tagServices !== [])
                    <ul class="dv-tags" aria-label="Tipos de experiencia">
                        @if ($publicProfile->publicationTypeLabel !== null)
                            <li class="dv-tag dv-tag-hot">{{ $publicProfile->publicationTypeLabel }}</li>
                        @endif
                        @foreach ($tagServices as $tag)
                            <li class="dv-tag">{{ $tag }}</li>
                        @endforeach
                    </ul>
                @endif
                @if ($publicProfile->bio !== null)
                    <p class="dv-lead">{{ \Illuminate\Support\Str::limit($publicProfile->bio, 220) }}</p>
                @endif
                @if ($facts !== [])
                    <dl class="dv-quickfacts" aria-label="Datos principales">
                        @foreach ($facts as $label => $value)
                            <div>
                                <dt>{{ $label }}</dt>
                                <dd>{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
                <div class="dv-cta-row">
                    <a class="dv-btn dv-btn-primary" href="#gallery-title">Ver galería</a>
                    <button class="dv-btn dv-btn-ghost" type="button" disabled title="Próximamente">Enviar mensaje</button>
                    <button class="dv-btn dv-btn-ghost" type="button" disabled title="Próximamente">Agendar videollamada</button>
                    <button class="dv-fav" type="button" data-favorite-name="{{ $publicProfile->stageName }}" aria-pressed="false" aria-label="Guardar a {{ $publicProfile->stageName }} en favoritas">♥</button>
                </div>
            </div>

            <aside class="dv-aside" aria-label="Resumen del perfil">
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
            </aside>
        </div>

        <div class="dv-columns">
            @if ($publicProfile->bio !== null)
                <section class="dv-card" id="dv-sobre-mi" aria-labelledby="bio-title">
                    <h2 id="bio-title">Sobre mí</h2>
                    <p class="profile-bio-preview">{!! nl2br(e($publicProfile->bio)) !!}</p>
                </section>
            @endif
            @if ($publicProfile->characteristics !== [])
                <section class="dv-card" aria-labelledby="characteristics-title">
                    <h2 id="characteristics-title">Características</h2>
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
            @if ($publicProfile->serviceGroups !== [])
                <section class="dv-card" id="dv-servicios" aria-labelledby="services-title">
                    <h2 id="services-title">Servicios y detalles</h2>
                    <dl class="dv-servicerows">
                        @if ($publicProfile->publicationTypeLabel !== null)
                            <div>
                                <dt>Tipo de publicación</dt>
                                <dd>{{ $publicProfile->publicationTypeLabel }}</dd>
                            </div>
                        @endif
                        @foreach ($publicProfile->serviceGroups as $group => $services)
                            <div>
                                <dt>{{ $group }}</dt>
                                <dd>{{ implode(', ', $services) }}</dd>
                            </div>
                        @endforeach
                        @if ($publicProfile->location !== [])
                            <div>
                                <dt>Zona de atención</dt>
                                <dd>{{ implode(' · ', $publicProfile->location) }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>
            @endif
        </div>

        @if ($similar !== [])
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
        @endif
    </main>
</div>
<footer class="shell"><span>© {{ date('Y') }} <strong>DIVAS CUYO</strong> · Comunidad de modelos</span><a href="{{ route('terms.show') }}">Términos y condiciones ↗</a></footer>
</body>
</html>
