<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar perfil · Divas Cuyo</title>
    @vite('resources/css/styles.css')
    <script src="{{ asset('js/notifications.js') }}" defer></script>
</head>
<body class="account-page account-dashboard-page profile-page">
<div class="account-app-shell">
    @include('account.partials.sidebar', ['active' => 'profile'])
    <main class="account-main" id="contenido-principal">
        @include('account.partials.topbar', ['label' => $profile->stage_name ?: $profile->name])
    <header class="account-header" aria-label="Navegación de mi perfil">
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

    <section class="account-hero" aria-labelledby="profile-edit-title">
        <p class="account-eyebrow">Perfil de modelo</p>
        <h1 id="profile-edit-title">Editar mi perfil</h1>
        <p>Completá tus datos privados y la información que querés mostrar en tu publicación.</p>
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

    <form method="POST" action="{{ route('account.profile.update') }}" class="profile-form">
        @csrf
        @method('PATCH')

        <section class="account-card" aria-labelledby="private-details-title">
            <div class="account-section-heading">
                <div>
                    <p class="account-kicker">Información protegida</p>
                    <h2 id="private-details-title">Información privada</h2>
                </div>
                <span class="account-heading-note">Sólo vos y administración autorizada</span>
            </div>
            <p class="profile-private-note">Esta información es privada y no se muestra públicamente.</p>
            @if ($privateDetails)
                <p class="profile-help">Edad real calculada: <strong>{{ $privateDetails->realAge() }} años</strong></p>
            @endif
            <div class="profile-fields">
                <label>Nombre real <input name="real_first_name" value="{{ old('real_first_name', $privateDetails?->real_first_name) }}" required></label>
                <label>Apellido real <input name="real_last_name" value="{{ old('real_last_name', $privateDetails?->real_last_name) }}" required></label>
                <label>Fecha de nacimiento <input type="date" name="birth_date" value="{{ old('birth_date', $privateDetails?->birth_date?->format('Y-m-d')) }}" required></label>
                <label>Altura real (cm) <input type="number" name="real_height_cm" min="80" max="250" value="{{ old('real_height_cm', $privateDetails?->real_height_cm) }}"></label>
                <label>Peso real (kg) <input type="number" step="0.01" name="real_weight_kg" min="20" max="400" value="{{ old('real_weight_kg', $privateDetails?->real_weight_kg) }}"></label>
                <label>Medidas reales <input name="real_measurements" value="{{ old('real_measurements', $privateDetails?->real_measurements) }}"></label>
                <label>Teléfono privado <input name="private_phone" value="{{ old('private_phone', $privateDetails?->private_phone) }}"></label>
            </div>
        </section>

        <section class="account-card" aria-labelledby="public-details-title">
            <div class="account-section-heading">
                <div>
                    <p class="account-kicker">Información de publicación</p>
                    <h2 id="public-details-title">Datos públicos</h2>
                </div>
                <span class="account-heading-note">Lo que puede mostrarse en tu perfil</span>
            </div>
            <div class="profile-fields">
                <label>Nombre laboral o artístico <input name="stage_name" value="{{ old('stage_name', $profile->stage_name ?: $profile->name) }}" required></label>
                <label>Nacionalidad/origen <input name="nationality" value="{{ old('nationality', $profile->nationality) }}" required></label>
                <label>Edad pública <input type="number" name="public_age" min="18" max="120" value="{{ old('public_age', $profile->public_age) }}"></label>
                <label class="profile-checkbox"><input type="checkbox" name="show_age" value="1" @checked(old('show_age', $profile->show_age))> Mostrar mi edad públicamente</label>
                <label>Disponibilidad
                    <select name="availability_status" required>
                        <option value="available" @selected(old('availability_status', $profile->availability_status) === 'available')>Disponible</option>
                        <option value="unavailable" @selected(old('availability_status', $profile->availability_status) === 'unavailable')>No disponible</option>
                    </select>
                </label>
                <label>Disponibilidad horaria
                    <select name="schedule_availability">
                        <option value="">Seleccioná una opción</option>
                        <option value="part_time" @selected(old('schedule_availability', $profile->schedule_availability) === 'part_time')>Part Time</option>
                        <option value="full_time" @selected(old('schedule_availability', $profile->schedule_availability) === 'full_time')>Full Time</option>
                    </select>
                </label>
                <label>Género
                    <select name="gender">
                        <option value="">Seleccioná una opción</option>
                        <option value="female" @selected(old('gender', $profile->gender) === 'female')>Femenino</option>
                        <option value="trans" @selected(old('gender', $profile->gender) === 'trans')>Trans</option>
                    </select>
                </label>
                <label>Depilación
                    <select name="depilation">
                        <option value="">Seleccioná una opción</option>
                        <option value="full" @selected(old('depilation', $profile->depilation) === 'full')>Full</option>
                        <option value="partial" @selected(old('depilation', $profile->depilation) === 'partial')>Parcial</option>
                    </select>
                </label>
                <fieldset class="profile-rate-group" id="rate-group">
                    <legend>Precios</legend>
                    <label>Mostrar precios
                        <select name="rate_visibility" id="rate_visibility">
                            <option value="consult" @selected(old('rate_visibility', $profile->rate_visibility ?? 'consult') === 'consult')>Consultar</option>
                            <option value="published" @selected(old('rate_visibility', $profile->rate_visibility ?? 'consult') === 'published')>Publicar mis valores</option>
                        </select>
                    </label>
                    <div class="profile-rate-row">
                        <label>30 minutos
                            <input type="number" name="rate_30min" id="rate_30min" step="0.01" min="0" value="{{ old('rate_30min', $profile->rate_30min) }}" placeholder="Ej: 20000">
                        </label>
                        <label>Moneda
                            <select name="rate_30min_currency">
                                <option value="ARS" @selected(old('rate_30min_currency', $profile->rate_30min_currency ?? 'ARS') === 'ARS')>Pesos (ARS)</option>
                                <option value="USD" @selected(old('rate_30min_currency', $profile->rate_30min_currency) === 'USD')>Dólares (USD)</option>
                            </select>
                        </label>
                    </div>
                    <div class="profile-rate-row">
                        <label>1 hora
                            <input type="number" name="rate_60min" id="rate_60min" step="0.01" min="0" value="{{ old('rate_60min', $profile->rate_60min) }}" placeholder="Ej: 35000">
                        </label>
                        <label>Moneda
                            <select name="rate_60min_currency">
                                <option value="ARS" @selected(old('rate_60min_currency', $profile->rate_60min_currency ?? 'ARS') === 'ARS')>Pesos (ARS)</option>
                                <option value="USD" @selected(old('rate_60min_currency', $profile->rate_60min_currency) === 'USD')>Dólares (USD)</option>
                            </select>
                        </label>
                    </div>
                </fieldset>
                <label>Provincia
                    <select name="province_id" id="province_id">
                        <option value="">Seleccioná una provincia</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" @selected((string) old('province_id', $profile->province_id) === (string) $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Localidad
                    <select name="locality_id" id="locality_id">
                        <option value="">Seleccioná una localidad</option>
                        @foreach ($provinces as $province)
                            @foreach ($province->localities as $locality)
                                <option value="{{ $locality->id }}" data-province-id="{{ $province->id }}" @selected((string) old('locality_id', $profile->locality_id) === (string) $locality->id)>{{ $locality->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </label>
                <label>Zona aproximada
                    <input name="approximate_location_text" value="{{ old('approximate_location_text', $profile->approximate_location_text) }}" maxlength="255" placeholder="Barrio, zona o calles cercanas">
                </label>
            </div>
            <p class="profile-help">La edad pública puede ser como máximo tu edad real y hasta cinco años menor. La ubicación es aproximada; no ingreses un domicilio exacto.</p>
        </section>

        <div class="profile-form-actions">
            <button type="submit" class="account-primary-action">Guardar información</button>
        </div>
    </form>

    @php
        $pendingBio = $profile->bios->firstWhere('status', 'pending');
        $latestRejectedBio = $profile->bios->firstWhere('status', 'rejected');
    @endphp
    <section class="account-card" aria-labelledby="bio-title">
        <div class="account-section-heading">
            <div>
                <p class="account-kicker">Presentación</p>
                <h2 id="bio-title">Biografía</h2>
            </div>
            @if ($pendingBio)
                <span class="account-badge account-badge-pending">Pendiente de aprobación</span>
            @elseif ($profile->currentBio)
                <span class="account-badge account-badge-success">Aprobada</span>
            @endif
        </div>

        @if ($profile->currentBio)
            <div class="profile-bio-preview">
                <p class="profile-help">Biografía pública actual</p>
                <p>{{ $profile->currentBio->content }}</p>
            </div>
        @elseif (! $pendingBio && ! $latestRejectedBio)
            <p class="profile-empty-state">Todavía no tenés una biografía aprobada.</p>
        @endif

        @if ($pendingBio)
            <div class="profile-bio-preview">
                <p class="profile-help">Nueva versión pendiente; todavía no es pública.</p>
                <p>{{ $pendingBio->content }}</p>
            </div>
            @if ($latestRejectedBio)
                <div class="profile-feedback profile-feedback-error" role="alert">
                    <strong>Motivo del rechazo anterior:</strong>
                    <p>{{ $latestRejectedBio->rejection_reason }}</p>
                </div>
            @endif
        @else
            @if ($latestRejectedBio)
                <div class="profile-feedback profile-feedback-error" role="alert">
                    <strong>Tu última biografía fue rechazada.</strong>
                    <p>{{ $latestRejectedBio->rejection_reason }}</p>
                </div>
            @endif
            <form method="POST" action="{{ route('account.profile.bios.store') }}" class="profile-fields">
                @csrf
                <label>Texto de presentación
                    <textarea name="content" rows="7" minlength="10" maxlength="5000" required>{{ old('content', $latestRejectedBio?->content) }}</textarea>
                </label>
                <div class="profile-form-actions profile-form-actions-full">
                    <button type="submit" class="account-secondary-action">Enviar biografía a aprobación</button>
                </div>
            </form>
        @endif
        <p class="profile-help">Las nuevas versiones se revisan antes de reemplazar la biografía pública actual.</p>
    </section>

    <section class="account-card" aria-labelledby="physical-details-title">
        <div class="account-section-heading">
            <div>
                <p class="account-kicker">Revisión de publicación</p>
                <h2 id="physical-details-title">Características físicas</h2>
            </div>
            @if ($pendingPhysicalRevision)
                <span class="account-badge account-badge-pending">Pendiente de revisión</span>
            @endif
        </div>
        <p class="profile-help">Los cambios físicos se revisan antes de reemplazar la información pública aprobada.</p>
        <form method="POST" action="{{ route('account.profile.physical-revisions.store') }}" class="profile-fields">
            @csrf
            <label>Altura (cm) <input type="number" name="height_cm" min="80" max="250" value="{{ old('height_cm', $profile->height_cm) }}" required></label>
            <label>Peso (kg) <input type="number" step="0.01" name="weight_kg" min="20" max="400" value="{{ old('weight_kg', $profile->weight_kg) }}" required></label>
            <label>Medidas <input name="measurements" value="{{ old('measurements', $profile->measurements) }}" required></label>
            <label>Color de ojos <input name="eye_color" value="{{ old('eye_color', $profile->eye_color) }}" required></label>
            <label>Color de cabello <input name="hair_color" value="{{ old('hair_color', $profile->hair_color) }}"></label>
            <label>Color de piel <input name="skin_color" value="{{ old('skin_color', $profile->skin_color) }}"></label>
            <label>Tipo de cuerpo <input name="body_type" value="{{ old('body_type', $profile->body_type) }}"></label>
            <label>Nacionalidad/origen <input name="nationality" value="{{ old('nationality', $profile->nationality) }}" required></label>
            <div class="profile-form-actions profile-form-actions-full">
                <button type="submit" class="account-secondary-action">Enviar cambios físicos a revisión</button>
            </div>
        </form>
        @if ($pendingPhysicalRevision)
            <p class="profile-pending-note">Ya tenés una modificación pendiente. Administración debe revisarla antes de enviar otra.</p>
        @endif
    </section>
</main>
</div>
<script>
    (() => {
        const province = document.getElementById('province_id');
        const locality = document.getElementById('locality_id');
        if (!province || !locality) return;

        const filterLocalities = () => {
            [...locality.options].forEach((option) => {
                if (!option.dataset.provinceId) return;
                const visible = option.dataset.provinceId === province.value;
                option.hidden = !visible;
                if (!visible && option.selected) locality.value = '';
            });
        };

        province.addEventListener('change', filterLocalities);
        filterLocalities();
    })();
</script>
<script>
    (() => {
        const visibility = document.getElementById('rate_visibility');
        const amounts = [document.getElementById('rate_30min'), document.getElementById('rate_60min')].filter(Boolean);
        if (!visibility || amounts.length === 0) return;

        const toggle = () => amounts.forEach((input) => {
            input.disabled = visibility.value !== 'published';
        });

        visibility.addEventListener('change', toggle);
        toggle();
    })();
</script>
</body>
</html>
