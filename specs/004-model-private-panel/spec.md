# Feature Specification: Panel privado de la modelo y post-login

**Feature Branch**: `004-model-private-panel`

**Created**: 2026-09-16

**Status**: Draft

**Input**: User description: "crear el panel privado de la modelo y definir el comportamiento post-login"

## Clarifications

### Session 2026-09-16

- Q: ¿A dónde debe redirigirse una cuenta autenticada y verificada que no tiene `ModelProfile`? → A: A una pantalla interna controlada de perfil incompleto.
- Q: Cuando una modelo verificada llega al login desde una ruta interna, ¿debe volver siempre a `/account` o regresar a esa ruta interna? → A: Debe redirigirse siempre a `/account`.

## User Scenarios & Testing

### User Story 1 - Redirección después del login (Priority: P1)

Una modelo con email verificado inicia sesión y llega a su panel privado para continuar administrando su perfil. Una administradora con email verificado llega al panel interno de administración.

**Why this priority**: El destino posterior al login define el punto de entrada principal para cada tipo de usuario y evita que una modelo quede en una página pública sin contexto.

**Independent Test**: Iniciar sesión con una modelo verificada y comprobar que llega a `/account`; repetir con una administradora verificada y comprobar que llega a `/admin`; intentar con una modelo no verificada y comprobar que el bloqueo actual se mantiene.

**Acceptance Scenarios**:

1. **Given** una modelo autenticable con email verificado, **When** inicia sesión correctamente, **Then** es redirigida a `/account`.
2. **Given** una administradora con email verificado, **When** inicia sesión correctamente, **Then** es redirigida a `/admin`.
3. **Given** una modelo con email no verificado, **When** intenta iniciar sesión, **Then** conserva el bloqueo actual y no accede al panel privado.
4. **Given** una modelo verificada que llega al login desde cualquier ruta interna, **When** completa un login válido, **Then** siempre es redirigida a `/account`.
5. **Given** una cuenta verificada sin `ModelProfile`, **When** inicia sesión correctamente, **Then** llega a una pantalla interna de perfil incompleto sin acceder al perfil de otra persona.

---

### User Story 2 - Acceso privado a la cuenta (Priority: P1)

Una modelo autenticada puede abrir su panel privado y consultar la información propia. No puede consultar el panel de otra modelo ni acceder al panel sin autenticación.

**Why this priority**: El panel contiene información de cuenta, perfil y validación de identidad, por lo que el aislamiento entre modelos es una condición básica de privacidad.

**Independent Test**: Abrir `/account` como modelo autenticada, como invitada y como otra modelo; confirmar que sólo la propietaria obtiene el contenido privado.

**Acceptance Scenarios**:

1. **Given** una modelo autenticada, **When** visita `/account`, **Then** ve exclusivamente su propio panel.
2. **Given** una persona no autenticada, **When** visita `/account`, **Then** es enviada al flujo de login y no recibe datos privados.
3. **Given** una modelo autenticada, **When** intenta consultar el panel de otra cuenta, **Then** la solicitud es rechazada y no revela datos de la otra modelo.
4. **Given** una cuenta administradora autenticada, **When** visita `/account`, **Then** no se convierte en una vista de otra modelo ni se mezclan las superficies de administración y cuenta privada.

---

### User Story 3 - Resumen del perfil y sus estados (Priority: P1)

Una modelo puede ver desde su panel un resumen claro de sus datos básicos y del estado de cada proceso relevante: email, identidad, revisión del perfil y publicación.

**Why this priority**: La modelo necesita saber qué acción puede realizar a continuación sin confundir verificación de email, validación de identidad, aprobación del perfil o visibilidad pública.

**Independent Test**: Crear perfiles con distintas combinaciones de estados y comprobar que el panel muestra cada estado por separado con textos comprensibles.

**Acceptance Scenarios**:

1. **Given** una modelo con perfil existente, **When** abre `/account`, **Then** ve sus datos básicos y los cuatro estados separados.
2. **Given** una identidad pendiente o rechazada, **When** la modelo consulta el panel, **Then** encuentra un acceso claro a la carga/revisión de documentos y, si corresponde, el motivo de rechazo visible para ella.
3. **Given** una identidad aprobada, **When** la modelo consulta el panel, **Then** el estado de identidad aprobada no se presenta como email verificado, perfil aprobado o publicación activa.
4. **Given** un perfil no publicado, **When** la modelo consulta el panel, **Then** el estado de publicación se muestra independientemente del estado de revisión.

---

### User Story 4 - Accesos preparados para futuras áreas (Priority: P2)

Una modelo puede reconocer desde su panel los espacios destinados a futuras fotos, videos y otros datos, sin que esas áreas agreguen funcionalidades todavía.

**Why this priority**: Una estructura de navegación estable permite ampliar el panel gradualmente sin cambiar el punto de entrada de las modelos.

**Independent Test**: Abrir el panel y comprobar que los accesos futuros, si se muestran, están identificados como próximos o no disponibles y no permiten operaciones inexistentes.

**Acceptance Scenarios**:

1. **Given** una modelo autenticada, **When** observa la navegación del panel, **Then** puede distinguir el acceso actual a identidad de las áreas futuras.
2. **Given** un acceso futuro todavía no habilitado, **When** la modelo lo selecciona, **Then** recibe una respuesta controlada sin crear ni modificar fotos, videos u otros datos.

### Edge Cases

