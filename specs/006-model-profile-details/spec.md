# Feature Specification: Detalles ampliados del perfil de modelo

**Feature Branch**: `006-model-profile-details`

**Created**: 2026-09-25

**Status**: Draft

**Input**: User description: "Permitir que cada modelo complete y administre sus datos privados, información pública, disponibilidad, ubicación, tipo de publicación, servicios y biografía moderada sin romper las funcionalidades existentes."

## Contexto y alcance

La feature amplía el perfil existente de una modelo separando claramente la cuenta de autenticación, los datos privados administrativos, los datos destinados a publicación y la configuración de tipo de publicación y servicios.

La modelo podrá administrar su propio perfil desde `/account/profile`. Administración podrá consultar datos privados autorizados, gestionar catálogos, revisar biografías y consultar el historial de cambios de tipo desde Filament. Los datos sensibles seguirán protegidos y la información pública sólo podrá exponerse según las reglas vigentes de identidad, moderación e `is_published`.

La primera versión no implementa pagos, precios, directorio público, mapa definitivo, búsqueda geográfica, redes sociales ni nuevas funcionalidades de contacto.

## Clarifications

### Session 2026-09-25

- Q: ¿Qué campos públicos deben conservarse y cómo deben almacenarse las medidas? → A: Altura, peso, medidas, ojos y nacionalidad son obligatorios; cabello, piel y tipo de cuerpo son opcionales; las medidas se guardan como texto validado.
- Q: ¿Qué debe ocurrir cuando una modelo cambia de “Encuentros” a “Solo Virtual”? → A: El cambio es inmediato; conserva servicios virtuales y desactiva automáticamente los servicios presenciales con confirmación previa, sin aprobación administrativa.
- Q: ¿Cómo deben gestionarse la provincia y la localidad? → A: Ambas deben pertenecer a catálogos con IDs relacionados; las tablas quedan preparadas para una futura carga desde un archivo JSON de provincias y localidades.
- Q: Después de validar la identidad, ¿quién puede modificar el nombre real y la fecha de nacimiento, y los cambios físicos públicos requieren moderación? → A: La modelo puede modificar los datos reales, pero el cambio dispara una nueva validación; los cambios físicos públicos requieren moderación.

## User Scenarios & Testing

### User Story 1 - Completar datos privados y públicos (Priority: P1)

Una modelo autenticada puede completar sus datos privados y la información que desea utilizar en su publicación, manteniendo ambas categorías separadas.

**Why this priority**: El perfil necesita una fuente confiable para administración y otra claramente delimitada para publicación, sin exponer datos reales o administrativos.

**Independent Test**: Completar nombre real, fecha de nacimiento, nombre artístico, características físicas y nacionalidad; verificar que la modelo puede revisar sus datos y que una consulta pública no contiene información privada.

**Acceptance Scenarios**:

1. **Given** una modelo autenticada con perfil propio, **When** guarda datos privados válidos, **Then** quedan asociados a su perfil y se muestran sólo en contextos autorizados.
2. **Given** una modelo autenticada, **When** guarda datos públicos válidos, **Then** se conservan como información de publicación sin copiar el nombre real a `stage_name`.
3. **Given** una cuenta sin perfil o un usuario no autenticado, **When** intenta acceder al formulario, **Then** no puede leer ni modificar datos de perfil.
4. **Given** una solicitud que incluye `user_id` o `model_profile_id` de otra persona, **When** se procesa, **Then** se rechaza y no modifica ningún perfil.

### User Story 2 - Gestionar edad pública y disponibilidad (Priority: P1)

Una modelo puede calcular su edad real a partir de su fecha de nacimiento, decidir si muestra una edad pública válida y cambiar su disponibilidad informativa.

**Why this priority**: La edad pública debe ser controlable sin alterar el dato real, y la disponibilidad debe comunicar el estado actual sin despublicar ni alterar moderaciones.

**Independent Test**: Configurar una edad pública entre la edad real y cinco años menos, ocultarla y cambiar disponibilidad; comprobar la representación pública y la invariancia de `is_published`, `review_status` e `identity_status`.

**Acceptance Scenarios**:

1. **Given** una fecha de nacimiento válida, **When** el sistema calcula la edad real, **Then** la edad se deriva dinámicamente de la fecha actual y no se persiste como valor fijo.
2. **Given** una modelo cuya edad real es 40, **When** define `public_age` entre 35 y 40, **Then** el valor es aceptado.
3. **Given** una `public_age` mayor que la edad real o menor que cinco años por debajo, **When** intenta guardarla, **Then** la operación es rechazada sin modificar `birth_date`.
4. **Given** `show_age = false`, **When** se presenta el perfil públicamente, **Then** la edad no se muestra; administración autorizada continúa viendo la edad real calculada.
5. **Given** un perfil publicado, **When** la modelo cambia `availability_status` a `unavailable`, **Then** se muestra el estado informativo sin modificar publicación, revisión, identidad ni tipo.

### User Story 3 - Gestionar ubicación aproximada (Priority: P1)

Una modelo puede seleccionar Mendoza, San Juan o San Luis y agregar localidad o referencia aproximada sin revelar su domicilio exacto.

**Why this priority**: La ubicación es necesaria para orientar la publicación, pero debe reducir el riesgo de exposición de la dirección personal.

**Independent Test**: Guardar cada provincia válida, cambiar de provincia conservando el perfil y verificar que nunca se muestra una dirección precisa.

**Acceptance Scenarios**:

1. **Given** una modelo con perfil, **When** selecciona Mendoza, San Juan o San Luis, **Then** la provincia es aceptada.
2. **Given** una provincia fuera del alcance inicial, **When** se envía al servidor, **Then** se rechaza aunque no esté visible en el selector.
3. **Given** una modelo que cambia de provincia, **When** confirma el cambio, **Then** conserva perfil, fotografías, moderaciones, bio, servicios e identidad.
4. **Given** una ubicación aproximada, **When** se presenta públicamente, **Then** sólo se muestran provincia, localidad, zona, referencia o coordenadas aproximadas según corresponda; nunca domicilio, piso, departamento ni dirección exacta.

### User Story 4 - Seleccionar tipo de publicación y servicios compatibles (Priority: P1)

Una modelo puede seleccionar un tipo de publicación y servicios disponibles según ese tipo, con validación del servidor.

**Why this priority**: El tipo determina qué servicios pueden ofrecerse y debe evitar combinaciones incompatibles aunque alguien manipule el formulario.

**Independent Test**: Seleccionar “Solo Virtual” y comprobar que un servicio presencial es rechazado; cambiar a “Encuentros”, conservar servicios virtuales y habilitar servicios presenciales.

**Acceptance Scenarios**:

1. **Given** el tipo “Solo Virtual”, **When** la modelo selecciona servicios virtuales, **Then** la selección es aceptada.
2. **Given** el tipo “Solo Virtual”, **When** intenta asociar un servicio presencial mediante una solicitud manipulada, **Then** se rechaza server-side.
3. **Given** el tipo “Encuentros”, **When** la modelo selecciona servicios virtuales y presenciales, **Then** las asociaciones válidas se conservan.
4. **Given** un cambio de “Solo Virtual” a “Encuentros”, **When** se confirma, **Then** se conservan servicios virtuales, se habilitan presenciales y no se crea un perfil nuevo ni se pierden fotos, bio, ubicación o identidad.
5. **Given** un servicio inactivo o inexistente, **When** se intenta seleccionar, **Then** se rechaza y no se alteran las asociaciones válidas existentes.

### User Story 5 - Conservar historial de cambios de tipo (Priority: P1)

Una modelo o una persona administradora autorizada puede cambiar el tipo de publicación mediante un flujo centralizado y cada transición queda registrada.

**Why this priority**: El historial será necesario para futuras reglas de pagos y permite explicar qué tipo tuvo la modelo en cada momento.

**Independent Test**: Ejecutar cambios Virtual → Encuentros → Virtual y verificar el tipo actual, los eventos históricos, origen, actor y fecha.

**Acceptance Scenarios**:

1. **Given** un tipo actual diferente del solicitado, **When** se confirma el cambio autorizado, **Then** se actualiza el tipo actual y se registra el tipo anterior, nuevo, actor, origen y fecha.
2. **Given** un cambio al mismo tipo, **When** se procesa, **Then** se rechaza o no-opera sin crear historial redundante.
3. **Given** un cambio de tipo, **When** se completa, **Then** no se modifican `identity_status`, `review_status` ni `is_published`.
4. **Given** un intento de actualizar `publication_type_id` fuera del flujo autorizado, **When** se procesa, **Then** se rechaza sin dejar un estado parcial.

### User Story 6 - Crear y moderar biografías versionadas (Priority: P1)

