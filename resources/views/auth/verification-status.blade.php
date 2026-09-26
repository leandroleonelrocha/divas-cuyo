<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación · Divas Cuyo</title>
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
                <a class="registration-nav-current" href="{{ route('login.show') }}">Ingresar</a>
                <a class="registration-nav-action" href="{{ route('login.show') }}">Ingresar</a>
            </nav>
        </div>
    </header>

    <main class="registration-main">
        <section class="registration-layout">
            <div class="registration-branding">
                @if (session('status'))<p class="registration-feedback" role="status">{{ session('status') }}</p>@endif
                @if (session('error'))<p class="registration-feedback" role="alert">{{ session('error') }}</p>@endif
                <h1>Verificá tu correo electrónico</h1>
                <h2>Ya casi terminamos</h2>
                <p class="registration-description">Te enviamos las instrucciones{{ $email ? ' a '.$email : '' }}. El enlace es válido durante 24 horas.</p>
                <p class="registration-existing"><a href="{{ route('login.show') }}">Ir a iniciar sesión</a></p>
            </div>

            <form class="registration-card registration-verification-card" method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <div class="registration-card-heading registration-card-heading-visible">
                    <h2>Reenviar verificación</h2>
                    <p>¿No recibiste el correo? Podés solicitarlo nuevamente.</p>
                </div>

                <label class="registration-field">Correo electrónico
                    <span class="registration-input-wrap">
                        <input type="email" name="email" value="{{ old('email', $email) }}" required>
                    </span>
                </label>
                @error('email')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <button class="registration-submit" type="submit">Reenviar verificación</button>
            </form>
        </section>
    </main>
</body>
</html>
