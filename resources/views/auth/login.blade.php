<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="registration-page">
    <header class="registration-header">
        <div class="registration-header-inner">
            <a class="registration-brand" href="{{ url('/') }}" aria-label="Divas Cuyo">
                <img class="header-logo" src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="Divas Cuyo">
                <span>DIVAS CUYO</span>
            </a>
            <nav class="registration-nav" aria-label="Navegación principal">
                <a href="{{ route('register.show') }}">Registro</a>
                <a class="registration-nav-current" href="{{ route('login.show') }}" aria-current="page">Ingresar</a>
                <a class="registration-nav-action" href="{{ route('login.show') }}">Ingresar</a>
            </nav>
        </div>
    </header>

    <main class="registration-main">
        <section class="registration-layout">
            <div class="registration-branding">
                <h1>Iniciar sesión</h1>
                <h2>Volvé a tu perfil</h2>
                <p class="registration-description">Ingresá para administrar tu cuenta en Divas Cuyo.</p>
                <p class="registration-existing"><a href="{{ route('register.show') }}">Crear una cuenta</a></p>
            </div>

            <form class="registration-card registration-login-card" method="POST" action="{{ route('login.store') }}">
                @csrf
                @if (session('status'))<p class="registration-feedback" role="status">{{ session('status') }}</p>@endif

                <label class="registration-field">Correo electrónico
                    <span class="registration-input-wrap">
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                    </span>
                </label>
                @error('email')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-field">Contraseña
                    <span class="registration-input-wrap">
                        <input type="password" name="password" required>
                    </span>
                </label>
                @error('password')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-consent">
                    <input type="checkbox" name="remember" value="1">
                    <span>Recordarme</span>
                </label>

                <p class="registration-login registration-login-forgot"><a href="{{ route('password.request') }}">Olvidé mi contraseña</a></p>
                <button class="registration-submit" type="submit">Ingresar</button>
                <div class="registration-login-divider"><span>¿No tenés una cuenta?</span></div>
                <p class="registration-login"><a href="{{ route('register.show') }}">Crear una cuenta</a></p>
            </form>
        </section>
    </main>
</body>
</html>
