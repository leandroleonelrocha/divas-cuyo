<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validación de identidad · Divas Cuyo</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/css/styles.css')
    <script src="{{ asset('js/identity-documents.js') }}" defer></script>
</head>
<body class="account-page account-dashboard-page">
@php
    $identityState = match ($profile->identity_status) {
        'pending' => ['label' => 'Pendiente de revisión', 'class' => 'pending'],
        'approved' => ['label' => 'Aprobada', 'class' => 'approved'],
        'rejected' => ['label' => 'Rechazada', 'class' => 'rejected'],
        default => ['label' => 'Incompleta', 'class' => 'incomplete'],
    };
    $labels = [
        'dni_front' => 'DNI frente',
        'dni_back' => 'DNI dorso',
        'selfie' => 'Selfie',
    ];
    $requiredTypes = config('identity-documents.required_types');
    $documents = $profile->documents->keyBy('type');
    $canUpload = in_array($profile->identity_status, ['incomplete', 'rejected'], true);
    $uploadedCount = collect($requiredTypes)->filter(fn ($type) => $documents->has($type))->count();
    $complete = $uploadedCount === count($requiredTypes);
    $missingLabels = collect($requiredTypes)
        ->reject(fn ($type) => $documents->has($type))
        ->map(fn ($type) => $labels[$type] ?? $type)
        ->values();
@endphp

<div class="account-app-shell">
    @include('account.partials.sidebar', ['active' => 'profile'])
    <main class="account-main" id="contenido-principal">
        <header class="account-dashboard-topbar"><span class="account-notification">♧</span><span class="account-avatar">{{ mb_strtoupper(mb_substr($profile->name, 0, 1)) }}</span><strong>{{ $profile->name }}</strong><span>⌄</span></header>
    <header class="account-header" aria-label="Navegación de identidad">
        <a href="{{ route('account.dashboard') }}" class="account-brand" aria-label="Divas Cuyo, Mi cuenta">
            <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo">
            <span>DIVAS CUYO</span>
        </a>
        <nav class="account-nav" aria-label="Navegación privada">
            <a href="{{ route('account.dashboard') }}">Mi cuenta</a>
            <a href="{{ route('account.photos.index') }}">Mis fotos</a>
            <a href="{{ route('account.videos.index') }}">Mis videos</a>
            <span class="account-profile-name">{{ $user->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="account-logout">Cerrar sesión</button>
            </form>
        </nav>
    </header>

    <section class="account-hero" aria-labelledby="identity-title">
        <p class="account-eyebrow">Seguridad del perfil</p>
        <h1 id="identity-title">Validación de identidad</h1>
        <p>Completá la documentación para validar tu identidad y mayoría de edad. Al elegir una imagen se sube automáticamente.</p>
        <p class="identity-status-line">Estado: <strong class="account-badge account-badge-{{ $identityState['class'] }}">{{ $identityState['label'] }}</strong></p>
    </section>

    @if (session('status'))<p class="identity-feedback feedback-success" role="status">{{ session('status') }}</p>@endif
    @if ($profile->identity_rejection_reason)
        <p class="identity-feedback identity-feedback-error" role="alert"><strong>Motivo del rechazo:</strong> {{ $profile->identity_rejection_reason }}</p>
    @endif

    <section class="account-card" aria-labelledby="documents-title">
        <div class="account-section-heading">
            <div>
                <p class="account-kicker">Documentación requerida</p>
                <h2 id="documents-title">Tus documentos</h2>
            </div>
            <span class="account-heading-note">Información privada y protegida</span>
        </div>

        <div class="identity-progress" role="status" aria-live="polite">
            <span class="identity-progress-label" data-doc-counter>{{ $uploadedCount }} de {{ count($requiredTypes) }} documentos cargados</span>
            <span class="identity-progress-track"><span class="identity-progress-fill" data-doc-progress-bar style="width: {{ (int) round($uploadedCount / count($requiredTypes) * 100) }}%"></span></span>
        </div>

        <div class="identity-document-grid">
            @foreach ($requiredTypes as $type)
                @php $document = $documents->get($type); @endphp
                <article
                    class="identity-document-card"
                    data-identity-document="{{ $type }}"
                    data-label="{{ $labels[$type] ?? $type }}"
                    data-loaded="{{ $document ? 'true' : 'false' }}"
                    data-uploadable="{{ $canUpload ? 'true' : 'false' }}"
                >
                    <div class="identity-document-top">
                        <div>
                            <p class="account-kicker">Documento</p>
                            <h3>{{ $labels[$type] ?? $type }}</h3>
                        </div>
                        <span class="identity-doc-badge {{ $document ? 'identity-doc-badge-loaded' : 'identity-doc-badge-pending' }}" data-doc-badge>
                            {{ $document ? 'Cargado' : 'Pendiente' }}
                        </span>
                    </div>

                    <p class="identity-document-hint" data-doc-hint>
                        {{ $document ? 'Tu documento está cargado y protegido.' : 'Todavía falta este documento.' }}
                    </p>

                    <div class="identity-doc-progress" data-doc-progress hidden>
                        <span class="identity-doc-progress-track"><span class="identity-doc-progress-bar" data-doc-bar></span></span>
                        <span class="identity-doc-progress-value" data-doc-percent>0%</span>
                    </div>

                    <p class="identity-error" data-doc-error role="alert" hidden></p>

                    <div class="identity-document-actions">
                        <a
                            class="identity-document-link"
                            data-doc-view
                            @if ($document) href="{{ route('identity.documents.download', [$user, $document]) }}" @else hidden @endif
                        >Ver documento</a>

                        @if ($canUpload)
                            <input
                                id="document-{{ $type }}"
                                class="identity-doc-input"
                                type="file"
                                name="document"
                                accept="image/jpeg,image/png,image/webp,application/pdf"
                                data-doc-input
                                data-type="{{ $type }}"
                                data-upload-url="{{ route('identity.documents.store', $user) }}"
                            >
                            <label class="identity-doc-pick" for="document-{{ $type }}" data-doc-pick>
                                {{ $document ? 'Cambiar imagen' : 'Elegir imagen' }}
                            </label>
                        @endif
                    </div>

                    @unless ($canUpload)
                        <p class="identity-document-help">
                            @if ($profile->identity_status === 'pending')
                                La documentación está en revisión.
                            @elseif ($profile->identity_status === 'approved')
                                La documentación está aprobada y no puede modificarse.
                            @else
                                Podés reemplazar este archivo y volver a enviarlo.
                            @endif
                        </p>
                    @endunless
                </article>
            @endforeach
        </div>
    </section>

    @if ($canUpload)
        <form class="identity-submit" method="POST" action="{{ route('identity.submit', $user) }}" data-identity-submit>
            @csrf
            <p class="identity-error" data-submit-error role="alert" hidden></p>
            <p class="identity-submit-hint" data-submit-hint>
                {{ $complete
                    ? 'Los ' . count($requiredTypes) . ' documentos están cargados. Podés enviarlos a revisión.'
                    : 'Faltan ' . $missingLabels->join(', ') . ' para poder enviar.' }}
            </p>
            <button class="account-primary-action" type="submit" data-submit-button @disabled(! $complete)>
                Enviar documentación a revisión <span aria-hidden="true">→</span>
            </button>
        </form>
    @endif

    <p class="identity-back-link"><a href="{{ route('account.show', $user) }}">Volver a mi perfil</a></p>
</main>
</div>
</body>
</html>
