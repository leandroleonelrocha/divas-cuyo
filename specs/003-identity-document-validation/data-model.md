# Data Model: Validación de identidad con documentación privada

## `users` existente

No se agregan credenciales ni datos sensibles nuevos a `users`. Se reutilizan:

| Columna | Tipo/restricción | Uso |
|---|---|---|
| `id` | bigint unsigned, PK | Cuenta propietaria o revisor. |
| `email` | varchar, unique, not null | Identidad y login existentes. |
| `email_verified_at` | timestamp nullable, index | Verificación independiente de identidad. |
| `is_admin` | boolean, default false, index | Acceso administrativo inicial junto con email verificado. |
| `password` | varchar, not null | Credencial; nunca se expone en esta feature. |

Relaciones existentes:

- `User hasOne ModelProfile`.
- `User hasMany ModelDocument` a través de `modelProfile`.
- `User` puede ser revisor mediante la FK `reviewed_by`.

## `model_profiles` existente y extensión

Agregar a la tabla existente:

| Columna | Tipo/restricción | Uso |
|---|---|---|
| `identity_status` | varchar(20), not null, default `incomplete`, index | Estado agregado: `incomplete`, `pending`, `approved`, `rejected`. |
| `identity_reviewed_at` | timestamp nullable | Fecha de la última aprobación/rechazo de identidad. |
| `identity_reviewed_by` | bigint unsigned nullable, FK a `users.id`, nullOnDelete | Administrador que resolvió la identidad. |
| `identity_rejection_reason` | text nullable | Motivo visible para la modelo cuando el estado es `rejected`. |

Constraints:

- `identity_status` sólo acepta los cuatro valores definidos por la aplicación y sus transiciones server-side.
- `identity_reviewed_at` y `identity_reviewed_by` se establecen juntos al resolver una revisión y son nullables antes de ella.
- `identity_rejection_reason` es obligatorio al rechazar y se limpia al aprobar una nueva revisión.
- `identity_status` no modifica automáticamente `review_status` ni `is_published`.

Relaciones:

- `ModelProfile belongsTo User`.
- `ModelProfile hasMany ModelDocument`.
- `ModelProfile` no agrega relaciones para fotos, videos, planes, favoritos o comentarios.

## `model_documents` nueva

| Columna | Tipo/restricción | Uso |
|---|---|---|
| `id` | bigint unsigned, PK | Identificador interno. |
| `model_profile_id` | bigint unsigned, not null, FK a `model_profiles.id`, cascadeOnDelete | Ownership del documento. |
| `type` | varchar(32), not null | Tipo extensible; inicialmente `dni_front`, `dni_back`, `selfie`. |
| `storage_path` | varchar(512), not null | Ruta interna privada; nunca se entrega al frontend. |
| `original_name` | varchar(255), nullable | Nombre original sólo como metadato operativo sanitizado. |
| `mime_type` | varchar(100), not null | MIME detectado del contenido, no confiado desde el cliente. |
| `file_size` | unsigned bigint, not null | Tamaño validado, máximo 5 MiB por archivo. |
| `status` | varchar(20), not null, default `pending` | Estado del documento: `pending`, `approved`, `rejected`. |
| `rejection_reason` | text nullable | Motivo específico si el documento fue rechazado. |
| `reviewed_at` | timestamp nullable | Fecha de revisión del documento. |
| `reviewed_by` | bigint unsigned nullable, FK a `users.id`, nullOnDelete | Administrador revisor del documento. |
| `created_at`, `updated_at` | timestamps | Metadatos de ciclo de vida. |

Constraints e índices:

- FK `model_profile_id` con `ON DELETE CASCADE`.
- FK `reviewed_by` con `ON DELETE SET NULL`.
- unique compuesto `model_profile_id + type`: sólo un documento vigente por tipo.
- índice compuesto `model_profile_id + status` para comprobar rápidamente el conjunto requerido.
- índice `type` para consultas administrativas y futuras extensiones.
- `type` es texto configurable, no un enum de base de datos, para permitir agregar tipos sin migración estructural; la configuración valida los tipos requeridos.
- `status` se restringe a `pending`, `approved` y `rejected` en el modelo/servicio; no representa el estado agregado de identidad.

## Document types

Configuración inicial:

| Type | Obligatorio | Descripción |
|---|---:|---|
| `dni_front` | Sí | Frente del documento nacional de identidad. |
| `dni_back` | Sí | Dorso del documento nacional de identidad. |
| `selfie` | Sí | Selfie solicitada para acompañar la validación. |

La lista debe poder ampliarse desde configuración de la feature. No se debe permitir enviar a revisión si falta un tipo obligatorio.

## State transitions

```text
incomplete -> pending   (modelo, sólo con documentos obligatorios válidos)
pending    -> approved  (admin verificado, conjunto completo)
pending    -> rejected  (admin verificado, motivo obligatorio)
rejected   -> pending   (modelo, documentos corregidos y conjunto completo)
```

No permitidas:

- `incomplete -> approved` o `incomplete -> rejected`;
- `pending -> pending` como resolución administrativa;
- reemplazo/eliminación por modelo en `pending`;
- reemplazo por modelo en `approved`;
- `rejected -> approved` sin nuevo envío a `pending`;
- cualquier cambio de estado por asignación directa desde un formulario público o Filament.

## Business invariants

- `email_verified_at` confirma email y nunca se modifica desde identidad.
- `identity_status = approved` no implica `review_status = approved`.
- `review_status = approved` no puede alcanzarse si `identity_status != approved`.
- `is_published = true` requiere simultáneamente `identity_status = approved` y `review_status = approved`.
- aprobar identidad no publica ni aprueba el perfil.
- rechazar identidad no debe aprobar, publicar ni modificar `email_verified_at`.
- el archivo anterior se elimina de forma segura al reemplazar y sólo permanece el vigente.
- `storage_path`, nombres físicos y contenido nunca forman parte de respuestas públicas, exports o logs.

## Access and query boundaries

- La pantalla de la modelo carga sólo el perfil propio mediante `UserPolicy`; las operaciones de documentos revalidan ownership con `ModelDocumentPolicy`.
- El panel Filament requiere `is_admin = true` y `email_verified_at` no nulo. Las acciones de identidad vuelven a validar estado, documentos obligatorios y actor dentro del servicio.
- La descarga no usa `asset()` ni `Storage::url()`: utiliza el disco privado y una respuesta Laravel con `Cache-Control: no-store`, `Pragma: no-cache` y `X-Content-Type-Options: nosniff`.
- El listado administrativo carga `user`, `reviewer` y `documents` con eager loading; no se serializan `password`, tokens, `storage_path` ni nombres físicos.
- Los índices existentes son suficientes para los filtros actuales: `model_profiles.identity_status`, `model_documents(model_profile_id, type)` unique, `model_documents(model_profile_id, status)` y `model_documents.type`.

## Migration and rollout

1. Agregar columnas de identidad a `model_profiles` con default `incomplete`; perfiles existentes no se consideran identificados automáticamente.
2. Crear `model_documents` con FKs, unique por tipo e índices.
3. Configurar/validar el disco privado y permisos del entorno antes de habilitar cargas.
4. Desplegar servicios, policies y rutas privadas sin cambiar las rutas públicas existentes.
5. Integrar la precondición de identidad a aprobación/publicación después de que el estado exista para todos los perfiles.

Las migraciones deben tener `down()` seguro. No se deben mover ni eliminar todavía las columnas legacy de `users` ni alterar credenciales.
