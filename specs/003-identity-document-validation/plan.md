# Implementation Plan: Validación de identidad con documentación privada

**Branch**: `003-identity-document-validation` | **Date**: 2026-09-15 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/003-identity-document-validation/spec.md`

## Summary

Agregar un flujo privado para que una modelo verificada cargue frente y dorso del DNI y selfie, los envíe a revisión y pueda corregirlos si son rechazados. La revisión se realizará en el panel Filament existente por administradores verificados. La documentación se almacenará en un disco privado de Laravel, con nombres aleatorios, sin URLs públicas ni exposición de rutas físicas.

La validación usará una entidad `ModelDocument` separada y un estado agregado `identity_status` persistido en `model_profiles`. Esta decisión permite representar explícitamente el estado de negocio, aplicar transiciones server-side y bloquear la aprobación/publicación del perfil sin inferir el estado desde archivos. `ModelProfileModerationService` conservará la lógica de publicación y aprobación de perfil, pero incorporará una precondición común de identidad aprobada sin duplicar las reglas de transición.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 12

**Primary Dependencies**: Filament 5 existente, Laravel Filesystem, Eloquent, Form Requests; no se agrega otra dependencia

**Storage**: MySQL para metadatos y estado; disco privado de Laravel fuera de `public/` y `storage/app/public` para archivos

**Testing**: PHPUnit/Laravel Feature tests, pruebas HTTP/Livewire de Blade y Filament, `vendor/bin/pint --test`

**Target Platform**: Aplicación web Laravel con MySQL y almacenamiento privado configurado

**Project Type**: Aplicación web server-rendered con frontend público Blade y panel administrativo Filament

**Performance Goals**: carga/validación de un archivo de hasta 5 MB en una operación; revisión de documentos y estados en una interacción; evitar N+1 al listar perfiles con identidad y documentos

**Constraints**: conservar registro, login, verificación de email, recuperación de contraseña y panel actuales; sólo JPEG, PNG, WebP y PDF; máximo 5 MB por archivo; no URLs públicas; toda lectura de archivos requiere autorización server-side; no agregar fotos, videos, planes, favoritos ni comentarios

**Scale/Scope**: una validación de identidad por `ModelProfile`, tres tipos obligatorios inicialmente, un documento vigente por tipo, cuatro estados de identidad y revisión administrativa dentro del recurso Filament existente

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Laravel standards and simplicity — PASS**: se reutilizan Laravel, Eloquent, Form Requests, Policies, Filesystem y Filament ya instalado; no se incorpora una dependencia adicional.
- **II. Explicit and safe persistence — PASS**: `model_documents`, `identity_status` y auditoría se agregarán mediante migraciones MySQL reversibles; las relaciones usadas por listado/detalle se cargarán explícitamente.
- **III. Validated and authorized boundaries — PASS**: ownership de modelo, Policy de documentos y Policy/Gate de revisión se validarán antes de leer, guardar o servir archivos; los endpoints públicos usarán Form Requests.
- **IV. Privacy and moderated publication — PASS**: los archivos quedan en almacenamiento privado, no se exponen rutas físicas y un perfil sin identidad aprobada no puede aprobarse/publicarse.
- **V. Tested and maintainable delivery — PASS**: se cubrirán carga, reemplazo, aislamiento, MIME/tamaño, transiciones, autorización, protección de publicación y regresión de las features 001 y 002.

**Re-check after Phase 1 design: PASS.** El diseño mantiene Blade para el flujo de modelo, limita Filament a revisión administrativa, centraliza las transiciones de identidad y publicación y no incorpora módulos fuera de alcance.

## Project Structure

### Documentation (this feature)

```text
specs/003-identity-document-validation/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── identity-document-validation.md
└── checklists/
    └── requirements.md
```

### Source Code (repository root)

```text
app/
├── Filament/Resources/ModelProfiles/
│   ├── ModelProfileResource.php
│   └── Pages/ViewModelProfile.php
├── Http/
│   ├── Controllers/IdentityDocumentController.php
│   ├── Requests/IdentityDocumentUploadRequest.php
│   └── Requests/IdentityDocumentSubmissionRequest.php
├── Models/
│   ├── ModelProfile.php
│   └── ModelDocument.php
├── Policies/
│   ├── ModelDocumentPolicy.php
│   └── ModelProfilePolicy.php
├── Services/
│   ├── IdentityDocumentService.php
│   └── ModelProfileModerationService.php
└── Providers/Filament/AdminPanelProvider.php

database/migrations/
├── *_add_identity_status_to_model_profiles_table.php
└── *_create_model_documents_table.php

resources/views/
└── identity/
    ├── show.blade.php
    └── partials/status.blade.php

routes/web.php                 # sólo rutas privadas de identidad, sin cambiar rutas existentes
config/filesystems.php         # disco privado ya existente o configuración mínima documentada

