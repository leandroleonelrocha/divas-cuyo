#!/bin/sh
set -eu

cd /var/www/html

# Allow diagnostic and maintenance commands to run without bootstrapping Laravel.
case "${1:-}" in
    php|composer|/usr/local/bin/composer)
        exec "$@"
        ;;
esac

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

php artisan key:generate --force --no-interaction
php artisan filament:assets
php artisan migrate --force --no-interaction

exec "$@"
