---

description: "Tareas ejecutables para la gestión de fotografías de modelos"
---

# Tasks: Model Photos

**Input**: Design documents from `specs/005-model-photos/`

**Prerequisites**: `spec.md`, `plan.md`, `research.md`, `data-model.md`, `contracts/model-photos.md`, `quickstart.md`

**Organization**: las tareas están agrupadas por historia de usuario y respetan la arquitectura
de versionado, storage privado y procesamiento definida en los documentos de diseño.

## Phase 1: Setup / Dependencias

**Purpose**: preparar la dependencia de procesamiento y la configuración base sin implementar
todavía el flujo de fotos.

- [x] T001 Agregar Intervention Image v3 y su integración con GD en `composer.json` y `composer.lock`, documentando la extensión GD requerida.
- [x] T002 [P] Crear la configuración inicial de imágenes y watermark en `config/model-photos.php`, incluyendo MIME, extensiones, límites, dimensiones, variantes y asset `public/images/watermark-divas-cuyo.png`.
- [x] T003 [P] Registrar el disk privado `model_photos` en `config/filesystems.php` con driver configurable, ruta privada relativa y sin exposición mediante `public/storage`.
- [x] T004 [P] Cubrir fixtures de JPEG, PNG y WebP válidos, inválidos y de dimensiones límite mediante `UploadedFile::fake()` en `tests/Feature/Account/ModelPhotoUploadTest.php` y `tests/Unit/Services/ModelPhotoImageProcessorTest.php`.

---

## Phase 2: Foundation / Modelo de datos

**Purpose**: crear las entidades, relaciones y constraints que bloquean todas las historias.

**⚠️ CRITICAL**: ninguna historia puede comenzar hasta completar esta fase.

- [x] T005 Crear la migración de `model_photos` en `database/migrations/*_create_model_photos_table.php` con `model_profile_id`, `current_version_id` nullable, `position`, `is_primary` y timestamps.
- [x] T006 Crear la migración de `model_photo_versions` en `database/migrations/*_create_model_photo_versions_table.php` con metadata, cuatro paths relativos, estado, auditoría y timestamps.
- [x] T007 Crear o completar la migración de la FK `model_photos.current_version_id` en `database/migrations/*_add_current_version_fk_to_model_photos_table.php`, respetando el orden de creación de ambas tablas.
- [x] T008 Definir FKs, índices y constraints de `model_photos` y `model_photo_versions`, incluyendo owner, versión única por ítem, índices por estado/posición y borrado controlado, en las migraciones de `database/migrations/`.
- [x] T009 Crear el enum o constantes de estados `pending`, `approved` y `rejected` en `app/Models/ModelPhotoVersion.php` o una clase de dominio equivalente, sin permitir valores arbitrarios.
- [x] T010 [P] Crear `app/Models/ModelPhoto.php` con fillable/guarded seguro, casts, relación con `ModelProfile`, `currentVersion`, `versions` y consultas de fotos aprobadas.
- [x] T011 [P] Crear `app/Models/ModelPhotoVersion.php` con casts, ocultamiento de paths sensibles, relaciones `photo`, `supersededVersion`, `successors` y `reviewedBy`.
- [x] T012 Agregar a `app/Models/ModelProfile.php` las relaciones `photos()`, `currentApprovedPhotos()` y cualquier relación through necesaria para Filament, sin modificar los estados existentes.
- [x] T013 Agregar a `app/Models/User.php` la relación inversa necesaria para revisores de fotos, manteniendo separada la relación existente con `ModelProfile`.
- [x] T014 Crear `app/Services/ModelPhotoDomainRules.php` o equivalente para centralizar máximo de 5 fotos, estados válidos, principal approved y reglas de versionado sin duplicarlas en controllers/views.
- [x] T015 Crear `tests/Feature/ModelPhotos/ModelPhotoSchemaTest.php` para verificar migraciones, FKs, índices, relaciones, unicidad de versión y ejecución limpia de `php artisan migrate:fresh`.

