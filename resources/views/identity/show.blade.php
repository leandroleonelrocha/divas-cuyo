<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validación de identidad · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="account-page">
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
    $documents = $profile->documents->keyBy('type');
    $canUpload = in_array($profile->identity_status, ['incomplete', 'rejected'], true);
@endphp

<main class="account-shell" id="contenido-principal">
    <header class="account-header" aria-label="Navegación de identidad">
        <a href="{{ route('account.dashboard') }}" class="account-brand" aria-label="Divas Cuyo, Mi cuenta">
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

    <section class="account-hero" aria-labelledby="identity-title">
        <p class="account-eyebrow">Seguridad del perfil</p>
        <h1 id="identity-title">Validación de identidad</h1>
        <p>Completá la documentación para validar tu identidad y mayoría de edad.</p>
        <p class="identity-status-line">Estado: <strong class="account-badge account-badge-{{ $identityState['class'] }}">{{ $identityState['label'] }}</strong></p>
    </section>

    @if (session('status'))<p class="identity-feedback" role="status">{{ session('status') }}</p>@endif
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

        <div class="identity-document-grid">
            @foreach (config('identity-documents.required_types') as $type)
                <article class="identity-document-card">
                    <div>
                        <p class="account-kicker">Documento</p>
                        <h3>{{ $labels[$type] ?? $type }}</h3>
                    </div>

                    @if ($documents->has($type))
                        <p class="identity-document-status identity-document-status-uploaded">Documento cargado</p>
                        <a class="identity-document-link" href="{{ route('identity.documents.download', [$user, $documents[$type]]) }}">Ver documento</a>
                    @else
                        <p class="identity-document-status">Aún no cargado</p>
                    @endif

                    @if ($canUpload)
                        <form class="identity-upload-form" method="POST" action="{{ route('identity.documents.store', $user) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="type" value="{{ $type }}">
                            <label for="document-{{ $type }}">{{ $documents->has($type) ? 'Reemplazar archivo' : 'Seleccionar archivo' }}</label>
                            <input id="document-{{ $type }}" type="file" name="document" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                            @error('document')<p class="identity-error" role="alert">{{ $message }}</p>@enderror
                            @error('type')<p class="identity-error" role="alert">{{ $message }}</p>@enderror
                            <button class="account-primary-action" type="submit">{{ $documents->has($type) ? 'Reemplazar' : 'Cargar' }}</button>
                        </form>
                    @elseif ($profile->identity_status === 'pending')
                        <p class="identity-document-help">La documentación está en revisión.</p>
                    @elseif ($profile->identity_status === 'approved')
                        <p class="identity-document-help">La documentación está aprobada y no puede modificarse.</p>
                    @else
                        <p class="identity-document-help">Podés reemplazar este archivo y volver a enviarlo.</p>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    @if ($canUpload)
        <form class="identity-submit" method="POST" action="{{ route('identity.submit', $user) }}">
            @csrf
            @error('documents')<p class="identity-error" role="alert">{{ $message }}</p>@enderror
            <button class="account-primary-action" type="submit">Enviar documentación a revisión <span aria-hidden="true">→</span></button>
        </form>
    @endif

    <p class="identity-back-link"><a href="{{ route('account.show', $user) }}">Volver a mi perfil</a></p>
</main>
</body>
</html>
