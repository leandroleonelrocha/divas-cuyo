# Plan de importación de perfiles desde Excel

Fecha: 2026-10-13. Actualizado: 2026-10-15. Estado: propuesta; no se ejecutó ninguna importación.

## Diagnóstico

Se revisó `Perfiles Divas Cuyo.xlsx` y el código actual, no los registros de una base de datos en ejecución.

- Hoja `Perfiles`: 29 registros, 30 columnas, 29 páginas de origen distintas y 26 nombres distintos.
- Hoja `Servicios`: matriz de 29 perfiles y una fila adicional de totales. No importar los totales como una modelo.
- Hay nombres repetidos entre publicaciones Creadora y Escort. No equivalen necesariamente a 29 personas ni deben fusionarse automáticamente.
- El esquema actual permite un perfil por usuario y un tipo de publicación por perfil. Cada usuario requiere un email, ausente en el Excel.
- Faltan WhatsApp en 3 registros, ubicación en 3 y edad/altura en 2. `whatsapp` y `location` son columnas obligatorias en la creación original de perfiles: resolver los faltantes antes de persistir, sin inventarlos.
- El archivo no aporta nombres legales, fecha de nacimiento, documentación de identidad ni archivos de fotos o videos. `Video en perfil = Sí` no proporciona un video importable.
- `DatabaseSeeder` carga catálogos y perfiles demo en local. Los perfiles demo generan cuentas verificadas y perfiles aprobados: ese comportamiento no corresponde a la importación de estos datos.

## Enfoque propuesto

Crear un importador explícito con vista previa, validación y reporte por fila. Mantener los seeders para catálogos y demostración. Primero preparar y revisar los registros; luego guardar únicamente los que tengan resuelta su cuenta, identidad de origen y validaciones.

La primera entrega será una pantalla de importación en Filament, porque el operador no tiene acceso SSH/SFTP. Flujo elegido: primero subir y revisar el Excel; después cargar fotos individualmente desde cada perfil. No se requiere un ZIP ni preparar carpetas en producción. El servidor guarda automáticamente los archivos en almacenamiento privado. Un comando Artisan sería opcional, no una dependencia de uso.

Flujo administrativo: subir Excel → analizar y revisar filas → resolver correspondencias y errores → confirmar importación → abrir cada perfil y cargar sus fotos → revisar y publicar cuando cumpla los requisitos. Mostrar los perfiles sin fotos como pendientes de completar. La carga del Excel no debe exigir fotos para crear un perfil no publicado.

## Mapeo completo

