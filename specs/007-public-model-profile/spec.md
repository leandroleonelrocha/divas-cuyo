# Feature Specification: Perfil público individual de modelo

**Feature Branch**: Sin crear; identificador de feature `007-public-model-profile`.

**Created**: 2026-09-28

**Status**: Cierre técnico del MVP completado (2026-10-01), con SC-008/T058 explícitamente postergado por el usuario; no evaluado. Ver [evidencia de cierre](validation.md#cierre-técnico-del-mvp--t059-y-t060--2026-10-01). No implica despliegue ni validación humana.

**Input**: User description: "Crear el perfil público individual en /modelos/{slug}, accesible sin autenticación, con una única regla reutilizable de visibilidad y únicamente información pública aprobada. Generar sólo spec.md y checklists/requirements.md, sin implementar código."

## Clarifications

### Session 2026-09-28

- Q: ¿Desde cuándo debe quedar fijo el slug, la parte del enlace como `mia` en `/modelos/mia`? → A: Siempre cambia con el nombre artístico y el enlace anterior redirige al nuevo.
- Q: ¿Qué debe ocurrir si un perfil cumple las demás condiciones de publicación, pero no tiene una foto principal aprobada? → A: Responder 404 hasta disponer de una principal aprobada con variante pública y watermark.
- Q: ¿En esta feature la ubicación debe mostrarse sólo como texto o también en un mapa aproximado? → A: Sólo texto; no enviar coordenadas al navegador. Mapa para una feature posterior.

## User Scenarios & Testing

### User Story 1 - Consultar un perfil publicable sin iniciar sesión (Priority: P1)

Como visitante, quiero abrir el enlace de una modelo y reconocer su nombre artístico, verificación, disponibilidad y ubicación aproximada sin acceder a información privada.

**Why this priority**: Establece el acceso principal y la frontera de privacidad para todas las futuras superficies públicas.

**Independent Test**: Abrir un perfil que cumpla las cinco condiciones de visibilidad y variar cada condición por separado.

**Acceptance Scenarios**:

1. **Given** email verificado, identidad aprobada, revisión aprobada, publicación activa y principal vigente aprobada con variante pública disponible, **When** se visita `/modelos/mia` sin sesión, **Then** se obtiene 200 y el perfil público de Mia.
2. **Given** ese perfil, **When** por separado el email deja de estar verificado, la identidad pasa a `incomplete`, `pending` o `rejected`, la revisión pasa a `pending` o `rejected`, o se despublica, **Then** cada visita devuelve el mismo 404 genérico que un slug inexistente.
3. **Given** un perfil no publicable, **When** su propietaria o una administradora abre la ruta pública con sesión, **Then** también recibe 404; esta ruta no habilita una vista previa privada.
4. **Given** un perfil publicable, **When** cambia de `available` a `unavailable`, **Then** continúa respondiendo 200, muestra “No disponible” y conserva publicación y moderaciones; al volver a `available` muestra “Disponible”.
5. **Given** un perfil visible, **When** se presenta su verificación, **Then** muestra “Modelo verificada por Divas Cuyo” sin documentos, fechas ni datos del proceso.

### User Story 2 - Compartir enlaces que acompañan al nombre artístico (Priority: P1)

Como modelo, quiero compartir un enlace basado en mi nombre artístico que siga funcionando después de cambiarlo.

**Why this priority**: Evita revelar identificadores internos y romper enlaces publicados.

**Independent Test**: Preparar perfiles con nombres repetidos, abrir sus enlaces y cambiar el nombre de uno ya publicado.

**Acceptance Scenarios**:

1. **Given** tres perfiles llamados Mia, **When** se asignan slugs, **Then** reciben valores únicos como `mia`, `mia-2` y `mia-3`; cada enlace resuelve sólo su perfil cuando es publicable.
2. **Given** nombres con tildes, espacios o signos, **When** se generan slugs, **Then** resultan legibles y seguros para una URL; un nombre sólo numérico no produce una URL confundible con un ID.
3. **Given** un perfil publicable con slug asignado, **When** cambia el nombre artístico, **Then** se genera un slug actual acorde al nuevo nombre y el enlace anterior redirige al actual, conservando el acceso al mismo perfil.
4. **Given** un slug desconocido o un ID secuencial, **When** se consulta `/modelos/{valor}`, **Then** responde 404 sin resolver por ID ni redirigir a otro enlace.
5. **Given** asignaciones concurrentes o perfiles existentes sin slug, **When** se incorporan al sistema de enlaces, **Then** reciben valores únicos sin sobreescribir URLs ni utilizar nombre real, email o teléfono.

### User Story 3 - Ver fotografías vigentes y aprobadas (Priority: P1)

Como visitante, quiero ver la principal y una galería consistente con las fotos aprobadas actualmente.

**Why this priority**: Las imágenes deben respetar moderación y privacidad incluso cuando se abren sus enlaces directamente.

**Independent Test**: Consultar página y enlaces de imágenes con versiones actuales aprobadas, reemplazos pendientes, rechazos y versiones antiguas.

**Acceptance Scenarios**:

1. **Given** principal y otras fotos aprobadas, **When** se abre el perfil, **Then** se destaca la principal y la galería respeta `position`, incluye la principal en su posición y utiliza únicamente variantes públicas con watermark.
2. **Given** un reemplazo pendiente o rechazado, **When** se consulta el perfil, **Then** permanece la versión aprobada vigente anterior; no se muestran propuestas ni versiones aprobadas antiguas no vigentes.
3. **Given** una foto sin versión vigente aprobada, **When** se consulta la galería o su enlace público, **Then** no se entrega la imagen ni información administrativa.
4. **Given** la eliminación de la principal, **When** el flujo existente selecciona otra aprobada por posición, **Then** esa foto pasa a destacarse; si no queda principal apta, el perfil responde 404 sin modificar automáticamente `is_published`.
5. **Given** un enlace de foto previamente válido, **When** el perfil deja de ser publicable, la foto se elimina o la versión deja de ser vigente, **Then** nuevas solicitudes no entregan esa imagen retirada.
6. **Given** una variante pública principal ausente o inutilizable, **When** se consulta el perfil, **Then** responde 404 sin sustituirla por originales o variantes privadas; una foto secundaria sin variante pública se omite.

### User Story 4 - Leer información aprobada respetando privacidad (Priority: P1)

Como visitante, quiero conocer la presentación y características autorizadas para publicación.

**Why this priority**: Evita revelar datos reales, propuestas pendientes o motivos administrativos.

**Independent Test**: Usar valores distinguibles públicos, privados, aprobados y pendientes; inspeccionar contenido visible, respuesta completa, metadata y recursos.

**Acceptance Scenarios**:

1. **Given** características canónicas y una revisión física pendiente o rechazada con valores diferentes, **When** se abre el perfil, **Then** sólo aparecen valores canónicos; una aprobación posterior permite mostrar los nuevos valores.
2. **Given** una bio vigente aprobada y otra pendiente o rechazada, **When** se abre el perfil, **Then** sólo aparece la vigente aprobada; si no existe se omite “Sobre mí”, sin informar de pendientes.
3. **Given** `show_age = true` y una edad pública diferente de la real, **When** se consulta, **Then** sólo se muestra `public_age`; con `show_age = false` o sin edad pública se omite el dato incluso de contenido oculto y metadata, sin derivarlo de `birth_date`.
4. **Given** campos opcionales vacíos, **When** se presenta el perfil, **Then** no aparecen etiquetas vacías; cabello, piel y tipo de cuerpo aparecen sólo con valor público canónico.
5. **Given** datos privados y administrativos cargados, **When** se inspecciona la respuesta completa, **Then** no contiene esos campos ni sus valores privados, aunque no fueran visibles en pantalla.
6. **Given** provincia, localidad compatible y zona aproximada, **When** se consulta, **Then** se muestran esas referencias, nunca dirección exacta, mapas ni coordenadas, tampoco en contenido oculto; sin localidad o zona se omiten esas partes.

### User Story 5 - Comprender tipo de publicación y servicios (Priority: P2)

Como visitante, quiero distinguir la modalidad actual y sus servicios sin encontrar combinaciones incompatibles.

**Why this priority**: Permite interpretar la oferta sin historial ni información comercial fuera de alcance.

**Independent Test**: Consultar “Solo Virtual” y “Encuentros” con servicios activos, inactivos y asociaciones incompatibles preparadas expresamente.

**Acceptance Scenarios**:

1. **Given** “Solo Virtual” con servicios de ambos tipos por inconsistencia, **When** se consulta, **Then** aparece el tipo y únicamente los servicios virtuales activos asociados.
2. **Given** “Encuentros”, **When** se consulta, **Then** los servicios activos asociados se agrupan como “Servicios virtuales” y “Servicios presenciales”, ordenados por `sort_order` dentro de cada grupo.
3. **Given** un servicio que se desactiva, **When** se vuelve a solicitar el perfil, **Then** ya no aparece, sin despublicar ni borrar asociaciones históricas.
4. **Given** un grupo sin servicios visibles, **When** se presenta el perfil, **Then** se omite el grupo; si ambos están vacíos se omite toda la sección.
5. **Given** un cambio de tipo confirmado, **When** se consulta nuevamente, **Then** aparece el tipo actual y los servicios compatibles, sin IDs, actores, motivos ni historial.

### User Story 6 - Recorrer una página diseñada y accesible (Priority: P2)

Como visitante desde teléfono, tablet o escritorio, quiero una página legible y reconocible como Divas Cuyo.

**Why this priority**: La entrega pública requiere diseño y validación visual además de contenido correcto.

**Independent Test**: Revisar perfiles completos y mínimos a 360, 768 y 1440 px, con teclado y contenido largo.

**Acceptance Scenarios**:

1. **Given** un perfil completo, **When** se visita en esos tamaños, **Then** hay cabecera Divas Cuyo, jerarquía de nombre/foto/estado y secciones legibles sin overflow horizontal; móvil apila contenido y escritorio distribuye imagen e información en columnas.
2. **Given** galería y controles, **When** se recorren con teclado, **Then** el foco es visible y los controles tienen nombres comprensibles; las imágenes tienen alternativas públicas y no se deforman.
3. **Given** un perfil visible, **When** se inspecciona su presentación para buscadores, **Then** tiene H1 con nombre artístico, título `{stage_name} | Divas Cuyo` y descripción exclusivamente pública.
4. **Given** un perfil inexistente o no publicable, **When** se abre su enlace, **Then** la página 404 mantiene el diseño sin mencionar nombre, existencia o estado de la modelo.

### Edge Cases

- Usuario asociado ausente o estados de identidad/revisión desconocidos: 404.
- Perfiles previos sin nombre artístico utilizable: no tomar nombre de cuenta o datos privados como sustitutos; no habilitar su enlace hasta completar el dato público.
- Normalización vacía: usar una base pública neutra como `modelo` con resolución de colisiones. Nombres sólo numéricos: añadir prefijo legible.
- La existencia de `mia-2` no autoriza listar perfiles ni explicar colisiones o estados de otros slugs.
- Falta de bio, edad, físicos opcionales, localidad o servicios: omitir esos contenidos sin agregar condiciones de visibilidad.
- Localidad incompatible: omitirla, sin reconstruir ubicación desde campos heredados o privados.
- Una bio o versión de foto vinculada por error a otro perfil no se publica; nunca sustituir la vigente aprobada por la última creada.
- Cambiar el nombre genera un slug actual y conserva los anteriores como alias del mismo perfil; los slugs actuales e históricos no pueden asignarse a otra modelo. Despublicar temporalmente tampoco los libera.
- Empates en orden de fotos o servicios: resultado determinista sin mostrar identificadores internos.
- Textos extensos, caracteres especiales o marcado: representación segura, sin ejecución ni ruptura del diseño.
- Una caché no puede permitir nueva entrega de contenido que ya no cumple la regla pública. No se promete retirar copias ya descargadas por visitantes.

## Requirements

### Functional Requirements

- **FR-001**: El sistema MUST ofrecer `GET /modelos/{slug}` sin autenticación, exclusivamente por slug público, nunca por ID secuencial ni mediante accesos alternativos basados en IDs.
- **FR-002**: Cada perfil MUST disponer de slug actual único, legible, normalizado y URL-safe, derivado sólo del nombre artístico público. Las colisiones MUST resolverse con sufijos como `mia-2`, considerando slugs actuales e históricos reservados a otros perfiles, incluso ante cambios o asignaciones concurrentes. Los perfiles existentes MUST recibir un slug seguro antes de habilitar su enlace.
- **FR-003**: Cada cambio de nombre artístico MUST actualizar el slug para reflejar el nuevo nombre, resolviendo colisiones. Si la normalización produce el mismo slug no se crea un alias redundante. Los slugs anteriores MUST conservarse como alias del mismo perfil y redirigir al slug actual cuando el perfil sea publicable. No se incluye edición manual del slug ni una presentación pública del historial.
- **FR-004**: La visibilidad MUST depender de una única regla central reutilizable: email verificado, `identity_status = approved`, `review_status = approved`, `is_published = true` y foto lógica principal con versión vigente aprobada y variante pública con watermark disponible. Todas son necesarias.
- **FR-005**: La regla MUST permitir reutilización sin duplicación en 008-public-model-directory y 009-home-model-showcase, incluidas futuras búsquedas y filtros. Esta entrega MUST aplicar la misma elegibilidad al perfil y a la entrega pública de sus fotos.
- **FR-006**: Slugs inexistentes y perfiles no elegibles MUST producir el mismo 404 genérico, sin redirecciones, nombres, metadata personalizada ni motivos que distingan existencia o estados. Esta regla también rige al solicitar un alias histórico de un perfil no elegible. Tener sesión no altera esta respuesta.
- **FR-007**: `availability_status` MUST ser informativo: `available` → “Disponible”; `unavailable` → “No disponible”. Ninguno cambia visibilidad, publicación, identidad, revisión o tipo.
- **FR-008**: Todo perfil visible MUST mostrar `stage_name` y “Modelo verificada por Divas Cuyo”, sin documentos, fecha de validación, revisor ni detalles del proceso.
- **FR-009**: La imagen destacada MUST ser la foto lógica principal con versión vigente aprobada. Un reemplazo pendiente o rechazado MUST conservar la versión aprobada actual.
- **FR-010**: La galería MUST contener sólo fotos activas del perfil con versión vigente aprobada y variante pública disponible, ordenadas por `position`. Se muestran todas las admitidas por el límite central existente, actualmente cinco, sin otro límite independiente. La principal también aparece en galería en su posición.
- **FR-011**: Todas las imágenes públicas, incluidas previews, MUST tener watermark y ser variantes autorizadas para publicación. Originales, procesadas privadas, thumbnails sin watermark, pendientes, rechazadas y versiones antiguas no vigentes MUST NOT ser accesibles desde la página ni por enlaces públicos directos.
- **FR-012**: La entrega pública MUST preservar el almacenamiento controlado, sin exponer rutas físicas ni abrir el disco. Cada nueva solicitud MUST verificar elegibilidad del perfil, pertenencia de foto y vigencia de versión; una variante ausente no se sustituye por otra privada.
- **FR-013**: Al perder la principal MUST respetarse la reasignación automática existente a la primera aprobada por posición. Si no queda principal pública apta, el perfil MUST responder 404, sin mostrar el perfil con una imagen de reemplazo y sin que la lectura modifique `is_published` o moderaciones.
- **FR-014**: MUST mostrarse, cuando existan como datos públicos canónicos, altura (`height_cm`), peso (`weight_kg`), medidas (`measurements`), ojos (`eye_color`), cabello (`hair_color`), nacionalidad/origen (`nationality`), piel (`skin_color`) y tipo de cuerpo (`body_type`). MUST omitirse etiquetas vacías y usarse unidades comprensibles, por ejemplo “Altura: 1,68 m”.
- **FR-015**: La edad MUST provenir sólo de `public_age` si `show_age = true` y existe el valor. Si es falso, MUST excluirse también de metadata y datos enviados al navegador. Nunca se calcula ni sustituye por edad real desde `birth_date`.
- **FR-016**: La ubicación MUST limitarse a provincia, localidad compatible y `approximate_location_text` público seguro cuando existan. MUST NOT usar domicilio, altura de calle, piso, departamento, dirección privada ni campos administrativos como fallback.
- **FR-017**: Esta feature MUST mostrar la ubicación sólo como texto y MUST NOT incluir mapas ni enviar coordenadas al navegador, tampoco `approximate_latitude` / `approximate_longitude` en HTML, metadata o datos embebidos. Los datos aproximados existentes se conservan para una feature posterior; la existencia de una solución de mapas reutilizable no amplía este alcance.
- **FR-018**: La página MUST identificar el tipo actual con etiqueta “Solo Virtual” o “Encuentros”, sin `publication_type_id`, historial, actor, motivo ni auditoría. Un tipo ausente o desconocido no habilita servicios por inferencia.
- **FR-019**: Sólo servicios activos y asociados MUST ser públicos, agrupados en “Servicios virtuales” y “Servicios presenciales” y ordenados por `sort_order` dentro de cada grupo. Grupos y secciones vacíos MUST omitirse.
- **FR-020**: “Solo Virtual” MUST excluir siempre `in_person`, incluso con asociaciones inconsistentes; “Encuentros” admite `virtual` e `in_person`. Desactivar un servicio MUST reflejarse en la siguiente consulta sin borrar historial ni despublicar el perfil.
- **FR-021**: “Sobre mí” MUST usar exclusivamente `currentBio` perteneciente al perfil y en estado `approved`. Bio pendiente, rechazada o histórica MUST NOT mostrarse; la anterior aprobada vigente permanece hasta otra aprobación. Sin bio vigente aprobada se omite la sección sin informar pendientes.
- **FR-022**: Los físicos MUST proceder sólo de valores canónicos aprobados, nunca de revisiones pendientes, rechazadas ni snapshots administrativos. Sólo una aprobación cambia su presentación pública.
- **FR-023**: La presentación MUST recibir un conjunto explícito de datos públicos permitidos, nunca el perfil completo indiscriminadamente. La exclusión MUST abarcar HTML, atributos, comentarios, metadata, datos embebidos, recursos y errores, no sólo texto visible.
- **FR-024**: MUST NOT exponerse `real_first_name`, `real_last_name`, `birth_date`, edad real, `private_phone`, `model_profile_private_details`, email, WhatsApp privado o heredado, documentos, DNI, selfie, `user_id`, `model_profile_id`, `reviewed_by`, `reviewed_at`, `rejection_reason`, notas administrativas, `storage_path` u otras rutas físicas, historial de tipo y estados internos innecesarios. La insignia no autoriza enviar el estado interno de identidad.
- **FR-025**: La consulta pública MUST recuperar sólo información necesaria para elegibilidad y presentación, excluyendo detalles privados, documentos e historiales administrativos. MUST evitar trabajo repetido por cada foto o servicio y cargar sólo variantes necesarias, nunca originales de gran tamaño.
- **FR-026**: La página MUST tener diseño completo coherente con Divas Cuyo: cabecera, imagen destacada, nombre, insignia, disponibilidad, características, bio, ubicación, tipo, servicios y galería según datos disponibles. No se acepta HTML sin diseño.
- **FR-027**: A 360 px MUST apilar contenido, ofrecer galería usable y evitar overflow horizontal; a 768 px MUST adaptarse como tablet; a 1440 px MUST usar columnas para imagen e información. Fotos MUST conservar proporciones y recortes consistentes sin deformación.
- **FR-028**: Página y 404 MUST usar español, encabezados semánticos, alternativas públicas de imágenes, controles por teclado, foco visible y contraste mínimo de 4,5:1 para texto normal y 3:1 para texto grande.
- **FR-029**: Cada perfil MUST tener H1 con `stage_name`, título `{stage_name} | Divas Cuyo` y descripción basada sólo en nombre artístico, modalidad y ubicación pública disponible. MUST excluir información privada, edad oculta y estados administrativos; SEO avanzado queda fuera.
- **FR-030**: La implementación MUST incluir pruebas de historias y casos límite: visibilidad, privacidad integral, slug y colisiones, bio, físicos, fotos y enlaces directos, edad, disponibilidad, servicios y ubicación. Los datos de prueba MUST diferenciar valores privados o pendientes de los aprobados.
- **FR-031**: MUST conservarse `/account`, `/account/profile`, identidad, fotos, moderaciones físicas y de bio, servicios, tipo, disponibilidad, ubicación, administración y autenticación. Antes de entregar la implementación MUST ejecutarse la suite completa y documentarse resultados y revisión visual manual a 360, 768 y 1440 px, incluyendo estados sin datos, foco y contraste. Esta fase documental no ejecuta ni afirma esas validaciones de producto.

### Key Entities

- **Perfil público**: Conjunto permitido de nombre artístico, slug, verificación pública, disponibilidad, ubicación y contenido vigente aprobado, sin datos privados o administrativos.
- **Regla de visibilidad**: Decisión común que combina email, identidad, revisión, publicación y principal apta; no depende de disponibilidad.
- **Slug actual y alias históricos**: Identificadores públicos legibles, independientes de IDs internos. El actual acompaña al nombre artístico y los anteriores conservan el acceso mediante redirección al mismo perfil, sujetos a visibilidad pública y sin reasignación a otras modelos.
- **Foto lógica y versión vigente**: Foto ordenada que puede ser principal y cuyo contenido público es su versión aprobada actual con watermark.
- **Bio vigente y características canónicas**: Presentación autorizada; las propuestas quedan fuera hasta aprobarse.
- **Tipo y servicios**: Modalidad y catálogo asociado con actividad, compatibilidad y orden público.
- **Ubicación pública**: Provincia, localidad compatible y referencia aproximada, separadas de domicilio y administración.

## Success Criteria

### Measurable Outcomes

- **SC-001**: El 100% de perfiles de prueba que cumple las cinco condiciones se abre sin login; el 100% que incumple alguna recibe el mismo 404 genérico que un perfil inexistente.
- **SC-002**: Cero campos o valores privados de prueba aparecen en respuestas públicas, metadata o recursos; ocultar la edad deja cero representaciones de esa edad en lo enviado al visitante.
- **SC-003**: El 100% de fotos entregadas es vigente, aprobado y con watermark; ninguna solicitud pública obtiene originales, pendientes, rechazadas o versiones retiradas.
- **SC-004**: El 100% de reemplazos pendientes o rechazados de bio, fotos y físicos conserva los valores aprobados anteriores; las aprobaciones se reflejan en la siguiente consulta.
- **SC-005**: El 100% de slugs actuales e históricos permanece reservado al mismo perfil. Tras cambiar el nombre artístico, el nuevo slug refleja el nombre y todos los enlaces anteriores redirigen al actual si el perfil es publicable; de lo contrario devuelven 404. Cero perfiles se resuelven por ID secuencial.
- **SC-006**: Todos los casos “No disponible” elegibles conservan acceso público; todos los casos “Solo Virtual” excluyen servicios presenciales incluso con datos inconsistentes.
- **SC-007**: A 360, 768 y 1440 px se recorre el 100% del contenido y controles sin desplazamiento horizontal, imágenes deformadas ni pérdida de foco visible, también con perfiles mínimos y textos largos.
- **SC-008**: En una revisión con cinco personas, al menos cuatro identifican en menos de 30 segundos nombre artístico, disponibilidad y tipo de publicación sin ayuda ni login. **Estado de alcance (2026-10-01): postergado, no evaluado y no requerido para el cierre técnico del MVP por decisión explícita del usuario.** Se conserva el criterio original para una etapa futura; esta excepción no modifica FR-001–031 ni permite declarar éxito de la prueba humana. El motivo es cerrar técnicamente el MVP sin realizar el estudio con participantes en esta etapa.
- **SC-009**: El 100% de flujos incluidos en regresión conserva su comportamiento esperado, con evidencia funcional y visual separada antes de entregar la implementación.

## Assumptions

### Decisiones iniciales para revisar en speckit-clarify

Las decisiones identificadas como confirmadas se registran en Clarifications; las demás siguen siendo supuestos comprobables.

| Tema | Supuesto inicial |
| --- | --- |
| Principal aprobada | Confirmado: obligatoria; 404 hasta disponer de principal aprobada con variante pública y watermark, sin imagen de reemplazo. |
| Slug | Confirmado: cambia con el nombre artístico y los enlaces anteriores redirigen al actual cuando el perfil sea publicable. |
| Físicos opcionales | Cabello, piel y tipo de cuerpo cuando existan como datos públicos canónicos; no agregar campos. |
| Localidad | Mostrarla cuando exista y corresponda a la provincia, además de provincia y zona disponibles. |
| Coordenadas/mapa | Confirmado: sólo texto, sin mapas ni coordenadas enviadas al navegador. Mapa para una feature posterior. |
| Máximo de fotos | Todas las permitidas por el límite central de 005, actualmente cinco; sin otro límite ni paginación. |
| Principal en galería | Sí, en su posición, además de destacarse. |
| Tipo como badge | Sí, etiqueta visible “Solo Virtual” o “Encuentros”. |
| Servicios inactivos | Desaparecen en la siguiente solicitud sin moderación adicional ni despublicación. |
| CTA/contacto | Fuera de alcance: no teléfono, WhatsApp, redes ni llamadas a contactar a la modelo. |

### Fuentes, dependencias y discrepancias verificadas

- Antecedentes: [001 autenticación](../001-model-registration-auth/spec.md), [002 administración](../002-admin-model-panel/spec.md), [003 identidad](../003-identity-document-validation/spec.md), [004 panel privado](../004-model-private-panel/spec.md), [005 fotografías](../005-model-photos/spec.md) y [006 detalles de perfil](../006-model-profile-details/spec.md), priorizando sus decisiones posteriores de privacidad y moderación.
- Se revisaron los contratos de [fotos](../005-model-photos/contracts/model-photos.md) y [perfil privado](../006-model-profile-details/contracts/account-profile.md), y los formularios actuales de registro y perfil. No se encontraron documentos separados identificables como formulario de creadoras, formulario modelos/virtual o documento funcional más reciente; no se les atribuyen requisitos no disponibles. El pedido actual es la fuente funcional explícita más reciente.
- La guía disponible está en [design.md](../../design.md); `docs/design.md` no existe al redactar esta spec. Se revisó [resources/css/styles.css](../../resources/css/styles.css), cuyos tokens difieren de algunos valores de la guía. Debe preservarse la identidad con estilos actuales conforme a la constitución, sin copiar valores obsoletos. No se mueve ni crea documentación de diseño en esta fase.
- El perfil actual no declara slug ni regla pública central. Ya existen fotos lógicas con versión aprobada vigente, bio vigente y físicos canónicos: se reutilizan sus significados y moderaciones.
- La entrega actual de imágenes exige autorización privada; no constituye todavía entrega anónima. Esta feature incluye habilitar entrega pública controlada de variantes aprobadas preservando los accesos privados. El thumbnail actual se genera antes de aplicar watermark y no puede presumirse publicable.
- Se asume que nombre artístico y textos canónicos destinados a publicación cumplen validaciones y moderaciones existentes. No se copian como sustitutos nombre real, nombre heredado de cuenta, WhatsApp ni ubicación libre heredada.
- La planificación deberá resolver el acceso público específico, la forma de la regla central reutilizable, la representación explícita de datos públicos y carga selectiva sin N+1. Debe evaluar unicidad/indexación de slug y los índices existentes de publicación, tipo y relaciones; cualquier índice adicional requiere justificación. No se prescribe una arquitectura nueva en esta spec.

### Alcance y continuidad

La fase documental inicial produjo únicamente `specs/007-public-model-profile/spec.md` y `checklists/requirements.md`, sin crear rama ni cambiar `.specify/feature.json`. La implementación y el cierre técnico posteriores se registran en tasks.md y validation.md; el contexto de esta feature sigue siendo explícitamente `specs/007-public-model-profile`.

La feature implementada abarca perfil individual diseñado, slugs, entrega segura de fotos, regla común y validaciones descritas. Quedan fuera directorio `/modelos`, buscador, filtros, home con modelos, destacados, ranking, favoritos, comentarios, ratings, mensajería, contacto/redes sociales, precios, pagos, planes, videos, historial de galerías, mapas de cualquier tipo, envío público de coordenadas, geolocalización por distancia y SEO avanzado.

008-public-model-directory y 009-home-model-showcase deberán reutilizar exactamente elegibilidad, slugs y representaciones públicas de principal, disponibilidad, ubicación y tipo establecidas aquí. Preparar esa reutilización no autoriza implementar esas páginas ahora.
