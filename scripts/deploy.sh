#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/jawla}"
ARTISAN="php ${APP_DIR}/artisan"

echo "=== Deploying Jawla ==="

cd "$APP_DIR"

echo "  Pulling latest code..."
git pull origin master

echo "  Installing Composer deps..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "  Migrating database..."
$ARTISAN migrate --force

echo "  Caching config..."
$ARTISAN config:cache

echo "  Caching routes..."
$ARTISAN route:cache

echo "  Caching views..."
$ARTISAN view:cache

echo "  Caching events..."
$ARTISAN event:cache

echo "  Restarting queue worker..."
$ARTISAN queue:restart

echo "  Health check..."
curl -sf http://localhost/up > /dev/null && echo "  OK" || echo "  WARN: health check failed"

echo "=== Deploy complete ==="
