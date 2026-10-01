#!/bin/sh
set -e

# Platforms (Railway, Render, Fly) inject PORT; Apache must listen on it.
PORT="${PORT:-8080}"
export PORT
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf

# mod_php needs the prefork MPM; Apache refuses to start if another MPM is also enabled.
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*

# Uploads live in storage/; a mounted volume starts empty, so recreate the tree.
mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

php artisan migrate --force
if [ "${SEED_ON_DEPLOY}" = "true" ]; then
    php artisan db:seed --force
fi
php artisan optimize

exec apache2-foreground
