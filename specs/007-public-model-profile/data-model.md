# Data Model: Perfil público individual

## ModelProfile existente

Agregar `slug`: varchar(160), nullable, unique. Nullable permite cuentas registradas sin stage_name; no implica un enlace público. No agregarlo a entrada masiva de usuario. Se mantiene una única columna actual; el resto de datos públicos conserva los campos y moderaciones existentes.

Relación nueva `slugs`: hasMany ModelProfileSlug. Invariante: todo slug actual no nulo existe en el registro de reservas con el mismo propietario. Sólo el servicio de slugs modifica ambos dentro de una transacción. Un perfil sin stage_name canónico no se resuelve públicamente.

## ModelProfileSlug — nueva tabla model_profile_slugs

| Campo | Tipo / restricción | Finalidad |
| --- | --- | --- |
| id | bigint PK interno | Persistencia; nunca público |
| slug | varchar(160), unique | Namespace compartido de actual e históricos |
| model_profile_id | FK nullable, index, nullOnDelete | Propietario; null conserva reserva tras eliminación |
| created_at / updated_at | timestamps | Control interno, no público |

No `is_current`: se determina comparando con ModelProfile.slug, evitando dos fuentes de verdad. Reserva con propietario null es tombstone no reutilizable y responde 404. No UI de historial ni datos personales copiados en reservas.

Normalización: minúsculas ASCII, palabras separadas por guion, sin guiones externos; formato de ruta `[a-z0-9]+(?:-[a-z0-9]+)*`, rechazando cadenas sólo numéricas y longitud mayor que 160. Nombre no vacío pero normalización vacía usa `modelo`; nombre numérico usa prefijo `modelo-`. Sufijo de colisión empieza en 2 y cabe dentro del máximo. Volver a una reserva propia la reutiliza.

Transición de nombre: bloquear perfil → normalizar y buscar/reservar candidato → conservar reservas previas → guardar nombre y slug actual → commit. Lecturas nunca actualizan slug. Renombrados simultáneos del mismo perfil se serializan; entre perfiles la unicidad del registro decide. En A→B→C tanto A como B apuntan directamente a C al resolverse, sin cadenas guardadas.

## ModelPhotoVersion existente

| Campo nuevo | Tipo / restricción | Finalidad |
| --- | --- | --- |
| public_token | UUID aleatorio v4, nullable inicialmente, unique | Localizador opaco de versión para entrega pública |
| public_watermarked_at | timestamp nullable | Evidencia interna de generación correcta de variante pública con watermark |

Los nuevos registros reciben token; backfill genera tokens faltantes sin usar nombres ni IDs como token. Un token no acredita acceso. La combinación status approved + versión vigente de su propia foto + public_watermarked_at + archivo seguro/legible + perfil elegible es obligatoria. No se muestra token como label ni se expone model_profile_id/model_photo_id.

Mantener `public_path` privado dentro del backend. Thumbnail actual sigue privado. No duplicar estado de moderación ni añadir booleano de publicación de foto. Preparar un derivado no aprueba versión ni cambia la principal.

## Relaciones existentes utilizadas

- user: sólo verificación mediante EXISTS; no email en memoria de presentación.
- province: id/name; locality: id/province_id/name, omitiendo localidad incompatible.
- publicationType: id/slug/name; sólo virtual/encounters habilitan grupos conocidos.
- services: id/name/service_type/is_active/sort_order, con asociación al perfil; pivot e IDs nunca salen del servicio.
- currentBio: id/model_profile_id/content/status, aprobado y del mismo perfil.
- currentApprovedPhotos/currentVersion: claves de relación, position/is_primary/status/public_token/public_path/public_watermarked_at y dimensiones necesarias; asegurar que current_version_id pertenece a esa foto.

Excluir privateDetails, documents, reviewer, histories, physicalRevisions y todas las columnas de coordenadas. Seleccionar columnas explícitas del perfil: claves internas necesarias, slug/stage_name, estados usados para autorización y campos públicos canónicos. Ninguna clave interna llega a Blade.

## PublicModelProfileViewModel — sin persistencia

| Dato público | Regla |
| --- | --- |
| stageName / canonicalUrl | Nombre artístico actual y enlace por slug |
| verifiedLabel | “Modelo verificada por Divas Cuyo” |
| availabilityLabel | “Disponible” / “No disponible” |
| location | Provincia, localidad compatible, zona aproximada; omitir ausentes |
| characteristics | Etiqueta/valor formateados; edad incluida sólo si show_age y public_age definido |
| bio | Texto aprobado vigente o null |
| publicationTypeLabel | Tipo actual conocido o null |
| serviceGroups | Nombres activos compatibles ordenados, sin grupos vacíos |
| primaryPhoto / photos | URL opaca, alt público, dimensiones; sólo fotos autorizadas |
| title / description | Construidos desde datos públicos permitidos |

No modelo Eloquent, atributos secretos, hashes de documentos, estado de revisión, IDs internos, paths, coordenadas ni edad oculta. `show_age` puede usarse dentro del constructor, no se requiere enviarlo a Blade. Orden de fotos `(position, id)` y servicios `(sort_order, id)`; el desempate interno no expone IDs.

## Migración y rollout

1. Crear columnas/registro con restricciones únicas y campos de fotos nullable; no modificar migraciones históricas.
2. Backfill de reservas y slug actual por lotes ordenados por ID, usando únicamente stage_name canónico no vacío. Ejecutable de forma idempotente, bajo bloqueo por perfil; no sobreescribe slug válido creado por escritura concurrente. Congelar normalización en migración, no depender de servicios futuros. Persistencia mediante Eloquent con modelos locales de migración si se necesita aislar evolución del modelo.
3. Backfill de UUID sin certificar watermark de archivos antiguos. Migraciones no procesan binarios ni dependen de disponibilidad del disco.
4. Comando `model-photos:prepare-public` prepara las versiones vigentes aprobadas sin evidencia de watermark: valida fuente, genera nueva variante marcada, guarda, revalida vigencia bajo bloqueo y actualiza path/timestamp. Idempotente; `--dry-run` reporta conteos sin escribir; fallos dejan registro no apto y salida no exitosa. No listar paths ni nombres privados en salida.
5. Antes de habilitar rutas, verificar conteos de perfiles con slug y principal apta. Perfiles no aptos siguen 404 sin mutar is_published.
6. Rollback de esquema elimina sólo campos/tablas nuevos después de desactivar rutas y código consumidor. Se pierde historial de enlaces al eliminar reservas; respaldarlo antes de rollback. No borrar originales ni variantes por rollback de esquema. No se usan migrate:fresh ni borrados de datos en producción.

## Índices y medición

Unique de model_profiles.slug, model_profile_slugs.slug y model_photo_versions.public_token son necesarios para resolución/unicidad; la FK de reserva requiere índice. Los índices existentes de publicación, tipo y relaciones se conservan. No añadir índice aislado de public_watermarked_at ni compuestos de futuros listados sin EXPLAIN y evidencia.
