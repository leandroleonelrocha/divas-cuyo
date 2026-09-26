# Implementation Plan: Detalles ampliados del perfil de modelo

**Branch**: `006-model-profile-details` | **Date**: 2026-09-25 | **Spec**: [spec.md](./spec.md)

## Summary

La feature agregará un perfil de modelo separado en cuatro áreas: cuenta, datos privados, datos públicos y modalidad/servicios. El plan reutiliza `ModelProfile`, autenticación, ownership, identidad, moderación y fotos existentes; incorpora migraciones reversibles, modelos Eloquent, servicios transaccionales, validación server-side, una pantalla `/account/profile` y secciones administrativas en `ModelProfileResource`.

La compatibilidad con el código actual se resolverá mediante una migración gradual: los campos heredados de `users` y `model_profiles` no se eliminarán en esta feature mientras registro, dashboard y tests existentes los utilicen; las nuevas escrituras canónicas irán a perfil privado y perfil público, y las lecturas se migrarán de forma controlada. Las provincias y localidades se almacenarán como catálogos relacionados por IDs, preparados para una futura carga desde JSON.

## Technical Context

**Language/Version**: PHP 8.2, Laravel 12, identificadores técnicos en inglés y textos visibles en español.

**Primary Dependencies**: Eloquent ORM, Form Requests, Policies/Gates, Filament 5, Blade, Vite, Tailwind ya instalado y `resources/css/styles.css`; no se agregan paquetes nuevos.

**Storage**: MySQL para entidades y relaciones; almacenamiento existente para identidad y fotos permanece sin cambios. No se incorpora proveedor de mapas.

**Testing**: PHPUnit 11.5 mediante `php artisan test`, tests de feature para HTTP/Livewire/ownership/moderación y tests unitarios para reglas de edad, servicios y transiciones.

**Target Platform**: Aplicación web Laravel server-rendered, panel privado autenticado `/account` y panel administrativo Filament.

**Project Type**: Monolito web Laravel con vistas Blade y administración Filament.

**Performance Goals**: Formularios y vistas de perfil deben responder con la latencia normal del panel existente; las relaciones de perfil, servicios, catálogos, bios e historial deben cargarse con eager loading y sin N+1 observable.

**Constraints**: Mantener compatibilidad con features 001–005, identidad, fotos, moderación, `is_published`, `review_status` e `identity_status`; aplicar ownership server-side; no exponer datos privados; no usar `docs/design.md` porque no existe actualmente, dejando constancia y reutilizando CSS/vistas aprobadas hasta que se incorpore.

**Scale/Scope**: Un perfil por usuario; catálogos pequeños de provincias/localidades/servicios; varias bios e historiales por perfil; no se requiere búsqueda geográfica ni dashboard público.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Laravel Standards and Simplicity**: PASS. Se reutilizan Laravel, Eloquent, Form Requests, Services, Policies, Blade y Filament; no se agrega una arquitectura o paquete nuevo.
- **II. Explicit and Safe Persistence**: PASS. Todas las entidades se crearán con migraciones reversibles y relaciones Eloquent; se planifican índices, unicidad y eager loading.
- **III. Validated and Authorized Boundaries**: PASS. Los formularios tendrán Form Requests o validación de Filament; Policies/Gates y servicios protegerán ownership, identidad y moderación.
- **IV. Privacy and Moderated Publication**: PASS. Datos privados, identidad, bios pendientes y revisiones físicas tendrán límites explícitos; la vista pública no serializará el perfil completo.
- **V. Tested and Maintainable Delivery**: PASS. Se agregan tests para reglas, HTTP, Livewire, autorización, transiciones y regresión.
- **VI. Complete User-Facing Blade Interfaces**: PASS WITH DOCUMENTATION NOTE. `/account/profile` seguirá `styles.css`, vistas aprobadas y comportamiento responsive; `docs/design.md` falta en el repositorio y se registrará como deuda/documentación a restaurar, sin introducir otro framework.

## Project Structure

### Documentation (this feature)

