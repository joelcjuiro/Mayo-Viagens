#!/usr/bin/env bash
# Actualiza el sitio en Hostinger. Úsalo por SSH dentro de la carpeta del proyecto:  bash deploy.sh
set -e
echo "→ Bajando cambios de GitHub…"
git pull --ff-only
echo "→ Instalando dependencias…"
composer install --no-dev --optimize-autoloader --no-interaction
echo "→ Actualizando la base de datos…"
php artisan migrate --force
echo "→ Optimizando (config, rutas, vistas)…"
php artisan optimize:clear
php artisan optimize
mkdir -p public/uploads
chmod -R 775 storage bootstrap/cache public/uploads 2>/dev/null || true
echo "✓ Listo."
