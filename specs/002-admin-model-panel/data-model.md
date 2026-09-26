# Data Model: Panel administrativo de modelos

## Modelo de cuenta: `User`

Tabla existente: `users`. Conserva identidad y autenticación.

| Columna | Tipo / restricción | Uso |
|---|---|---|
| `id` | bigint, PK | Identificador de cuenta. |
| `email` | varchar, unique, not null | Identidad de login y recuperación. |
| `email_verified_at` | timestamp nullable, index | Estado de verificación de email y filtro administrativo. |
| `password` | varchar, not null | Credencial hasheada. |
| `is_admin` | boolean, not null, default `false`, index | Autoriza acceso al panel interno. |
| `remember_token` | varchar nullable | Sesiones Laravel existentes. |
| `created_at`, `updated_at` | timestamps | Fecha de registro y actualización de cuenta. |

Las columnas `name`, `whatsapp`, `location` e `is_published` se trasladan a `model_profiles` mediante migración de datos. La migración debe ejecutarse en MySQL y conservar los datos durante el traslado.

### Relaciones Eloquent

- `User hasOne ModelProfile` mediante `model_profile`.
- `User hasMany PolicyAcceptance` existente.
- `User hasOne EmailVerificationToken` existente.
- `User` continúa usando `Notifiable`, `MustVerifyEmail`, recuperación de contraseña y autenticación pública.

## Modelo de perfil: `ModelProfile`

Tabla nueva: `model_profiles`.

| Columna | Tipo / restricción | Uso |
|---|---|---|
| `id` | bigint, PK | Identificador de perfil. |
| `user_id` | bigint unsigned, FK a `users.id`, unique, not null | Relación uno a uno; `cascadeOnDelete`. |
| `name` | varchar, not null | Nombre público de la modelo. |
| `whatsapp` | varchar(50), not null | Dato privado de contacto. |
| `location` | varchar, not null | Ubicación declarada. |
| `review_status` | varchar(20), not null, default `pending`, index | `pending`, `approved` o `rejected`. |
| `reviewed_at` | timestamp nullable | Momento de la última aprobación o rechazo. |
| `reviewed_by` | bigint unsigned nullable, FK a `users.id`, `nullOnDelete` | Cuenta administradora que realizó la última revisión. |
| `is_published` | boolean, not null, default `false`, index | Habilitación de publicación pública. |
| `created_at`, `updated_at` | timestamps | Fechas del perfil. |

Índices adicionales:

- índice sobre `name` para ordenar/filtrar el listado;
- índice sobre `location` para filtros y búsquedas por ubicación;
- índice sobre `review_status`;
- índice sobre `is_published`;
- índice único sobre `user_id` para asegurar un solo perfil por cuenta.

La búsqueda por email usa el índice unique existente de `users.email`; la búsqueda por WhatsApp usa un índice no único sobre `model_profiles.whatsapp` si la implementación requiere optimizar coincidencias por ese campo.

### Relaciones Eloquent

- `ModelProfile belongsTo User` mediante `user`.
- `ModelProfile` no tendrá relaciones para fotos, videos, planes, favoritos ni comentarios en esta feature.

## Estados y transiciones

### Estado de revisión

```text
pending ── aprobar ──> approved
pending ── rechazar ─> rejected
approved ─ rechazar ─> rejected
rejected ─ aprobar ──> approved
```

La aprobación no cambia `is_published`. El rechazo fuerza `is_published = false`.

### Estado de publicación

```text
approved + is_published=false ─ publicar ──> is_published=true
approved + is_published=true  ─ despublicar > is_published=false
pending/rejected              ─ publicar ──> rechazado por autorización
```

`email_verified_at` es independiente de ambos estados y sólo se modifica mediante el flujo público de verificación de email.

## Migración de datos

1. Agregar `users.is_admin` con default `false` e índice.
2. Crear `model_profiles` con FK `user_id` unique y los índices definidos.
3. Para cada fila existente de `users`, crear un `model_profiles` con `name`, `whatsapp`, `location` e `is_published`; establecer `review_status = pending` porque no existe aprobación histórica.
4. Verificar que la cantidad de perfiles creados coincide con la cantidad de usuarios y que ningún valor requerido queda sin copiar.
5. Actualizar el registro público y la cuenta autenticada para leer/escribir los datos de perfil a través de `modelProfile`.
6. En una migración posterior, eliminar de `users` las columnas trasladadas sólo cuando el código público y administrativo ya no las consulte directamente.

La primera migración de separación debe ser reversible mientras las columnas antiguas existan: su `down()` elimina perfiles creados por la migración y revierte `is_admin`; la eliminación de columnas debe quedar en una migración posterior con respaldo/plan de recuperación.

## Validación y privacidad

- `name`: requerido, string, máximo 255.
- `whatsapp`: requerido, string, máximo 50.
- `location`: requerido, string, máximo 255.
- `review_status`: sólo `pending`, `approved`, `rejected`.
- `is_published` sólo puede ser true para perfiles `approved`.
- `reviewed_at` y `reviewed_by` se actualizan únicamente al aprobar o rechazar; publicar/despublicar no modifica la auditoría de revisión.
- `password`, tokens y `remember_token` no se muestran en Filament ni en respuestas.
- Email, WhatsApp y ubicación son datos administrativos privados; no aparecen en superficies públicas por esta feature.
