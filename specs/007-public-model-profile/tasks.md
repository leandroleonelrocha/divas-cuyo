# Tasks: Perfil público individual de modelo

**Feature**: `007-public-model-profile` · **Created**: 2026-09-29

**Estado vigente (2026-10-01)**: cierre técnico del MVP completado. 59 tareas completadas; T058 postergada/no ejecutada por decisión explícita del usuario; cero tareas técnicas obligatorias pendientes. Evidencia en [validation.md](validation.md#cierre-técnico-del-mvp--t059-y-t060--2026-10-01). Sin rollout ni commit.

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/public-model-profile.md](contracts/public-model-profile.md) y [quickstart.md](quickstart.md).

**Prerequisites**: Documentos anteriores y constitución vigente. No implementar directorio, home, contacto, mapas, pagos ni otras features.

**Decisión de alcance vigente (2026-10-01, solicitada por el usuario)**: T058/SC-008 se posterga y no es requisito del cierre técnico del MVP en esta etapa. Motivo: concluir la entrega técnica con regresión y revisión visual, excluyendo expresamente la prueba con participantes de esta etapa. No se atribuye la decisión a falta de participantes ni a un resultado del estudio. T058 conserva su ID y casilla sin marcar, fuera del alcance actual; no fue ejecutada. El cierre requiere T059/T060 y toda la evidencia técnica restante.

**Tests**: Solicitados explícitamente. Escribir las pruebas de cada bloque antes de su implementación, comprobar el fallo esperado y hacerlas pasar al cerrar el bloque. Los checks permanecen vacíos hasta ejecutar y verificar el trabajo.

**Organization**: Setup → fundamentos → historias P1 en orden de dependencias (US2, US3, US1, US4) → US5 (P2, incluida en MVP) → US6 (P2) → performance, hardening y validación final. Se conservan los números de historia de la spec.

## Format y convenciones

Cada tarea tiene checkbox, ID secuencial, `[P]` opcional, `[USn]` en fases de historias y rutas relativas a la raíz. Los nombres de migración propuestos se reservan para esta feature; ajustar sólo el timestamp si ya existe una colisión al implementar.

`[P]` significa que, una vez satisfechos los prerrequisitos de su fase/bloque, puede ejecutarse junto con las otras tareas señaladas del mismo bloque en archivos distintos. No autoriza adelantar dependencias ni editar simultáneamente archivos compartidos.

La petición de regla central `publiclyVisible()` se satisface con `PublicModelProfileVisibility`, el equivalente completo elegido en plan/research. No agregar un scope SQL llamado publiclyVisible que ignore almacenamiento. Gate, página, alias y fotos delegan en la misma autoridad; no cambiar el diseño aprobado.

## Phase 1: Setup

**Objetivo**: Preparar validación sobre el proyecto existente sin reinstalar arquitectura ni dependencias innecesarias.

- [X] T001 Revisar entorno y fuentes de diseño (`design.md`, `resources/css/styles.css`, `resources/views/account/dashboard.blade.php`), confirmar versiones de `composer.lock`/`package.json` y registrar baseline de suite existente y discrepancia de `docs/design.md` en `specs/007-public-model-profile/validation.md`.
- [X] T002 Crear helpers sintéticos en `tests/Support/PublicModelProfileFixtures.php` reutilizando `database/factories/UserFactory.php`, con estados distinguibles, fotos WebP válidas en Storage::fake y valores centinela privados; no inventar ModelProfileFactory ni usar documentos personales reales.
- [X] T003 Crear `phpunit.mysql.xml` y `tests/Integration/MySqlTestCase.php` para base MySQL exclusiva de test, credenciales externas, bloqueo si no es base de pruebas y conexiones independientes sin transacción externa de RefreshDatabase; documentar invocación en `specs/007-public-model-profile/quickstart.md`.

## Phase 2: Foundational — esquema y autoridad compartidos

**Objetivo**: Proveer estructuras comunes a todas las historias. No abrir todavía endpoints públicos.

### Tests

- [X] T004 [P] Escribir pruebas de esquema en `tests/Feature/PublicModels/PublicModelSchemaTest.php`: slug nullable/unique, reservas actuales/históricas únicas, FK nullOnDelete que conserva tombstone, token UUID unique y evidencia de watermark inicialmente null.
- [X] T005 [P] Escribir pruebas de autoridad en `tests/Feature/PublicModels/PublicModelVisibilityRuleTest.php`: cinco condiciones, ownership de versión principal, ruta segura, archivo legible, watermark acreditado, user ausente/estados desconocidos y disponibilidad independiente; datos de fixtures aún sin rutas HTTP.

### Implementation