Una modelo puede redactar y reenviar su biografía, mientras administración revisa versiones sin reemplazar la versión pública aprobada antes de tiempo.

**Why this priority**: La presentación pública requiere moderación y una versión aprobada estable mientras una nueva propuesta está pendiente.

**Independent Test**: Crear una bio aprobada, enviar una nueva versión, verificar que la anterior sigue pública, aprobar o rechazar la nueva y revisar la auditoría.

**Acceptance Scenarios**:

1. **Given** una modelo con bio aprobada, **When** modifica el texto, **Then** se crea una nueva versión `pending` y la bio aprobada continúa siendo la pública.
2. **Given** una bio `pending`, **When** administración la aprueba, **Then** pasa a `approved` y se convierte en `current_bio_id`, registrando fecha y revisor.
3. **Given** una bio `pending`, **When** administración la rechaza con motivo, **Then** queda `rejected`, conserva el motivo y la bio aprobada anterior continúa pública.
4. **Given** una bio rechazada, **When** la modelo corrige y reenvía el texto, **Then** se crea una nueva versión pendiente sin sobrescribir la evidencia anterior.
5. **Given** una modelo intenta aprobar su propia bio o la de otra modelo, **When** se procesa, **Then** se rechaza.

### User Story 7 - Administrar el perfil desde `/account/profile` y Filament (Priority: P2)

La modelo cuenta con una pantalla organizada y responsive, y administración ve secciones separadas para datos privados, publicación, tipo/servicios y biografía.

**Why this priority**: Una organización clara reduce errores y evita confundir información privada con la que se publica.

**Independent Test**: Abrir la pantalla en desktop y mobile, completar cada sección, comprobar mensajes de éxito/error y revisar el equivalente administrativo.

**Acceptance Scenarios**:

1. **Given** una modelo autorizada, **When** abre `/account/profile`, **Then** ve secciones para datos privados, publicación, disponibilidad, ubicación, tipo, servicios y presentación.
2. **Given** una persona administradora autorizada, **When** abre el recurso del perfil, **Then** ve datos privados, datos públicos, tipo/servicios, historial y biografías en secciones diferenciadas.
3. **Given** una pantalla sin datos, error de validación o guardado exitoso, **When** la persona interactúa, **Then** recibe estado vacío, error o confirmación comprensible y accesible.
4. **Given** viewport mobile o desktop, **When** se usa el formulario, **Then** no hay overflow horizontal ni controles inaccesibles.

### Edge Cases

- La edad real debe considerar cumpleaños y años bisiestos según una única regla de calendario, sin aceptar fechas futuras.
- Cambiar `public_age` no debe alterar `birth_date`; ocultar la edad no debe borrar `public_age`.
- `availability_status` sólo admite `available` y `unavailable` y nunca activa/desactiva publicación ni moderación.
- Una provincia inválida, localidad malformada o coordenada fuera de límites permitidos no debe persistirse.
- Una dirección exacta enviada en el texto de ubicación debe rechazarse o normalizarse según la regla de privacidad definida antes de implementarla.
- Un servicio presencial asociado a un tipo virtual debe eliminarse o impedirse en una operación atómica; nunca debe quedar una combinación inválida.
- Un cambio de tipo concurrente no debe generar dos tipos actuales contradictorios ni historial incompleto.
- Desactivar un servicio no debe borrar silenciosamente su historial de asociaciones ni dejarlo seleccionable para nuevos perfiles.
- Una bio pendiente o rechazada nunca debe aparecer como bio pública ni reemplazar una aprobada.
- Errores y respuestas públicas no deben incluir nombre real, fecha de nacimiento, teléfono privado, documentos, revisor, motivo interno o IDs manipulables.
- La edición de ubicación o disponibilidad no debe perder relaciones existentes con fotos, identidad, moderación, bio o servicios.

## Requirements

### Functional Requirements

