# Implementation Plan: Pagos y vencimiento de publicaciones

**Branch**: `008-publication-payments` | **Date**: 2026-10-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/[###-feature-name]/spec.md`

**Note**: This template is filled in by the `$speckit-plan` command; its definition describes the execution workflow.

## Summary

Agregar instrucciones de pago visibles para modelos, registro administrativo de pagos y reglas de elegibilidad basadas en aprobación y vencimiento. Se conservará el flujo manual por WhatsApp y se centralizará la condición de vigencia en el servicio de visibilidad pública.

## Technical Context

<!--
  ACTION REQUIRED: Replace the content in this section with the technical details
  for the project. The structure here is presented in advisory capacity to guide
  the iteration process.
-->

**Language/Version**: PHP 8.2, Laravel 12

**Primary Dependencies**: Eloquent, Filament 5, Blade, MySQL/MariaDB, Docker Compose

**Storage**: MySQL/MariaDB mediante migraciones Laravel

**Testing**: PHPUnit/Laravel feature tests

**Target Platform**: Aplicación web server-rendered en contenedores Docker

**Project Type**: Aplicación web Laravel con panel administrativo Filament

**Performance Goals**: Mantener consultas públicas y administrativas sin N+1; seguimiento operativo inmediato para hasta 1.000 perfiles.

**Constraints**: No pasarela de pagos; autorización administrativa; privacidad de observaciones; migraciones reversibles; compatibilidad con visibilidad pública existente.

**Scale/Scope**: Configuración de pago, historial por perfil, activación, vencimiento y alertas administrativas; sin pagos automáticos.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Gates: PASS. Se usarán migraciones para persistencia, Form Requests/validación en acciones, autorización de Filament, pruebas de reglas críticas, y se preservarán privacidad y moderación pública.

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
├── plan.md              # This file ($speckit-plan command output)
├── research.md          # Phase 0 output ($speckit-plan command)
├── data-model.md        # Phase 1 output ($speckit-plan command)
├── quickstart.md        # Phase 1 output ($speckit-plan command)
├── contracts/           # Phase 1 output ($speckit-plan command)
└── tasks.md             # Phase 2 output ($speckit-tasks command - NOT created by $speckit-plan)
```

### Source Code (repository root)
<!--
  ACTION REQUIRED: Replace the placeholder tree below with the concrete layout
  for this feature. Delete unused options and expand the chosen structure with
  real paths (e.g., apps/admin, packages/something). The delivered plan must
  not include Option labels.
-->

```text
app/Models/PublicationPayment.php
app/Models/PaymentInstruction.php
app/Services/PublicationPaymentService.php
app/Services/PublicModelProfileVisibility.php
app/Filament/Resources/ModelProfiles/
app/Http/Requests/
database/migrations/
resources/views/account/
resources/views/filament/
tests/Feature/Payments/
tests/Feature/PublicModels/
```

**Structure Decision**: Mantener la aplicación Laravel existente: migraciones y modelos en `database/migrations` y `app/Models`, lógica de dominio en `app/Services`, operaciones administrativas en `app/Filament`, vistas Blade en `resources/views`, y pruebas en `tests/Feature`. No se agregará frontend ni paquete externo.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| [e.g., 4th project] | [current need] | [why 3 projects insufficient] |
| [e.g., Repository pattern] | [specific problem] | [why direct DB access insufficient] |
