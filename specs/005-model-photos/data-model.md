# Data Model: Model Photos

## 1. model_photos

Representa el ítem lógico de una galería. No representa una carga individual ni contiene
binarios.

| Column | Type | Null | Constraints / indexes | Description |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | no | PK | Identificador lógico de la foto |
| model_profile_id | BIGINT UNSIGNED | no | FK → model_profiles.id, cascade delete; index | Propietario |
| current_version_id | BIGINT UNSIGNED | sí | FK → model_photo_versions.id, null on delete | Versión aprobada actualmente visible |
| position | UNSIGNED INT | no | index with profile; unique with profile during normalized state | Orden, empieza en 0 |
| is_primary | BOOLEAN | no | default false; index with profile | Foto principal lógica |
| created_at | TIMESTAMP | no | | Alta del ítem |
| updated_at | TIMESTAMP | no | | Última modificación |

### Constraints

- Una fila pertenece a un único ModelProfile.
- El máximo de cinco se valida en servicio dentro de una transacción y se centraliza en
  config/model-photos.php.
- position debe ser entero no negativo y se normaliza en una secuencia densa.
- model_profile_id + position debe ser único al finalizar cada reorder, insert o delete.
- Sólo puede haber un is_primary = true por perfil. El servicio usa lock de perfil y una
  actualización transaccional.
- is_primary = true sólo es válido si current_version_id apunta a una versión approved.
- current_version_id permanece en la versión aprobada anterior durante un reemplazo pendiente.

## 2. model_photo_versions

Representa una carga/versionado de un ítem lógico y sus variantes físicas.

| Column | Type | Null | Constraints / indexes | Description |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | no | PK | Identificador de versión |
| model_photo_id | BIGINT UNSIGNED | no | FK → model_photos.id, cascade delete; index | Ítem lógico |
| version | UNSIGNED INT | no | unique with model_photo_id | Secuencia por foto |
| supersedes_version_id | BIGINT UNSIGNED | sí | FK self, null on delete | Versión reemplazada |
| original_path | VARCHAR(512) | no | | Ruta relativa del original privado |
| processed_path | VARCHAR(512) | no | | Ruta relativa de versión normalizada privada |
| public_path | VARCHAR(512) | no | | Ruta relativa de versión con watermark privada/controlada |
| thumbnail_path | VARCHAR(512) | no | | Ruta relativa del thumbnail privado |
| original_name | VARCHAR(255) | sí | | Metadata sanitizada |
| mime_type | VARCHAR(100) | no | | MIME detectado server-side |
| file_size | UNSIGNED BIGINT | no | | Bytes del original |
| width | UNSIGNED INT | no | | Dimensión de entrada |
| height | UNSIGNED INT | no | | Dimensión de entrada |
| processed_width | UNSIGNED INT | no | | Dimensión normalizada |
| processed_height | UNSIGNED INT | no | | Dimensión normalizada |
| status | VARCHAR(20) | no | index; pending/approved/rejected | Estado de moderación |
| rejection_reason | TEXT | sí | | Motivo visible a la propietaria |
| reviewed_at | TIMESTAMP | sí | | Fecha de moderación |
| reviewed_by | BIGINT UNSIGNED | sí | FK → users.id, null on delete; index | Admin revisor |
| created_at | TIMESTAMP | no | | Fecha de carga |
| updated_at | TIMESTAMP | no | | Último cambio |

### Constraints

- FK de model_photo_id a model_photos.id con cascadeOnDelete.
- FK de reviewed_by a users.id con nullOnDelete.
- FK de supersedes_version_id a la misma tabla con nullOnDelete.
- Unique model_photo_id + version.
- status sólo admite pending, approved y rejected mediante enum de dominio y validación
  server-side.
- Un rechazo exige rejection_reason.
- Una aprobación exige que la versión esté pending, todas las variantes estén disponibles y
  registra reviewed_at/reviewed_by.
- Una nueva versión se crea en pending; nunca se actualiza el archivo físico de una versión
  existente.

## 3. Relaciones Eloquent

- User hasOne ModelProfile.
- ModelProfile hasMany ModelPhoto.
- ModelProfile puede consultar ModelPhotoVersion mediante una relación through si resulta útil
  para administración.
- ModelPhoto belongsTo ModelProfile.
- ModelPhoto belongsTo currentVersion mediante current_version_id.
- ModelPhoto hasMany versions.
- ModelPhotoVersion belongsTo ModelPhoto.
- ModelPhotoVersion belongsTo supersededVersion y puede tener versiones sucesoras.
- ModelPhotoVersion belongsTo reviewedBy User.
- ModelProfile conserva la relación existente con User.

## 4. Versionado y reemplazo

### Upload inicial

1. Crear model_photos con posición normalizada e is_primary = false.
2. Procesar y guardar variantes en una carpeta aleatoria de la nueva versión.
3. Crear model_photo_versions con version = 1, pending y current_version_id = null.
4. La foto no puede ser principal ni pública hasta aprobarse.

### Reemplazo de pending/rejected

- Crear una nueva versión para el mismo model_photo_id.
- Mantener la versión anterior para auditoría o limpieza definida por el servicio.
- La nueva versión queda pending; no se cambia current_version_id si no había aprobada.
- Limpiar motivo y auditoría de la nueva versión.

### Reemplazo de approved

- Crear una nueva versión pending con supersedes_version_id apuntando a la versión actual.
- Mantener current_version_id apuntando a la versión aprobada anterior.
- Mantener is_primary sin cambios.
- Si la nueva versión se rechaza, la anterior continúa siendo la actual.
- Si la nueva versión se aprueba, dentro de una transacción se actualiza current_version_id,
  se conserva la principalidad lógica y luego se eliminan los archivos obsoletos después del
  commit.

## 5. Position y principal

- El servicio de reorder bloquea el ModelProfile, valida que el conjunto recibido coincida
  exactamente con las fotos propias activas y reescribe posiciones 0..n-1.
- Al eliminar, las posiciones posteriores se compactan.
- Una nueva principal desmarca la anterior dentro de la misma transacción.
- Una foto pending/rejected no puede ser principal.
- Si se elimina la actual, se selecciona la primera foto con versión actual approved por
  posición; si no hay ninguna, se deja el perfil sin principal.

## 6. Storage y variantes

Todas las rutas son relativas al disk configurado model_photos, nunca absolutas:

- model-profiles/{profile}/photos/{photo}/{version}/original.{ext}
- model-profiles/{profile}/photos/{photo}/{version}/processed.webp
- model-profiles/{profile}/photos/{photo}/{version}/public.webp
- model-profiles/{profile}/photos/{photo}/{version}/thumbnail.webp

Los segmentos físicos de foto/version son UUID o tokens aleatorios. El servicio valida que las
rutas pertenezcan al patrón esperado antes de leer o eliminar.

- original: privado, sin watermark, nunca público.
- processed: privado, normalizado, lado máximo 2000 px, sin watermark.
- public: privado/controlado, normalizado con watermark; única variante candidata para futura
  exposición pública después de aprobación.
- thumbnail: privado/controlado, 400x400 con crop/cover centrado; la futura interfaz pública
  podrá usarlo sólo cuando la versión actual esté aprobada.

No se almacenan binarios en MySQL.
