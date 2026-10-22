<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#101216">
    <title>{{ $denied ? 'Acceso denegado' : 'Aviso legal' }} · Divas Cuyo</title>
    <link rel="stylesheet" href="{{ asset('css/model-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/legal-access.css') }}">
</head>
<body class="model-home legal-access">
<main class="legal-access-card" aria-labelledby="legal-title">
    <img class="legal-access-logo" src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="Divas Cuyo" width="180" height="90">
    @if ($denied)
        <h1 id="legal-title">Acceso denegado</h1>
        <p>Para acceder a Divas Cuyo debés ser mayor de 18 años y aceptar expresamente el aviso legal.</p>
        <a class="legal-button legal-secondary" href="{{ route('legal.show') }}">Volver al aviso legal</a>
    @else
        <h1 id="legal-title">Aviso legal y acceso para mayores de 18 años</h1>
        <p class="legal-intro">Leé el aviso antes de continuar. El ingreso al sitio no implica aceptación.</p>
        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif
        <section class="legal-access-text" tabindex="0" aria-labelledby="notice-heading">
            <h2 id="notice-heading">Aviso legal</h2>
            <p>Divas Cuyo es un medio de publicidad independiente destinado a la difusión de perfiles de escorts independientes, quienes actúan por voluntad propia y de manera autónoma.</p>
            <p>Divas Cuyo no apoya, promueve ni mantiene vínculo alguno con establecimientos que funcionen bajo la apariencia de prostíbulos, whiskerías, cabarets, clubes nocturnos o locales clandestinos, ni con ninguna actividad que infrinja el Código de Faltas vigente, la Ley N.º 10.060 y su Decreto de Promulgación N.º 550/12.</p>
            <p>El sitio no establece relaciones laborales, económicas ni contractuales con las personas publicadas, ni con terceros —ya sean personas físicas o jurídicas— que se encuentren presentes en el mismo. La publicación de datos, imágenes, videos y descripciones se realiza con el consentimiento expreso de cada anunciante.</p>
            <p>Toda relación, acuerdo o encuentro que se establezca entre la anunciante y terceros es de exclusiva responsabilidad de las partes involucradas, quedando Divas Cuyo completamente ajeno a dichas interacciones.</p>
            <p>Divas Cuyo no percibe, administra ni estimula pagos, gastos o beneficios económicos derivados de los servicios ofrecidos por las personas publicadas ni por terceros.</p>
            <p>El sitio manifiesta su total apoyo a la prevención y erradicación de la trata de personas y de toda forma de explotación sexual y laboral en el territorio de la Provincia de Mendoza y de la República Argentina.</p>
            <p>Asimismo, quienes conforman Divas Cuyo expresan su adhesión y respeto a la normativa vigente, incluida la Ley Nacional N.º 26.842 y sus reformas.</p>
        </section>
        <form method="POST" action="{{ route('legal.decide') }}" class="legal-actions">
            @csrf
            <input type="hidden" name="version" value="{{ config('legal.version') }}">
            <button class="legal-button legal-primary" name="decision" value="accept" type="submit">Soy mayor de 18 años y acepto</button>
            <button class="legal-button legal-secondary" name="decision" value="reject" type="submit">No acepto</button>
        </form>
    @endif
</main>
</body>
</html>
