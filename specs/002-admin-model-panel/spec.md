# Feature Specification: Panel administrativo de modelos

**Feature Branch**: `002-admin-model-panel`

**Created**: 2026-09-15

**Status**: Draft

**Input**: User description: "Quiero implementar un panel administrativo usando Filament para gestionar las modelos registradas en la plataforma. El registro público existente debe mantenerse con Blade. El panel Filament será de uso interno para administradores. Debe permitir listar modelos registradas; buscar por nombre, email, WhatsApp y ubicación; ver el detalle de una modelo; ver si verificó el email; ver fecha de registro; ver estado de publicación; aprobar o rechazar perfiles; activar o desactivar publicación; editar información básica cuando corresponda; filtrar por estado; impedir acceso al panel a usuarios que no sean administradores. Filament debe reutilizar los modelos Eloquent existentes. No reemplazar registro público, login público, verificación de email ni recuperación de contraseña. El frontend público debe seguir usando Blade."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Acceder de forma restringida al panel (Priority: P1)

Una persona administradora puede ingresar al panel interno y una persona no administradora no puede acceder a sus pantallas ni acciones.

**Why this priority**: El panel expone datos privados y permite cambiar estados de publicación; la autorización es una condición de seguridad indispensable.

**Independent Test**: Probar el acceso con una cuenta administradora, una cuenta de modelo autenticada y una persona no autenticada, verificando que sólo la primera obtiene acceso.

**Acceptance Scenarios**:

1. **Given** una persona con una cuenta administradora válida, **When** ingresa al panel interno, **Then** puede ver la pantalla inicial del panel.
2. **Given** una persona no autenticada, **When** intenta abrir cualquier URL del panel, **Then** debe autenticarse y no recibe datos administrativos.
3. **Given** una modelo autenticada sin permisos administrativos, **When** intenta abrir cualquier URL del panel, **Then** el acceso es rechazado y no recibe datos de otras modelos.
4. **Given** una persona sin permisos administrativos, **When** intenta invocar directamente una acción administrativa, **Then** la acción es rechazada aunque conozca la URL o el identificador del registro.

### User Story 2 - Consultar y encontrar modelos registradas (Priority: P1)

Una persona administradora puede consultar un listado de modelos y localizar rápidamente una cuenta por nombre, email, WhatsApp o ubicación.

**Why this priority**: La revisión operativa empieza por encontrar la cuenta correcta y conocer su situación actual.

**Independent Test**: Crear varias cuentas con datos distintos, abrir el listado, buscar por cada campo y comprobar que los resultados corresponden únicamente a la consulta.

**Acceptance Scenarios**:

1. **Given** varias modelos registradas, **When** una administradora abre el listado, **Then** ve cada cuenta con nombre, email, fecha de registro, estado de verificación y estado de publicación.
2. **Given** modelos con coincidencias parciales, **When** la administradora busca por nombre, email, WhatsApp o ubicación, **Then** el listado muestra las cuentas coincidentes.
3. **Given** una búsqueda sin coincidencias, **When** la administradora la ejecuta, **Then** el panel informa que no hay resultados sin mostrar cuentas no relacionadas.
4. **Given** una lista con más registros que los visibles en una página, **When** la administradora navega el listado, **Then** puede continuar consultando los resultados sin perder la búsqueda o los filtros activos.

### User Story 3 - Revisar el detalle y el estado de una modelo (Priority: P1)

Una persona administradora puede abrir una modelo y revisar la información necesaria para decidir su estado de moderación y publicación.

**Why this priority**: La decisión de aprobar, rechazar o publicar necesita contexto suficiente y una separación clara entre verificación de email, revisión y publicación.

**Independent Test**: Abrir el detalle de una cuenta en distintos estados y comprobar que se muestran sus datos operativos y estados actuales de forma distinguible.

**Acceptance Scenarios**:

1. **Given** una modelo registrada, **When** la administradora abre su detalle, **Then** puede ver nombre público, email, WhatsApp, ubicación, fecha de registro, verificación de email, estado de revisión y estado de publicación.
2. **Given** una modelo cuyo email no fue verificado, **When** la administradora consulta el detalle, **Then** el estado aparece como no verificado y no se presenta como equivalente a un perfil aprobado.
3. **Given** una modelo aprobada pero no publicada, **When** la administradora consulta el detalle, **Then** los estados de aprobación y publicación aparecen separados.
4. **Given** una modelo rechazada, **When** la administradora consulta el detalle, **Then** el estado de rechazo es visible y la cuenta permanece no publicada.

### User Story 4 - Moderar y controlar la publicación (Priority: P1)

Una persona administradora puede aprobar o rechazar un perfil y activar o desactivar su publicación de forma controlada.

**Why this priority**: La plataforma necesita una decisión administrativa explícita antes de exponer perfiles públicamente.

**Independent Test**: Usar una cuenta pendiente, ejecutar aprobación y rechazo en casos separados, activar y desactivar publicación, y verificar cada transición y su efecto público.

**Acceptance Scenarios**:

