<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="auth-page">
    <div class="site-top"></div>
    <nav class="site-nav" aria-label="Navegación principal">
        <a class="site-logo" href="{{ url('/') }}" aria-label="Divas Cuyo">
            <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="Divas Cuyo">
        </a>
        <div class="site-links">
            @if ($active === 'register')
                <a href="{{ route('register.show') }}" aria-current="page">Registro</a>
                <a href="{{ route('login.show') }}">Ingresar</a>
            @else
                <a href="{{ route('login.show') }}" aria-current="page">Ingresar</a>
                <a href="{{ route('register.show') }}">Registro</a>
            @endif
        </div>
        <a class="publish" href="{{ $active === 'register' ? route('login.show') : route('register.show') }}">
            {{ $active === 'register' ? 'Ingresar' : 'Crear cuenta' }}
        </a>
    </nav>
    <main class="auth-main">
