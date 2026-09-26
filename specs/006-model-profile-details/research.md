# Research: Detalles ampliados del perfil de modelo

## Decision 1: Mantener una migración gradual de los campos heredados

**Decision**: Agregar las entidades y columnas canónicas de esta feature sin eliminar inmediatamente `users.name`, `users.whatsapp`, `users.location`, `users.is_published` ni `model_profiles.name`, `model_profiles.whatsapp`, `model_profiles.location`. Migrar primero las nuevas lecturas y escrituras; retirar columnas heredadas sólo en una feature posterior cuando registro, dashboard y tests ya no dependan de ellas.

**Rationale**: El código actual de registro, `UserFactory`, dashboard, `/account/show`, `ModelProfileResource` y tests usa esos campos. Eliminarlos en la misma feature rompería compatibilidad y mezclaría una refactorización de cuenta con el objetivo del perfil.

**Alternatives considered**:

- Eliminarlos ahora: rechazado por riesgo de regresión y porque no existe una migración de datos pública/privada completa para los valores existentes.
- Mantenerlos como fuente canónica: rechazado porque perpetúa la mezcla entre cuenta, datos privados y publicación.

## Decision 2: Separar datos privados de la cuenta y perfil público

**Decision**: `ModelProfilePrivateDetail` será relación 1:1 con `ModelProfile`; los campos públicos canónicos vivirán en `model_profiles`; `User` seguirá resolviendo autenticación, email verificado y administración.

**Rationale**: Respeta ownership, privacidad y la separación de responsabilidades indicada por la feature, sin duplicar documentos de identidad.

**Alternatives considered**:

- Agregar todos los campos a `users`: rechazado por exposición accidental y por mezclar autenticación con publicación.
- Crear una tabla pública completa separada: rechazado en la primera versión porque duplicaría `model_profiles`, `is_published`, `review_status` e `identity_status`.

## Decision 3: Modelar cambios físicos pendientes como revisiones versionadas

**Decision**: Mantener en `model_profiles` la versión física pública aprobada y crear `model_profile_physical_revisions` para propuestas `pending`, `approved` o `rejected`. Al aprobar, el servicio copia el snapshot a `model_profiles`; mientras está pendiente, la versión anterior continúa pública.

**Rationale**: La especificación exige moderar cambios físicos y conservar la versión pública anterior, pero no permite que valores pendientes contaminen el perfil público.

**Alternatives considered**:

- Guardar valores pendientes en las mismas columnas: rechazado porque mezcla estados y permite exposición prematura.
- Versionar todos los campos públicos: diferido; sólo los cambios físicos requieren moderación en esta feature.

## Decision 4: Versionar biografías y promover mediante `current_bio_id`

**Decision**: `model_profile_bios` conserva cada texto y su auditoría; una nueva versión comienza `pending`, y sólo una aprobación administrativa actualiza `model_profiles.current_bio_id`.

**Rationale**: Mantiene una bio aprobada estable durante la revisión y permite corregir/re-enviar sin perder evidencia.

**Alternatives considered**:

- Sobrescribir `bio` en `model_profiles`: rechazado porque una bio pendiente no debe ser pública.
- Crear una única fila con historial textual: rechazado porque no conserva de forma clara estados, revisores y versiones.

## Decision 5: Catálogos de provincia y localidad con IDs

**Decision**: Crear `provinces` y `localities`; `localities.province_id` será FK y `model_profiles` referenciará `province_id` y `locality_id`. Se incluirán datos iniciales mínimos y un seeder/importador futuro podrá cargar el JSON completo.

**Rationale**: El usuario pidió tablas preparadas para IDs y una carga posterior desde JSON, evitando texto libre y permitiendo validar que localidad pertenece a provincia.

**Alternatives considered**:

- Guardar provincia/localidad como strings: rechazado por inconsistencia y dificultad de carga/renombrado.
- Integrar un proveedor de mapas: fuera de alcance y no necesario para seleccionar catálogos.

## Decision 6: Reglas de tipo y servicios en un servicio transaccional

**Decision**: `PublicationTypeChangeService` bloqueará cambios inválidos, conservará servicios virtuales, permitirá presenciales en `encounters`, y al pasar a `virtual` pedirá confirmación antes de desactivar asociaciones presenciales. Cada cambio real crea historial.

**Rationale**: La compatibilidad no puede depender de la UI; una transacción evita tipo actual, asociaciones e historial inconsistentes.

**Alternatives considered**:

- Filtrar sólo checkboxes: rechazado porque requests manipulados podrían guardar relaciones inválidas.
- Borrar servicios presenciales automáticamente sin confirmación: rechazado por pérdida silenciosa de una selección de la modelo.

## Decision 7: Revalidación de identidad ante cambios reales

**Decision**: Si la modelo cambia nombre real o fecha de nacimiento después de una identidad aprobada, se conserva la documentación existente, se reinicia el estado de identidad a `incomplete`, se limpian los datos de resolución y se exige volver a enviar el flujo de identidad para obtener una nueva aprobación.

**Rationale**: Evita considerar aprobados datos que ya no coinciden con la identidad validada y reutiliza el flujo existente sin duplicar documentos.

**Alternatives considered**:

- Permitir el cambio sin revalidación: rechazado por riesgo de integridad.
- Borrar documentos existentes: rechazado porque no es necesario y destruye evidencia administrativa.

## Decision 8: Panel privado server-rendered y administración Filament

**Decision**: `/account/profile` tendrá Form Requests, Policies y Services; Filament ampliará `ModelProfileResource` con secciones y Relation Managers para bios, revisiones físicas, tipo/historial y servicios.

**Rationale**: Coincide con las convenciones reales del repositorio y mantiene controllers/acciones delgadas.

**Alternatives considered**:

- Nueva SPA o API: rechazado por alcance y por no existir necesidad de una arquitectura adicional.
- Reglas directamente en Blade/Filament: rechazado por seguridad y duplicación.

## Decision 9: Referencia visual disponible y ausencia de `docs/design.md`

**Decision**: Usar `resources/css/styles.css`, `resources/views/account/dashboard.blade.php`, `show.blade.php` y `photos/index.blade.php` como referencia inmediata; documentar que `docs/design.md` falta y no introducir CSS nuevo.

**Rationale**: La constitución exige revisión visual, pero el archivo esperado no está en el repositorio. Las vistas y clases existentes permiten mantener coherencia mientras se restaura la documentación.

**Alternatives considered**:

- Inventar una guía de diseño: rechazado porque no sería una fuente aprobada.
- Añadir framework CSS: rechazado por la constitución.