1. **Given** una modelo pendiente de revisión, **When** la administradora la aprueba, **Then** su estado de revisión pasa a aprobado y su publicación no se activa automáticamente.
2. **Given** una modelo pendiente o aprobada, **When** la administradora la rechaza, **Then** su estado de revisión pasa a rechazado y el perfil queda no publicado.
3. **Given** una modelo aprobada y elegible para publicación, **When** la administradora activa la publicación, **Then** el estado de publicación pasa a activo y el perfil puede aparecer en las superficies públicas previstas.
4. **Given** una modelo publicada, **When** la administradora desactiva la publicación, **Then** el perfil deja de estar publicado y conserva su estado de revisión.
5. **Given** una modelo no aprobada, **When** la administradora intenta activar su publicación, **Then** el panel impide la acción y mantiene el perfil no publicado.
6. **Given** una acción de aprobación, rechazo, activación o desactivación, **When** la administradora la confirma, **Then** el nuevo estado queda visible al volver al listado o detalle.

### User Story 5 - Corregir información básica (Priority: P2)

Una persona administradora puede corregir la información básica de una modelo cuando sea necesario para la gestión del perfil.

**Why this priority**: Permite resolver errores operativos sin crear cuentas nuevas ni modificar el flujo de autenticación pública.

**Independent Test**: Editar nombre público, WhatsApp y ubicación de una cuenta, guardar datos válidos y comprobarlos en el listado y detalle.

**Acceptance Scenarios**:

1. **Given** una modelo existente, **When** la administradora edita nombre público, WhatsApp o ubicación con datos válidos, **Then** los cambios quedan guardados y se reflejan en listado y detalle.
2. **Given** una edición con un dato obligatorio vacío o inválido, **When** la administradora intenta guardarla, **Then** el panel muestra el error y conserva los datos anteriores.
3. **Given** una cuenta con email verificado o pendiente, **When** la administradora edita la información básica, **Then** el estado de verificación de email no cambia.
4. **Given** una modelo publicada, **When** la administradora edita su información básica, **Then** la publicación no se activa ni desactiva implícitamente.

### Edge Cases

- Si dos modelos coinciden con una búsqueda, el listado debe mostrar ambas y permitir identificar cada una sin ambigüedad.
- Si una búsqueda combina texto con espacios o diferencias de mayúsculas, debe devolver coincidencias equivalentes sin alterar el dato almacenado.
- Si una cuenta fue eliminada o deja de estar disponible mientras se consulta su detalle, el panel debe mostrar una respuesta controlada y no ejecutar acciones sobre un registro inexistente.
- Si una administradora intenta publicar una modelo rechazada o pendiente, la acción debe bloquearse y el estado debe permanecer no publicado.
- Si una modelo aprobada deja de cumplir una condición necesaria para publicación, debe poder desactivarse sin perder su aprobación.
- Si se rechaza una modelo que ya estaba publicada, el rechazo debe retirar la publicación y no dejar el perfil visible.
- Si una actualización administrativa falla, no debe quedar guardada una modificación parcial.
- Si una cuenta no tiene email verificado, el panel debe mostrarlo claramente y no permitir que la revisión administrativa se interprete como verificación de identidad o de email.
- Si alguien intenta acceder a datos privados mediante una URL directa, una acción manipulada o un identificador ajeno, debe aplicarse la misma restricción administrativa.
- Si varias administradoras actúan sobre la misma modelo, el estado final debe ser coherente y no debe permitir publicar un perfil que terminó rechazado.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST ofrecer un panel interno separado de las pantallas públicas de registro, login, verificación de email y recuperación de contraseña.
- **FR-002**: El sistema MUST permitir el acceso al panel únicamente a cuentas identificadas como administradoras.
- **FR-003**: El sistema MUST rechazar el acceso al panel y a sus acciones a personas no autenticadas y a cuentas autenticadas sin permisos administrativos.
- **FR-004**: El sistema MUST reutilizar las cuentas de modelo registradas existentes como fuente de los datos gestionados por el panel.
- **FR-005**: El sistema MUST listar las modelos registradas mostrando, como mínimo, nombre público, email, fecha de registro, estado de verificación de email, estado de revisión y estado de publicación.
- **FR-006**: El sistema MUST permitir buscar modelos por nombre público, email, WhatsApp y ubicación.
- **FR-007**: El sistema MUST permitir filtrar el listado por estado de verificación de email, estado de revisión y estado de publicación.
- **FR-008**: El sistema MUST permitir abrir el detalle de una modelo y consultar su información básica y todos los estados administrativos relevantes.
- **FR-009**: El sistema MUST distinguir visualmente entre email verificado, email no verificado, perfil pendiente, aprobado, rechazado, publicado y no publicado.
- **FR-010**: El sistema MUST permitir aprobar un perfil pendiente y registrar que su estado de revisión pasó a aprobado.
- **FR-011**: El sistema MUST permitir rechazar un perfil y mantenerlo no publicado.
- **FR-012**: El sistema MUST mantener la aprobación o el rechazo separados del estado de publicación.
- **FR-013**: El sistema MUST impedir activar la publicación de una modelo pendiente o rechazada.
- **FR-014**: El sistema MUST permitir activar la publicación de una modelo aprobada.
- **FR-015**: El sistema MUST permitir desactivar la publicación de una modelo publicada sin borrar su cuenta ni cambiar automáticamente su estado de revisión.
- **FR-016**: El sistema MUST retirar la publicación si una modelo publicada es rechazada.
- **FR-017**: El sistema MUST permitir editar la información básica autorizada de una modelo, incluyendo nombre público, WhatsApp y ubicación.
- **FR-018**: El sistema MUST validar las ediciones administrativas antes de guardarlas y conservar los valores anteriores cuando la validación falle.
- **FR-019**: El sistema MUST mantener sin cambios el estado de verificación de email cuando se edite la información básica.
- **FR-020**: El sistema MUST impedir que una edición administrativa active o desactive publicación de forma implícita.
- **FR-021**: El sistema MUST mantener el registro público, login público, verificación de email, recuperación de contraseña y frontend público funcionando con sus flujos actuales.
- **FR-022**: El sistema MUST evitar exponer información de modelos o acciones administrativas a usuarios sin autorización.
- **FR-023**: El sistema MUST asegurar que las acciones administrativas se autoricen en el servidor, no sólo mediante elementos visibles u ocultos de la interfaz.
- **FR-024**: El sistema MUST mostrar mensajes claros cuando una búsqueda no tenga resultados, una acción no esté permitida o una edición no pueda guardarse.
- **FR-025**: El sistema MUST conservar una separación operativa entre verificación de email y aprobación administrativa; aprobar un perfil no verifica su email.

