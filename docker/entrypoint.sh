#!/bin/sh
set -eu

cd /var/www/html

# Allow diagnostic and maintenance commands to run without bootstrapping Laravel.
case "${1:-}" in
    php|composer|/usr/local/bin/composer)
        exec "$@"
        ;;
esac

as_www() {
    if [ "$(id -u)" = "0" ]; then
        runuser -u www-data -- "$@"
    else
        "$@"
    fi
}

if [ "${APP_ENV:-}" = "production" ]; then
    # Producción: la configuración llega por variables de entorno (env_file del compose).
    # Nunca se genera APP_KEY ni se instalan dependencias acá.
    if [ -z "${APP_KEY:-}" ]; then
        echo "ERROR: APP_KEY no está definido. Generalo una sola vez y guardalo en .env." >&2
        exit 1
    fi

    # storage es un volumen: asegurar estructura y dueño (sin chown recursivo sobre videos/fotos).
    mkdir -p storage/app/private storage/app/public storage/logs \
        storage/framework/cache/data storage/framework/sessions storage/framework/views
    chown www-data:www-data storage storage/app storage/app/private storage/app/public storage/logs \
        storage/framework storage/framework/cache storage/framework/cache/data \
        storage/framework/sessions storage/framework/views

    if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
        as_www php artisan migrate --force --no-interaction
    fi

    as_www php artisan optimize

    exec "$@"
fi

# Desarrollo local (docker-compose.yml).
if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Solo genera la clave si todavía no existe: regenerarla invalida sesiones y datos cifrados.
if ! grep -q '^APP_KEY=.\+' .env && [ -z "${APP_KEY:-}" ]; then
    php artisan key:generate --force --no-interaction
fi

php artisan filament:assets
php artisan migrate --force --no-interaction

exec "$@"
