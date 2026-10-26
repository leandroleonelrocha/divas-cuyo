<aside class="account-sidebar" aria-label="Navegación de mi cuenta">
    <a href="{{ route('account.dashboard') }}" class="account-brand" aria-label="Divas Cuyo, Mi perfil">
        <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo"><span>Divas<span>Cuyo</span></span>
    </a>
    <nav class="account-sidebar-nav">
        <a class="{{ ($active ?? '') === 'profile' ? 'is-active' : '' }}" href="{{ route('account.dashboard') }}"><span>⌂</span> Mi perfil</a>
        <a class="{{ ($active ?? '') === 'photos' ? 'is-active' : '' }}" href="{{ route('account.photos.index') }}"><span>▧</span> Mis fotos</a>
        <a class="{{ ($active ?? '') === 'videos' ? 'is-active' : '' }}" href="{{ route('account.videos.index') }}"><span>▣</span> Mis videos</a>
        <a class="{{ ($active ?? '') === 'services' ? 'is-active' : '' }}" href="{{ route('account.services.edit') }}"><span>◉</span> Servicios y tipo de publicación</a>
        <a class="{{ ($active ?? '') === 'stats' ? 'is-active' : '' }}" href="{{ route('account.dashboard') }}#estadisticas"><span>▥</span> Estadísticas</a>
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
