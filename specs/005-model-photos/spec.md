# Feature Specification: Gestión de fotografías de modelos

**Feature Branch**: `005-model-photos`

**Created**: 2026-09-17

**Status**: Draft

**Input**: User description: "Permitir que una modelo gestione sus fotografías desde /account y que administradores autorizados las moderen antes de su uso público."

## Clarifications

### Session 2026-09-17

- Q: ¿Cuál es el límite de cantidad y tamaño para las fotografías? → A: Máximo 5 fotografías por modelo; 5 MB por fotografía; las 5 son un máximo y no una cantidad obligatoria.
- Q: ¿Qué regla se aplica a la fotografía principal y al reemplazo por estado? → A: Sólo una fotografía `approved` puede ser principal; `pending`, `rejected` y `approved` pueden reemplazarse; toda nueva versión vuelve a `pending`; si se reemplaza la principal aprobada, la versión aprobada actual conserva la principalidad hasta que la nueva sea aprobada.
- Q: ¿Qué procesamiento de imagen se requiere? → A: Mínimo 800x800; entrada máxima 5000x5000; normalización conservando relación de aspecto; versión procesada con lado máximo 2000; thumbnail 400x400; marca de agua obligatoria en versiones públicas; original privado sin marca de agua y nunca público.

## Contexto y alcance

La feature agrega gestión privada de fotografías para modelos existentes. La modelo usa el panel Blade bajo `/account`; Filament continúa siendo exclusivo para administración. La validación de identidad, la revisión del perfil y la publicación son procesos independientes y no se modifican automáticamente por acciones sobre fotografías.

La primera versión no implementa videos, perfil público completo, directorio público ni funcionalidades sociales o comerciales.

## User Scenarios & Testing

### User Story 1 - Cargar fotografías propias (Priority: P1)

Una modelo autenticada y verificada con perfil puede entrar a “Mis fotos”, cargar imágenes válidas y verlas como pendientes de revisión.

**Why this priority**: Es la capacidad base para que una modelo pueda construir su galería y comenzar el flujo de moderación.

**Independent Test**: Con una modelo propietaria autenticada, cargar un JPEG, PNG y WebP válidos; confirmar que cada fotografía aparece en su galería con estado “En revisión” y que no queda disponible públicamente.

**Acceptance Scenarios**:

1. **Given** una modelo autenticada, verificada y con `ModelProfile`, **When** abre `/account/photos`, **Then** ve la sección “Mis fotos” con su galería y estado vacío si aún no tiene fotografías.
2. **Given** una modelo autorizada, **When** carga una imagen JPEG, PNG o WebP válida dentro de los límites configurados, **Then** se crea una fotografía propia con estado `pending`.
3. **Given** una modelo autorizada, **When** intenta cargar un video, PDF, SVG, ejecutable, archivo arbitrario, MIME falso o archivo sobredimensionado, **Then** la carga es rechazada con un mensaje claro y no se crea una fotografía.
4. **Given** una modelo que alcanzó el límite configurado, **When** intenta cargar otra fotografía, **Then** la operación es rechazada sin superar el límite.
5. **Given** una fotografía pendiente o rechazada, **When** una persona no autorizada intenta verla mediante una URL directa, **Then** no obtiene acceso público al archivo.

---

### User Story 2 - Organizar y mantener la galería propia (Priority: P1)

Una modelo puede ordenar sus fotografías, identificar la principal y realizar las operaciones permitidas por el estado de cada fotografía.

**Why this priority**: La organización permite presentar una galería coherente y corregir contenido rechazado sin afectar fotografías de otras modelos.

**Independent Test**: Crear varias fotografías propias, cambiar su orden, seleccionar una principal y probar reemplazo/eliminación según cada estado.

**Acceptance Scenarios**:

1. **Given** varias fotografías propias, **When** la modelo las reordena, **Then** las posiciones se persisten y el orden se conserva al recargar.
2. **Given** una modelo que selecciona una fotografía como principal, **When** la operación es válida según la regla definida, **Then** la nueva principal queda seleccionada y la anterior deja de serlo.
3. **Given** una fotografía rechazada, **When** la modelo consulta la galería, **Then** ve el motivo de rechazo y puede corregirla o reemplazarla según las reglas.
4. **Given** una fotografía `pending`, `rejected` o `approved`, **When** la modelo la reemplaza con una imagen válida, **Then** la nueva versión vuelve a `pending` y requiere moderación.
5. **Given** una fotografía propia eliminable, **When** la modelo la elimina, **Then** se elimina el registro y su archivo controlado, se recalculan posiciones cuando corresponde y se aplica la regla de principal definida.
6. **Given** una solicitud de ordenamiento, reemplazo, selección principal o eliminación que incluye una fotografía ajena, **When** se procesa, **Then** la operación es rechazada sin modificar datos ajenos.

