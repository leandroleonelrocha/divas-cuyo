<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perfil incompleto · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="account-page registration-enrollment">
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
        <div class="account-empty-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path d="M12 3a9 9 0 1 0 9 9 9 9 0 0 0-9-9Zm0 2a7 7 0 1 1-7 7 7 7 0 0 1 7-7Zm-1 3h2v5h-2V8Zm0 6h2v2h-2v-2Z"/></svg>
        </div>
        <p class="account-eyebrow">Cuenta verificada</p>
        <h1 id="incomplete-title">Bienvenida a Divas Cuyo</h1>
        <p>Completá tus datos y elegí tu modalidad para habilitar las funciones de tu panel.</p>
        <form method="POST" action="{{ route('account.complete-profile') }}" class="registration-card">
            @csrf
            @include('account.partials.enrollment-fields')
            <button type="submit" class="registration-submit" @disabled($publicationTypes->isEmpty())>Guardar y continuar</button>
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

