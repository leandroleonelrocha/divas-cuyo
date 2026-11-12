# Emails de revisión

Cada aviso de aprobación o rechazo de perfil/inscripción, identidad, fotos,
videos, biografía o características físicas genera un email para el titular
del perfil. La aprobación automática de la inscripción al validar la identidad
también genera su aviso. Se conserva la notificación dentro de la cuenta.

Los mensajes incluyen el motivo de rechazo cuando está disponible y un enlace
a Mi cuenta. Se envían después de confirmar la transacción de revisión; un
rollback no debe generar un correo.

## Configuración y ejecución

Configurar `MAIL_MAILER=smtp` y los parámetros `MAIL_HOST`, `MAIL_PORT`,
`MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME` y `MAIL_FROM_ADDRESS` en `.env`.
`MAIL_MAILER=log` registra los mensajes sin entregarlos. `APP_URL` debe apuntar
a la dirección que podrá abrir el destinatario.

En MAMP, con `QUEUE_CONNECTION=sync`, el correo se procesa durante la petición.
Para procesarlo en segundo plano y reintentar fallos de SMTP, usar
`QUEUE_CONNECTION=database` y mantener activo:

```sh
php artisan queue:work --sleep=3 --tries=3 --timeout=60
```

Docker usa la cola de base de datos y el servicio `queue`, que espera a que
la aplicación esté lista antes de arrancar:

```sh
docker compose up -d --build
```

Los emails de revisión tienen hasta tres intentos, separados por 60 segundos.
Después de cambiar código o configuración, reiniciar los workers. En Docker:

```sh
docker compose restart queue
docker compose exec app php artisan queue:failed
```

Revisar y corregir la causa antes de reintentar un trabajo fallido con
`php artisan queue:retry <id>` (o el equivalente con `docker compose exec app`).
