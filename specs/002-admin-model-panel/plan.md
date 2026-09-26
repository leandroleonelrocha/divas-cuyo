# Implementation Plan: Panel administrativo de modelos

**Branch**: `002-admin-model-panel` | **Date**: 2026-09-15 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/002-admin-model-panel/spec.md`

## Summary

Agregar un panel interno en `/admin` usando Laravel Filament 5 para que los administradores gestionen los perfiles de modelos existentes. El registro, login, verificación de email, recuperación de contraseña y demás frontend público continuarán usando Blade y las rutas actuales.

La identidad y autenticación permanecerán en `users`. Los datos propios del perfil (`name`, `whatsapp`, `location`, `review_status` e `is_published`) se normalizarán en una tabla `model_profiles` relacionada uno a uno. La migración será incremental: primero se agrega la capacidad administrativa y se copia la información existente; luego se adapta el código público para leer y escribir la relación; las columnas antiguas de `users` se retiran únicamente en una migración posterior, cuando ya no existan consumidores.

El recurso Filament será `ModelProfileResource`, con listado paginado, búsqueda, filtros, detalle, edición básica y acciones separadas para aprobar/rechazar y publicar/despublicar. El acceso al panel requerirá `users.is_admin = true` y email verificado. La autorización se reforzará mediante `FilamentUser`, `ModelProfilePolicy` y comprobaciones server-side para cada transición.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 12  
**Primary Dependencies**: `filament/filament:^5.0`; dependencias frontend y de Laravel ya existentes; no se agregarán otros paquetes  
**Storage**: MySQL existente, con `users` y nueva `model_profiles` en `utf8mb4`  
**Testing**: PHPUnit/Laravel Feature tests, pruebas de recursos Filament/Livewire y `vendor/bin/pint --test`  
**Target Platform**: Aplicación web Laravel ejecutándose sobre PHP y MySQL  
**Project Type**: Aplicación web server-rendered con frontend público Blade y panel interno Filament  
**Performance Goals**: listado y filtros interactivos con paginación; evitar N+1 mediante relaciones y consultas adecuadas; operación fluida para al menos 1.000 perfiles  
**Constraints**: conservar rutas, lógica, validaciones y UI pública; no crear módulos de fotos, videos, planes, favoritos o comentarios; email verificado y aprobación son estados independientes; un perfil rechazado no puede publicarse  
**Scale/Scope**: un panel `admin`, un recurso de perfiles, una relación por usuario, una bandera administrativa y pruebas de acceso/moderación/compatibilidad  

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Laravel standards and minimal dependencies — PASS**: se usan convenciones Laravel/Eloquent/PSR-12 y Filament es la única dependencia nueva, requerida explícitamente.
- **II. Safe persistence — PASS**: el esquema se implementa con migraciones MySQL, foreign key y restricciones explícitas; las consultas del recurso paginan y cargan la relación `user` para evitar N+1.
- **III. Validated and authorized boundaries — PASS**: Filament verifica acceso al panel mediante `canAccessPanel`; `ModelProfilePolicy` protege consulta/edición y las acciones de moderación verifican autorización en servidor.
- **IV. Privacy and moderated publication — PASS**: los datos de contacto quedan visibles solo en el panel autorizado; aprobación, publicación y verificación de email son estados distintos; rechazo fuerza `is_published = false`.
- **V. Tested maintainable delivery — PASS**: se cubrirán aislamiento de acceso, búsqueda/filtros, edición, transiciones de moderación y regresión del registro público.

**Re-check after Phase 1 design: PASS.** El diseño mantiene Blade para el frontend, no introduce contenido fuera de alcance y conserva una ruta de migración segura por etapas.

## Project Structure

### Documentation (this feature)

```text
specs/002-admin-model-panel/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
└── contracts/
    └── admin-panel.md
```

### Source Code (repository root)

```text
app/
├── Filament/
│   ├── Resources/ModelProfiles/
│   │   ├── ModelProfileResource.php
│   │   └── Pages/
│   │       ├── ListModelProfiles.php
│   │       ├── ViewModelProfile.php
│   │       └── EditModelProfile.php
│   └── Providers/Filament/AdminPanelProvider.php
├── Models/
│   ├── User.php
│   └── ModelProfile.php
├── Policies/ModelProfilePolicy.php
└── Services/ModelProfileModerationService.php

database/migrations/
├── *_add_is_admin_to_users_table.php
├── *_create_model_profiles_table.php
├── *_backfill_model_profiles_from_users.php
└── *_remove_legacy_profile_columns_from_users_table.php  # rollout posterior

resources/views/
└── ...                          # Blade público: mismas rutas y comportamiento

