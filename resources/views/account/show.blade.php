<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi perfil · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="account-page">
<main class="account-shell" id="contenido-principal">
    <header class="account-header" aria-label="Navegación de mi perfil">
        <a href="{{ url('/') }}" class="account-brand" aria-label="Divas Cuyo">
            <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo">
            <span>DIVAS CUYO</span>
        </a>
        <nav class="account-nav" aria-label="Navegación privada">
            <a href="{{ route('account.dashboard') }}">Mi cuenta</a>
            <a href="{{ route('account.photos.index') }}">Mis fotos</a>
            <span class="account-profile-name">{{ $user->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="account-logout">Cerrar sesión</button>
            </form>
        </nav>
    </header>

    <section class="account-hero" aria-labelledby="profile-title">
        <p class="account-eyebrow">Perfil de modelo</p>
        <h1 id="profile-title">{{ $user->name }}</h1>
        <p>Gestioná la información de tu perfil y completá las validaciones necesarias.</p>
    </section>

    <section class="account-card" aria-labelledby="profile-data-title">
        <div class="account-section-heading">
            <div>
                <p class="account-kicker">Información personal</p>
                <h2 id="profile-data-title">Datos del perfil</h2>
            </div>
            <span class="account-heading-note">Tu información privada</span>
        </div>

        <dl class="account-details">
            <div><dt>Nombre público</dt><dd>{{ $user->name }}</dd></div>
            <div><dt>Correo electrónico</dt><dd>{{ $user->email }}</dd></div>
            <div><dt>WhatsApp</dt><dd>{{ $user->whatsapp }}</dd></div>
            <div><dt>Provincia</dt><dd>{{ $user->modelProfile?->province?->name ?: $user->location }}</dd></div>
            @if ($user->modelProfile?->publicationType)
                <div><dt>Tipo de publicación</dt><dd>{{ $user->modelProfile->publicationType->name }}</dd></div>
            @endif
            @if ($user->modelProfile?->currentBio)
                <div><dt>Biografía</dt><dd>{{ $user->modelProfile->currentBio->content }}</dd></div>
            @endif
            @if ($user->modelProfile?->locality || $user->modelProfile?->approximate_location_text)
                <div><dt>Ubicación aproximada</dt><dd>{{ collect([$user->modelProfile?->locality?->name, $user->modelProfile?->approximate_location_text])->filter()->join(' · ') }}</dd></div>
            @endif
            <div><dt>Correo verificado</dt><dd>{{ $user->hasVerifiedEmail() ? 'Sí' : 'No' }}</dd></div>
            <div><dt>Perfil publicado</dt><dd>{{ $user->is_published ? 'Sí' : 'No' }}</dd></div>
        </dl>
    </section>

    <section class="account-card account-actions" aria-labelledby="profile-actions-title">
        <div class="account-section-heading">
            <div>
                <p class="account-kicker">Acciones</p>
                <h2 id="profile-actions-title">Gestioná tu perfil</h2>
            </div>
        </div>
        <a class="account-primary-action" href="{{ route('identity.show', $user) }}">Validar mi identidad <span aria-hidden="true">→</span></a>
    </section>
</main>
</body>
</html>
