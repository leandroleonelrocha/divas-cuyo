# Quickstart: Validación de registro y autenticación

## Prerrequisitos

- PHP 8.2+, Composer y las dependencias instaladas.
- Clave de aplicación configurada.
- Base de datos disponible. El destino es MySQL; el `.env` actual del proyecto usa SQLite localmente y puede utilizarse para tests si se mantiene compatible con las migrations.
- Mailer de desarrollo configurado como `log` o `array` para inspeccionar notificaciones sin proveedor externo.

## Preparar el entorno

```bash
composer install
php artisan migrate:fresh
php artisan config:clear
```

Para usar MySQL, configurar `DB_CONNECTION=mysql`, host, puerto, base, usuario y contraseña antes de migrar. No ejecutar cambios de esquema manuales.

## Ejecutar validación automatizada

```bash
php artisan test
```

El conjunto debe cubrir como mínimo:

1. Registro válido con las dos políticas: crea una cuenta con `email_verified_at = null` e `is_published = false`, guarda exactamente ambas aceptaciones y envía verificación.
2. Registro inválido sin términos o sin privacidad: devuelve errores y no crea cuenta.
3. Verificación: un token válido dentro de 24 horas habilita la cuenta; un token expirado no lo hace.
4. Rotación: solicitar reenvío invalida el token anterior; únicamente el último enlace verifica.
5. Login: cuenta verificada puede autenticarse; cuenta no verificada y credenciales inválidas no crean sesión.
6. Logout: la sesión ya no puede acceder a `/cuenta/{user}`.
7. Recuperación: solicitud existente/no existente tiene respuesta indistinguible; token de 60 minutos cambia la contraseña y un token vencido no lo hace.
8. Privacidad: la cuenta A puede ver A, no puede ver B y una visitante no autenticada no puede ver ninguna cuenta.

## Validación manual mínima

1. Abrir `/registro`, completar los cinco datos y aceptar ambos documentos.
2. Confirmar la pantalla de registro pendiente y extraer el enlace del mailer de desarrollo.
3. Abrir el enlace, intentar login antes/después de verificar y comprobar la diferencia.
4. Reenviar verificación y confirmar que el enlace anterior falla y el último funciona.
5. Cerrar sesión y comprobar redirección/bloqueo al volver a `/cuenta/{user}`.
6. Usar `/password/forgot`, tomar el enlace del mailer, comprobar reset válido y probar un enlace vencido/reutilizado.

## Referencias

- Reglas de datos y estados: [data-model.md](./data-model.md)
- Superficie HTTP: [contracts/web-routes.md](./contracts/web-routes.md)
- Reglas de diseño investigadas: [research.md](./research.md)
