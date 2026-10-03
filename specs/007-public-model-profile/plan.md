# Implementation Plan: Perfil público individual de modelo

**Branch**: Git actual `master`; contexto de feature `007-public-model-profile` (sin crear rama). | **Date**: 2026-09-28 | **Spec**: [spec.md](spec.md)

**Input**: `specs/007-public-model-profile/spec.md`, incluyendo las tres decisiones confirmadas en clarify.

## Summary

Construir `GET /modelos/{slug}` con Blade, una representación explícita de datos públicos y una única política de visibilidad reutilizable. Los nombres artísticos generan slugs actuales con reservas históricas y redirección al actual. Perfil, alias y fotos verifican email, identidad, revisión, publicación y principal aprobada con recurso público utilizable. No hay mapas ni coordenadas en el navegador.

El acceso anónimo a fotos será una ruta dedicada por UUID de versión, sólo para derivados con watermark. Se conservan las rutas privadas y sus autorizaciones. No implementar directorio, home de modelos, contacto ni pagos.

## Technical Context

**Language/Version**: PHP ^8.2; nombres técnicos en inglés y textos de interfaz en español.

**Primary Dependencies**: Laravel 12.69.2, Filament 5.8.2, Intervention Image 3.11.8 según composer.lock; Blade, Vite ^7 y CSS existente. Sin paquetes nuevos.

**Storage**: MySQL del proyecto y disco controlado `model_photos`; pruebas habituales SQLite en memoria según phpunit.xml, más integración MySQL aislada para concurrencia y migraciones.

**Testing**: PHPUnit ^11.5.50, RefreshDatabase, Storage::fake, pruebas HTTP/servicios, Pint, build Vite y revisión visual manual.

**Target Platform**: Aplicación web existente; navegadores móviles/escritorio a 360, 768 y 1440 px. No se encontró configuración Docker Compose en el repositorio; usar PHP/Composer/npm locales o el entorno ya provisionado.

**Project Type**: Monolito Laravel con frontend público Blade y administración Filament.

**Performance Goals**: Consultas de lectura acotadas, sin crecimiento por número de fotos/servicios: comparar fixture de 1 contra 5 fotos y 1 contra 20 servicios, mismo número de consultas de dominio. Registrar tiempos y consultas en validación; no inventar un SLA de tráfico sin medición. Principal eager; secundarias lazy; dimensiones explícitas. Hasta cinco fotos según configuración central.

**Constraints**: No originales, thumbnails privados, datos privados, IDs internos ni coordenadas en respuestas públicas. Sin caché persistente de página, redirecciones o imágenes que permita eludir revocaciones. No modificar publicación por una lectura. Únicamente documentación durante este comando.

**Scale/Scope**: Una página y una ruta de imagen, slugs históricos sin límite artificial y galería limitada por `config/model-photos.php`. No construir paginación ni listados futuros.

## Constitution Check

| Principio | Antes de research | Después del diseño |
| --- | --- | --- |
| I. Convenciones y simplicidad | PASS: arquitectura existente | PASS: controllers delgados, servicios de dominio, sin paquetes |
| II. Persistencia explícita | PASS: migraciones obligatorias | PASS: índices únicos, transacciones, ORM y backfill documentados |
| III. Límites autorizados | PASS: rutas públicas separadas | PASS: Gate público apto para invitados delega en regla central; no relajar Policies privadas |
| IV. Privacidad y moderación | PASS: exclusiones de spec | PASS: DTO sin modelos, ownership, versiones vigentes y recurso con watermark |
| V. Pruebas | PASS: reglas comprobables | PASS: matriz HTTP, integración MySQL, regresión completa y errores diagnosticables |
| VI. Diseño completo | PASS: fuentes disponibles revisadas | PASS: CSS actual, vistas existentes y revisión visual obligatoria |

`docs/design.md` no existe; se utiliza la guía real `design.md` y `resources/css/styles.css`, según la discrepancia documentada en la spec. La constitución permite ampliar CSS cuando sea necesario, aunque la guía anterior diga no crear clases. Se reutilizan componentes primero y se añaden estilos específicos mínimos en el mismo archivo; no se introduce framework. No hay excepción constitucional ni cambio de constitución.

## Project Structure

### Documentation (this feature)

```text
specs/007-public-model-profile/
├── spec.md
├── checklists/requirements.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
└── contracts/public-model-profile.md
```

`tasks.md` corresponde al siguiente comando, no se genera aquí. El setup de planificación actualizó `.specify/feature.json` para seleccionar 007; la nota de la spec sobre conservar 006 describe únicamente la fase previa de especificación. La rama Git sigue siendo `master`.

### Source Code (repository root)

Rutas previstas, todavía no implementadas:

```text
app/
├── Models/ModelProfile.php
├── Models/ModelProfileSlug.php
├── Models/ModelPhotoVersion.php
├── Services/ModelProfileSlugService.php
├── Services/PublicModelProfileVisibility.php
├── Services/PublicModelProfileQuery.php
├── Services/PublicModelPhotoService.php
├── Services/ModelProfileDetailsService.php
├── Services/ModelPhotoImageProcessor.php
├── Services/ModelPhotoService.php
├── ViewModels/PublicModelProfileViewModel.php
├── Http/Controllers/PublicModelProfileController.php
├── Http/Controllers/PublicModelPhotoController.php
├── Providers/AppServiceProvider.php
└── Console/Commands/PreparePublicModelPhotos.php
database/migrations/  # slug, reservas, token/procedencia pública y backfill
resources/views/
├── public/models/show.blade.php
├── components/public-model/  # badge, disponibilidad y galería sin modelos
└── errors/404.blade.php
resources/css/styles.css
routes/web.php
tests/Feature/PublicModels/
tests/Unit/Services/ModelProfileSlugServiceTest.php
tests/Integration/PublicModelSlugConcurrencyTest.php
phpunit.mysql.xml
```

