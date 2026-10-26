<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis fotos · Divas Cuyo</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/css/styles.css')
    <script src="{{ asset('js/model-photos.js') }}" defer></script>
</head>
<body class="account-page account-dashboard-page photos-page">
@php
    $maxPhotos = (int) config('model-photos.max_photos');
    $isFull = $photos->count() >= $maxPhotos;
@endphp
<div class="account-app-shell">
    @include('account.partials.sidebar', ['active' => 'photos'])
    <main class="account-main" id="contenido-principal">
    <header class="account-dashboard-topbar"><span class="account-notification">♧</span><span class="account-avatar">{{ mb_strtoupper(mb_substr($profile->name, 0, 1)) }}</span><strong>{{ $profile->name }}</strong><span>⌄</span></header>
    <header class="account-header legacy-account-header" aria-label="Navegación de mis fotos">
        <a href="{{ route('account.dashboard') }}" class="account-brand" aria-label="Divas Cuyo, Mi cuenta">
            <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo">
            <span>DIVAS CUYO</span>
        </a>
        <nav class="account-nav" aria-label="Navegación privada">
            <a href="{{ route('account.dashboard') }}">Mi cuenta</a>
            <a href="{{ route('account.photos.index') }}" aria-current="page">Mis fotos</a>
            <a href="{{ route('account.videos.index') }}">Mis videos</a>
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
            <strong data-photos-count>{{ $photos->count() }} / {{ $maxPhotos }}</strong>
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
        <div
            class="photos-gallery-panel account-card"
            data-photos-panel
            data-empty="{{ $photos->isEmpty() ? 'true' : 'false' }}"
            data-full="{{ $isFull ? 'true' : 'false' }}"
            data-count="{{ $photos->count() }}"
            data-limit="{{ $maxPhotos }}"
            data-max-size-kb="{{ config('model-photos.max_size_kb') }}"
            data-upload-url="{{ route('account.photos.store') }}"
        >
            <div class="account-section-heading">
                <div>
                    <p class="account-kicker">Galería</p>
                    <h2>Tus fotografías</h2>
                </div>
                <span class="account-heading-note">Privadas y protegidas</span>
            </div>

            <p class="photos-feedback photos-feedback-error" data-photos-errors role="alert" hidden></p>
            <p class="photos-feedback photos-feedback-success" data-photos-success role="status" hidden></p>

            <input
                id="photos-file-input"
                class="photos-file-input"
                type="file"
                name="photo"
                accept="image/jpeg,image/png,image/webp"
                multiple
                data-photos-input
            >

            @if ($photos->isEmpty())
                <div class="photos-dropzone" data-photos-dropzone>
                    <span class="photos-dropzone-icon" aria-hidden="true">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <path d="M17 8l-5-5-5 5"/>
                            <path d="M12 3v12"/>
                        </svg>
                    </span>
                    <h3>Todavía no cargaste fotos</h3>
                    <p>Arrastrá tus imágenes acá o elegilas desde tu dispositivo. Se cargan automáticamente y pasan por revisión.</p>
                    <label class="account-primary-action photos-pick" for="photos-file-input">Agregar fotografías <span aria-hidden="true">+</span></label>
                    <p class="photos-upload-help">JPEG, PNG o WebP · hasta 10 MB · se redimensiona automáticamente</p>
                </div>
            @endif

            <div class="photos-upload-queue" data-photos-queue aria-live="polite" hidden></div>

            <form class="photos-order-form" id="photos-order-form" method="POST" action="{{ route('account.photos.order') }}">
                @csrf
                @method('PATCH')
                <div id="photo-order-inputs" aria-hidden="true"></div>
                <div class="photos-order-toolbar">
                    <p>Usá las flechas para definir el orden de tu galería.</p>
                    <button class="account-secondary-action" type="submit">Guardar orden</button>
                </div>
            </form>

            <div class="photos-grid" id="photo-order-list" data-photos-grid aria-label="Orden de tus fotografías">
                @foreach ($photos as $photo)
                    @include('account.photos.card', ['photo' => $photo, 'profile' => $profile, 'index' => $loop->index])
                @endforeach

                <article class="photo-add-card" data-photos-add>
                    <span class="photo-add-icon" aria-hidden="true">+</span>
                    <h3>Agregar más fotos</h3>
                    <p>Elegí varias imágenes o arrastralas acá. Se cargan automáticamente.</p>
                    <label class="account-primary-action photos-pick" for="photos-file-input">Agregar fotografías <span aria-hidden="true">+</span></label>
                </article>
            </div>

            <p class="photos-limit-message" data-photos-limit>Alcanzaste el máximo de {{ $maxPhotos }} fotografías.</p>

            <div class="photos-private-note">
                <span class="photos-private-icon" aria-hidden="true">✓</span>
                <p>Tus imágenes se guardan en un espacio privado y no se muestran públicamente mientras están en revisión.</p>
            </div>
        </div>
    </section>
</main>
</div>
</body>
</html>