- **FR-001**: El sistema MUST mantener en `users` únicamente la información de cuenta y autenticación existente y MUST NOT agregar allí características físicas, servicios, tipo de publicación, biografía, disponibilidad o ubicación pública.
- **FR-002**: Cada `ModelProfile` MUST poder tener un único conjunto de datos privados separado, con nombre real, fecha de nacimiento, datos físicos administrativos, nacionalidad y teléfono privado según los campos existentes o aprobados.
- **FR-003**: Los datos privados MUST ser accesibles únicamente a la propia modelo en el contexto autorizado, administración autorizada y validaciones internas; MUST NOT aparecer en respuestas públicas ni serializaciones indiscriminadas.
- **FR-004**: La edad real MUST calcularse dinámicamente desde `birth_date`; no debe almacenarse como un valor fijo ni aceptar fechas de nacimiento futuras.
- **FR-005**: El perfil MUST soportar `stage_name` como nombre público independiente del nombre real y MUST NOT completar automáticamente el nombre artístico con datos privados.
- **FR-006**: El perfil MUST soportar `public_age` opcional y `show_age`; `public_age` MUST estar entre la edad real y cinco años menos inclusive y MUST NOT modificar `birth_date`.
- **FR-007**: Si `show_age` es falso, la edad MUST omitirse de toda presentación pública, mientras administración autorizada podrá consultar la edad real calculada.
- **FR-008**: El perfil MUST exigir altura, peso, medidas, color de ojos y nacionalidad/origen como datos públicos obligatorios; cabello, color de piel y tipo de cuerpo MUST conservarse como campos opcionales; `measurements` MUST almacenarse como texto validado.
- **FR-009**: El perfil MUST soportar `availability_status` con sólo `available` y `unavailable`, labels “Disponible” y “No disponible”, y edición manual desde `/account`.
- **FR-010**: Cambiar disponibilidad MUST NOT modificar `is_published`, `review_status`, `identity_status` ni `publication_type_id`.
- **FR-011**: La ubicación MUST conservar `province_id` y `locality_id` como referencias a catálogos administrables y relacionados; las tablas deben quedar preparadas para una futura carga desde JSON, sin acoplar la feature a un proveedor externo.
- **FR-012**: La ubicación pública MUST ser aproximada y MUST NOT exponer domicilio exacto, altura, piso, departamento ni dirección precisa; la representación deberá quedar preparada para un mapa futuro sin elegir todavía proveedor.
- **FR-013**: Cambiar provincia o localidad MUST conservar el mismo perfil, fotos, moderaciones, servicios, bio, identidad y estado de publicación.
- **FR-014**: `PublicationType` MUST incluir “Solo Virtual” (`virtual`) y “Encuentros” (`encounters`), con estado activo y una indicación de si admite servicios presenciales.
- **FR-015**: Un perfil MUST tener un único tipo de publicación actual y el tipo MUST representar una modalidad de publicación, no una categoría permanente de la modelo.
- **FR-016**: `Service` MUST soportar nombre, slug, `service_type` (`virtual` o `in_person`), activación y orden; los servicios MUST relacionarse con perfiles mediante una asociación many-to-many.
- **FR-017**: Un perfil de tipo virtual MUST poder asociar únicamente servicios virtuales; un perfil de tipo encounters MUST poder asociar servicios virtuales y presenciales.
- **FR-018**: Las reglas de compatibilidad de servicios MUST validarse server-side y no depender sólo de ocultar o deshabilitar controles en la interfaz.
- **FR-019**: El cambio de tipo MUST ejecutarse como una operación transaccional centralizada que valide el cambio, actualice el tipo, aplique reglas de servicios y registre el historial.
- **FR-020**: Al cambiar de virtual a encounters, el sistema MUST conservar servicios virtuales, permitir servicios presenciales y MUST NOT crear otro perfil ni perder fotos, bio, ubicación o identidad.
- **FR-021**: Al cambiar de encounters a virtual, el sistema MUST solicitar confirmación antes de desactivar automáticamente los servicios presenciales seleccionados; tras confirmarla, MUST conservar los servicios virtuales, desactivar los presenciales y aplicar el cambio sin aprobación administrativa adicional.
- **FR-022**: Cada cambio real de tipo MUST registrar perfil, tipo anterior opcional, tipo nuevo, actor opcional, origen (`model`, `admin` o `system`), fecha y motivo opcional; un no-op no debe crear historial.
- **FR-023**: El sistema MUST permitir consultar el historial de tipo sólo en contextos administrativos autorizados y MUST conservar eventos anteriores aunque cambien nombres o estados actuales.
- **FR-024**: El perfil MUST permitir crear múltiples versiones de bio con contenido, estado, fecha de revisión, revisor y motivo de rechazo opcional; los estados iniciales son `pending`, `approved` y `rejected`.
- **FR-025**: Una nueva bio MUST iniciar como `pending`; mientras exista una versión pendiente, la última versión aprobada continuará siendo la pública mediante `current_bio_id`.
- **FR-026**: Sólo administración autorizada podrá aprobar o rechazar bios; aprobar deberá promover la versión a `current_bio_id`, y rechazar deberá guardar `rejection_reason` sin reemplazar la bio aprobada anterior.
- **FR-027**: Una modelo podrá corregir y reenviar una bio rechazada creando una nueva versión pendiente; no podrá aprobarla ni modificar la auditoría de revisión.
- **FR-028**: Una modelo podrá modificar nombre real y fecha de nacimiento después de una identidad validada, pero cada cambio deberá iniciar una nueva validación de identidad y no podrá considerarse nuevamente validado hasta completar ese proceso.
- **FR-029**: Los cambios en altura, peso, medidas, ojos, cabello, piel o tipo de cuerpo públicos deberán iniciar un estado de moderación antes de reflejarse públicamente; la versión pública anterior deberá conservarse mientras el cambio esté pendiente.
- **FR-030**: La propia modelo MUST poder editar únicamente su perfil resuelto desde la sesión autenticada; el servidor MUST ignorar o rechazar `user_id` y `model_profile_id` controlados por el cliente.
- **FR-031**: Policies, Form Requests y Services MUST proteger lectura y escritura, ownership, cambios de tipo, asociaciones de servicios y moderación de bios y datos físicos.
- **FR-032**: Una modelo MUST NOT consultar o modificar datos privados, servicios, ubicación, tipo o bios de otra modelo, ni aprobar su propia bio o sus propios cambios físicos.
- **FR-033**: El recurso administrativo del perfil MUST separar visualmente datos privados, información pública, tipo/servicios, historial de tipo y biografías; no debe editar documentación de identidad desde esta feature.
- **FR-034**: `/account/profile` MUST incluir secciones para datos privados, publicación, edad, disponibilidad, ubicación, tipo, servicios y presentación, con estados vacíos, mensajes de éxito/error, accesibilidad y comportamiento responsive.
- **FR-035**: Toda interfaz Blade nueva o modificada MUST reutilizar `docs/design.md` y `resources/css/styles.css`, sin incorporar un framework CSS nuevo; la validación debe incluir desktop, tablet y mobile.
- **FR-036**: La persistencia MUST reutilizar campos existentes y evitar duplicados; las relaciones esperadas son perfil–detalles privados, perfil–bios, perfil–tipo, perfil–servicios e historial de tipo, además de las relaciones existentes con fotos e identidad.
- **FR-037**: La feature MUST NOT implementar pagos, comprobantes, precios de publicación o servicios, moneda, planes, facturación, directorio público, filtros públicos, mapa definitivo, búsqueda por distancia, favoritos, comentarios, ratings, mensajería ni videos.
- **FR-038**: Los tests MUST cubrir privacidad, ownership, edad, datos públicos, disponibilidad, ubicación, tipos, compatibilidad de servicios, cambios de tipo, historial, moderación de bios y datos físicos, revalidación de identidad y regresión de registro, login, verificación, `/account`, identidad, Filament, fotos e indicadores existentes.

