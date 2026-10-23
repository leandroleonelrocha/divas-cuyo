<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perfil incompleto · Divas Cuyo</title>
    @vite('resources/css/styles.css')
    <script src="{{ asset('js/form-submit-loading.js') }}" defer></script>
</head>
<body class="account-page registration-enrollment enrollment-page">
<main class="account-shell account-incomplete" id="contenido-principal">
    <header class="account-header" aria-label="Navegación de cuenta">
        <a href="{{ url('/') }}" class="account-brand" aria-label="Divas Cuyo">
            <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo">
            <span>DIVAS CUYO</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="account-logout">Cerrar sesión</button>
        </form>
    </header>

    <section class="account-card account-incomplete-card" aria-labelledby="incomplete-title">
        <div class="enrollment-welcome">
            <span class="enrollment-verified">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 12 2 2 4-4"/><path d="M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6l-8-3Z"/></svg>
                Cuenta verificada
            </span>
            <h1 id="incomplete-title">Bienvenida a Divas Cuyo</h1>
            <p>Elegí cómo querés presentarte y completá tus datos para habilitar tu panel.</p>
        </div>
        <form method="POST" action="{{ route('account.complete-profile') }}" data-submit-loading="Guardando perfil…" class="registration-card">
            @csrf
            @include('account.partials.enrollment-fields')
            <button type="submit" class="registration-submit" @disabled($publicationTypes->isEmpty())>Guardar y continuar <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></button>
        </form>
    </section>
</main>
<script>
    (() => {
        const province = document.getElementById('province_id');
        const locality = document.getElementById('locality_id');
        if (!province || !locality) return;

        const filterLocalities = () => {
            [...locality.options].forEach((option) => {
                if (!option.dataset.provinceId) return;
                const visible = option.dataset.provinceId === province.value;
                option.hidden = !visible;
                if (!visible && option.selected) locality.value = '';
            });
        };

        province.addEventListener('change', filterLocalities);
        filterLocalities();
    })();
</script>
</body>
</html>