---

### User Story 3 - Moderar fotografías desde administración (Priority: P1)

Un administrador verificado y autorizado puede revisar fotografías pendientes desde Filament, ver la imagen mediante acceso controlado y aprobarla o rechazarla con un motivo.

**Why this priority**: La moderación evita que fotografías no revisadas se utilicen públicamente y permite un circuito de control administrativo.

**Independent Test**: Ingresar como administrador verificado, abrir el detalle de una modelo y moderar una fotografía pendiente; confirmar auditoría y estados.

**Acceptance Scenarios**:

1. **Given** un administrador verificado, **When** abre la sección de fotografías de una modelo en Filament, **Then** ve metadata necesaria, estado y acceso controlado a las fotografías.
2. **Given** una fotografía `pending`, **When** el administrador la aprueba, **Then** pasa a `approved`, registra fecha y administrador revisor y limpia cualquier motivo anterior.
3. **Given** una fotografía `pending`, **When** el administrador la rechaza sin motivo, **Then** la operación es rechazada y no cambia el estado.
4. **Given** una fotografía `pending`, **When** el administrador la rechaza con motivo, **Then** pasa a `rejected`, registra fecha y administrador y la modelo puede ver el motivo.
5. **Given** un usuario común o administrador no verificado, **When** intenta revisar o descargar una fotografía privada, **Then** recibe una respuesta no autorizada.
6. **Given** una fotografía `approved`, **When** se modifica su estado desde una acción no permitida, **Then** la transición es rechazada server-side.

---

### User Story 4 - Separar moderación de publicación (Priority: P1)

El sistema mantiene independientes la moderación de fotografías, la validación de identidad, la revisión del perfil y la publicación del perfil.

**Why this priority**: Aprobar una fotografía no debe alterar controles de identidad, revisión ni visibilidad general del perfil.

**Independent Test**: Aprobar y rechazar fotografías mientras se observan los estados del perfil y confirmar que permanecen sin cambios.

**Acceptance Scenarios**:

1. **Given** una fotografía aprobada, **When** se completa la moderación, **Then** no cambia `email_verified_at`, `identity_status`, `review_status` ni `is_published`.
2. **Given** fotografías pendientes o rechazadas, **When** se consulta cualquier futura fuente pública de fotografías aprobadas, **Then** esas fotografías no son incluidas.
3. **Given** una fotografía aprobada, **When** se consulta la información del perfil, **Then** la aprobación no publica automáticamente el perfil.

---

### Edge Cases

- Una cuenta sin `ModelProfile`, no verificada o no autenticada no puede acceder a “Mis fotos”.
- Un `model_profile_id` enviado desde el cliente no cambia el perfil resuelto desde la sesión autenticada.
- Una fotografía con nombre engañoso, extensión válida y contenido inválido es rechazada.
- Una imagen con dimensiones inferiores a 800x800 px o superiores a 5000x5000 px es rechazada antes de persistirse.
- Un archivo parcialmente escrito o una imagen corrupta no se persiste como fotografía válida.
- Una posición duplicada, negativa, ausente o perteneciente a otra modelo no puede producir un ordenamiento inconsistente.
- Un intento concurrente de seleccionar principal debe terminar con como máximo una fotografía principal.
- Un fallo durante reemplazo debe conservar la fotografía anterior y no dejar un registro apuntando a un archivo inexistente.
- Un fallo durante eliminación no debe permitir borrar archivos fuera del almacenamiento de fotografías.
- Una ruta de archivo manipulada, absoluta o con traversal no puede leerse ni eliminarse.
- Una fotografía `pending` o `rejected` no puede obtener una versión pública ni una URL pública permanente.
- Los errores no deben mostrar rutas físicas, nombres internos, tokens ni información del administrador.

## Requirements

### Functional Requirements

