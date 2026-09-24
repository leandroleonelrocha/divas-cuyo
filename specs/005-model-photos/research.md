# Research: Gestión de fotografías de modelos

## Decision 1: Separar el ítem lógico de sus versiones

**Decision**: Usar `model_photos` como ítem estable de la galería y
`model_photo_versions` para cada carga/versionado de archivos.

**Rationale**: Un reemplazo de una fotografía aprobada debe conservar la versión aprobada
mientras la nueva versión está pendiente. Sobrescribir `storage_path` no permite cumplir
esa regla de forma segura. El ítem lógico conserva `position`, `is_primary` y un
`current_version_id`; la versión pendiente se procesa de forma independiente.

**Alternatives considered**:

- Sobrescribir los paths de una única fila: rechazado porque puede retirar la versión aprobada
  antes de que la nueva sea aprobada.
- Agregar columnas pending/approved a `model_photos`: rechazado por duplicación de metadata
  y dificultad para extender auditoría/versiones.
- Crear una fila nueva sin entidad lógica: rechazado porque complica el máximo de cinco fotos,
  posición y principalidad.

## Decision 2: Estados y promoción de versiones

**Decision**: Las versiones usan `pending`, `approved` y `rejected`. Una aprobación
válida mueve `model_photos.current_version_id` a la versión aprobada dentro de una transacción.
Una versión nueva aprobada reemplaza la referencia actual; la versión anterior queda como
historial no actual y sus archivos pueden eliminarse después de confirmar la promoción.

**Rationale**: La referencia explícita al current evita que una versión aprobada antigua siga
siendo seleccionada por consultas públicas después de aprobar el reemplazo. Durante una
revisión pendiente, la referencia actual permanece intacta.

**Alternatives considered**:

- Derivar la versión actual por `status = approved` y fecha: rechazado por ambigüedad cuando
  existen varias versiones aprobadas.
- Eliminar la versión anterior antes de promover la nueva: rechazado por riesgo de pérdida y
  de perfil sin representación aprobada.

## Decision 3: Procesamiento de imágenes

**Decision**: Usar una clase de servicio de procesamiento basada en Intervention Image v3
con driver GD, si la extensión GD está disponible. El flujo genera original, procesada,
thumbnail y variante pública con marca de agua. La dependencia se agrega sólo porque PHP/GD
sin una capa de imagen reusable aumentaría el riesgo de errores de orientación, WebP,
redimensionado y composición del watermark.

**Rationale**: El procesamiento requerido incluye normalización, preservación de relación de
aspecto, crop centrado, WebP y watermark. La librería encapsula esas operaciones y el servicio
mantiene el resto de la aplicación independiente del driver.

**Alternatives considered**:

- GD/Imagick directamente desde controllers: rechazado por acoplamiento, duplicación y mayor
  superficie de errores.
- Procesamiento en navegador: rechazado porque no es confiable para validación, privacidad ni
  reproducibilidad server-side.
- Procesamiento externo: fuera de alcance para la primera versión.

## Decision 4: Variantes y privacidad

**Decision**: Todas las variantes se guardan en el disk dedicado `model_photos`, configurado
como privado. El original nunca recibe watermark y nunca se entrega públicamente. La variante
`public` es la única candidata para una futura consulta pública, siempre filtrada por
`model_photos.current_version_id` y versión `approved`; en esta feature tampoco tendrá URL
pública permanente.

**Rationale**: Mantener todas las variantes privadas evita mezclar storage sensible con
`public/` y permite cambiar local por S3/R2/B2 mediante configuración del disk.

**Alternatives considered**:

- Guardar la variante pública bajo `public/`: rechazado porque expone archivos sin pasar por
  autorización y dificulta invalidación al reemplazar.
- Exponer el original para administración: rechazado; Filament usará entrega privada autorizada.

## Decision 5: Watermark

**Decision**: Reutilizar `public/images/logo-divas-cuyo.webp` como asset de marca existente.
El servicio aplicará el logo sobre la variante pública después del resize, limitado por una
configuración de ancho relativo, opacidad y posición. El original y la variante procesada
privada permanecen sin watermark.

**Rationale**: Se evita inventar branding y se mantiene la identidad visual existente.

**Configuration**:

- máximo por perfil: 5 fotos lógicas;
- máximo por upload: 5120 KB;
- MIME: JPEG, PNG y WebP;
- dimensiones de entrada: 800x800 a 5000x5000 px;
- lado máximo procesado: 2000 px;
- thumbnail: 400x400 px con crop/cover centrado;
- watermark: logo existente, posición configurable, opacidad configurable.

## Decision 6: Principalidad y reemplazo

**Decision**: Sólo una versión `approved` actual puede representar la foto principal.
`model_photos.is_primary` vive en el ítem lógico, no en una versión pendiente. Al reemplazar
una principal aprobada, `is_primary` no cambia y `current_version_id` sigue apuntando a la
versión vieja hasta aprobar la nueva. Al aprobar la nueva, la referencia se promueve y la
principalidad se conserva. Si se elimina la principal, se elige la primera foto aprobada por
`position`; si no existe, se deja el perfil sin principal.

**Rationale**: Evita que una foto pendiente sea principal o pública y conserva continuidad
visual durante moderación.

## Decision 7: Orden y límite

**Decision**: `position` es un entero no negativo, único por `model_profile_id`, y se
normaliza como una secuencia densa comenzando en 0. El reorder recibe únicamente IDs de fotos
del perfil autenticado, se verifica el conjunto completo dentro de una transacción y luego
reasigna posiciones.

**Rationale**: La secuencia densa simplifica eliminación, consultas públicas y ordenamiento.
El servicio no confía en posiciones ni IDs parciales enviados por el navegador.

## Decision 8: Limpieza y fallos

**Decision**: El servicio escribe cada variante en una carpeta lógica de la versión, registra
paths sólo después de confirmar los writes, y elimina archivos nuevos si falla cualquier paso
antes de persistir la versión. Al promover una versión aprobada, la base se actualiza primero
en una transacción; los archivos obsoletos se eliminan después de confirmar el commit. Si una
limpieza posterior falla, se registra el error sin revertir una promoción válida.

**Rationale**: Prioriza no perder la versión aprobada ni dejar referencias a archivos inexistentes.
La limpieza posterior es recuperable y auditable.

## Decision 9: Autorización

**Decision**: `ModelPhotoPolicy` cubre view, upload/create, update/reorder, replace, delete,
setPrimary y private delivery. La identidad del perfil se resuelve desde
`auth()->user()->modelProfile`. La revisión administrativa usa `isVerifiedAdmin()` detrás
de Policy/Service para permitir migrar luego a permisos específicos.

## Decision 10: Dependencias y frontend

**Decision**: Mantener Blade para `/account/photos`, Filament 5 para administración y
`resources/css/styles.css` para UI. No se agrega framework CSS. La única dependencia nueva
propuesta es Intervention Image v3 para procesamiento server-side, sujeta a disponibilidad de
GD y documentada en Composer.

## Deferred operational policy

La retención legal permanente de originales y versiones históricas no se define en esta feature.
Los archivos obsoletos se eliminan después de una promoción o eliminación exitosa, según el
servicio, y la política podrá ampliarse antes de producción regulada.

