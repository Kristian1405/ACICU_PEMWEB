#!/bin/sh
set -eu

if [ ! -f .env ]; then
    cp .env.example .env
fi

mkdir -p database/data storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
if [ ! -f database/data/database.sqlite ]; then
    touch database/data/database.sqlite
fi

rm -f bootstrap/cache/*.php
if [ -z "${APP_KEY:-}" ] && ! grep -qE '^APP_KEY=base64:.+' .env; then
    php artisan key:generate --force
fi
php artisan migrate --force
php artisan storage:link || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
