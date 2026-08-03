#!/bin/sh
set -eu

PORT="${PORT:-8080}"

envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

# Railway pre-deploy commands run in a separate container, so filesystem cache
# artifacts must be generated in the application container that will serve.
php /app/artisan config:cache
php /app/artisan route:cache
php /app/artisan view:clear
php /app/artisan view:cache

# Public storage symlink for rep-captured photos (public disk). Each replica has
# an ephemeral filesystem, so recreate it on boot. Idempotent.
php /app/artisan storage:link 2>/dev/null || true

php-fpm -F &
php_fpm_pid=$!

php /app/artisan schedule:work --no-interaction &
scheduler_pid=$!

nginx -g 'daemon off;' &
nginx_pid=$!

process_ids="$php_fpm_pid $scheduler_pid $nginx_pid"

terminate() {
    trap - EXIT INT TERM

    for pid in $process_ids; do
        kill "$pid" 2>/dev/null || true
    done

    for pid in $process_ids; do
        wait "$pid" 2>/dev/null || true
    done

    exit 0
}

trap terminate INT TERM

# Railway restarts the container if any required process exits. This keeps the
# scheduler supervised instead of silently losing retention/maintenance jobs.
while kill -0 "$php_fpm_pid" 2>/dev/null \
    && kill -0 "$scheduler_pid" 2>/dev/null \
    && kill -0 "$nginx_pid" 2>/dev/null; do
    sleep 1
done

echo "A required Jawla process exited; stopping the container." >&2

for pid in $process_ids; do
    kill "$pid" 2>/dev/null || true
done

for pid in $process_ids; do
    wait "$pid" 2>/dev/null || true
done

exit 1
