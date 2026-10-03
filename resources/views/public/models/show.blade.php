<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $publicProfile->title }}</title>
    <meta name="description" content="{{ $publicProfile->description }}">
    @vite('resources/css/styles.css')
    @include('components.front-header-styles')
</head>
<body class="account-page public-model-page">
<a class="public-profile-skip-link" href="#contenido-principal">Saltar al contenido principal</a>
<x-front-header />
<div class="account-shell public-profile-shell">
    <main id="contenido-principal" class="public-profile-main" tabindex="-1">
        <section class="account-card public-profile-hero" aria-labelledby="profile-name">
            <div class="account-hero public-profile-hero-copy">
                <x-public-model.verified-badge :label="$publicProfile->verifiedLabel" />
                <h1 id="profile-name">{{ $publicProfile->stageName }}</h1>
            </div>
            <x-public-model.availability :label="$publicProfile->availabilityLabel" />
        </section>
        <div class="public-profile-layout">
            <div class="public-profile-gallery-column">
                <x-public-model.gallery :primary-photo="$publicProfile->primaryPhoto" :photos="$publicProfile->photos" />
            </div>
            <div class="public-profile-details">
                @if ($publicProfile->location !== [])
                    <section class="account-card" aria-labelledby="location-title">
                        <h2 id="location-title">Ubicación</h2>
                        <p class="public-profile-location">{{ implode(' · ', $publicProfile->location) }}</p>
                    </section>
                @endif
                @if ($publicProfile->characteristics !== [])
                    <section class="account-card" aria-labelledby="characteristics-title">
                        <h2 id="characteristics-title">Características</h2>
                        <dl class="account-details">
                            @foreach ($publicProfile->characteristics as $label => $value)
                                <div>
                                    <dt>{{ $label }}</dt>
                                    <dd>{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif
                @if ($publicProfile->publicationTypeLabel !== null)
                    <section class="account-card" aria-labelledby="publication-type-title">
                        <h2 id="publication-type-title">Modalidad</h2>
                        <p>{{ $publicProfile->publicationTypeLabel }}</p>
                    </section>
                @endif
                @if ($publicProfile->serviceGroups !== [])
                    <section class="account-card public-profile-services" aria-labelledby="public-services-title">
                        <h2 id="public-services-title">Servicios</h2>
                        <dl class="account-details">
                            @foreach ($publicProfile->serviceGroups as $group => $services)
                                <div>
                                    <dt>{{ $group }}</dt>
                                    <dd>
                                        <ul>
                                            @foreach ($services as $service)
                                                <li>{{ $service }}</li>
                                            @endforeach
                                        </ul>
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif
                @if ($publicProfile->bio !== null)
                    <section class="account-card" aria-labelledby="bio-title">
                        <h2 id="bio-title">Sobre mí</h2>
                        <p class="profile-bio-preview">{!! nl2br(e($publicProfile->bio)) !!}</p>
                    </section>
                @endif
            </div>
        </div>
    </main>
</div>
</body>
</html>
