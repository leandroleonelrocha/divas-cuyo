#!/usr/bin/env bash
# Deploy de producción. Ejecutar desde /srv/divas-cuyo DESPUÉS de "git pull":
#   git pull --ff-only && ./deploy.sh
set -euo pipefail

cd "$(dirname "$0")"

COMPOSE=(docker compose -f docker-compose.prod.yml)

if [ ! -f .env ]; then
    echo "Falta .env (copiar docker/production.env.example y completarlo)." >&2
    exit 1
fi

echo "→ Construyendo imagen..."
"${COMPOSE[@]}" build app

echo "→ Base de datos..."
"${COMPOSE[@]}" up -d --wait db

echo "→ Migraciones..."
"${COMPOSE[@]}" run --rm --no-deps --user www-data app php artisan migrate --force --no-interaction

echo "→ Levantando servicios..."
# Los workers se recrean con la imagen nueva. Si hay un video procesándose,
# queue-videos espera a que termine (hasta 16 min) antes de reiniciarse.
"${COMPOSE[@]}" up -d --remove-orphans

echo "→ Limpiando imágenes viejas..."
docker image prune -f >/dev/null

"${COMPOSE[@]}" ps
echo "✔ Deploy terminado."
