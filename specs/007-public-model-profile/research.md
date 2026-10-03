# Research: Perfil público individual

Fecha: 2026-09-28. Investigación local de código/contratos y documentación oficial. Decisiones de producto: slug cambia con nombre y conserva redirecciones; principal obligatoria; ubicación sólo textual.

## 1. Slugs y concurrencia

**Decision**: `model_profiles.slug` nullable/unique y registro `model_profile_slugs` con namespace unique global de actuales e históricos. Transacción de nombre + reserva + slug bajo bloqueo de perfil. Slug histórico resuelve directamente al slug actual, con redirección 302 y `Cache-Control: no-store` después de verificar visibilidad.

**Rationale**: El único escritor productivo actual de stage_name es `app/Services/ModelProfileDetailsService.php`, ya transaccional y con lockForUpdate. Registro y UserFactory sólo crean name heredado. Filament EditModelProfile edita name/whatsapp/location, no stage_name. No cambiar estas semánticas ni generar slugs desde nombre real o legacy. 302 evita consolidar en clientes una redirección permanente que más adelante debería convertirse en 404 por privacidad.

**Alternatives considered**: Slug fijo contradice clarify. Tabla de alias única separada sin reservar actuales no garantiza unicidad cruzada. Observer como única garantía no cubre actualizaciones masivas; envolver en el servicio el punto de escritura actual y exigir el mismo flujo en futuros escritores. No cambiar getRouteKeyName globalmente.

Normalizar con Str::slug, minúsculas ASCII, longitud máxima 160 incluyendo sufijo. Base vacía tras normalizar nombre no vacío → modelo; sólo dígitos → modelo-{base}. Nombre vacío → sin slug ni acceso. Reservas de otros perfiles o tombstones nunca se reutilizan. Reusar reservas propias al volver a nombre anterior. Colisiones únicas se resuelven con -2, -3, etc.; truncar base para cada sufijo. Reintentar únicamente colisiones de la restricción de slug, no cualquier error SQL; deadlocks con reintentos limitados de la transacción completa. Agotamiento falla sin cambios parciales.

