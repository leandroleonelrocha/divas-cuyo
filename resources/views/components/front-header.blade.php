@props(['onHome' => false])

@php
    $homeUrl = route('home');
@endphp

<div class="dc-front-header-wrap">
    <div class="dc-front-topbar"></div>
    <header class="dc-front-header">
        <div class="dc-front-header-inner">
            <a href="{{ $homeUrl }}" class="dc-front-brand" aria-label="Divas Cuyo">
                <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="Divas Cuyo">
                <span>DIVAS CUYO</span>
            </a>
            <nav class="dc-front-navigation" aria-label="Navegación principal">
                {{-- <a class="dc-front-nav-item" href="{{ $onHome ? '#novedades' : $homeUrl.'#novedades' }}"><span class="dc-front-nav-icon" aria-hidden="true">ϟ</span><span>Novedades</span></a> --}}
                <a class="dc-front-nav-item" href="{{ $onHome ? '#videos' : $homeUrl.'#videos' }}"><span class="dc-front-nav-icon" aria-hidden="true">▶</span><span>Videos</span></a>
                <a class="dc-front-nav-item" href="{{ $onHome ? '#llamadas' : $homeUrl.'#llamadas' }}"><span class="dc-front-nav-icon" aria-hidden="true">▣</span><span>VideoLlamadas</span></a>
                @if (auth()->guest())
                    <a class="dc-front-create" href="{{ route('register.show') }}">Crear perfil</a>
                @else
                    @php
                        $frontUser = auth()->user();
                        $frontProfile = $frontUser->modelProfile;
                        $frontAvatar = $frontProfile?->currentApprovedPhotos()->first();
                    @endphp
                    <details class="dc-front-user-menu">
                        <summary aria-label="Abrir menú de usuario">
                            <span class="dc-front-user-avatar">
                                @if ($frontAvatar)
                                    <img src="{{ route('account.photos.file', [$frontAvatar, 'thumbnail']) }}" alt="">
                                @else
                                    {{ strtoupper(substr($frontUser->name, 0, 1)) }}
                                @endif
                            </span>
                            <span>{{ $frontUser->name }}</span>
                        </summary>
                        <div class="dc-front-user-menu-panel">
                            <a href="{{ route('account.dashboard') }}">Mi cuenta</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">Cerrar sesión</button>
                            </form>
                        </div>
                    </details>
                @endif
                <a class="dc-front-star" href="{{ $onHome ? '#favoritos' : $homeUrl.'#favoritos' }}" aria-label="Favoritos">★</a>
                <a class="dc-front-menu" href="{{ $onHome ? '#menu' : $homeUrl.'#menu' }}" aria-label="Menú">☰</a>
            </nav>
        </div>
    </header>
</div>