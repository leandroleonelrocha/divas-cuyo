@php
    $paymentState = app(\App\Services\PublicationPaymentStatus::class)->summary($profile);
    $publicVisible = app(\App\Services\PublicModelProfileVisibility::class)->publiclyVisible($profile);
    $paymentsUrl = route('account.dashboard', ['section' => 'payments']);
    $paymentCurrent = in_array($paymentState['status'], ['current', 'near_expiry'], true);
    $action = null;
    if ($publicVisible) {
        $title = 'Tu perfil está publicado';
        $message = 'Tu perfil ya puede verse en Divas Cuyo.';
        $action = ['label' => 'Ver mi perfil público', 'url' => route('public.models.show', ['slug' => $profile->slug])];
    } elseif (in_array($paymentState['status'], ['unpaid', 'expired'], true)) {
        $title = $paymentState['status'] === 'expired' ? 'Tu publicación está vencida' : 'Tu perfil está aprobado';
        $message = $paymentState['status'] === 'expired'
            ? 'El período abonado terminó. Consultá los datos para renovar tu publicación.'
            : 'Todavía no hay un pago registrado. Consultá las instrucciones para continuar.';
        $action = ['label' => $paymentState['status'] === 'expired' ? 'Ver cómo renovar' : 'Ver instrucciones de pago', 'url' => $paymentsUrl];
    } elseif ($paymentState['status'] === 'scheduled') {
        $title = 'Tu publicación tiene un pago programado';
        $message = 'El período abonado todavía no comenzó. Podés consultar sus fechas en Mis pagos.';
        $action = ['label' => 'Ver mis pagos', 'url' => $paymentsUrl];
    } elseif (! $profile->is_published) {
        $title = 'Tu publicación no está habilitada';
        $message = 'Tu perfil está aprobado y el pago está vigente. Falta que la administración habilite la publicación.';
    } else {
        $title = 'Tu perfil todavía no está visible';
        $message = 'La publicación está habilitada y el pago está vigente, pero hay información o archivos del perfil que necesitan revisión.';
        $action = ['label' => 'Revisar mi información', 'url' => route('account.show', $user)];
    }
    $pendingPhotos = $profile->photos()->whereHas('latestVersion', fn ($q) => $q->where('status', 'pending'))->count();
    $pendingBios = $profile->bios()->where('status', 'pending')->count();
    $shortcuts = [
        ['title' => 'Mis fotos', 'icon' => 'heroicon-o-photo', 'url' => route('account.photos.index'), 'description' => 'Administrar fotografías'],
        ['title' => 'Mis videos', 'icon' => 'heroicon-o-play-circle', 'url' => route('account.videos.index'), 'description' => 'Administrar videos'],
        ['title' => 'Mis datos', 'icon' => 'heroicon-o-user', 'url' => route('account.profile.edit'), 'description' => 'Editar mi información'],
        ['title' => 'Mis servicios', 'icon' => 'heroicon-o-squares-2x2', 'url' => route('account.services.edit'), 'description' => 'Servicios y modalidad'],
        ['title' => 'Mi identidad', 'icon' => 'heroicon-o-identification', 'url' => route('identity.show', $user), 'description' => 'Consultar documentación'],
        ['title' => 'Mis pagos', 'icon' => 'heroicon-o-credit-card', 'url' => $paymentsUrl, 'description' => 'Instrucciones e historial'],
    ];
@endphp
@if ($publicVisible)
    <section class="dashboard-panel published-summary" aria-labelledby="published-title">
        <h2 id="published-title"><span aria-hidden="true">✓</span> Tu perfil está publicado</h2>
        <p>Administrá tu información y mantené tu publicación al día.</p>
        <div class="published-validity {{ $paymentState['status'] === 'near_expiry' ? 'is-expiring' : '' }}">
            @if ($paymentCurrent)
                <strong>{{ $paymentState['status'] === 'near_expiry' ? 'Próxima a vencer' : 'Vigencia activa' }}</strong>
                <span>Vence el {{ $paymentState['expires_at']->format('d/m/Y') }}</span>
            @else
                <span>No hay un período de pago vigente registrado.</span>
            @endif
        </div>
        <x-account.primary-action :href="$action['url']">{{ $action['label'] }}</x-account.primary-action>
        @if ($paymentState['status'] === 'near_expiry')
            <p class="profile-setup-note">Para renovar, consultá las instrucciones en <a href="{{ $paymentsUrl }}">Mis pagos</a>. La renovación no es automática.</p>
        @endif
        @if ($pendingPhotos || $pendingBios)
            <p class="profile-setup-note">Cambios en revisión: {{ $pendingPhotos }} fotos y {{ $pendingBios }} biografías. Tu contenido aprobado se conserva.</p>
        @endif
    </section>
@else
<x-account.stage :title="$publicVisible ? 'Tu perfil está publicado' : 'Tu perfil todavía no está publicado'" id="management-title" class="management-status reference-status">
    <div class="reference-next-step {{ $publicVisible ? 'is-published' : '' }}">
        <h3>{{ $title }}</h3>
        <p>{{ $message }}</p>
        @if ($action)<x-account.primary-action :href="$action['url']">{{ $action['label'] }}</x-account.primary-action>@endif
    </div>
    <p class="reference-approved"><span aria-hidden="true">✓</span> Identidad y contenido aprobados</p>
    @if ($paymentState['expires_at'])<p class="profile-setup-note">Fin del período: {{ $paymentState['expires_at']->format('d/m/Y') }}.</p>@endif
    @if ($pendingPhotos || $pendingBios)<p class="profile-setup-note">Cambios en revisión: {{ $pendingPhotos }} fotos y {{ $pendingBios }} biografías. Tu contenido aprobado se conserva.</p>@endif
</x-account.stage>
@include('account.partials.publication-progress')
@endif
<h2 class="management-shortcuts-title">Administrá tu perfil</h2>
<nav class="management-shortcuts {{ $publicVisible ? 'published-shortcuts' : '' }}" aria-label="Accesos rápidos">
    @foreach ($shortcuts as $shortcut)
        <a class="dashboard-panel management-shortcut" href="{{ $shortcut['url'] }}">
            @svg($shortcut['icon'], 'management-icon')
            <strong>{{ $shortcut['title'] }}</strong><span>{{ $shortcut['description'] }}</span><span class="shortcut-arrow" aria-hidden="true">↗</span>
        </a>
    @endforeach
</nav>
<p class="profile-setup-note"><a href="{{ route('account.show', $user) }}">Consultar toda mi información</a></p>
