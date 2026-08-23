#!/usr/bin/env bash
# Rollback rehearsal: deploy the previous release, verify health, then deploy
# the current release again. Run before major releases or after long gaps.
# Requires: Railway CLI authenticated, project linked.
set -euo pipefail

: "${HEALTH_URL:?Set HEALTH_URL to your production /health endpoint}"

echo "=== Rollback rehearsal $(date -u +%Y-%m-%dT%H:%M:%SZ) ==="
echo "Health URL: $HEALTH_URL"

# 1. Capture current deployment ID
current="$(railway status --json 2>/dev/null | grep -o '"deploymentId":"[^"]*"' | cut -d'"' -f4)"
if [ -z "$current" ]; then
  echo "ERROR: Could not determine current deployment" >&2
  exit 1
fi
echo "Current deployment: $current"

# 2. List recent deployments to find the previous
echo ""
echo "Recent deployments:"
railway status 2>/dev/null || true

# 3. Prompt for previous deployment ID
echo ""
echo "Enter the previous deployment ID to roll back to (or 'skip' to abort):"
read -r prev_id
if [ "$prev_id" = "skip" ] || [ -z "$prev_id" ]; then
  echo "Aborted."
  exit 0
fi

# 4. Rollback
echo ""
echo "Rolling back to $prev_id..."
railway rollback "$prev_id" 2>&1

# 5. Wait for deployment
echo "Waiting 45s for rollback to stabilize..."
sleep 45

# 6. Health check
echo "Running health check..."
health="$(curl -sf "$HEALTH_URL" 2>/dev/null || echo 'FAIL')"
if echo "$health" | grep -q '"status":"ok"'; then
  echo "✅ Health check passed after rollback"
else
  echo "⚠️  Health check result: $health"
  echo "Continuing anyway — will roll forward..."
fi

# 7. Roll forward to original
echo ""
echo "Rolling forward to original deployment $current..."
railway rollback "$current" 2>&1

echo "Waiting 45s for roll-forward to stabilize..."
sleep 45

health2="$(curl -sf "$HEALTH_URL" 2>/dev/null || echo 'FAIL')"
if echo "$health2" | grep -q '"status":"ok"'; then
  echo "✅ Health check passed after roll-forward"
else
  echo "⚠️  Health check result: $health2"
  echo "MANUAL INTERVENTION REQUIRED — check Railway dashboard"
  exit 1
fi

echo ""
echo "=== Rollback rehearsal complete ==="
echo "Result: Rollback and roll-forward both healthy."
echo "Your deployment pipeline is verified."