**Sources**: `app/Services/ModelProfileDetailsService.php`, `app/Models/ModelProfile.php`, `app/Filament/Resources/ModelProfiles/Pages/EditModelProfile.php`, migración `2026_09_25_000024_backfill_canonical_profile_fields.php`. Eloquent documenta que los eventos no cubren mass updates: [Laravel 12 Eloquent](https://laravel.com/docs/12.x/eloquent#events). El bloqueo pesimista dentro de transacciones se documenta en [Laravel 12 Query Builder](https://laravel.com/docs/12.x/queries#pessimistic-locking).

## 2. Visibilidad reutilizable y archivos faltantes

**Decision**: Servicio `PublicModelProfileVisibility` con selección relacional y comprobación final del principal en almacenamiento. Todos los accesos públicos, incluidos alias/fotos y futuras superficies, pasan por la autoridad completa; Gate delegado para acceso público con invitados.

**Rationale**: Las cinco condiciones incluyen un recurso físico legible. SQL sólo puede comprobar metadata. Ocultar ese límite en un scope llamado publiclyVisible dejaría futuros listados inconsistentes. No almacenar un booleano de visibilidad que quede obsoleto ante revocaciones de email/moderación o desaparición de archivos.

**Alternatives considered**: Repetir condiciones en controllers; scope global que afecta administración; readiness persistido como prueba suficiente de existencia física. Rechazados. Se puede compartir dentro del servicio una consulta de candidatos, claramente no definitiva.

**Sources**: `app/Models/ModelProfile.php`, `app/Services/ModelPhotoStorage.php`, contratos 005/006 y spec 007.

## 3. Fotos existentes y watermark verificable

**Decision**: UUID aleatorio por versión y timestamp nullable `public_watermarked_at`, establecido sólo tras generar y guardar con éxito un derivado público con watermark. Nueva generación reutiliza Intervention y almacenamiento controlado; si watermark está deshabilitado, no acredita el derivado para publicación. Comando idempotente prepara versiones aprobadas vigentes legadas, regenerando desde processed privado, sin modificar estado de moderación ni current_version_id.

**Rationale**: `ModelPhotoImageProcessor` genera thumbnail antes del watermark; `public_path` puede existir aunque watermark estuviera deshabilitado. No se puede asumir que archivos antiguos cumplen sólo porque configuración actual diga true. Se regenera exclusivamente variante pública desde un archivo sin marca para no duplicarla. La ausencia de fuente legible deja la versión no habilitada y reporta fallo controlado.

**Alternatives considered**: Abrir las rutas privadas a invitados, symlink al disco, usar thumbnails actuales o confiar en la configuración actual. Todas permiten exposición no autorizada o imágenes sin watermark. No se añade proveedor, CDN ni thumbnails públicos nuevos ahora.

Preparación escribe a un archivo nuevo, bloquea/revalida la versión/foto antes de actualizar path y timestamp; si dejó de ser vigente, descarta sólo el temporal nuevo. Sólo limpia el antiguo derivado después del commit y sin tocar originales/processed/thumbnail. Una variante pública se valida como WebP decodificable durante preparación; entrega abre stream y comprueba lectura, sin decodificar imágenes completas por visita.

La promoción actual elimina la fila y archivos de la versión anterior (`ModelPhotoService::promoteApprovedVersion`); preservar ese comportamiento. Por eso una URL antigua puede fallar por ausencia del token o por falta de vigencia, siempre con el mismo 404.

**Sources**: `app/Services/ModelPhotoImageProcessor.php`, `ModelPhotoService.php`, `ModelPhotoStorage.php`, `ModelPhotoModerationService.php`, `config/model-photos.php`. El máximo actual es cinco; los límites actuales de subida difieren de documentos antiguos y esta feature no los modifica.

## 4. Datos públicos y consultas

**Decision**: ViewModel readonly con lista explícita de campos, sin objetos Eloquent. Eager loading selectivo; user sólo en EXISTS de email. Datos físicos canónicos y currentBio aprobado con ownership verificado. Tipos conocidos virtual/encounters, servicios activos compatibles; desconocidos no habilitan grupos. Tipo inactivo conserva su nombre actual mientras siga asociado: la spec sólo exige actividad a servicios, no agrega otra condición de publicación.

**Rationale**: `$hidden` actual sólo oculta privateDetails, insuficiente contra filtraciones futuras. Las claves internas necesarias para hidratar relaciones permanecen en el servicio y se eliminan al proyectar. No leer coordenadas. Edad oculta no forma parte del ViewModel.

**Alternatives considered**: Pasar ModelProfile completo a Blade o serializar relaciones automáticamente; usar versión más reciente sin verificar vigencia. Rechazados por privacidad/moderación.

**Sources**: `app/Models/ModelProfile.php`, `Service.php`, `PublicationType.php`, `ModelProfileBio.php` y formulario `resources/views/account/profile.blade.php`.

## 5. Índices, infraestructura y pruebas

**Decision**: Agregar sólo unique de slug actual, unique de reservas y UUID de versión, más índice/FK de propietario de reserva. Reutilizar índices de is_published, review_status, email_verified_at, publication_type_id, fotos(profile,position)/(profile,is_primary), versiones(photo,status) y asociaciones existentes. Medir EXPLAIN MySQL antes de cualquier índice adicional.

**Rationale**: Búsqueda individual comienza por slug unique; índices compuestos especulativos para listados aún inexistentes no se justifican. SQLite en memoria es útil para suite habitual, pero no prueba concurrencia real de MySQL.

**Alternatives considered**: Agregar índices para futuros filtros o confiar sólo en SQLite; rechazados. Crear configuración PHPUnit MySQL aislada y tests de integración con conexiones independientes y commit real; no usar la transacción externa de RefreshDatabase en esa prueba de carreras.

**Sources**: migraciones existentes, `composer.lock`, `phpunit.xml`, `tests/TestCase.php`. No hay Compose local verificado, por eso quickstart no presupone Docker.

## 6. Diseño y límites

**Decision**: Blade con CSS existente, pequeñas extensiones donde falten componentes, galería sin carrusel ni modal obligatorio, principal destacada y repetida en orden, imágenes públicas lazy secundarias y eager principal. Ubicación sólo texto, sin scripts/proveedores de mapas.

**Rationale**: Identidad visual y responsive son requisitos; las preferencias menores restantes de la spec ofrecen defaults comprobables. Se revisaron design.md, estilos y vistas privadas/públicas existentes; no existe docs/design.md.

**Alternatives considered**: Framework CSS nuevo, lightbox externo, mapa reutilizable en esta entrega; innecesarios o contrarios a clarify.

## Resultado

No quedan decisiones bloqueantes de diseño. El tamaño de producción, tiempos medidos y evidencia visual/humana se recogerán durante implementación y validación; no se afirman como mediciones realizadas aquí.
