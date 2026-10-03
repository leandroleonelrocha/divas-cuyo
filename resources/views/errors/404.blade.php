<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página no encontrada · Divas Cuyo</title>
    @vite('resources/css/styles.css')
    @include('components.front-header-styles')
</head>
<body class="account-page public-model-page">
<a class="public-profile-skip-link" href="#contenido-principal">Saltar al contenido principal</a>
<x-front-header />
<div class="account-shell public-profile-shell">
    <main id="contenido-principal" class="public-profile-error" tabindex="-1">
        <section class="account-card" aria-labelledby="not-found-title">
            <h1 id="not-found-title">Página no encontrada</h1>
            <p>No encontramos la página que buscás.</p>
        </section>
    </main>
</div>
</body>
</html>
