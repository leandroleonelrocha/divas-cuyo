# Quickstart: Validar pagos y vencimientos

## Preparación

```bash
docker compose up -d
docker compose exec app php artisan migrate
docker compose exec app php artisan optimize:clear
```

Crear o configurar una cuenta administradora y un perfil aprobado con foto pública válida.

## Escenario 1: instrucciones

1. Configurar alias, datos de transferencia, importe y WhatsApp.
2. Ingresar al panel de modelo.
3. Confirmar que aparecen los datos configurados y que no aparecen notas internas.
4. Vaciar un dato y confirmar que se muestra como no configurado.

## Escenario 2: pago y activación

1. Registrar un pago con fecha, importe positivo, período y vencimiento futuro.
2. Confirmar que el pago queda asociado al perfil y al administrador.
3. Intentar publicar un perfil no aprobado y verificar que se rechaza.
4. Aprobar el perfil y activar la publicación.
5. Confirmar que aparece en las superficies públicas.

## Escenario 3: vencimiento

1. Registrar o preparar un pago con vencimiento pasado.
2. Confirmar que home, búsqueda y ruta pública no muestran el perfil.
3. Preparar un vencimiento dentro de 7 días.
4. Confirmar que aparece en el seguimiento administrativo como próximo a vencer.

## Automatización esperada

Agregar pruebas de feature para instrucciones, registro autorizado, elegibilidad, ocultamiento público, renovación e identificación de próximos vencimientos.
