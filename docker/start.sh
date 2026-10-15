#!/bin/sh
set -eu

if [ ! -f .env ]; then
    cp .env.example .env
fi

mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

rm -f bootstrap/cache/*.php
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
