<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis fotos · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="account-page photos-page">
<main class="account-shell" id="contenido-principal">
    <header class="account-header" aria-label="Navegación de mis fotos">
        <a href="{{ route('account.dashboard') }}" class="account-brand" aria-label="Divas Cuyo, Mi cuenta">
            <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo">
            <span>DIVAS CUYO</span>
        </a>
        <nav class="account-nav" aria-label="Navegación privada">
            <a href="{{ route('account.dashboard') }}">Mi cuenta</a>
            <a href="{{ route('account.photos.index') }}" aria-current="page">Mis fotos</a>
            <span class="account-profile-name">{{ $profile->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="account-logout">Cerrar sesión</button>
            </form>
        </nav>
    </header>

    <section class="photos-hero" aria-labelledby="photos-title">
        <div>
            <p class="account-eyebrow">Tu galería privada</p>
            <h1 id="photos-title">Mis fotos</h1>
            <p>Mostrá tu estilo con imágenes cuidadas. Todas las fotos pasan por una revisión antes de poder publicarse.</p>
        </div>
        <div class="photos-counter" aria-label="Cantidad de fotos cargadas">
            <strong>{{ $photos->count() }} / {{ config('model-photos.max_photos') }}</strong>
            <span>fotos cargadas</span>
        </div>
    </section>

    @if (session('status'))
        <p class="photos-feedback photos-feedback-success" role="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div class="photos-feedback photos-feedback-error" role="alert">
            <strong>No pudimos cargar la fotografía.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="photos-layout" aria-label="Galería y carga de fotografías">
        <div class="photos-gallery-panel account-card">
            <div class="account-section-heading">
                <div>
                    <p class="account-kicker">Galería</p>
                    <h2>Tus fotografías</h2>
                </div>
                <span class="account-heading-note">Privadas y protegidas</span>
            </div>

            @if ($photos->isEmpty())
                <div class="photos-empty" role="status">
                    <div class="photos-empty-icon" aria-hidden="true">+</div>
                    <h3>Todavía no cargaste fotos</h3>
                    <p>Cuando subas tu primera imagen, la vas a ver acá con su estado de revisión.</p>
                </div>
            @else
                <form class="photos-order-form" id="photos-order-form" method="POST" action="{{ route('account.photos.order') }}">
                    @csrf
                    @method('PATCH')
                    <div id="photo-order-inputs" aria-hidden="true"></div>
                    <div class="photos-order-toolbar">
                        <p>Usá las flechas para definir el orden de tu galería.</p>
                        <button class="account-secondary-action" type="submit">Guardar orden</button>
                    </div>
                </form>

                <div class="photos-grid" id="photo-order-list" aria-label="Orden de tus fotografías">
                    @foreach ($photos as $photo)
                        @php
                            $currentVersion = $photo->currentVersion;
                            $latestVersion = $photo->latestVersion;
                            $version = $currentVersion ?? $latestVersion;
                            $statusVersion = $latestVersion ?? $currentVersion;
                            $status = $statusVersion?->status?->value ?? 'pending';
                            $hasApprovedCurrent = $currentVersion?->status?->value === 'approved';
                            $hasNewVersion = $currentVersion && $latestVersion && $currentVersion->id !== $latestVersion->id;
                            $statusLabel = $hasNewVersion && $status === 'pending'
                                ? 'Nueva versión en revisión'
                                : (['pending' => 'En revisión', 'approved' => 'Aprobada', 'rejected' => 'Rechazada'][$status] ?? 'En revisión');
                            $statusClass = ['pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected'][$status] ?? 'pending';
                        @endphp
                        <article class="photo-card" data-photo-id="{{ $photo->id }}">
                            <div class="photo-preview">
                                @if ($version)
                                    <img src="{{ route('account.photos.file', [$photo, 'thumbnail']) }}" alt="Fotografía cargada por {{ $profile->name }}">
                                @else
                                    <span aria-hidden="true">Sin vista previa</span>
                                @endif
                            </div>
                            <div class="photo-card-body">
                                <div class="photo-card-heading">
                                    <span class="photo-position" data-photo-position>Foto {{ $loop->iteration }}</span>
                                    <span class="account-badge account-badge-{{ $statusClass }}">{{ $statusLabel }}</span>
                                </div>
                                @if ($photo->is_primary)
                                    <span class="photo-primary-badge">Principal</span>
                                @elseif ($hasApprovedCurrent)
                                    <form class="photo-primary-form" method="POST" action="{{ route('account.photos.primary', $photo) }}">
                                        @csrf
                                        <button class="photo-primary-action" type="submit">Usar como principal</button>
                                    </form>
                                @endif
                                @if ($status === 'rejected' && $statusVersion?->rejection_reason)
                                    <p class="photo-rejection"><strong>Motivo:</strong> {{ $statusVersion->rejection_reason }}</p>
                                @elseif ($hasNewVersion && $status === 'pending')
                                    <p class="photo-card-help">La versión aprobada actual sigue vigente mientras revisamos esta nueva versión.</p>
                                @else
                                    <p class="photo-card-help">La revisión protege la calidad de tu perfil.</p>
                                @endif
                                <div class="photo-card-actions">
                                    <form class="photo-replace-form" method="POST" action="{{ route('account.photos.replace', $photo) }}" enctype="multipart/form-data">
                                        @csrf
                                        <label for="replace-photo-{{ $photo->id }}">Reemplazar</label>
                                        <input id="replace-photo-{{ $photo->id }}" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                                        <button class="photo-primary-action" type="submit">Cargar nueva versión</button>
                                    </form>
                                    <form class="photo-delete-form" method="POST" action="{{ route('account.photos.destroy', $photo) }}" onsubmit="return confirm('¿Querés eliminar esta fotografía? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="photo-delete-action" type="submit">Eliminar</button>
                                    </form>
                                </div>
                                <div class="photo-order-controls" aria-label="Cambiar orden de la foto">
                                    <button class="photo-order-button" type="button" data-order-move="up" aria-label="Mover foto hacia arriba">↑</button>
                                    <button class="photo-order-button" type="button" data-order-move="down" aria-label="Mover foto hacia abajo">↓</button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="photos-upload-panel account-card" aria-labelledby="upload-title">
            <div class="account-section-heading">
                <div>
                    <p class="account-kicker">Nueva fotografía</p>
                    <h2 id="upload-title">Sumá una imagen</h2>
                </div>
            </div>
            @if ($photos->count() >= config('model-photos.max_photos'))
                <p class="photos-limit-message">Alcanzaste el máximo de {{ config('model-photos.max_photos') }} fotografías.</p>
            @else
                <form class="photos-upload-form" method="POST" action="{{ route('account.photos.store') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="photo">Elegí una fotografía</label>
                    <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                <p class="photos-upload-help">JPEG, PNG o WebP · hasta 10 MB · se redimensiona automáticamente</p>
                    <button class="account-primary-action" type="submit">Cargar fotografía <span aria-hidden="true">→</span></button>
                </form>
            @endif
            <div class="photos-private-note">
                <span class="photos-private-icon" aria-hidden="true">✓</span>
                <p>Tus imágenes se guardan en un espacio privado y no se muestran públicamente mientras están en revisión.</p>
            </div>
        </aside>
    </section>
</main>
@if ($photos->isNotEmpty())
    <script>
        (() => {
            const list = document.querySelector('#photo-order-list');
            const inputs = document.querySelector('#photo-order-inputs');
            const orderForm = document.querySelector('#photos-order-form');
            if (!list || !inputs || !orderForm) return;

            const syncOrder = () => {
                inputs.replaceChildren(...Array.from(list.querySelectorAll('[data-photo-id]'), (card) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'photo_ids[]';
                    input.value = card.dataset.photoId;
                    return input;
                }));

                list.querySelectorAll('[data-photo-position]').forEach((label, index) => {
                    label.textContent = `Foto ${index + 1}`;
                });
                list.querySelectorAll('[data-order-move="up"]').forEach((button, index) => {
                    button.disabled = index === 0;
                });
                list.querySelectorAll('[data-order-move="down"]').forEach((button, index, buttons) => {
                    button.disabled = index === buttons.length - 1;
                });
            };

            list.addEventListener('click', (event) => {
                const button = event.target.closest('[data-order-move]');
                if (!button) return;
                const card = button.closest('[data-photo-id]');
                const sibling = button.dataset.orderMove === 'up' ? card.previousElementSibling : card.nextElementSibling;
                if (!sibling) return;
                if (button.dataset.orderMove === 'up') list.insertBefore(card, sibling);
                else list.insertBefore(sibling, card);
                syncOrder();
            });

            orderForm.addEventListener('submit', syncOrder);
            syncOrder();
        })();
    </script>
@endif
</body>
</html>
