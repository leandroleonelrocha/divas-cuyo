# Data Model: Panel privado de la modelo y post-login

## Schema impact

No se requieren tablas, columnas, foreign keys, índices ni migraciones nuevas. La feature compone datos que ya existen.

## Existing entities used

### User

- `id`: identidad de la cuenta autenticada.
- `name`: nombre visible de la cuenta/modelo cuando corresponda.
- `email`: correo mostrado en el resumen privado.
- `email_verified_at`: estado independiente de verificación del email.
- `is_admin`: capacidad administrativa existente; no se expone como dato de la modelo.
- relación `modelProfile`: perfil asociado de la modelo.

### ModelProfile

- `user_id`: ownership del perfil; se resuelve desde el usuario autenticado.
- `name`: nombre público.
- `whatsapp`: contacto del perfil.
- `location`: ubicación.
- `identity_status`: `incomplete`, `pending`, `approved` o `rejected`.
- `review_status`: `pending`, `approved` o `rejected`.
- `is_published`: booleano de visibilidad pública.
- `identity_rejection_reason`: motivo visible únicamente para la modelo propietaria cuando corresponda.

## Relationships

- `User hasOne ModelProfile`.
- `ModelProfile belongsTo User`.
- La sección de identidad existente gestiona la relación `ModelProfile hasMany ModelDocument`; el dashboard sólo enlaza hacia ella y no carga ni serializa documentos.

## Access rules

- `/account` no recibe `user_id` ni `model_profile_id`.
- La cuenta se obtiene de `auth()->user()` y el perfil mediante su relación.
- Invitados son redirigidos al login.
- Usuarios no verificados conservan el bloqueo actual.
- Un usuario autenticado no puede cambiar el perfil mostrado mediante parámetros de la solicitud.
- `password`, tokens, `is_admin`, `storage_path` y contenido de documentos quedan fuera del modelo de presentación.

## Query expectations

- Cargar el usuario autenticado y su `ModelProfile` en una consulta acotada o relación equivalente.
- Seleccionar sólo columnas necesarias para el panel.
- No consultar modelos de otras cuentas.
- No cargar `model_documents` para el resumen; la ruta privada existente resolverá documentos bajo su propia autorización.

## State presentation

| Concepto | Fuente | Presentación |
|---|---|---|
| Email | `users.email_verified_at` | Verificado / Pendiente de verificación |
| Identidad | `model_profiles.identity_status` | Incompleta / Pendiente / Aprobada / Rechazada |
| Revisión | `model_profiles.review_status` | Pendiente / Aprobado / Rechazado |
| Publicación | `model_profiles.is_published` | Publicado / No publicado |

Ningún estado se deriva o reemplaza por otro.
