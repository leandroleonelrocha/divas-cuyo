# Feature Specification: Pagos y vencimiento de publicaciones

**Feature Branch**: `008-publication-payments`
**Created**: 2026-10-25
**Status**: Draft

## User Scenarios & Testing

### User Story 1 - Consultar instrucciones de pago (P1)

Una modelo puede consultar desde su panel el alias o datos de transferencia, el importe correspondiente y el WhatsApp destinado al comprobante.

**Acceptance Scenarios**

1. Given una publicación con importe configurado, when la modelo abre las instrucciones, then ve importe, datos de transferencia y WhatsApp de destino.
2. Given que falta un dato de configuración, when se muestran las instrucciones, then se informa claramente que el dato aún no está configurado y no se inventan valores.

### User Story 2 - Registrar manualmente un pago (P1)

Una persona administradora puede registrar un comprobante recibido por WhatsApp y asociarlo a una publicación.

**Acceptance Scenarios**

1. Given un comprobante recibido, when el administrador registra el pago válido, then quedan guardados fecha, importe, período, vencimiento y observación interna.
2. Given un importe o período inválido, when se intenta guardar, then la operación se rechaza y no queda un pago parcial.
3. Given un pago registrado, when se consulta el perfil, then el estado del pago y la fecha de vencimiento son visibles para administración.

### User Story 3 - Activar una publicación pagada (P1)

Una persona administradora puede activar una publicación después de registrar un pago válido.

**Acceptance Scenarios**

1. Given un pago registrado con vencimiento futuro y un perfil aprobado, when se activa la publicación, then el perfil queda visible públicamente.
2. Given un pago ausente, vencido o un perfil no aprobado, when se intenta activar, then la acción es bloqueada.

### User Story 4 - Controlar vencimientos (P1)

El sistema oculta automáticamente una publicación cuando alcanza su fecha de vencimiento.

**Acceptance Scenarios**

1. Given una publicación con vencimiento pasado, when se consulta el home o perfil público, then no aparece públicamente.
2. Given una publicación con vencimiento futuro, when se consulta el home, then puede aparecer si cumple las demás condiciones actuales.
3. Given una publicación próxima a vencer, when un administrador consulta el panel, then puede identificarla para seguimiento y renovación.

## Functional Requirements

- **FR-001**: El sistema MUST mostrar instrucciones configurables de pago con alias/datos de transferencia, importe y WhatsApp de comprobantes.
- **FR-002**: El sistema MUST mostrar únicamente datos de pago configurados y debe evitar exponer información interna innecesaria.
- **FR-003**: El sistema MUST permitir a administradores registrar fecha de pago, importe, período, fecha de vencimiento y observación interna.
- **FR-004**: El sistema MUST validar importes positivos, fechas coherentes y períodos permitidos.
- **FR-005**: El sistema MUST impedir pagos duplicados o definir explícitamente su relación con renovaciones.
- **FR-006**: El sistema MUST asociar cada pago a una publicación o perfil y registrar quién lo cargó.
- **FR-007**: El sistema MUST permitir activar una publicación sólo con aprobación administrativa vigente y pago válido no vencido.
- **FR-008**: El sistema MUST ocultar automáticamente publicaciones vencidas de todas las superficies públicas.
- **FR-009**: El sistema MUST permitir identificar publicaciones próximas a vencer mediante un umbral configurable.
- **FR-010**: El sistema MUST conservar historial de pagos y no borrar información al renovar.
- **FR-011**: El sistema MUST restringir datos de pago, observaciones y acciones administrativas a usuarios autorizados.
- **FR-012**: El sistema MUST mantener funcionando el registro, login, panel público y moderación existentes.

## Key Entities

- **Configuración de pago**: alias/datos de transferencia, importe base y WhatsApp receptor.
- **Pago de publicación**: perfil, fecha de pago, importe, período, vencimiento, observación, administrador y estado.
- **Publicación**: perfil aprobado, estado público y fecha de vencimiento efectiva.

## Success Criteria

- **SC-001**: Un administrador puede registrar un pago válido y visualizarlo en menos de 30 segundos.
- **SC-002**: El 100% de las publicaciones vencidas queda excluido de home, búsquedas y perfil público.
- **SC-003**: El 100% de los intentos de activar publicaciones sin aprobación o pago válido es rechazado.
- **SC-004**: Un administrador puede localizar todas las publicaciones que vencen dentro del umbral configurado.
- **SC-005**: Ningún usuario no autorizado puede consultar observaciones internas o datos administrativos de pagos.

## Assumptions

- El período inicial se registra como una cantidad de días o una fecha de vencimiento calculada según la configuración del pago.
- El umbral de “próximo a vencer” será configurable y tendrá un valor inicial de 7 días.
- La activación por pago no reemplaza la aprobación de contenido existente.
- No se integra una pasarela de pagos en esta entrega; la recepción del comprobante continúa por WhatsApp.
