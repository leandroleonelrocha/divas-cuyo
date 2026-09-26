# Feature Specification: Registro y autenticación de modelos

**Feature Branch**: `001-model-registration-auth`

**Created**: 2026-09-14

**Status**: Draft

**Input**: User description: "Implementar el registro y autenticación de modelos. La modelo debe poder crear una cuenta proporcionando correo electrónico, contraseña, nombre público, número de WhatsApp y ubicación. Debe aceptar obligatoriamente los términos y condiciones y la política de privacidad. Debe poder iniciar sesión y cerrar sesión. Debe poder solicitar recuperación de contraseña por correo electrónico. El registro no debe publicar automáticamente el perfil. Una modelo autenticada solamente puede acceder a su propia información privada. No implementar todavía validación de identidad, fotografías, videos, moderación, pagos, publicación pública ni panel administrativo."

## Clarifications

### Session 2026-09-14

- Q: ¿Debe la modelo verificar su correo electrónico antes de poder iniciar sesión y acceder a su información privada? → A: Sí; exigir verificación de correo antes del primer inicio de sesión.
- Q: ¿Qué debe poder hacer una modelo si no encuentra o deja vencer el correo de verificación? → A: Permitir reenviar la verificación; los enlaces expiran y solo el último permanece válido.
- Q: ¿Cuánto tiempo debe permanecer válido un enlace de verificación de correo? → A: 24 horas.
- Q: ¿Cuánto tiempo debe permanecer válido un enlace de recuperación de contraseña? → A: 1 hora.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Crear una cuenta de modelo (Priority: P1)

Una modelo puede crear una cuenta proporcionando sus datos básicos y aceptando explícitamente los términos y condiciones y la política de privacidad. La cuenta queda registrada para continuar el proceso posteriormente, pero su perfil no se publica automáticamente.

**Why this priority**: Es el punto de entrada indispensable para que una modelo pueda utilizar cualquier capacidad futura de la plataforma.

**Independent Test**: Se puede completar el formulario con datos válidos y comprobar que la cuenta queda creada, que las aceptaciones obligatorias quedan registradas y que el perfil no aparece como publicado.

**Acceptance Scenarios**:

1. **Given** una visitante que no tiene una cuenta, **When** proporciona un correo electrónico válido, una contraseña válida, nombre público, número de WhatsApp, ubicación y acepta ambos documentos obligatorios, **Then** se crea una cuenta de modelo no verificada, se envían instrucciones para verificar el correo y se informa que debe completar esa verificación antes de iniciar sesión.
2. **Given** una visitante que intenta registrarse sin aceptar los términos y condiciones o la política de privacidad, **When** envía el formulario, **Then** el sistema rechaza el registro, identifica la aceptación faltante y no crea la cuenta.
3. **Given** una modelo que completa el registro, **When** busca su perfil por las vías públicas disponibles, **Then** no se muestra como perfil publicado.

### User Story 2 - Iniciar y cerrar sesión (Priority: P1)

Una modelo registrada puede iniciar sesión con su correo y contraseña, consultar su área autenticada y cerrar sesión para invalidar el acceso de esa sesión.

**Why this priority**: La autenticación protege los datos privados de la modelo y habilita el acceso seguro a sus futuras funciones.

**Independent Test**: Se puede registrar una cuenta, iniciar sesión con credenciales válidas, comprobar el acceso autenticado, cerrar sesión y comprobar que las áreas protegidas dejan de estar disponibles.

**Acceptance Scenarios**:

1. **Given** una cuenta registrada con correo verificado y credenciales correctas, **When** la modelo inicia sesión, **Then** queda autenticada y puede acceder a su propia información privada.
2. **Given** una cuenta registrada, **When** la modelo intenta iniciar sesión con una contraseña incorrecta, **Then** el acceso es rechazado y no se revela información sensible sobre la cuenta.
3. **Given** una modelo autenticada, **When** cierra sesión, **Then** la sesión deja de permitir acceso a información o acciones protegidas.
4. **Given** una cuenta registrada con correo no verificado y credenciales correctas, **When** la modelo intenta iniciar sesión, **Then** el acceso es rechazado y se le indica que debe verificar su correo.
5. **Given** una cuenta registrada con correo no verificado, **When** la modelo solicita reenviar la verificación, **Then** recibe un nuevo correo y cualquier enlace anterior deja de ser válido.

### User Story 3 - Recuperar el acceso por correo electrónico (Priority: P2)

Una modelo que no recuerda su contraseña puede solicitar un proceso de recuperación por correo electrónico y establecer una nueva contraseña mediante un enlace válido.

**Why this priority**: Reduce el bloqueo de cuentas y permite recuperar el acceso sin intervención administrativa, manteniendo el control de la cuenta en el correo registrado.

**Independent Test**: Se puede solicitar la recuperación, recibir un mensaje en el correo asociado, usar un enlace válido una sola vez para definir una nueva contraseña e iniciar sesión con ella.

