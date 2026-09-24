# Feature Specification: Validación de identidad con documentación privada

**Feature Branch**: `003-identity-document-validation`

**Created**: 2026-09-15

**Status**: Draft

**Input**: User description: "Permitir que una modelo cargue documentación privada para validar su identidad y mayoría de edad, y que administradores autorizados puedan revisarla antes de aprobar el perfil, manteniendo separado el email verificado, la revisión del perfil y la publicación."

## Clarifications

### Session 2026-09-15

- Q: ¿Qué formatos y tamaño máximo deben aceptarse para los documentos de identidad? → A: JPEG, PNG, WebP y PDF; máximo 5 MB por archivo.
- Q: ¿Qué política operativa inicial debe aplicarse a los documentos reemplazados o superados? → A: Eliminar de forma segura los documentos reemplazados y conservar sólo los vigentes.
- Q: ¿Qué administradores deben poder revisar y resolver la documentación de identidad en la primera versión? → A: Todos los administradores verificados identificados actualmente por `is_admin`, con autorización preparada para migrar luego a permisos específicos.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Completar y enviar la documentación (Priority: P1)

Una modelo con su email verificado puede cargar la documentación requerida para validar su identidad y mayoría de edad, consultar el estado del proceso y enviarlo a revisión.

**Why this priority**: La plataforma necesita una forma explícita y segura de recibir la información necesaria antes de aprobar un perfil.

**Independent Test**: Una modelo autenticada carga los tres documentos requeridos, los envía a revisión y observa el estado pendiente sin acceder a información de otra cuenta.

**Acceptance Scenarios**:

1. **Given** una modelo autenticada con un perfil propio y email verificado, **When** abre la sección de validación, **Then** puede ver el estado de identidad y los documentos que debe completar.
2. **Given** una modelo que carga documentos válidos de frente del DNI, dorso del DNI y selfie, **When** confirma el envío, **Then** la documentación queda pendiente de revisión y el estado de identidad pasa a `pending`.
3. **Given** una modelo que no completó uno o más documentos requeridos, **When** intenta enviar la documentación, **Then** el sistema impide el envío e indica qué documento falta.
4. **Given** una modelo sin email verificado o sin autorización sobre el perfil, **When** intenta cargar documentación, **Then** el sistema rechaza la operación y no almacena el archivo.

### User Story 2 - Corregir documentación rechazada (Priority: P1)

Una modelo cuya documentación fue rechazada puede consultar el motivo, reemplazar los archivos observados y volver a enviarlos a revisión.

**Why this priority**: La validación debe permitir corregir errores sin crear una cuenta nueva ni dejar bloqueado el proceso.

**Independent Test**: Rechazar una documentación con motivo, comprobar que la modelo ve el motivo, reemplazar el documento rechazado y reenviar el conjunto completo.

**Acceptance Scenarios**:

1. **Given** una validación rechazada, **When** la modelo consulta su sección privada, **Then** ve el estado `rejected` y el motivo de rechazo sin ver información interna del administrador.
2. **Given** una modelo con documentación rechazada, **When** reemplaza uno o más archivos y completa los documentos requeridos, **Then** puede volver a enviar la documentación y el estado pasa a `pending`.
3. **Given** una modelo con identidad aprobada, **When** intenta reemplazar o reenviar documentación sin una acción permitida por el flujo, **Then** el sistema conserva la validación aprobada y no crea una transición inválida.

### User Story 3 - Revisar documentación de forma privada (Priority: P1)

Una persona administradora autorizada puede consultar los documentos de una modelo, revisar su identidad y mayoría de edad, y resolver la validación con aprobación o rechazo motivado.

**Why this priority**: La aprobación administrativa es la barrera que evita que documentación sensible o perfiles no validados se consideren aptos.

**Independent Test**: Un administrador verificado abre una modelo con documentación pendiente, visualiza los archivos privados, aprueba o rechaza con motivo y verifica la auditoría resultante.

**Acceptance Scenarios**:

1. **Given** documentación pendiente y todos los documentos requeridos presentes, **When** un administrador autorizado abre el detalle, **Then** puede visualizar los documentos privados y el estado de identidad sin recibir URLs públicas permanentes.
2. **Given** documentación pendiente, **When** el administrador la aprueba, **Then** `identity_status` pasa a `approved`, se registran la fecha y el administrador revisor, y el perfil no se publica automáticamente.
3. **Given** documentación pendiente, **When** el administrador la rechaza sin motivo, **Then** el sistema impide la resolución y solicita un motivo.
4. **Given** documentación pendiente, **When** el administrador la rechaza con motivo, **Then** `identity_status` pasa a `rejected`, se registra la auditoría y el motivo queda visible para la modelo.
5. **Given** una cuenta administradora no autorizada o una cuenta común, **When** intenta ver, descargar, aprobar o rechazar documentación ajena, **Then** recibe una respuesta rechazada y no obtiene el archivo ni sus metadatos sensibles.

### User Story 4 - Mantener separadas identidad, revisión y publicación (Priority: P1)

La plataforma conserva separados el acceso al email, la validación de identidad, la aprobación del perfil y su visibilidad pública.

**Why this priority**: Confundir estos estados podría exponer perfiles sin validación suficiente o presentar una revisión administrativa como prueba de acceso al email.

**Independent Test**: Usar perfiles en distintos estados y comprobar que cada acción sólo modifica el estado que le corresponde y que las acciones finales requieren las condiciones correctas.

**Acceptance Scenarios**:

1. **Given** un email verificado, **When** la identidad está incompleta, pendiente o rechazada, **Then** el sistema no presenta ese email verificado como identidad aprobada.
2. **Given** una identidad aprobada y un perfil pendiente, **When** se aprueba la identidad, **Then** el perfil continúa pendiente y no publicado.
3. **Given** una identidad no aprobada, **When** un administrador intenta aprobar definitivamente el perfil, **Then** la acción es rechazada.
4. **Given** `identity_status = approved` y `review_status = approved`, **When** se solicita publicar, **Then** la publicación puede ejecutarse como acción separada.
5. **Given** `identity_status != approved` o `review_status != approved`, **When** se solicita publicar, **Then** la publicación es rechazada y `is_published` permanece falso.

### Edge Cases

