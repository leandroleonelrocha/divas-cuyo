<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validá tu identidad · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="account-page account-dashboard-page">
<div class="account-app-shell">
    @include('account.partials.sidebar', ['active' => 'profile'])
    <main class="account-main" id="contenido-principal">
        <section class="dashboard-welcome">
            <div>
                <p class="account-eyebrow">Mi perfil</p>
                <h1>Hola, {{ $profile->name }}</h1>
                <p>Tu acceso al panel está habilitado. El siguiente paso es validar tu identidad.</p>
            </div>
        </section>
        <section class="account-card" aria-labelledby="identity-step-title">
            @if ($profile->identity_status === 'pending')
                <h2 id="identity-step-title">Tu identidad está en revisión</h2>
                <p role="status">Recibimos tu documentación. No necesitás hacer nada más por ahora.</p>
                <p>Cuando la administración apruebe tu identidad, se habilitarán las opciones para completar tu publicación.</p>
                <a class="account-secondary-action" href="{{ route('identity.show', $user) }}">Ver documentación enviada</a>
            @elseif ($profile->identity_status === 'rejected')
                <h2 id="identity-step-title">Corregí tu documentación</h2>
                <p>Revisá el motivo indicado por la administración y volvé a enviar tu documentación.</p>
                @if ($profile->identity_rejection_reason)
                    <x-notify type="warning" title="Motivo de la corrección">
                        <p>{{ $profile->identity_rejection_reason }}</p>
                    </x-notify>
                @endif
                <a class="account-primary-action" href="{{ route('identity.show', $user) }}">Corregir documentación</a>
            @else
                <h2 id="identity-step-title">Validá tu identidad</h2>
                <p>Completá la documentación solicitada para verificar tu identidad y mayoría de edad.</p>
                <p>Por ahora, solo necesitás completar este paso y enviarlo a revisión.</p>
                <a class="account-primary-action" href="{{ route('identity.show', $user) }}">Completar verificación</a>
            @endif
        </section>
    </main>
</div>
</body>
</html>
