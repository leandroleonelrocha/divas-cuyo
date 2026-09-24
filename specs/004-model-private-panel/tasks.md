---
description: "Tareas de implementación del panel privado de la modelo y post-login"
---

# Tasks: Panel privado de la modelo y post-login

**Input**: Design documents from `/specs/004-model-private-panel/`

**Prerequisites**: `spec.md`, `plan.md`, `research.md`, `data-model.md`, `contracts/account-panel.md`, `quickstart.md`

**Tests**: Se incluyen pruebas porque la especificación exige cobertura de redirección, ownership, estados y regresión de las features 001–003.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirmar la estructura existente y las restricciones de alcance.

- [X] T001 Revisar `app/Http/Controllers/Auth/LoginController.php`, `routes/web.php`, `app/Models/User.php` y `app/Providers/Filament/AdminPanelProvider.php` para documentar el flujo actual de login, `/admin` y cuenta privada.
- [X] T002 [P] Revisar `resources/css/styles.css` y `design.md` para identificar patrones visuales reutilizables en el dashboard Blade, sin agregar frameworks CSS.
- [X] T003 [P] Confirmar en `specs/004-model-private-panel/data-model.md` que no se requieren tablas, columnas, foreign keys, índices ni migrations nuevas.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Establecer las fronteras compartidas de redirección, autenticación, ownership y presentación.

**CRITICAL**: Completar esta fase antes de las historias de usuario.

- [X] T004 Crear `app/Services/AuthenticatedUserRedirector.php` con resolución única: admin verificado → `/admin`; usuario verificado con `ModelProfile` → `/account`; usuario verificado sin perfil → `/account/incomplete-profile`; usuario no verificado conserva el bloqueo previo.
- [X] T005 [P] Definir en `app/Policies/UserPolicy.php` o mecanismo equivalente la autorización de la cuenta autenticada, manteniendo ownership implícito y sin recibir IDs desde el cliente.
- [X] T006 [P] Preparar `app/Http/Controllers/AccountController.php` para cargar únicamente el usuario autenticado y su relación `modelProfile`, sin documentos ni campos sensibles.
- [X] T007 [P] Preparar `resources/views/account/` para el dashboard y la pantalla controlada de perfil incompleto, reutilizando la sección de identidad existente sin duplicar lógica.
- [X] T008 [P] Preparar `tests/Feature/Account/ModelAccountPanelTest.php` para invitado, cuenta sin perfil, sesión expirada y ausencia de datos sensibles.

**Checkpoint**: el redirector, la autorización de ownership y la estructura de vistas están definidos.

---

## Phase 3: User Story 1 - Redirección después del login (Priority: P1) 🎯 MVP

**Goal**: Resolver el destino post-login por tipo de cuenta, dando prioridad a `/admin` y sin enviar modelos a Filament.

**Independent Test**: comprobar los destinos de admin, modelo, cuenta sin perfil y bloqueo de no verificados, incluyendo destinos `intended` alternativos.

### Tests for User Story 1

- [X] T009 [P] [US1] Agregar en `tests/Feature/Auth/LoginTest.php` el caso de modelo verificada con `ModelProfile` → `/account`.
- [X] T010 [P] [US1] Agregar en `tests/Feature/Auth/LoginTest.php` el caso de admin verificado → `/admin`.
- [X] T011 [P] [US1] Agregar en `tests/Feature/Auth/LoginTest.php` el caso de usuario simultáneamente admin y modelo, confirmando que `/admin` tiene prioridad.
- [X] T012 [P] [US1] Agregar en `tests/Feature/Auth/LoginTest.php` el caso de modelo verificada con `intended=/admin`, confirmando que termina siempre en `/account`.
- [X] T013 [P] [US1] Agregar en `tests/Feature/Auth/LoginTest.php` el caso de modelo verificada con `intended` a otra ruta interna, confirmando que también termina siempre en `/account`.
- [X] T014 [P] [US1] Agregar en `tests/Feature/Auth/LoginTest.php` el caso de usuario verificado sin `ModelProfile` → `/account/incomplete-profile`, sin home ni selección de otra cuenta.
- [X] T015 [P] [US1] Agregar en `tests/Feature/Auth/LoginTest.php` el caso de usuario no verificado, confirmando el bloqueo actual y ausencia de acceso al panel.