- Si se carga un archivo inválido, con MIME real no permitido o mayor al límite, el sistema lo rechaza sin conservarlo como documento válido.
- Si el nombre original contiene rutas, caracteres peligrosos o información sensible, no controla el nombre ni la ubicación física del archivo.
- Si una modelo intenta acceder al identificador de otra modelo, no puede consultar, reemplazar, enviar ni descargar sus documentos.
- Si falta cualquier documento obligatorio, no se puede enviar la documentación a revisión ni aprobar la identidad.
- Si una solicitud de revisión llega mientras otra administradora resolvió el caso, la operación se vuelve a validar contra el estado actual y no aplica una transición obsoleta.
- Si una modelo reemplaza un archivo, el documento anterior no queda disponible para el frontend público y se elimina de forma segura según la política operativa inicial.
- Si una identidad rechazada se corrige, el reenvío vuelve a `pending` sin borrar el motivo histórico mostrado del rechazo anterior hasta que el nuevo resultado lo reemplace.
- Si una identidad aprobada tiene el perfil rechazado o despublicado, la aprobación de identidad se conserva separada de esos estados.
- Si se elimina el perfil o la cuenta mientras se revisa un documento, la visualización y las acciones terminan de forma controlada sin operar sobre un registro inexistente.
- Si el almacenamiento privado no está disponible, la carga o visualización falla sin crear referencias públicas ni registrar el contenido del archivo.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST permitir a una modelo autenticada y con email verificado gestionar únicamente la documentación de su propio perfil.
- **FR-002**: El sistema MUST requerir los documentos `dni_front`, `dni_back` y `selfie` para enviar una identidad a revisión.
- **FR-003**: El sistema MUST mantener los tipos de documento configurables y extensibles sin acoplar el flujo a una lista cerrada permanente.
- **FR-004**: El sistema MUST validar cada archivo por su contenido/MIME real, aceptar únicamente JPEG, PNG, WebP o PDF y limitarlo a 5 MB antes de almacenarlo.
- **FR-005**: El sistema MUST rechazar archivos inválidos, incompletos o que excedan el límite sin tratarlos como documentación válida.
- **FR-006**: El sistema MUST asignar nombres físicos aleatorios y no permitir que el nombre enviado por la modelo determine la ruta de almacenamiento.
- **FR-007**: El sistema MUST conservar, cuando sea necesario para la operación, el nombre original como metadato sin usarlo como nombre físico ni como ruta.
- **FR-008**: El sistema MUST representar la validación de identidad con los estados `incomplete`, `pending`, `approved` y `rejected`.
- **FR-009**: El sistema MUST permitir sólo las transiciones `incomplete -> pending`, `pending -> approved`, `pending -> rejected` y `rejected -> pending`.
- **FR-010**: El sistema MUST impedir transiciones inválidas y volver a validar el estado actual antes de resolver una revisión.
- **FR-011**: El sistema MUST permitir que una modelo reemplace documentación rechazada y vuelva a enviarla a revisión.
- **FR-012**: El sistema MUST mostrar a la modelo el motivo del rechazo asociado a su documentación sin exponer información interna del administrador.
- **FR-013**: El sistema MUST permitir aprobar identidad sólo cuando todos los documentos obligatorios estén presentes y sean aptos para revisión.
- **FR-014**: El sistema MUST requerir un motivo no vacío al rechazar una identidad.
- **FR-015**: El sistema MUST guardar como mínimo `reviewed_at`, `reviewed_by` y `rejection_reason` cuando correspondan a la resolución administrativa.
- **FR-016**: El sistema MUST permitir a administradores autorizados visualizar o descargar documentos sólo mediante una solicitud protegida y autorizada en servidor.
- **FR-017**: El sistema MUST rechazar el acceso a documentos y acciones de identidad de personas no autenticadas, usuarios comunes y administradores no autorizados.
- **FR-018**: El sistema MUST verificar ownership para que una modelo sólo pueda manipular documentos pertenecientes a su propio `model_profile`.
- **FR-019**: El sistema MUST almacenar documentación de identidad fuera de cualquier ubicación pública y no generar URLs públicas directas para ella.
- **FR-020**: El sistema MUST evitar exponer `storage_path` en vistas, respuestas públicas, APIs, exports o logs.
- **FR-021**: El sistema MUST proteger las operaciones de carga, reemplazo, envío, visualización y descarga contra CSRF, path traversal y nombres de archivo controlados por el usuario.
- **FR-022**: El sistema MUST conservar separados `email_verified_at`, `identity_status`, `review_status` e `is_published`, sin usar uno como sustituto de otro.
- **FR-023**: El sistema MUST impedir aprobar definitivamente un perfil si `identity_status` no es `approved`.
- **FR-024**: El sistema MUST impedir publicar un perfil si `identity_status != approved` o `review_status != approved`.
- **FR-025**: El sistema MUST mantener la aprobación de identidad separada de la publicación y no publicar automáticamente al aprobar identidad.
- **FR-026**: El sistema MUST mostrar en el panel administrativo los estados de email, identidad, revisión y publicación de forma distinguible.
- **FR-027**: El sistema MUST permitir a administradores autorizados aprobar o rechazar la identidad desde el panel, con confirmación y mensajes claros.
- **FR-028**: El sistema MUST autorizar inicialmente a administradores verificados identificados por `is_admin` y diseñar la autorización de documentos y acciones para poder reemplazar esa identificación por roles o permisos sin cambiar la separación de responsabilidades.
- **FR-029**: El sistema MUST definir una política operativa configurable para documentos antiguos; inicialmente debe eliminar de forma segura los documentos reemplazados y conservar sólo el conjunto vigente, sin asumir todavía una retención legal permanente.
- **FR-030**: El sistema MUST mantener funcionando el registro, login, verificación de email, recuperación de contraseña, cuenta pública y panel administrativo existentes.
- **FR-031**: El sistema MUST excluir documentos sensibles de cualquier superficie pública, foto pública de perfil, API pública, export público y mensaje de log.

### Key Entities *(include if feature involves data)*

