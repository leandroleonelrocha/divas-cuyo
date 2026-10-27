@include('auth.partials.header', ['title' => 'Recuperación', 'active' => 'login'])

<section class="auth-layout auth-layout--single">
    <div class="contact-form auth-card">
        @if (session('status'))
            <x-notify type="success" :message="session('status')" />
        @endif
        <div class="auth-links">
            <a href="{{ route('login.show') }}">Iniciar sesión</a>
        </div>
    </div>
</section>

@include('auth.partials.footer')
<script src="{{ asset('js/notifications.js') }}" defer></script>
