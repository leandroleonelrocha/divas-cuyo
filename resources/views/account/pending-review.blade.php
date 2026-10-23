<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscripción en revisión · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="account-page">
<main class="account-shell account-incomplete">
    <header class="account-header">
        <span class="account-brand">DIVAS CUYO</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="account-logout">Cerrar sesión</button>
        </form>
    </header>
    <section class="account-card account-incomplete-card" aria-labelledby="review-title">
        <h1 id="review-title">Tu perfil está en revisión</h1>
        <p role="status">Recibimos tus datos. Podrás acceder al panel cuando el administrador apruebe tu inscripción.</p>
        <div class="account-review-status" role="status">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            <span>Pendiente de aprobación</span>
        </div>
        <p class="account-review-hint">Podés actualizar esta página para comprobar si tu inscripción ya fue aprobada.</p>
    </section>
</main>
</body>
</html>
