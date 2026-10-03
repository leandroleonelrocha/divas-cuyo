# Quickstart: Validación de perfil público

Guía de la implementación terminada. Cierre técnico del MVP verificado el 2026-10-01; T058/SC-008 queda expresamente postergado por decisión del usuario, sin resultados humanos. Evidencia y límites del cierre en [validation.md](validation.md).

## Prerrequisitos y preparación

PHP compatible con composer.json (mínimo 8.2), dependencias ya instaladas, Node compatible con Vite 7 y MySQL para la integración. Usar datos sintéticos y disco privado; no crear un enlace público al disco. Los tests habituales fuerzan SQLite en memoria y los MySQL exigen exclusivamente las variables externas `PUBLIC_PROFILE_MYSQL_TEST_*` con base vacía terminada en `_test`. No utilizar `divas_cuyo` ni producción.

Para repetir el cierre técnico desde la raíz, sin configuración cacheada ni procesos de prueba concurrentes:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL='' CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync php artisan test
php -r '$n=getenv("PUBLIC_PROFILE_MYSQL_TEST_DATABASE"); if (!$n || !str_ends_with($n,"_test")) { fwrite(STDERR,"ABORT: DATABASE must end in _test\n"); exit(1); } echo "Guard PASS: ".$n.PHP_EOL;'
vendor/bin/phpunit --configuration phpunit.mysql.xml
vendor/bin/pint --test
npm run build
```

Detenerse si falla una guarda. No sustituir la base aislada por `.env`. `PublicModelMigrationTest` ejecuta forward/backfill, dry-run/real y rollback dentro del esquema propio, con Storage fake; para regresión no hace falta levantar un servidor ni migrar otra base. Un eventual rollout se autoriza y prepara por separado, con respaldo y acceso público deshabilitado hasta completar slugs y fotos.

No usar migrate:fresh sobre una base con datos. El comando de preparación debe terminar sin errores de variantes requeridas; si reporta faltantes, esos perfiles deben seguir 404 hasta corregir fuente y reejecutarlo. Un segundo recorrido no cambia variantes ya preparadas ni estados de moderación. Para backfill de slugs, validar tanto migración limpia como actualización desde esquema 006 sobre copia local sintética.

## Automatización

```bash
php artisan test tests/Feature/PublicModels
php artisan test tests/Unit/Services/ModelProfileSlugServiceTest.php
php artisan test
vendor/bin/pint --test
npm run build
```

La suite habitual usa SQLite en memoria y RefreshDatabase. La integración MySQL usa `phpunit.mysql.xml` y `tests/Integration/MySqlTestCase.php`, que no hereda `RefreshDatabase`, no abre transacciones implícitas y exige una base cuyo nombre termine en `_test`. Las cinco variables `PUBLIC_PROFILE_MYSQL_TEST_HOST`, `PUBLIC_PROFILE_MYSQL_TEST_PORT`, `PUBLIC_PROFILE_MYSQL_TEST_DATABASE`, `PUBLIC_PROFILE_MYSQL_TEST_USERNAME` y `PUBLIC_PROFILE_MYSQL_TEST_PASSWORD` deben configurarse externamente; no se guardan credenciales en el repositorio. El test case rechaza la configuración ausente o una base sin sufijo `_test` antes de arrancar Laravel o conectar. No uses la base configurada en `.env` como fallback.

Con el entorno aislado ya configurado, ejecutar:

```bash
vendor/bin/phpunit --configuration phpunit.mysql.xml
```

Debe usar conexiones/procesos independientes con commit real para dos perfiles solicitando el mismo slug, colisión con alias histórico y dos renombrados del mismo perfil. No heredar una transacción externa de RefreshDatabase que esconda commits. Verificar reservas únicas, consistencia nombre/slug, rollback completo y ausencia de cadenas. Probar migración forward/backfill/repetición sobre la misma base aislada y comparar índices con EXPLAIN.

## Fixture funcional

Crear una cuenta sintética por el flujo existente y verificar email. Completar stage_name `Mia`, provincia/localidad compatibles y datos públicos. Aprobar identidad y perfil desde administración y activar publicación. Subir/aprobar una foto, seleccionarla principal y preparar su variante pública cuando corresponda. Agregar otra foto aprobada y una pendiente, bio aprobada, servicios virtual/presencial y tipo Encuentros. No usar documentación real para demostraciones.

Abrir como invitado `http://127.0.0.1:8000/modelos/mia`. Para obtener fixtures automatizados reutilizar UserFactory y helpers existentes; actualmente no existe ModelProfileFactory. Las pruebas nuevas crean la matriz de estados y recursos sintéticos con Storage::fake.

