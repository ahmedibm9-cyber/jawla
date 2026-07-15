#!/bin/bash
set -e

# Render assigns PORT (default 10000). Make Apache listen on it.
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-enabled/*.conf 2>/dev/null || true

cd /var/www/html

# Laravel setup
php artisan storage:link --force || true
php artisan migrate --force
php artisan db:seed --class=DemoSeeder --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Apache in foreground
exec apache2-foreground