- [X] T006 [P] Crear `database/migrations/2026_09_29_000001_add_public_slug_to_model_profiles.php` y `database/migrations/2026_09_29_000003_create_model_profile_slugs_table.php` con slug varchar(160) nullable unique y tabla model_profile_slugs con reserva unique/FK nullable indexada nullOnDelete; rollback sólo de estructuras nuevas, sin tocar migraciones previas.
- [X] T007 [P] Crear `database/migrations/2026_09_29_000002_add_public_watermark_evidence_to_model_photo_versions.php` y `database/migrations/2026_09_29_000005_add_public_token_to_model_photo_versions.php` con public_token UUID nullable unique y public_watermarked_at nullable; no certificar archivos existentes ni procesar imágenes en migración.
- [X] T008 Integrar `app/Models/ModelProfileSlug.php`, relación slugs en `app/Models/ModelProfile.php` y campos/casts públicos internos en `app/Models/ModelPhotoVersion.php`; impedir que slug/token/procedencia se acepten desde entrada masiva del usuario y preservar binding privado.
- [X] T009 Implementar `app/Services/PublicModelProfileVisibility.php` como única autoridad: consulta Eloquent de candidatos con EXISTS de email y estados, principal propia vigente approved y comprobación final de procedencia/ruta/lectura mediante `app/Services/ModelPhotoStorage.php`; reutilizar resultado durante la solicitud, sin persistir booleano de visibilidad ni mutar publicación.
- [X] T010 Registrar Gate `viewPublicModelProfile` con usuario nullable en `app/Providers/AppServiceProvider.php`, delegado íntegramente a la autoridad y denegación 404 sin bypass administrativo; ejecutar T004–T005 y registrar resultado en `specs/007-public-model-profile/validation.md`.

**Checkpoint**: Esquema y regla completa comprobados; siguen intactos auth, binding y Policies existentes. Todas las historias dependen de este bloque.

## Phase 3: US2 — Slugs seguros e históricos (P1)

**Goal**: Crear enlaces actuales seguros y reservar todos los anteriores para el mismo perfil.

**Independent Test**: A través del servicio y guardado privado, nombres repetidos obtienen slugs únicos; A→B→C y vuelta a A conservan reservas propias; rollback no separa nombre y slug. Redirecciones HTTP se integran en US1.

### Tests

- [X] T011 [P] [US2] Escribir `tests/Unit/Services/ModelProfileSlugServiceTest.php` para normalización ASCII, tildes/signos, máximo 160 incluido sufijo, base vacía → modelo, numérico → modelo-{base}, nombre vacío sin slug y normalización equivalente sin alias redundante.
- [X] T012 [P] [US2] Escribir `tests/Feature/PublicModels/PublicModelSlugPersistenceTest.php` para reserva actual/histórica, colisiones, vuelta a alias propio, tombstone no reutilizable, transacción de `/account/profile` y rechazo de slug enviado por cliente.
- [X] T013 [P] [US2] Escribir `tests/Feature/PublicModels/PublicModelSlugBackfillTest.php` para datos previos, stage_name ausente sin fallback legacy, asignación por lotes, reejecución idempotente y preservación de asignación concurrente válida.

### Implementation

- [X] T014 [US2] Implementar `app/Services/ModelProfileSlugService.php`: normalizar, reservar candidato en namespace global bajo bloqueo de perfil, reusar reserva propia, sufijos -2/-3 con truncado, capturar sólo violación unique correspondiente y reintentar deadlock de transacción completa de forma acotada sin cambios parciales.
- [X] T015 [US2] Integrar el servicio en la transacción de `app/Services/ModelProfileDetailsService.php` para guardar stage_name y slug juntos; no cambiar semántica de registro ni edición legacy en Filament, ni agregar observer como única garantía.
- [X] T016 [US2] Crear `database/migrations/2026_09_29_000004_backfill_public_profile_slugs.php` con normalización fijada en migración y Eloquent aislado de cambios futuros, lotes ordenados/bloqueo por perfil, sin copiar name/email/teléfono; documentar pérdida de historial al revertir esquema en `specs/007-public-model-profile/quickstart.md`.
- [X] T017 [US2] Ejecutar T011–T013 y regresión de guardado privado; verificar reservas sin cadenas y atomicidad en `tests/Feature/PublicModels/PublicModelSlugPersistenceTest.php`, y registrar resultados en `specs/007-public-model-profile/validation.md`.

## Phase 4: US3 — Principal y galería públicas seguras (P1)

**Goal**: Entregar versiones vigentes aprobadas con watermark mediante URL opaca y preparar fotos legadas.

**Independent Test**: Con perfil elegible de fixture, endpoint de imagen entrega sólo public.webp actual; reemplazo aprobado retira URL anterior y falta de principal invalida también secundarias. Galería se puede verificar mediante su ViewModel/componente antes de la página de US1.

### Tests