## Escenarios end-to-end

1. Perfil elegible → 200 con principal, insignia, nombre, tipo, características y disponibilidad. Cambiar a unavailable → 200 y “No disponible”, is_published intacto.
2. Renombrar Mia a Luna desde `/account/profile`: /modelos/luna responde 200 y /modelos/mia responde 302 hacia Luna. Renombrar a Sol: ambos aliases llevan directamente a Sol. Volver a Mia: sin bucles ni pérdida de reservas.
3. Despublicar: URL actual, aliases e imágenes antiguas responden 404, sin Location en aliases. Comparar cuerpo con slug inexistente e ID numérico; repetir autenticado como propietaria/admin.
4. Variar individualmente email no verificado, identidad incomplete/pending/rejected, review pending/rejected y ausencia de principal: todos 404. Restaurar cada condición antes del siguiente caso.
5. Reemplazar principal y bio por propuestas pendientes/rechazadas: siguen los aprobados anteriores. Aprobar reemplazo de foto: URL de versión vieja da 404; nueva imagen lleva watermark. Eliminar principal con/sin suplente comprueba reasignación o 404.
6. En tests aislados retirar archivo principal, invalidar ruta y usar variante sin evidencia de watermark: perfil e imágenes denegados; secundaria faltante sólo se omite. No exponer excepción ni fallback privado.
7. Configurar edad pública distinta de real, show_age true/false. Inspeccionar HTML, metadata y recursos con centinelas de email, nombres reales, fecha, teléfono, DNI, paths, IDs, motivos y coordenadas. Comparar campos estructurales y valores distintivos, no buscar números triviales que coincidan con dimensiones.
8. Revisión física pendiente/rechazada conserva canónicos; aprobación cambia valores. Bio ausente no deja sección vacía. Ninguna relación de otro perfil se acepta como actual.
9. Solo Virtual excluye servicios presenciales incluso con asociación inconsistente inyectada en fixture; Encuentros muestra ambos grupos en sort_order; inactivar un servicio lo retira en siguiente solicitud. Localidad incompatible se omite y nunca aparecen coordenadas/mapas.
10. Medir queries de dominio con 1/5 fotos y 1/20 servicios: cantidad constante, sin lazy loading desde Blade. Registrar tiempos observados sin declararlos SLA de producción.

## Validación visual y cierre

A 360, 768 y 1440 px revisar perfil completo, mínimo, textos largos y 404: jerarquía, principal, galería, badge, disponibilidad, físicos, bio, ubicación, tipo, servicios, omisión de vacíos, foco por teclado y contraste. Guardar evidencia y resultados en el registro de validación de la implementación. No sustituir revisión por tests HTTP.

SC-008 mantiene el criterio de cinco participantes y al menos cuatro aciertos en menos de 30 segundos sin ayuda ni login. **T058 está postergada y no requerida para el cierre técnico del MVP por decisión explícita del usuario del 2026-10-01**: no se realiza en esta etapa ni se declara satisfecha. Se conserva la [guía futura](t058-session-guide.md).

Suite completa debe cubrir auth, `/account`, `/account/profile`, identidad, fotos, moderación física/bio, tipo, servicios, ubicación y Filament. Verificar que accesos privados siguen exigiendo permisos y las nuevas rutas no cambian binding administrativo.

Referencias: [contrato público](contracts/public-model-profile.md), [datos y rollout](data-model.md), [plan](plan.md). Ningún resultado de esta guía se declara ejecutado por el mero hecho de generar estos documentos.


## Operación del backfill de slugs

Las migraciones de US2 agregan `model_profile_slugs` (000003) y ejecutan el backfill (000004), conservando las migraciones de US1. El comando `model-photos:prepare-public` está implementado por US3 y validado en MySQL por T056/T059.

El backfill primero reserva todos los slugs existentes y después asigna únicamente a perfiles sin slug con stage_name no vacío. Recorre lotes de 100 por ID, relee cada perfil bajo bloqueo y conserva cualquier asignación válida sobrevenida. No usa name legacy ni datos privados. Normaliza a ASCII, máximo 160 con sufijo, usa modelo para nombres no vacíos sin base y modelo-{base} para bases numéricas. Respeta reservas actuales/históricas/tombstones, usa sufijos -2/-3 y permite reejecución sin sobrescrituras. Una contradicción entre propietario y reserva falla explícitamente; no la corrige reasignando URLs.