- **FR-001**: El sistema MUST permitir que una modelo autenticada, verificada y con `ModelProfile` acceda a `/account/photos`.
- **FR-002**: El sistema MUST resolver el perfil de fotografías exclusivamente desde el usuario autenticado y MUST NOT aceptar `model_profile_id` controlado por el cliente.
- **FR-003**: El sistema MUST crear una entidad independiente `model_photos` relacionada con `ModelProfile`.
- **FR-004**: Cada fotografía MUST conservar como mínimo: perfil propietario, ruta lógica relativa, nombre original si es necesario, MIME, tamaño, ancho, alto, posición, principal, estado, motivo de rechazo, fecha de revisión, revisor y timestamps.
- **FR-005**: El sistema MUST aceptar únicamente JPEG, PNG y WebP válidos, verificando el MIME real, la integridad de imagen y los límites configurados.
- **FR-006**: El sistema MUST rechazar videos, PDF, SVG, ejecutables, archivos arbitrarios, MIME falsos, imágenes menores a 800x800 px o mayores a 5000x5000 px y archivos que superen 5 MB.
- **FR-007**: Cada nueva fotografía MUST comenzar en estado `pending`.
- **FR-008**: El sistema MUST permitir como máximo 5 fotografías por perfil; ese máximo no implica que una modelo deba cargar cinco. El valor MUST estar centralizado sin duplicarlo en controllers, services, requests, vistas o tests.
- **FR-009**: El almacenamiento MUST usar un disco lógico dedicado y privado/controlado, con rutas relativas y nombres físicos aleatorios que no contengan información personal.
- **FR-010**: Las fotografías `pending` y `rejected` MUST permanecer fuera del acceso público directo y MUST servirse sólo mediante una capa autorizada.
- **FR-011**: El sistema MUST impedir la exposición de `storage_path`, rutas absolutas, nombres físicos, datos del revisor y demás información administrativa a la modelo.
- **FR-012**: La modelo MUST poder ver el estado de cada fotografía como “En revisión”, “Aprobada” o “Rechazada”.
- **FR-013**: La modelo MUST poder ver el motivo de rechazo de sus propias fotografías y MUST NOT ver información de revisión interna innecesaria.
- **FR-014**: La modelo MUST poder ordenar sus propias fotografías y el orden MUST persistir.
- **FR-015**: El reordenamiento MUST validar ownership de todas las fotografías involucradas y MUST rechazar posiciones inválidas o fotografías ajenas.
- **FR-016**: Cada perfil MUST tener como máximo una fotografía principal; sólo una fotografía `approved` puede ser principal y toda selección MUST validarse server-side.
- **FR-017**: Si se elimina la fotografía principal aprobada, el sistema MUST seleccionar automáticamente la primera fotografía `approved` por `position`, o dejar el perfil sin principal si no existe otra aprobada.
- **FR-018**: El sistema MUST permitir reemplazar fotografías `pending`, `rejected` o `approved`; toda nueva versión MUST volver a `pending` y requerir moderación.
- **FR-019**: Un reemplazo MUST validar primero el archivo, almacenar el nuevo archivo, actualizar el registro de forma segura y eliminar el archivo anterior sólo después del éxito.
- **FR-020**: Un reemplazo fallido MUST conservar el archivo y registro anteriores, evitando estados parciales.
- **FR-021**: Al reemplazar, el sistema MUST limpiar `rejection_reason`, `reviewed_at` y `reviewed_by`.
- **FR-021a**: Si se reemplaza una fotografía `approved` que era principal, la versión aprobada actual MUST conservar `is_primary` hasta que la nueva versión sea aprobada; una versión `pending` MUST NOT convertirse en principal.
- **FR-022**: La eliminación MUST validar ownership, eliminar el registro y retirar únicamente el archivo perteneciente al disco controlado.
- **FR-023**: El sistema MUST proteger las operaciones de carga, reemplazo, orden, principal y eliminación con autenticación, verificación, ownership, autorización server-side y CSRF.
- **FR-024**: Filament MUST mostrar una sección de fotografías dentro del detalle de `ModelProfile` o una relación administrativa equivalente.
- **FR-025**: Sólo administradores verificados y autorizados MUST poder visualizar, aprobar o rechazar fotografías privadas.
- **FR-026**: Aprobar una fotografía MUST implementar únicamente `pending -> approved`, registrar `reviewed_at` y `reviewed_by`, y limpiar `rejection_reason`.
- **FR-027**: Rechazar una fotografía MUST implementar únicamente `pending -> rejected`, exigir `rejection_reason`, registrar `reviewed_at` y `reviewed_by`.
- **FR-028**: El sistema MUST rechazar transiciones de estado no definidas server-side.
- **FR-029**: Aprobar o rechazar fotografías MUST NOT modificar `email_verified_at`, `identity_status`, `review_status` ni `is_published`.
- **FR-030**: Sólo fotografías con estado `approved` MUST quedar disponibles para una futura consulta pública.
- **FR-030a**: El sistema MUST normalizar las imágenes conservando su relación de aspecto, generar una versión procesada cuyo lado máximo sea 2000 px y un thumbnail de 400x400 px.
- **FR-030b**: Las versiones destinadas a exposición pública MUST incluir marca de agua; el original sin marca de agua MUST conservarse en storage privado y MUST NOT exponerse públicamente.
- **FR-031**: La primera versión MUST NOT implementar videos, perfil público completo, favoritos, comentarios, ratings, pagos, planes, mensajería, OCR, reconocimiento facial ni almacenamiento externo definitivo.
- **FR-032**: La pantalla Blade de “Mis fotos” MUST incluir diseño completo, estados vacíos, errores, mensajes de éxito, responsive desktop/tablet/mobile, jerarquía visual y reutilización de `docs/design.md` y `resources/css/styles.css`.
- **FR-033**: Los tests MUST cubrir carga, validación, límites, ownership, privacidad, moderación, principal, orden, reemplazo, eliminación y regresión de features 001–004.

