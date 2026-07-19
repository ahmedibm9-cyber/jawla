const autocannon = require("autocannon");
const fs = require("fs");
const path = require("path");

const BASE_URL = process.env.BASE_URL || "http://localhost:8000";
const RESULTS_DIR = path.join(__dirname);

async function runStressTest(config) {
  return new Promise((resolve, reject) => {
    const instance = autocannon(config, (err, result) => {
      if (err) reject(err);
      else resolve(result);
    });
  });
}

async function testHealthEndpoint() {
  console.log("\n=== Stress Test 1: Health Endpoint ===");
  console.log(`Target: ${BASE_URL}/health`);
  console.log("Config: 10 connections, 30 seconds, pipelining 10\n");

  const result = await runStressTest({
    url: `${BASE_URL}/health`,
    connections: 10,
    duration: 30,
    pipelining: 10,
  });

  console.log("Results:");
  console.log(`  Total Requests: ${result.requests.total}`);
  console.log(`  Requests/sec: ${result.requests.average}`);
  console.log(`  Latency (avg): ${result.latency.average}ms`);
  console.log(`  Latency (p99): ${result.latency.p99}ms`);
  console.log(`  Throughput: ${result.throughput.average} bytes/sec`);
  console.log(`  Errors: ${result.errors}`);

  fs.writeFileSync(
    path.join(RESULTS_DIR, "health-stress-results.json"),
    JSON.stringify(result, null, 2)
  );

  return result;
}

async function testLoginPage() {
  console.log("\n=== Stress Test 2: Login Page ===");
  console.log(`Target: ${BASE_URL}/admin/login`);
  console.log("Config: 5 connections, 30 seconds\n");

  const result = await runStressTest({
    url: `${BASE_URL}/admin/login`,
    connections: 5,
    duration: 30,
    method: "GET",
  });

  console.log("Results:");
  console.log(`  Total Requests: ${result.requests.total}`);
  console.log(`  Requests/sec: ${result.requests.average}`);
  console.log(`  Latency (avg): ${result.latency.average}ms`);
  console.log(`  Latency (p99): ${result.latency.p99}ms`);
  console.log(`  Errors: ${result.errors}`);

  fs.writeFileSync(
    path.join(RESULTS_DIR, "login-stress-results.json"),
    JSON.stringify(result, null, 2)
  );

  return result;
}

async function testConcurrentUsers() {
  console.log("\n=== Stress Test 3: Concurrent Users (Spike Test) ===");
  console.log(`Target: ${BASE_URL}/health`);
  console.log("Config: 50 connections, 60 seconds\n");

  const result = await runStressTest({
    url: `${BASE_URL}/health`,
    connections: 50,
    duration: 60,
    pipelining: 5,
  });

  console.log("Results:");
  console.log(`  Total Requests: ${result.requests.total}`);
  console.log(`  Requests/sec: ${result.requests.average}`);
  console.log(`  Latency (avg): ${result.latency.average}ms`);
  console.log(`  Latency (p99): ${result.latency.p99}ms`);
  console.log(`  Errors: ${result.errors}`);

  fs.writeFileSync(
    path.join(RESULTS_DIR, "spike-stress-results.json"),
    JSON.stringify(result, null, 2)
  );

  return result;
}

async function main() {
  console.log("========================================");
  console.log("  Jawla Stress Tests");
  console.log("========================================");
  console.log(`Base URL: ${BASE_URL}`);
  console.log(`Date: ${new Date().toISOString()}`);

  try {
    const healthResult = await testHealthEndpoint();
    const loginResult = await testLoginPage();
    const spikeResult = await testConcurrentUsers();

    console.log("\n========================================");
    console.log("  SUMMARY");
    console.log("========================================");
    console.log(
      `Health Endpoint: ${healthResult.requests.average} req/s, ${healthResult.latency.average}ms avg latency`
    );
    console.log(
      `Login Page: ${loginResult.requests.average} req/s, ${loginResult.latency.average}ms avg latency`
    );
    console.log(
      `Spike Test: ${spikeResult.requests.average} req/s, ${spikeResult.latency.average}ms avg latency`
    );

    const allPassed =
      healthResult.errors === 0 &&
      loginResult.errors === 0 &&
      spikeResult.latency.p99 < 5000;

    console.log(`\nOverall: ${allPassed ? "PASS" : "FAIL"}`);
    process.exit(allPassed ? 0 : 1);
  } catch (error) {
    console.error("Stress test failed:", error);
    process.exit(1);
  }
}

main();