La migración de datos tiene down sin borrado: no es seguro inferir cuáles URLs ya se compartieron ni eliminar reservas. Antes de revertir esquema, deshabilitar acceso público y respaldar model_profiles.slug y toda model_profile_slugs. Revertir la migración de tabla elimina historial/reservas/tombstones; volver a ejecutar backfill sólo reconstruye slugs actuales, no recupera ese historial. Restaurar el respaldo es necesario para conservar URLs históricas.

Las migraciones se ejecutaron sólo dentro de pruebas aisladas, no sobre datos de desarrollo/producción. T003/T053/T056 y su regresión T059 están verificadas en MySQL aislado. Asignar slug no publica un perfil: siguen vigentes todos los requisitos de US1, incluida principal pública aprobada y acreditada.

## Ejecución aislada verificada — T052/T053/T056 (2026-10-01)

Esta sección actualiza las notas históricas anteriores: el comando de preparación está implementado y las tres tareas se validaron en MySQL 5.7.39. Para repetirlas, usar exclusivamente las cinco variables externas `PUBLIC_PROFILE_MYSQL_TEST_*`; no copiar credenciales al repositorio ni usar `.env` como fallback. DATABASE debe terminar exactamente en `_test`. La base debe estar **vacía** y ser exclusiva de esta ejecución; la suite aborta si encuentra tablas. No borrar una base existente para satisfacer este requisito.

```bash
php -r '$n=getenv("PUBLIC_PROFILE_MYSQL_TEST_DATABASE"); if (!$n || !str_ends_with($n,"_test")) { fwrite(STDERR,"ABORT: DATABASE must end in _test\n"); exit(1); } echo "Guard PASS: ".$n.PHP_EOL;'
vendor/bin/phpunit --configuration phpunit.mysql.xml
```

No continuar si falla la guarda. El harness exige además todas las variables, rechaza configuración cacheada, anula DB_URL/DB_SOCKET alternativos y confirma `SELECT DATABASE()` antes de migrar. Ejecutar secuencialmente, sin otro proceso usando esa base. No ejecutar los comandos genéricos de migración del principio de esta guía contra `divas_cuyo`.

La suite crea fixtures sintéticos y revierte su propio esquema al terminar (incluida la tabla de migraciones); no usa `migrate:fresh`. Si una interrupción deja tablas, la próxima ejecución aborta: inspeccionar el estado antes de cualquier recuperación. Las fotos usan Storage fake y nunca el disco de producción. El worker de concurrencia es parte interna del test, no un comando de uso independiente.

Resultados: 36 migraciones desde cero; actualización desde 006 con 101 perfiles y una versión legacy; repetición sin reasignar slugs/tokens. Preparación en MySQL: dry-run 1, real 1/0 fallidas, repetición 0; original y processed intactos. La ruta del fixture responde 404 antes de preparar y 200 después. No se habilitaron rutas de ningún entorno real.

Antes de cualquier rollback operativo autorizado por separado, mantener el acceso público deshabilitado y respaldar `model_profiles.id/slug`, todas las filas de `model_profile_slugs` (incluidos alias y tombstones), y los metadatos/archivos de fotos necesarios para reconstruir sus URLs. Conservar ese respaldo fuera del destino del rollback. `down()` de datos no elimina reservas, pero revertir la tabla sí: volver a migrar sólo recupera slugs actuales. Restaurar y verificar el respaldo antes de reabrir acceso, luego ejecutar dry-run/real de preparación y comprobar que no hay pendientes requeridos. La prueba aislada respaldó/restauró 104 reservas y comprobó 102 slugs; su JSON temporal en Storage fake no reemplaza un respaldo operativo.

Ver tiempos, solicitudes, planes EXPLAIN y límites de la medición en [validation.md](validation.md). T057 aporta la revisión visual independiente; T059/T060 completan el cierre técnico, con T058 postergada. Estas pruebas no constituyen un rollout.

## Reutilización obligatoria en 008/009

Las futuras 008-public-model-directory y 009-home-model-showcase deben delegar la decisión completa en `PublicModelProfileVisibility::publiclyVisible()` / Gate `viewPublicModelProfile`. `candidates()` sólo preselecciona por SQL: no acredita por sí solo legibilidad del recurso, ownership final ni procedencia. No duplicar una regla incompleta ni persistir un booleano de visibilidad.

Reutilizar reservas/slugs actuales e históricos, `PublicModelPhotoService` para principal/galería y URLs opacas, y las proyecciones públicas explícitas. Mantener la autorización por solicitud, revocación/no-store, disponibilidad informativa y omisión de campos privados. El cierre de 007 no implementa ni habilita directorio, home con modelos, contacto o mapas.
