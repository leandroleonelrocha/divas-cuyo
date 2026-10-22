<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="registration-page registration-enrollment">
    <header class="registration-header">
        <div class="registration-header-inner">
            <a class="registration-brand" href="{{ url('/') }}" aria-label="Divas Cuyo">
                <img class="header-logo" src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="Divas Cuyo">
                <span>DIVAS CUYO</span>
            </a>
            <nav class="registration-nav" aria-label="Navegación principal">
                <a href="{{ route('login.show') }}">Ingresar</a>
                <a class="registration-nav-action" href="{{ route('register.show') }}" aria-current="page">Crear perfil</a>
            </nav>
        </div>
    </header>

    <main class="registration-main">
        <section class="registration-layout">
            <div class="registration-branding">
                <h1>Solicitud de incorporación</h1>
                <h2>Publicitá tu perfil de forma independiente, segura y discreta.</h2>
                <p class="registration-description">En Divas Cuyo te ofrecemos un espacio pensado para destacar tu imagen, mostrar tu estilo y conectar con personas interesadas, manteniendo siempre el control sobre tu contenido y tu información.</p>
                <p class="registration-description">Nuestro criterio es mantener una estructura clara, equitativa y profesional para todas las integrantes de la plataforma.</p>
                <p class="registration-description"><strong>Completá el formulario y empezá a formar parte de nuestra web.</strong></p>
                <p class="registration-existing"><a href="{{ route('login.show') }}">Ya tengo una cuenta</a></p>
            </div>

            <form class="registration-card" method="POST" action="{{ route('register.store') }}">
                @csrf
                @if (session('error'))<p class="registration-feedback" role="alert">{{ session('error') }}</p>@endif

                <div class="registration-card-heading">
                    <h2>Crear cuenta</h2>
                    <p>Creá tu cuenta. Después de verificar tu correo podrás completar tu perfil.</p>
                </div>

                <label class="registration-field">Correo electrónico
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3.5 5h17A1.5 1.5 0 0 1 22 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-17A1.5 1.5 0 0 1 2 17.5v-11A1.5 1.5 0 0 1 3.5 5Zm.5 3v9h16V8l-8 5-8-5Zm1.8-1L12 10.7 18.2 7H5.8Z"/></svg>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                    </span>
                </label>
                @error('email')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-field">Contraseña
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 10V7a5 5 0 0 1 10 0v3h1.5A1.5 1.5 0 0 1 20 11.5v8a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 19.5v-8A1.5 1.5 0 0 1 5.5 10H7Zm2 0h6V7a3 3 0 0 0-6 0v3Z"/></svg>
                        <input type="password" name="password" required minlength="8">
                    </span>
                </label>
                @error('password')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-field">Repetir contraseña
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 10V7a5 5 0 0 1 10 0v3h1.5A1.5 1.5 0 0 1 20 11.5v8a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 19.5v-8A1.5 1.5 0 0 1 5.5 10H7Zm2 0h6V7a3 3 0 0 0-6 0v3Z"/></svg>
                        <input type="password" name="password_confirmation" required minlength="8">
                    </span>
                </label>

                <p class="registration-publication-note">Para publicar, primero debés verificar tu correo, completar tu perfil y obtener la aprobación.</p>
                <button class="registration-submit" type="submit">Crear cuenta <span aria-hidden="true">→</span></button>
                <div class="registration-login-divider"><span>¿Ya tenés una cuenta?</span></div>
                <p class="registration-login"><a href="{{ route('login.show') }}">Ingresar</a></p>
            </form>
        </section>
    </main>

</body>
</html>