**Checkpoint**: las tablas, relaciones y reglas estructurales existen; todavía no hay carga ni moderación utilizables.

---

## Phase 3: Storage y procesamiento de imágenes

**Purpose**: generar y limpiar variantes privadas de forma segura y desacoplada del filesystem local.

- [x] T016 Crear `app/Services/ModelPhotoStorage.php` para usar únicamente `Storage::disk(config('model-photos.disk'))`, guardar rutas relativas y generar segmentos físicos aleatorios.
- [x] T017 Implementar en `app/Services/ModelPhotoStorage.php` validación de prefijo, path traversal, rutas absolutas y eliminación restringida al disk/prefijo de fotos.
- [x] T018 Crear `app/Services/ModelPhotoImageProcessor.php` usando Intervention Image v3 + GD para decodificar, validar integridad/MIME real y dimensiones 800x800–5000x5000.
- [x] T019 Implementar en `app/Services/ModelPhotoImageProcessor.php` normalización con relación de aspecto y variante procesada WebP con lado máximo de 2000 px.
- [x] T020 Implementar en `app/Services/ModelPhotoImageProcessor.php` thumbnail WebP 400x400 con crop/cover centrado.
- [x] T021 Implementar en `app/Services/ModelPhotoImageProcessor.php` la variante pública WebP con watermark usando `public/images/watermark-divas-cuyo.png` y configuración de tamaño, opacidad y posición.
- [x] T022 Implementar en `app/Services/ModelPhotoService.php` la escritura coordinada de original privado sin watermark, processed, thumbnail y public watermark, sin exponer ninguna ruta física.
- [x] T023 Implementar compensación en `app/Services/ModelPhotoStorage.php` y `app/Services/ModelPhotoService.php` para borrar sólo archivos nuevos si falla procesamiento, escritura o persistencia.
- [x] T024 Crear `tests/Unit/Services/ModelPhotoImageProcessorTest.php` para verificar MIME, integridad, dimensiones, relación de aspecto, processed 2000, thumbnail 400 y watermark.
- [x] T025 Crear `tests/Feature/Storage/ModelPhotoStorageTest.php` para verificar disk dedicado, rutas relativas aleatorias, ausencia bajo `public/`, limpieza compensatoria y ausencia de `storage_path` en respuestas.

---

## Phase 4: User Story 1 — Carga inicial (Priority: P1) 🎯 MVP

**Goal**: una modelo verificada con `ModelProfile` puede cargar hasta cinco imágenes válidas, que quedan `pending` y privadas.

**Independent Test**: cargar JPEG, PNG y WebP válidos desde una modelo propietaria; confirmar estado `pending`, variantes privadas, estado vacío y rechazo de archivos inválidos o del sexto upload.

### Tests for User Story 1

- [x] T026 [P] [US1] Crear `tests/Feature/Account/ModelPhotoUploadTest.php` para upload válido JPEG/PNG/WebP y creación `pending`.
- [x] T027 [P] [US1] Agregar en `tests/Feature/Account/ModelPhotoUploadTest.php` casos de video, PDF, SVG, ejecutable, archivo arbitrario y MIME falso rechazados sin persistencia.
- [x] T028 [P] [US1] Agregar en `tests/Feature/Account/ModelPhotoUploadTest.php` casos de >5 MB, menor a 800x800, mayor a 5000x5000 y contenido corrupto.
- [x] T029 [P] [US1] Agregar en `tests/Feature/Account/ModelPhotoUploadTest.php` el límite máximo de 5 fotos y la ausencia de acceso público al original/pending.

### Implementation for User Story 1