### Key Entities

- **ModelPhoto**: Fotografía asociada a un único `ModelProfile`. Incluye metadata técnica, orden, indicador de principal, estado de moderación y auditoría de revisión.
- **ModelProfile**: Perfil existente que posee muchas fotografías y puede tener como máximo una fotografía principal.
- **User**: Cuenta existente propietaria del perfil o administradora revisora.
- **Private photo storage**: Almacenamiento controlado que conserva las fotografías fuera de acceso público directo y permite cambiar el proveedor sin cambiar reglas de negocio.

### Data and lifecycle rules

- Estados permitidos: `pending`, `approved`, `rejected`.
- Transiciones obligatorias: carga → `pending`; `pending -> approved`; `pending -> rejected`.
- Una fotografía `pending`, `rejected` o `approved` puede reemplazarse; la nueva versión vuelve a `pending` y requiere nueva moderación.
- Sólo una fotografía `approved` puede ser principal. Al reemplazar la principal aprobada, la versión actual mantiene la principalidad hasta una nueva aprobación.
- Si se elimina la principal aprobada, se selecciona automáticamente la primera `approved` por `position`; si no existe, el perfil queda sin principal.
- Las transiciones inválidas, incluida cualquier modificación directa del estado desde el frontend de la modelo, MUST ser rechazadas.
- Las relaciones son: `ModelProfile hasMany ModelPhoto`; `ModelPhoto belongsTo ModelProfile`; `ModelPhoto reviewedBy belongsTo User`.
- La tabla debe asegurar una relación válida con `model_profiles`, índices para perfil/estado/posición y una representación que impida más de una principal por perfil.
- Los datos físicos se conservan sólo mediante una ruta lógica relativa al disco configurado.
- La imagen de entrada debe medir entre 800x800 y 5000x5000 px. La versión procesada conserva la relación de aspecto y tiene un lado máximo de 2000 px; además se genera un thumbnail de 400x400 px.
- Sólo las versiones públicas procesadas llevan marca de agua. El original sin marca de agua permanece privado y nunca se entrega públicamente.

## Success Criteria

### Measurable Outcomes

- **SC-001**: El 100% de las fotografías nuevas válidas queda visible para su propietaria con estado “En revisión” y sin acceso público directo.
- **SC-002**: El 100% de los intentos de acceso, carga, reemplazo, orden, principal o eliminación sobre fotografías ajenas es rechazado sin modificar datos.
- **SC-003**: El 100% de las fotografías rechazadas muestra a su propietaria el motivo de rechazo sin exponer datos internos del revisor.
- **SC-004**: El 100% de las acciones administrativas de aprobación o rechazo registra estado, fecha y administrador cuando la transición es válida.
- **SC-005**: En una revisión de los casos soportados, nunca existen dos fotografías principales simultáneas para el mismo perfil y ninguna fotografía `pending` es principal.
- **SC-006**: El 100% de las consultas públicas futuras de fotografías devuelve únicamente fotografías `approved`, ordenadas por posición.
- **SC-007**: Al menos el 95% de las modelos de prueba puede encontrar “Mis fotos”, entender el estado de una fotografía y localizar una acción disponible sin asistencia.
- **SC-008**: La pantalla de fotografías funciona en anchos de 360 px, tablet y desktop sin overflow horizontal ni controles inaccesibles.

## Assumptions

- El sistema reutiliza autenticación, verificación de email, ownership, Policies, Services, Filament y el panel Blade existentes.
- La aprobación de una fotografía no implica aprobación ni publicación del perfil.
- Todas las fotografías de esta primera versión requieren moderación administrativa.
- El almacenamiento inicial será privado/controlado mediante Laravel Storage/Flysystem; el proveedor físico podrá cambiarse posteriormente.
- El nombre original puede conservarse sólo como metadata necesaria para una descarga controlada y nunca se utilizará como nombre físico.
- Se generan una versión procesada de lado máximo 2000 px y un thumbnail de 400x400 px; sólo las versiones públicas procesadas reciben marca de agua.
- El original sin marca de agua se conserva únicamente en storage privado y nunca se entrega públicamente.
- La UI seguirá el sistema visual existente; si `docs/design.md` no estuviera disponible en el repositorio, se utilizará la guía visual vigente del proyecto y se dejará constancia antes del plan.

## Out of Scope

- Videos o streaming.
- Perfil público completo, directorio público o CDN definitiva.
- Favoritos, comentarios, ratings, pagos, planes o contenido premium.
- Mensajería o geolocalización.
- Reconocimiento facial, biometría u OCR.
- Almacenamiento externo definitivo.
- Nuevos roles/permisos completos.