**Acceptance Scenarios**:

1. **Given** un correo asociado a una cuenta, **When** la modelo solicita recuperar la contraseña, **Then** recibe instrucciones de recuperación por correo electrónico.
2. **Given** un correo no asociado a una cuenta, **When** alguien solicita recuperar la contraseña, **Then** el sistema muestra una respuesta genérica equivalente a la de una solicitud válida y no revela si el correo está registrado.
3. **Given** un enlace de recuperación válido y vigente, **When** la modelo define una nueva contraseña válida, **Then** la contraseña anterior deja de permitir el inicio de sesión y la nueva permite autenticarse.
4. **Given** un enlace de recuperación vencido, ya utilizado o con más de 1 hora desde su emisión, **When** alguien intenta usarlo, **Then** el sistema rechaza el cambio y solicita iniciar una nueva recuperación.

### User Story 4 - Mantener la privacidad de la información (Priority: P1)

Una modelo autenticada puede consultar únicamente su propia información privada. No puede consultar ni modificar la información privada de otra modelo y una persona no autenticada no puede acceder a información privada.

**Why this priority**: El correo, WhatsApp, ubicación y credenciales son datos sensibles; la privacidad es una condición esencial del registro y la autenticación.

**Independent Test**: Con dos cuentas distintas se comprueba que cada una puede acceder a sus propios datos, que no puede acceder a los datos de la otra y que una visita sin sesión recibe una respuesta de acceso restringido.

**Acceptance Scenarios**:

1. **Given** una modelo autenticada, **When** consulta sus datos de cuenta, **Then** recibe únicamente su propia información.
2. **Given** una modelo autenticada y el identificador de otra cuenta, **When** intenta consultar o modificar la información privada de esa cuenta, **Then** el sistema rechaza la operación.
3. **Given** una persona sin sesión, **When** intenta acceder a información privada de una modelo, **Then** el sistema rechaza el acceso y no entrega esos datos.

### Edge Cases

- Si el correo ya está asociado a una cuenta, el registro se rechaza sin crear una cuenta duplicada y se muestra un mensaje accionable.
- Si falta cualquier campo obligatorio, el registro se rechaza indicando qué dato debe completarse.
- Si el correo tiene un formato inválido o la contraseña no cumple la política mínima informada, el registro o cambio de contraseña se rechaza antes de guardar datos.
- Si se envían valores diferentes para una aceptación obligatoria, ambas aceptaciones deben estar presentes para completar el registro.
- Si se solicitan muchas recuperaciones consecutivas, el sistema aplica una respuesta segura y no expone el enlace ni la contraseña en la interfaz o en mensajes visibles.
- Si una modelo intenta usar una sesión inexistente, vencida o cerrada, debe volver a solicitar autenticación.
- Si una modelo solicita reenviar la verificación, el enlace anterior queda invalidado y el nuevo enlace tiene una vigencia de 24 horas.
- Si una modelo usa un enlace de verificación vencido o invalidado, el sistema rechaza la verificación y permite solicitar otro enlace.
- Los mensajes de autenticación y recuperación no deben confirmar si un correo existe cuando esa confirmación permita enumerar cuentas.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST permitir que una modelo cree una cuenta proporcionando correo electrónico, contraseña, nombre público, número de WhatsApp y ubicación.
- **FR-002**: El sistema MUST validar que el correo tenga un formato válido y no esté asociado a otra cuenta.
- **FR-003**: El sistema MUST aplicar una política de contraseña que exija al menos 8 caracteres y comunicarla antes de completar el registro o el cambio de contraseña.
- **FR-004**: El sistema MUST exigir la aceptación explícita de los términos y condiciones y de la política de privacidad para crear la cuenta.
- **FR-005**: El sistema MUST registrar que ambas políticas fueron aceptadas junto con la fecha de aceptación y una referencia a la versión vigente de cada documento.
- **FR-006**: El sistema MUST crear la cuenta en un estado no publicado y con el correo pendiente de verificación; completar el registro no debe hacer que el perfil sea visible públicamente.
- **FR-007**: El sistema MUST enviar instrucciones de verificación al correo proporcionado y MUST permitir iniciar sesión únicamente después de que la modelo complete una verificación válida.
- **FR-007A**: El sistema MUST permitir solicitar un nuevo correo de verificación para una cuenta no verificada y MUST invalidar el enlace anterior cuando se emita uno nuevo.
- **FR-007B**: El sistema MUST rechazar los enlaces de verificación vencidos o ya invalidados y MUST permitir iniciar una nueva solicitud de verificación.
- **FR-007C**: El sistema MUST hacer que cada enlace de verificación expire 24 horas después de su emisión.
- **FR-008**: El sistema MUST rechazar credenciales inválidas sin revelar si el correo o la contraseña fueron el dato incorrecto.
- **FR-009**: El sistema MUST permitir que una modelo autenticada cierre sesión y MUST impedir que esa sesión continúe accediendo a áreas protegidas.
- **FR-010**: El sistema MUST permitir solicitar la recuperación de contraseña mediante correo electrónico.
- **FR-011**: El sistema MUST enviar instrucciones de recuperación únicamente a la dirección asociada con la cuenta, sin revelar en pantalla si una dirección está registrada.
- **FR-012**: El sistema MUST permitir definir una nueva contraseña solamente mediante una instrucción de recuperación válida, vigente, no utilizada y emitida hace menos de 1 hora.
- **FR-013**: El sistema MUST invalidar la instrucción de recuperación después de utilizarla o cuando expire.
- **FR-014**: El sistema MUST restringir el acceso a información privada a la modelo propietaria de esa información y a las acciones autorizadas sobre su propia cuenta.
- **FR-015**: El sistema MUST rechazar el acceso de una modelo autenticada a la información privada de otra modelo.
- **FR-016**: El sistema MUST rechazar el acceso de personas no autenticadas a información privada.
- **FR-017**: El sistema MUST evitar exponer contraseñas, instrucciones de recuperación, aceptación de políticas u otros datos privados en mensajes públicos, respuestas no autorizadas o registros visibles para otras modelos.
- **FR-018**: Esta entrega MUST limitarse a registro, verificación de correo, inicio de sesión, cierre de sesión, recuperación de contraseña y protección de la información privada de la cuenta; no debe incluir validación de identidad, fotografías, videos, moderación, pagos, publicación pública ni panel administrativo.

