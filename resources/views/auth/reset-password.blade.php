@include('auth.partials.header', ['title' => 'Nueva contraseña', 'active' => 'login'])

<section class="auth-layout auth-layout--single">
    <form class="contact-form auth-card" method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <h1>Elegí una nueva contraseña</h1>
        <label>Correo electrónico <input type="email" name="email" value="{{ old('email', $email) }}" required></label>
        @error('email')<p role="alert">{{ $message }}</p>@enderror
        <label>Nueva contraseña <input type="password" name="password" minlength="8" required></label>
        @error('password')<p role="alert">{{ $message }}</p>@enderror
        <label>Repetir contraseña <input type="password" name="password_confirmation" minlength="8" required></label>
        <button type="submit">Guardar contraseña</button>
    </form>
</section>

@include('auth.partials.footer')