| Columna del Excel | Destino o tratamiento |
| --- | --- |
| Nombre | `model_profiles.stage_name`; completar también el campo obligatorio `name` según la convención de importación, sin tratarlo como nombre legal. |
| Tipo | Creadora → `publication_types.slug=virtual`; Escort → `encounters`. Resolver el ID por slug. |
| Título del perfil | Nuevo `profile_title` opcional si debe mostrarse; conservar en el registro de origen hasta entonces. No reemplazar el nombre artístico. |
| Página | Identificador de origen en una tabla de correspondencias; evaluar conservación/redirección del slug antiguo mediante `ModelProfileSlugService`. No descargar automáticamente la página. |
| Teléfono | Contacto de origen; nuevo `contact_phone` opcional si se necesita mostrar un teléfono distinto de WhatsApp. No asumir que es un teléfono privado. |
| WhatsApp | `model_profiles.whatsapp`; extraer número de enlaces si corresponde y normalizar sin adivinar números faltantes. |
| Telegram | Nuevo `telegram` opcional; validar usuario/enlace. |
| Disponibilidad | `schedule_availability`: Full time → `full_time`; Part time → `part_time`. No representa el estado disponible/ausente de `availability_status`. |
| Precio | Todos dicen Consultar → `rate_visibility=consult`, importes y monedas nulos para perfiles nuevos. |
| Ubicación | Resolver `province_id` y `locality_id` con los catálogos existentes; completar `location` compatible con el esquema vigente. “Consultar” no es una localidad. |
| Categoría | Nuevo `service_category` opcional para Convencional/Completo; es distinto del tipo de publicación. |
| Zona / dirección | `approximate_location_text`, revisando el texto antes de hacerlo público; no generar coordenadas ficticias. |
| Edad | `public_age`; definir `show_age` explícitamente. No calcular una fecha de nacimiento a partir de esta edad. |
| Altura | `height_cm`: por ejemplo `1,62` → `162`. |
| Medidas | `measurements`; validar formato y advertir anomalías como `250-60-100` sin corregirlas por suposición. |
| Busto / Cola | Nuevos campos opcionales `bust_description` / `buttocks_description`, o conservar como información de origen si no se mostrarán. |
| Ojos / Cabello / Color de piel | `eye_color` / `hair_color` / `skin_color`; normalizar variantes mediante un diccionario explícito. |
| Idiomas | Nuevo `languages` estructurado, preservando nivel cuando figure “inglés básico”. |
| Depilación | `depilation`: Full → `full`. |
| Fuma | Nuevo `smokes` booleano nullable: No → false; vacío → null, no false. |
| Cuerpo | `body_type`; alias explícito Fitnes → Fitness. |
| Nacionalidad | `model_profiles.nationality`; acordar nomenclatura para Argentina/Chile. |
| Video en perfil | Metadato de origen. No crear un recurso multimedia a partir de “Sí”. |
| Cant. servicios | Control contra la lista original y la matriz, no atributo editable del perfil. Contar antes de unificar alias. |
| Servicios | Catálogo `services` y relación `model_profile_service`, con alias y validación de compatibilidad. |
| Descripción | `model_profile_bios.content`, inicialmente pendiente de revisión. `current_bio_id` se establece mediante el flujo de aprobación. |
| Otros datos | Información de origen; “Biotipo: Natural” requiere un campo opcional si se desea editar/mostrar. |

Los campos nuevos son propuestas de producto: conservarlos en staging permite importar el núcleo sin ampliar inmediatamente toda la ficha pública.

## Decisiones necesarias antes de guardar perfiles

1. **Cuentas — decidido:** crear una cuenta genérica por perfil nuevo, respetando la relación actual de un perfil por usuario. Usar nombre `Modelo importada <identificador>` y email único `modelo-importada-<identificador>@divascuyo.invalid`, donde el identificador es estable y está ligado a la correspondencia de origen, no al número de fila. Contraseña inicial `password`, almacenada con `Hash::make`, nunca en texto plano. Crear con `is_admin=false` y `email_verified_at=null`, sin enviar correos al email provisional. La falta de email real no bloquea la importación. Al reimportar, reutilizar la cuenta vinculada sin restablecer contraseña ni reemplazar un email real incorporado posteriormente. Para activar la cuenta y publicar conforme a las reglas actuales, completar y verificar un email real.
2. **Publicaciones repetidas:** revisar los tres nombres repetidos y resolver si son personas distintas o publicaciones de una misma persona. Si son la misma persona, decidir consolidación en un perfil compatible o diseñar soporte para múltiples perfiles; no cambiar la relación uno a uno implícitamente.
3. **Datos adicionales:** definir cuáles de título, Telegram, teléfono, categoría, idiomas, busto, cola, fuma y biotipo deben aparecer en administración y ficha pública.
4. **Servicios ambiguos:** resolver Packs, Venta de contenido, Nivel vip, Solo hombres y otros términos sin equivalencia directa. Full domingos/Full noches parecen horarios; no convertirlos automáticamente en prestaciones.

## Tareas ordenadas