### Key Entities

- **Cuenta de modelo**: Representa a una modelo registrada; contiene correo electrónico, estado de verificación del correo, contraseña protegida, nombre público, número de WhatsApp, ubicación y estado no publicado.
- **Verificación de correo**: Representa la confirmación de que la modelo controla la dirección registrada y habilita el primer inicio de sesión.
- **Enlace de verificación**: Representa una instrucción temporal para confirmar el correo; solo el enlace más reciente puede utilizarse y tiene una vigencia de 24 horas.
- **Aceptación de políticas**: Representa la aceptación obligatoria de los términos y condiciones y de la política de privacidad, incluyendo momento y versión aceptada.
- **Instrucción de recuperación**: Representa una autorización temporal asociada a una cuenta para definir una nueva contraseña; tiene una vigencia de 1 hora y no puede reutilizarse.
- **Sesión autenticada**: Representa el acceso activo de una modelo a sus propias áreas y datos privados.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Al menos el 95% de las modelos que ingresen datos válidos pueden completar el registro en menos de 2 minutos.
- **SC-002**: El 100% de los intentos de registro que omitan una de las dos aceptaciones obligatorias son rechazados y no crean una cuenta.
- **SC-003**: El 100% de las pruebas con dos cuentas confirman que una modelo no puede consultar ni modificar la información privada de la otra.
- **SC-004**: Al menos el 95% de las solicitudes de recuperación para cuentas existentes reciben instrucciones por correo en menos de 5 minutos, sin revelar la existencia de cuentas en la respuesta visible.
- **SC-005**: El 100% de las cuentas creadas durante esta entrega permanecen no publicadas hasta una acción futura explícita fuera del alcance de esta funcionalidad.
- **SC-006**: Al menos el 95% de las modelos que completen correctamente la recuperación pueden iniciar sesión con la nueva contraseña en el primer intento.
- **SC-007**: El 100% de los intentos de inicio de sesión de cuentas con correo no verificado son rechazados y no crean una sesión autenticada.

## Assumptions

- La plataforma opera en español y las etiquetas, validaciones y mensajes visibles para la modelo se presentan en español.
- La cuenta se identifica por un único correo electrónico; no se permite más de una cuenta con el mismo correo.
- Se considera suficiente una política inicial de contraseña de 8 caracteres como mínimo; requisitos más estrictos pueden definirse posteriormente con una necesidad de seguridad específica.
- La modelo proporciona una dirección de correo a la que tiene acceso para recibir la recuperación; no se implementa recuperación por WhatsApp en esta entrega.
- La modelo debe tener acceso al correo registrado para verificar la cuenta antes del primer inicio de sesión.
- Los enlaces de verificación expiran 24 horas después de su emisión y el reenvío invalida el enlace anterior.
- La aceptación de cada documento se registra contra una versión vigente definida por el negocio, sin implementar aún un flujo de reaceptación por cambios de versión.
- El nombre público, WhatsApp y ubicación forman parte de la información de la cuenta y permanecen protegidos mientras no exista una funcionalidad de publicación pública.
- La entrega no incluye validación de identidad, contenido multimedia, moderación, pagos, publicación pública ni funciones administrativas, incluso si esos conceptos aparecen relacionados con el futuro perfil de una modelo.
- Las pruebas y criterios de aceptación pueden verificar los envíos de correo mediante el mecanismo de prueba disponible en el entorno, sin requerir un proveedor de correo específico.
