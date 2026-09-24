@include('auth.partials.header', ['title' => 'Acceso', 'active' => 'login'])

<section class="auth-layout auth-layout--single">
    <div class="contact-form auth-card">
        <p role="alert">{{ session('error') }}</p>
        <div class="auth-links">
            <a href="{{ route('login.show') }}">Volver a iniciar sesión</a>
        </div>
    </div>
</section>

@include('auth.partials.footer')