- [x] T030 Crear `app/Policies/ModelPhotoPolicy.php` con autorización de `viewAny`, `view`, `create` y `upload` basada en auth, email verificado, `ModelProfile` y ownership implícito.
- [x] T031 Registrar `ModelPhotoPolicy` en `app/Providers/AppServiceProvider.php` o el mecanismo de policies existente, incluyendo autorización administrativa futura sin abrir acceso general.
- [x] T032 Crear `app/Http/Requests/ModelPhotoUploadRequest.php` con `image`, tamaño configurable, MIME/extensiones configurables y sin aceptar `model_profile_id`, `status`, paths ni auditoría.
- [x] T033 Crear `app/Services/ModelPhotoService.php` con `uploadForAuthenticatedModel()` para resolver el perfil desde `auth()->user()->modelProfile`, verificar el máximo y crear ítem lógico/versión `pending`.
- [x] T034 Crear `app/Http/Controllers/ModelPhotoController.php` con `index()` y `store()` delegando autorización, validación, procesamiento, storage y mensajes al servicio.
- [x] T035 Agregar las rutas GET/POST de `/account/photos` en `routes/web.php` con middleware `auth`, `verified` y nombres consistentes con `contracts/model-photos.md`.
- [x] T036 Crear `resources/views/account/photos/index.blade.php` con layout completo, galería, upload, estados `pending/approved/rejected`, estado vacío, errores y mensajes de éxito.
- [x] T037 Agregar en `resources/css/styles.css` sólo las clases necesarias para la galería de fotos, dropzone/formulario, cards de estado y responsive, reutilizando primero patrones existentes y respetando `design.md`.
- [x] T038 Verificar visualmente `resources/views/account/photos/index.blade.php` en desktop, tablet y 360px, incluyendo accesibilidad básica y ausencia de overflow, según la guía visual vigente.

**Checkpoint**: la modelo puede cargar imágenes válidas y verlas privadas como `pending`; no puede superar cinco ni exponer el original.

---

## Phase 5: User Story 2 — Orden y foto principal (Priority: P1)

**Goal**: la modelo puede ordenar sus fotos, seleccionar sólo una foto aprobada como principal y mantener posiciones consistentes.

**Independent Test**: crear varias fotos propias, aprobar fixtures, reordenar, seleccionar principal, cambiarla y eliminarla verificando fallback por posición.

### Tests for User Story 2

- [x] T039 [P] [US2] Crear `tests/Feature/Account/ModelPhotoReorderTest.php` para persistencia de posiciones densas y recarga en el mismo orden.
- [x] T040 [P] [US2] Crear `tests/Feature/Account/ModelPhotoPrimaryTest.php` para exigir `approved`, desmarcar la anterior y mantener como máximo una principal.
- [x] T041 [P] [US2] Agregar en `tests/Feature/Account/ModelPhotoPrimaryTest.php` rechazo de pending/rejected y acceso cruzado al marcar principal.
- [x] T042 [P] [US2] Agregar en `tests/Feature/Account/ModelPhotoReorderTest.php` rechazo de IDs ajenos, IDs duplicados o conjuntos incompletos, dejando que el servicio normalice las posiciones y use locks transaccionales.

### Implementation for User Story 2

- [x] T043 Crear `app/Http/Requests/ModelPhotoReorderRequest.php` para recibir sólo el conjunto de IDs propios, validar forma/duplicados y no aceptar posiciones confiadas sin normalización server-side.
- [x] T044 Agregar a `app/Services/ModelPhotoService.php` `reorderForAuthenticatedModel()` con lock del `ModelProfile`, validación del conjunto completo y posiciones 0..n-1.
- [x] T045 Agregar a `app/Services/ModelPhotoService.php` `setPrimaryForAuthenticatedModel()` con lock, verificación de versión current approved y desmarcado transaccional de la anterior.
- [x] T046 Agregar `order()` y `setPrimary()` a `app/Http/Controllers/ModelPhotoController.php`, usando Policy y mensajes claros sin mutar estados administrativos.
- [x] T047 Agregar rutas PATCH `/account/photos/order` y POST `/account/photos/{photo}/primary` en `routes/web.php`, con binding/policy sin aceptar `model_profile_id`.
- [x] T048 Integrar controles de reorder y principal en `resources/views/account/photos/index.blade.php` y sus estilos en `resources/css/styles.css`, incluyendo feedback de error/éxito responsive.

