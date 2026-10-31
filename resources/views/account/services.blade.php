<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicios y tipo de publicación · Divas Cuyo</title>
    @vite('resources/css/styles.css')
    <script src="{{ asset('js/notifications.js') }}" defer></script>
</head>
<body class="account-mobile-navigation account-page account-dashboard-page profile-page services-page">
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

    <section class="account-card modality-card" id="publication-type" aria-labelledby="publication-type-title">
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
            <fieldset class="modality-options">
                <legend class="sr-only">Elegí tu modalidad de publicación</legend>
                @foreach ($publicationTypes as $publicationType)
                    <label class="modality-option">
                        <input type="radio" name="publication_type_id" value="{{ $publicationType->id }}"
                            data-slug="{{ $publicationType->slug }}" required
                            @checked((string) old('publication_type_id', $profile->publication_type_id) === (string) $publicationType->id)>
                        <span class="modality-option-body">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                @if ($publicationType->slug === 'virtual')
                                    <rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 18h4"/>
                                @else
                                    <path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>
                                @endif
                            </svg>
                            <strong>{{ $publicationType->name }}</strong>
                            <span>{{ $publicationType->slug === 'virtual' ? 'Ofrecé tus servicios de forma virtual.' : 'Ofrecé encuentros presenciales y servicios virtuales.' }}</span>
                            @if (config('publication.monthly_prices_ars.'.$publicationType->slug) !== null)
                                <strong class="modality-price">${{ number_format(config('publication.monthly_prices_ars.'.$publicationType->slug), 0, ',', '.') }} ARS mensuales</strong>
                            @endif
                            @if ((string) $profile->publication_type_id === (string) $publicationType->id)
                                <small>Modalidad actual</small>
                            @endif
                        </span>
                    </label>
                @endforeach
            </fieldset>
            <div id="modality-confirmation" class="modality-confirmation" hidden>
                <h3>Confirmá el cambio de modalidad</h3>
                <p class="profile-help">El cambio se registra en tu historial. No modifica tus fotos, identidad ni habilita tu publicación.</p>
                <p class="profile-help">Los pagos registrados y sus fechas de vigencia se mantienen. Este cambio no recalcula importes ni renueva el período abonado.</p>
                <label>Motivo del cambio (opcional)
                    <textarea name="reason" rows="2" maxlength="2000">{{ old('reason') }}</textarea>
                </label>
                <label class="profile-checkbox" id="modality-removal" hidden>
                    <input type="checkbox" name="confirm_in_person_removal" value="1" @checked(old('confirm_in_person_removal'))>
                    Confirmo quitar los servicios presenciales: no son compatibles con Solo Virtual.
                </label>
                <div class="modality-confirm-actions">
                    <button type="submit" class="account-primary-action">Confirmar cambio</button>
                    <button type="button" class="account-secondary-action" id="modality-cancel">Cancelar</button>
                </div>
            </div>
            <noscript><p>Activá JavaScript para cambiar la modalidad con confirmación.</p></noscript>
        </form>
        <p class="profile-help">El cambio se registra en el historial y no crea otro perfil ni modifica tus fotos, identidad o publicación.</p>
    </section>

    <section class="account-card services-compact-card" id="services" aria-labelledby="services-title">
        <div class="account-section-heading">
            <div>
                <p class="account-kicker">Servicios ofrecidos</p>
                <h2 id="services-title">Servicios</h2>
            </div>
            <span class="account-heading-note" id="services-selected-count" aria-live="polite"></span>
        </div>
        <form method="POST" action="{{ route('account.profile.services.update') }}" class="profile-fields" id="services-autosave-form">
            @csrf
            @method('PATCH')
            @foreach (['virtual' => 'Servicios virtuales', 'in_person' => 'Servicios presenciales'] as $serviceType => $label)
                @php
                    $availableServices = $services->where('service_type', $serviceType);
                @endphp
                @if ($availableServices->isNotEmpty() && ($serviceType === 'virtual' || $profile->publicationType?->slug === 'encounters'))
                    <fieldset class="profile-service-group" data-service-type="{{ $serviceType }}">
                        <legend>{{ $label }}</legend>
                        @foreach ($availableServices as $service)
                            <label class="service-chip">
                                <input type="checkbox" disabled name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, old('service_ids', $profile->services->modelKeys()), true))>
                                <span class="service-chip-label"><span class="service-chip-check" aria-hidden="true">✓</span>{{ $service->name }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
            @endforeach
            <p id="services-save-status" class="profile-help" role="status" aria-live="polite">Los cambios se guardan automáticamente.</p>
            <button type="button" id="services-save-retry" class="account-secondary-action" hidden>Reintentar guardado</button>
            <noscript><p>Activá JavaScript para modificar tus servicios.</p></noscript>
        </form>
        <p class="profile-help">Los servicios presenciales no pueden asociarse a Solo Virtual.</p>
    </section>
</main>
</div>
<script>
    (() => {
        const form = document.getElementById('publication-type-form');
        const current = @js((string) $profile->publication_type_id);
        const radios = [...form.querySelectorAll('[name="publication_type_id"]')];
        const panel = document.getElementById('modality-confirmation');
        const removal = document.getElementById('modality-removal');
        const confirmation = removal.querySelector('input');
        const reason = form.querySelector('[name="reason"]');
        function update() {
            const selected = radios.find(radio => radio.checked);
            const changed = selected && selected.value !== current;
            panel.hidden = !changed;
            reason.disabled = !changed;
            // Read current selections: services can be changed by autosave on this page.
            const hasInPerson = [...document.querySelectorAll('[data-service-type="in_person"] input:checked')].length > 0;
            const incompatible = changed && selected.dataset.slug === 'virtual' && hasInPerson;
            removal.hidden = !incompatible;
            confirmation.disabled = !incompatible;
            confirmation.required = !!incompatible;
            if (!incompatible) confirmation.checked = false;
        }
        radios.forEach(radio => radio.addEventListener('change', update));
        document.getElementById('services-autosave-form').addEventListener('change', update);
        document.getElementById('modality-cancel').addEventListener('click', () => {
            radios.forEach(radio => radio.checked = radio.value === current);
            reason.value = '';
            confirmation.checked = false;
            update();
            radios.find(radio => radio.checked)?.focus();
        });
        form.addEventListener('submit', event => {
            if (panel.hidden) event.preventDefault();
        });
        update();
    })();
</script>
<script>
(() => {
    const form = document.getElementById('services-autosave-form');
    const fields = [...form.querySelectorAll('input[type="checkbox"]')];
    const status = document.getElementById('services-save-status');
    const retry = document.getElementById('services-save-retry');
    const counter = document.getElementById('services-selected-count');
    function updateCount() {
        const count = fields.filter(field => field.checked).length;
        counter.textContent = count + (count === 1 ? ' servicio seleccionado' : ' servicios seleccionados');
    }
    updateCount();
    let saving = false;
    let unsaved = false;
    fields.forEach(field => field.disabled = false);
    async function save() {
        if (saving) return;
        saving = true;
        unsaved = true;
        const ids = fields.filter(field => field.checked).map(field => field.value);
        fields.forEach(field => field.disabled = true);
        retry.hidden = true;
        form.setAttribute('aria-busy', 'true');
        status.textContent = 'Guardando...';
        status.dataset.state = 'saving';
        try {
            const response = await fetch(form.action, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json', 'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value
                },
                body: JSON.stringify({ service_ids: ids })
            });
            const data = await response.json().catch(() => ({ message: 'No se pudo guardar. Recargá la página y reintentá.' }));
            if (response.redirected) throw new Error('Tu sesión puede haber vencido. Recargá la página.');
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || 'No se pudieron guardar los cambios. Recargá la página y reintentá.');
            unsaved = false;
            status.textContent = 'Guardado';
            status.dataset.state = 'saved';
        } catch (error) {
            status.textContent = 'Error al guardar. ' + (error.message === 'Failed to fetch' ? 'Revisá tu conexión y reintentá.' : error.message);
            status.dataset.state = 'error';
            retry.hidden = false;
        } finally {
            saving = false;
            fields.forEach(field => field.disabled = false);
            form.setAttribute('aria-busy', 'false');
        }
    }
    form.addEventListener('submit', event => { event.preventDefault(); save(); });
    form.addEventListener('change', () => { updateCount(); save(); });
    retry.addEventListener('click', save);
    window.addEventListener('beforeunload', event => {
        if (unsaved) { event.preventDefault(); event.returnValue = ''; }
    });
})();
</script>
</body>
</html>
