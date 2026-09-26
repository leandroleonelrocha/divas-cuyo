# Implementation Plan: Model Photos

**Branch**: `005-model-photos` | **Date**: 2026-09-17 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/005-model-photos/spec.md`

## Summary

La feature agrega una galería privada para modelos bajo `/account/photos` y una sección de
moderación dentro del detalle de `ModelProfile` en Filament. `model_photos` representa cada
ítem estable de la galería y `model_photo_versions` conserva las cargas y reemplazos. Esta
separación permite que una versión aprobada continúe vigente mientras un reemplazo queda
pendiente.

Los originales, las variantes procesadas, los thumbnails y las versiones con marca de agua se
guardan en el disk privado `model_photos` mediante Laravel Storage/Flysystem. El original nunca
es público; una futura superficie pública sólo podrá consultar la variante watermarked de la
versión aprobada actual. El procesamiento genera una imagen normalizada de hasta 2000 px, un
thumbnail de 400x400 con crop centrado y una variante pública con el logo existente de Divas Cuyo.

La lógica de upload, reemplazo, eliminación, orden, principalidad, procesamiento, storage y
moderación se centraliza en servicios y Policies. Blade sigue siendo el frontend de la modelo,
Filament el frontend administrativo y los estados de identidad, revisión y publicación siguen
independientes.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 12

**Primary Dependencies**: Eloquent, Laravel Storage/Flysystem, Blade, Filament 5, PHPUnit/Pest
según la configuración existente e Intervention Image v3 con driver GD para procesamiento de
imágenes

**Storage**: MySQL para metadata; disk privado Laravel `model_photos` para archivos. El disk debe
ser configurable para poder migrar de local a S3/R2/B2 u otro driver compatible sin modificar
modelos, Policies ni reglas de negocio.

**Testing**: `php artisan test`, pruebas HTTP/feature y unitarias, `vendor/bin/pint --test`,
`php artisan view:cache`, `php artisan route:list` y `php artisan optimize:clear`

**Target Platform**: aplicación web Laravel desplegada sobre PHP/MySQL

**Project Type**: aplicación web monolítica Laravel con frontend Blade y panel Filament

**Performance Goals**: mantener el upload acotado a un máximo de cinco ítems por perfil y 5 MB
por original; procesar sólo las variantes necesarias para cada carga; evitar N+1 en la galería y
en la relación administrativa mediante eager loading.

**Constraints**:

- máximo cinco fotos lógicas por `ModelProfile`, sin exigir que el perfil tenga cinco;
- sólo JPEG, PNG y WebP, MIME detectado server-side, mínimo 800x800 y máximo 5000x5000;
- no usar `public/`, `public_path`, `file_put_contents`, `unlink` ni rutas absolutas para archivos;
- no aceptar `model_profile_id`, `status`, auditoría ni paths desde el cliente;
- no exponer `storage_path` ni generar URLs públicas permanentes para archivos privados;
- todos los uploads requieren moderación;
- no modificar identity, revisión, publicación, registro, login ni funcionalidades de video;
- la UI Blade respeta el sistema visual existente. En el repositorio actual la guía visual
  disponible es `design.md` en la raíz; si se incorpora `docs/design.md`, deberá mantenerse
  alineada con esa guía y con `resources/css/styles.css`.

**Scale/Scope**: una galería de hasta cinco fotos por modelo, cuatro variantes físicas por
versión, una sección privada Blade, moderación de fotos en el detalle de modelo de Filament y
pruebas de regresión para features 001–004.

## Constitution Check

*GATE: Must pass before implementation and be re-checked after design.*

| Principle / gate | Status | Evidence in this plan |
|---|---|---|
| Laravel, Blade público y Filament administrativo | PASS | `/account/photos` en Blade; moderación en Filament; no se reemplazan superficies existentes |
| Persistencia explícita y segura | PASS | migraciones para `model_photos` y `model_photo_versions`, FKs, índices, constraints y relaciones Eloquent |
| Autorización server-side | PASS | `ModelPhotoPolicy`, ownership desde `auth()->user()->modelProfile` y capability admin verificado |
| Privacidad de archivos | PASS | disk privado dedicado, entrega controlada por Laravel, sin rutas públicas ni `storage_path` |
| UI completa y consistente | PASS | Blade responsive, estados vacíos/errores/éxito, reutilización de `styles.css`, revisión visual contra la guía existente |
| No introducir dependencias innecesarias | PASS WITH JUSTIFICATION | Intervention Image v3 se limita al procesamiento requerido; evita implementar resize, crop, WebP y watermark manualmente en controllers |
| Testing y calidad | PASS | cobertura de upload, ownership, variantes, moderación, reemplazo, principalidad, privacidad y regresión |

## Project Structure

### Documentation (this feature)

```text
specs/005-model-photos/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── model-photos.md
├── checklists/
│   └── requirements.md
└── tasks.md                 # generado después por $speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Filament/Resources/ModelProfiles/
│   ├── Pages/                 # ajustes mínimos al detalle existente
│   └── RelationManagers/
│       └── ModelPhotosRelationManager.php
├── Http/Controllers/
│   └── ModelPhotoController.php
├── Http/Requests/
│   ├── ModelPhotoUploadRequest.php
│   ├── ModelPhotoReplaceRequest.php
│   └── ModelPhotoReorderRequest.php
├── Models/
│   ├── ModelPhoto.php
│   └── ModelPhotoVersion.php
├── Policies/
│   └── ModelPhotoPolicy.php
└── Services/
    ├── ModelPhotoService.php
    ├── ModelPhotoStorage.php
    ├── ModelPhotoImageProcessor.php
    └── ModelPhotoModerationService.php