### Implementation for User Story 1

- [X] T016 [US1] Integrar `app/Services/AuthenticatedUserRedirector.php` en `app/Http/Controllers/Auth/LoginController.php` después de autenticar y regenerar la sesión, conservando el bloqueo previo de email no verificado.
- [X] T017 [US1] Hacer que `app/Services/AuthenticatedUserRedirector.php` ignore destinos `intended` enviados o conservados por el cliente para modelos verificadas y use sólo destinos internos conocidos, manteniendo Filament exclusivo para admins.

**Checkpoint**: admin verificado llega a `/admin`; modelo verificada llega siempre a `/account`; cuenta verificada sin perfil llega a `/account/incomplete-profile`.

---

## Phase 4: User Story 2 - Acceso seguro a `/account` (Priority: P1)

**Goal**: Permitir el dashboard sólo a la persona autenticada, verificada y propietaria del perfil resuelto desde su usuario.

**Independent Test**: la propietaria ve sólo sus datos; invitado, no verificado y otra modelo no reciben contenido privado.

### Tests for User Story 2

- [X] T018 [P] [US2] Agregar en `tests/Feature/Account/ModelAccountPanelTest.php` acceso exitoso de una modelo verificada a `/account`.
- [X] T019 [P] [US2] Agregar en `tests/Feature/Account/ModelAccountPanelTest.php` invitado en `/account` → login y modelo no verificada → bloqueo actual.
- [X] T020 [P] [US2] Agregar en `tests/Feature/Account/ModelAccountPanelTest.php` intentos con `user_id` o `model_profile_id` en URL, query y request, confirmando que no alteran el perfil resuelto.
- [X] T021 [P] [US2] Agregar en `tests/Feature/Account/ModelAccountPanelTest.php` prueba de acceso cruzado entre dos modelos, confirmando rechazo sin datos de la cuenta objetivo.
- [X] T022 [P] [US2] Agregar en `tests/Feature/Account/ModelAccountPanelTest.php` caso de cuenta verificada sin perfil en `/account/incomplete-profile`, con respuesta interna controlada.

### Implementation for User Story 2

- [X] T023 [US2] Agregar en `routes/web.php` `GET /account` y `GET /account/incomplete-profile` con los middlewares de autenticación y verificación requeridos, sin parámetros de usuario o perfil.
- [X] T024 [US2] Implementar en `app/Http/Controllers/AccountController.php` las acciones del panel resolviendo exclusivamente desde `auth()->user()` y `$user->modelProfile`, sin aceptar IDs externos.
- [X] T025 [US2] Aplicar en `app/Http/Controllers/AccountController.php` `UserPolicy`/Gate y una respuesta controlada para la ausencia de perfil, sin cargar ni seleccionar el perfil de otra modelo.
- [X] T026 [US2] Verificar en `app/Models/User.php`, `app/Models/ModelProfile.php` y `app/Policies/UserPolicy.php` que las relaciones y autorización mantengan ownership y no habiliten acceso cruzado.

**Checkpoint**: `/account` no recibe IDs y sólo muestra la cuenta autenticada; Filament continúa siendo la superficie administrativa.

---

## Phase 5: User Story 3 - Dashboard privado y estados (Priority: P1)

**Goal**: Mostrar datos básicos, los cuatro estados independientes y el acceso a identidad en una vista Blade consistente.

**Independent Test**: una modelo ve sus datos y estados separados, sin documentos ni información sensible.

### Tests for User Story 3

