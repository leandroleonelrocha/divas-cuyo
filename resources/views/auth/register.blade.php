<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta · Divas Cuyo</title>
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
                <a href="{{ route('login.show') }}">Ingresar</a>
                <a class="registration-nav-action" href="{{ route('register.show') }}" aria-current="page">Crear perfil</a>
            </nav>
        </div>
    </header>

    <main class="registration-main">
        <section class="registration-layout">
            <div class="registration-branding">
                <h1>Crear cuenta</h1>
                <h2>Sumate a Divas Cuyo</h2>
                <p class="registration-description">Completá tus datos para crear tu perfil de modelo.</p>
                <p class="registration-existing"><a href="{{ route('login.show') }}">Ya tengo una cuenta</a></p>
            </div>

            <form class="registration-card" method="POST" action="{{ route('register.store') }}">
                @csrf
                @if (session('error'))<p class="registration-feedback" role="alert">{{ session('error') }}</p>@endif

                <div class="registration-card-heading">
                    <h2>Crear cuenta</h2>
                    <p>Completá tus datos para crear tu perfil de modelo.</p>
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

                <label class="registration-field">Nombre público
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5Z"/></svg>
                        <input type="text" name="name" value="{{ old('name') }}" required>
                    </span>
                </label>
                @error('name')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-field">WhatsApp
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.5 3h3l1.2 4-1.8 1.5a13.5 13.5 0 0 0 6.6 6.6l1.5-1.8 4 1.2v3c0 1.1-.9 2-2 2C10.7 19.5 4.5 13.3 4.5 5a2 2 0 0 1 2-2Z"/></svg>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" required>
                    </span>
                </label>
                @error('whatsapp')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-field">Ubicación
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 22s7-6.1 7-12A7 7 0 0 0 5 10c0 5.9 7 12 7 12Zm0-9a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>
                        <input type="text" name="location" value="{{ old('location') }}" required>
                    </span>
                </label>
                @error('location')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-consent">
                    <input type="checkbox" name="terms_accepted" value="1" required>
                    <span>Acepto los <a href="#terminos">términos y condiciones</a>.</span>
                </label>
                @error('terms_accepted')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-consent">
                    <input type="checkbox" name="privacy_accepted" value="1" required>
                    <span>Acepto la <a href="#privacidad">política de privacidad</a>.</span>
                </label>
                @error('privacy_accepted')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <button class="registration-submit" type="submit">Crear cuenta <span aria-hidden="true">→</span></button>
                <div class="registration-login-divider"><span>¿Ya tenés una cuenta?</span></div>
                <p class="registration-login"><a href="{{ route('login.show') }}">Ingresar</a></p>
            </form>
        </section>
    </main>
</body>
</html>
