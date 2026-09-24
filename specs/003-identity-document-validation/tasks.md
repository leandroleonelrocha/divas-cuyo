---

description: "Tareas de implementación de validación de identidad con documentación privada"
---

# Tasks: Validación de identidad con documentación privada

**Input**: Design documents from `/specs/003-identity-document-validation/`

**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/identity-document-validation.md` y `quickstart.md`

**Scope guard**: conservar Blade para el flujo público autenticado y Filament 5 para revisión administrativa. No implementar fotos públicas, videos, planes, favoritos, comentarios, OCR, biometría, pagos ni un sistema completo de roles/permisos. No agregar dependencias nuevas.

**Execution rule**: las tareas de tests deben escribirse y ejecutarse en rojo antes de su implementación correspondiente; las tareas que afectan el mismo archivo deben ejecutarse secuencialmente aunque tengan prioridad paralela conceptual.

## Phase 1: Setup / Foundation

**Purpose**: preparar configuración, migraciones, modelos, storage y límites compartidos sin cambiar el frontend público existente.

- [X] T001 Verificar en `composer.json`, `config/filesystems.php`, `.env.example` y `phpunit.xml` que Laravel, MySQL, Filament 5 y el entorno de tests existentes son suficientes, sin agregar dependencias nuevas.
- [X] T002 [P] Documentar en `config/identity-documents.php` los tipos obligatorios `dni_front`, `dni_back` y `selfie`, formatos JPEG/PNG/WebP/PDF, límite de 5 MB y política de conservar sólo el documento vigente.
- [X] T003 [P] Configurar en `config/filesystems.php` y `.env.example` el disco privado para identidad, fuera de `public/` y `storage/app/public`, sin habilitar URLs públicas.
- [X] T004 Crear la migración `database/migrations/*_add_identity_fields_to_model_profiles_table.php` con `identity_status`, `identity_reviewed_at`, `identity_reviewed_by` e `identity_rejection_reason`, default `incomplete`, índice y FK nullable a `users.id` con `nullOnDelete`.
- [X] T005 Crear la migración `database/migrations/*_create_model_documents_table.php` con `model_profile_id`, `type`, `storage_path`, `original_name`, `mime_type`, `file_size`, `status`, `rejection_reason`, `reviewed_at`, `reviewed_by` y timestamps.
- [X] T006 Agregar en `database/migrations/*_create_model_documents_table.php` las FKs a `model_profiles.id` con `cascadeOnDelete` y a `users.id` con `nullOnDelete`, unique compuesto `model_profile_id + type`, índice `model_profile_id + status` e índice `type`.
- [X] T007 [P] Actualizar `app/Models/ModelProfile.php` con casts, estados permitidos, campos de identidad protegidos y relaciones `hasMany(ModelDocument::class)` y `belongsTo` del administrador revisor.
- [X] T008 [P] Crear `app/Models/ModelDocument.php` con asignación protegida, casts, estados de documento permitidos y relaciones `modelProfile()` y `reviewer()`.
- [X] T009 Actualizar `app/Models/User.php` con las relaciones necesarias para documentos y revisión sin exponer password, tokens ni `remember_token`.
- [X] T010 [P] Crear `tests/Feature/Identity/IdentityDocumentMigrationTest.php` para verificar columnas, defaults, FKs, constraints, unique por tipo e índices en MySQL.
- [X] T011 Ejecutar las migraciones de Foundation y validar rollback seguro en `tests/Feature/Identity/IdentityDocumentMigrationTest.php` sin modificar datos legacy ni rutas públicas.

**Checkpoint**: existen el estado agregado persistido, la entidad documental separada, las relaciones Eloquent y la configuración privada base.

## Phase 2: Storage privado y seguridad de archivos

**Purpose**: definir una frontera única y segura para validar, nombrar, guardar, reemplazar y servir documentos.

- [X] T012 Crear `app/Services/PrivateIdentityDocumentStorage.php` para generar nombres físicos aleatorios, guardar en el disco privado y eliminar de forma segura sólo rutas internas generadas por la aplicación.
- [X] T013 [P] Crear `app/Rules/ValidIdentityDocument.php` o la regla equivalente en `app/Http/Requests/` para validar contenido/MIME real JPEG, PNG, WebP y PDF, con máximo 5 MB, sin confiar en la extensión ni en el nombre recibido.
- [X] T014 Crear `app/Policies/ModelDocumentPolicy.php` con `view`, `download`, `create`, `update` y `delete`, comprobando ownership para modelos y administrador verificado para revisión.
- [X] T015 Registrar `ModelDocumentPolicy` y las capabilities administrativas en `app/Providers/AppServiceProvider.php` o el mecanismo equivalente, dejando reemplazable `is_admin` por permisos específicos en el futuro.
- [X] T016 Crear `app/Http/Controllers/PrivateIdentityDocumentController.php` con respuestas autorizadas para visualizar/descargar archivos, sin exponer `storage_path`, nombres físicos ni URLs públicas.
- [X] T017 Agregar en `routes/web.php` únicamente rutas autenticadas y autorizadas para la sección de identidad y servir documentos privados, conservando todas las rutas públicas existentes.
- [X] T018 [P] Crear `tests/Feature/Identity/PrivateDocumentAccessTest.php` para comprobar que documentos no son accesibles mediante `asset()`, `public/`, `storage/app/public` ni rutas directas, y que la descarga exige autorización server-side.
- [X] T019 [P] Crear `tests/Feature/Identity/PrivateDocumentStorageTest.php` para verificar nombres aleatorios, path traversal rechazado, MIME real, límite de 5 MB y exclusión de rutas/contenido en respuestas y logs.

**Checkpoint**: ningún documento puede quedar público ni ser servido sin Policy; MIME, tamaño, nombre físico y path quedan controlados server-side.

## Phase 3: User Story 1 — Carga y envío a revisión (Priority: P1) 🎯 MVP

**Goal**: permitir que una modelo verificada cargue sus tres documentos obligatorios y los envíe a revisión.

**Independent Test**: una modelo autenticada y con email verificado carga DNI frente, DNI dorso y selfie válidos, los envía y obtiene `identity_status = pending`; otra modelo no puede acceder a ellos.

### Tests for User Story 1

- [X] T020 [P] [US1] Crear `tests/Feature/Identity/IdentityDocumentUploadTest.php` para upload válido de `dni_front`, `dni_back` y `selfie` sobre el perfil propio.
- [X] T021 [P] [US1] Agregar en `tests/Feature/Identity/IdentityDocumentUploadTest.php` casos de MIME inválido, extensión engañosa, archivo mayor a 5 MB y formato no permitido.
- [X] T022 [P] [US1] Agregar en `tests/Feature/Identity/IdentityDocumentUploadTest.php` casos de ownership, invitado, usuario común y acceso cruzado entre perfiles.
- [X] T023 [P] [US1] Crear `tests/Feature/Identity/IdentityDocumentSubmissionTest.php` para documentos faltantes, `incomplete -> pending` y preservación de `email_verified_at`, `review_status` e `is_published`.

### Implementation for User Story 1

- [X] T024 [US1] Crear `app/Http/Requests/IdentityDocumentUploadRequest.php` con ownership, email verificado, estado permitido, tipo configurable, MIME real y máximo 5 MB.
- [X] T025 [US1] Crear `app/Http/Requests/IdentityDocumentSubmissionRequest.php` para validar que el perfil propio contiene los tres tipos obligatorios vigentes antes de enviar.
- [X] T026 [US1] Crear `app/Services/IdentityDocumentService.php` con `upload`, `submit` y validación de documentos requeridos, sin permitir cambios directos de `identity_status` desde el cliente.
- [X] T027 [US1] Implementar en `app/Http/Controllers/PrivateIdentityDocumentController.php` la carga y envío a revisión delegando en `IdentityDocumentService` y conservando errores claros sin detalles sensibles.
- [X] T028 [US1] Agregar en `routes/web.php` endpoints POST/GET privados para carga, estado y envío, protegidos con autenticación, CSRF, ownership y Policies.
- [X] T029 [US1] Crear `resources/views/identity/show.blade.php` y parciales necesarios para mostrar estado, tres campos de archivo, errores de validación y motivo de rechazo sin renderizar `storage_path`.
- [X] T030 [US1] Integrar desde la cuenta pública existente un enlace acotado a la sección de identidad en `resources/views/account/show.blade.php`, sin modificar otras pantallas ni el flujo de registro.
- [X] T031 [US1] Ejecutar `tests/Feature/Identity/IdentityDocumentUploadTest.php` y `IdentityDocumentSubmissionTest.php`, verificando que sólo el conjunto completo pase a `pending`.

**Checkpoint**: una modelo puede completar y enviar su documentación propia; archivos inválidos, incompletos o ajenos quedan bloqueados.

## Phase 4: User Story 2 — Corrección y reemplazo de documentos rechazados (Priority: P1)

**Goal**: permitir reemplazar documentación sólo en `incomplete` o `rejected`, eliminar el archivo anterior y reenviar el conjunto completo.

**Independent Test**: una identidad rechazada muestra el motivo, la modelo reemplaza un documento, el archivo anterior desaparece y el conjunto corregido vuelve a `pending`.

### Tests for User Story 2

- [X] T032 [P] [US2] Agregar en `tests/Feature/Identity/IdentityDocumentReplacementTest.php` que reemplazar elimina el archivo anterior, conserva sólo el vigente y mantiene el nombre físico aleatorio.
- [X] T033 [P] [US2] Agregar en `tests/Feature/Identity/IdentityDocumentReplacementTest.php` que `incomplete` y `rejected` permiten reemplazo, mientras `pending` y `approved` lo rechazan sin mutación parcial.
- [X] T034 [P] [US2] Agregar en `tests/Feature/Identity/IdentityDocumentResubmissionTest.php` que `rejected -> pending` exige nuevamente todos los documentos y que `rejection_reason` es visible para la modelo.

### Implementation for User Story 2

- [X] T035 [US2] Completar `app/Services/IdentityDocumentService.php` con reemplazo atómico/compensable: guardar nuevo archivo, actualizar metadatos, eliminar el anterior sólo después de confirmar el nuevo y conservar el anterior si falla la operación.
- [X] T036 [US2] Implementar en `app/Http/Controllers/PrivateIdentityDocumentController.php` la respuesta de estado/motivo y el reemplazo condicionado por `identity_status`.
- [X] T037 [US2] Actualizar `resources/views/identity/show.blade.php` para habilitar reemplazo sólo en `incomplete`/`rejected`, mostrar `rejection_reason` y deshabilitar controles en `pending`/`approved`.
- [X] T038 [US2] Agregar mensajes de validación y estado en `resources/lang/es/validation.php` o el archivo de traducciones existente, sin revelar rutas ni datos internos.
- [X] T039 [US2] Ejecutar `IdentityDocumentReplacementTest.php` y `IdentityDocumentResubmissionTest.php`, verificando ausencia de archivos huérfanos y preservación del estado ante errores.

**Checkpoint**: la corrección funciona sólo después de rechazo o durante incomplete; pending/approved son de sólo lectura para la modelo.

## Phase 5: User Story 3 — Revisión administrativa en Filament (Priority: P1)

**Goal**: permitir que administradores verificados revisen documentos privados y aprueben/rechacen la identidad con auditoría.

**Independent Test**: un administrador verificado abre el detalle de un perfil pendiente, visualiza documentos mediante acceso protegido, aprueba o rechaza con motivo y el estado/auditoría se actualizan.

### Tests for User Story 3

- [X] T040 [P] [US3] Crear `tests/Feature/Admin/IdentityDocumentReviewTest.php` para acceso de admin verificado, acceso denegado a admin no verificado/no admin y visualización segura de documentos.
- [X] T041 [P] [US3] Agregar en `tests/Feature/Admin/IdentityDocumentReviewTest.php` aprobación `pending -> approved`, auditoría `reviewed_at/reviewed_by` y ausencia de publicación automática.
- [X] T042 [P] [US3] Agregar en `tests/Feature/Admin/IdentityDocumentReviewTest.php` rechazo con motivo obligatorio, persistencia de `rejection_reason` y estado `pending -> rejected`.
- [X] T043 [P] [US3] Agregar en `tests/Feature/Admin/IdentityDocumentReviewTest.php` bloqueo por documentos faltantes, transiciones inválidas y ausencia de exposición de secretos.

### Implementation for User Story 3

- [X] T044 [US3] Completar `app/Services/IdentityDocumentService.php` con `approveIdentity` y `rejectIdentity`, revalidando estado, conjunto obligatorio, actor y motivo dentro de una operación segura.
- [X] T045 [US3] Extender `app/Filament/Resources/ModelProfiles/ModelProfileResource.php` para mostrar estado de email, identidad, revisión, publicación y documentos vigentes sin incluir passwords, tokens, `storage_path` ni nombres físicos.
- [X] T046 [US3] Extender `app/Filament/Resources/ModelProfiles/Pages/ViewModelProfile.php` con sección de identidad y visualización/descarga segura mediante acciones autorizadas, sin CRUD libre de documentos.
- [X] T047 [US3] Agregar acciones Filament `approveIdentity` y `rejectIdentity` en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`, con confirmación, motivo requerido al rechazar, visibilidad por estado y notificaciones claras.
- [X] T048 [US3] Conectar las acciones de Filament con `IdentityDocumentService` y `ModelDocumentPolicy`, manteniendo la autorización server-side aunque la acción esté oculta en UI.
- [X] T049 [US3] Ejecutar `IdentityDocumentReviewTest.php` y comprobar que un admin autorizado puede revisar, que uno no autorizado recibe 403 y que no se generan URLs públicas.

**Checkpoint**: la revisión documental funciona sólo para admins verificados, registra auditoría y mantiene identidad separada de perfil/publicación.

## Phase 6: User Story 4 — Separación e integración con aprobación/publicación (Priority: P1)

**Goal**: hacer cumplir que identidad aprobada sea condición previa para aprobar/publicar, sin mezclar estados ni publicar automáticamente.

**Independent Test**: perfiles con identidad incompleta, pendiente, rechazada y aprobada no pueden avanzar a revisión/publicación salvo cuando cumplen todas las precondiciones.

### Tests for User Story 4

- [X] T050 [P] [US4] Agregar en `tests/Feature/Admin/ModelProfileModerationTest.php` que aprobar perfil falla cuando `identity_status != approved` y no modifica `review_status` ni auditoría.
- [X] T051 [P] [US4] Agregar en `tests/Feature/Admin/ModelProfileModerationTest.php` que publicar falla cuando identidad o revisión no están aprobadas y conserva `is_published = false`.
- [X] T052 [P] [US4] Agregar en `tests/Feature/Admin/ModelProfileModerationTest.php` que identidad aprobada no publica automáticamente y despublicar conserva identidad/revisión.
- [X] T053 [P] [US4] Crear `tests/Feature/Identity/IdentityStateTransitionTest.php` para las cuatro transiciones permitidas y rechazar todas las inválidas, incluida concurrencia/estado obsoleto.

### Implementation for User Story 4

- [X] T054 [US4] Centralizar en `app/Services/IdentityDocumentService.php` las transiciones `incomplete -> pending`, `pending -> approved`, `pending -> rejected` y `rejected -> pending`, con validación server-side y sin asignación directa desde formularios.
- [X] T055 [US4] Actualizar `app/Services/ModelProfileModerationService.php` para exigir `identity_status = approved` antes de aprobar perfil o publicar, manteniendo las transiciones existentes y sin duplicar lógica de identidad.
- [X] T056 [US4] Actualizar `app/Policies/ModelProfilePolicy.php` para reflejar las nuevas precondiciones de aprobación/publicación y mantener autorización administrativa en servidor.
- [X] T057 [US4] Actualizar badges, labels y estados en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php` para distinguir `email_verified_at`, `identity_status`, `review_status` e `is_published`.
- [X] T058 [US4] Verificar en `resources/views/identity/show.blade.php` y `resources/views/account/show.blade.php` que identidad aprobada no se presente como email verificado, perfil aprobado ni publicación activa.
- [X] T059 [US4] Ejecutar `IdentityStateTransitionTest.php` y `ModelProfileModerationTest.php`, confirmando reglas de publicación y regresión de moderación anterior.

**Checkpoint**: identidad, email, revisión y publicación son estados independientes y las precondiciones se aplican en el servicio/policy, no sólo en la UI.

## Phase 7: Security / Authorization hardening

**Purpose**: cerrar ownership, exposición, concurrencia, logs y compatibilidad con futura autorización por permisos.

- [X] T060 [P] Revisar `app/Policies/ModelDocumentPolicy.php`, `app/Policies/ModelProfilePolicy.php` y `app/Providers/AppServiceProvider.php` para asegurar que `is_admin + email verificado` sea la única autorización inicial y quede encapsulada como capability migrable.
- [X] T061 [P] Revisar `app/Http/Controllers/PrivateIdentityDocumentController.php`, Form Requests y `app/Services/IdentityDocumentService.php` para impedir manipulación de `model_profile_id`, path traversal, mass assignment, IDs cruzados y respuestas con secretos.
- [X] T062 [P] Revisar `app/Filament/Resources/ModelProfiles/ModelProfileResource.php` y `ViewModelProfile.php` para aplicar eager loading acotado de documentos/revisores y evitar N+1 o exposición de credenciales/tokens.
- [X] T063 [P] Agregar `tests/Feature/Identity/IdentityAuthorizationTest.php` para invitado, usuario común, acceso cruzado, admin no autorizado, admin verificado y respuesta controlada ante perfil/documento inexistente.
- [X] T064 [P] Agregar `tests/Feature/Identity/IdentityPrivacyTest.php` para confirmar exclusión de documentos en APIs/exports/logs/superficies públicas y ausencia de URLs directas.
- [X] T065 Ejecutar revisión de configuración de PHP/MySQL/disco privado y documentar límites operativos o fallos de almacenamiento sin incluir contenido sensible en `specs/003-identity-document-validation/quickstart.md`.

**Checkpoint**: todas las fronteras de autorización y privacidad quedan verificadas independientemente de la interfaz.

## Phase 8: Testing, Polish / Documentation

**Purpose**: validar integración completa, documentación operativa y ausencia de regresiones.

- [X] T066 [P] Actualizar `specs/003-identity-document-validation/quickstart.md` con setup privado, migraciones, escenarios de upload/reemplazo/revisión, estados, seguridad y rollback.
- [X] T067 [P] Actualizar `specs/003-identity-document-validation/contracts/identity-document-validation.md` si las rutas, mensajes o campos observables cambian durante la integración, sin documentar `storage_path` como dato público.
- [X] T068 [P] Revisar `specs/003-identity-document-validation/data-model.md` y `research.md` para reflejar columnas, índices, retención vigente y decisiones finales sin contradicciones.
- [X] T069 Ejecutar `php artisan migrate` sobre MySQL y verificar integridad/rollback de las migraciones de identidad en `tests/Feature/Identity/IdentityDocumentMigrationTest.php`.
- [X] T070 Ejecutar `php artisan test` sobre `tests/` y confirmar upload, MIME, tamaño, ownership, transiciones, moderación/publicación, privacidad y regresión completa de features 001 y 002.
- [X] T071 Ejecutar `vendor/bin/pint --test` sobre `app/`, `resources/` y `tests/`, corrigiendo únicamente formato en archivos de esta feature.
- [X] T072 Ejecutar `php artisan route:list`, `php artisan view:cache` y `php artisan optimize:clear` para validar `routes/web.php`, `resources/views/` y configuración sin alterar rutas públicas.
- [X] T073 Revisar `git diff --stat`, `git diff --name-only` y `git status` para confirmar que no se tocaron fotos, videos, planes, favoritos, comentarios, OCR ni módulos ajenos; documentar cualquier deuda técnica en `quickstart.md`.

**Checkpoint final**: documentación privada validable, revisión administrativa segura, publicación protegida y regresiones públicas cubiertas.

## Dependencies & Execution Order

### Phase Dependencies

- **Setup / Foundation (Phase 1)**: sin dependencias; bloquea todos los flujos por requerir schema, modelos y configuración.
- **Storage privado (Phase 2)**: depende de T001–T003; bloquea carga, reemplazo y visualización.
- **US1 (Phase 3)**: depende de Foundation y Storage; entrega el MVP de carga/envío.
- **US2 (Phase 4)**: depende de US1 porque reutiliza upload, estados y almacenamiento.
- **US3 (Phase 5)**: depende de Foundation/Storage y de los documentos en US1; puede desarrollarse en paralelo con US2 si se coordinan `IdentityDocumentService.php` y sus tests.
- **US4 (Phase 6)**: depende de US1/US3 y del servicio de moderación existente; bloquea publicación segura.
- **Security (Phase 7)**: puede comenzar parcialmente después de Phase 2, pero su cierre depende de US1–US4.
- **Polish (Phase 8)**: depende de todas las fases funcionales.

### User Story Completion Order

1. **US1 (P1)**: carga y envío a revisión; MVP operativo mínimo.
2. **US2 (P1)**: corrección y reemplazo tras rechazo.
3. **US3 (P1)**: revisión privada y resolución administrativa.
4. **US4 (P1)**: integración obligatoria con aprobación/publicación.

### Parallel Opportunities

- **Foundation**: T002/T003, T007/T008/T009 y T010 pueden trabajarse en paralelo si se coordinan las migraciones.
- **Storage**: T013, T014 y T018/T019 pueden desarrollarse en paralelo; T012 debe integrar el resultado de validación.
- **US1**: T020–T023 son pruebas separables por archivo; T024/T025 pueden prepararse en paralelo, y T029 sólo después de conocer los contratos.
- **US2**: T032–T034 son pruebas separables; servicio, controlador y Blade deben coordinarse para no eliminar el archivo anterior antes de confirmar el nuevo.
- **US3**: T040–T043 son pruebas separables; Resource y página Filament comparten integración, mientras Policy/servicio pueden prepararse antes.
- **US4**: T050–T053 son pruebas separables; Policy y servicio deben integrarse secuencialmente antes de ejecutar el checkpoint.
- **Security**: T060–T064 pueden revisarse en paralelo sobre archivos distintos; T065 consolida la configuración.
- **Documentation**: T066–T068 pueden actualizarse en paralelo; T069–T073 son validaciones finales secuenciales.

## Parallel Example: Foundation and first MVP

```text
Después de T001:
  Trabajador A: T002 -> T003
  Trabajador B: T004 -> T005 -> T006
  Trabajador C: T007 -> T008 -> T009

Después de T011 y T019:
  Trabajador A: T020 -> T024 -> T026 -> T027 -> T029
  Trabajador B: T021 -> T025 -> T028
  Trabajador C: T022 -> T023 -> T018
```

## Implementation Strategy

### MVP First

1. Completar Phase 1: schema, `ModelProfile`, `ModelDocument` y configuración.
2. Completar Phase 2: disco privado, validación MIME/tamaño, nombres aleatorios y Policy.
3. Completar US1: carga propia de los tres documentos y envío a `pending`.
4. **DETENERSE Y VALIDAR**: ejecutar los tests de upload, ownership, privacidad y documentos faltantes antes de habilitar revisión administrativa.

### Incremental Delivery

1. Agregar US2: reemplazo seguro sólo en `incomplete`/`rejected`.
2. Agregar US3: revisión privada Filament, aprobación/rechazo y auditoría.
3. Agregar US4: bloquear aprobación/publicación sin identidad aprobada.
4. Completar hardening, documentación, regresión y validaciones de despliegue.

### Risks and Mitigations

- **Archivos públicos por configuración incorrecta**: validar disco, ausencia de symlink público y tests de acceso directo antes de aceptar uploads.
- **MIME falsificado o archivos maliciosos**: inspeccionar contenido real, limitar tamaño y no confiar en extensión/nombre.
- **Pérdida del documento vigente durante replacement**: persistir el nuevo archivo antes de borrar el anterior y usar compensación ante fallos.
- **Bypass de UI o carreras de estado**: revalidar Policy, ownership y transición dentro del servicio server-side.
- **Exposición accidental en Filament/logs**: seleccionar relaciones acotadas, nunca serializar rutas físicas y revisar exports/logging.
- **Confusión entre estados**: mostrar y testear `email_verified_at`, `identity_status`, `review_status` e `is_published` como campos independientes.

## Notes

- Total: **73 tareas**.
- Las tareas `[P]` sólo indican oportunidad de trabajo paralelo; si comparten archivo deben coordinarse secuencialmente.
- No se crean CRUD libre de documentos, fotos públicas, videos, planes, favoritos, comentarios, OCR, biometría ni roles/permisos completos.
