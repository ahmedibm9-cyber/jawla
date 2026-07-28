#!/usr/bin/env bash
# Standalone health check — exit 0 if healthy, exit 1 if degraded.
# Usage: ./scripts/uptime-check.sh [URL]
set -euo pipefail

URL="${1:-http://localhost:8000/health}"
response=$(curl -sf --max-time 10 "$URL" 2>/dev/null) || { echo "UNREACHABLE"; exit 1; }
status=$(echo "$response" | grep -o '"status":"[^"]*"' | head -1 | cut -d'"' -f4)
if [ "$status" = "ok" ]; then echo "OK"; exit 0; else echo "DEGRADED: $response"; exit 1; fi
