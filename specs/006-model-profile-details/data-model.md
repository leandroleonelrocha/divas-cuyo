# Data Model: Detalles ampliados del perfil de modelo

## Existing entities extended

### `users`

Se conserva como entidad de cuenta y autenticación. No se agregan nuevos atributos de publicación. Las columnas heredadas `name`, `whatsapp`, `location` e `is_published` se mantienen temporalmente por compatibilidad y se dejan fuera de las nuevas escrituras canónicas cuando sea posible.

### `model_profiles`

Se amplía como entidad canónica de publicación y estado de perfil.

Campos nuevos o reutilizados previstos:

| Campo | Regla |
|---|---|
| `publication_type_id` | FK nullable durante migración inicial o requerida después del backfill; un único tipo actual. |
| `stage_name` | Nombre público independiente del nombre real. |
| `public_age` | Nullable; entero validado entre edad real y edad real - 5. |
| `show_age` | Booleano; controla exposición pública. |
| `height_cm` | Obligatorio para perfil público completo; unidad centímetros. |
| `weight_kg` | Obligatorio para perfil público completo; unidad kilogramos. |
| `measurements` | Texto obligatorio y validado. |
| `eye_color` | Obligatorio para perfil público completo. |
| `hair_color` | Nullable. |
| `skin_color` | Nullable. |
| `body_type` | Nullable. |
| `nationality` | Obligatorio para perfil público completo. |
| `availability_status` | Enum lógico `available`/`unavailable`. |
| `province_id` | FK a `provinces`; selección por catálogo. |
| `locality_id` | FK a `localities`; debe pertenecer a `province_id`. |
| `approximate_location_text` | Nullable; nunca domicilio exacto. |
| `approximate_latitude` / `approximate_longitude` | Nullable; sólo aproximación y sin proveedor de mapas. |
| `current_bio_id` | FK nullable a `model_profile_bios`; sólo apunta a bio aprobada. |

Se conservan `review_status`, `identity_status`, `is_published`, auditoría existente, fotos y documentos.

## New entities

### `model_profile_private_details`

Relación 1:1 con `model_profiles`, con unique `model_profile_id`.

Campos: `id`, `model_profile_id`, `real_first_name`, `real_last_name`, `birth_date`, `real_height_cm` nullable, `real_weight_kg` nullable, `real_measurements` nullable, `nationality` nullable, `private_phone` nullable, timestamps.

Reglas: no edad persistida; `birth_date` no futura; acceso sólo por ownership/modela autorizada, administración autorizada o validaciones internas.

### `provinces`

Catálogo con `id`, `name`, `slug`, `is_active`, timestamps. `slug` unique. Se prepara para seed/import futuro desde JSON.

### `localities`

Catálogo con `id`, `province_id`, `name`, `slug`, `is_active`, timestamps. Unique compuesto `province_id + slug`, índice por provincia y FK. La aplicación valida que la localidad corresponda a la provincia enviada.

### `publication_types`

Catálogo con `id`, `name`, `slug`, `allows_in_person_services`, `is_active`, timestamps. `slug` unique. Seed inicial: `virtual` y `encounters`.

### `services`

Catálogo con `id`, `name`, `slug`, `service_type`, `is_active`, `sort_order`, timestamps. `slug` unique. `service_type` sólo `virtual` o `in_person`.

### `model_profile_service`

Pivot con `model_profile_id`, `service_id`, timestamps opcionales. Unique compuesto `model_profile_id + service_id`; índices para ambas FKs.

### `model_profile_publication_type_history`

Historial con `id`, `model_profile_id`, `from_publication_type_id` nullable, `to_publication_type_id`, `changed_by_user_id` nullable, `source`, `reason` nullable, `changed_at`, timestamps. Índices por perfil/fecha y FKs con `nullOnDelete` para actores y tipos históricos según estrategia de integridad. `source` sólo `model`, `admin`, `system`.

### `model_profile_bios`

Versiones con `id`, `model_profile_id`, `content`, `status`, `reviewed_at` nullable, `reviewed_by` nullable, `rejection_reason` nullable, timestamps. Estados `pending`, `approved`, `rejected`; índice por perfil/estado/fecha. Sólo una bio aprobada puede ser `current_bio_id`.

### `model_profile_physical_revisions`

Snapshot moderable con `id`, `model_profile_id`, `height_cm`, `weight_kg`, `measurements`, `eye_color`, `hair_color` nullable, `skin_color` nullable, `body_type` nullable, `nationality`, `status`, `submitted_by`, `reviewed_at` nullable, `reviewed_by` nullable, `rejection_reason` nullable, timestamps. Estados `pending`, `approved`, `rejected`; índices por perfil/estado/fecha.

La versión aprobada se materializa en las columnas públicas de `model_profiles`; una revisión pendiente no altera esos valores.

## Relationships

```text
User 1──1 ModelProfile
ModelProfile 1──1 ModelProfilePrivateDetail
ModelProfile N──1 PublicationType
ModelProfile N──1 Province
ModelProfile N──1 Locality
Province 1──N Locality
ModelProfile N──N Service (model_profile_service)
ModelProfile 1──N ModelProfileBio
ModelProfile 1──N ModelProfilePhysicalRevision
ModelProfile 1──N ModelProfilePublicationTypeHistory
ModelProfile 1──N ModelPhoto (existing)
ModelProfile 1──N ModelDocument (existing)
```

## Lifecycle and transaction rules

1. Completar perfil privado/público valida ownership; las ediciones directas se guardan sólo en el área autorizada.
2. Un cambio de identidad real reinicia `identity_status` a `incomplete`, limpia auditoría de resolución y conserva documentos para permitir un nuevo envío.
3. Un cambio físico crea una revisión `pending`; aprobación administrativa copia el snapshot a `model_profiles`; rechazo conserva valores aprobados.
4. Una bio nueva crea `pending`; aprobación actualiza `current_bio_id`; rechazo no modifica la bio actual.
5. Un cambio de tipo bloquea y valida servicios, actualiza el tipo actual y crea historial en una transacción.
6. `encounters -> virtual` requiere confirmación de interfaz; al confirmar desactiva lógicamente las asociaciones presenciales sin perder servicios virtuales.
7. Disponibilidad y ubicación no modifican `is_published`, `review_status` ni `identity_status`.

## Compatibility and migration notes

- Crear primero tablas/catálogos y columnas nullable donde el backfill no pueda ser determinista.
- Backfill `stage_name` desde `model_profiles.name` cuando exista; crear detalles privados desde valores de registro sólo con una regla documentada y sin convertir automáticamente un nombre ambiguo en dato real sin confirmación.
- Copiar `model_profiles.whatsapp` a `private_phone` sólo si se confirma que representa teléfono privado; mantener el campo antiguo durante la transición.
- Mapear `model_profiles.location`/`users.location` a `province_id`/`locality_id` sólo cuando exista coincidencia segura; conservar el texto en `approximate_location_text` en caso contrario.
- No eliminar columnas heredadas ni cambiar el flujo de registro en la misma entrega sin pruebas de regresión explícitas.