- [ ] **T01 — Preparar lector y diagnóstico.** Incorporar lectura XLSX con una dependencia compatible con el proyecto, elegida al implementar. Reconocer hojas y encabezados por nombre, ignorar filas vacías/totales, leer valores como datos sin ejecutar fórmulas ni instrucciones de las celdas. Reportar fila y columna de errores.
- [ ] **T02 — Crear staging y trazabilidad.** Diseñar lotes/filas de importación con huella del archivo, versión del mapeo, datos originales, datos normalizados, estado y errores. Guardar el archivo y reportes en almacenamiento privado, fuera del repositorio. Registrar una correspondencia única `(source, source_url)` con `model_profile_id`; permitir múltiples páginas de origen asociadas al mismo perfil sólo con resolución explícita.
- [ ] **T03 — Resolver cuentas y duplicados.** Preparar un manifiesto de correspondencias con usuarios/perfiles existentes y crear cuentas genéricas para perfiles nuevos según la decisión anterior. Persistir cuenta, perfil y correspondencia en la misma transacción; ante colisiones de email, reportar el conflicto sin apropiarse de una cuenta ajena. Nombre y teléfono sirven como señales de revisión, nunca como clave automática de actualización. Bloquear asignaciones incompatibles con un perfil por usuario. Mantener en staging las filas incompletas.
- [ ] **T04 — Implementar normalización.** Crear un mapeador probado para números, alturas, edades, texto, horarios, tarifas, contactos y ubicación. Resolver provincia/localidad sin ambigüedad; señalar contradicciones entre ubicación y zona. Validar longitudes y reglas reales del dominio. Distinguir vacío de instrucción explícita para borrar un dato.
- [ ] **T05 — Conciliar servicios.** Tomar `Perfiles.Servicios` como lista de origen y cotejar con la matriz usando Nombre + Tipo sólo cuando esa combinación sea única. Ignorar totales. Incorporar alias Video llamada → Videollamada, Streptease → Striptease y Masaje con final feliz → Masajes con final feliz. Revisar términos restantes y agregar al `ServiceSeeder` únicamente prestaciones acordadas con tipo y slug estables.
- [ ] **T06 — Resolver compatibilidad virtual/presencial.** El catálogo actual clasifica Fantasías, Juguetes y Squirt como presenciales y aparecen en registros Creadora. Reportar estos conflictos; acordar reclasificación o servicios específicos antes de aplicarlos. Reutilizar la regla de compatibilidad, sin eliminar silenciosamente servicios ni convertir automáticamente una Creadora en Escort.
- [ ] **T07 — Agregar atributos aprobados.** Para los campos nuevos seleccionados, crear migración nullable, casts, validaciones, formulario administrativo y representación pública explícita. Mantener los datos de origen fuera de la respuesta pública. Si el alcance inicial sólo usa campos existentes, diferir esta tarea conservando la información en staging.
- [ ] **T08 — Implementar aplicación de filas.** Crear `ModelProfileImportService` con transacción por perfil y bloqueo de correspondencias. Persistir perfil, servicios, biografía pendiente y vínculo de origen de forma consistente. Reutilizar servicios de slug y cambios de publicación/historial cuando corresponda. No llamar al servicio de datos privados con datos legales inventados. Crear perfiles pendientes y no publicados; no aprobar identidad ni verificar email desde el Excel.
- [ ] **T09 — Definir reimportación.** La misma fuente y el mismo contenido no deben duplicar cuentas, perfiles, relaciones, bios ni historial. Por defecto, mostrar diferencias y omitir actualizaciones no resueltas; no sobrescribir cambios manuales, borrar campos por celdas vacías o reemplazar todos los servicios de un perfil existente sin política explícita. Resolver colisiones de slug con el registro existente.
- [ ] **T10 — Crear pantalla de importación y reporte en Filament.** Restringir carga, consulta de lotes, vista previa y aplicación a administradores autorizados. Validar tipo y tamaño del Excel y almacenarlo de forma privada. La vista previa guarda staging pero no modifica perfiles/catálogos ni dispara notificaciones. Mostrar altas, vínculos, cambios, filas sin cambios, bloqueos y advertencias. Permitir confirmar las filas válidas y conservar el detalle de las bloqueadas. Impedir aplicar un archivo que cambió respecto del lote revisado y proteger contra doble envío. Incluir enlaces a los perfiles resultantes. Procesar por bloques con avance persistido y reanudación, sin requerir que el operador ejecute workers por SSH; si se usan colas, verificar antes que el hosting tenga un worker configurado.
- [ ] **T11 — Probar casos reales con fixtures sintéticos.** Cubrir 29 filas + totales; nombres repetidos; creación de cuenta genérica sin email real; contraseña inicial verificable con `Hash::check`; cuenta sin privilegios ni email verificado; falta de WhatsApp/ubicación; preservación de contraseña y email al reimportar; conversión 1,62 → 162; vacíos; alias; servicios virtuales incompatibles; coincidencia entre hojas; repetición del lote; cambios manuales; rollback por fila; slugs ocupados; bios pendientes y ausencia de publicación automática. No copiar datos personales del Excel a los tests.
- [ ] **T12 — Ensayar y aplicar.** Ejecutar vista previa en un entorno de prueba, resolver reporte y revisar una muestra contra el archivo. Antes de aplicar a la base objetivo, respaldarla y registrar el lote. Verificar conteos de registros de origen, perfiles creados y filas bloqueadas por separado: 29 filas no obliga a crear 29 perfiles.
- [ ] **T13 — Cargar fotos por perfil y completar publicación.** Después de importar el Excel, abrir cada perfil desde el reporte y cargar sus fotos mediante el administrador existente, adaptándolo si hace falta. Mostrar nombre, tipo e identificador de origen antes de subir para evitar asociaciones equivocadas. Permitir elegir principal y orden; guardar originales de forma privada y procesar copias sin EXIF mediante el flujo existente. Para fotos con marca de agua previa, diseñar y probar una revisión explícita que registre esa procedencia sin duplicar automáticamente la marca ni simular procesamiento. Completar revisión de perfil, identidad, biografía y verificación de cuenta. La visibilidad actual exige email verificado, identidad/perfil aprobados, publicación activa y foto principal aprobada con marca de agua y archivo público accesible.
- [ ] **T14 — Verificar operación desde navegador.** Probar carga del Excel, permisos de acceso a archivos y lotes, confirmación, interrupciones/reintentos, carga posterior de fotos y reporte de perfiles incompletos. Comprobar límites de carga del hosting y persistencia de storage entre despliegues. Documentar limpieza de archivos temporales sin eliminar imágenes definitivas. Todo el flujo habitual debe funcionar sin terminal ni transferencia manual al servidor.