- [X] T018 [P] [US3] Escribir `tests/Feature/PublicModels/PublicModelPhotoDeliveryTest.php`: 200 WebP/token válido y headers, 404 token ausente/UUID inválido/versión antigua/pending/rejected/ajena, pérdida de elegibilidad, archivo inseguro/ilegible/faltante y exclusión de original/processed/thumbnail; stream abierto antes de responder y cerrado al finalizar.
- [X] T019 [P] [US3] Escribir `tests/Feature/PublicModels/PreparePublicModelPhotosTest.php` para --dry-run sin escrituras, idempotencia, legado no certificado, fuente ausente, error de almacenamiento, carrera de cambio de vigencia y limpieza exclusiva del nuevo temporal; nunca cambiar moderación/principal.
- [X] T020 [P] [US3] Ampliar `tests/Unit/Services/ModelPhotoImageProcessorTest.php` para watermark efectivamente aplicado antes de acreditar variante, configuración deshabilitada sin evidencia, preservación de originales/processed y thumbnail siempre privado.
- [X] T021 [P] [US3] Escribir `tests/Feature/PublicModels/PublicModelGalleryTest.php` para orden `(position,id)`, límite central, principal incluida, exclusión de secundaria inválida, pending/rejected mantiene vigente y eliminación de principal reasigna o deja 404.

### Implementation

- [X] T022 [US3] Actualizar `app/Services/ModelPhotoImageProcessor.php`, `app/Services/ModelPhotoService.php` y `app/Models/ModelPhotoVersion.php` para UUID en versiones nuevas y public_watermarked_at sólo tras generar/guardar con watermark; no alterar límites de upload, aprobación ni eliminación de versiones retiradas.
- [X] T023 [US3] Crear `database/migrations/2026_09_29_000006_backfill_public_photo_tokens.php` para UUID aleatorios faltantes en lotes sin derivarlos de IDs/nombres y sin acreditar watermark ni leer el disco.
- [X] T024 [US3] Implementar `app/Console/Commands/PreparePublicModelPhotos.php` y reutilización necesaria de `app/Services/ModelPhotoStorage.php` para `model-photos:prepare-public`: regenerar sólo public desde processed privado, validar WebP, escribir nuevo recurso, revalidar bajo bloqueo y actualizar path/evidencia; limpiar antiguo tras commit, --dry-run por conteos, fallos no habilitan entrega y salida no exitosa sin paths.
- [X] T025 [US3] Implementar `app/Services/PublicModelPhotoService.php` para resolver UUID, aplicar autoridad central, verificar versión/ownership y abrir sólo stream público seguro antes de headers; secundarias inválidas se omiten y la principal inválida impide toda entrega, sin decodificar imágenes completas por visita.
- [X] T026 [US3] Crear `app/Http/Controllers/PublicModelPhotoController.php` y ruta `public.models.photos.show` `/modelos/fotos/{publicToken}` en `routes/web.php` fuera de auth/verified/guest; imponer UUID, headers de contrato, errores genéricos no-store y ningún selector de variante ni redirect a storage.
- [X] T027 [US3] Crear base readonly de `app/ViewModels/PublicModelProfileViewModel.php` con slots públicos explícitos y proyección de primaryPhoto/photos desde recursos autorizados, sin retener modelos; crear `resources/views/components/public-model/gallery.blade.php` con principal destacada/galería ordenada, URLs opacas, alt público y proporciones sin deformación.
- [X] T028 [US3] Ejecutar T018–T021 y regresiones de fotos/privacidad/moderación existentes; registrar resultados en `specs/007-public-model-profile/validation.md`, manteniendo privadas todas las rutas y variantes previas.

## Phase 5: US1 — Perfil navegable y visibilidad pública (P1)

**Goal**: Consultar perfil por slug sin sesión, con verificación, disponibilidad y ubicación básica pública; resolver aliases sólo después de autorizar.

**Independent Test**: GET actual → 200, alias → 302 directo, inexistente/no publicable/ID → mismo 404. Repetir como invitado, propietaria y admin. Unavailable sigue público y no altera estados.

### Tests

- [X] T029 [P] [US1] Escribir `tests/Feature/PublicModels/PublicModelProfileVisibilityTest.php` para matriz HTTP completa de email, identidad incomplete/pending/rejected, review pending/rejected, publicación/principal, usuario ausente y estados desconocidos, disponibilidad y ausencia de mutaciones en GET.
- [X] T030 [P] [US1] Escribir `tests/Feature/PublicModels/PublicModelSlugRoutingTest.php` para slug actual, aliases A→B→C/vuelta a A sin bucles, 302 interno sin query arbitraria, límite/formato/ID numérico, tombstone y alias oculto 404 sin Location; conservar binding privado.
- [X] T031 [P] [US1] Escribir `tests/Feature/PublicModels/PublicModelResponseBoundaryTest.php` para 404 genérico idéntico, no-store en 200/302/404 y ausencia de profile/user/modelos Eloquent o campos privados en datos de vista, HTML y errores.

### Implementation

