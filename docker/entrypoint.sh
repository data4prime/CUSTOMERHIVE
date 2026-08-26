#!/bin/bash
set -e

if [ ! -f .env ]; then
    echo "[entrypoint] Creo .env da .env.example"
    cp .env.example .env
fi

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] Installo le dipendenze Composer"
    composer install --no-interaction --prefer-dist
fi

if ! grep -q "^APP_KEY=base64" .env; then
    echo "[entrypoint] Genero APP_KEY"
    php artisan key:generate --ansi
fi

if [ ! -e public/storage ]; then
    php artisan storage:link
fi

echo "[entrypoint] Creo il database di test (se non esiste)"
mysql --skip-ssl -h "${DB_HOST:-db}" -u root -p"${MYSQL_ROOT_PASSWORD:-root}" -e "
    CREATE DATABASE IF NOT EXISTS customerhive_testing;
    GRANT ALL PRIVILEGES ON customerhive_testing.* TO '${DB_USERNAME:-chive_user}'@'%';
    FLUSH PRIVILEGES;
" || echo "[entrypoint] DB non ancora raggiungibile, il DB di test verra' creato al prossimo avvio"

exec "$@"