Dependencias: T01–T06 preparan el lote; T07 depende del alcance de atributos; T08–T10 dependen de las decisiones y el mapeo; T11 valida el conjunto; T12 aplica; T13 completa fotos y habilita publicación. T14 valida la entrega administrativa completa.

## Archivos previstos

- Nuevos: página administrativa bajo `app/Filament/Pages/` y su vista, `app/Services/ModelProfileImportService.php`, lector/mapeador bajo `app/Imports/`, migraciones y modelos de staging/correspondencias, configuración de alias y tests de importación.
- Según alcance: `app/Models/ModelProfile.php`, `database/seeders/ServiceSeeder.php`, formularios/validaciones de perfiles y `app/Services/PublicModelProfileQuery.php` junto con su view model para exponer atributos nuevos.
- Conservar `DatabaseSeeder.php` y `DemoModelProfilesSeeder.php` con su finalidad actual; la carga real se ejecuta explícitamente.

## Criterios de aceptación

- Cada fila original queda contabilizada como aplicada, vinculada, sin cambios o bloqueada con motivo.
- Ejecutar dos veces el mismo lote no duplica datos ni restablece credenciales.
- Cada perfil nuevo recibe una cuenta genérica única con contraseña inicial `password` almacenada mediante hash; la falta de email real no bloquea su creación. No se envían correos a direcciones provisionales.
- No hay pérdida silenciosa de columnas, servicios ni cambios manuales.
- Las filas sin información obligatoria permanecen preparadas y reportadas.
- Importar no fabrica identidad, consentimiento, emails verificados, fotos ni aprobaciones.
- La ficha pública mantiene las reglas existentes de visibilidad y privacidad.

- El administrador puede importar el Excel y luego cargar fotos por perfil íntegramente desde el navegador, sin SSH/SFTP ni ZIP.
- Los archivos y vistas previas permanecen privados; sólo se publican las imágenes mediante el flujo de aprobación existente o su adaptación explícita para marcas previas.
