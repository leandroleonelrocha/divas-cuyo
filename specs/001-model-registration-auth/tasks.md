---

description: "Task list for model registration and authentication"
---

# Tasks: Registro y autenticación de modelos

**Input**: Design documents from `/specs/001-model-registration-auth/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/web-routes.md, quickstart.md

**Tests**: Incluidos porque la especificación y la constitución exigen pruebas automatizadas para las reglas críticas de autenticación, privacidad y publicación.

**Organization**: Las tareas están agrupadas por historia de usuario y ordenadas por dependencia.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo porque trabaja en archivos distintos y no depende de tareas incompletas.
- **[Story]**: Identifica la historia de usuario correspondiente.
- Todas las tareas incluyen rutas concretas del repositorio.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Preparar la estructura mínima para implementar la funcionalidad sin agregar dependencias externas.

- [X] T001 Revisar las convenciones actuales de Laravel, rutas, vistas, modelo `User` y configuración de mail en `app/Models/User.php`, `routes/web.php`, `resources/views/` y `config/mail.php`.
- [X] T002 [P] Crear las carpetas de aplicación y pruebas definidas en el plan en `app/Http/Controllers/Auth/`, `app/Http/Requests/Auth/`, `app/Notifications/`, `app/Policies/`, `app/Services/`, `resources/views/auth/`, `resources/views/account/`, `tests/Feature/Auth/`, `tests/Feature/Account/` y `tests/Unit/Services/`.
- [X] T003 [P] Definir las versiones vigentes de términos y privacidad en `config/policies.php` sin introducir una dependencia externa.
- [X] T004 [P] Confirmar los valores de expiración, throttle y broker nativos en `config/auth.php` y documentar cualquier ajuste requerido para recuperación de 60 minutos.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Implementar persistencia, modelos y base de pruebas compartidos por todas las historias.

**⚠️ CRITICAL**: No comenzar las historias de usuario hasta completar esta fase.

- [X] T005 Crear una migration reversible para agregar `whatsapp`, `location`, `is_published` con default `false` y restricciones necesarias a `database/migrations/`.
- [X] T006 Crear una migration reversible para `policy_acceptances` con FK a `users`, políticas `terms`/`privacy`, versión, `accepted_at` e índice único por usuario y política en `database/migrations/`.
- [X] T007 Crear una migration reversible para `email_verification_tokens` con un token vigente por usuario, `token_hash`, `expires_at`, FK y eliminación en cascada en `database/migrations/`.
- [X] T008 [P] Crear el modelo Eloquent `PolicyAcceptance` con relación `belongsTo(User::class)` en `app/Models/PolicyAcceptance.php`.
- [X] T009 [P] Crear el modelo Eloquent `EmailVerificationToken` con relación `belongsTo(User::class)` y ocultamiento de `token_hash` en `app/Models/EmailVerificationToken.php`.
- [X] T010 Actualizar `User` para implementar `MustVerifyEmail` y `CanResetPassword`, agregar casts, campos fillable, relaciones y protección de atributos en `app/Models/User.php`.
- [X] T011 [P] Completar la factory de usuarios con datos de modelo, estados verificado/no verificado y `is_published = false` en `database/factories/UserFactory.php`.
- [X] T012 [P] Preparar `Tests\TestCase` para usar `RefreshDatabase` y mail/notification fakes sin exponer secretos en `tests/TestCase.php`.
- [X] T013 Ejecutar las migrations contra la base de pruebas y verificar compatibilidad del esquema con MySQL en `database/migrations/`.

**Checkpoint**: La persistencia y los modelos compartidos están disponibles; las historias pueden implementarse en orden o por equipos.

---

## Phase 3: User Story 1 - Crear y verificar una cuenta (Priority: P1) 🎯 MVP

**Goal**: Crear una cuenta no publicada, registrar las dos aceptaciones obligatorias y habilitarla únicamente después de verificar el correo.

**Independent Test**: Un formulario válido crea una cuenta no verificada/no publicada y envía verificación; una aceptación faltante no crea cuenta; el último enlace válido dentro de 24 horas verifica y uno anterior o vencido falla.

### Tests for User Story 1

- [X] T014 [P] [US1] Escribir pruebas Feature de registro válido, campos obligatorios, email duplicado, contraseña mínima, aceptación de políticas, estado no publicado y envío de verificación en `tests/Feature/Auth/RegistrationTest.php`.
- [X] T015 [P] [US1] Escribir pruebas Feature de verificación válida, token vencido, reenvío que invalida el token anterior y enlace más reciente válido en `tests/Feature/Auth/EmailVerificationTest.php`.

### Implementation for User Story 1

- [X] T016 [US1] Crear `RegisterRequest` con reglas para email único, contraseña de al menos 8 caracteres, datos requeridos y aceptación explícita de términos/privacidad en `app/Http/Requests/Auth/RegisterRequest.php`.
- [X] T017 [P] [US1] Crear la notificación de verificación en español con enlace opaco y texto de expiración de 24 horas en `app/Notifications/VerifyModelEmail.php`.
- [X] T018 [US1] Implementar emisión, rotación, hash, expiración y consumo atómico del token de verificación en `app/Services/EmailVerificationService.php`.
- [X] T019 [US1] Implementar registro transaccional de usuario, aceptaciones y token inicial, con `is_published = false`, en `app/Http/Controllers/Auth/RegistrationController.php`.
- [X] T020 [US1] Implementar endpoint de verificación y endpoint de reenvío con respuestas genéricas para no enumerar cuentas en `app/Http/Controllers/Auth/EmailVerificationController.php`.
- [X] T021 [US1] Crear formularios y estados de registro, pendiente de verificación, verificación exitosa y error en `resources/views/auth/register.blade.php`, `resources/views/auth/verify-email.blade.php` y `resources/views/auth/verification-status.blade.php`.
- [X] T022 [US1] Registrar las rutas de registro, verificación y reenvío con nombres consistentes, CSRF y middleware de invitada en `routes/web.php`.
- [X] T023 [US1] Ejecutar y corregir las pruebas de registro/verificación hasta cubrir los escenarios de `tests/Feature/Auth/RegistrationTest.php` y `tests/Feature/Auth/EmailVerificationTest.php`.

**Checkpoint**: Una modelo puede registrarse y verificar su correo; la cuenta sigue sin publicarse y los tokens antiguos no funcionan.

---

## Phase 4: User Story 2 - Iniciar y cerrar sesión (Priority: P1)

**Goal**: Permitir login solo a modelos verificadas, rechazar credenciales inválidas sin enumeración y cerrar la sesión correctamente.

**Independent Test**: Una cuenta verificada inicia sesión y accede al área protegida; una no verificada o con contraseña incorrecta no crea sesión; logout invalida el acceso.

### Tests for User Story 2

- [X] T024 [P] [US2] Escribir pruebas Feature de login exitoso, credenciales inválidas, cuenta no verificada, regeneración de sesión y mensajes no enumerables en `tests/Feature/Auth/LoginTest.php`.
- [X] T025 [P] [US2] Escribir pruebas Feature de logout y bloqueo posterior de rutas autenticadas en `tests/Feature/Auth/LogoutTest.php`.

### Implementation for User Story 2

- [X] T026 [US2] Crear `LoginRequest` para validar email, contraseña y opción `remember` en `app/Http/Requests/Auth/LoginRequest.php`.
- [X] T027 [US2] Implementar login con `Auth::attempt`, comprobación explícita de email verificado, regeneración de sesión y respuestas en español en `app/Http/Controllers/Auth/LoginController.php`.
- [X] T028 [US2] Implementar logout con invalidación de sesión y regeneración de token CSRF en `app/Http/Controllers/Auth/LogoutController.php`.
- [X] T029 [US2] Crear las vistas de login, error de credenciales y estado de verificación pendiente en `resources/views/auth/login.blade.php` y `resources/views/auth/login-status.blade.php`.
- [X] T030 [US2] Agregar las rutas GET/POST de login y POST de logout con middleware de invitada/autenticada en `routes/web.php`.
- [X] T031 [US2] Ejecutar y corregir las pruebas de `tests/Feature/Auth/LoginTest.php` y `tests/Feature/Auth/LogoutTest.php` sin romper el registro de `tests/Feature/Auth/RegistrationTest.php`.

**Checkpoint**: Las cuentas verificadas pueden entrar y salir; ninguna sesión se crea para cuentas no verificadas o credenciales inválidas.

---

## Phase 5: User Story 4 - Aislar la información privada (Priority: P1)

**Goal**: Permitir que una modelo autenticada vea únicamente los datos de su propia cuenta.

**Independent Test**: La cuenta A puede consultar A, no puede consultar B y una visitante no autenticada no puede consultar ninguna cuenta.

### Tests for User Story 4

- [X] T032 [P] [US4] Escribir pruebas unitarias de `UserPolicy` para propietaria, otra modelo y visitante no autenticada en `tests/Unit/Policies/UserPolicyTest.php`.
- [X] T033 [P] [US4] Escribir pruebas Feature del endpoint privado, respuestas 403/redirect, ausencia de secretos y no existencia de publicación pública en `tests/Feature/Account/AccountAccessTest.php`.

### Implementation for User Story 4

- [X] T034 [US4] Implementar `UserPolicy::view` y registrar la policy para permitir únicamente que la usuaria autenticada vea su propio `User` en `app/Policies/UserPolicy.php` y `app/Providers/AppServiceProvider.php`.
- [X] T035 [US4] Implementar el controlador de consulta privada usando route model binding y autorización de policy en `app/Http/Controllers/AccountController.php`.
- [X] T036 [US4] Crear la vista de cuenta privada mostrando solo email, nombre, WhatsApp, ubicación, estado de verificación y estado no publicado en `resources/views/account/show.blade.php`.
- [X] T037 [US4] Registrar la ruta protegida `GET /cuenta/{user}` con middleware `auth` y autorización de policy en `routes/web.php`.
- [X] T038 [US4] Ejecutar y corregir las pruebas de `tests/Unit/Policies/UserPolicyTest.php` y `tests/Feature/Account/AccountAccessTest.php`, verificando que no se expongan contraseñas, tokens ni datos de otra cuenta.

**Checkpoint**: El acceso privado está aislado por policy y no existe ruta pública para listar o consultar perfiles creados.

---

## Phase 6: User Story 3 - Recuperar contraseña por email (Priority: P2)

**Goal**: Permitir solicitar y completar la recuperación mediante el broker nativo, con enlaces de 60 minutos y respuesta genérica.

**Independent Test**: Una solicitud para un correo existente y otra inexistente tienen respuesta equivalente; un enlace vigente cambia la contraseña una vez; uno vencido o reutilizado falla.

### Tests for User Story 3

- [X] T039 [P] [US3] Escribir pruebas Feature de solicitud genérica, throttle, mail enviado solo al correo existente, reset exitoso, contraseña anterior inválida y token vencido/reutilizado en `tests/Feature/Auth/PasswordResetTest.php`.

### Implementation for User Story 3

- [X] T040 [US3] Crear `ForgotPasswordRequest` y `ResetPasswordRequest` con validación de email, contraseña mínima y confirmación en `app/Http/Requests/Auth/ForgotPasswordRequest.php` y `app/Http/Requests/Auth/ResetPasswordRequest.php`.
- [X] T041 [US3] Implementar solicitud y aplicación de reset usando `Password::sendResetLink` y `Password::reset`, manteniendo respuesta genérica y expiración nativa de 60 minutos en `app/Http/Controllers/Auth/PasswordResetController.php`.
- [X] T042 [US3] Crear vistas Blade de solicitud, enlace recibido, formulario de nueva contraseña y errores de token en `resources/views/auth/forgot-password.blade.php`, `resources/views/auth/reset-password.blade.php` y `resources/views/auth/password-status.blade.php`.
- [X] T043 [US3] Registrar las rutas GET/POST de solicitud y reset con middleware de invitada y nombres compatibles con la notificación nativa en `routes/web.php`.
- [X] T044 [US3] Ejecutar y corregir `tests/Feature/Auth/PasswordResetTest.php` y verificar que el flujo de reset no permite enumerar cuentas ni reutilizar tokens.

**Checkpoint**: La modelo puede recuperar su contraseña por email sin exposición de existencia de cuentas y con token válido durante una hora.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Validar integración completa, seguridad, formato y alcance antes de entregar.

- [X] T045 [P] Ejecutar Laravel Pint sobre los archivos PHP modificados en `app/`, `database/`, `routes/` y `tests/` y corregir incumplimientos de PSR-12.
- [X] T046 [P] Revisar las vistas y mensajes visibles en español, accesibilidad básica de labels/errores y consistencia visual con `resources/css/styles.css` y `resources/views/auth/`.
- [X] T047 Ejecutar toda la suite con `php artisan test` y resolver regresiones en `tests/Feature/` y `tests/Unit/`.
- [X] T048 Revisar el flujo completo contra `specs/001-model-registration-auth/contracts/web-routes.md`, `specs/001-model-registration-auth/data-model.md` y `specs/001-model-registration-auth/quickstart.md`.
- [X] T049 Ejecutar la validación manual descrita en `specs/001-model-registration-auth/quickstart.md` usando mailer de prueba y verificar que ningún perfil quede publicado.
- [X] T050 Realizar revisión final de privacidad, logs, secretos, CSRF, autorización, migrations reversibles y alcance en `app/`, `routes/`, `database/` y `resources/views/`.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 Setup**: Sin dependencias; prepara estructura y configuración.
- **Phase 2 Foundational**: Depende de Setup y bloquea todas las historias.
- **Phase 3 US1**: Depende de Foundation; entrega el MVP de registro/verificación.
- **Phase 4 US2**: Depende de US1 para el flujo real de una cuenta verificada, aunque sus pruebas pueden prepararse en paralelo después de Foundation.
- **Phase 5 US4**: Depende de US2 para probar acceso autenticado real; sus pruebas de policy pueden prepararse en paralelo.
- **Phase 6 US3**: Depende de Foundation y del modelo `User`; puede implementarse en paralelo con US2/US4 si se coordina el trabajo sobre `routes/web.php`.
- **Phase 7 Polish**: Depende de todas las historias seleccionadas.

### User Story Dependencies

- **US1 (P1)**: Primera entrega funcional; no depende de otra historia.
- **US2 (P1)**: Usa el estado de verificación creado por US1.
- **US4 (P1)**: Usa la sesión de US2 y el modelo extendido por US1.
- **US3 (P2)**: Puede desarrollarse después de Foundation; la validación final de login usa US2.

### Parallel Opportunities

- T002, T003 y T004 pueden ejecutarse en paralelo.
- T008, T009, T011 y T012 pueden ejecutarse en paralelo después de definir las migrations.
- T014 y T015 pueden escribirse en paralelo.
- T017 puede desarrollarse en paralelo con T016; T021 puede desarrollarse en paralelo con T018/T019.
- T024 y T025 pueden escribirse en paralelo; T026 y T029 también.
- T032 y T033 pueden escribirse en paralelo; T034 y T036 pueden desarrollarse en paralelo si la policy queda definida.
- T039 puede escribirse en paralelo con pruebas de US2 y US4.
- T040 y T042 pueden desarrollarse en paralelo; T045 y T046 son independientes.
- Con varios colaboradores, después de Foundation: US1, US2 y US3 pueden avanzar por ramas separadas; US4 requiere integrar el contrato de sesión.

## Parallel Example: User Story 1

```text
Developer A: T014 tests/Feature/Auth/RegistrationTest.php
Developer B: T015 tests/Feature/Auth/EmailVerificationTest.php
Developer C: T017 app/Notifications/VerifyModelEmail.php