- [X] T027 [P] [US3] Agregar en `tests/Feature/Account/ModelAccountPanelTest.php` assertions para nombre, email, WhatsApp y ubicación del usuario autenticado.
- [X] T028 [P] [US3] Agregar en `tests/Feature/Account/ModelAccountPanelTest.php` assertions separadas para `email_verified_at`, `identity_status`, `review_status` e `is_published` en combinaciones representativas.
- [X] T029 [P] [US3] Agregar en `tests/Feature/Account/ModelAccountPanelTest.php` assertion del enlace a `identity.show` y del motivo de rechazo visible sólo para la propietaria.
- [X] T030 [P] [US3] Agregar en `tests/Feature/Account/ModelAccountPanelTest.php` assertions de ausencia de `storage_path`, nombres físicos, contraseñas, tokens y contenido documental.

### Implementation for User Story 3

- [X] T031 [US3] Ajustar `app/Http/Controllers/AccountController.php` para enviar a la vista sólo columnas y relaciones necesarias del usuario y `ModelProfile`, sin eager loading de documentos privados.
- [X] T032 [US3] Crear o ajustar `resources/views/account/dashboard.blade.php` (reutilizando `resources/views/account/show.blade.php` si corresponde) con header privado, resumen, acciones y layout responsive basado en `design.md` y `resources/css/styles.css`.
- [X] T033 [US3] Implementar en `resources/views/account/dashboard.blade.php` badges con labels claros y separados para email verificado, identidad, revisión del perfil y publicación.
- [X] T034 [US3] Agregar en `resources/views/account/dashboard.blade.php` el enlace a `identity.show`, retorno a cuenta y mensajes controlados para identidad incompleta o rechazada, sin duplicar la lógica documental.
- [X] T035 [US3] Reutilizar en `resources/css/styles.css` variables y patrones existentes para cards, navegación, botones, estados, tipografía y responsive, sin reglas globales innecesarias ni dependencias nuevas.
- [X] T036 [US3] Mantener `resources/views/account/show.blade.php` y las rutas de identidad compatibles durante la transición, evitando duplicar contenido sensible del dashboard.

**Checkpoint**: el dashboard Blade distingue claramente email, identidad, revisión y publicación.

---

## Phase 6: User Story 4 - Accesos futuros sin funcionalidades (Priority: P2)

**Goal**: Preparar visualmente futuras áreas sin implementar fotos, videos ni módulos nuevos.

**Independent Test**: las áreas futuras, si se muestran, son no operativas y no agregan rutas, persistencia ni datos.

### Tests for User Story 4

- [ ] T037 [P] [US4] Agregar en `tests/Feature/Account/ModelAccountPanelTest.php` assertions de labels “Próximamente” o equivalente para áreas futuras, sólo si se muestran.
- [ ] T038 [P] [US4] Agregar en `tests/Feature/Account/ModelAccountPanelTest.php` una prueba de que los accesos futuros no ejecutan operaciones ni requieren rutas nuevas.

### Implementation for User Story 4

- [ ] T039 [US4] Incorporar en `resources/views/account/dashboard.blade.php` placeholders no interactivos para fotos, videos y otros datos sólo si la UX los requiere, marcados como no disponibles.
- [ ] T040 [US4] Verificar en `routes/web.php`, `app/Http/Controllers/AccountController.php` y `resources/views/account/dashboard.blade.php` que no se creen endpoints, modelos, migrations, tablas ni persistencia para funcionalidades futuras.

**Checkpoint**: el panel es extensible visualmente sin ampliar el alcance funcional.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Validar consistencia, regresión, calidad técnica y documentación.