- [X] T032 [US1] Implementar `app/Services/PublicModelProfileQuery.php`: resolver registro de reserva, aplicar autoridad completa antes de Location/DTO, eager loading selectivo inicial de principal/provincia/localidad, email sólo por EXISTS, sin relaciones privadas ni coordenadas.
- [X] T033 [US1] Ampliar `app/ViewModels/PublicModelProfileViewModel.php` con stageName, canonicalUrl, verifiedLabel, availabilityLabel y ubicación textual compatible; sin fallback a name/location/whatsapp legacy ni estados internos, con ausentes omitidos.
- [X] T034 [US1] Crear `app/Http/Controllers/PublicModelProfileController.php` y `public.models.show` `/modelos/{slug}` en `routes/web.php`: GET anónimo, slug limitado, Gate central, actual 200 y alias 302 directo sólo si visible; no cambio de getRouteKeyName, caché no-store también en denegaciones y sin login/403.
- [X] T035 [US1] Crear `resources/views/public/models/show.blade.php`, `resources/views/errors/404.blade.php`, `resources/views/components/public-model/verified-badge.blade.php` y `resources/views/components/public-model/availability.blade.php` con diseño base usando `resources/css/styles.css`: cabecera, nombre/H1, principal/galería, insignia, disponibilidad, ubicación y 404 genérico; sólo ViewModel, sin links a listado/contacto.
- [X] T036 [US1] Ejecutar T029–T031 junto a pruebas de imágenes y slugs, comprobar recorrido actual/alias/404 como invitado y registrar resultados en `specs/007-public-model-profile/validation.md`; no habilitar acceso en un entorno con backfill/preparación pendientes.

## Phase 6: US4 — Datos públicos aprobados y privacidad integral (P1)

**Goal**: Incorporar físicos, edad opcional, currentBio approved y ubicación pública sin exponer revisiones ni datos reales.

**Independent Test**: Perfiles completos y mínimos, con valores distintos para canónicos/pendientes/privados; sólo valores aprobados visibles y ausencia de datos privados incluso en atributos/metadata.

### Tests

- [X] T037 [P] [US4] Escribir `tests/Feature/PublicModels/PublicModelPrivacyTest.php` con centinelas de email/nombres reales/birth_date/teléfono/DNI/selfie/paths/revisores/motivos/IDs y coordenadas; revisar HTML, atributos, comentarios y datos de vista sin falsos positivos por números de dimensiones.
- [X] T038 [P] [US4] Escribir `tests/Feature/PublicModels/PublicModelApprovedContentTest.php` para currentBio propio approved, pendiente/rechazado conserva anterior, históricos/ajenos excluidos y revisión física pending/rejected no reemplaza canónicos; verificar promoción aprobada.
- [X] T039 [P] [US4] Escribir `tests/Feature/PublicModels/PublicModelAgeLocationTest.php` para show_age true/false/null, edad real nunca derivada, provincia/localidad/zona opcionales, localidad incompatible omitida y ningún mapa/coordenadas en navegador.

### Implementation

- [X] T040 [US4] Ampliar `app/Services/PublicModelProfileQuery.php` con columnas físicas canónicas y currentBio aprobado del mismo perfil; no cargar privateDetails/documents/revisiones/historial ni birth_date o coordenadas, y mantener claves sólo internas para hidratar relaciones.
- [X] T041 [US4] Completar `app/ViewModels/PublicModelProfileViewModel.php` con características formateadas, public_age sólo cuando permitido y bio aprobada nullable; omitir campos/labels vacíos y no conservar modelos ni datos ocultos en propiedades privadas del DTO.
- [X] T042 [US4] Integrar características, “Sobre mí” y ubicación en `resources/views/public/models/show.blade.php` con texto escapado, saltos de línea seguros y secciones vacías omitidas; ejecutar T037–T039 y registrar evidencia en `specs/007-public-model-profile/validation.md`.

## Phase 7: US5 — Tipo y servicios compatibles (P2, parte del MVP)

**Goal**: Mostrar modalidad actual y servicios activos compatibles, agrupados y ordenados.

**Independent Test**: Virtual con asociación presencial inconsistente jamás la muestra; Encuentros admite ambos grupos; inactive desaparece en siguiente solicitud y sin servicios no hay sección vacía.

### Tests

- [X] T043 [US5] Escribir `tests/Feature/PublicModels/PublicModelServicesTest.php` para virtual/encounters, incompatibilidad forzada, servicio inactive, orden `(sort_order,id)`, grupos vacíos, tipo ausente/desconocido y tipo inactivo todavía asociado según research; no IDs/pivot/historial público.

### Implementation

- [X] T044 [US5] Ampliar `app/Services/PublicModelProfileQuery.php` y `app/ViewModels/PublicModelProfileViewModel.php` para cargar publicationType/services activos selectivamente, aplicar defensa de compatibilidad, agrupar/ordenar nombres y omitir grupos vacíos; sin condición adicional de visibilidad por disponibilidad/tipo inactivo.
- [X] T045 [US5] Mostrar etiqueta del tipo y grupos en `resources/views/public/models/show.blade.php`, sin auditoría ni contacto; ejecutar T043 y conjunto de `tests/Feature/PublicModels/`, registrar checklist de los doce puntos MVP en `specs/007-public-model-profile/validation.md`.

**Checkpoint MVP funcional**: T001–T045 completadas. Cubre US1–US5 y los doce puntos pedidos con diseño base y privacidad obligatoria. Es un hito interno demostrable, no aceptación final ni autorización de despliegue; faltan US6 y validaciones finales exigidas por la spec/constitución.