**Checkpoint**: el orden es persistente y seguro; sólo una fotografía `approved` puede ser principal.

---

## Phase 6: User Story 3 — Reemplazo y eliminación (Priority: P1)

**Goal**: la modelo puede reemplazar fotos `pending`, `rejected` o `approved` mediante nuevas versiones y eliminar fotos propias sin perder la versión aprobada vigente.

**Independent Test**: reemplazar una foto en cada estado, comprobar promoción/rechazo de la nueva versión, conservar la approved anterior y eliminar la principal con fallback.

### Tests for User Story 3

- [x] T049 [P] [US3] Crear `tests/Feature/Account/ModelPhotoReplacementTest.php` para reemplazo válido de pending, rejected y approved con auditoría de la nueva versión limpia.
- [x] T050 [P] [US3] Agregar en `tests/Feature/Account/ModelPhotoReplacementTest.php` que una approved reemplazada conserva `current_version_id` e `is_primary` mientras la nueva está pending.
- [x] T051 [P] [US3] Agregar en `tests/Feature/Account/ModelPhotoReplacementTest.php` promoción de la nueva versión aprobada y conservación de la approved anterior cuando la nueva es rechazada.
- [x] T052 [P] [US3] Agregar en `tests/Feature/Account/ModelPhotoReplacementTest.php` fallo de procesamiento/escritura con archivo y registro anteriores intactos, sin huérfanos de la nueva operación.
- [x] T053 [P] [US3] Crear `tests/Feature/Account/ModelPhotoDeletionTest.php` para eliminar DB+variantes, proteger ownership, compactar posiciones y aplicar fallback de principal.

### Implementation for User Story 3

- [x] T054 Crear `app/Http/Requests/ModelPhotoReplaceRequest.php` con las mismas reglas configurables de upload y sin aceptar status, auditoría, paths o profile IDs.
- [x] T055 Agregar `replaceForAuthenticatedModel()` en `app/Services/ModelPhotoService.php` para crear una nueva versión, limpiar rejection/auditoría y conservar la versión current approved anterior.
- [x] T056 Agregar `deleteForAuthenticatedModel()` en `app/Services/ModelPhotoService.php` para ownership, eliminación controlada de variantes, compactación y selección de primera approved por position o ninguna.
- [x] T057 Agregar métodos `replace()` y `destroy()` en `app/Http/Controllers/ModelPhotoController.php`, incluyendo confirmación/flash messages y manejo de errores de dominio.
- [x] T058 Agregar rutas POST `/account/photos/{photo}/replace` y DELETE `/account/photos/{photo}` en `routes/web.php` con Policy, CSRF y binding seguro.
- [x] T059 Extender `resources/views/account/photos/index.blade.php` con reemplazo por estado, rejection_reason, eliminación y confirmaciones accesibles sin mostrar paths ni datos internos.
- [x] T060 Agregar `promoteApprovedVersion()` a `app/Services/ModelPhotoService.php` para promover transaccionalmente una versión aprobada, conservar principalidad y limpiar la versión física anterior sólo después del éxito.
- [x] T061 Actualizar `resources/css/styles.css` para estados de rechazo, acciones de reemplazo/eliminación, confirmaciones y comportamiento mobile sin introducir framework CSS.

**Checkpoint**: reemplazos y eliminaciones son seguros, versionados y no dejan sin foto approved vigente cuando existe.

---

## Phase 7: User Story 4 — Moderación Filament (Priority: P1)

**Goal**: un administrador verificado puede revisar versiones pendientes, compararlas con la approved vigente y aprobarlas o rechazarlas con auditoría.

