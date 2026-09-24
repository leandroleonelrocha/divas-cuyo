# Quickstart: Panel privado de la modelo y post-login

## Prerequisites

- Dependencias Composer y assets instalados.
- MySQL existente funcionando.
- Usuario modelo con `ModelProfile` y email verificado.
- Usuario administrador con `is_admin = true` y email verificado.
- Features 001, 002 y 003 aplicadas.

## Validation flow

1. Iniciar sesión como modelo verificada y confirmar redirección a `/account`.
2. Confirmar que `/account` muestra nombre, email, WhatsApp y ubicación propios.
3. Confirmar que email, identidad, revisión y publicación aparecen como estados separados.
4. Confirmar que el enlace a identidad lleva a la sección privada existente.
5. Iniciar sesión como administradora verificada y confirmar redirección a `/admin`.
6. Intentar iniciar sesión con una modelo no verificada y confirmar que se conserva el bloqueo actual.
7. Iniciar sesión como cuenta verificada sin `ModelProfile` y confirmar redirección a `/account/incomplete-profile`, sin caer en home ni en otra cuenta.
8. Llegar al login desde otra ruta interna como modelo verificada y confirmar que igualmente termina en `/account`.
9. Llegar al login con una modelo verificada y `intended=/admin` y confirmar que `/account` prevalece.
10. Visitar `/account` como invitado y confirmar redirección al login.
11. Intentar acceder a una cuenta ajena o enviar `user_id`/`model_profile_id` y confirmar que no cambia el perfil mostrado.
12. Confirmar que no aparecen `storage_path`, nombres físicos, contraseñas, tokens ni contenido documental en el HTML.
13. Confirmar que logout finaliza la sesión y que luego `/account` vuelve a requerir autenticación.
14. Probar una pantalla de 360 px y confirmar ausencia de overflow horizontal y foco visible en controles.

## Commands

```bash
php artisan migrate
php artisan test
vendor/bin/pint --test
php artisan route:list
php artisan view:cache
php artisan optimize:clear
```

## Expected routes

- `GET /account`: panel privado de la modelo autenticada.
- `GET /account/incomplete-profile`: pantalla interna para cuentas verificadas sin `ModelProfile`.
- `GET /admin`: panel administrativo Filament para administradoras verificadas.
- Rutas existentes de `/cuenta/{user}` e identidad: se mantienen durante la transición y conservan sus Policies.

## Rollback and compatibility

No hay migraciones ni cambios de datos. Para revertir la feature se retiran la ruta/vista del panel y el redirector post-login, manteniendo intactos el login, la verificación, `/admin` y las rutas de identidad existentes.

## Final redirect flow

Después de autenticar y regenerar la sesión, `AuthenticatedUserRedirector` es la única fuente de verdad para el destino. Primero prioriza admin verificado (`is_admin = true` y email verificado) hacia `/admin`; luego envía una cuenta verificada con `ModelProfile` a `/account`; finalmente envía una cuenta verificada sin perfil a `/account/incomplete-profile`. Las modelos verificadas no respetan `intended`, aunque apunte a `/admin` u otra ruta interna. El bloqueo de cuentas no verificadas ocurre antes del redirector y se mantiene.

## Future debt

- Migrar la autorización administrativa `is_admin` a permisos específicos.
- Definir una navegación completa para fotos, videos y otros datos cuando esas features existan.
- Consolidar la URL canónica si se decide retirar los accesos antiguos con `{user}`.
- Incorporar pruebas browser/responsive automatizadas si el proyecto las adopta.
