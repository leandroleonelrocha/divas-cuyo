<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña · Divas Cuyo</title>
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
                <a class="registration-nav-current" href="{{ route('login.show') }}">Ingresar</a>
                <a class="registration-nav-action" href="{{ route('register.show') }}">Crear perfil</a>
            </nav>
        </div>
    </header>

    <main class="registration-main">
        <section class="registration-layout">
            <div class="registration-branding">
                <h1>Recuperar contraseña</h1>
                <h2>Volvé a ingresar</h2>
                <p class="registration-description">Te enviaremos un enlace para crear una nueva contraseña y recuperar el acceso a tu cuenta.</p>
                <p class="registration-existing"><a href="{{ route('login.show') }}">Volver a iniciar sesión</a></p>
            </div>

            <form class="registration-card registration-recovery-card" method="POST" action="{{ route('password.email') }}">
                @csrf
                @if (session('status'))<p class="registration-feedback" role="status">{{ session('status') }}</p>@endif

                <div class="registration-card-heading registration-card-heading-visible">
                    <h2>¿Olvidaste tu contraseña?</h2>
                    <p>Ingresá tu correo y te enviaremos las instrucciones.</p>
                </div>

                <label class="registration-field">Correo electrónico
                    <span class="registration-input-wrap">
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                    </span>
                </label>
                @error('email')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <button class="registration-submit" type="submit">Enviar instrucciones</button>
            </form>
        </section>
    </main>
</body>
</html>