- **Documento de identidad**: Archivo privado asociado a un `model_profile`, con tipo extensible, nombre físico aleatorio, nombre original opcional, MIME real, tamaño, estado, motivo de rechazo y auditoría de revisión.
- **Validación de identidad**: Estado agregado de la documentación de una modelo (`incomplete`, `pending`, `approved`, `rejected`) y su resolución administrativa actual.
- **ModelProfile**: Perfil existente al que pertenecen los documentos y cuyo `review_status` e `is_published` permanecen separados de la identidad.
- **User**: Cuenta existente que conserva email, `email_verified_at` y `is_admin`; su verificación de email no equivale a identidad aprobada.
- **Revisión de identidad**: Acción administrativa que aprueba o rechaza un conjunto de documentos, registra revisor y fecha, y exige motivo en caso de rechazo.

### Conceptual data requirements

La entidad separada de documentos debe contemplar como mínimo:

- identificador del documento;
- perfil de modelo propietario;
- tipo de documento extensible, inicialmente `dni_front`, `dni_back` y `selfie`;
- ruta privada y nombre físico aleatorio;
- nombre original sólo como metadato cuando sea necesario;
- MIME real y tamaño;
- estado del documento;
- motivo de rechazo;
- fecha y administrador revisor;
- fechas de creación y actualización.

El perfil debe conservar un estado agregado de identidad y su auditoría correspondiente. La especificación no fija todavía una política legal permanente de retención: la solución debe permitir documentar o configurar si los documentos reemplazados, rechazados o superados se conservan, se aíslan o se eliminan, respetando cualquier obligación legal que se defina posteriormente.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los intentos de acceso de usuarios no autorizados a documentos o acciones de identidad son rechazados sin entregar el archivo ni su ruta privada.
- **SC-002**: El 100% de los envíos con los tres documentos obligatorios válidos llega a estado pendiente y el 100% de los envíos incompletos o inválidos es bloqueado con indicación del problema.
- **SC-003**: El 100% de las decisiones de aprobación o rechazo registra responsable y fecha; el 100% de los rechazos registra un motivo visible para la modelo.
- **SC-004**: El 100% de las transiciones de identidad ejecutadas coincide con las cuatro transiciones permitidas y ninguna aprobación de identidad activa publicación automáticamente.
- **SC-005**: El 100% de los perfiles con identidad no aprobada permanece impedido de publicarse, aunque el email esté verificado o el perfil tenga otra información completa.
- **SC-006**: Al menos el 95% de las modelos puede corregir y reenviar una documentación rechazada sin crear una cuenta nueva ni perder el motivo recibido.
- **SC-007**: El 100% de los archivos almacenados de identidad permanece fuera de ubicaciones públicas y no aparece como URL directa en las superficies públicas.
- **SC-008**: En pruebas de regresión, el registro, login, verificación de email, recuperación de contraseña, cuenta pública y panel existente mantienen sus flujos actuales.

## Assumptions

- La autenticación pública y el perfil existente se reutilizan; esta feature agrega la gestión de identidad sin reemplazar el frontend público por el panel administrativo.
- Sólo una modelo con cuenta autenticada, perfil propio y email verificado puede cargar o enviar documentación.
- La mayoría de edad se acredita inicialmente mediante la documentación solicitada; no se incluye reconocimiento facial, OCR ni validación contra organismos externos.
- Los tres documentos iniciales son frente del DNI, dorso del DNI y selfie; los tipos se administran como valores configurables para permitir ampliaciones futuras.
- Se establece un único estado agregado de identidad por perfil, aunque puedan existir documentos versionados o reemplazados para conservar trazabilidad operativa.
- La política final de retención y eliminación dependerá de una definición legal/de negocio posterior; mientras tanto, la decisión operativa documentada es eliminar de forma segura los documentos reemplazados y conservar sólo los vigentes.
- Todos los administradores identificados por `users.is_admin` y con email verificado pueden revisar identidad en la primera versión; las reglas se expresan de modo que puedan migrarse a roles o permisos específicos.
- Aprobar identidad no aprueba el perfil ni publica el perfil; aprobar/rechazar perfil y publicar/despublicar siguen siendo responsabilidades separadas.
- Los formatos admitidos son JPEG, PNG, WebP y PDF, con un máximo de 5 MB por archivo; el período de retención se definirá como configuración operativa antes de implementar, manteniendo validación estricta y mensajes claros.
- No se agregan fotos públicas de perfil, videos, planes, favoritos, comentarios, biometría, pagos ni auditoría histórica avanzada.
