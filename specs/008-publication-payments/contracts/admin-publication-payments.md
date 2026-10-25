# Contract: Administración de pagos y publicaciones

## Admin operations

- Consultar pagos de un perfil.
- Registrar un pago con fecha, importe, período, vencimiento y observación interna.
- Identificar publicaciones vencidas y próximas a vencer.
- Activar una publicación sólo cuando el perfil esté aprobado y tenga pago vigente.
- Desactivar una publicación sin borrar pagos ni revocar aprobación.

Todas las operaciones requieren sesión administrativa y autorización en servidor.

## Model-facing display

La interfaz de la modelo muestra sólo instrucciones activas: alias/datos de transferencia, importe y WhatsApp para comprobantes. Nunca muestra observaciones internas ni datos administrativos.

## Public visibility contract

Home, búsqueda, perfil individual y cualquier otra superficie pública deben aplicar conjuntamente las condiciones existentes y la vigencia del pago/publicación. Un perfil vencido debe comportarse como no publicado.
