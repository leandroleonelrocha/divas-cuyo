<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicios y tipo de publicación · Divas Cuyo</title>
    @vite('resources/css/styles.css')
    <script src="{{ asset('js/notifications.js') }}" defer></script>
</head>
<body class="account-page account-dashboard-page profile-page services-page">
<div class="account-app-shell">
    @include('account.partials.sidebar', ['active' => 'services'])
    <main class="account-main" id="contenido-principal">
        @include('account.partials.topbar', ['label' => $profile->stage_name ?: $profile->name])
    <header class="account-header" aria-label="Navegación de mi cuenta">
        <a href="{{ url('/') }}" class="account-brand" aria-label="Divas Cuyo">
            <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo">
            <span>DIVAS CUYO</span>
        </a>
        <nav class="account-nav" aria-label="Navegación privada">
            <a href="{{ route('account.dashboard') }}" aria-current="page">Mi cuenta</a>
            <a href="{{ route('account.photos.index') }}">Mis fotos</a>
            <a href="{{ route('account.videos.index') }}">Mis videos</a>
            <span class="account-profile-name">{{ $profile->stage_name ?: $profile->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="account-logout">Cerrar sesión</button>
            </form>
        </nav>
    </header>

    <section class="account-hero" aria-labelledby="services-page-title">
        <p class="account-eyebrow">Perfil de modelo</p>
        <h1 id="services-page-title">Servicios y tipo de publicación</h1>
        <p>Elegí tu modalidad de publicación y los servicios que querés ofrecer.</p>
    </section>

    @if (session('success'))
        <x-notify type="success" variant="toast" :message="session('success')" />
    @endif

    @if ($errors->any())
        <x-notify type="error" title="Revisá la información indicada.">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-notify>
    @endif

    <section class="account-card" id="publication-type" aria-labelledby="publication-type-title">
        <div class="account-section-heading">
            <div>
                <p class="account-kicker">Modalidad de publicación</p>
                <h2 id="publication-type-title">Tipo de publicación</h2>
            </div>
            <span class="account-heading-note">Una modalidad activa por perfil</span>
        </div>
        <form method="POST" action="{{ route('account.profile.publication-type.update') }}" class="profile-fields" id="publication-type-form">
            @csrf
            @method('PATCH')
            <label>Tipo actual
                <select name="publication_type_id" required>
                    <option value="">Seleccioná un tipo</option>
                    @foreach ($publicationTypes as $publicationType)
                        <option value="{{ $publicationType->id }}" @selected((string) old('publication_type_id', $profile->publication_type_id) === (string) $publicationType->id)>{{ $publicationType->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Motivo opcional
                <textarea name="reason" rows="2" maxlength="2000">{{ old('reason') }}</textarea>
            </label>
            @if ($profile->publicationType?->slug === 'encounters' && $profile->services->contains('service_type', 'in_person'))
                <label class="profile-checkbox">
                    <input type="checkbox" name="confirm_in_person_removal" value="1">
                    Confirmo desactivar los servicios presenciales si cambio a Solo Virtual.
                </label>
            @endif
            <div class="profile-form-actions profile-form-actions-full">
                <button type="submit" class="account-secondary-action">Actualizar tipo de publicación</button>
            </div>
        </form>
        <p class="profile-help">El cambio se registra en el historial y no crea otro perfil ni modifica tus fotos, identidad o publicación.</p>
    </section>

    <section class="account-card" id="services" aria-labelledby="services-title">
        <div class="account-section-heading">
            <div>
                <p class="account-kicker">Servicios ofrecidos</p>
                <h2 id="services-title">Servicios</h2>
            </div>
            <span class="account-heading-note">Según tu tipo de publicación</span>
        </div>
        <form method="POST" action="{{ route('account.profile.services.update') }}" class="profile-fields">
            @csrf
            @method('PATCH')
            @foreach (['virtual' => 'Servicios virtuales', 'in_person' => 'Servicios presenciales'] as $serviceType => $label)
                @php
                    $availableServices = $services->where('service_type', $serviceType);
                @endphp
                @if ($availableServices->isNotEmpty() && ($serviceType === 'virtual' || $profile->publicationType?->slug === 'encounters'))
                    <fieldset class="profile-service-group">
                        <legend>{{ $label }}</legend>
                        @foreach ($availableServices as $service)
                            <label class="profile-checkbox">
                                <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, old('service_ids', $profile->services->modelKeys()), true))>
                                {{ $service->name }}
                            </label>
                        @endforeach
                    </fieldset>
                @endif
            @endforeach
            <div class="profile-form-actions profile-form-actions-full">
                <button type="submit" class="account-secondary-action">Guardar servicios</button>
            </div>
        </form>
        <p class="profile-help">Los servicios presenciales no pueden asociarse a Solo Virtual, incluso mediante solicitudes manipuladas.</p>
    </section>
</main>
</div>
<script>
    (() => {
        const form = document.getElementById('publication-type-form');
        if (!form) return;

        form.addEventListener('submit', (event) => {
            const selected = form.querySelector('select[name="publication_type_id"] option:checked');
            const confirmation = form.querySelector('input[name="confirm_in_person_removal"]');
            if (!selected || !confirmation || selected.textContent.trim() !== 'Solo Virtual' || confirmation.checked) return;

            if (!window.confirm('Al pasar a Solo Virtual se desactivarán tus servicios presenciales. ¿Querés continuar?')) {
                event.preventDefault();
                return;
            }

            confirmation.checked = true;
        });
    })();
</script>
</body>
</html>