### Key Entities

- **User**: Cuenta existente para autenticación, verificación y administración; no contiene atributos de publicación.
- **ModelProfile**: Perfil de la modelo con datos públicos, tipo actual, disponibilidad, ubicación, bio actual y relaciones existentes.
- **ModelProfilePrivateDetail**: Información real y administrativa protegida, incluida fecha de nacimiento desde la que se calcula la edad real.
- **Province**: Catálogo de provincias identificadas por ID y nombre, preparado para futuras cargas desde JSON.
- **Locality**: Catálogo de localidades identificadas por ID y relacionadas con una provincia mediante ID.
- **PublicationType**: Modalidad de publicación activa, con reglas sobre servicios presenciales.
- **Service**: Servicio administrable, clasificado como virtual o presencial y ordenable.
- **ModelProfileBio**: Versión de presentación de una modelo, con estados de moderación y auditoría de revisión.
- **PublicationTypeHistory**: Registro inmutable de transiciones del tipo actual, con origen, actor, fecha y motivo.
- **ModelPhoto / Identity records**: Relaciones existentes que no deben romperse ni duplicarse.

### Data and lifecycle rules

- La cuenta y el perfil son conceptos separados; los datos reales no se convierten automáticamente en datos públicos.
- La edad real se calcula desde `birth_date`; la edad pública sólo se muestra si `show_age` está activo y supera las reglas de diferencia máxima de cinco años.
- La disponibilidad es informativa y no controla publicación, identidad, revisión ni tipo.
- La provincia y localidad seleccionadas deben existir en sus catálogos y la localidad debe pertenecer a la provincia seleccionada; la ubicación pública debe ser aproximada.
- La modelo puede modificar nombre real o fecha de nacimiento después de una validación, pero cada modificación inicia una nueva validación; los cambios físicos públicos requieren moderación y no reemplazan la versión pública anterior mientras estén pendientes.
- El tipo actual es único por perfil y los servicios válidos dependen de su clasificación.
- Todo cambio de tipo válido actualiza el tipo, aplica las reglas de servicios y crea un evento de historial en una misma operación.
- Al pasar de encounters a virtual, la modelo debe confirmar la desactivación de servicios presenciales; los servicios virtuales se conservan y no se requiere aprobación administrativa adicional.
- Las bios son versionadas: `pending` nunca reemplaza una bio `approved`; sólo una aprobación administrativa actualiza `current_bio_id`.
- Los datos privados, documentos de identidad y auditoría interna nunca forman parte de la representación pública.
- La eliminación física de historial, bios aprobadas, fotos o datos de identidad existentes queda fuera de esta feature.