## Phase 8: US6 — Mejoras visuales y SEO posteriores al MVP (P2)

**Goal**: Completar diseño responsive/accesible y metadata segura; separar refinamiento del núcleo funcional sin entregar HTML sin diseño.

**Independent Test**: Verificar H1/title/description y ausencia de datos privados en metadata; página mínima/completa y 404 mantienen estructura semántica. La revisión manual final se registra en Phase 11.

### Tests

- [X] T046 [P] [US6] Escribir `tests/Feature/PublicModels/PublicModelSeoTest.php` para título exacto, H1 único con nombre artístico, descripción pública segura, escape de texto y metadata de 404 sin nombre/estados/edad oculta.
- [X] T047 [P] [US6] Escribir `tests/Feature/PublicModels/PublicModelPresentationTest.php` para landmarks, alt público, secciones omitidas, controles nativos, ausencia de links inexistentes/contacto/mapas y estructura de 404; no afirmar que tests HTTP validan contraste o geometría.

### Mejoras visuales

- [X] T048 [US6] Refinar `resources/css/styles.css`, `resources/views/public/models/show.blade.php` y `resources/views/components/public-model/gallery.blade.php` para columnas a 1440 px, tablet 768 px, apilado 360 px, object-fit cover/aspect-ratio, textos largos y galería usable sin carrusel obligatorio; reutilizar tokens y clases existentes antes de añadir estilos.
- [X] T049 [US6] Completar foco visible, semántica, labels independientes del color y contraste 4,5:1/3:1 en `resources/views/components/public-model/verified-badge.blade.php`, `resources/views/components/public-model/availability.blade.php`, `resources/views/errors/404.blade.php` y `resources/css/styles.css`, sin alterar las pantallas privadas.

### SEO básico

- [X] T050 [US6] Agregar title y description seguros a `app/ViewModels/PublicModelProfileViewModel.php` y `resources/views/public/models/show.blade.php`, sólo nombre/modalidad/ubicación pública; comprobar H1, ejecutar T046–T047 y registrar resultados en `specs/007-public-model-profile/validation.md`; sin SEO avanzado.

## Phase 9: Performance posterior al MVP

**Objetivo**: Medir y optimizar sobre la carga selectiva ya obligatoria, sin inventar índices para futuras features.

- [X] T051 Escribir/ejecutar `tests/Feature/PublicModels/PublicModelQueryBudgetTest.php` comparando 1/5 fotos y 1/20 servicios con igual número de queries de dominio; bloquear lazy loading accidental y ajustar `app/Services/PublicModelProfileQuery.php`/`app/Services/PublicModelProfileVisibility.php` si falla, sin cargar user privado ni originales.
- [X] T052 Ajustar carga eager de principal/lazy de secundarias y dimensiones en `resources/views/components/public-model/gallery.blade.php`; medir solicitudes y uso de public.webp sin thumbnail privado, registrar tiempos observados y EXPLAIN MySQL de slug/relaciones en `specs/007-public-model-profile/validation.md`; justificar cualquier índice adicional antes de crearlo.

## Phase 10: Hardening posterior al MVP

**Objetivo**: Profundizar validación operativa/concurrencia; los controles de privacidad, propiedad, 404, watermark y no-store ya deben existir en el MVP.

- [X] T053 [P] Implementar/ejecutar `tests/Integration/PublicModelSlugConcurrencyTest.php` con configuración MySQL aislada: conexiones independientes y commit real para mismo slug en dos perfiles, alias reservado y dos renombrados del mismo perfil; verificar atomicidad, retries limitados, rollback y tombstone sin reasignación.
- [X] T054 [P] Implementar/ejecutar `tests/Feature/PublicModels/PublicModelRevocationTest.php` para pérdida de elegibilidad entre solicitudes, alias sin Location, recursos anteriores retirados, intento de reutilizar ETag/304 y ausencia de cache pública; comprobar que sesiones privilegiadas no amplían permisos.
- [X] T055 Verificar diagnóstico y manejo de fallos en `app/Services/PublicModelPhotoService.php` y `app/Console/Commands/PreparePublicModelPhotos.php`: logs internos con código operativo sin paths/datos personales, salida no exitosa de preparación parcial y stream cerrado en error; ampliar `tests/Feature/PublicModels/PreparePublicModelPhotosTest.php` según hallazgos.
- [X] T056 Validar forward/backfill/repetición/rollback en MySQL aislado desde esquema 006 y esquema limpio, conservar originales y documentar respaldo de reservas antes de rollback, conteos y ejecución dry-run/real en `specs/007-public-model-profile/quickstart.md` y `specs/007-public-model-profile/validation.md`; no habilitar rutas con preparación pendiente ni usar migrate:fresh sobre datos existentes.

## Phase 11: Validación visual manual, regresión y cierre

**Objetivo**: Demostrar cumplimiento completo, sin marcar pruebas humanas como ejecutadas por disponer de scripts.

