#!/bin/sh
# Sincroniza tus cambios locales al contenedor Docker y limpia la caché.
# Uso: ./sync-docker.sh
set -eu

cd "$(dirname "$0")"

echo "→ Copiando archivos al contenedor..."
tar \
  --exclude='./vendor' \
  --exclude='./node_modules' \
  --exclude='./.git' \
  --exclude='./storage' \
  --exclude='./.env' \
  -cf - . | docker exec -i divas-cuyo-app-1 tar -xf - -C /var/www/html

echo "→ Limpiando caché..."
docker exec divas-cuyo-app-1 php artisan optimize:clear

echo "→ Corrigiendo permisos..."
docker exec divas-cuyo-app-1 chown -R www-data:www-data /var/www/html/bootstrap/cache /var/www/html/storage

echo "✔ Listo. Recargá el navegador con Cmd+Shift+R (Mac) o Ctrl+F5 (Windows)."
