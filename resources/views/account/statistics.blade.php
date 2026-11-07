<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Mis estadísticas · Divas Cuyo</title>
    @vite('resources/css/styles.css')
    <script src="{{ asset('js/notifications.js') }}" defer></script>
</head>
<body class="account-mobile-navigation account-page account-dashboard-page">
<div class="account-app-shell">
    @include('account.partials.sidebar', ['active' => 'stats'])
    <main class="account-main" id="contenido-principal">
        @include('account.partials.topbar', ['label' => $profile->stage_name ?: $profile->name])
        <header class="dashboard-welcome"><div><h1>Mis estadísticas</h1><p>Conocé la actividad de tu perfil público.</p></div></header>
        <section class="dashboard-panel">
            <form method="GET" action="{{ route('account.statistics') }}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
                <label for="stats-period">Período</label>
                <select id="stats-period" name="period" style="min-height:44px">
                    @foreach (['7' => 'Últimos 7 días', '30' => 'Últimos 30 días', 'month' => 'Este mes'] as $value => $label)
                        <option value="{{ $value }}" @selected($stats['period'] === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="account-secondary-action" type="submit" style="min-height:44px">Consultar</button>
            </form>
            @include('statistics.summary')
        </section>
    </main>
</div>
</body>
</html>
