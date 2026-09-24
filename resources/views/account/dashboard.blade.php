<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi cuenta · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="account-page">
@php
    $identityState = match ($profile->identity_status) {
        'pending' => ['label' => 'En revisión', 'description' => 'Estamos revisando tu documentación.', 'class' => 'pending', 'action' => 'Consultar verificación'],
        'approved' => ['label' => 'Validada', 'description' => 'Tu identidad fue validada correctamente.', 'class' => 'approved', 'action' => 'Ver verificación'],
        'rejected' => ['label' => 'Requiere corrección', 'description' => 'Revisá el motivo y corregí la documentación.', 'class' => 'rejected', 'action' => 'Corregir documentación'],
        default => ['label' => 'Incompleta', 'description' => 'Completá la documentación para validar tu identidad.', 'class' => 'incomplete', 'action' => 'Completar verificación'],
    };
    $reviewState = match ($profile->review_status) {
        'approved' => ['label' => 'Aprobado', 'description' => 'Tu perfil fue aprobado.', 'class' => 'approved'],
        'rejected' => ['label' => 'Requiere atención', 'description' => 'Revisá las indicaciones de tu perfil.', 'class' => 'rejected'],
        default => ['label' => 'Pendiente', 'description' => 'Tu perfil todavía está pendiente de revisión.', 'class' => 'pending'],
    };
@endphp
<main class="account-shell" id="contenido-principal">
    <header class="account-header" aria-label="Navegación de mi cuenta">
        <a href="{{ route('account.dashboard') }}" class="account-brand" aria-label="Divas Cuyo, Mi cuenta">
            <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo">
            <span>DIVAS CUYO</span>
        </a>
        <nav class="account-nav" aria-label="Navegación privada">
            <a href="{{ route('account.dashboard') }}" aria-current="page">Mi cuenta</a>
            <a href="{{ route('account.photos.index') }}">Mis fotos</a>
            <span class="account-profile-name">{{ $profile->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="account-logout">Cerrar sesión</button>
            </form>
        </nav>
    </header>

    <section class="account-hero" aria-labelledby="account-title">
        <p class="account-eyebrow">Panel privado</p>
        <h1 id="account-title">Hola, {{ $profile->name }}</h1>
        <p>Este es el resumen de tu perfil y del estado de cada validación.</p>
    </section>

    <section class="account-card" aria-labelledby="status-title">
        <div class="account-section-heading">
            <div>
                <p class="account-kicker">Resumen</p>
                <h2 id="status-title">Estados de tu perfil</h2>
            </div>
            <span class="account-heading-note">Actualizado con tu última información</span>
        </div>
        <div class="account-status-grid" aria-live="polite">
            <article class="account-status-card">
                <div class="account-status-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" focusable="false"><path d="M3.5 5h17A1.5 1.5 0 0 1 22 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-17A1.5 1.5 0 0 1 2 17.5v-11A1.5 1.5 0 0 1 3.5 5Zm.5 3v9h16V8l-8 5-8-5Zm1.8-1L12 10.7 18.2 7H5.8Z"/></svg>
                </div>
                <div class="account-status-content">
                    <h3>Email</h3>
                    <strong class="account-badge account-badge-success">Verificado</strong>
                    <p>Tu correo está confirmado.</p>
                </div>
            </article>
            <article class="account-status-card">
                <div class="account-status-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" focusable="false"><path d="M12 3 5 6v5c0 4.4 2.9 8.5 7 10 4.1-1.5 7-5.6 7-10V6l-7-3Zm0 4 3 1.3v2.2c0 2.5-1.3 4.9-3 6-1.7-1.1-3-3.5-3-6V8.3L12 7Z"/></svg>
                </div>
                <div class="account-status-content">
                    <h3>Identidad</h3>
                    <strong class="account-badge account-badge-{{ $identityState['class'] }}">{{ $identityState['label'] }}</strong>
                    <p>{{ $identityState['description'] }}</p>
                </div>
            </article>
            <article class="account-status-card">
                <div class="account-status-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" focusable="false"><path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h13A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5v-13ZM7 8h10V6H7v2Zm0 4h10v-2H7v2Zm0 4h6v-2H7v2Z"/></svg>
                </div>
                <div class="account-status-content">
                    <h3>Revisión del perfil</h3>
                    <strong class="account-badge account-badge-{{ $reviewState['class'] }}">{{ $reviewState['label'] }}</strong>
                    <p>{{ $reviewState['description'] }}</p>
                </div>
            </article>
            <article class="account-status-card">
                <div class="account-status-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" focusable="false"><path d="M12 3a9 9 0 1 0 9 9 9 9 0 0 0-9-9Zm0 2a7 7 0 1 1-7 7 7 7 0 0 1 7-7Zm-1 2v5.4l4 2.3 1-1.7-3-1.7V7h-2Z"/></svg>
                </div>
                <div class="account-status-content">
                    <h3>Publicación</h3>
                    <strong class="account-badge {{ $profile->is_published ? 'account-badge-success' : 'account-badge-muted' }}">{{ $profile->is_published ? 'Publicado' : 'No publicado' }}</strong>
                    <p>{{ $profile->is_published ? 'Tu perfil es visible públicamente.' : 'Tu perfil todavía no es visible.' }}</p>
                </div>
            </article>
        </div>
    </section>

    <div class="account-content-grid">
        <section class="account-card" aria-labelledby="profile-data-title">
            <div class="account-section-heading">
                <div>
                    <p class="account-kicker">Tu información</p>
                    <h2 id="profile-data-title">Datos del perfil</h2>
                </div>
            </div>
            <dl class="account-details">
                <div><dt>Nombre público</dt><dd>{{ $profile->name }}</dd></div>
                <div><dt>Correo electrónico</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>WhatsApp</dt><dd>{{ $profile->whatsapp }}</dd></div>
                <div><dt>Ubicación</dt><dd>{{ $profile->location }}</dd></div>
            </dl>
        </section>

        <section class="account-card account-actions" aria-labelledby="actions-title">
            <div class="account-section-heading">
                <div>
                    <p class="account-kicker">Accesos rápidos</p>
                    <h2 id="actions-title">¿Qué querés hacer?</h2>
                </div>
            </div>
            <a class="account-primary-action" href="{{ route('identity.show', $user) }}">{{ $identityState['action'] }} <span aria-hidden="true">→</span></a>
            @if ($profile->identity_status === 'rejected' && $profile->identity_rejection_reason)
                <p class="account-rejection" role="alert"><strong>Motivo de rechazo:</strong> {{ $profile->identity_rejection_reason }}</p>
            @endif
        </section>
    </div>
</main>
</body>
</html>
