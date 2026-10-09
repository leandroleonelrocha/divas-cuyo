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
        <nav class="profile-breadcrumb" aria-label="Ruta de navegación"><a href="{{ route('home') }}#modelos">← Volver a modelos</a><span aria-hidden="true">/</span><span>{{ $publicProfile->stageName }}</span></nav>
        <section class="account-card public-profile-hero" aria-labelledby="profile-name">
            <div class="account-hero public-profile-hero-copy">
                <p class="eyebrow">DIVAS CUYO / PORTFOLIO</p>
                <x-public-model.verified-badge :label="$publicProfile->verifiedLabel" />
                <h1 id="profile-name">{{ $publicProfile->stageName }}</h1>
            </div>
            <a class="profile-gallery-link" href="#gallery-title">Ver fotografías <span aria-hidden="true">↘</span></a>
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
<footer class="shell"><span>© {{ date('Y') }} <strong>DIVAS CUYO</strong> · Comunidad de modelos</span><a href="{{ route('terms.show') }}">Términos y condiciones ↗</a></footer>
</body>
</html>