config/
├── filesystems.php
└── model-photos.php

database/migrations/
├── *_create_model_photos_table.php
├── *_create_model_photo_versions_table.php
└── *_add_current_version_fk_to_model_photos_table.php

resources/views/account/photos/
└── index.blade.php

resources/css/
└── styles.css

routes/
└── web.php

tests/
├── Feature/Account/ModelPhotosTest.php
├── Feature/Admin/ModelPhotoModerationTest.php
├── Feature/Storage/ModelPhotoStorageTest.php
└── Unit/Services/ModelPhoto*Test.php
```

**Structure Decision**: se mantiene la estructura monolítica Laravel existente. El controlador
coordina HTTP y validación de requests; los servicios son dueños de invariantes, procesamiento,
storage y transiciones; los modelos sólo expresan relaciones y casts; Policies autorizan cada
operación. No se agrega un frontend independiente ni un CRUD administrativo libre de versiones.

## Architecture and Data Flow

### Persistence

1. `model_photos` contiene `model_profile_id`, `current_version_id`, `position` e
   `is_primary`.
2. `model_photo_versions` contiene una fila por carga, los paths relativos de las cuatro
   variantes, metadata de imagen y auditoría de moderación.
3. Las migraciones crean FKs con borrado controlado, índices por propietario/posición/estado y
   unicidad de versión por ítem. El servicio mantiene el máximo de cinco y la secuencia densa
   de posiciones dentro de transacciones con lock del perfil.
4. `current_version_id` sólo puede apuntar a una versión `approved`. En un reemplazo aprobado
   continúa apuntando a la versión anterior hasta la promoción de la nueva.

### Upload and processing

1. `ModelPhotoUploadRequest` o `ModelPhotoReplaceRequest` valida autenticación, archivo,
   tamaño y reglas declarativas; `ModelPhotoImageProcessor` vuelve a comprobar MIME real,
   integridad y dimensiones con la imagen decodificada.
2. `ModelPhotoService` resuelve el perfil desde el usuario autenticado, verifica ownership,
   límite de cantidad y estado permitido, y solicita un identificador aleatorio de versión.
3. `ModelPhotoImageProcessor` genera en memoria/streams controlados: original intacto,
   processed WebP con lado máximo 2000, thumbnail WebP 400x400 crop/cover centrado y public
   WebP con watermark. El logo se carga desde el asset existente y su tamaño, opacidad y
   posición se configuran en `model-photos.php`.
4. `ModelPhotoStorage` escribe todas las variantes usando únicamente el disk `model_photos` y
   rutas relativas aleatorias. Si falla una escritura, elimina sólo los archivos nuevos de esa
   operación y no toca la versión actual anterior.
5. La fila de versión y el ítem lógico se persisten después de tener las variantes escritas.
   Si falla la transacción, se ejecuta la limpieza compensatoria de la carpeta de la nueva
   versión.

### Replacement and promotion

- `pending`, `rejected` y `approved` pueden reemplazarse.
- Cada reemplazo crea una nueva versión `pending`; nunca sobrescribe una versión existente.
- Si existía una versión approved, `current_version_id` e `is_primary` permanecen apuntando a
  la versión vigente anterior.
- Al aprobar una pending, `ModelPhotoModerationService` bloquea el ítem, verifica que la
  versión siga pending y que sus variantes existan, promueve `current_version_id` y registra
  `reviewed_at`/`reviewed_by`.
- La eliminación de archivos antiguos ocurre después del commit. Si falla esa limpieza, la
  promoción válida se conserva y el error se registra sin exponer paths sensibles.
- Si la nueva versión es rechazada, la versión approved anterior continúa siendo la actual.
- Una foto sin versión approved no puede ser principal ni aparecer en una futura consulta
  pública.

### Moderation

El Relation Manager muestra metadata mínima, thumbnail/processed mediante entrega privada
autorizada, estado y comparación entre la versión pending y la versión approved vigente cuando
existe. Las acciones de aprobar y rechazar llaman a `ModelPhotoModerationService`; los botones
no son la única protección. Rechazar requiere motivo. Ninguna acción modifica
`email_verified_at`, `identity_status`, `review_status` o `is_published`.

### Private delivery

Las rutas de entrega reciben el ítem lógico y la variante, autorizan con Policy, resuelven sólo
la versión actual aprobada o el contexto administrativo permitido y leen mediante
`Storage::disk(config('model-photos.disk'))`. Nunca reciben ni devuelven un path físico del
cliente. Las respuestas usan headers de contenido controlados y no se publica `storage:link` para
este disk.

## Configuration

`config/model-photos.php` centralizará:

- disk `model_photos`;
- máximo de 5 fotos;
- máximo de 5120 KB;
- MIME y extensiones JPEG/PNG/WebP;
- mínimo 800x800 y máximo 5000x5000;
- lado máximo processed 2000;
- thumbnail 400x400 y crop centrado;
- asset de watermark `public/images/logo-divas-cuyo.webp`;
- ancho relativo, opacidad y posición del watermark;
- prefijo de paths y estrategia de nombres aleatorios.

Ni Requests, controllers, Blade ni tests duplicarán estos valores: los tests leerán la misma
configuración y cubrirán los límites efectivos.

## Security and Authorization

- Todas las rutas de modelo requieren `auth` y `verified`.
- El perfil propietario se obtiene de `auth()->user()->modelProfile`; no se acepta
  `model_profile_id`.
- `ModelPhotoPolicy` cubre visualizar, subir, reemplazar, reordenar, marcar principal, eliminar
  y entregar variantes privadas. Las operaciones administrativas delegan en la capacidad de
  admin verificado existente y quedan listas para migrar a permisos específicos.
- El cliente no puede enviar `status`, `reviewed_at`, `reviewed_by`, `current_version_id`,
  paths ni auditoría.
- El servicio rechaza path traversal, rutas fuera del prefijo esperado, IDs de fotos ajenas,
  conjuntos de reorder incompletos y transiciones inválidas.
- No se registran binarios, nombres físicos, tokens ni rutas privadas en logs de aplicación.

## Testing Strategy

La suite debe cubrir:

- JPEG/PNG/WebP válidos, MIME falso, video/PDF/SVG/arbitrario, corrupción, tamaño, dimensiones
  y límite de cinco;
- ownership, acceso no autenticado, usuario normal/admin no verificado, IDs manipulados y
  path traversal;
- persistencia en disk privado, rutas relativas aleatorias, ausencia de `/public`, originales
  sin watermark, processed 2000, thumbnail 400 y watermark público;
- compensación de archivos ante fallos y ausencia de huérfanos relevantes;
- pending→approved/rejected, motivo obligatorio y auditoría;
- reemplazo approved conservando versión/principal anterior y promoción posterior;
- única principal approved, fallback al eliminar, reorder completo y posiciones densas;
- no exposición de `storage_path`, acceso privado controlado y separación del disk de identidad;
- regresión completa de features 001–004, incluyendo registro, identidad, moderación y `/account`.

Además se verificará la compilación/caché de vistas, rutas `/account/photos` y Filament, Pint y
la consistencia responsive de la pantalla con `design.md` y `resources/css/styles.css`.

## Rollout and Operational Notes

1. Instalar y configurar la única dependencia nueva de procesamiento; verificar GD en el entorno.
2. Ejecutar migraciones y configurar el disk privado sin crear un enlace público.
3. Validar watermark y procesamiento con fixtures de imagen antes de habilitar cargas.
4. Habilitar la UI privada y la relación Filament sólo después de pasar la suite de seguridad y
   regresión.
5. La migración futura a object storage debe limitarse a configuración del disk y, si hace falta,
   al adaptador de `ModelPhotoStorage`; no debe requerir cambios en modelos, Policies ni reglas.

La retención legal permanente de versiones antiguas queda fuera de esta feature. La política
inicial elimina variantes obsoletas después de una promoción o eliminación confirmada; deberá
revisarse antes de un entorno regulado.

## Complexity Tracking

| Addition | Why needed | Simpler alternative rejected because |
|---|---|---|
| `model_photo_versions` | Mantener la foto approved vigente durante reemplazos pendientes y comparar versiones en moderación | Una sola fila sobrescrita puede dejar al perfil sin versión aprobada y pierde el estado anterior |
| Intervention Image v3 | Resize, crop centrado, WebP y watermark consistentes server-side | Implementar GD directamente en controllers duplicaría lógica y aumentaría riesgo de corrupción/orientación |