- [X] T041 [P] Revisar `app/Http/Controllers/Auth/LoginController.php`, `app/Services/AuthenticatedUserRedirector.php` y `app/Providers/Filament/AdminPanelProvider.php` para eliminar duplicación entre login público y Filament.
- [X] T042 [P] Revisar `app/Http/Controllers/AccountController.php`, `app/Models/User.php` y `app/Models/ModelProfile.php` para eliminar imports innecesarios, evitar N+1 y confirmar que no se carguen documentos en el resumen.
- [X] T043 [P] Revisar `resources/views/account/dashboard.blade.php`, `resources/views/account/incomplete-profile.blade.php` y `resources/css/styles.css` contra `design.md` para estados vacíos, mensajes, focus, responsive y ausencia de overflow.
- [X] T044 [P] Actualizar `specs/004-model-private-panel/quickstart.md` con admin prioritario, landing canónica de modelos, `intended`, cuenta sin perfil, ownership y comandos de validación.
- [X] T045 [P] Actualizar `specs/004-model-private-panel/contracts/account-panel.md` con `/account/incomplete-profile`, la prioridad `/admin` y el rechazo de destinos alternativos.
- [X] T046 [P] Revisar `specs/004-model-private-panel/plan.md`, `research.md` y `data-model.md` para mantener decisiones sincronizadas y confirmar que no hay cambios de schema.
- [X] T047 Ejecutar `php artisan test` sobre `tests/` y confirmar regresión completa de features 001, 002 y 003, además de login, logout, verificación y recuperación.
- [X] T048 Ejecutar `vendor/bin/pint --test` y corregir únicamente formato en los archivos modificados por esta feature.
- [X] T049 Ejecutar `php artisan route:list`, `php artisan view:cache` y `php artisan optimize:clear`; verificar `/account`, `/account/incomplete-profile`, `/admin`, identidad y rutas públicas existentes.
- [X] T050 Revisar `git diff --stat`, `git diff --name-only` y `git status` para confirmar que no se modificaron migrations, Filament fuera de autorización, funcionalidades futuras ni el frontend público fuera de lo necesario.

## Dependencies & Execution Order

### Phase Dependencies

- Setup no depende de otras fases.
- Foundation depende de Setup y bloquea todas las historias.
- US1 depende de Foundation y define el redirector post-login.
- US2 depende de Foundation y del destino `/account` definido por US1.
- US3 depende de US2 para reutilizar acceso seguro y perfil autenticado.
- US4 depende de US3 y es opcional para el MVP.
- Polish depende de las historias que se hayan implementado.

### User Story Dependencies

- **US1 (P1)**: después de Foundation; entrega el MVP de redirección.
- **US2 (P1)**: después de US1; establece la superficie privada segura.
- **US3 (P1)**: después de US2; entrega el dashboard funcional.
- **US4 (P2)**: después de US3; sólo prepara accesos futuros no operativos.

### Parallel Opportunities

- T002–T003 pueden ejecutarse en paralelo con T001.
- T005–T008 pueden ejecutarse en paralelo dentro de Foundation; T004 debe estar definido antes de integrar el login.
- T009–T015 son pruebas separables; T016–T017 requieren coordinación con el flujo actual de autenticación.
- T018–T022 son pruebas separables; T023 y T026 pueden prepararse en paralelo, mientras T024–T025 coordinan la ruta y el controlador.
- T027–T030 son pruebas separables; T032–T035 pueden dividirse por vista y estilos evitando conflictos de clases.
- T037–T038 son pruebas separables; T039–T040 deben conservar el alcance no funcional.
- T041–T046 son revisiones separables por área; T047–T049 se ejecutan al final.

## Implementation Strategy

### MVP recomendado

1. Completar Setup y Foundation.
2. Completar US1: redirector centralizado y destinos `/admin`, `/account` y `/account/incomplete-profile`.
3. Completar US2: acceso seguro a `/account` sin IDs externos.
4. Completar US3: dashboard Blade con datos y estados separados.
5. Validar regresión antes de considerar la feature utilizable.

US4 puede postergarse: no agrega capacidad de negocio y sólo prepara áreas futuras.

### Incremental Delivery

1. Foundation lista → validar autenticación y ownership.
2. US1 → validar redirecciones por rol/estado.
3. US2 → validar privacidad y resolución desde `auth()->user()`.
4. US3 → validar resumen, estados y enlace de identidad.
5. US4 → validar placeholders sin funcionalidades.
6. Polish → ejecutar regresión y documentación.

### Format Validation

Las 50 tareas usan checkbox Markdown, ID secuencial `T###`, marcador `[P]` sólo cuando corresponde, etiqueta `[US#]` únicamente en fases de historias y una ruta de archivo explícita en cada descripción.
