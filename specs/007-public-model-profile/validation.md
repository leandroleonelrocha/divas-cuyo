# Validación — 007-public-model-profile

**Estado vigente al 2026-10-01: MVP cerrado técnicamente, con T058 postergada por decisión explícita del usuario y SC-008 no evaluado.** Resultado final y trazabilidad en [cierre T059/T060](#cierre-técnico-del-mvp--t059-y-t060--2026-10-01). Las secciones anteriores al cierre conservan el historial de cada iteración y no representan pendientes actuales.

Fecha: 2026-09-29. Alcance autorizado: Setup/Foundation estrictamente necesaria + base de US1. No representa entrega completa de la feature.

## Resultado funcional

`GET /modelos/{slug}` resuelve sólo slug actual explícitamente asignado. No generación, alias, reservas ni binding global modificado. No ruta por ID ni listado.

`PublicModelProfileVisibility::publiclyVisible()` es la regla definitiva, reutilizable por Gate `viewPublicModelProfile`. Su consulta de candidatos verifica email no nulo, identidad approved, review approved, publicación true y principal con versión vigente aprobada propia y public_watermarked_at presente. La comprobación final consulta estado persistido, exige un único principal, nombre artístico no vacío, ruta segura y stream legible/no vacío; cierra el stream. Disponibilidad no participa.

El query de presentación selecciona sólo claves internas y datos públicos básicos y eager-load province/locality con columnas explícitas. Email se verifica mediante EXISTS sin cargar user; no consulta privateDetails/documentos/revisiones/coordenadas. Blade sólo recibe DTO readonly con stageName, canonicalUrl, verifiedLabel, availabilityLabel, location; sin modelo subyacente.

Actual elegible → 200; inexistente, numérico, formato inválido, estado no elegible o principal no apta → mismo 404 genérico sin Location y con no-store. Propietaria/admin no tienen bypass. Unavailable sigue 200 sin modificar is_published. La ubicación sólo usa provincia/localidad compatible/zona canónica de 006. Sin fallback a datos legacy.

## Foundation mínima y límites

Agregar slug nullable unique y public_watermarked_at nullable conserva cuentas existentes y no acredita fotos antiguas. No se agregó UUID, tabla de reservas, servicio de slug, preparación de imágenes ni endpoint de fotos.

**Limitación operativa deliberada:** no existe todavía un flujo productivo de esta feature para asignar slugs o acreditar fotos. Los tests preparan fixtures sintéticos válidos explícitamente. Los perfiles existentes siguen 404 hasta la preparación de US2/US3; no rellenar evidencia de watermark por presunción. Es una base funcional verificable, no una feature lista para despliegue.

Las dos migraciones se ejecutaron únicamente en las bases aisladas de tests; no se migró la base de desarrollo/producción. Tampoco se ejecutó backfill ni se modificaron imágenes existentes.

Se revisaron `design.md`, estilos y vistas existentes; `docs/design.md` sigue ausente. La vista reutiliza clases existentes sin ampliar CSS ni implementar US6. No hubo revisión visual manual ni SEO; siguen pendientes según alcance.

## Evidencia de ejecución

| Comprobación | Resultado |
| --- | --- |
| Baseline `php artisan test --compact` antes de cambios | 195 passed, 949 assertions |
| Tests escritos antes de implementación | Fallo esperado: falta columna slug; luego corregida integración de eager loading |
| `php artisan test tests/Feature/PublicModels --compact` final | 33 passed, 186 assertions |
| `php artisan test --compact` final | 228 passed, 1135 assertions |
| Pint aplicado a PHP de la iteración | PASS; ajustes de formato únicamente en nuevos archivos |
| Pint --test sobre PHP de la iteración | PASS |
| `vendor/bin/pint --test` global | FAIL preexistente en app/Filament/Resources/ModelProfiles/Pages/ViewModelProfile.php: fully_qualified_strict_types; archivo sin cambios respecto a HEAD |
| `git diff --check` | PASS |

No se aplicó el ajuste de Pint global fuera de alcance. No hubo MySQL concurrente, build adicional (no se modificaron assets), validación manual responsive ni prueba humana SC-008; corresponden a fases excluidas.

Pruebas cubren esquema nullable/unique/metadata no asignable, matriz de estados, perfil eliminado/usuario ausente, estados desconocidos, sesiones, ausencia de principal/versión, ownership incorrecto, aprobación, procedencia, ruta insegura, archivo ausente/vacío/error de lectura, revocación de email con modelo cargado, omisión/compatibilidad de ubicación, texto escapado, no-store y centinelas de privacidad. Se inspeccionan datos de vista y SQL para evitar relaciones/columnas privadas.

## Tareas

Completas: **T001, T002, T005, T009, T010, T029, T031, T033**.

Parciales: **T004, T006, T007, T008, T030, T032, T034, T035, T036**; los checks quedan pendientes para no dar por completas reservas, tokens, aliases ni fotos que fueron excluidos.

Diferidas: **T003**, por infraestructura MySQL innecesaria en esta iteración; **T011–T028, T037–T060**, por US2–US6/Polish fuera del alcance. Las verificaciones globales pedidas se ejecutaron, sin marcar T059 completa porque incluye MySQL/validación final más amplia.

## Archivos creados

- `app/Http/Controllers/PublicModelProfileController.php`
- `app/Services/PublicModelProfileQuery.php`
- `app/Services/PublicModelProfileVisibility.php`
- `app/ViewModels/PublicModelProfileViewModel.php`
- `database/migrations/2026_09_29_000001_add_public_slug_to_model_profiles.php`
- `database/migrations/2026_09_29_000002_add_public_watermark_evidence_to_model_photo_versions.php`
- `resources/views/public/models/show.blade.php`
- `resources/views/errors/404.blade.php`
- `resources/views/components/public-model/verified-badge.blade.php`
- `resources/views/components/public-model/availability.blade.php`
- `tests/Support/PublicModelProfileFixtures.php`
- `tests/Feature/PublicModels/PublicModelProfileVisibilityTest.php`
- `tests/Feature/PublicModels/PublicModelVisibilityRuleTest.php`
- `tests/Feature/PublicModels/PublicModelResponseBoundaryTest.php`
- `tests/Feature/PublicModels/PublicModelSchemaTest.php`
- `specs/007-public-model-profile/validation.md`

## Archivos modificados

- `.gitignore`: ignorar variantes de .env manteniendo .env.example versionable; sin otros ignore nuevos porque no hay herramientas adicionales aplicables y package.json es privado.
- `app/Models/ModelPhotoVersion.php`: cast interno de public_watermarked_at, fuera de fillable.
- `app/Providers/AppServiceProvider.php`: Gate público delegado.
- `routes/web.php`: ruta pública sin autenticación obligatoria.
- `specs/007-public-model-profile/tasks.md`: checks completos y registro del alcance parcial.

`resources/views/welcome.blade.php` tenía cambios anteriores y no fue editado. Los demás documentos 007 ya estaban presentes sin seguimiento al inicio. No hay hooks configurados en `.specify/extensions.yml`; no se ejecutó hook ni commit automático.


## Iteración US2 — 2026-09-29

Alcance: slugs y aliases, backfill y cierre de dependencias de US1. Sin US3–US6 ni Polish. La autoridad pública previa permanece intacta; asignar un slug no exime de email/identidad/review/publicación/principal válida. Disponibilidad unavailable sigue permitida.

### Comportamiento y seguridad

ModelProfileSlugService usa exclusivamente stage_name, ASCII/minúsculas, máximo 160 incluyendo sufijo y prefijo modelo- para bases numéricas. Nombre vacío queda sin slug; nombre no vacío sin base usa modelo. Colisiones deterministas -2/-3; reserva única global actual/histórica y tombstones sin reutilización ajena. No hay lista de palabras reservadas en spec/plan/research: no se agregó una inventada.

La edición privada mantiene stage_name, slug, reservas y cambios privados dentro de una transacción con bloqueo y reintentos acotados de deadlock. Sólo se captura la violación unique específica de reserva. El request prohíbe slug manual y el modelo de reservas está protegido de asignación masiva. No se cambia binding privado ni registro legacy.

Cambiar el nombre cambia el slug; normalización equivalente conserva el actual, incluso sufijado. Volver a un nombre anterior reutiliza su reserva propia. Históricos resuelven directamente al perfil y luego a su canonicalUrl vigente: 302 privado/no-store sin cadenas ni query externa, sólo después de autorización. Slug inexistente/inválido/numérico, reserva ausente/tombstone, canonical inválido o perfil no publicable: 404 genérico sin Location. La URL canónica está en el DTO; no se agregaron metadatos SEO de US6.

Backfill en dos pasadas: reserva existentes antes de generar faltantes, lotes por ID con bloqueo y relectura, idempotente, sin modificar privados ni sobrescribir asignaciones existentes. down de datos conserva URLs; rollback de tabla requiere respaldo para no perder históricos. Se probó únicamente con datos sintéticos; no se aplicó a la base real del proyecto. No se generaron/prepararon fotos de aplicación; las fotos sintéticas existentes sólo permiten comprobar autorización en tests.

### Evidencia ejecutada

| Comprobación | Resultado |
| --- | --- |
| Tests US2 antes de implementación | Fallo esperado por servicio/modelo/esquema faltantes |
| `php artisan test tests/Feature/PublicModels tests/Unit/Services/ModelProfileSlugServiceTest.php` | PASS: 54 tests, 281 assertions |
| `php artisan test` final, ejecución aislada | PASS: 249 tests, 1230 assertions |
| Pint aplicado y `--test` sobre los 14 PHP intervenidos | PASS |
| `git diff --check` | PASS |

La primera ejecución global se solapó con la dirigida y falló un test previo de fotos porque ambas suites compartían Storage::fake. Se repitió la suite completa sin solapamiento: PASS. No se modificó lógica de fotos para resolverlo. El primer fallo de backfill se corrigió recargando el snapshot previo del test, para comparar las mismas columnas persistidas antes/después.

Casos nuevos: normalización, truncado, números, generación, colisiones actuales/históricas, vuelta a alias, tombstone por borrado, constraints, atomicidad/rollback del guardado privado, entrada manual rechazada, backfill 101 perfiles, conservación de datos privados, idempotencia, asignación intercalada entre selección y bloqueo, cadena de cambios sin cadenas HTTP, canonical actual, no-store, formatos inválidos y 404 sin Location para ocultos. La intercalación del test es determinista en SQLite; NO acredita concurrencia real MySQL. T003/T053/T056 permanecen pendientes, como fotos/US3 y validaciones finales. No se ejecutó Pint global fuera del alcance; el fallo preexistente documentado de ViewModelProfile.php no se tocó.

### Tareas de esta iteración

Completadas: **T006, T011, T012, T013, T014, T015, T016, T017, T030, T032, T034** (11).

Parciales: **T004** (falta token UUID), **T007** (falta token de fotos), **T008** (modelo/relación de slugs completos, falta token de fotos), **T035** (falta principal/galería), **T036** (falta integración/preparación US3). Resto pendiente fuera de alcance: T003, T018–T028, T037–T060. No se cerraron tareas de fotos ni Polish.

### Archivos creados en US2

- app/Models/ModelProfileSlug.php
- app/Services/ModelProfileSlugService.php
- database/migrations/2026_09_29_000003_create_model_profile_slugs_table.php
- database/migrations/2026_09_29_000004_backfill_public_profile_slugs.php
- tests/Unit/Services/ModelProfileSlugServiceTest.php
- tests/Feature/PublicModels/PublicModelSlugPersistenceTest.php
- tests/Feature/PublicModels/PublicModelSlugBackfillTest.php
- tests/Feature/PublicModels/PublicModelSlugRoutingTest.php

### Archivos modificados en US2

- app/Models/ModelProfile.php
- app/Services/ModelProfileDetailsService.php
- app/Http/Requests/AccountProfileUpdateRequest.php
- app/Services/PublicModelProfileQuery.php
- app/Http/Controllers/PublicModelProfileController.php
- tests/Support/PublicModelProfileFixtures.php
- specs/007-public-model-profile/tasks.md
- specs/007-public-model-profile/quickstart.md
- specs/007-public-model-profile/validation.md

No se editaron ViewModelProfile.php, welcome.blade.php ni archivos de fotos/estilos durante US2. No se modificaron checklists. Sin .specify/extensions.yml: hooks posteriores no aplican. Sin commit automático.


## Recuperación US3 — 2026-09-30

### Diagnóstico antes de editar

Se ejecutaron git status --short, git diff, revisión de todos los archivos no trackeados de 007, spec/plan/tasks/contracts/data-model y pruebas nuevas/modificadas. El comando siguiente reprodujo exactamente el estado interrumpido:

```bash
php artisan test --filter='PublicModelPhotoDeliveryTest|PreparePublicModelPhotosTest|PublicModelGalleryTest|ModelPhotoImageProcessorTest'
```

Resultado: **15 failed, 1 passed, 2 assertions**. Trece fallos compartían la causa `no such column: public_token` al preparar fixtures; dos correspondían a `Undefined array key public_watermarked`. No eran quince causas independientes. Existían las pruebas y la base de US1/US2, pero faltaban migraciones de token, generación/acreditación, comando de preparación, entrega pública y galería.

Después de resolver esas causas aparecieron dos defectos de pruebas incompletas: el fixture de administradora usaba `role` en vez de `is_admin`, y el test de unicidad replicaba un snapshot cuyo token seguía null después del backfill. Se corrigieron usando la columna existente y recargando la versión. El intento con slash codificado también reveló un 404 del router sin no-store: la ruta de fotos ahora recibe el token completo y el servicio exige UUID antes de consultar, conservando error genérico/no-store incluso para formatos inválidos. Ninguna entrada arbitraria llega a almacenamiento.

### Implementación y límites

- Migraciones **000005/000006** extienden el esquema sin modificar las cuatro existentes. UUID v4 por versión nueva, unique y fuera de fillable. Backfill idempotente por lotes con actualización condicional; no acredita watermark ni lee imágenes, no modifica timestamps previos.
- Se reutiliza ModelPhotoImageProcessor de 005: extrae generación de public para que upload y preparación compartan watermark/encoding. Sólo acredita cuando la marca se aplicó y alteró el derivado; marca deshabilitada/invisible no acredita. ModelPhotoService persiste evidencia sólo después del guardado de variantes, dentro de la transacción existente. No cambia moderación, límites, principal ni eliminación de versiones retiradas.
- `model-photos:prepare-public` considera exclusivamente versiones approved/current propias sin evidencia. Dry-run sólo cuenta. Regenera public desde processed, valida WebP, guarda en ruta nueva, revalida vigencia/ownership/estado/fuente/evidencia bajo bloqueo y actualiza sólo path/evidencia. Limpia el derivado anterior después del commit; ante carrera descarta únicamente el nuevo recurso. Fuente ausente, watermark deshabilitado o error de escritura dejan evidencia null y salida 1. Los logs usan códigos operativos sin paths ni contenidos privados.
- PublicModelPhotoService y el controlador entregan sólo public.webp mediante token opaco. Gate y autoridad central verifican elegibilidad vigente sin bypass de sesión. Se comprueba current_version_id, propiedad, approved, token y evidencia. Rutas privadas/processed/thumbnail/original nunca se sirven como fallback.
- Stream público seguro, legible y no vacío abierto antes de headers y cerrado al terminar. Content-Type WebP, inline photo.webp, nosniff y private/no-store; sin ETag ni redirect a storage. Tokens retirados/de fotos borradas o perfiles ocultos dejan de funcionar en la siguiente solicitud.
- Query carga fotos aprobadas vigentes y sus versiones en lote, con columnas explícitas, sin historial ni variantes privadas. DTO añade primaryPhoto/photos como arrays públicos (url/alt/position/width/height). La principal se destaca y aparece también en la galería ordenada por position/id. Secundarias inválidas se omiten; principal inválida causa 404. Secundarias lazy, principal eager. El límite continúa siendo el central de 005; no se introduce otro límite de presentación.
- Se preservan reemplazos pending/rejected y reasignación al eliminar principal mediante los servicios existentes de 005. Tests verifican ausencia de cambios de publicación/moderación por lectura o preparación.
- No se implementaron US4–US6 ni Polish; no hubo commit, reset ni limpieza del working tree. Los cambios previos ajenos al bloque, incluida welcome.blade.php, se conservaron.

### Verificación ejecutada en el orden solicitado

| Paso | Resultado |
| --- | --- |
| 1. Tests US3 + dependencia de esquema | **27 passed, 211 assertions** |
| 2. Fotos relevantes de 005 (incluye procesador ampliado) | **44 passed, 241 assertions** |
| 3. Regresión US1/US2 + esquema | **55 passed, 300 assertions** |
| 4. Suite completa | **271 passed, 1439 assertions** |
| 5. Pint aplicado + --test en 21 archivos PHP intervenidos | **PASS** |
| Build de assets tras regla CSS de galería | **PASS** |
| git diff --check | **PASS** |

Las suites se ejecutaron secuencialmente, sin competir por Storage::fake. PHP/SQLite en memoria y archivos sintéticos, no MySQL ni datos reales. La comparación HTTP de 1 frente a 5 fotos tiene igual número de consultas con lazy loading bloqueado; verifica desempate por ID y ausencia de originales, thumbnails e historial en SQL. No equivale a cerrar la medición de servicios/EXPLAIN de T051/T052.

Comandos de los pasos 1–4:

```bash
php artisan test --compact --filter='PublicModelPhotoDeliveryTest|PreparePublicModelPhotosTest|PublicModelGalleryTest|ModelPhotoImageProcessorTest|PublicModelSchemaTest'
php artisan test --compact tests/Feature/Account/ModelPhotoUploadTest.php tests/Feature/Account/ModelPhotoReplacementTest.php tests/Feature/Account/ModelPhotoPrimaryTest.php tests/Feature/Account/ModelPhotoReorderTest.php tests/Feature/Account/ModelPhotoDeletionTest.php tests/Feature/Admin/ModelPhotoModerationTest.php tests/Feature/ModelPhotos/ModelPhotoSchemaTest.php tests/Feature/Storage/ModelPhotoStorageTest.php tests/Feature/Security/ModelPhotoAuthorizationTest.php tests/Feature/Security/ModelPhotoPrivacyTest.php tests/Unit/Services/ModelPhotoImageProcessorTest.php
php artisan test --compact tests/Feature/PublicModels/PublicModelProfileVisibilityTest.php tests/Feature/PublicModels/PublicModelVisibilityRuleTest.php tests/Feature/PublicModels/PublicModelResponseBoundaryTest.php tests/Feature/PublicModels/PublicModelSlugRoutingTest.php tests/Feature/PublicModels/PublicModelSlugPersistenceTest.php tests/Feature/PublicModels/PublicModelSlugBackfillTest.php tests/Feature/PublicModels/PublicModelSchemaTest.php tests/Unit/Services/ModelProfileSlugServiceTest.php
php artisan test --compact
```

Pint se limitó a ModelPhotoVersion, ModelPhotoImageProcessor, ModelPhotoService, ModelPhotoStorage, PublicModelPhotoService, PublicModelProfileQuery, PublicModelProfileVisibility, PublicModelPhotoController, PreparePublicModelPhotos, PublicModelProfileViewModel, routes/web.php, migraciones 000005/000006, PreparePublicModelPhotosTest, PublicModelGalleryTest, PublicModelPhotoDeliveryTest, PublicModelSchemaTest, PublicModelResponseBoundaryTest, PublicModelVisibilityRuleTest, ModelPhotoImageProcessorTest y PublicModelProfileFixtures. No se aplicó Pint global fuera del alcance.

### Revisión visual acotada de T027

Se renderizó el componente Blade real con el CSS actual, cinco imágenes sintéticas numeradas y principal en posición 3, en HTML temporal sin base de datos. Chrome headless con emulación de viewport confirmó scrollWidth igual a innerWidth: **360/360, 768/768 y 1440/1440**. Principal destacada, repetición correcta en posición, orden y recortes sin deformación; móvil apilado, tablet/escritorio en columnas. La regla min-width:0 queda acotada a las tarjetas/grilla públicas. Las primeras capturas por tamaño de ventana no eran evidencia válida de viewport móvil en macOS; las capturas finales usan DevTools para fijar dimensiones reales.

Capturas revisadas: [360 px](validation/us3-gallery-360.png), [768 px](validation/us3-gallery-768.png), [1440 px](validation/us3-gallery-1440.png). No se presenta esto como revisión final de perfil completo, SEO, teclado/contraste integral ni prueba humana de US6/Polish. No se alteraron pantallas privadas.

### Tareas y operación

Completadas **T018–T028** y dependencias **T004/T007/T008**. **Ninguna tarea parcial de US3**. T035/T036 conservan sus checks de cierre pendientes de US1/rollout aunque su dependencia de fotos quedó resuelta. T003 y T037–T060 siguen pendientes y fuera de alcance.

No se ejecutó preparación ni migración sobre la base real. Antes de habilitar datos existentes hay que aplicar migraciones y comprobar dry-run/preparación en el entorno autorizado; una principal sin evidencia sigue 404. MySQL concurrente, rollout/rollback real y validación final de feature continúan diferidos a sus tareas existentes. No hay hooks configurados; no se hizo commit.

### Git status final

[Salida completa de git status --short](validation/git-status-us3.txt). Se mantienen los cambios previos y los de US3 sin staging ni commit; no hay archivos eliminados. El resumen contiene 13 archivos trackeados modificados y 22 entradas sin seguimiento (algunas representan directorios).

## Cierre acotado 2026-09-30 — US4

| Validación | Resultado |
| --- | --- |
| Tests US4 (`PublicModelPrivacyTest`, `PublicModelApprovedContentTest`, `PublicModelAgeLocationTest`) después de Pint | PASS: 15 tests, 286 assertions |
| Regresión de fundamentos y US1–US3 | PASS: 78 tests, 499 assertions |
| Suite completa (`php artisan test`) | PASS: 286 tests, 1725 assertions |
| Pint sobre PHP modificados/nuevos; `vendor/bin/pint --test` sobre el mismo conjunto | PASS; Pint formateó `PublicModelProfileQuery.php` y `PublicModelProfileViewModel.php` |

Completadas **T037–T042**. No se marcaron tareas de US1, US5, US6, performance, hardening ni Polish. La validación MySQL aislada, build frontend y validaciones finales permanecen pendientes según sus tareas; no se ejecutaron ni se consideran parte de este cierre de US4.

## Cierre acotado 2026-09-30 — US5

| Validación | Resultado |
| --- | --- |
| Baseline antes de cambios (PublicModels + compatibilidad/tipo/servicios existentes) | PASS: 97 tests, 816 assertions |
| Tests específicos US5 (`PublicModelServicesTest`) después de Pint | PASS: 5 tests, 67 assertions |
| Regresión US1–US4 y flujos privados relacionados | PASS: 131 tests, 1014 assertions |
| Suite completa (`php artisan test`) | PASS: 291 tests, 1811 assertions |
| Pint y `vendor/bin/pint --test` en los PHP intervenidos | PASS; formateó `PublicModelProfileViewModel.php` y `PublicModelServicesTest.php` |
| `git diff --check` | PASS |

### Cobertura automatizada de los 12 puntos MVP

| Punto | Cobertura ejecutada | Resultado |
| --- | --- | --- |
| 1. Slug seguro | `PublicModelSlugPersistenceTest`, `PublicModelSlugBackfillTest`, `ModelProfileSlugServiceTest` | PASS |
| 2. Regla central de visibilidad | `PublicModelVisibilityRuleTest`, `PublicModelProfileVisibilityTest` | PASS |
| 3. GET por slug | `PublicModelSlugRoutingTest`, `PublicModelResponseBoundaryTest` | PASS |
| 4. Datos aprobados | `PublicModelApprovedContentTest`, `PublicModelAgeLocationTest` | PASS |
| 5. Foto principal pública | `PublicModelPhotoDeliveryTest`, `PublicModelGalleryTest` | PASS |
| 6. Galería aprobada | `PublicModelGalleryTest` | PASS |
| 7. Disponibilidad | `PublicModelProfileVisibilityTest` | PASS |
| 8. Ubicación textual | `PublicModelAgeLocationTest`, `ProfileLocationRegressionTest`, `ProfileLocationTest` | PASS |
| 9. Bio vigente | `PublicModelApprovedContentTest` | PASS |
| 10. Tipo y servicios | `PublicModelServicesTest` | PASS |
| 11. Privacidad y 404 | `PublicModelPrivacyTest`, `PublicModelResponseBoundaryTest` | PASS |
| 12. Pruebas/regresión | Regresión indicada arriba y suite completa | PASS |

La matriz registra cobertura automatizada, no certifica validación visual/humana, MySQL aislado, rollout ni build frontend. Se completaron únicamente **T043–T045**; las tareas de US1–US4 no se modificaron en este cierre y no se avanzó US6 ni Polish.

## Cierre acotado 2026-10-01 — US6

| Validación | Resultado |
| --- | --- |
| Baseline `tests/Feature/PublicModels` | PASS: 87 tests, 843 assertions |
| Tests US6, galería y frontera de privacidad | PASS: 15 tests, 271 assertions |
| Regresión US1–US5 y flujos relacionados | PASS: 136 tests, 1056 assertions |
| Suite completa (`php artisan test`) | PASS: 296 tests, 1853 assertions |
| `npm run build` y `php artisan view:cache` | PASS |
| Pint y `vendor/bin/pint --test` sobre PHP intervenidos | PASS |
| `git diff --check` | PASS antes de esta anotación documental; repetir al cerrar |

La comprobación responsive usó un DOM sintético con la misma estructura/clases de Blade y el CSS compilado: `documentWidth` coincidió con el viewport a 360, 768 y 1440 px; el perfil apila contenido en 360 y usa columnas en 768/1440. La galería conserva `ul/li`, grilla y recorte; los estilos de foco y estados tienen contraste calculado de 3,38:1 (foco), 4,51:1 (insignia) y 6,49:1 (disponibilidad). No se afirma revisión humana completa ni SC-008.

La ruta real del perfil no pudo revisarse visualmente desde el servidor local: respondió 500 porque la base MySQL `divas_cuyo` no tiene `model_profile_slugs`. No se ejecutaron migraciones sobre esa base. Se completaron **T046–T050**; **T057–T060** permanecen pendientes, y no se avanzó a Polish.

## Cierre operativo acotado 2026-10-01 — T003 y US1

| Validación | Resultado |
| --- | --- |
| `vendor/bin/phpunit --configuration phpunit.mysql.xml` (guardas del harness, sin conexión) | PASS: 3 tests, 3 assertions |
| T035–T036: visibilidad, response boundary, slugs/rutas, entrega/galería, privacidad y contenido aprobado | PASS: 56 tests, 739 assertions |
| T036: persistencia/backfill/schema/visibilidad de slugs | PASS: 32 tests, 82 assertions |

T003 y T035–T036 completadas para la configuración, vistas y comportamiento HTTP probado en SQLite. El harness MySQL exige las cinco variables `PUBLIC_PROFILE_MYSQL_TEST_*`, base con sufijo `_test`, y no hereda `RefreshDatabase`. No hubo migración, conexión ni rollout sobre MySQL local; la verificación de backfills y operación real permanece pendiente en T056.

## Cierre operativo parcial 2026-10-01 — performance y hardening

| Validación | Resultado |
| --- | --- |
| T051 `PublicModelQueryBudgetTest` (1/5 fotos, 1/20 servicios, lazy loading bloqueado) | PASS: 1 test, 3 assertions; conteo de consultas igual entre fixtures |
| T054 `PublicModelRevocationTest` | PASS: 1 test, 14 assertions |
| T055 preparación + entrega de fotos | PASS: 13 tests, 95 assertions; evento seguro y stream cerrado ante error |

T051, T054 y T055 completadas. T052 queda pendiente de tiempos/EXPLAIN en MySQL; el Blade ya conserva eager en principal, lazy en secundarias y dimensiones explícitas. T053 y T056 no se ejecutaron: `PUBLIC_PROFILE_MYSQL_TEST_HOST/PORT/DATABASE/USERNAME/PASSWORD` están unset y no existe configuración de integración previa. No se reutilizó ni modificó `divas_cuyo`; el harness probado rechaza nombres sin sufijo `_test`.

## Cierre acotado 2026-10-01 — T052, T053 y T056 (MySQL)

Guarda repetida antes de conectar: `PUBLIC_PROFILE_MYSQL_TEST_DATABASE=divas_cuyo_test`, sufijo exacto `_test`; cinco variables externas presentes. `SELECT DATABASE()` confirmó el mismo nombre y `SHOW TABLES` confirmó una base vacía. Servidor MySQL **5.7.39**, PHP **8.2.33**. No se conectó a `divas_cuyo` ni a producción durante este cierre.

El harness ahora rechaza configuración Laravel cacheada, elimina URL/socket alternativos, fuerza el entorno testing y comprueba la base efectiva. `OwnedMySqlSchema` exige cero tablas antes de cada caso, sin `RefreshDatabase` ni transacción externa. Crea su esquema mediante `migrate` y revierte únicamente ese esquema al terminar; no usa `migrate:fresh`. No ejecutar esta suite simultáneamente en la misma base.

### T052 — solicitudes, tiempos y EXPLAIN

`PublicModelPerformanceTest` ejecuta HTTP por el kernel Laravel con MySQL y almacenamiento de imágenes sintéticas aislado. Inspecciona el HTML: principal eager/high, secundarias lazy y dimensiones positivas; conserva el Blade existente porque ya cumple. Solicita cada URL de imagen única y compara sus bytes con la variante pública `public.webp`, verifica Content-Type WebP y ausencia del thumbnail privado en HTML.

| Fixture | Consultas del perfil | HTTP perfil | SQL acumulado | GET de imágenes únicas | HTTP imágenes |
| --- | --- | --- | --- | --- | --- |
| 1 foto / 1 servicio | 11 | 6,86 ms | 2,08 ms | 1 | 2,66 ms |
| 5 fotos / 20 servicios | 11 | 5,49 ms | 1,76 ms | 5 | 2,03 / 1,70 / 1,66 / 1,68 / 1,61 ms |

Mediciones observadas en la ejecución final, sin umbral SLA. Fixtures con ubicación y tipo, sin bio. Lazy loading Eloquent bloqueado. Son solicitudes HTTP de prueba, no un waterfall de navegador: el test solicita cada URL única una vez, no acredita la deduplicación del navegador ni el momento de descarga al hacer scroll. La principal también aparece en la grilla con la misma URL.

EXPLAIN ejecutado sobre **cada SELECT real** del perfil de 5 fotos; la suite imprime SQL y plan completo. Resumen de los planes observados:

| Consulta/relación | type / índice elegido | rows / observación |
| --- | --- | --- |
| Reserva por slug | const / `model_profile_slugs_slug_unique` | 1 |
| Perfil por ID y visibilidad (consulta y Gate) | const / PRIMARY | 1 |
| Usuario verificado | const / PRIMARY | 1 |
| Principal por perfil | ref / `model_photos_model_profile_id_is_primary_index` | 1; filesort en carga ordenada |
| Versión vigente en subconsulta | eq_ref / PRIMARY | 1 |
| Tipo, provincia, localidad | const / PRIMARY | 1 cada una |
| Pivot servicios | ref / PRIMARY | 20; temporary/filesort |
| Servicio por ID | eq_ref / PRIMARY | 1 |
| Galería por perfil | ALL / ninguno | 6; where/filesort, índice perfil/posición disponible |
| Versiones por lista de IDs | ALL / ninguno | 6; PRIMARY disponible |
| Versión principal por ID | const / PRIMARY | 1 |

No se agregó ningún índice: el acceso por slug ya es único; los scans de fotos/versiones corresponden a una tabla de seis filas, insuficiente para justificar otro índice. La ordenación de veinte servicios tampoco justifica alterar el esquema con esta evidencia. No se extrapola el plan a volumen de producción.

### T053 — concurrencia y atomicidad

`PublicModelSlugConcurrencyTest` usa procesos PHP separados, una barrera de inicio y conexiones MySQL con IDs distintos. Comprueba commits visibles desde la conexión principal: dos perfiles compiten por Mia (`mia`, `mia-2`); tras renombrar su propietario, otros dos obtienen `mia-3`/`mia-4` sin tomar el alias reservado. Dos renombrados del mismo perfil esperan un bloqueo real y conservan ambos nombres como reservas con nombre/slug final coherentes.

También verifica rollback externo, rollback automático ante excepción después de reservar y antes de guardar, y bloqueo persistente con `innodb_lock_wait_timeout=1`: exactamente tres intentos, error y ningún cambio parcial. El borrado deja tombstones; un nuevo perfil obtiene `mia-5`. Sin cambios al servicio de producción.

### T056 — migraciones y preparación

- Esquema vacío: 36 migraciones forward, cero perfiles; rollback del esquema creado por el caso.
- Esquema 006: 101 perfiles sintéticos homónimos → `mia` hasta `mia-101`; datos privados intactos. Una versión de foto legacy conserva todos sus campos, recibe token y continúa sin certificación automática de watermark.
- Repetición de `migrate`, backfill de slugs y tokens: sin reasignación; `down()` del backfill conserva reservas.
- Un perfil/foto adicional: antes de preparar devuelve 404; dry-run cuenta 1 sin escribir DB/archivos; real prepara 1 y falla 0; HTTP después devuelve 200; repetición prepara 0. Original y processed conservados byte a byte.
- Respaldo sintético antes del rollback: **102 slugs de perfiles y 104 reservas**, incluidos alias y tombstone, en `rollback-reservations.json` del disco fake. Reversión de las seis migraciones 007 conserva 102 perfiles, datos privados y original. Forward posterior no reconstruye el tombstone: se restaura el respaldo y se compara exactamente con reservas/slugs previos. El respaldo es un artefacto temporal de prueba, no un respaldo operativo de datos reales.

### Resultado

`vendor/bin/phpunit --configuration phpunit.mysql.xml`: **PASS, 7 tests / 188 assertions**, 16,328 s. Incluye las tres guardas, dos casos de migración, performance y concurrencia. Pint sobre `tests/Integration` y el worker: PASS; `git diff --check`: PASS. El primer intento del worker requirió corregir su barrera de sincronización y el fixture privado requirió releer la fecha desde MySQL antes de comparar; los resultados anteriores corresponden a la ejecución posterior completa.

Completadas exclusivamente **T052, T053 y T056**. T057–T060 siguen pendientes. No se ejecutó suite general SQLite, rollout, build, validación visual/humana ni commit.

Verificación final independiente de solo lectura: `SELECT DATABASE()` confirmó nuevamente `divas_cuyo_test`; `SHOW TABLES` devolvió `[]`. La base aislada quedó vacía después de las pruebas.

## Revisión visual final acotada — T057 — 2026-10-01

**Resultado: satisfecha en el alcance revisado.** Inspección visual del agente sobre capturas de Chrome **154.0.8037.59** y navegación con eventos reales de teclado enviados al navegador mediante CDP. No se declara una sesión con participantes humanos ni una prueba de dispositivos físicos. Esta revisión incluye observación de las imágenes renderizadas, además de las mediciones; no se cierra por el mero resultado de un script.

### Entorno y evidencia

Se repitió la guarda `_test` antes de preparar datos. Exclusivamente `PUBLIC_PROFILE_MYSQL_TEST_*` → `divas_cuyo_test`, inicialmente vacía, con el harness que comprueba la base efectiva. Servidor temporal en `127.0.0.1:8077`, rutas `/modelos/revision-completa`, `/modelos/revision-minimo`, `/modelos/revision-textos-largos` y `/modelos/revision-inexistente`. Se utilizaron controladores, consultas, Blade y entrega de imágenes reales del proyecto; no se reconstruyó el DOM. Imágenes ilustrativas sintéticas, sin fotografías personales.

CSS servido por el manifiesto existente: `styles-TZYj2j1_.css`. No se ejecutó build ni se alteraron los estilos para las capturas. Viewports de **360×900, 768×900 y 1440×900**, escala 1. Las capturas completas incluyen el contenido más allá del viewport.

Evidencia persistida: **72 capturas**, [índice visual](evidence/t057/README.md), [mediciones/recursos/foco/accesibilidad](evidence/t057/measurements.json), [teclado y scroll](evidence/t057/keyboard-scroll.json) y [SHA-256 de vistas/CSS](evidence/t057/provenance.json).

| Escenario inspeccionado | 360 px | 768 px | 1440 px | Resultado |
| --- | --- | --- | --- | --- |
| Completo: disponible, 5 fotos, ubicación, 9 características, modalidad, ambos grupos de servicios, bio con párrafos | [Captura](evidence/t057/completa-360.png) | [Captura](evidence/t057/completa-768.png) | [Captura](evidence/t057/completa-1440.png) | PASS |
| Mínimo: no disponible, una foto, sin ubicación/características/modalidad/servicios/bio | [Captura](evidence/t057/minimo-360.png) | [Captura](evidence/t057/minimo-768.png) | [Captura](evidence/t057/minimo-1440.png) | PASS |
| Textos largos: nombre multilínea, ubicación y servicio largos, bio extensa y segmento sin espacios | [Captura](evidence/t057/textos-largos-360.png) | [Captura](evidence/t057/textos-largos-768.png) | [Captura](evidence/t057/textos-largos-1440.png) | PASS |
| 404 real, sin datos de perfil | [Captura](evidence/t057/inexistente-360.png) | [Captura](evidence/t057/inexistente-768.png) | [Captura](evidence/t057/inexistente-1440.png) | PASS |

### Observaciones visuales

- **Jerarquía y estados:** marca, verificación, nombre y disponibilidad identificables. A 360 px se apilan sin superposición; 768/1440 usan dos columnas para galería/detalles. El nombre largo y “No disponible” pueden ocupar varias líneas sin recortarse ni solaparse.
- **Galería:** imágenes cargadas, proporciones y recorte consistentes, principal destacada en escritorio. La principal se repite dentro de la galería como ya estaba implementado. En móvil las fotos se apilan y generan una página larga, pero el contenido posterior permanece accesible. Sin cambios de alcance para introducir zoom, carrusel o rediseño.
- **Lectura:** características con etiqueta/valor claros; servicios agrupados y listas legibles; bio con párrafos y saltos de línea conservados. El segmento sin espacios se ajusta al ancho. Se inspeccionó también el pie a tamaño de viewport, no sólo la captura completa reducida: [servicios/bio a 360](evidence/t057/completa-360-keyboard-end.png), [bio extensa a 360](evidence/t057/textos-largos-360-keyboard-end.png), [bio extensa a 768](evidence/t057/textos-largos-768-keyboard-end.png).
- **Opcionales ausentes:** el mínimo muestra únicamente nombre/estados y Fotografías; no quedan títulos o tarjetas vacías para bio, ubicación, físicos, modalidad ni servicios. En escritorio queda espacio libre junto a la galería, sin elementos vacíos visibles. El perfil largo también comprueba omisión parcial de características y de servicios presenciales.
- **Overflow:** `scrollWidth` igual a 360/768/1440 en las doce combinaciones; ningún descendiente de main sobrepasa horizontalmente el viewport. Sin texto truncado ni solapamientos observados.
- **Carga:** los nueve perfiles respondieron 200 y las tres vistas inexistentes 404. Todas las imágenes renderizadas terminaron cargadas; cero excepciones JavaScript registradas. Recursos CSS/imágenes obtenidos desde el servidor temporal.

### Teclado, orden de foco y contraste

En las doce combinaciones se envió **Tab → Tab → Shift+Tab → Enter**: enlace “Saltar al contenido principal” → marca/enlace al inicio → enlace de salto → `main#contenido-principal`. El orden coincide con el DOM, sin tabindex positivos ni paradas artificiales sobre textos o imágenes no interactivas. El árbol de accesibilidad conserva enlaces con nombre, main, encabezados e imágenes con texto alternativo.

El enlace de salto aparece al enfocarse, completo y dentro del viewport. Ambos enlaces muestran contorno de **3 px** con separación de **4 px**. Enter mueve efectivamente el foco al main y Chrome dibuja su indicador nativo. Evidencia inspeccionada: [primer foco a 360](evidence/t057/completa-360-focus-skip.png), [marca a 768](evidence/t057/completa-768-focus-brand.png), [main a 1440](evidence/t057/completa-1440-skip-target.png).

Adicionalmente, en completo/textos largos a los tres anchos, **End** llegó al pie (`atBottom=true`); **Shift+Tab** retornó a la marca y el scroll volvió a 0 con foco visible. Tab posterior permitió continuar/reingresar al recorrido, sin trampa. Se esperó a que finalizara la animación de scroll suave antes de medir la posición final; no se confundieron coordenadas transitorias con un defecto. [Retorno visible a 360](evidence/t057/completa-360-focus-return.png).

Contrastes calculados a partir de colores efectivos del navegador, sobre sus fondos:

| Elemento | Ratio observado |
| --- | --- |
| Texto principal sobre blanco | 16,41:1 |
| Bio sobre fondo gris | 14,65:1 |
| Etiquetas y mensaje 404 | 4,83:1 |
| Marca | 4,84:1 |
| Insignia de verificación (mínimo del texto revisado) | 4,51:1 |
| Disponible | 6,49:1 |
| Contorno de foco sobre fondo de página / blanco | 3,38:1 / 3,62:1 |

Los contrastes de texto revisados superan 4,5:1 y el indicador personalizado supera 3:1. Las ilustraciones de prueba no se evalúan como texto de interfaz. Esto no constituye una auditoría exhaustiva de accesibilidad ni una prueba con lector de pantalla.

### Cierre de alcance

No se identificó un defecto visual o funcional que requiriera modificación. Se conservaron vistas, estilos y funcionalidad. La revisión está satisfecha para los cuatro escenarios y los tres anchos indicados en Chrome; se marca **sólo T057** en este cierre. T058, T059 y T060 permanecen pendientes; no se ejecutaron estudio humano, suite general, build ni recorrido de cierre global. Sin commit. Servidor temporal y Chrome detenidos al finalizar; el harness retira exclusivamente el esquema/fixtures que creó en la base aislada.

Limpieza confirmada por consulta independiente: `SELECT DATABASE()` → `divas_cuyo_test`, `SHOW TABLES` → `[]`. El harness temporal de fixtures terminó correctamente (1 caso, 3 aserciones de preparación/finalización, no una suite de regresión). Las 72 capturas y sus enlaces se verificaron; los hashes de vistas/CSS coinciden con los revisados. `git diff --check`: PASS.

## Preparación exclusiva de T058 — 2026-10-01

**Estado: preparada, no ejecutada. T058 permanece pendiente.** Se creó la [guía de sesión y plantilla para cinco participantes](t058-session-guide.md), con guion breve, cuatro tareas, preguntas posteriores, reglas de cronometraje, criterios de éxito y evidencia que se registrará aquí cuando se ejecuten sesiones reales.

SC-008 se evaluará por persona: nombre artístico, disponibilidad y modalidad correctos en menos de 30 segundos, sin ayuda ni login; se requieren al menos cuatro éxitos entre cinco sesiones válidas. Las tareas adicionales son diagnósticas y no sustituyen ese criterio.

No se contactó a participantes ni se ejecutó ninguna sesión en esta preparación. No hay respuestas, tiempos ni resultados humanos que informar. No se conectó a bases, recreó el entorno visual ni modificó funcionalidad. T058, T059 y T060 siguen sin marcar; no se avanzó a T059/T060 ni se hizo commit.


## Decisión de alcance para el cierre técnico — 2026-10-01

Por instrucción explícita del usuario, **T058 se posterga y no es requerida para el cierre técnico del MVP**. Motivo declarado: concluir técnicamente 007 en esta etapa sin realizar el estudio con participantes. No se presume falta de personas, no se inventan sesiones, respuestas, tiempos ni resultados. **SC-008 permanece no evaluado**, con su definición y guía conservadas para una etapa futura sin fecha comprometida.

Esta decisión sustituye la dependencia anterior que hacía obligatoria T058 para T060. Su casilla permanece sin marcar y rotulada POSTERGADA, fuera del alcance actual; no representa una tarea técnica obligatoria pendiente. Las notas previas de preparación/pending se conservan como historia. Los requisitos FR-001–031 y las verificaciones técnicas T059/T060 siguen vigentes. El cierre sólo se declarará tras registrar sus resultados reales. Sin funcionalidades nuevas ni commit automático.

## Cierre técnico del MVP — T059 y T060 — 2026-10-01

**Conclusión: 007-public-model-profile puede declararse cerrada técnicamente en el alcance vigente, con T058 explícitamente postergada y SC-008 no evaluado.** No hay tareas técnicas obligatorias pendientes. Esta conclusión no declara validación con participantes ni despliegue en un entorno real.

### T059 — regresión final ejecutada

| Control | Resultado y evidencia |
| --- | --- |
| `php artisan test` (entorno testing, SQLite `:memory:` explícito) | **PASS: 299 tests, 1876 assertions**, 10,94 s. [Log completo](evidence/closure/php-artisan-test.txt). |
| `vendor/bin/phpunit --configuration phpunit.mysql.xml` | **PASS: 7 tests, 188 assertions**, 16,504 s. MySQL 5.7.39, exclusivamente `divas_cuyo_test`. [Log con concurrencia, migraciones, tiempos y EXPLAIN](evidence/closure/mysql-integration.txt). |
| `vendor/bin/pint --test`, primera ejecución | Detectó únicamente `fully_qualified_strict_types` en `app/Filament/Resources/ModelProfiles/Pages/ViewModelProfile.php`. [Resultado inicial](evidence/closure/pint-initial.txt). |
| Corrección acotada y nuevo Pint global | **PASS**. Sólo se importó `Illuminate\Contracts\Support\Htmlable` y se usó el nombre importado en la firma; mismo tipo y comportamiento. [Resultado final](evidence/closure/pint-final.txt). |
| Regresión administrativa posterior al ajuste de formato | **PASS: 49 tests, 264 assertions**, 2,17 s. [Log](evidence/closure/admin-after-format.txt). |
| `npm run build` | **PASS**, Vite 7.3.6, 59 módulos; sin cambios de vistas/CSS. [Log](evidence/closure/vite-build.txt). |
| Vigencia de la revisión visual T057 | Vistas, galería, CSS fuente y CSS compilado coinciden por SHA-256 con lo observado en T057. [Comprobación](evidence/closure/visual-provenance-check.json). No se inventa una nueva sesión visual. |
| Seguridad y limpieza de MySQL | Guarda `_test` repetida antes de la integración; harness rechaza tablas preexistentes y verifica base efectiva. Consulta independiente posterior: `SELECT DATABASE()` → `divas_cuyo_test`, `SHOW TABLES` → `[]`. Sin conexiones a `divas_cuyo` ni producción. |

La suite general incluye Auth (registro/login/logout/verificación/reset), Account (`/account`, `/account/profile`, fotos), Identity, Profile (físicos/bio/edad/disponibilidad/ubicación/tipo/servicios), Admin/Filament, Security y Storage, además de PublicModels y unitarias. El log identifica cada clase/caso ejecutado. La regresión MySQL se ejecutó después de la suite general, sin competir por Storage fake. No se instalaron dependencias ni se agregó funcionalidad.

### T060 — recorrido de los diez escenarios del quickstart

Se conciliaron las consignas del quickstart con casos HTTP y servicios reales **ejecutados por la suite de T059**, más integración MySQL y evidencia visual T057. Esta tabla registra un recorrido automatizado de los estados funcionales; no afirma una sesión humana ni un recorrido único de punta a punta por formularios en un navegador.

| Escenario | Evidencia ejecutada | Resultado |
| --- | --- | --- |
| 1. Perfil elegible y cambio a No disponible | `PublicModelProfileVisibilityTest`, `PublicModelApprovedContentTest`, `PublicModelServicesTest`, `PublicModelGalleryTest` | 200, datos públicos, disponibilidad informativa y publicación conservada. PASS. |
| 2. Guardado privado y cadena Mia→Luna→Sol→Mia | `PublicModelSlugPersistenceTest::test_private_profile_update_generates_slug_and_rejects_manual_assignment` y `PublicModelSlugRoutingTest::test_aliases_redirect_directly_to_current_canonical_and_returning_to_old_name_has_no_loop` | PATCH privado actualiza slug; aliases 302 directos/canonical y vuelta sin bucles. Los dos tramos se prueban en casos separados. PASS. |
| 3. Despublicación, alias, imagen e ID; sesión sin bypass | `PublicModelRevocationTest`, `PublicModelSlugRoutingTest`, `PublicModelResponseBoundaryTest`, `PublicModelProfileVisibilityTest::test_session_never_bypasses_public_rules` | 404 genérico, sin Location/ETag indebidos, no-store; propietaria/admin tampoco ven perfil oculto. PASS. |
| 4. Cada condición de elegibilidad | `PublicModelProfileVisibilityTest`, `PublicModelVisibilityRuleTest`, `PublicModelPhotoDeliveryTest` | Email, identidad/revisión, publicación y principal; 404 al faltar cada condición. PASS. |
| 5. Reemplazos de bio/foto y eliminación de principal | `PublicModelApprovedContentTest`, `PublicModelGalleryTest`, regresión `Account/ModelPhoto*` y `Admin/ModelPhotoModerationTest` | Aprobado vigente hasta promoción; URL anterior retirada; fallback o 404 sin despublicación implícita. PASS. |
| 6. Faltantes, rutas inseguras, watermark y secundaria inválida | `PublicModelVisibilityRuleTest`, `PublicModelPhotoDeliveryTest`, `PublicModelGalleryTest`, `PreparePublicModelPhotosTest` | Fallo cerrado sin original/thumbnail ni excepción privada; secundaria inválida omitida. PASS. |
| 7. Edad pública/oculta y privacidad integral | `PublicModelAgeLocationTest`, `PublicModelPrivacyTest`, `PublicModelResponseBoundaryTest`, `PublicModelSeoTest` | HTML, metadata, DTO, recursos y consultas sin datos privados/sentinelas. PASS. |
| 8. Físicos moderados, bio ausente o ajena | `PublicModelApprovedContentTest`, regresión de moderación física/bio y perfil privado | Sólo canónicos aprobados y bio vigente propia; vacíos omitidos. PASS. |
| 9. Tipo, servicios, actividad y localidad | `PublicModelServicesTest`, `PublicModelAgeLocationTest`, `PublicModelResponseBoundaryTest`, `Profile/ServiceCompatibilityTest` | Virtual excluye presencial; Encuentros agrupa/ordena; inactive desaparece; localidad incompatible omitida. PASS. |
| 10. Consultas, variantes, tiempos y EXPLAIN | `PublicModelQueryBudgetTest`, `PublicModelGalleryTest`, `PublicModelPerformanceTest` | 11 consultas con 1/5 fotos y 1/20 servicios; lazy loading bloqueado; variantes públicas y planes reales en log MySQL. PASS, sin extrapolar tiempos a producción. |

Además, preparación/forward/backfill/repetición/rollback del quickstart se reejecutaron mediante `PublicModelMigrationTest`: 36 migraciones desde vacío, 101 perfiles legacy, una foto legacy, perfil de preparación adicional, dry-run 1/real 1/repetición 0, originales conservados y 104 reservas respaldadas/restauradas. La revisión de completo/mínimo/largos/404, 360/768/1440, teclado, foco y contraste está en T057 con 72 capturas. El único escenario humano del quickstart, SC-008/T058, queda excluido por la decisión de alcance documentada, sin sustituirlo por estos tests.

### Conciliación de FR-001–031

Los estados PASS siguientes corresponden a los fixtures y verificaciones ejecutados, no a una certificación sobre datos de producción.

| Requisitos | Implementación/evidencia | Estado |
| --- | --- | --- |
| FR-001–003 | Ruta por slug; persistencia, normalización, aliases y tombstones; `PublicModelSlug*`, unidad `ModelProfileSlugServiceTest`, concurrencia/backfill MySQL | PASS |
| FR-004–008 | `PublicModelProfileVisibility`, Gate, consulta pública; matriz de estados, 404 genérico, sesión sin bypass, disponibilidad y badge | PASS |
| FR-009–013 | Principal/galería, ownership/vigencia, watermark, entrega por UUID, revocación y reasignación; suites de fotos y almacenamiento | PASS |
| FR-014–017 | `PublicModelApprovedContentTest`, `PublicModelAgeLocationTest`: físicos, edad pública, ubicación compatible textual y ausencia de coordenadas/mapas | PASS |
| FR-018–020 | `PublicModelServicesTest`: modalidad, servicios activos, orden y compatibilidad, sin auditoría | PASS |
| FR-021–024 | Bio/canónicos propios aprobados; DTO explícito; `PublicModelApprovedContentTest`, Privacy, ResponseBoundary y Seo | PASS |
| FR-025 | Selección restringida y ausencia de N+1: QueryBudget, Privacy, Gallery y Performance MySQL; no índices nuevos sin evidencia | PASS |
| FR-026–028 | Presentación semántica y revisión visual T057 completa/mínima/larga/404, tres anchos, foco y contraste; hashes vigentes después del build | PASS |
| FR-029 | `PublicModelSeoTest`: H1/título/description públicos, escape y metadata de 404 | PASS |
| FR-030–031 | 299 pruebas completas + 7 MySQL; regresión de auth, privado, identidad, fotos, moderaciones, tipo/servicios/ubicación/disponibilidad/Filament; evidencia visual separada | PASS |

### Conciliación de SC-001–009

| Criterio | Evidencia / evaluación |
| --- | --- |
| SC-001 | Matriz de elegibilidad y regla central: acceso anónimo 200 o 404 genérico. Satisfecho en casos probados. |
| SC-002 | Centinelas y frontera explícita, edad oculta y metadata; suites Privacy/ResponseBoundary/AgeLocation/Seo. Satisfecho en casos probados. |
| SC-003 | Entrega/galería y procesamiento de imágenes: vigencia, aprobación, watermark y exclusión de variantes privadas. Satisfecho en casos probados. |
| SC-004 | Reemplazos de fotos, bio y físicos conservan vigente hasta aprobación. Satisfecho en casos probados. |
| SC-005 | Slugs/aliases reservados, vuelta al propio, tombstones, sin IDs ni cadenas, concurrencia/rollback MySQL. Satisfecho en casos probados. |
| SC-006 | Unavailable no oculta; Virtual nunca entrega servicios presenciales. Satisfecho en casos probados. |
| SC-007 | T057: tres anchos y cuatro variantes; lectura completa, cero overflow, imágenes correctas y foco visible. Evidencia sin cambios por hashes. Satisfecho en alcance visual revisado. |
| SC-008 | **POSTERGADO / NO EVALUADO**, no requerido para este cierre técnico por decisión explícita del usuario del 2026-10-01. Ninguna sesión, tiempo ni resultado humano atribuido. |
| SC-009 | Regresión funcional completa y evidencia visual independiente T057. Satisfecho en flujos probados. |

### Documentos, continuidad y decisión de cierre

- `spec.md` conserva el criterio original SC-008 y anota su exclusión actual; no se declara satisfecho.
- `tasks.md` conserva T058 con ID/casilla sin marcar y estado POSTERGADA. T059 y T060 se completan con esta evidencia. Se ajustaron las referencias propuestas de T006/T007/T016/T023 a los seis nombres de migración realmente implementados, sin modificar archivos de migración.
- `quickstart.md` elimina instrucciones históricas que afirmaban que comandos/tests no existían, documenta la ejecución aislada y distingue rollout de validación. Los respaldos y la preparación previa a exponer rutas siguen siendo obligatorios para un eventual despliegue.
- `plan.md` y la guía T058 reflejan la misma excepción. Las entradas antiguas de este registro se conservan como historia, y esta sección establece el estado actual.
- **008 y 009 deberán usar `PublicModelProfileVisibility::publiclyVisible()` / Gate como autoridad completa.** `candidates()` es sólo un prefiltro SQL; no basta para publicación. Deben reutilizar la entrega/proyección pública de fotos, slugs/aliases y datos permitidos, sin duplicar reglas, abrir storage ni introducir cachés que impidan revocación. No se implementaron esas features.

**Balance: 59 tareas completadas (T001–T057, T059–T060), una postergada (T058), cero tareas técnicas obligatorias pendientes.** Cierre técnico del MVP autorizado con esta excepción explícita; SC-008 no medido, sin aceptación humana atribuida. Sin despliegue, sin modificaciones de datos reales y sin commit automático.
