# Quickstart: Model Photos

## Prerequisites

- PHP, Composer, Node y MySQL configurados según el proyecto.
- Features 001–004 aplicadas.
- Una modelo autenticada, verificada y con ModelProfile.
- Un administrador con is_admin = true y email verificado.
- El asset existente public/images/logo-divas-cuyo.webp disponible para watermark.
- Extensión GD disponible si se adopta el driver GD de Intervention Image.
- Disk model_photos configurado como privado y escribible.

## Configuration checklist

Confirmar en config/model-photos.php:

- maximum photos = 5;
- max size = 5120 KB;
- allowed MIME = image/jpeg, image/png, image/webp;
- min dimensions = 800x800;
- max input dimensions = 5000x5000;
- processed max side = 2000;
- thumbnail = 400x400 crop/cover centered;
- watermark asset, opacity, width and position;
- disk = model_photos.

Confirmar que el disk no sea expuesto mediante public/storage ni asset().

## Model flow

1. Iniciar sesión como modelo verificada.
2. Abrir /account/photos.
3. Confirmar estado vacío si no existen fotos y que no se solicitan cinco obligatoriamente.
4. Cargar JPEG, PNG y WebP válidos.
5. Confirmar que cada ítem queda pending y muestra “En revisión”.
6. Confirmar que el original no aparece en HTML, responses ni URLs públicas.
7. Reordenar las fotos propias y recargar para confirmar positions.
8. Seleccionar una foto aprobada como principal.
9. Reemplazar una foto pending, rejected y approved.
10. En el reemplazo approved, confirmar que la versión anterior continúa vigente mientras la nueva
    está pending.
11. Eliminar la principal y confirmar selección de la primera approved por position o ausencia de
    principal.
12. Confirmar rejection_reason visible sólo para la propietaria.

## Validation failures

Probar y confirmar rechazo sin registros o archivos huérfanos para:

- video, PDF, SVG y archivo arbitrario;
- MIME falso;
- archivo mayor a 5 MB;
- imagen menor a 800x800;
- imagen mayor a 5000x5000;
- contenido de imagen corrupto;
- sexta foto;
- model_profile_id manipulado;
- foto de otra modelo;
- path traversal;
- status enviado desde frontend;
- reorder con IDs ajenos o posiciones inválidas.

## Image variants

Para una carga válida, confirmar:

- original privado sin watermark;
- processed privada, normalizada y con lado máximo 2000;
- thumbnail privada de 400x400 con crop centrado;
- public privada/controlada con watermark;
- las cuatro variantes bajo el disk model_photos;
- fallo intermedio elimina temporales sin retirar current version anterior.

## Administrative flow

1. Iniciar sesión como admin verificado y abrir /admin/model-profiles.
2. Abrir el detalle de una modelo.
3. Ver la sección de fotografías.
4. Para un reemplazo approved, comparar pending con current approved.
5. Aprobar una pending y confirmar pending → approved, reviewed_at, reviewed_by y current_version_id.
6. Rechazar otra pending sin motivo y confirmar error.
7. Rechazar con motivo y confirmar pending → rejected.
8. Confirmar que el motivo se muestra a la modelo.
9. Confirmar que identidad, revisión y publicación no cambian.
10. Repetir como usuario normal y admin no verificado y confirmar 403.

## Commands

~~~bash
php artisan migrate
php artisan test
vendor/bin/pint --test
php artisan route:list --path=account/photos
php artisan route:list --path=admin
php artisan view:cache
CACHE_STORE=file php artisan optimize:clear
~~~

No se debe ejecutar `storage:link` para el disk privado: las fotografías no deben quedar bajo
`public/storage` ni tener una ruta pública directa.

## Expected behavior

- Máximo cinco fotos lógicas por ModelProfile.
- Todas requieren moderación.
- Sólo current approved puede ser principal.
- Sólo variantes public/thumbnail de una versión approved podrán ser usadas por una futura
  superficie pública.
- La aprobación de una foto no publica el perfil ni cambia identity_status o review_status.

## Regression

Ejecutar la suite completa y confirmar features 001–004: registro/login/logout, identidad
privada, moderación Filament y panel /account. Confirmar que identity_private y model_photos
sean disks separados y que no se mezclen sus paths.