tests/Feature/Admin/
├── AdminPanelAccessTest.php
├── ModelProfileResourceTest.php
└── ModelProfileModerationTest.php
```

**Structure Decision**: se adopta la estructura Laravel existente. Filament se limita al namespace `App\Filament`; el modelo y la policy se integran con `App\Models` y `App\Policies`; la lógica de transiciones se centraliza en un servicio pequeño para que las acciones de tabla y las pruebas compartan invariantes. No se crea un frontend separado ni un sistema de roles adicional.

## Architecture and Data Design

### Panel and authentication

1. Instalar Filament 5 y generar/configurar el panel `admin` en `/admin`.
2. Hacer que `User` implemente `FilamentUser` y que `canAccessPanel(Panel $panel)` permita únicamente el panel `admin`, usuarios con `is_admin = true` y `hasVerifiedEmail()`.
3. Agregar `is_admin` como booleano no nulo, default `false`, indexado. La promoción inicial se hará fuera del panel mediante procedimiento de despliegue controlado sobre una cuenta verificada; no se habilitará autoconcesión de privilegios ni CRUD de usuarios administradores.
4. Registrar `ModelProfilePolicy`: `viewAny`, `view` y `update` requieren administrador; `create` y `delete` no se ofrecen porque los perfiles nacen en el registro público. Las acciones custom de moderación/publicación deben llamar autorización server-side además de ocultarse o deshabilitarse en la UI.

### Schema and migration sequence

`users` conserva `id`, credenciales, `email_verified_at`, email y timestamps. Se agrega:

- `is_admin`: `BOOLEAN NOT NULL DEFAULT 0`, índice simple.

`model_profiles` tendrá:

- `id`: bigint unsigned primary key;
- `user_id`: bigint unsigned, `NOT NULL`, foreign key a `users.id`, `ON DELETE CASCADE`, `UNIQUE`;
- `name`: varchar, `NOT NULL`;
- `whatsapp`: varchar(50), `NOT NULL`;
- `location`: varchar, `NOT NULL`;
- `review_status`: varchar(20), `NOT NULL`, default `pending`, índice;
- `is_published`: boolean, `NOT NULL`, default `false`, índice;
- `created_at` y `updated_at`.

Agregar índices para `name`, `location`, `review_status` e `is_published`; el email y su unicidad siguen indexados en `users.email`; WhatsApp puede indexarse si el volumen real de búsquedas lo justifica. La búsqueda del recurso usará `user.email` y los campos del perfil.

Orden seguro:

1. Agregar `users.is_admin` sin cambiar valores existentes.
2. Crear `model_profiles` con foreign key, unique y defaults.
3. Backfill con Query Builder dentro de la migración de datos: crear una fila por usuario registrado, copiando `name`, `whatsapp`, `location` e `is_published`, y asignando `review_status = pending` (los perfiles publicados preexistentes podrán preservarse como `approved` según la regla documentada de backfill; no se debe inventar una aprobación para perfiles no publicados).
4. Verificar conteos y ausencia de perfiles sin usuario antes de desplegar el código que depende de la relación.
5. Adaptar registro, cuenta y cualquier notificación que lea `User::name` para usar `modelProfile`, manteniendo las rutas, `old()`, nombres de input, errores y resultado público.
6. En una entrega posterior, eliminar `name`, `whatsapp`, `location` e `is_published` de `users` solo después de confirmar que no quedan lecturas/escrituras legacy. La migración debe conservar `down()` en los pasos estructurales y documentar el backup requerido para el backfill.

### Resource behavior

- `ModelProfileResource` consulta `ModelProfile::query()->with('user')` y no incluye secretos ni contraseñas.
- La tabla muestra nombre, email, WhatsApp, ubicación, fecha de registro, email verificado, estado de revisión y publicación; permite buscar por nombre, email, WhatsApp y ubicación.
- Filtros: `review_status` (`pending`, `approved`, `rejected`), publicación (`published`/`unpublished`) y email verificado.
- La página de detalle usa una Infolist/read-only para identidad, contacto y estados.
- La página de edición permite únicamente `name`, `whatsapp` y `location`, con validaciones equivalentes a las existentes. Email, contraseña, verificación y `is_admin` no se editan desde este recurso.
- Acciones: `approve`, `reject`, `publish` y `unpublish`, con confirmación. Aprobar/rechazar no cambia la verificación de email; rechazar obliga a despublicar; publicar solo está permitido si el estado es `approved`.
- No se agregan crear/eliminar, cargas de archivos, acciones masivas ni módulos de contenido.

### Public compatibility

El frontend público seguirá renderizando Blade y usando las rutas actuales. La adaptación interna cambiará únicamente la fuente de datos al perfil relacionado: registro crea `User` y `ModelProfile` en una transacción; cuenta y vistas leen la relación; los errores y valores de formulario se conservan. Durante el período de compatibilidad se mantendrán las columnas legacy para permitir rollback de código, sin duplicar escrituras indefinidamente.

## Implementation Phases

1. **Foundation**: agregar Filament, configurar panel `admin`, implementar `is_admin`, `FilamentUser` y provider sin alterar rutas públicas.
2. **Persistence**: crear `model_profiles`, constraints/índices y backfill; verificar datos MySQL y documentar promoción del primer administrador.
3. **Domain integration**: crear `ModelProfile`, relación `User`, policy y servicio de transiciones; adaptar solo los puntos Blade existentes que leen/escriben los campos movidos.
4. **Filament resource**: construir listado, búsqueda, filtros, detalle, edición y acciones de moderación/publicación con estados e invariantes server-side.
5. **Verification**: agregar pruebas automatizadas, ejecutar suite, Pint, migraciones y validación de rutas/Blade; revisar que no se hayan tocado controllers, models, services, policies o rutas ajenos al alcance.

## Complexity Tracking

No hay violaciones constitucionales que justificar. La tabla `model_profiles` y el servicio de moderación son necesarios para separar identidad de perfil y para evitar que las acciones Filament puedan producir estados inválidos; no se agregan roles, tablas de contenido ni dependencias fuera de Filament.
