# Deploy en producción (Docker)

Servidor: Debian 13 con Docker Engine + Compose plugin, firewall con 22/80/443 abiertos. La app vive en `/srv/divas-cuyo` (checkout del repo) y se levanta con `docker-compose.prod.yml`.

## Servicios

| Servicio | Qué hace |
|---|---|
| `caddy` | Único expuesto a internet (80/443). HTTPS automático con Let's Encrypt. Con `SITE_PROFILE=staging` pide usuario/contraseña y envía `X-Robots-Tag: noindex`. |
| `app` | Apache + PHP 8.4 con la app. Al arrancar corre `php artisan optimize`. |
| `queue-default` | Worker de la cola `default` (mails de verificación, etc.). |
| `queue-videos` | Worker de `video_database` / `videos` con timeout 900 s (ver `docs/model-videos.md`). |
| `scheduler` | `schedule:work` (tareas programadas de `routes/console.php`). |
| `db` | MariaDB 11.4, sin puertos publicados. |

Volúmenes: `storage` (todo `storage/`, incluidos DNI, fotos y videos), `db-data`, `caddy-data` (certificados).

## Primera instalación

```bash
cd /srv/divas-cuyo
git clone <url-del-repo> .
cp docker/production.env.example .env
chmod 600 .env
```

Completar `.env`:

- `DB_PASSWORD` y `DB_ROOT_PASSWORD`: `openssl rand -base64 32 | tr -d '/+='`
- `BASIC_AUTH_HASH` (solo staging): `docker run --rm caddy:2 caddy hash-password --plaintext 'la-contraseña'`, pegarlo entre comillas simples.
- SMTP (`MAIL_*`) y admin inicial (`ADMIN_*`).

Construir y generar la clave de la app (una sola vez, guardarla también en el gestor de contraseñas):

```bash
docker compose -f docker-compose.prod.yml build app
docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan key:generate --show
# copiar el valor "base64:..." en APP_KEY del .env
```

Levantar, cargar catálogos y crear el admin:

```bash
./deploy.sh
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan db:seed --class=ProvinceSeeder --force
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan db:seed --class=LocalitySeeder --force
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan db:seed --class=PublicationTypeSeeder --force
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan db:seed --class=ServiceSeeder --force
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan admin:create --from-env
```

> `DatabaseSeeder` llama siempre a `PerfilesDivasCuyoSeeder` (la condición está comentada). No correr `db:seed` sin `--class` en producción salvo que se quiera importar esos perfiles.

## Deploys siguientes

```bash
cd /srv/divas-cuyo
git pull --ff-only && ./deploy.sh
```

`deploy.sh` construye la imagen, corre las migraciones y recrea los contenedores. Si hay un video procesándose, `queue-videos` espera a que termine (hasta 16 min) antes de reiniciarse.

## Cambio de dominio

1. Apuntar el registro A del dominio nuevo al servidor.
2. En `.env`: `APP_DOMAIN`, `APP_URL`, `MAIL_FROM_ADDRESS` y `SITE_PROFILE=public`.
3. `docker compose -f docker-compose.prod.yml up -d` (Caddy pide el certificado nuevo solo).

## Comandos útiles

```bash
alias dc='docker compose -f /srv/divas-cuyo/docker-compose.prod.yml'

dc ps                                   # estado
dc logs -f --tail 100 app               # logs de Apache/PHP
dc logs -f queue-videos                 # procesamiento de videos
dc exec app tail -f storage/logs/laravel-$(date +%F).log
dc exec --user www-data app php artisan tinker
dc exec --user www-data app php artisan queue:failed
dc exec --user www-data app php artisan queue:retry all
dc exec --user www-data app php artisan down    # modo mantenimiento
dc exec --user www-data app php artisan up
```

Usar siempre `--user www-data` para comandos de artisan: si se corren como root, los archivos que creen (logs, cachés) quedan con dueño root y la app no puede escribirlos.

## Respaldos

Lo mínimo, diario y copiado fuera del servidor (cifrado: `storage` contiene documentos de identidad):

```bash
# Base de datos
dc exec -T db sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --routines "$MARIADB_DATABASE"' | gzip > divas-db-$(date +%F).sql.gz

# Archivos (storage completo)
docker run --rm -v divas-cuyo-prod_storage:/data:ro -v "$PWD":/backup alpine tar czf /backup/divas-storage-$(date +%F).tar.gz -C /data .
```

Guardar también una copia del `.env` (sin él, `APP_KEY` se pierde y no se puede descifrar lo cifrado).