### Key Entities

- **Cuenta de modelo**: Cuenta existente de una modelo, con nombre público, email, WhatsApp, ubicación, fecha de registro, verificación de email y publicación.
- **Estado de revisión**: Estado administrativo de un perfil: pendiente, aprobado o rechazado.
- **Estado de publicación**: Estado que indica si el perfil está habilitado o deshabilitado para mostrarse públicamente.
- **Cuenta administradora**: Cuenta autorizada a ingresar al panel y ejecutar acciones administrativas; su mecanismo de asignación pertenece a la configuración de acceso interno.
- **Acción administrativa**: Aprobación, rechazo, activación, desactivación o edición ejecutada sobre una cuenta de modelo.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las pruebas de acceso con una persona no autenticada o una cuenta no administradora rechaza el acceso al panel y no entrega datos administrativos.
- **SC-002**: Una administradora puede encontrar una modelo por cualquiera de los cuatro criterios de búsqueda en menos de 10 segundos con hasta 1.000 cuentas registradas.
- **SC-003**: El 100% de las transiciones de aprobación, rechazo, publicación y despublicación respetan las reglas de elegibilidad definidas y dejan el estado visible en listado y detalle.
- **SC-004**: El 100% de los perfiles rechazados permanece no publicado, incluso si antes había estado publicado.
- **SC-005**: Al menos el 95% de las ediciones válidas de información básica se reflejan correctamente en listado y detalle en menos de 3 segundos.
- **SC-006**: El 100% de las ediciones inválidas conserva los datos anteriores y muestra el campo que requiere corrección.
- **SC-007**: En pruebas con modelos verificadas y no verificadas, el panel muestra el estado correcto en el 100% de los casos sin confundir verificación de email con aprobación administrativa.
- **SC-008**: Ninguna pantalla o acción del panel modifica el comportamiento del registro público, login público, verificación de email o recuperación de contraseña en la suite de regresión existente.

## Assumptions

- El panel será una interfaz interna para administradores y el frontend público continuará siendo servido por Blade.
- La entrega administrativa utilizará Filament como se solicita y reutilizará los modelos Eloquent existentes; esto no cambia los flujos públicos.
- Una cuenta administradora se identifica mediante un rol o permiso administrativo dedicado; el proceso para asignar ese rol queda fuera del alcance de este panel.
- La aprobación y la publicación son estados independientes: aprobar no publica automáticamente y publicar requiere una acción explícita posterior.
- Los estados de revisión iniciales son pendiente, aprobado y rechazado.
- Un perfil rechazado no puede publicarse hasta que una administradora lo apruebe nuevamente.
- La despublicación no revoca automáticamente la aprobación; permite ocultar temporalmente un perfil aprobado.
- La verificación de email sigue dependiendo del flujo existente y no puede ser marcada manualmente como verificada desde este panel.
- La edición básica del panel incluye nombre público, WhatsApp y ubicación; el email se consulta, pero no se modifica desde esta entrega para preservar la identidad de la cuenta y su verificación.
- Los datos privados de las modelos sólo estarán disponibles dentro del panel para personas autorizadas.
- Las acciones administrativas requieren confirmación antes de cambiar estados de revisión o publicación.
- No se incluye en esta entrega la gestión de fotos, videos, identidad, pagos, métricas, mensajes, roles avanzados, auditoría histórica detallada ni configuración de políticas.
- Los datos existentes de las modelos deben permanecer disponibles después de habilitar el panel.
