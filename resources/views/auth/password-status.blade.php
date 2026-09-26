@include('auth.partials.header', ['title' => 'Recuperación', 'active' => 'login'])

<section class="auth-layout auth-layout--single">
    <div class="contact-form auth-card">
        <p role="status">{{ session('status') }}</p>
        <div class="auth-links">
            <a href="{{ route('login.show') }}">Iniciar sesión</a>
        </div>
    </div>
</section>

@include('auth.partials.footer')
