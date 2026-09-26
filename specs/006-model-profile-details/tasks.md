---

description: "Task list for the model profile details feature"
---

# Tasks: Detalles ampliados del perfil de modelo

**Input**: Design documents from `/specs/006-model-profile-details/`

**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/`, `quickstart.md`

**Organization**: Las tareas están agrupadas por historia de usuario para permitir implementación, prueba y entrega incremental.

## Phase 1: Setup

**Purpose**: Preparar el inventario y los puntos de integración sin alterar todavía el comportamiento existente.

- [X] T001 Revisar `app/Models/ModelProfile.php`, `app/Models/User.php`, `routes/web.php`, `app/Filament/Resources/ModelProfiles/ModelProfileResource.php` y los tests existentes para documentar los campos heredados que deben seguir funcionando.
- [X] T002 [P] Confirmar la ausencia de `docs/design.md` y registrar en `specs/006-model-profile-details/research.md` que `resources/css/styles.css` y las vistas aprobadas serán la referencia visual temporal.
- [X] T003 [P] Crear la estructura de pruebas `tests/Feature/Profile/` y verificar que `tests/TestCase.php` y `RefreshDatabase` están disponibles para las nuevas entidades.

---

## Phase 2: Foundational

**Purpose**: Crear la base persistente, relaciones y límites compartidos que bloquean todas las historias.

**⚠️ CRITICAL**: Esta fase debe completarse antes de implementar historias de usuario.

- [X] T004 Crear migraciones reversibles para `publication_types`, `services`, `provinces` y `localities` con slugs únicos, activación, relaciones e índices en `database/migrations/`.
- [X] T005 Crear migración para `model_profile_private_details` con relación 1:1, `birth_date`, campos administrativos y unique `model_profile_id` en `database/migrations/`.
- [X] T006 Crear migración para ampliar `model_profiles` con `stage_name`, edad pública, datos físicos aprobados, disponibilidad, `province_id`, `locality_id`, ubicación aproximada, `publication_type_id` y `current_bio_id` sin romper columnas heredadas en `database/migrations/`.
- [X] T007 Crear migraciones para `model_profile_service`, `model_profile_publication_type_history`, `model_profile_bios` y `model_profile_physical_revisions` con claves foráneas, estados, índices y restricciones en `database/migrations/`.
- [X] T008 Implementar la migración de compatibilidad que preserve `users` y `model_profiles` heredados, copie sólo valores deterministas y conserve textos de ubicación no mapeables en `approximate_location_text` en `database/migrations/`.
- [X] T009 [P] Crear `PublicationType`, `Service`, `Province` y `Locality` con casts, fillable, relaciones y scopes de activos en `app/Models/`.
- [X] T010 [P] Crear `ModelProfilePrivateDetail`, `ModelProfileBio`, `ModelProfilePhysicalRevision` y `ModelProfilePublicationTypeHistory` con relaciones, casts y fillable protegidos en `app/Models/`.
- [X] T011 Ampliar `app/Models/ModelProfile.php` con relaciones, casts, fillable canónico y métodos para edad real, bio actual, tipo, servicios, catálogos e historial sin exponer datos privados por defecto.
- [X] T012 [P] Crear enums o constantes de estados para disponibilidad, bios, revisiones físicas y tipos de servicio en `app/Enums/`.
- [X] T013 Crear factories y seeders mínimos para tipos `virtual`/`encounters`, servicios iniciales y provincias iniciales en `database/factories/` y `database/seeders/`.
- [X] T014 Ampliar `app/Policies/ModelProfilePolicy.php` y crear policies para bios y revisiones físicas con ownership de modelo, administración verificada y prohibición de auto-moderación en `app/Policies/`.
- [X] T015 Crear reglas compartidas para edad pública, pertenencia de localidad a provincia y compatibilidad de servicios en `app/Rules/`.
- [X] T016 [P] Crear tests de esquema, relaciones, índices, estados, claves foráneas y compatibilidad de migración en `tests/Feature/Profile/ProfileSchemaTest.php`.
- [X] T017 Actualizar `database/factories/UserFactory.php` y los datos de registro sólo donde sea necesario para que las nuevas relaciones se creen sin romper los tests de autenticación, cuenta, identidad y fotos.

**Checkpoint**: migraciones, modelos, catálogos, factories, policies y reglas compartidas están disponibles; ninguna historia depende de columnas inexistentes.

---

## Phase 3: User Story 1 - Completar datos privados y públicos (Priority: P1) 🎯 MVP

**Goal**: Permitir que la modelo complete su información privada y pública sin mezclarla con la cuenta ni exponer datos sensibles.

**Independent Test**: Una modelo autenticada guarda datos privados y públicos; el perfil propio los muestra separados, una solicitud con perfil ajeno es rechazada y ninguna salida pública contiene datos reales.

### Tests for User Story 1

- [X] T018 [P] [US1] Crear tests de ownership, privacidad, separación de cuenta y validación de campos en `tests/Feature/Profile/ModelProfileDetailsTest.php`.
- [X] T019 [P] [US1] Crear tests de no exposición pública de nombre real, fecha de nacimiento, teléfono privado, documentos y datos de revisión en `tests/Feature/Profile/ModelProfilePrivacyTest.php`.
- [X] T020 [P] [US1] Crear tests de revisión física pendiente/aprobada/rechazada y conservación de la versión aprobada en `tests/Feature/Profile/ModelProfilePhysicalRevisionTest.php`.

### Implementation for User Story 1

- [X] T021 [US1] Crear `app/Http/Requests/AccountProfileUpdateRequest.php` para validar ownership, datos privados, `stage_name`, campos públicos obligatorios y valores manipulados.
- [X] T022 [US1] Crear `app/Services/ModelProfileDetailsService.php` para guardar datos privados/públicos en una transacción sin aceptar `user_id` o `model_profile_id` del cliente.
- [X] T023 [US1] Crear `app/Services/ModelProfilePhysicalModerationService.php` y su flujo de revisión para crear snapshots pendientes y promover sólo revisiones aprobadas a `model_profiles`.
- [X] T024 [US1] Crear `app/Http/Controllers/AccountProfileController.php` y agregar rutas protegidas a `routes/web.php` para mostrar y guardar el perfil propio.
- [X] T025 [US1] Crear `resources/views/account/profile.blade.php` con secciones privadas/públicas, aviso de privacidad, errores, éxito, empty states y formularios accesibles.
- [X] T026 [US1] Extender `resources/css/styles.css` con los estilos mínimos de `/account/profile`, reutilizando variables, cards, inputs, badges y breakpoints existentes.
- [ ] T027 [US1] Integrar relaciones y datos del perfil en `resources/views/account/dashboard.blade.php` y `resources/views/account/show.blade.php` sin exponer campos privados.
- [X] T028 [US1] Registrar y probar el flujo de envío de cambios físicos desde `/account/profile` sin publicar valores pendientes.

**Checkpoint**: la modelo puede completar datos privados/públicos y enviar revisiones físicas sin acceso cruzado ni exposición prematura.

---

## Phase 4: User Story 2 - Gestionar edad pública y disponibilidad (Priority: P1)

**Goal**: Calcular la edad real, validar la edad artística y cambiar disponibilidad sin tocar publicación, revisión o identidad.

**Independent Test**: Probar edades válidas/inválidas, ocultar la edad y cambiar disponibilidad; confirmar que los estados existentes permanecen iguales.

### Tests for User Story 2

- [X] T029 [P] [US2] Crear tests de edad real dinámica, fechas futuras, rango de cinco años, `show_age` y no modificación de `birth_date` en `tests/Feature/Profile/PublicAgeTest.php`.
- [X] T030 [P] [US2] Crear tests de `available`/`unavailable` y de invariancia de `is_published`, `review_status`, `identity_status` y `publication_type_id` en `tests/Feature/Profile/AvailabilityStatusTest.php`.

### Implementation for User Story 2

- [X] T031 [US2] Implementar la regla calculada de edad y sus mensajes en `app/Rules/ValidPublicAge.php` y `app/Models/ModelProfilePrivateDetail.php`.
- [X] T032 [US2] Integrar `public_age`, `show_age` y `availability_status` en `app/Http/Requests/AccountProfileUpdateRequest.php` y `app/Services/ModelProfileDetailsService.php`.
- [X] T033 [US2] Renderizar edad real sólo para el contexto autorizado, ocultar edad pública cuando corresponda y mostrar disponibilidad en `resources/views/account/profile.blade.php` y `resources/views/account/dashboard.blade.php`.
- [X] T034 [US2] Agregar labels y estados visuales de disponibilidad en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`.