```text
specs/006-model-profile-details/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── checklists/requirements.md
└── contracts/
    ├── account-profile.md
    └── admin-profile.md
```

### Source Code (repository root)

```text
app/
├── Enums/
│   ├── ModelProfileAvailabilityStatus.php
│   ├── ModelProfileBioStatus.php
│   ├── ModelProfilePhysicalRevisionStatus.php
│   └── PublicationTypeServiceType.php
├── Http/
│   ├── Controllers/AccountProfileController.php
│   └── Requests/
│       ├── AccountProfileUpdateRequest.php
│       ├── ModelProfileBioRequest.php
│       ├── ModelProfilePhysicalDetailsRequest.php
│       └── PublicationTypeChangeRequest.php
├── Models/
│   ├── ModelProfile.php
│   ├── ModelProfilePrivateDetail.php
│   ├── ModelProfileBio.php
│   ├── ModelProfilePhysicalRevision.php
│   ├── PublicationType.php
│   ├── Service.php
│   ├── Province.php
│   ├── Locality.php
│   └── ModelProfilePublicationTypeHistory.php
├── Policies/
│   ├── ModelProfilePolicy.php
│   ├── ModelProfileBioPolicy.php
│   └── ModelProfilePhysicalRevisionPolicy.php
├── Services/
│   ├── ModelProfileDetailsService.php
│   ├── ModelProfileBioModerationService.php
│   ├── ModelProfilePhysicalModerationService.php
│   ├── PublicationTypeChangeService.php
│   └── IdentityDocumentService.php
└── Filament/Resources/ModelProfiles/
    ├── ModelProfileResource.php
    └── RelationManagers/
        ├── ModelPhotosRelationManager.php
        ├── ModelProfileBiosRelationManager.php
        └── PublicationTypeHistoryRelationManager.php

database/
├── migrations/
├── factories/
└── seeders/

resources/views/account/
└── profile.blade.php

routes/web.php
resources/css/styles.css
tests/Feature/Account/
tests/Feature/Admin/
tests/Feature/Profile/
tests/Unit/Services/
```

**Structure Decision**: Se mantiene el monolito Laravel existente. Las reglas complejas se centralizan en Services; controllers y acciones Filament sólo coordinan validación, autorización y presentación. La pantalla Blade nueva se integra al layout visual existente sin crear frontend separado.

## Phase 0: Research Decisions

Las decisiones de investigación están consolidadas en [research.md](./research.md). No quedan decisiones técnicas sin resolver para iniciar el diseño.

## Phase 1: Design Outputs

- [data-model.md](./data-model.md): entidades, columnas, relaciones, índices, estados y estrategia de compatibilidad.
- [contracts/account-profile.md](./contracts/account-profile.md): contrato de rutas y formularios del panel privado.
- [contracts/admin-profile.md](./contracts/admin-profile.md): contrato del panel administrativo, moderación y catálogos.
- [quickstart.md](./quickstart.md): guía ejecutable de validación end-to-end y regresión.

## Complexity Tracking

No hay violaciones de la constitución que requieran excepción. La tabla de revisiones físicas y la versionación de bios agregan entidades porque la especificación exige conservar la versión pública anterior mientras una propuesta está pendiente; no se introduce un repositorio o paquete adicional.

## Post-Design Constitution Check

- **Persistence**: PASS. El diseño usa migraciones, claves foráneas, índices, unicidad y relaciones Eloquent.
- **Authorization/privacy**: PASS. El contrato separa datos privados, públicos y administrativos; todas las mutaciones pasan por ownership y autorización.
- **Moderation**: PASS. Bios y datos físicos tienen estados/versiones independientes de identidad, revisión general y publicación.
- **Compatibility**: PASS WITH MIGRATION NOTE. Se mantienen temporalmente columnas heredadas usadas por registro y tests; las tareas deberán migrar lecturas/escrituras sin eliminar datos existentes.
- **UI**: PASS WITH DOCUMENTATION NOTE. Se reutiliza `resources/css/styles.css` y vistas existentes; la falta de `docs/design.md` queda documentada como deuda del repositorio.
