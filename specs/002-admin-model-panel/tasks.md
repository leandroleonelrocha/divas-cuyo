---

description: "Tareas de implementación del panel administrativo de modelos"
---

# Tasks: Panel administrativo de modelos

**Input**: Design documents from `specs/002-admin-model-panel/`

**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/admin-panel.md` y `quickstart.md`

**Organization**: tareas agrupadas por historia de usuario, en orden de dependencia. Las tareas con `[P]` pueden ejecutarse en paralelo cuando sus dependencias indicadas ya estén completas.

**Scope guard**: no implementar fotos, videos, planes, favoritos ni comentarios; no modificar controllers, models, services, policies, migrations ni rutas fuera de los cambios explícitamente necesarios para esta feature.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: preparar Filament 5 y la estructura del panel sin alterar el frontend público Blade.

- [X] T001 Instalar `filament/filament:^5.0` con Composer y registrar el cambio únicamente en `composer.json` y `composer.lock`.
- [X] T002 Generar la infraestructura de panel con `php artisan filament:install --panels` y configurar el panel `admin` en `app/Providers/Filament/AdminPanelProvider.php` con path `/admin`.
- [X] T003 [P] Verificar en `config/database.php`, `.env.example` y `phpunit.xml` que los entornos de aplicación y tests continúan usando MySQL/configuración existente sin agregar otra base de datos.
- [X] T004 [P] Documentar en `specs/002-admin-model-panel/quickstart.md` la instalación, promoción controlada del primer administrador y comandos de verificación definidos por el plan.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: crear el esquema, las relaciones y la compatibilidad de datos que todas las historias necesitan.

**CRITICAL**: ninguna historia puede considerarse implementable hasta completar esta fase.

- [X] T005 Crear la migración `database/migrations/*_add_is_admin_to_users_table.php` con `users.is_admin` booleano no nulo, default `false`, índice y rollback seguro.
- [X] T006 Crear la migración `database/migrations/*_create_model_profiles_table.php` con `id`, `user_id`, `name`, `whatsapp`, `location`, `review_status`, `is_published`, timestamps, foreign key `users.id` con `ON DELETE CASCADE`, unique sobre `user_id` e índices definidos en `data-model.md`.
- [X] T007 Crear la migración de datos `database/migrations/*_backfill_model_profiles_from_users.php` para copiar los perfiles existentes desde `users` mediante Query Builder, conservar publicación preexistente de forma segura y asignar estados de revisión sin inventar aprobación.
- [X] T008 [P] Crear `app/Models/ModelProfile.php` con casts, asignación protegida, estados permitidos y relaciones `belongsTo(User::class)`.
- [X] T009 [P] Actualizar `app/Models/User.php` para el cast/atributo `is_admin`, la relación `hasOne(ModelProfile::class)` y el método `isAdmin()` sin exponer credenciales ni cambiar el login público.
- [X] T010 Verificar con pruebas de migración en `tests/Feature/Admin/ModelProfileMigrationTest.php` los conteos del backfill, foreign key, unique `user_id`, defaults, índices relevantes y reversión estructural.
- [X] T011 Adaptar el flujo de registro existente en `app/Http/Controllers/Auth/RegistrationController.php` para crear `ModelProfile` junto con `User` en una transacción, conservando rutas, names, `old()`, errores y validaciones actuales.
- [X] T012 Verificar que `app/Http/Controllers/AccountController.php`, `resources/views/account/show.blade.php` y `app/Notifications/VerifyModelEmail.php` mantienen compatibilidad con las columnas legacy durante la transición, sin cambiar el comportamiento público.
- [X] T013 [P] Agregar regresión del registro público en `tests/Feature/Admin/ModelProfileMigrationTest.php`, verificando creación por relación, errores de validación, `old()`, email verification y rutas existentes.
- [X] T014 Ejecutar `php artisan migrate --force` sobre MySQL y `php artisan test --filter=ModelProfileMigrationTest`, revisar integridad y confirmar que toda cuenta existente tiene como máximo un perfil relacionado.

**Checkpoint**: esquema MySQL, modelo, relación y registro público compatibles; las historias administrativas pueden implementarse.

---

## Phase 3: User Story 1 - Acceder de forma restringida al panel (Priority: P1) 🎯 MVP

**Goal**: permitir el panel únicamente a administradores autenticados y verificados, rechazando a usuarios públicos y no autenticados.

**Independent Test**: una cuenta administradora verificada ve `/admin`; una cuenta de modelo, una cuenta no verificada y una persona no autenticada no reciben datos ni acciones administrativas.

### Tests for User Story 1

- [X] T015 [P] [US1] Crear `tests/Feature/Admin/AdminPanelAccessTest.php` con casos de acceso permitido para administrador verificado, redirección/autenticación para invitado y rechazo para usuario no administrador o no verificado.
- [X] T016 [P] [US1] Agregar en `tests/Feature/Admin/AdminPanelAccessTest.php` un caso de invocación directa a una URL/acción administrativa por usuario sin permisos, verificando respuesta rechazada y ausencia de datos privados.

### Implementation for User Story 1

- [X] T017 [US1] Implementar `FilamentUser` y `canAccessPanel(Panel $panel)` en `app/Models/User.php`, limitado al panel `admin`, `is_admin === true` y email verificado.
- [X] T018 [US1] Configurar autenticación, branding y middleware del panel en `app/Providers/Filament/AdminPanelProvider.php` sin crear rutas públicas nuevas ni reemplazar el login público.
- [X] T019 [US1] Definir la forma controlada de promover administradores en `app/Console/Commands/PromoteUserToAdmin.php`, sin UI de autoasignación y sin modificar usuarios automáticamente.
- [X] T020 [US1] Ejecutar `tests/Feature/Admin/AdminPanelAccessTest.php` y comprobar que el panel no expone listado, detalle ni acciones a identidades no autorizadas.

**Checkpoint**: acceso exclusivo al panel verificado y probado.

---

## Phase 4: User Story 2 - Consultar y encontrar modelos registradas (Priority: P1)

**Goal**: listar perfiles registrados y encontrarlos por nombre, email, WhatsApp o ubicación, con paginación y filtros de estado.

**Independent Test**: con varias cuentas, un administrador busca cada campo, combina búsqueda/filtros y pagina sin perder criterios; una cuenta no autorizada no puede consultar.

### Tests for User Story 2

- [X] T021 [P] [US2] Crear `tests/Feature/Admin/ModelProfileResourceTest.php` para comprobar listado paginado con nombre, email, fecha de registro, verificación, revisión y publicación.
- [X] T022 [P] [US2] Agregar pruebas de búsqueda parcial por `name`, `user.email`, `whatsapp` y `location`, además del caso sin coincidencias, en `tests/Feature/Admin/ModelProfileResourceTest.php`.
- [X] T023 [P] [US2] Agregar pruebas de filtros por verificación de email, `review_status` y `is_published`, y persistencia de criterios al paginar, en `tests/Feature/Admin/ModelProfileResourceTest.php`.

### Implementation for User Story 2

- [X] T024 [US2] Crear `app/Filament/Resources/ModelProfiles/ModelProfileResource.php` con navegación administrativa, query base `ModelProfile::query()->with('user')`, sin páginas de crear/eliminar y respetando la policy.
- [X] T025 [US2] Crear `app/Filament/Resources/ModelProfiles/Pages/ListModelProfiles.php` y configurar tabla paginada con columnas, estados visualmente distinguibles y orden por fecha de registro.
- [X] T026 [US2] Configurar búsqueda global de la tabla sobre `name`, `user.email`, `whatsapp` y `location`, con consultas relacionales e índices compatibles, en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`.
- [X] T027 [US2] Configurar filtros de verificación (`user.email_verified_at`), revisión (`pending`, `approved`, `rejected`) y publicación en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`.
- [X] T028 [US2] Ejecutar `tests/Feature/Admin/ModelProfileResourceTest.php` y revisar que el listado no produzca N+1 mediante eager loading de `user`.

**Checkpoint**: una administradora puede localizar modelos y distinguir sus estados desde el listado.

---

## Phase 5: User Story 3 - Revisar el detalle y el estado de una modelo (Priority: P1)

**Goal**: mostrar en detalle la información operativa y separar claramente email verificado, revisión y publicación.

**Independent Test**: abrir perfiles pendientes, aprobados, rechazados, publicados y no verificados; todos muestran sus datos y estados correctos sin confundirlos.

### Tests for User Story 3

- [X] T029 [P] [US3] Agregar casos de detalle para datos básicos, email, WhatsApp, ubicación, fecha, verificación, revisión y publicación en `tests/Feature/Admin/ModelProfileResourceTest.php`.
- [X] T030 [P] [US3] Agregar casos de detalle para perfil no verificado, aprobado/no publicado y rechazado/no publicado en `tests/Feature/Admin/ModelProfileResourceTest.php`.

### Implementation for User Story 3

- [X] T031 [US3] Crear `app/Filament/Resources/ModelProfiles/Pages/ViewModelProfile.php` con Infolist read-only para identidad, contacto y estados, sin contraseñas ni secretos.
- [X] T032 [US3] Configurar en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php` labels y badges separados para verificación, `review_status` y `is_published`, incluyendo estados `pending`, `approved`, `rejected`, verificado/no verificado y publicado/no publicado.
- [X] T033 [US3] Manejar en `app/Filament/Resources/ModelProfiles/Pages/ViewModelProfile.php` el registro inexistente/eliminado con respuesta controlada y sin ejecutar acciones sobre un modelo ausente.
- [X] T034 [US3] Ejecutar los tests de detalle de `tests/Feature/Admin/ModelProfileResourceTest.php` con el usuario administrador y confirmar que las rutas públicas permanecen sin cambios.

**Checkpoint**: detalle operativo completo y estados no ambiguos.

---

## Phase 6: User Story 4 - Moderar y controlar la publicación (Priority: P1)

**Goal**: aplicar aprobación/rechazo y publicación/despublicación con invariantes, confirmación y autorización server-side.

**Independent Test**: aprobar, rechazar, publicar y despublicar perfiles en cada estado; publicar perfiles pendientes/rechazados debe fallar y rechazar un publicado debe despublicarlo.

### Tests for User Story 4

- [X] T035 [P] [US4] Crear `tests/Feature/Admin/ModelProfileModerationTest.php` para aprobación de pendiente sin publicación automática y rechazo con publicación forzada a `false`.
- [X] T036 [P] [US4] Agregar pruebas de publicar/despublicar, publicación bloqueada para pendiente/rechazado y conservación de aprobación al despublicar en `tests/Feature/Admin/ModelProfileModerationTest.php`.
- [X] T037 [P] [US4] Agregar pruebas de autorización directa, confirmación/resultado visible y consistencia ante actualización concurrente en `tests/Feature/Admin/ModelProfileModerationTest.php`.

### Implementation for User Story 4

- [X] T038 [US4] Crear `app/Services/ModelProfileModerationService.php` con transacciones y métodos `approve`, `reject`, `publish` y `unpublish`, aplicando todas las transiciones de `data-model.md`.
- [X] T039 [US4] Crear `app/Policies/ModelProfilePolicy.php` con `viewAny`, `view`, `update` y métodos explícitos para moderación/publicación, requiriendo administrador en servidor y sin permitir crear/eliminar desde el recurso.
- [X] T040 [US4] Agregar acciones de tabla confirmables para aprobar/rechazar en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`, delegando al servicio y actualizando listado/detalle.
- [X] T041 [US4] Agregar acciones de tabla confirmables para publicar/despublicar en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`, deshabilitando estados no elegibles en UI y revalidándolos en el servicio.
- [X] T042 [US4] Registrar la policy en `app/Providers/AppServiceProvider.php` o mediante descubrimiento Laravel, y verificar que Filament la aplique al recurso y sus páginas.
- [X] T043 [US4] Ejecutar `tests/Feature/Admin/ModelProfileModerationTest.php` y validar que aprobar nunca verifica email, rechazar siempre despublica y ninguna acción modifica credenciales.

**Checkpoint**: moderación y publicación operan de forma independiente, segura y auditable por estado actual.

---

## Phase 7: User Story 5 - Corregir información básica (Priority: P2)

**Goal**: permitir editar nombre público, WhatsApp y ubicación sin modificar email, verificación, contraseña o publicación implícita.

**Independent Test**: guardar datos válidos y verlos en listado/detalle; una edición inválida conserva datos anteriores; editar un perfil publicado no cambia publicación.

### Tests for User Story 5

- [X] T044 [P] [US5] Crear pruebas de edición válida de `name`, `whatsapp` y `location` en `tests/Feature/Admin/ModelProfileResourceTest.php` y verificar reflejo en listado/detalle.
- [X] T045 [P] [US5] Agregar pruebas de validación inválida, rollback sin modificación parcial, preservación de verificación y publicación en `tests/Feature/Admin/ModelProfileResourceTest.php`.
- [X] T046 [P] [US5] Agregar prueba de que email, password, `email_verified_at` e `is_admin` no son campos editables del recurso en `tests/Feature/Admin/ModelProfileResourceTest.php`.

### Implementation for User Story 5

- [X] T047 [US5] Crear `app/Filament/Resources/ModelProfiles/Pages/EditModelProfile.php` con formulario limitado a `name`, `whatsapp` y `location`, usando validaciones server-side y policy `update`.
- [X] T048 [US5] Configurar validaciones y mensajes de error del formulario Filament en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`, alineados con las validaciones públicas actuales.
- [X] T049 [US5] Verificar guardado atómico y no mutación de `user.email`, `password`, `email_verified_at`, `review_status` o `is_published` durante edición en `app/Filament/Resources/ModelProfiles/Pages/EditModelProfile.php`.
- [X] T050 [US5] Ejecutar las pruebas de edición de `tests/Feature/Admin/ModelProfileResourceTest.php` y comprobar errores claros sin pérdida de datos previos.

**Checkpoint**: edición básica disponible con alcance restringido y sin efectos colaterales.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: cerrar compatibilidad, seguridad, documentación y verificación integral.

- [X] T051 [P] Revisar `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`, `app/Policies/ModelProfilePolicy.php` y `app/Services/ModelProfileModerationService.php` para impedir exposición de contraseñas, tokens u otros secretos.
- [X] T052 [P] Actualizar `specs/002-admin-model-panel/quickstart.md` con los escenarios manuales completos de acceso, búsqueda, detalle, moderación, edición y regresión pública.
- [X] T053 Ejecutar `php artisan test` y confirmar los criterios de autorización, MySQL, migración, registro Blade y flujo administrativo.
- [X] T054 Ejecutar `vendor/bin/pint --test` y corregir únicamente formato de los archivos involucrados en la feature.
- [X] T055 Ejecutar `php artisan route:list`, `php artisan view:cache` y `php artisan optimize:clear` para validar rutas, compilación Blade y configuración sin modificar rutas públicas.
- [X] T056 Revisar `git diff --stat` y `git diff --name-only` para confirmar que no se tocaron fotos, videos, planes, favoritos, comentarios ni lógica ajena al alcance; documentar cualquier columna legacy pendiente de retirar.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 Setup**: sin dependencias; T001 y T002 son secuenciales, T003 y T004 pueden ejecutarse en paralelo con ellos cuando se conozca la estructura generada.
- **Phase 2 Foundational**: depende de T001/T002; T005 y T006 pueden comenzar en paralelo, pero T007 depende de T006; T008/T009 pueden comenzar en paralelo con migraciones; T011/T012 dependen de T008/T009 y T006/T007.
- **US1**: depende de T005, T008, T009 y T017/T018; bloquea el acceso seguro de las historias posteriores.
- **US2**: depende de US1 y T008; puede implementarse en paralelo con US3 después de estar disponible el recurso base.
- **US3**: depende de US2/T024 porque reutiliza `ModelProfileResource`; puede desarrollarse en paralelo con pruebas de US2 tras crear el recurso.
- **US4**: depende de US1, T008/T009 y T024; las pruebas T035–T037 pueden escribirse antes de T038–T042.
- **US5**: depende de T039 y T024; puede desarrollarse en paralelo con US3/US4 si se coordinan los cambios sobre el resource.
- **Polish**: depende de todas las historias que se decidan entregar.

### User Story Completion Order

1. **US1 (P1)**: acceso restringido; requisito de seguridad y MVP mínimo.
2. **US2 (P1)**: listado, búsqueda y filtros.
3. **US3 (P1)**: detalle y estados.
4. **US4 (P1)**: moderación y publicación.
5. **US5 (P2)**: edición básica.

US2 y US3 comparten el recurso, por lo que se recomienda entregar US2 primero; US4 y US5 son paralelizables por archivos de tests/servicio/página, pero deben coordinar modificaciones a `ModelProfileResource.php`.

### Parallel Opportunities

- **Setup**: T003 y T004.
- **Foundation**: T005/T006; T008/T009; T010 puede prepararse mientras finaliza el backfill, y T013 puede escribirse con el contrato público definido.
- **US1**: T015/T016; luego T019 puede hacerse separado de T018.
- **US2**: T021/T022/T023; implementación de tabla y filtros debe integrarse después.
- **US3**: T029/T030 y el diseño del Infolist T031 pueden prepararse en paralelo.
- **US4**: T035/T036/T037; servicio T038 y policy T039 pueden implementarse en paralelo antes de conectar acciones.
- **US5**: T044/T045/T046; formulario T047 y reglas T048 pueden coordinarse por archivos separados.
- **Polish**: T051/T052 en paralelo; T053–T056 después de integrar todo.

## Parallel Example: Foundation and User Stories

```text
Después de T001/T002:
  Trabajador A: T005 -> T006 -> T007 -> T010
  Trabajador B: T008 -> T009
  Trabajador C: T003 -> T004

Después de la fundación:
  Trabajador A: US1 (T015-T020)
  Trabajador B: preparar US2 (T021-T027)
  Trabajador C: preparar US3 (T029-T032)

Después de T024 y US1:
  Trabajador A: US4 (T035-T043)
  Trabajador B: US5 (T044-T050)
```

## Implementation Strategy

### MVP First

1. Completar Setup y Foundation.
2. Completar US1: panel accesible sólo por administradores verificados.
3. Validar T015–T020 y detenerse para revisión de seguridad.

### Incremental Delivery

1. Agregar US2 para localizar modelos.
2. Agregar US3 para inspeccionar estados.
3. Agregar US4 para moderar y publicar.
4. Agregar US5 para editar datos básicos.
5. Ejecutar Polish y regresión pública después de cada incremento relevante.

### Format Validation

Todas las tareas usan el formato obligatorio `- [ ] T### [P?] [US#?] descripción con ruta exacta`. Las tareas de Setup, Foundation y Polish no llevan etiqueta de historia; las tareas dentro de fases de usuario llevan `[US1]` a `[US5]`; `[P]` aparece sólo cuando la tarea puede ejecutarse en paralelo según las dependencias descritas.