**Checkpoint**: edad y disponibilidad funcionan de manera independiente y no alteran los estados de identidad, revisión o publicación.

---

## Phase 5: User Story 3 - Gestionar ubicación aproximada (Priority: P1)

**Goal**: Seleccionar provincia/localidad desde catálogos relacionados por IDs y conservar una ubicación pública aproximada.

**Independent Test**: Guardar una combinación válida, rechazar una localidad perteneciente a otra provincia y cambiar de ubicación sin perder relaciones del perfil.

### Tests for User Story 3

- [X] T035 [P] [US3] Crear tests de catálogos, IDs, provincias iniciales, localidad/provincia consistente y provincia fuera de catálogo en `tests/Feature/Profile/ProfileLocationTest.php`.
- [X] T036 [P] [US3] Crear tests de cambio de ubicación que preserven fotos, identidad, bio, servicios, moderación e `is_published` en `tests/Feature/Profile/ProfileLocationRegressionTest.php`.

### Implementation for User Story 3

- [X] T037 [US3] Implementar `Province` y `Locality` con scopes de activos y relación provincia-localidades en `app/Models/Province.php` y `app/Models/Locality.php`.
- [X] T038 [US3] Agregar validación de `province_id`, `locality_id`, relación entre ambos y campos aproximados en `app/Http/Requests/AccountProfileUpdateRequest.php` y `app/Rules/LocalityBelongsToProvince.php`.
- [X] T039 [US3] Cargar catálogos activos con eager loading y renderizar selects dependientes en `app/Http/Controllers/AccountProfileController.php` y `resources/views/account/profile.blade.php`.
- [X] T040 [US3] Actualizar la representación administrativa de provincia, localidad y ubicación aproximada en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`.
- [X] T041 [US3] Mantener compatibilidad visual y de datos heredados en `resources/views/account/dashboard.blade.php`, `resources/views/account/show.blade.php` y `app/Http/Controllers/AccountController.php`.

**Checkpoint**: provincia/localidad se validan por IDs, la ubicación no expone domicilio y los cambios no rompen el resto del perfil.

---

## Phase 6: User Story 4 - Seleccionar tipo y servicios compatibles (Priority: P1)

**Goal**: Administrar tipos de publicación y asociaciones many-to-many de servicios con reglas server-side.

**Independent Test**: Un perfil virtual rechaza servicios presenciales; un perfil encounters acepta ambos tipos; cambiar a virtual conserva virtuales y desactiva presenciales con confirmación.

### Tests for User Story 4

- [X] T042 [P] [US4] Crear tests de catálogo de tipos, servicios activos y relación many-to-many en `tests/Feature/Profile/PublicationTypeAndServiceSchemaTest.php`.
- [X] T043 [P] [US4] Crear tests de compatibilidad server-side, request manipulado y selección de servicios en `tests/Feature/Profile/ServiceCompatibilityTest.php`.
- [X] T044 [P] [US4] Crear tests de cambio encounters/virtual, conservación de servicios virtuales y confirmación/desactivación presencial en `tests/Feature/Profile/PublicationTypeChangeTest.php`.

### Implementation for User Story 4

- [X] T045 [US4] Implementar `app/Services/PublicationTypeChangeService.php` con transacción, locking, validación, sincronización de servicios y confirmación requerida para pasar a virtual.
- [X] T046 [US4] Crear `app/Http/Requests/PublicationTypeChangeRequest.php` y reglas de servicios válidos en `app/Rules/CompatibleServices.php`.
- [X] T047 [US4] Agregar endpoints protegidos y acciones del perfil para tipo/servicios en `app/Http/Controllers/AccountProfileController.php` y `routes/web.php`.
- [X] T048 [US4] Renderizar el selector dinámico de servicios virtuales/presenciales y modal de confirmación en `resources/views/account/profile.blade.php`.
- [X] T049 [US4] Mostrar tipo y servicios actuales en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php` sin permitir asociaciones inválidas.

