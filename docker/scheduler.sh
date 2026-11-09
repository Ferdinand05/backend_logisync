#!/bin/sh

cd /var/www/html || exit 1

while true; do
    su -s /bin/sh www-data -c 'php artisan schedule:run --no-interaction' || true
    sleep 60
done
