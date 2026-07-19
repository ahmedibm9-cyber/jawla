#!/bin/bash

# Stress Test Script for Jawla
# Tests: Health endpoint, Login page, Concurrent requests

BASE_URL="${BASE_URL:-http://localhost:8000}"
RESULTS_DIR="$(dirname "$0")"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "========================================"
echo "  Jawla Stress Tests"
echo "========================================"
echo "Base URL: $BASE_URL"
echo "Timestamp: $TIMESTAMP"
echo ""

# Test 1: Health Endpoint - Sequential requests
echo "=== Test 1: Health Endpoint (Sequential) ==="
echo "Running 100 sequential requests..."
START=$(date +%s%N)
for i in $(seq 1 100); do
    curl -s -o /dev/null -w "%{http_code} %{time_total}\n" "$BASE_URL/health" >> "$RESULTS_DIR/health-sequential-$TIMESTAMP.txt"
done
END=$(date +%s%N)
DURATION=$(( (END - START) / 1000000 ))
echo "Completed 100 requests in ${DURATION}ms"
echo "Average: $((DURATION / 100))ms per request"
echo ""

# Test 2: Health Endpoint - Concurrent requests (5 at a time)
echo "=== Test 2: Health Endpoint (5 Concurrent) ==="
echo "Running 50 requests with 5 concurrent..."
START=$(date +%s%N)
for i in $(seq 1 10); do
    for j in $(seq 1 5); do
        curl -s -o /dev/null -w "%{http_code} %{time_total}\n" "$BASE_URL/health" >> "$RESULTS_DIR/health-concurrent-5-$TIMESTAMP.txt" &
    done
    wait
done
END=$(date +%s%N)
DURATION=$(( (END - START) / 1000000 ))
echo "Completed 50 requests (5 concurrent) in ${DURATION}ms"
echo "Average: $((DURATION / 10))ms per batch"
echo ""

# Test 3: Health Endpoint - High concurrency (20 at a time)
echo "=== Test 3: Health Endpoint (20 Concurrent - Spike) ==="
echo "Running 100 requests with 20 concurrent..."
START=$(date +%s%N)
for i in $(seq 1 5); do
    for j in $(seq 1 20); do
        curl -s -o /dev/null -w "%{http_code} %{time_total}\n" "$BASE_URL/health" >> "$RESULTS_DIR/health-concurrent-20-$TIMESTAMP.txt" &
    done
    wait
done
END=$(date +%s%N)
DURATION=$(( (END - START) / 1000000 ))
echo "Completed 100 requests (20 concurrent) in ${DURATION}ms"
echo "Average: $((DURATION / 5))ms per batch"
echo ""

# Test 4: Login Page - Sequential requests
echo "=== Test 4: Login Page (Sequential) ==="
echo "Running 50 sequential requests..."
START=$(date +%s%N)
for i in $(seq 1 50); do
    curl -s -o /dev/null -w "%{http_code} %{time_total}\n" "$BASE_URL/admin/login" >> "$RESULTS_DIR/login-sequential-$TIMESTAMP.txt"
done
END=$(date +%s%N)
DURATION=$(( (END - START) / 1000000 ))
echo "Completed 50 requests in ${DURATION}ms"
echo "Average: $((DURATION / 50))ms per request"
echo ""

# Test 5: Login Page - Concurrent requests (10 at a time)
echo "=== Test 5: Login Page (10 Concurrent) ==="
echo "Running 50 requests with 10 concurrent..."
START=$(date +%s%N)
for i in $(seq 1 5); do
    for j in $(seq 1 10); do
        curl -s -o /dev/null -w "%{http_code} %{time_total}\n" "$BASE_URL/admin/login" >> "$RESULTS_DIR/login-concurrent-10-$TIMESTAMP.txt" &
    done
    wait
done
END=$(date +%s%N)
DURATION=$(( (END - START) / 1000000 ))
echo "Completed 50 requests (10 concurrent) in ${DURATION}ms"
echo "Average: $((DURATION / 5))ms per batch"
echo ""

# Analyze results
echo "========================================"
echo "  RESULTS ANALYSIS"
echo "========================================"

# Calculate success rates
for file in "$RESULTS_DIR"/*-$TIMESTAMP.txt; do
    if [ -f "$file" ]; then
        TEST_NAME=$(basename "$file" .txt)
        TOTAL=$(wc -l < "$file")
        SUCCESS=$(grep -c "^200\|^302" "$file" || echo "0")
        FAILED=$((TOTAL - SUCCESS))
        SUCCESS_RATE=$((SUCCESS * 100 / TOTAL))
        
        echo "$TEST_NAME:"
        echo "  Total: $TOTAL, Success: $SUCCESS, Failed: $FAILED, Rate: ${SUCCESS_RATE}%"
        
        # Calculate average response time
        AVG_TIME=$(awk '{sum += $2} END {printf "%.2f", sum/NR}' "$file")
        echo "  Average Response Time: ${AVG_TIME}s"
        echo ""
    fi
done

echo "========================================"
echo "  STRESS TEST COMPLETE"
echo "========================================"
