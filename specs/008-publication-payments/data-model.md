# Data Model: Pagos y vencimiento de publicaciones

## PaymentInstruction

Configuración vigente que se muestra a las modelos.

- `id`
- `alias` nullable/string
- `transfer_details` nullable/text
- `receipt_whatsapp` nullable/string
- `default_amount` nullable/decimal
- `is_active` boolean
- timestamps

Debe existir como máximo una configuración activa para evitar instrucciones contradictorias.

## PublicationPayment

Registro administrativo e histórico de un pago.

- `id`
- `model_profile_id` foreign key
- `registered_by` foreign key a `users`
- `paid_at` date/datetime
- `amount` decimal positivo
- `period_days` positive integer o plan equivalente
- `starts_at` date
- `expires_at` date, posterior a `starts_at`
- `internal_note` nullable/text
- timestamps

Relaciones: un perfil tiene muchos pagos; un administrador registra muchos pagos.

## ModelProfile additions

No se recomienda guardar el pago actual duplicado en el perfil. Para consultas operativas se podrá derivar el pago vigente más reciente mediante relación/consulta. Si se requiere una fecha efectiva denormalizada, debe actualizarse transaccionalmente y quedar justificada.

## State rules

- `expires_at < today`: vencida, no elegible para publicación.
- `today <= expires_at <= today + threshold`: próxima a vencer.
- pago vigente + `review_status=approved`: elegible para activar.
- `review_status=rejected`: nunca elegible aunque exista pago vigente.
- un nuevo pago no debe borrar pagos anteriores.

## Validation

- Importe mayor que cero.
- Fecha de pago válida.
- Período positivo.
- Vencimiento posterior o igual al inicio, según la política elegida.
- Nota interna sólo visible a administradores.
- Todas las claves foráneas y cambios de estado deben persistirse dentro de una transacción cuando afecten activación.