## Success Criteria

### Measurable Outcomes

- **SC-001**: El 100% de las actualizaciones de una modelo se aplica únicamente a su propio perfil y el 100% de los intentos sobre perfiles ajenos es rechazado sin cambios.
- **SC-002**: El 100% de las presentaciones públicas omite nombre real, fecha de nacimiento, teléfono privado, documentos, revisor y motivos internos.
- **SC-003**: El 100% de las edades públicas aceptadas está dentro del rango permitido y el 100% de las edades ocultas no aparece en la salida pública.
- **SC-004**: El 100% de los perfiles con tipo virtual carece de asociaciones presenciales inválidas, incluso ante requests manipulados.
- **SC-005**: El 100% de los cambios de tipo válidos conserva un historial consultable con transición, fecha, origen y actor cuando corresponda.
- **SC-006**: El 100% de las bios pendientes conserva la última versión aprobada como pública hasta una aprobación posterior.
- **SC-007**: Una modelo de prueba puede completar y guardar las secciones principales del perfil en menos de 5 minutos, con mensajes claros y sin asistencia.
- **SC-008**: La pantalla `/account/profile` funciona a 360 px, tablet y desktop sin overflow horizontal ni controles inaccesibles.
- **SC-009**: Las pruebas de regresión mantienen operativos registro, login, verificación de email, `/account`, identidad, fotos, Filament, `is_published`, `review_status` e `identity_status`.

## Assumptions

- La autenticación, verificación de email, ownership, autorización, identidad, fotos, moderación e infraestructura de `/account` existentes se reutilizan.
- Los nombres de entidades, relaciones y campos indicados son el vocabulario funcional esperado y deberán alinearse con las convenciones actuales durante la planificación.
- En ausencia de una decisión distinta, los servicios iniciales se cargarán como datos administrables y los tipos iniciales serán “Solo Virtual” y “Encuentros”.
- El idioma visible será español y los identificadores técnicos permanecerán en inglés conforme a la constitución del proyecto.
- La modelo podrá editar su propia información desde `/account`; administración podrá consultar y administrar según sus permisos, sin exponer documentos de identidad desde esta feature.
- La información pública sólo se presenta si las reglas existentes de identidad, moderación e `is_published` lo permiten; editar un perfil no lo publica automáticamente.
- Las tablas de provincias y localidades quedan preparadas para una carga posterior desde JSON, con IDs y relación entre localidad y provincia; no se incorpora todavía un proveedor de mapas.
- Los cambios de datos no definidos como moderables se guardan sin crear una nueva bio ni alterar estados de identidad, revisión o publicación.

## Out of Scope

- Pagos mensuales, comprobantes, precios, moneda, planes y facturación.
- Directorio público completo, filtros, búsqueda geográfica, mapa definitivo y búsqueda por distancia.
- Redes sociales, contactos públicos, favoritos, comentarios, ratings y mensajería.
- Videos, historial de galerías y nuevas funciones de fotografías.
- Tracking de presencia, última conexión y disponibilidad automática.
- Cambios o duplicación de documentación de identidad, DNI o selfies.