tests/Feature/
├── Identity/IdentityDocumentTest.php
├── Admin/IdentityDocumentReviewTest.php
├── Admin/ModelProfileModerationTest.php
├── Auth/RegistrationTest.php
└── Admin/ModelProfileResourceTest.php
```

**Structure Decision**: una aplicación Laravel única. El flujo de modelo se integra en Blade y rutas autenticadas existentes; Filament añade un panel read-only/reviewer al recurso `ModelProfile`. La persistencia de archivos y los cambios de estado se encapsulan en servicios/policies para que los controladores y componentes de UI no dupliquen reglas.

## Architecture and Data Design

### Identity state strategy

`identity_status` vivirá como columna persistida en `model_profiles`, con default `incomplete` e índice. El estado representa la decisión agregada sobre el conjunto vigente de documentos y no se derivará consultando `model_documents` en cada pantalla.

Razones:

- permite bloquear `approve` y `publish` con una condición atómica sobre el perfil;
- hace visible un estado `incomplete` incluso antes de cargar archivos;
- conserva el resultado administrativo aunque una política futura de retención cambie;
- facilita auditoría con `identity_reviewed_at`, `identity_reviewed_by` y `identity_rejection_reason`;
- evita que un documento individual aprobado se interprete como identidad completa.

El servicio de identidad verificará la presencia y validez operativa de los tres tipos obligatorios antes de `incomplete -> pending` y `pending -> approved`. `ModelProfileModerationService` verificará `identity_status = approved` antes de aprobar definitivamente el perfil y antes de publicar, además de las precondiciones actuales de `review_status`.

### State transitions

```text
incomplete ── submit ──> pending
pending    ── approve ─> approved
pending    ── reject ──> rejected
rejected   ── resubmit > pending
```

Server-side rules:

- `incomplete` permite cargar/reemplazar y sólo puede pasar a `pending` con todos los documentos obligatorios válidos;
- `pending` es inmutable para la modelo: no permite reemplazar ni eliminar documentos, y sólo un admin puede aprobar/rechazar;
- `rejected` permite reemplazo y reenvío, pero no aprobación directa sin volver a `pending`;
- `approved` es sólo lectura para la modelo y no admite reemplazo en esta feature;
- rechazar exige `rejection_reason`; aprobar limpia el motivo vigente;
- aprobar identidad no modifica `review_status` ni `is_published`;
- aprobar perfil y publicar requieren identidad aprobada; publicar requiere además perfil aprobado.

### Private file boundary

El archivo recibido se valida por contenido/MIME real y tamaño antes de persistirlo. Se genera un nombre aleatorio independiente de `original_name`; el `storage_path` queda sólo en base de datos y servicios internos. La descarga/visualización usa una respuesta de Laravel autorizada por `ModelDocumentPolicy`, sin `asset()`, URL pública, export ni log del contenido o ruta.

Al reemplazar, la operación guardará el nuevo archivo y metadatos dentro de una transacción/flujo compensable, eliminará de forma segura el archivo anterior y dejará un único documento vigente por `model_profile_id + type`. Si falla el guardado del nuevo archivo, no se eliminará el anterior ni se cambiará el estado.

### Authorization

`ModelDocumentPolicy` tendrá capacidades conceptuales `view`, `download`, `create`, `update` y `delete`, aplicadas con ownership para modelos y con autorización administrativa para revisión. Inicialmente una persona administradora es `is_admin = true` y tiene email verificado. La policy delegará la decisión de rol a un método/capability reemplazable, de modo que una futura migración a permisos no cambie los consumidores.

`ModelProfilePolicy` y el servicio existente seguirán autorizando revisión/publicación. La Policy no será la única barrera: cada servicio revalidará usuario, ownership, estado y presencia de documentos al ejecutar la operación.

### Public compatibility

No se reemplazan rutas ni pantallas públicas existentes. Se agrega una sección privada Blade bajo autenticación para el perfil propio. Las vistas no recibirán `storage_path`; sólo mostrarán tipo, estado, nombre original sanitizado si es necesario y motivo de rechazo. El panel Filament cargará relaciones acotadas (`user`, documentos vigentes y revisores) para evitar N+1 y excluir credenciales/tokens.

## Implementation Phases

1. **Research and decisions**: confirmar APIs de Filesystem privado, validación MIME real, eliminación segura y límites de subida compatibles con PHP/Laravel/MySQL; documentar alternativas rechazadas.
2. **Persistence**: agregar `identity_status` y auditoría agregada a `model_profiles`; crear `model_documents` con FK, índice único vigente, estados, constraints y referencias de revisor; configurar el disco privado sin publicar documentos.
3. **Domain and authorization**: crear `ModelDocument`, relaciones Eloquent, `ModelDocumentPolicy`, servicio de identidad y reglas compartidas con `ModelProfileModerationService` para aprobación/publicación.
4. **Public Blade flow**: agregar pantalla privada para estado, carga, reemplazo permitido, envío y motivo de rechazo; mantener ownership, CSRF, Form Requests, old/errors y rutas públicas existentes.
5. **Filament review**: extender el detalle de `ModelProfile` con estados/documentos privados y acciones confirmables de aprobar/rechazar con motivo, sin CRUD libre ni URLs directas.
6. **Verification**: agregar pruebas de archivos, ownership, autorización, transiciones, publicación, privacidad, reemplazo y regresión de features 001/002; ejecutar migraciones, suite, Pint, Blade/cache y rutas.

## Complexity Tracking

No hay violaciones de la constitución que justificar. La entidad separada de documentos, el estado agregado persistido y el servicio de almacenamiento son necesarios para proteger documentación sensible, conservar transiciones inequívocas y aplicar publicación moderada sin mezclar responsabilidades.