**Independent Test**: abrir el detalle de una modelo en Filament, aprobar y rechazar pendientes y verificar transiciones, motivos, auditoría y permisos.

### Tests for User Story 4

- [x] T062 [P] [US4] Crear `tests/Feature/Admin/ModelPhotoModerationTest.php` para acceso de admin verificado, usuario común y admin no verificado.
- [x] T063 [P] [US4] Agregar en `tests/Feature/Admin/ModelPhotoModerationTest.php` pending→approved con `reviewed_at`, `reviewed_by`, current version y limpieza de rejection_reason.
- [x] T064 [P] [US4] Agregar en `tests/Feature/Admin/ModelPhotoModerationTest.php` pending→rejected con motivo obligatorio y rechazo sin mutación cuando el motivo falta.
- [x] T065 [P] [US4] Agregar en `tests/Feature/Admin/ModelPhotoModerationTest.php` transiciones inválidas, comparación de replacement approved y no exposición de storage_path.

### Implementation for User Story 4

- [x] T066 Crear `app/Services/ModelPhotoModerationService.php` con `approve()` y `reject()` transaccionales, estados válidos, variantes completas, auditoría y cleanup posterior.
- [x] T067 Extender `app/Policies/ModelPhotoPolicy.php` con `moderate`, `approve`, `reject` y `deliverPrivate` sólo para `isVerifiedAdmin()` y sin asumir cualquier usuario autenticado.
- [x] T068 Crear `app/Filament/Resources/ModelProfiles/RelationManagers/ModelPhotosRelationManager.php` con eager loading, metadata, badges, thumbnail/processed privado y comparación con current approved.
- [x] T069 Agregar acciones Filament approve/reject en `app/Filament/Resources/ModelProfiles/RelationManagers/ModelPhotosRelationManager.php`, confirmación, modal con motivo obligatorio y refresh visual.
- [x] T070 Ajustar `app/Filament/Resources/ModelProfiles/Pages/ViewModelProfile.php` o la página de detalle existente para registrar el Relation Manager sin crear CRUD libre de paths/estados.
- [x] T071 Agregar entrega privada controlada para administración en `app/Http/Controllers/ModelPhotoController.php` y rutas autorizadas en `routes/web.php`, usando Storage y headers sin URLs públicas.
- [x] T072 Verificar que `app/Services/ModelPhotoModerationService.php` no modifique `email_verified_at`, `identity_status`, `review_status` ni `is_published`, y documentar la separación en `contracts/model-photos.md` si el código requiere precisión adicional.

**Checkpoint**: Filament modera sólo versiones `pending`, registra auditoría y mantiene independientes los estados del perfil.

---

## Phase 8: Security / Authorization Hardening

**Purpose**: revisar todos los límites de seguridad de la feature después de completar los flujos.

- [x] T073 Revisar todas las rutas de `routes/web.php` para confirmar `auth`, `verified`, CSRF, Policy, ownership y ausencia de `model_profile_id` controlado por cliente.
- [x] T074 Revisar `app/Policies/ModelPhotoPolicy.php` y `app/Services/ModelPhotoService.php` para acceso cruzado, IDs ajenos, estados directos, auditoría y principalidad server-side.
- [x] T075 Revisar `app/Services/ModelPhotoStorage.php` para path traversal, prefijos, rutas absolutas, nombres aleatorios, headers y ausencia de acceso fuera del disk `model_photos`.
- [x] T076 Revisar `app/Http/Requests/ModelPhotoUploadRequest.php`, `ModelPhotoReplaceRequest.php` y `ModelPhotoReorderRequest.php` para MIME real, dimensiones, 5 MB, máximo 5 y datos no mass-assignable.
- [x] T077 Agregar en `tests/Feature/Security/ModelPhotoAuthorizationTest.php` casos no autenticado, acceso cruzado, admin no verificado, manipulación de IDs/profile IDs, path traversal y transición inválida directa.
- [x] T078 Agregar en `tests/Feature/Security/ModelPhotoPrivacyTest.php` verificación de ausencia en `public/`, ausencia de URL pública, no exposición de storage_path/nombres físicos y separación respecto del storage de identidad.
- [x] T079 Revisar logging en `app/Services/ModelPhoto*.php` y configuración de excepciones para evitar binarios, tokens, paths privados, datos originales y metadata sensible.
- [x] T080 Revisar locks/transacciones en `app/Services/ModelPhotoService.php` y `ModelPhotoModerationService.php` para carreras de principal, reorder, reemplazo y aprobación de versiones obsoletas.