- Una cuenta con `is_admin = true` y perfil de modelo debe priorizar el destino administrativo `/admin` después de un login válido.
- Un usuario autenticado sin `ModelProfile` debe recibir un estado controlado y no un error que revele detalles internos.
- Una sesión expirada al abrir `/account` debe volver al login sin mostrar datos previamente cargados.
- Un intento de usar identificadores de otra cuenta en la URL no debe cambiar la cuenta mostrada ni revelar información.
- Un error de carga del perfil debe mostrar un mensaje general y no credenciales, tokens ni datos de identidad privada.
- El panel debe conservar una presentación utilizable en mobile sin overflow horizontal.

## Requirements

### Functional Requirements

- **FR-001**: El sistema MUST redirigir a una modelo con email verificado a `/account` después de un login válido.
- **FR-002**: El sistema MUST redirigir a una administradora con email verificado a `/admin` después de un login válido.
- **FR-003**: El sistema MUST mantener el bloqueo actual para modelos con email no verificado y no crear una sesión de panel utilizable.
- **FR-004**: El sistema MUST permitir que `/account` sea accesible sólo por una persona autenticada que sea la propietaria de la cuenta modelo.
- **FR-005**: El sistema MUST rechazar accesos cruzados a cuentas de otras modelos sin revelar datos privados.
- **FR-006**: El panel MUST mostrar los datos básicos del perfil propio disponibles actualmente.
- **FR-007**: El panel MUST mostrar de forma independiente el estado de `email_verified_at`, `identity_status`, `review_status` e `is_published`.
- **FR-008**: El panel MUST ofrecer un acceso visible a la sección privada de carga y revisión de documentos de identidad existente.
- **FR-009**: El panel MUST mostrar a la modelo el motivo de rechazo de identidad cuando exista y corresponda a su cuenta.
- **FR-010**: El sistema MUST mantener separadas las superficies privadas de modelos y el panel interno administrativo.
- **FR-011**: El panel de la modelo MUST permanecer en el frontend público existente y no utilizar la interfaz administrativa interna como su superficie principal.
- **FR-012**: El sistema MUST redirigir una cuenta verificada sin `ModelProfile` a una pantalla interna de perfil incompleto, con un mensaje general y sin acceso alternativo a otra cuenta.
- **FR-013**: El sistema MUST preservar las rutas, validaciones, verificación de email, carga/reemplazo de documentos y revisión administrativa existentes salvo los cambios necesarios en el destino post-login.
- **FR-014**: El sistema MUST redirigir siempre a las modelos verificadas a `/account`, sin respetar destinos alternativos enviados o conservados desde el cliente.
- **FR-015**: El panel MUST permitir reservar accesos para fotos, videos y otras áreas futuras sin implementar todavía esas funcionalidades.
- **FR-016**: Las áreas futuras MUST mostrar un estado controlado si todavía no están habilitadas y no deben crear ni modificar datos.
- **FR-017**: El panel MUST manejar de forma controlada una cuenta sin perfil, una sesión expirada o un fallo de carga sin exponer información sensible.
- **FR-018**: Las respuestas del panel MUST omitir contraseñas, tokens, rutas físicas de documentos y contenido de documentación privada.
- **FR-019**: El panel MUST ser usable en desktop, tablet y mobile sin overflow horizontal.

### Key Entities

- **Cuenta de usuario**: Identidad autenticable con email, estado de verificación y capacidad administrativa existente.
- **Perfil de modelo**: Datos básicos y estados independientes de la modelo asociada a una cuenta.
- **Sección privada de identidad**: Acceso existente para cargar, consultar y corregir la documentación de la propia modelo según sus estados permitidos.
- **Panel privado de la modelo**: Resumen de cuenta, perfil, estados y accesos actuales o futuros, restringido a su propietaria.

## Success Criteria

### Measurable Outcomes

- **SC-001**: El 100% de los logins válidos de modelos verificadas termina en `/account`, sin excepciones por destinos internos alternativos.
- **SC-002**: El 100% de los logins válidos de administradoras verificadas termina en `/admin` cuando no existe un destino interno permitido de mayor prioridad.
- **SC-003**: El 100% de los intentos de acceso no autenticados o cruzados a `/account` es rechazado sin datos privados en la respuesta.
- **SC-004**: En una revisión manual, el 100% de los perfiles probados muestra email, identidad, revisión y publicación como cuatro estados distinguibles.
- **SC-005**: Al menos el 95% de las modelos de prueba puede encontrar el acceso a identidad desde `/account` sin asistencia y en menos de 30 segundos.
- **SC-006**: El panel no expone rutas físicas, tokens, contraseñas ni contenido de documentos privados en ninguna respuesta de la superficie de cuenta.
- **SC-007**: El panel puede utilizarse en una pantalla mobile de 360 px de ancho sin desplazamiento horizontal ni superposición de contenido.

## Assumptions

- El login público existente continuará usando email y contraseña y mantendrá el requisito de email verificado.
- `users.is_admin = true` junto con email verificado seguirá identificando inicialmente a las administradoras; no se crea un sistema nuevo de roles.
- `/account` será el destino canónico del panel de modelo; los accesos privados existentes podrán conservar compatibilidad mientras se incorpora ese punto de entrada. Las cuentas verificadas sin `ModelProfile` usarán una pantalla interna separada de perfil incompleto.
- El panel privado reutilizará los datos, policies y sección de identidad existentes, sin duplicar documentación ni estados.
- El panel administrativo `/admin` no se reemplaza, rediseña ni mezcla con la experiencia de la modelo.
- Fotos, videos, planes, favoritos, comentarios y otras áreas de contenido quedan fuera de esta entrega; sólo se pueden reservar sus accesos visuales.
- Las pantallas futuras mantendrán las convenciones visuales documentadas en `docs/design.md` y `resources/css/styles.css`.
- La aplicación debe seguir funcionando con la configuración actual de Laravel, MySQL y el pipeline de assets.