**Checkpoint**: las relaciones de servicios son consistentes aunque se manipule el request y el tipo no altera fotos, bio, ubicación o identidad.

---

## Phase 7: User Story 5 - Conservar historial de cambios de tipo (Priority: P1)

**Goal**: Registrar cada cambio real de tipo con actor, origen, fecha y motivo para futuras features de pagos.

**Independent Test**: Ejecutar Virtual → Encuentros → Virtual y consultar ambos eventos sin que un no-op cree historial.

### Tests for User Story 5

- [X] T050 [P] [US5] Crear tests de historial, origen, actor, fechas, motivo, transacción y no-op en `tests/Feature/Profile/PublicationTypeHistoryTest.php`.
- [X] T051 [P] [US5] Crear test de consulta administrativa y rechazo de acceso de modelos al historial en `tests/Feature/Admin/PublicationTypeHistoryTest.php`.

### Implementation for User Story 5

- [X] T052 [US5] Completar la persistencia de historial y relaciones en `app/Models/ModelProfilePublicationTypeHistory.php` y `app/Models/ModelProfile.php`.
- [X] T053 [US5] Integrar el registro de `source`, `changed_by_user_id`, `reason` y `changed_at` en `app/Services/PublicationTypeChangeService.php`.
- [X] T054 [US5] Crear `app/Filament/Resources/ModelProfiles/RelationManagers/PublicationTypeHistoryRelationManager.php` con orden cronológico, actor y transición.