- [X] T057 Revisar manualmente perfil completo/mínimo/textos largos/404 en 360, 768 y 1440 px, teclado, foco, contraste, jerarquía, galería, badge, físicos, bio, ubicación, tipo y servicios; registrar capturas y resultados en `specs/007-public-model-profile/validation.md` y corregir desviaciones en `resources/views/public/models/show.blade.php`/`resources/css/styles.css` antes de cerrar.
- [ ] T058 **POSTERGADA — no requerida para el cierre técnico del MVP por decisión explícita del usuario (2026-10-01); no ejecutada, SC-008 no evaluado.** Alcance original preservado: ejecutar SC-008 con cinco personas reales y perfiles de datos sintéticos: al menos cuatro identifican nombre/disponibilidad/tipo en menos de 30 segundos sin ayuda; registrar resultados en `specs/007-public-model-profile/validation.md`, dejando explícitamente pendiente si no se dispone de participantes.
- [X] T059 Ejecutar suite completa `php artisan test`, integración `phpunit.mysql.xml`, `vendor/bin/pint --test` y `npm run build`, cubrir `/account`, `/account/profile`, auth, Filament, identidad, fotos, moderaciones, servicios, tipo, ubicación y disponibilidad; resolver regresiones y registrar resultados reales en `specs/007-public-model-profile/validation.md`.
- [X] T060 Recorrer todos los escenarios de `specs/007-public-model-profile/quickstart.md`, conciliar evidencias con FR-001–031 y SC-001–009 en `specs/007-public-model-profile/validation.md`, actualizar sólo instrucciones que lo requieran y documentar uso obligatorio de la autoridad central por futuras 008/009; no declarar entrega completa si falta validación requerida.

## Dependencies & Execution Order

### Grafo por fases

```text
Setup T001–T003
  → Foundational T004–T010
    → US2 T011–T017 (slug/persistencia)
      → US3 T018–T028 (foto vigente entregable)
        → US1 T029–T036 (página, alias y 404)
          → US4 T037–T042 (contenido/privacidad)
            → US5 T043–T045 (tipo/servicios) → MVP funcional
              → US6 T046–T050 (refinamiento visual y SEO)
                → Performance T051–T052
                  → Hardening T053–T056
                    → Validación final T057–T060
```

US2 y US3 son P1 igual que US1: se adelantan para que la primera página navegable use slugs reales y fotos públicas seguras. US3 puede preparar sus pruebas y lógica tras fundamentos en paralelo con US2 cuando no toca archivos compartidos, pero la integración de página espera ambas. US4 y US5 extienden la misma query/DTO/vista y se integran secuencialmente.

### Dependencias críticas

- T004/T005 antes de implementación de fundamento; T006 y T007 antes de T008; T008 antes de T009; T009 antes de T010. T006/T007 pueden desarrollarse en paralelo, migraciones se ejecutan en orden.
- T011–T013 antes de T014–T016; T014 antes de T015, T016 usa normalización congelada equivalente sin llamar al servicio futuro; T017 valida todo el bloque.
- T018–T021 antes de T022–T027; T022/T023 antes de T024; T009/T010 antes de T025; T025 antes de T026/T027. T028 cierra el bloque.
- T029–T031 antes de T032–T035; T032/T033/T026/T027 antes de T034/T035; T036 exige pruebas previas completas. Endpoint no se habilita fuera de entorno de prueba hasta terminar backfill y preparación.
- T037–T039 antes de T040; T040 → T041 → T042. T043 → T044 → T045. No editar query/DTO/vista de US4 y US5 simultáneamente.
- T046/T047 antes de T048–T050; T048 y T049 comparten CSS, son secuenciales; T050 puede cambiar DTO sólo cuando US5 esté integrado.
- T053 requiere T003 y US2, T054 requiere US1/US3; su ejecución se agrupa en hardening. T055/T056 siguen a sus pruebas correspondientes, y T059 se realiza tras las últimas correcciones visuales/operativas.
- T060 depende de toda la evidencia técnica anterior y de la decisión explícita sobre T058. Desde 2026-10-01, T058 está postergada por el usuario y no bloquea el cierre técnico del MVP. No se permite simular resultados ni declarar SC-008 satisfecho; se conserva para una etapa futura sin fecha comprometida.

## Parallel Examples

| Historia / bloque | Tareas que pueden ejecutarse juntas | Condición |
| --- | --- | --- |
| Fundamentos | T004 + T005; luego T006 + T007 | Setup terminado; tests separados y después migraciones separadas |
| US2 | T011 + T012 + T013 | Fundamentos completos; archivos de pruebas distintos |
| US3 | T018 + T019 + T020 + T021 | Fundamentos completos y helpers estables; sin ejecución concurrente sobre la misma BD mutable |
| US1 | T029 + T030 + T031 | US2 y US3 completos |
| US4 | T037 + T038 + T039 | US1 completo |
| US5 | T043 junto con revisión read-only del contrato | Un solo archivo de test; T044/T045 son secuenciales, no hay par de mutaciones independiente dentro de esta historia |
| US6 | T046 + T047 | MVP completo |
| Hardening | T053 + T054 | MySQL exclusivo para T053 y SQLite aislado para T054; archivos separados |

