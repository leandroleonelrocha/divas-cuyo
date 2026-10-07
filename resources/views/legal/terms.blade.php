<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Términos y condiciones · Divas Cuyo</title>
    @vite('resources/css/styles.css')
</head>
<body class="registration-page">
    <header class="registration-header">
        <div class="registration-header-inner">
            <a class="registration-brand" href="{{ url('/') }}" aria-label="Divas Cuyo">
                <img class="header-logo" src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="Divas Cuyo">
                <span>DIVAS CUYO</span>
            </a>
        </div>
    </header>

    <main class="legal-main">
        <article class="legal-document" aria-labelledby="terms-title">
            <p class="legal-important"><strong>IMPORTANTE</strong> — Leer antes de continuar.</p>
            <h1 id="terms-title">Términos y condiciones de permanencia y uso de imagen Divas Cuyo</h1>
            <p>Al formar parte de Divas Cuyo, la profesional declara conocer y aceptar las siguientes condiciones:</p>

            <section aria-labelledby="terms-identity">
                <h2 id="terms-identity">1. Confidencialidad y Protección de Identidad</h2>
                <p>Divas Cuyo trabaja bajo lineamientos estrictos de privacidad y resguardo de identidad.</p>
                <p>No publicamos redes sociales ni canales personales de nuestras escorts y creadoras.</p>
                <p>No publicamos nombres reales ni usuarios de redes sociales.</p>
                <p>Queda prohibida la exposición de cualquier dato personal que pueda comprometer la identidad, privacidad o seguridad de las profesionales publicadas en la plataforma.</p>
            </section>

            <section aria-labelledby="terms-content">
                <h2 id="terms-content">2. Lineamientos sobre el Contenido Publicado</h2>
                <p>Ningún material será publicado en la página ni en los canales oficiales si contiene datos personales, referencias a cuentas privadas o información que vulnere las políticas internas de confidencialidad.</p>
                <p>Todo contenido es previamente revisado y aprobado conforme a los estándares institucionales de la marca.</p>
            </section>

            <section aria-labelledby="terms-image">
                <h2 id="terms-image">3. Uso de Imagen en Redes Sociales</h2>
                <p>El material difundido en la red social oficial X será exclusivamente contenido institucional de Divas Cuyo y contará con sello de agua como medida de protección de marca y resguardo de imagen.</p>
                <p>Solo serán expuestas en redes sociales aquellas profesionales que hayan firmado el consentimiento legal correspondiente para el uso de imagen en plataformas digitales.</p>
                <p>En caso de que una profesional no autorice la difusión de su imagen en redes sociales, se respetará su decisión y únicamente se publicará el enlace directo a su perfil dentro de la página oficial.</p>
            </section>

            <section aria-labelledby="terms-membership">
                <h2 id="terms-membership">4. Permanencia en la Plataforma</h2>
                <p>La permanencia dentro de Divas Cuyo implica el cumplimiento de estas políticas internas, el respeto por los estándares profesionales de la marca y una conducta acorde a la imagen institucional del proyecto.</p>
                <p>Divas Cuyo se reserva el derecho de suspender o finalizar la publicación de cualquier perfil que incumpla los presentes términos y condiciones.</p>
            </section>

            <section aria-labelledby="terms-respect">
                <h2 id="terms-respect">5. Convivencia Profesional y Respeto Entre Colegas</h2>
                <p>Divas Cuyo no promueve ni infunde conflictos entre colegas.</p>
                <p>Nuestra visión es construir un espacio de trabajo colaborativo, respetuoso y profesional.</p>
                <p>No se tolerarán faltas de respeto, agresiones, difamaciones ni conductas que generen enfrentamientos entre profesionales publicadas en la plataforma.</p>
                <p>Si bien Divas Cuyo actúa como medio de publicación y difusión, no avalará comportamientos que afecten el buen clima de trabajo o la imagen institucional del proyecto.</p>
            </section>

            <section aria-labelledby="terms-authenticity">
                <h2 id="terms-authenticity">6. Marco Institucional, Canales Oficiales y Garantía de Autenticidad</h2>
                <p>Divas Cuyo opera exclusivamente a través de sus canales oficiales: Canal oficial de Telegram.</p>
                <p>No utilizamos Instagram TikTok ni otras plataformas digitales.</p>
                <p>La marca mantiene una estructura independiente de las actividades personales o digitales de cada profesional fuera del sitio, con el objetivo de evitar conflictos externos o situaciones legales que no correspondan a la plataforma.</p>
                <p>Asimismo, todo el contenido publicado en la página (fotografías y videos) es previamente recibido, evaluado y validado por el equipo interno de verificación.</p>
                <p>Divas Cuyo implementa un proceso de validación de autenticidad de perfiles antes de su publicación.</p>
                <p>No se admiten imágenes generadas por inteligencia artificial, material manipulado con fines engañosos ni contenido que distorsione la identidad real de la profesional.</p>
                <p>Nuestra política se basa en la integridad, la transparencia y la confianza, garantizando tanto a las profesionales como a los visitantes de nuestra página web.</p>
            </section>

            <p><strong>Atte:&#64;DivasCuyo</strong></p>
            <a class="legal-register-link" href="{{ route('register.show') }}">Ir al registro</a>
        </article>
    </main>
</body>
</html>
