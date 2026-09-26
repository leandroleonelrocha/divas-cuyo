<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar perfil · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="account-page profile-page">
<main class="account-shell" id="contenido-principal">
    <header class="account-header" aria-label="Navegación de mi perfil">
        <a href="{{ url('/') }}" class="account-brand" aria-label="Divas Cuyo">
            <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo">
            <span>DIVAS CUYO</span>
        </a>
        <nav class="account-nav" aria-label="Navegación privada">
            <a href="{{ route('account.dashboard') }}">Mi cuenta</a>
            <a href="{{ route('account.photos.index') }}">Mis fotos</a>
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
        <div class="profile-feedback profile-feedback-success" role="status">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="profile-feedback profile-feedback-error" role="alert">
            <strong>Revisá la información indicada.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
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
                <label>Latitud aproximada
                    <input type="number" name="approximate_latitude" step="0.0000001" min="-90" max="90" value="{{ old('approximate_latitude', $profile->approximate_latitude) }}">
                </label>
                <label>Longitud aproximada
                    <input type="number" name="approximate_longitude" step="0.0000001" min="-180" max="180" value="{{ old('approximate_longitude', $profile->approximate_longitude) }}">
                </label>
            </div>
            <p class="profile-help">La edad pública puede ser como máximo tu edad real y hasta cinco años menor. La ubicación es aproximada; no ingreses un domicilio exacto.</p>
        </section>

        <div class="profile-form-actions">
            <button type="submit" class="account-primary-action">Guardar información</button>
        </div>
    </form>

    <section class="account-card" aria-labelledby="publication-type-title">
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

    <section class="account-card" aria-labelledby="services-title">
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
