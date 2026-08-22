<?php

namespace Tests\Feature\Deployment;

use Tests\TestCase;

class ReleasePipelineTest extends TestCase
{
    public function test_ci_has_blocking_runtime_analysis_build_security_and_browser_gates(): void
    {
        $ci = file_get_contents(base_path('.github/workflows/ci.yml'));
        $security = file_get_contents(base_path('.github/workflows/security.yml'));

        $this->assertIsString($ci);
        $this->assertIsString($security);
        $this->assertStringContainsString('static-analysis:', $ci);
        $this->assertStringContainsString('phpstan analyse --level=0', $ci);
        $this->assertStringContainsString('browser-test:', $ci);
        $this->assertStringContainsString('npm run test:pwa-browser', $ci);
        $this->assertStringContainsString('npm run build', $ci);
        $this->assertStringContainsString('npm run test:offline', $ci);
        $this->assertStringContainsString('secret-scan:', $ci);
        $this->assertStringContainsString('dependency-audit:', $ci);
        $this->assertStringNotContainsString('continue-on-error: true', $ci);
        $this->assertStringNotContainsString('continue-on-error: true', $security);
        $this->assertStringNotContainsString('zap-report.md || true', $security);
        $this->assertDoesNotMatchRegularExpression('/uses:\s+[^@\s]+@v\d+\b/', $ci);
        $this->assertDoesNotMatchRegularExpression('/uses:\s+[^@\s]+@v\d+\b/', $security);
        $this->assertDoesNotMatchRegularExpression('/image:\s+\S+:\d+\s*$/m', $ci);
        $this->assertDoesNotMatchRegularExpression('/image:\s+\S+:\d+\s*$/m', $security);
    }

    public function test_deploy_promotes_one_ci_commit_through_staging_and_protected_production(): void
    {
        $deploy = file_get_contents(base_path('.github/workflows/deploy.yml'));

        $this->assertIsString($deploy);
        $this->assertStringContainsString('workflow_run:', $deploy);
        $this->assertStringContainsString('github.event.workflow_run.conclusion == \'success\'', $deploy);
        $this->assertStringContainsString('ref: ${{ env.RELEASE_SHA }}', $deploy);
        $this->assertStringContainsString('--environment staging', $deploy);
        $this->assertStringContainsString('staging-dast:', $deploy);
        $this->assertStringContainsString('name: production', $deploy);
        $this->assertStringContainsString('--environment production', $deploy);
        $this->assertStringContainsString('/health', $deploy);
        $this->assertDoesNotMatchRegularExpression('/uses:\s+[^@\s]+@v\d+\b/', $deploy);
        $this->assertStringContainsString('zaproxy:stable@sha256:', $deploy);
    }

    public function test_railway_and_container_use_dependency_readiness_and_private_photos(): void
    {
        $railway = file_get_contents(base_path('railway.toml'));
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $dockerignore = file_get_contents(base_path('.dockerignore'));
        $filesystems = file_get_contents(config_path('filesystems.php'));

        $this->assertIsString($railway);
        $this->assertIsString($dockerfile);
        $this->assertIsString($dockerignore);
        $this->assertIsString($filesystems);
        $this->assertStringContainsString('healthcheckPath = "/health"', $railway);
        $this->assertStringContainsString('restartPolicyType = "ON_FAILURE"', $railway);
        $this->assertStringContainsString("env('APP_ENV') === 'production' ? 's3' : 'public'", $filesystems);
        $this->assertStringContainsString('FROM node:22-alpine@sha256:', $dockerfile);
        $this->assertStringContainsString('npm ci', $dockerfile);
        $this->assertStringContainsString('COPY --from=php-dependencies /app/vendor /app/vendor', $dockerfile);
        $this->assertStringContainsString('.env*', $dockerignore);
        $this->assertStringContainsString('storage/*', $dockerignore);
        $this->assertStringContainsString('check_*.php', $dockerignore);
        $this->assertStringContainsString('fix_*.php', $dockerignore);
    }

    public function test_rollback_and_restore_require_explicit_evidence_bearing_inputs(): void
    {
        $rollback = file_get_contents(base_path('.github/workflows/rollback.yml'));
        $restore = file_get_contents(base_path('scripts/restore-backup.sh'));

        $this->assertIsString($rollback);
        $this->assertIsString($restore);
        $this->assertStringContainsString('ROLLBACK_PRODUCTION', $rollback);
        $this->assertStringContainsString('canRollback', $rollback);
        $this->assertStringContainsString('deploymentRollback', $rollback);
        $this->assertStringContainsString('SOURCE_DATABASE_URL', $restore);
        $this->assertStringContainsString('RESTORE_EVIDENCE_FILE', $restore);
        $this->assertStringContainsString('result=PASS', $restore);
    }

    public function test_production_health_monitor_retries_and_opens_an_incident(): void
    {
        $monitor = file_get_contents(base_path('.github/workflows/production-health.yml'));

        $this->assertIsString($monitor);
        $this->assertStringContainsString('cron: "*/5 * * * *"', $monitor);
        $this->assertStringContainsString('issues: write', $monitor);
        $this->assertStringContainsString('/health', $monitor);
        $this->assertStringContainsString('for attempt in 1 2 3', $monitor);
        $this->assertStringContainsString('gh issue create', $monitor);
        $this->assertStringContainsString('gh issue close', $monitor);
        $this->assertStringContainsString('.status == "ok"', $monitor);
        $this->assertStringContainsString('.db == "ok"', $monitor);
        $this->assertStringContainsString('.cache == "ok"', $monitor);
    }
}