Hay 21 tareas marcadas `[P]`. El paralelismo propuesto es una opción de ejecución futura, no autorización para compartir una BD de pruebas destructiva ni editar los mismos archivos al mismo tiempo.

## Implementation Strategy

### MVP recomendado

Completar T001–T045: fundamentos + US2 + US3 + US1 + US4 + US5. No reducir a US1: el usuario exige galería, bio, físicos, ubicación, tipo y servicios junto con privacidad. La página inicial tiene diseño base; las mejoras visuales detalladas, SEO, performance medida, hardening adicional y validación visual manual quedan separados después.

| Punto MVP solicitado | Tareas principales |
| --- | --- |
| 1. Slug seguro | T006, T011–T017 |
| 2. Regla publiclyVisible completa | T005, T009–T010 |
| 3. GET por slug | T029–T036 |
| 4. Sólo datos públicos aprobados | T031, T037–T042 |
| 5. Principal pública aprobada | T007–T009, T018–T028 |
| 6. Galería aprobada | T021, T025–T028, T035 |
| 7. Disponibilidad | T029, T033, T035 |
| 8. Ubicación pública | T033, T039–T042 |
| 9. currentBio approved | T038, T040–T042 |
| 10. Tipo y servicios compatibles | T043–T045 |
| 11. Privacidad y 404 | T005, T018, T029–T031, T037–T042 |
| 12. Tests de visibilidad/privacidad | T005, T018, T029–T031, T037–T039 |

El MVP es un hito interno funcional; no elimina FR-026–031 ni las condiciones de entrega de la constitución. Para el cierre técnico actual se requieren T046–T057 y T059–T060 con evidencias reales; T058 queda explícitamente postergada por la decisión de alcance del usuario del 2026-10-01.

### Trazabilidad de requisitos

| Requisitos | Cobertura |
| --- | --- |
| FR-001–003 | US2, T030/T034, T053/T056 |
| FR-004–008 | T005/T009/T010, US1, T054 |
| FR-009–013 | US3, T005/T009, T054/T055 |
| FR-014–017, FR-021–024 | US4, T031, T046/T050 |
| FR-018–020 | US5 |
| FR-025 | T032/T040/T044, T051/T052 |
| FR-026–028 | T027/T035, T047–T049, T057/T058 |
| FR-029 | T046/T050 |
| FR-030–031 | Tests por historia, T053–T060 |

### Resumen de distribución

| Bloque | Cantidad |
| --- | ---: |
| Setup | 3 |
| Fundamentos | 7 |
| US1 | 8 |
| US2 | 7 |
| US3 | 11 |
| US4 | 6 |
| US5 | 3 |
| US6 | 5 |
| Performance | 2 |
| Hardening | 4 |
| Validación final | 4 |
| Total | 60 |

## Notes

- Generar este archivo no implementa tareas ni ejecuta migraciones/pruebas de producto.
- `specs/007-public-model-profile/validation.md` es el registro futuro de evidencia previsto por plan/quickstart; no se crea ni se rellena con resultados ficticios durante tasks.
- No agregar nuevos requisitos de negocio ni cambiar las tres decisiones confirmadas: slug cambiante con redirecciones, principal obligatoria y ubicación sin mapas/coordenadas.
- No hay `.specify/extensions.yml` al generar este documento; no hay hooks previos/posteriores que ejecutar.


## Iteración acotada 2026-09-29 — Setup/Foundation mínima + US1

El pedido de implementación posterior limita esta iteración a la base de US1 y excluye US2–US6/Polish. Este alcance sustituye temporalmente el orden de dependencias completo, sin eliminar requisitos de entrega de la feature.

- Completadas íntegramente: T001, T002, T005, T009, T010, T029, T031, T033.
- Parciales (checkbox pendiente): T004/T006 sólo esquema slug nullable unique, sin reservas; T007 sólo public_watermarked_at, sin public_token; T008 sólo cast de procedencia, sin ModelProfileSlug; T030 sólo casos de slug directo/inválido/numérico en PublicModelResponseBoundaryTest, sin aliases; T032 resolución directa de slug y carga selectiva, sin reservas; T034 endpoint 200/404, sin 302; T035 vista básica y componentes de insignia/disponibilidad/404, sin fotos; T036 tests del alcance ejecutados, faltan integración con US2/US3 y preparación de datos.
- T003 pendiente: infraestructura MySQL de concurrencia corresponde al trabajo de slugs excluido; SQLite usado para los tests de esta iteración.
- T011–T028, T037–T060 pendientes por exclusión explícita de US2–US6 y fases finales. La base readonly del ViewModel se creó como requisito mínimo de US1; esto no completa T027 ni implementa la galería.
- Foundation implementada mediante `2026_09_29_000001_add_public_slug_to_model_profiles.php` y `2026_09_29_000002_add_public_watermark_evidence_to_model_photo_versions.php`. Las futuras tareas deben extender el esquema con nuevas migraciones, no intentar agregar otra vez estas columnas ni modificar migraciones ya aplicadas.
- No generación/backfill de slug ni acreditación automática del watermark. La ruta sólo resuelve slugs ya asignados y exige procedencia explícita de principal. Por diseño los datos existentes permanecen 404 hasta que US2/US3 preparen lo necesario; no se abre una ruta temporal por ID.
- No endpoint de imágenes, galería, físicos, bio, servicios, SEO ni refinamiento visual. Se reutilizó CSS existente sin modificarlo; validación visual manual final sigue pendiente.
- Evidencias, archivos y limitaciones: [validation.md](validation.md). No se modificó ningún checklist de calidad ni se hizo commit.