Después de las pruebas y modelos compartidos:
Developer A: T018 app/Services/EmailVerificationService.php
Developer B: T019 app/Http/Controllers/Auth/RegistrationController.php
Developer C: T021 resources/views/auth/
```

## Implementation Strategy

### MVP First (US1)

1. Completar Phase 1 y Phase 2.
2. Completar US1: registro, aceptaciones, no publicación y verificación de email.
3. Ejecutar T023 y validar el checkpoint de US1.
4. Detenerse para revisar que solo el último enlace funciona y que ningún perfil se publica.

### Incremental Delivery

1. Agregar US2 para habilitar login/logout de cuentas verificadas.
2. Agregar US4 para entregar el área privada y el aislamiento entre modelos.
3. Agregar US3 para recuperación de contraseña.
4. Ejecutar Phase 7 y validar el quickstart completo.

## Notes

- Las tareas `[P]` solo son paralelizables cuando no modifican simultáneamente `routes/web.php` o el mismo archivo compartido.
- Las migrations deben ser reversibles y compatibles con MySQL; no realizar cambios manuales de esquema.
- Los nombres técnicos permanecen en inglés y los textos visibles para la usuaria en español.
- No implementar publicación pública, validación de identidad, fotos, videos, moderación, pagos ni panel administrativo.
- Cada historia tiene pruebas independientes y un checkpoint de validación.
