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
        <a href="{{ route('account.pending-review') }}" class="account-primary-action">Consultar estado</a>
    </section>
</main>
</body>
</html>
