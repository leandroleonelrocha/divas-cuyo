# Implementation Plan: Registro y autenticación de modelos

**Branch**: `001-model-registration-auth` | **Date**: 2026-09-14 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-model-registration-auth/spec.md`

**Note**: This template is filled in by the `$speckit-plan` command; its definition describes the execution workflow.

## Summary

Implementar el ciclo completo de registro y autenticación de modelos en la aplicación web existente: alta con datos privados y aceptación de políticas, verificación de correo obligatoria, inicio/cierre de sesión, recuperación de contraseña y acceso aislado a la propia información. Se reutilizarán la autenticación de sesión, el proveedor Eloquent y el broker nativo de contraseñas de Laravel 12. Para cumplir la regla de que solo el último enlace de verificación sea válido se añadirá un almacenamiento propio de tokens de verificación rotables; la recuperación de contraseña conservará el broker nativo con expiración de 60 minutos.

## Technical Context

<!--
  ACTION REQUIRED: Replace the content in this section with the technical details
  for the project. The structure here is presented in advisory capacity to guide
  the iteration process.
-->

**Language/Version**: PHP 8.2+, Laravel Framework 12.69.2, Blade, JavaScript/Vite existente

**Primary Dependencies**: Laravel Auth, Eloquent ORM, Notifications/Mail, PHPUnit 11; no nuevas dependencias externas

**Storage**: MySQL como destino; migraciones compatibles con el esquema Laravel actual (`users`, `password_reset_tokens`, `sessions`)

**Testing**: PHPUnit 11 mediante `php artisan test`, `RefreshDatabase`, mail fake y pruebas HTTP de Feature

**Target Platform**: Aplicación web server-rendered Laravel en el entorno del proyecto

**Project Type**: Aplicación web monolítica Laravel con rutas web, sesiones y vistas Blade

**Performance Goals**: Los formularios y respuestas de autenticación deben mantener el comportamiento web estándar del proyecto; las solicitudes de registro, login y recuperación no deben introducir consultas repetitivas por usuario ni cargar colecciones innecesarias.

**Constraints**: Contraseñas nunca se almacenan en claro; respuestas de login y recuperación no deben enumerar cuentas; datos privados solo para la propietaria; verificación de correo obligatoria; enlace de verificación de 24 horas y recuperación nativa de 60 minutos; textos visibles en español; cambios de esquema solo mediante migrations.

**Scale/Scope**: Primer slice de cuentas de modelos sobre el proyecto actual; una cuenta por correo y una sesión web por flujo. Sin publicación pública, identidad, multimedia, moderación, pagos ni panel administrativo.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Gate | Resultado | Evidencia del plan |
|---|---|---|
| Convenciones Laravel y simplicidad | PASS | Se mantienen rutas web, Eloquent, Form Requests, Policies, servicios pequeños solo para la rotación de verificación y vistas Blade; no se agrega paquete. |
| Persistencia explícita y segura | PASS | Nuevos campos/tablas mediante migrations reversibles; acceso de datos mediante modelos Eloquent y relaciones puntuales. |
| Límites validados y autorizados | PASS | Form Requests para entrada, `UserPolicy` para datos privados y middleware `auth`/`verified` en rutas protegidas. |
| Privacidad y publicación moderada | PASS | `is_published` inicia en `false`; no hay ruta pública de perfiles; solo la usuaria autenticada puede ver su cuenta. |
| Entrega probada y mantenible | PASS | Feature tests para flujos HTTP, correo, expiración, rotación de tokens, autorización y no publicación. |
| Stack y compatibilidad | PASS | Laravel 12/PHP 8.2 del `composer.json`; diseño MySQL-compatible. El `.env` local usa SQLite para desarrollo, por lo que los tests deben cubrir ambas compatibilidades cuando el entorno CI lo permita. |

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
app/
├── Http/Controllers/Auth/                # Registro, login, logout, verificación y reset
├── Http/Requests/Auth/                   # Entrada validada de cada formulario
├── Models/User.php                        # Identidad, verificación y perfil privado
├── Notifications/                        # Verificación de correo con enlace rotado
├── Policies/UserPolicy.php                # Acceso únicamente a la cuenta propia
└── Services/EmailVerificationService.php  # Emitir/rotar/validar tokens de verificación

config/policies.php                        # Versiones vigentes de términos y privacidad
database/migrations/                       # Campos de cuenta, aceptaciones y tokens
database/factories/UserFactory.php         # Datos de prueba completos
resources/views/auth/                      # Formularios y estados de autenticación
resources/views/account/show.blade.php     # Información privada propia
routes/web.php                             # Superficie HTTP protegida y pública
tests/Feature/Auth/                        # Flujos web y seguridad de autenticación
tests/Feature/Account/                     # Aislamiento y autorización privada
tests/Unit/Services/                       # Rotación/expiración de tokens
```

**Structure Decision**: Se mantiene el monolito Laravel existente y se agregan carpetas por responsabilidad dentro de `app/`, vistas Blade bajo `resources/views/`, rutas web en `routes/web.php` y pruebas Feature/Unit bajo `tests/`. No se introduce una API separada, SPA, repositorio genérico ni arquitectura adicional.

## Complexity Tracking

No hay violaciones constitucionales que justificar.