## Iteración acotada 2026-09-29 — US2 y fundamentos de slugs

- Completadas en esta iteración: **T006, T011, T012, T013, T014, T015, T016, T017, T030, T032, T034**.
- T006 se completa conservando la migración 000001 de US1 y agregando `2026_09_29_000003_create_model_profile_slugs_table.php`. T016 usa `2026_09_29_000004_backfill_public_profile_slugs.php`; no se alteran migraciones previas. Son ajustes de nombres para extender el esquema existente.
- T011 distribuye casos entre prueba unitaria de normalización y prueba de persistencia (truncado con sufijo y normalización equivalente). T013 prueba 101 perfiles, idempotencia y una asignación intercalada entre selección y bloqueo; no equivale a concurrencia MySQL real.
- T004 queda parcial: reservas únicas y tombstones comprobados en PublicModelSlugPersistenceTest, pero falta token UUID de fotos. T007 continúa parcial sin cambios. T008 completa modelo/relación/reservas protegidas de entrada masiva; permanece parcial por token de fotos.
- T035 permanece parcial por principal/galería. T036 completa recorrido slug/alias/404 y regresión US1/US2, pero permanece parcial por integración y preparación US3.
- T003, T018–T028 y T037–T060 siguen pendientes por alcance. No se marcaron tareas de fotos ni Polish, incluida T059 aunque se ejecutaron suite completa y Pint solicitado.
- Slug cambiante con aliases directos según clarify/research; no hay catálogo adicional de palabras reservadas en los artefactos. Se respetan reservas actuales, históricas y tombstones, sin inventar términos prohibidos.
- Backfill probado sólo sobre datos sintéticos SQLite; no aplicado a la base de desarrollo/producción. No se generaron ni prepararon fotos de aplicación.
- Resultados y archivos: [validation.md](validation.md). Sin commit automático.


## Recuperación acotada 2026-09-30 — US3

- Se recuperó el working tree sin reiniciar US3 ni eliminar cambios previos. Baseline reproducido antes de editar: **15 failed, 1 passed** (13 fallos por columna public_token ausente; 2 por public_watermarked ausente en el resultado del procesador).
- Completadas y validadas: **T018–T028**. Dependencias completadas: **T004, T007, T008**, al incorporar token UUID único/protegido y verificar esquema/backfill.
- T007 extiende la migración 000002 existente mediante `2026_09_29_000005_add_public_token_to_model_photo_versions.php`; T023 utiliza `2026_09_29_000006_backfill_public_photo_tokens.php`, porque 000004 pertenece a los slugs. No se modificaron migraciones anteriores.
- T027 conserva el DTO de US1, agrega proyección explícita de principal/galería y reutiliza estilos de 005 con una regla acotada a la galería pública. Se verificó el componente con cinco imágenes sintéticas a 360/768/1440 px. Esto no completa la página final ni las validaciones visuales de US6/Polish.
- No quedan tareas parciales de US3. T035/T036 mantienen sus checks anteriores: su dependencia de fotos ya está resuelta, pero no se declara el cierre de US1 ni habilitación operativa de datos reales en esta sesión exclusivamente US3.
- T003 y T037–T060 siguen fuera de alcance. No se marcaron T051/T052/T055/T059 por ejecutar controles necesarios de consultas, errores, regresión o formato en US3.
- No se ejecutaron migraciones ni preparación sobre datos reales; tampoco commit. Resultados, límites y estado final: [validation.md](validation.md).


## Cierre técnico — 2026-10-01

T059 y T060 completadas: suite general 299 tests/1876 assertions; MySQL aislado 7/188; Pint global y build PASS; regresión administrativa posterior al ajuste de imports 49/264. Quickstart y FR-001–031/SC-001–009 conciliados en validation.md. No quedan tareas técnicas obligatorias pendientes.

T058 conserva su casilla sin marcar: **POSTERGADA / no requerida para el cierre técnico actual**, por decisión explícita del usuario de no realizar el estudio en esta etapa. Motivo: concluir la entrega técnica del MVP con la regresión y revisión visual existentes. SC-008 no se evaluó; no hay participantes, tiempos ni resultados registrados. Guion conservado para una etapa futura sin fecha comprometida. Esta decisión reemplaza el bloqueo de cierre anterior, sin borrar la trazabilidad ni alterar el criterio original.
