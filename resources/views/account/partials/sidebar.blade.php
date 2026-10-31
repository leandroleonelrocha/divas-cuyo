@php
    $preparingProfile = auth()->user()?->modelProfile?->identity_status !== 'approved'
        || auth()->user()?->modelProfile?->review_status !== 'approved';
    $menuActive = match (true) {
        request()->routeIs('account.dashboard') => request('section') === 'payments' ? 'payments' : 'home',
        request()->routeIs('account.profile.edit') => 'data',
        request()->routeIs('identity.*') => 'identity',
        request()->routeIs('account.photos.*') => 'photos',
        request()->routeIs('account.videos.*') => 'videos',
        request()->routeIs('account.services.*') => 'services',
        default => '',
    };
@endphp
<aside class="account-sidebar" aria-label="Navegación de mi cuenta">
    <a href="{{ route('account.dashboard') }}" class="account-brand" aria-label="Divas Cuyo, Mi perfil">
        <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo"><span>Divas<span>Cuyo</span></span>
    </a>
    <nav class="account-sidebar-nav">
        <a class="{{ $menuActive === 'home' ? 'is-active' : '' }}" @if ($menuActive === 'home') aria-current="page" @endif href="{{ route('account.dashboard') }}"><span>⌂</span> Inicio</a>
        @if (! $preparingProfile)
        <a class="{{ $menuActive === 'photos' ? 'is-active' : '' }}" @if ($menuActive === 'photos') aria-current="page" @endif href="{{ route('account.photos.index') }}"><span>▧</span> Mis fotos</a>
        <a class="{{ $menuActive === 'videos' ? 'is-active' : '' }}" @if ($menuActive === 'videos') aria-current="page" @endif href="{{ route('account.videos.index') }}"><span>▣</span> Mis videos</a>
        <a class="{{ $menuActive === 'services' ? 'is-active' : '' }}" @if ($menuActive === 'services') aria-current="page" @endif href="{{ route('account.services.edit') }}"><span>◉</span> Mis servicios</a>
        {{-- <a class="{{ ($active ?? '') === 'stats' ? 'is-active' : '' }}" href="{{ route('account.dashboard') }}#estadisticas"><span>▥</span> Estadísticas</a> --}}
        @endif
        <a class="{{ $menuActive === 'data' ? 'is-active' : '' }}" @if ($menuActive === 'data') aria-current="page" @endif href="{{ route('account.profile.edit') }}"><span>✎</span> Mis datos</a>
        <a class="{{ $menuActive === 'identity' ? 'is-active' : '' }}" @if ($menuActive === 'identity') aria-current="page" @endif href="{{ route('identity.show', auth()->user()) }}"><span>♢</span> Mi identidad</a>
        @if ($managementReady ?? false)
            <a class="{{ $menuActive === 'payments' ? 'is-active' : '' }}" @if ($menuActive === 'payments') aria-current="page" @endif href="{{ route('account.dashboard', ['section' => 'payments']) }}"><span>▤</span> Mis pagos</a>
        @endif
    </nav>
    <nav class="account-sidebar-bottom">
        <a href="{{ route('terms.show') }}"><span>?</span> Centro de ayuda</a>
        <a href="{{ route('account.profile.edit') }}"><span>⚙</span> Configuración</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><span>↪</span> Cerrar sesión</button></form>
    </nav>
</aside>
<script defer>
(() => {
    const highlightByHash = () => {
        const hash = window.location.hash;
        if (!hash) return;
        document.querySelectorAll('.account-sidebar-nav a').forEach((link) => {
            link.classList.toggle('is-active', (link.getAttribute('href') || '').endsWith(hash));
        });
    };
    highlightByHash();
    window.addEventListener('hashchange', highlightByHash);
})();
</script>
