# Aviso legal obligatorio

El grupo público de `routes/web.php` exige `RequireLegalAcceptance` para inicio,
perfiles, fotos y páginas legales. Toda nueva ruta que entregue contenido público
debe agregarse a ese grupo. Login, registro, verificación, recuperación y cuentas
conservan sus controles actuales. Las fotos reales permanecen en almacenamiento
privado y se sirven únicamente por la ruta protegida.

La pantalla `/aviso-legal` no incluye perfiles ni imágenes de anunciantes. Funciona
sin JavaScript, con formularios POST y CSRF. Rechazar reemplaza cualquier aceptación
anterior y conduce a `/acceso-denegado` (403). Desde allí se puede volver a leer el
aviso y aceptarlo explícitamente. Atrás o ESC no conceden acceso: cada solicitud de
contenido vuelve a validar la cookie. No se manipula el historial del navegador. Un pequeño script en las páginas públicas
recarga las restauraciones de historial (bfcache) para repetir la validación;
el control de acceso principal sigue siendo del servidor.
Las respuestas públicas y legales usan `private, no-store` para evitar reutilizar
contenido desde cachés compartidas o HTTP. El navegador siempre controla su propio
historial y las copias que el usuario ya haya descargado.

## Configuración

- `LEGAL_DISCLAIMER_VERSION=1`: incrementar cada vez que cambie el texto de
  `resources/views/legal/access.blade.php`. Invalida aceptaciones anteriores.
- `LEGAL_ACCEPTANCE_DAYS=30`: duración de aceptación y rechazo; también se verifica
  el vencimiento en el servidor, aunque un cliente conserve una cookie vencida.
- `LEGAL_COOKIE_SECURE=true`: requiere HTTPS. Solo para desarrollar con MAMP sobre
  HTTP usar `LEGAL_COOKIE_SECURE=false` en el `.env` local.

Después de cambiar configuración en un despliegue que la cachea, ejecutar
`php artisan config:cache`. Servir producción por HTTPS, sin cachear las rutas
protegidas en CDN/proxy. No publicar el almacenamiento privado por alias del servidor.

Laravel cifra y autentica la cookie, que es HttpOnly, SameSite=Lax, de dominio
local y path `/`. Solo contiene estado, versión y vencimiento; no identifica al
visitante. La URL solicitada se conserva temporalmente en la sesión para regresar
al destino tras aceptar. Los recursos estáticos de CSS y logo siguen accesibles.

## Verificación

`php artisan test --compact tests/Feature/LegalAccessTest.php`

Cubre visitante nuevo, acceso directo a perfiles/fotos, JSON, aceptación, rechazo,
cookies alteradas o vencidas, cambio de versión, configuración de duración,
redirecciones seguras y exclusión de autenticación/administración. Las pruebas
existentes de perfiles usan `AcceptsLegalDisclaimer` para conservar el control
real del middleware y probar contenido con aceptación válida.
