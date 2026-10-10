@props(['onHome' => false])

@php
    $homeUrl = route('home');
    $headerIsAdmin = auth()->user()?->isVerifiedAdmin() ?? false;
    $headerAccountUrl = $headerIsAdmin
        ? url('/admin')
        : (auth()->check() ? route('account.dashboard') : route('login.show'));
    $headerAccountLabel = $headerIsAdmin ? 'Administración' : 'Mi cuenta';
    $headerProvinces = \App\Models\Province::query()->active()->orderBy('name')->get(['slug', 'name']);
    $headerProvince = request()->query('provincia', '');
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
                {{-- <label class="dc-front-province-label" for="dc-front-province">Provincia</label>
                <select id="dc-front-province" class="dc-front-province" aria-label="Filtrar por provincia" data-home-url="{{ $homeUrl }}" onchange="var u=this.getAttribute('data-home-url');window.location.href=this.value?u+'?provincia='+encodeURIComponent(this.value):u;">
                    <option value="">Todas las provincias</option>
                    @foreach ($headerProvinces as $headerProvinceItem)
                        <option value="{{ $headerProvinceItem->slug }}" {{ $headerProvince === $headerProvinceItem->slug ? 'selected' : '' }}>{{ $headerProvinceItem->name }}</option>
                    @endforeach
                </select> --}}
                {{-- <a class="dc-front-nav-item" href="{{ $onHome ? '#novedades' : $homeUrl.'#novedades' }}"><span class="dc-front-nav-icon" aria-hidden="true">ϟ</span><span>Novedades</span></a> --}}
                <a class="dc-front-nav-item" href="{{ $onHome ? '#modelos' : $homeUrl.'#modelos' }}"><span class="dc-front-nav-icon" aria-hidden="true">◇</span><span>Modelos</span></a>
                <a class="dc-front-nav-item" href="{{ $onHome ? '#destacadas' : $homeUrl.'#destacadas' }}"><span class="dc-front-nav-icon" aria-hidden="true">☆</span><span>Destacadas</span></a>
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
                            <a href="{{ $headerAccountUrl }}">{{ $headerAccountLabel }}</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">Cerrar sesión</button>
                            </form>
                        </div>
                    </details>
                @endif
                <a class="dc-front-star" href="{{ $onHome ? '#favoritos' : $homeUrl.'#favoritos' }}" aria-label="Favoritos">★</a>
                <details class="dc-front-user-menu">
                    <summary class="dc-front-menu" aria-label="Abrir menú">☰</summary>
                    <div class="dc-front-user-menu-panel">
                        <a href="{{ $homeUrl }}#modelos">Explorar modelos</a>
                        <a href="{{ $headerAccountUrl }}">{{ $headerAccountLabel }}</a>
                        <a href="{{ route('terms.show') }}">Términos y condiciones</a>
                    </div>
                </details>
            </nav>
        </div>
    </header>
</div>