---

## Phase 9: Testing / Polish / Documentation

**Purpose**: validar la feature completa, mejorar la interfaz y mantener la documentación sincronizada.

- [ ] T081 Crear `tests/Feature/ModelPhotos/ModelPhotosRegressionTest.php` para regresión completa de features 001–004, incluyendo registro/login, identidad privada, Filament y `/account`.
- [ ] T082 Crear `tests/Feature/ModelPhotos/ModelPhotoStateIsolationTest.php` para confirmar que moderar fotos no cambia `email_verified_at`, `identity_status`, `review_status` ni `is_published`.
- [ ] T083 Agregar en `tests/Feature/ModelPhotos/ModelPhotoProcessingTest.php` assertions de las cuatro variantes, watermark sólo en public, original sin watermark, dimensiones y cleanup.
- [ ] T084 Revisar eager loading y consultas de la galería y Relation Manager en `app/Models/ModelPhoto.php`, `app/Models/ModelProfile.php` y `app/Filament/Resources/ModelProfiles/RelationManagers/ModelPhotosRelationManager.php` para evitar N+1.
- [ ] T085 Revisar índices y constraints documentados en `database/migrations/` y sincronizar cualquier decisión final en `specs/005-model-photos/data-model.md`.
- [ ] T086 Revisar `resources/views/account/photos/index.blade.php` para labels, badges, estados vacíos, errores, éxito, accesibilidad y responsive desktop/tablet/mobile según `design.md` y `resources/css/styles.css`.
- [ ] T087 Revisar `resources/css/styles.css` para eliminar duplicaciones, mantener clases reutilizables, limitar iconos/imágenes y asegurar que no se agregue framework CSS nuevo.
- [ ] T088 Revisar rutas privadas y Filament con `php artisan route:list`, caché de vistas con `php artisan view:cache` y limpieza con `php artisan optimize:clear`, documentando resultados en `specs/005-model-photos/quickstart.md`.
- [ ] T089 Actualizar `specs/005-model-photos/quickstart.md` con instalación GD/Intervention, configuración del disk, comandos, fixtures, flujo de replacement, moderación y prohibición de `storage:link` para fotos privadas.
- [ ] T090 Sincronizar `specs/005-model-photos/data-model.md`, `specs/005-model-photos/contracts/model-photos.md` y `specs/005-model-photos/research.md` con cualquier decisión implementada sobre retención, versiones y entrega privada.
- [ ] T091 Ejecutar la suite completa con `php artisan test` y registrar en `specs/005-model-photos/quickstart.md` el resultado de upload, processing, principal, reorder, replacement, deletion, moderation y regresión.
- [ ] T092 Ejecutar `vendor/bin/pint --test` y corregir formato en los archivos PHP de la feature antes de cerrar `specs/005-model-photos/tasks.md`.
- [ ] T093 Validar manualmente `/account/photos` en 360px, tablet y desktop, y el detalle Filament con un admin verificado, documentando la revisión visual en `specs/005-model-photos/quickstart.md`.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: no depende de otras fases; T001 debe completarse antes de ejecutar procesamiento real.
- **Foundation (Phase 2)**: depende de Setup y bloquea todas las historias.
- **Storage/Processing (Phase 3)**: depende de Foundation; bloquea US1 y cualquier operación de variantes.
- **US1 (Phase 4)**: depende de Phases 1–3; entrega el MVP mínimo funcional.
- **US2 (Phase 5)**: depende de US1 porque necesita fotos cargadas, aunque sus servicios pueden desarrollarse en paralelo al cierre de US1 si los contratos ya están estables.
- **US3 (Phase 6)**: depende de US1 y de la base de US2 para principalidad/eliminación; requiere versionado y cleanup de Phase 3.
- **US4 (Phase 7)**: depende de US1 y US3 para moderar versiones iniciales y reemplazos aprobados.
- **Security hardening (Phase 8)**: depende de US1–US4 implementadas para revisar el flujo completo.
- **Polish/final (Phase 9)**: depende de todas las fases funcionales y de seguridad.

