#!/bin/sh
set -eu

PORT="${PORT:-8080}"

envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

# Public storage symlink for rep-captured photos (public disk). Each replica has
# an ephemeral filesystem, so recreate it on boot. Idempotent.
php /app/artisan storage:link 2>/dev/null || true

php-fpm -D

exec nginx -g 'daemon off;'
