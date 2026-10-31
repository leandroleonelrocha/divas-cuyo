<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi perfil · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="account-mobile-navigation account-page account-dashboard-page dashboard-mobile-compact">
@php
    $dashboardVisible = app(\App\Services\PublicModelProfileVisibility::class)->publiclyVisible($profile);
    $preparePublication = $profile->identity_status === 'approved' && $profile->review_status === 'approved';
    $dataComplete = filled($profile->stage_name) && filled($profile->slug)
        && filled($profile->whatsapp) && filled($profile->publication_type_id);
    $identitySubmitted = in_array($profile->identity_status, ['pending', 'approved'], true);
    $completedCount = (int) $dataComplete + (int) $identitySubmitted;
    $firstPendingUrl = $profile->identity_status === 'rejected' ? route('identity.show', $user)
        : (! $dataComplete ? route('account.profile.edit') : (! $identitySubmitted ? route('identity.show', $user) : null));
    $identity = match ($profile->identity_status) {
        'approved' => ['label' => 'Aprobada', 'class' => 'success', 'action' => 'Ver documentación'],
        'pending' => ['label' => 'En revisión', 'class' => 'pending', 'action' => 'Ver documentación'],
        'rejected' => ['label' => 'Requiere corrección', 'class' => 'rejected', 'action' => 'Corregir documentación'],
        default => ['label' => 'Pendiente', 'class' => 'muted', 'action' => 'Completar documentación'],
    };
    $publicationMessage = null;
    if ($completedCount === 2) {
        $publicationMessage = match (true) {
            $profile->identity_status === 'pending' => 'Tu identidad está en revisión. Recibimos tu documentación; no necesitás volver a enviarla.',
            $profile->review_status === 'rejected' => 'Tu perfil requiere correcciones. Revisá tus datos y las indicaciones de la administración.',
            $profile->review_status !== 'approved' => 'Tu perfil está en revisión. La administración debe aprobarlo antes de su publicación.',
            app(\App\Services\PublicModelProfileVisibility::class)->publiclyVisible($profile) => 'Tu perfil está publicado y visible en el sitio.',
            default => 'Tu perfil está aprobado, pero todavía no está visible en el sitio. La publicación depende de los requisitos restantes.',
        };
    }
@endphp
<div class="account-app-shell">
    @include('account.partials.sidebar', ['active' => 'profile'])
    <main class="account-main" id="contenido-principal">
        @include('account.partials.topbar', ['label' => $profile->name, 'compactMobile' => true])
        @if (! ($managementReady && request('section') === 'payments'))
        <x-account.heading :name="$profile->name" :visible="$dashboardVisible" :show-status="! ($managementReady && $dashboardVisible && request('section') !== 'payments')" />
        @endif
        @if (request('section') !== 'payments' && ! $managementReady)
            @include('account.partials.publication-progress')
        @endif
        @if ($managementReady)
            @if (request('section') === 'payments')
                @include('account.partials.payments')
            @else
                @include('account.partials.management-dashboard')
            @endif
        @elseif ($preparePublication)
            @include('account.partials.publication-setup')
        @else
        <x-account.stage title="Completá tu perfil" id="setup-title">
            <p class="profile-setup-note">{{ $completedCount }} de 2 pasos completados o enviados.</p>
            <ol class="profile-setup-steps">
                <x-account.task title="Mis datos" description="Completá tu nombre público y la información de tu perfil."
                    :status="$dataComplete ? 'Completados' : 'Pendientes'" :color="$dataComplete ? 'success' : 'muted'"
                    :href="route('account.profile.edit')" :action="$dataComplete ? 'Editar datos' : 'Completar datos'"
                    number="1" :attention="$firstPendingUrl === route('account.profile.edit')" />
                <x-account.task title="Mi identidad" description="Enviá la documentación para validar tu identidad y mayoría de edad."
                    :status="$identity['label']" :color="$identity['class']" :href="route('identity.show', $user)"
                    :action="$identity['action']" number="2" :attention="$firstPendingUrl === route('identity.show', $user)"
                    :reason="$profile->identity_status === 'rejected' ? $profile->identity_rejection_reason : null" />
            </ol>
            <div class="profile-setup-footer">
                @if ($firstPendingUrl)
                    <x-account.primary-action :href="$firstPendingUrl">{{ $profile->identity_status === 'rejected' ? 'Corregir mi documentación' : 'Comenzar a completar mi perfil' }}</x-account.primary-action>
                @else
                    <x-account.notice>{{ $publicationMessage }}</x-account.notice>
                @endif
                <p class="profile-setup-note">Completar estos pasos no publica tu perfil automáticamente.</p>
            </div>
        </x-account.stage>
        @endif
        <footer class="profile-setup-support">
            <span>¿Necesitás ayuda?</span> <a href="{{ route('terms.show') }}">Contactar soporte</a>
        </footer>
    </main>
</div>

</body>
</html>
