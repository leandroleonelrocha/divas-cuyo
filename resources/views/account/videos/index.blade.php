<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis videos · Divas Cuyo</title>
    @vite('resources/css/styles.css')
    <script src="{{ asset('js/notifications.js') }}" defer></script>
    <script src="{{ asset('js/model-videos.js') }}" defer></script>
</head>
<body class="account-page account-dashboard-page photos-page">
<div class="account-app-shell">
    @include('account.partials.sidebar', ['active' => 'videos'])
    <main class="account-main" id="contenido-principal">
    @include('account.partials.topbar', ['label' => $profile->name])
    <header class="account-header" aria-label="Navegación de mis videos">
        <a href="{{ route('account.dashboard') }}" class="account-brand" aria-label="Divas Cuyo, Mi cuenta">
            <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo">
            <span>DIVAS CUYO</span>
        </a>
        <nav class="account-nav" aria-label="Navegación privada">
            <a href="{{ route('account.dashboard') }}">Mi cuenta</a>
            <a href="{{ route('account.photos.index') }}">Mis fotos</a>
            <a href="{{ route('account.videos.index') }}" aria-current="page">Mis videos</a>
            <span class="account-profile-name">{{ $profile->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="account-logout">Cerrar sesión</button>
            </form>
        </nav>
    </header>
    <section class="photos-hero">
        <div><h1>Mis videos</h1><p>Los videos llevan marca de agua y pasan por revisión antes de mostrarse en tu perfil público.</p></div>
        <div class="photos-counter"><strong>{{ $videos->count() }} / {{ config('model-videos.max_videos') }}</strong><span>videos cargados</span></div>
    </section>
    @if (session('status'))
        <x-notify type="success" variant="toast" :message="session('status')" />
    @endif
    @if ($errors->any())
        <x-notify type="error">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</x-notify>
    @endif
    <section class="account-card">
        <h2>Subir video</h2>
        <p>MP4 o MOV · Hasta {{ config('model-videos.max_duration') }} segundos · {{ config('model-videos.max_size_kb') / 1024 }} MB por archivo.</p>
        <form id="video-upload" action="{{ route('account.videos.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="video-file">Elegí un video</label>
            <input id="video-file" type="file" name="video" accept="video/mp4,video/quicktime,.mp4,.mov" required @disabled($videos->count() >= config('model-videos.max_videos'))>
            <video id="video-preview" controls playsinline preload="metadata" hidden style="width:100%;max-height:320px"></video>
            <p id="video-upload-message" role="status" aria-live="polite"></p>
            <progress id="video-upload-progress" max="100" value="0" hidden aria-label="Progreso de subida"></progress>
            <button class="account-primary-action" type="submit" @disabled($videos->count() >= config('model-videos.max_videos'))>Subir video</button>
        </form>
    </section>
    <section class="account-card">
        <h2>Tus videos</h2>
        <p>Actualizá esta página para consultar el estado del procesamiento y la revisión.</p>
        <div class="photos-grid">
            @forelse($videos as $video)
                <article class="photo-card">
                    @if($video->ready())
                        <video controls playsinline preload="none" style="width:100%;max-height:360px" poster="{{ route('account.videos.file', [$video, 'poster']) }}" src="{{ route('account.videos.file', [$video, 'video']) }}" aria-label="Video {{ $loop->iteration }}"></video>
                    @endif
                    <div class="photo-card-body">
                        <h3>Video {{ $loop->iteration }}</h3>
                        <p>{{ $video->statusLabel() }}</p>
                        @if($video->moderation_status === 'approved')<p>Se muestra al público cuando tu perfil está publicado y aprobado.</p>@endif
                        @if($video->rejection_reason)<p>{{ $video->rejection_reason }}</p>@endif
                        @if($video->error_message)<p role="alert">{{ $video->error_message }}</p>@endif
                        @if($video->processing_status === 'failed')
                            <form method="POST" action="{{ route('account.videos.retry', $video) }}">@csrf<button class="account-secondary-action">Reintentar procesamiento</button></form>
                        @endif
                        <form method="POST" action="{{ route('account.videos.destroy', $video) }}">@csrf @method('DELETE')<button class="account-secondary-action" type="submit">Eliminar video {{ $loop->iteration }}</button></form>
                    </div>
                </article>
            @empty
                <p>Todavía no subiste videos.</p>
            @endforelse
        </div>
    </section>
</main>
</div>
</body>
</html>
