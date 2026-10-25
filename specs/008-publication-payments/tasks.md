# Tasks: Pagos y vencimiento de publicaciones

**Input**: Design documents from `specs/008-publication-payments/`

## Phase 1: Setup

- [x] T001 Confirm payment period policy and automatic/manual activation decision in `specs/008-publication-payments/research.md`
- [ ] T002 [P] Review existing publication visibility and moderation tests in `app/Services/PublicModelProfileVisibility.php` and `tests/Feature/PublicModels/`
- [x] T003 [P] Define payment configuration keys and expiry threshold in `config/publication.php`

## Phase 2: Foundational

- [x] T004 Create reversible migrations for payment instructions and publication payment history in `database/migrations/`
- [x] T005 [P] Create `PaymentInstruction` model in `app/Models/PaymentInstruction.php`
- [x] T006 [P] Create `PublicationPayment` model in `app/Models/PublicationPayment.php`
- [x] T007 Add payment relationships and current-payment queries to `app/Models/ModelProfile.php`
- [ ] T008 Add factories for payment entities in `database/factories/`
- [ ] T009 Create Form Requests for payment configuration and registration in `app/Http/Requests/`
- [x] T010 Create shared eligibility and expiry logic in `app/Services/PublicationPaymentService.php`
- [ ] T011 Add authorization policies or Filament authorization checks in `app/Policies/` and `app/Providers/AppServiceProvider.php`
- [ ] T012 Add foundational model and validation tests in `tests/Feature/Payments/`

## Phase 3: User Story 1 - Consultar instrucciones de pago (P1)

**Goal**: Las modelos consultan instrucciones activas sin ver datos internos.

**Independent Test**: Configurar alias, importe y WhatsApp; comprobar visualización y manejo de datos faltantes.

- [ ] T013 [P] [US1] Add payment instruction configuration fields to the admin interface in `app/Filament/Resources/`
- [x] T014 [P] [US1] Add the model-facing payment instructions data loading in `app/Http/Controllers/AccountController.php`
- [x] T015 [US1] Add the payment instructions section to `resources/views/account/dashboard.blade.php`
- [ ] T016 [US1] Add feature tests for configured, missing and unauthorized instructions in `tests/Feature/Payments/PaymentInstructionsTest.php`

## Phase 4: User Story 2 - Registrar manualmente un pago (P1)

**Goal**: Un administrador registra pagos completos, válidos y trazables.

**Independent Test**: Registrar un pago válido y comprobar que datos inválidos no dejan registros parciales.

- [x] T017 [P] [US2] Add payment history relation manager or admin section in `app/Filament/Resources/ModelProfiles/`
- [x] T018 [US2] Implement transactional payment registration with admin attribution in `app/Filament/Resources/ModelProfiles/`
- [x] T019 [US2] Add payment history and internal observation display restricted to administrators in `app/Filament/Resources/ModelProfiles/`
- [ ] T020 [US2] Add registration, validation, renewal and authorization tests in `tests/Feature/Payments/PublicationPaymentRegistrationTest.php`

## Phase 5: User Story 3 - Activar publicaciones pagadas (P1)

**Goal**: Sólo se activa una publicación aprobada con pago vigente.

**Independent Test**: Intentar activar perfiles sin pago, vencidos, no aprobados y aprobados con pago vigente.

- [x] T021 [P] [US3] Add payment eligibility and expiry status to the Filament table/detail in `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`
- [x] T022 [US3] Update activation/deactivation actions to call `PublicationPaymentService` in `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`
- [x] T023 [US3] Apply current-payment eligibility to public visibility in `app/Services/PublicModelProfileVisibility.php`
- [ ] T024 [US3] Add activation and public exposure tests in `tests/Feature/Payments/PublicationActivationTest.php`

## Phase 6: User Story 4 - Controlar vencimientos (P1)

**Goal**: Los perfiles vencidos se ocultan y los próximos a vencer se identifican.

**Independent Test**: Preparar vencimientos pasado, futuro y dentro del umbral; comprobar visibilidad y seguimiento administrativo.

- [x] T025 [P] [US4] Add expiry and near-expiry filters/badges to `app/Filament/Resources/ModelProfiles/ModelProfileResource.php`
- [ ] T026 [P] [US4] Add expiry synchronization command or schedule in `app/Console/Commands/` and `routes/console.php`
- [ ] T027 [US4] Ensure every public entry point uses centralized expiry logic in `app/Services/PublicModelProfileVisibility.php`
- [ ] T028 [US4] Add expiry tests across home, search and profile routes in `tests/Feature/Payments/PublicationExpiryTest.php`

## Phase 7: Polish and cross-cutting validation

- [ ] T029 [P] Add regression coverage for moderation and public profiles in `tests/Feature/PublicModels/`
- [ ] T030 [P] Review privacy boundaries for payment data and notes in `tests/Feature/Payments/`
- [ ] T031 [P] Review responsive instructions UI at desktop/mobile breakpoints in `resources/views/account/` and `public/css/`
- [ ] T032 Run `specs/008-publication-payments/quickstart.md` scenarios in Docker and document results
- [ ] T033 Run the relevant PHPUnit suite and resolve migration, authorization, N+1 and regression failures

## Dependencies and execution order

- Setup T001-T003 precedes all work.
- Foundational T004-T012 blocks every user story.
- US1 depends on T004-T009; US2 depends on T004-T012; US3 depends on T010-T012 and US2; US4 depends on T010 and US3.
- T013, T014, T017, T021, T025 and T026 can run in parallel after their prerequisites.
- MVP: T004-T012, T017-T020 and T021-T024.

## Implementation strategy

1. Complete schema, models, validation, authorization and eligibility service.
2. Deliver payment registration and safe activation.
3. Add model-facing instructions and expiry tracking.
4. Validate public visibility, privacy and responsive UI.