**Structure Decision**: Mantener monolito y convenciones de Services actuales. No API JSON nueva ni patrón Repository. Gate público con usuario nullable, sin privilegios por sesión; controlador usa servicio de consulta y entrega sólo ViewModel. No cambiar globalmente el binding de ModelProfile usado por administración.

## Phase 0 — Research concluido

Ver [research.md](research.md). Resueltos: escritores de nombre artístico, namespace de slugs, cambio atómico, imágenes no públicas existentes, procedencia del watermark, lectura selectiva, índices, validación MySQL y diseño. No quedan incógnitas bloqueantes.

## Phase 1 — Diseño

### Escritura y enlaces

`ModelProfileSlugService` participa en la transacción ya existente de `ModelProfileDetailsService::update`. Bloquea el perfil, reserva candidato en un registro único y guarda nombre/slug juntos. Backfill en migración por lotes con semántica fijada al momento de la migración; sólo nombre artístico canónico no vacío, sin copiar `name`. Normalización equivalente no agrega alias; volver a un alias propio lo hace actual sin bucles. Alias apunta siempre al perfil, nunca a otro alias.

### Única regla pública

`PublicModelProfileVisibility` es la autoridad completa. Su constructor de consulta concentra condiciones relacionales mediante Eloquent y `whereHas`; su comprobación final verifica ruta segura, procedencia de watermark y archivo principal legible. No se denomina `publiclyVisible()` a un scope SQL incompleto: el disco no puede comprobarse en SQL. El servicio es el equivalente central permitido por la spec.

`PublicModelProfileQuery` resuelve slug o alias mediante esa autoridad, carga relaciones públicas en lote y construye el ViewModel sólo después de la autorización. Gate `viewPublicModelProfile` delega en la misma autoridad, devuelve denegación como 404 y acepta invitados. La entrega de imágenes usa exactamente esa autoridad. Las features 008/009 deberán usar este servicio completo, nunca sólo la consulta de candidatos; una futura paginación deberá filtrar recursos faltantes sin presentar perfiles incompletos.

No cargar user completo: `whereHas(user)` comprueba email. Seleccionar campos canónicos y claves internas necesarias sólo dentro del servicio, province/locality, publicationType, services activos, currentBio aprobado y currentApprovedPhotos/currentVersion. Comprobar pertenencia de bio y versión, no confiar sólo en IDs relacionados. Reutilizar datos precargados al construir el ViewModel, sin consultas desde Blade.

### Entrega pública de imágenes

Ruta por UUID aleatorio de versión; no se admiten parámetro variant ni IDs secuenciales. Se sirve únicamente `public_path`. Añadir procedencia persistida del derivado con watermark y preparar variantes legadas antes de habilitar su consumo. No usar thumbnail existente, generado sin watermark. Previews iniciales usan el mismo public.webp optimizado con tamaños visuales y carga diferida; un thumbnail público nuevo no es necesario para esta entrega.

Antes de responder, revalidar elegibilidad/versión actual y abrir el stream para detectar faltantes antes de enviar headers. La validación rige al inicio de cada solicitud; no se promete cancelar bytes ya entregados si hay una revocación concurrente. Errores de lectura dan 404 genérico sin paths ni excepción expuesta; registrar un código operativo interno sin datos personales.

### Presentación

ViewModel readonly compuesto por escalares/arrays públicos, sin ModelProfile ni relaciones Eloquent. Edad ausente cuando se oculta. Servicios por grupo, tipo y actividad, sin IDs/pivot. Texto escapado y bio como texto con saltos de línea; no renderizar HTML aportado por usuarios. No coordenadas, datos privados ni fallback a campos legacy.

Cabecera Divas Cuyo sin enlace al directorio inexistente ni contacto; principal e información en dos columnas de escritorio, una en móvil; galería completa ordenada, principal incluida. Características en lista descriptiva, bio, ubicación textual, tipo y grupos de servicios; omitir vacíos. CSS con object-fit: cover, aspect-ratio, ancho adaptable y foco visible. No carrusel/modal necesario. Título, H1 y descripción segura; 404 compartido genérico sin datos de perfil, también para alias ocultos.

### Entrega, pruebas y riesgos controlados

Orden de implementación: esquema y reservas → actualización/backfill de slugs → preparación de derivados y UUID → autoridad pública/query/Gate → DTO/controladores/rutas → diseño y SEO → regresión y validación visual. Detalle de datos en [data-model.md](data-model.md), contrato en [contracts/public-model-profile.md](contracts/public-model-profile.md), ejecución en [quickstart.md](quickstart.md).

No se habilita la ruta antes de terminar backfill/preparación. Una foto legada sin procedencia comprobada queda privada hasta regenerarse; esto puede dejar el perfil en 404, conforme a la spec. Registrar conteos para detectar esa situación antes del despliegue. No ejecutar preparación de imágenes ni migraciones en este comando.

Validar FR-001–013 con slugs/visibilidad/imagen directa; FR-014–024 con valores privados distinguibles y moderaciones; FR-025 con número de queries constante; FR-026–029 visual, accesibilidad y metadata; FR-030–031 con suite completa y flujos existentes. SC-008 conserva su definición de prueba humana con cinco personas, pero quedó postergado y fuera del cierre técnico actual por decisión explícita del usuario del 2026-10-01. No hay resultados humanos ni afirmación de cumplimiento; la excepción se traza en spec.md, tasks.md y validation.md.

## Complexity Tracking

Sin violaciones. El registro de reservas es necesario para unicidad entre slugs actuales e históricos; el DTO evita exposición accidental; los metadatos públicos de fotos acreditan watermark sin depender de una configuración global actual.
