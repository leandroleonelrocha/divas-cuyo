# Research: Pagos y vencimiento de publicaciones

## Decision: Mantener la recepción manual por WhatsApp

El sistema no integrará una pasarela. El panel de la modelo mostrará instrucciones configuradas y el administrador registrará el comprobante recibido.

**Rationale**: coincide con el flujo solicitado y evita introducir dependencias externas o datos sensibles de tarjetas.

**Alternatives considered**: integración de Mercado Pago/Stripe; descartada para esta entrega por alcance y porque no fue solicitada.

## Decision: Separar pagos de publicaciones

Se agregará un registro histórico de pagos asociado a `model_profiles`, en lugar de reutilizar `is_published` como comprobante de pago.

**Rationale**: permite renovaciones, auditoría, observaciones internas y múltiples períodos sin perder historial.

**Alternatives considered**: guardar únicamente `paid_until` en el perfil; descartado porque no conserva historial ni quién registró el pago.

## Decision: Vencimiento como condición pública centralizada

La consulta de visibilidad pública deberá exigir que la fecha de vencimiento sea nula o futura según la política definida para perfiles sin plan activo; la activación administrativa exigirá pago válido, aprobación y vencimiento futuro.

**Rationale**: evita que home, búsqueda y ruta individual implementen reglas distintas.

**Alternatives considered**: comando que despublique físicamente cada perfil vencido; descartado como única regla porque puede dejar estados obsoletos y requiere ejecución puntual.

## Decision: Umbral inicial configurable de 7 días

Las publicaciones con vencimiento entre hoy y los próximos 7 días se marcarán como próximas a vencer. El valor debe poder cambiarse por configuración sin migración.

**Rationale**: permite operación diaria sin hardcodear una política de negocio.

## Confirmed decisions

- El período será una cantidad de días editable por cada pago.
- La fecha de vencimiento se calculará automáticamente desde la fecha de inicio más la cantidad de días.
- El pago no activa por sí solo la publicación: la publicación requiere una acción administrativa posterior, además de aprobación y pago vigente.