### User Story Dependencies

- **US1 (P1)**: Foundation + Storage/Processing.
- **US2 (P1)**: US1 para datos existentes y endpoint de galería.
- **US3 (P1)**: US1 + US2 + versionado de Foundation/Processing.
- **US4 (P1)**: US1 + US3; no modifica los estados de identidad/revisión/publicación existentes.

### Parallel Opportunities

- T002, T003 y T004 pueden ejecutarse en paralelo después de confirmar T001.
- T010 y T011 pueden ejecutarse en paralelo; T012/T013 dependen de las relaciones definidas.
- T018–T021 pueden dividirse por responsabilidad del procesador, pero T022 depende de todas.
- Los tests de cada historia pueden escribirse en paralelo entre sí antes de la implementación.
- T039–T042 pueden desarrollarse en paralelo; T043–T045 deben preceder a T046–T048.
- T049–T053 pueden desarrollarse en paralelo; T054–T056 y T060 requieren coordinación sobre el modelo de versiones.
- T062–T065 pueden desarrollarse en paralelo; T068 y T069 deben compartir el diseño del Relation Manager.
- T077–T079 pueden ejecutarse en paralelo; T080 requiere revisar los servicios completos.

### Parallel Example: MVP

```text
T001 → T002/T003/T004
T005–T015 → T016–T025
T026–T029 (tests) || T030–T033 (Policy, Request y Service)
T034–T038 (controller, rutas, Blade y responsive)
```

## Implementation Strategy

### MVP recomendado

1. Completar Setup, Foundation y Storage/Processing.
2. Completar US1: carga inicial privada, procesamiento de variantes, límite, validaciones y galería Blade.
3. Ejecutar T026–T029, T024–T025 y la regresión mínima de features 001–004.
4. Detenerse y validar que una modelo verificada pueda cargar una foto y que ningún original/pending sea público.

### Entrega incremental

1. Agregar US2 para ordenar y seleccionar principal aprobada.
2. Agregar US3 para reemplazo versionado y eliminación segura.
3. Agregar US4 para moderación Filament y auditoría.
4. Ejecutar Security hardening y luego Polish/documentación.

## Risk Notes

- Procesar cuatro variantes dentro del request puede consumir memoria; verificar GD, imágenes de 5000x5000 y considerar cola futura sin cambiar el contrato del servicio.
- La promoción de una versión approved y la limpieza posterior al commit requieren pruebas de fallos y locks para no perder la versión vigente.
- Las constraints de una única principal y posiciones densas pueden depender de capacidades de MySQL; el servicio debe reforzarlas transaccionalmente.
- La marca de agua debe validarse visualmente con el logo existente y configurarse sin alterar el original.
- Los archivos privados no deben exponerse accidentalmente mediante `storage:link`, assets, exports, logs o respuestas HTML.

## Completion Criteria

- Todas las tareas funcionales, de seguridad, testing y polish están marcadas como completadas.
- La suite completa y Pint pasan.
- Las vistas Blade compilan y son responsive.
- Las rutas privadas no exponen archivos ni paths físicos.
- `spec.md`, `plan.md`, `research.md`, `data-model.md`, `contracts/model-photos.md` y `quickstart.md` están sincronizados.