**Checkpoint**: cada transición válida es auditable y el historial es sólo administrativo.

---

## Phase 8: User Story 6 - Crear y moderar biografías versionadas (Priority: P1)

**Goal**: Permitir bios pendientes, aprobación/rechazo administrativo y promoción segura de la versión pública.

**Independent Test**: Enviar Bio B mientras Bio A está aprobada; confirmar que A permanece pública hasta aprobar B y que un rechazo conserva A.

### Tests for User Story 6

- [X] T055 [P] [US6] Crear tests de creación, ownership, longitud, estado pendiente y reenvío de bios en `tests/Feature/Profile/ModelProfileBioTest.php`.
- [X] T056 [P] [US6] Crear tests de aprobación/rechazo, motivo, auditoría y conservación de `current_bio_id` en `tests/Feature/Admin/ModelProfileBioModerationTest.php`.

### Implementation for User Story 6

- [X] T057 [US6] Crear `app/Http/Requests/ModelProfileBioRequest.php`, `app/Services/ModelProfileBioModerationService.php` y `app/Policies/ModelProfileBioPolicy.php`.
- [X] T058 [US6] Agregar creación y corrección de bios propias en `app/Http/Controllers/AccountProfileController.php`, `routes/web.php` y `resources/views/account/profile.blade.php`.
- [X] T059 [US6] Crear `app/Filament/Resources/ModelProfiles/RelationManagers/ModelProfileBiosRelationManager.php` con acciones aprobar/rechazar y motivo obligatorio.
- [X] T060 [US6] Integrar `currentBio()` y contenido aprobado en `resources/views/account/dashboard.blade.php`, `resources/views/account/show.blade.php` y futuras salidas públicas controladas.

**Checkpoint**: las bios versionadas y moderadas no pisan una aprobación existente ni permiten auto-aprobación.

---

## Phase 9: User Story 7 - Administrar perfil desde `/account/profile` y Filament (Priority: P2)

**Goal**: Completar la experiencia visual y administrativa separando datos privados, públicos, tipo/servicios, bios y revisiones físicas.

