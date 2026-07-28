#!/usr/bin/env bash
set -euo pipefail

: "${RELEASE_REF:?RELEASE_REF must be an immutable Git tag or full commit SHA}"

APP_DIR="${APP_DIR:-/var/www/jawla}"
ARTISAN=(php "${APP_DIR}/artisan")

cd "$APP_DIR"

if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
  echo "Refusing to deploy over tracked working-tree changes." >&2
  exit 64
fi

previous_release="$(git rev-parse HEAD)"
deployment_started=0

install_release() {
  composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
  npm ci
  npm run build
}

optimize_release() {
  "${ARTISAN[@]}" config:cache
  "${ARTISAN[@]}" route:cache
  "${ARTISAN[@]}" view:cache
  "${ARTISAN[@]}" event:cache
  "${ARTISAN[@]}" queue:restart
}

rollback_application() {
  local failed_release
  failed_release="$(git rev-parse HEAD)"

  echo "Deployment failed; restoring application release ${previous_release}." >&2
  git checkout --detach "$previous_release"
  install_release
  optimize_release

  if ! curl --fail --silent --show-error --retry 5 --retry-connrefused \
    http://localhost/health >/dev/null; then
    echo "Rollback health check also failed. Escalate immediately." >&2
    return 1
  fi

  echo "Application rollback complete (${failed_release} -> ${previous_release})." >&2
}

on_error() {
  local exit_code=$?
  trap - ERR

  if [[ "$deployment_started" == "1" ]]; then
    rollback_application || true
  fi

  exit "$exit_code"
}

trap on_error ERR

echo "=== Deploying Jawla ${RELEASE_REF} ==="

git fetch --tags --prune origin
target_release="$(git rev-parse --verify "${RELEASE_REF}^{commit}")"
git checkout --detach "$target_release"
deployment_started=1

install_release

# Migrations are forward-only. Every production migration must use an
# expand/contract shape so the previous application release remains runnable.
"${ARTISAN[@]}" migrate --force
optimize_release

if ! curl --fail --silent --show-error --retry 5 --retry-connrefused \
  http://localhost/health >/dev/null; then
  echo "Readiness check failed." >&2
  false
fi

trap - ERR

echo "=== Deploy complete: $(git rev-parse HEAD) ==="