**Independent Test**: Una modelo completa el formulario en desktop/mobile y un administrador verifica todas las secciones, estados, acciones y auditorías desde Filament.

### Tests for User Story 7

- [X] T061 [P] [US7] Crear tests de render, estados vacíos, errores, éxito, ownership y responsive contract del panel en `tests/Feature/Account/ModelProfileAccountPanelTest.php`.
- [X] T062 [P] [US7] Ampliar tests del recurso Filament para secciones privadas/públicas, tipo, servicios, bios y revisiones en `tests/Feature/Admin/ModelProfileResourceTest.php`.
- [X] T063 [P] [US7] Crear tests de revalidación al modificar nombre real/fecha de nacimiento y preservación de documentos en `tests/Feature/Profile/IdentityRevalidationTest.php`.

### Implementation for User Story 7

- [X] T064 [US7] Integrar la revalidación de identidad en `app/Services/IdentityDocumentService.php` o un servicio dedicado, reiniciando el estado sin borrar documentos y requiriendo nuevo envío.
- [X] T065 [US7] Ampliar `app/Filament/Resources/ModelProfiles/ModelProfileResource.php` con secciones privadas, públicas, tipo/servicios, bios y revisiones físicas, manteniendo acciones de identidad separadas.
- [X] T066 [US7] Integrar `ModelProfileBiosRelationManager`, `PublicationTypeHistoryRelationManager`, revisiones físicas y fotos en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`.
- [X] T067 [US7] Ajustar `app/Filament/Resources/ModelProfiles/Pages/EditModelProfile.php` y `ViewModelProfile.php` para limitar campos, conservar estados protegidos y usar servicios para cambios complejos.
- [X] T068 [US7] Revisar `resources/views/account/profile.blade.php` y `resources/css/styles.css` en desktop, tablet y 360 px; corregir overflow, foco, labels, feedback y estados vacíos.
- [X] T069 [US7] Actualizar contratos y textos visibles en español conforme a `specs/006-model-profile-details/contracts/` sin exponer información administrativa.

**Checkpoint**: la experiencia completa funciona en `/account/profile` y Filament sin romper fotos, identidad, publicación ni moderación general.

---

## Phase 10: Polish & Cross-Cutting Concerns

**Purpose**: Cerrar seguridad, regresión, documentación y validación de entrega.

- [X] T070 [P] Auditar serialización, rutas, policies y requests contra IDOR, manipulación de IDs, exposición de datos privados y auto-moderación en `app/Models/`, `app/Policies/`, `app/Http/Requests/` y `routes/web.php`.
- [X] T071 [P] Revisar eager loading y consultas de `ModelProfileResource`, account controller y servicios para evitar N+1 en `app/Filament/Resources/ModelProfiles/ModelProfileResource.php` y `app/Http/Controllers/AccountProfileController.php`.
- [X] T072 [P] Actualizar tests de regresión existentes que dependan de campos heredados en `tests/Feature/Auth/`, `tests/Feature/Account/`, `tests/Feature/Identity/`, `tests/Feature/ModelPhotos/` y `tests/Feature/Admin/`.
- [X] T073 Ejecutar `php artisan test` y corregir regresiones en `tests/` y los archivos de aplicación responsables, sin ocultar fallos mediante cambios de configuración.
- [X] T074 Ejecutar los escenarios de `specs/006-model-profile-details/quickstart.md` y registrar resultados de migración, ownership, moderación y regresión.
- [ ] T075 Realizar validación visual manual de `/account/profile` en 360 px, tablet y desktop usando `resources/css/styles.css`, y documentar la ausencia de `docs/design.md` en la entrega.
- [ ] T076 Revisar que `database/migrations/` sea reversible, `database/seeders/` tenga seeds reproducibles y no exista dependencia accidental de un JSON o proveedor de mapas aún no incorporado.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: puede comenzar inmediatamente.
- **Foundational (Phase 2)**: depende de Setup y bloquea todas las historias.
- **US1 (Phase 3)**: depende de Foundation; entrega el MVP de datos privados/públicos.
- **US2 (Phase 4)**: depende de US1 para compartir el formulario y servicio de perfil.
- **US3 (Phase 5)**: depende de Foundation y se integra con US1; puede desarrollarse en paralelo con US2 si se coordina `AccountProfileUpdateRequest`.
- **US4 (Phase 6)**: depende de Foundation; US5 depende de su servicio de cambio de tipo.
- **US5 (Phase 7)**: depende de US4.
- **US6 (Phase 8)**: depende de Foundation y comparte `/account/profile` con US1; puede desarrollarse en paralelo si se coordinan rutas y vista.
- **US7 (Phase 9)**: integra y verifica US1–US6; debe completarse antes del cierre visual final.
- **Polish (Phase 10)**: depende de todas las historias incluidas en la entrega.

### User Story Dependencies

- **US1 (P1)**: independiente después de Foundation; MVP recomendado.
- **US2 (P1)**: depende del formulario/servicio base de US1.
- **US3 (P1)**: puede iniciar después de Foundation; integración final con US1.
- **US4 (P1)**: independiente después de Foundation.
- **US5 (P1)**: depende de US4.
- **US6 (P1)**: independiente después de Foundation, con integración de vista compartida.
- **US7 (P2)**: depende de la funcionalidad de las historias anteriores para presentar y administrar todos los estados.

### Parallel Opportunities

- T004–T007 requieren coordinación de orden de migraciones, pero T009, T010, T012 y T016 pueden dividirse por archivos después de definir el esquema.
- T018–T020 son tests independientes de US1.
- T029–T030, T035–T036, T042–T044, T050–T051, T055–T056 y T061–T063 son grupos de tests paralelizables.
- US3, US4 y US6 pueden desarrollarse en paralelo después de Foundation si se reserva la integración común de `AccountProfileController`, `AccountProfileUpdateRequest` y `profile.blade.php`.
- T070–T072 son auditorías paralelizables antes de T073.

## Parallel Example: MVP / User Story 1

```text
Worker A: T018 + T019 en tests/Feature/Profile/ModelProfileDetailsTest.php y ModelProfilePrivacyTest.php
Worker B: T020 en tests/Feature/Profile/ModelProfilePhysicalRevisionTest.php
Worker C: T021 + T022 en AccountProfileUpdateRequest.php y ModelProfileDetailsService.php
Worker D: T023 en ModelProfilePhysicalModerationService.php
Integración: T024–T028 después de que los tests y servicios estén listos
```

## Parallel Example: Catálogos y publicación

```text
Worker A: T035 + T037–T041 para provincia/localidad
Worker B: T042–T044 para tipos/servicios
Worker C: T050–T054 para historial después de acordar la API del servicio de cambio
```

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Setup y Foundation.
2. Implementar US1 con datos privados/públicos, revisión física, ownership y `/account/profile` básico.
3. Ejecutar los tests de US1 y la regresión de cuenta/identidad/fotos.
4. Validar visualmente la pantalla en mobile y desktop.
5. Detenerse para revisión antes de agregar tipos, servicios y bios.

### Incremental Delivery

1. Foundation → esquema y límites seguros.
2. US1 → perfil privado/público y MVP.
3. US2 → edad y disponibilidad.
4. US3 → catálogos de ubicación.
5. US4 + US5 → tipos, servicios e historial.
6. US6 → biografías moderadas.
7. US7 → integración administrativa y revalidación.
8. Polish → seguridad, regresión, quickstart y validación visual.

## Notes

- Cada tarea sigue el formato de checklist con ID secuencial, marcador `[P]` sólo cuando corresponde, etiqueta `[US#]` en fases de historia y una ruta concreta.
- `[P]` se usa sólo cuando la tarea puede ejecutarse en paralelo sin compartir archivos incompletos.
- No se agrega un importador JSON en esta feature; las tablas y IDs quedan preparados para una carga futura.
- No se elimina todavía la información heredada de `users` o `model_profiles`; esa limpieza requiere una migración y una spec de compatibilidad posterior.
