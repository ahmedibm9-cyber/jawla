<?php

namespace Tests\Feature\Deployment;

use App\Models\Company;
use App\Models\User;
use App\Services\PdfService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionDeploymentSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordinary_deploy_never_runs_bootstrap_or_demo_seeders(): void
    {
        $railway = file_get_contents(base_path('railway.toml'));

        $this->assertIsString($railway);
        $this->assertStringNotContainsString('DemoSeeder', $railway);
        $this->assertStringNotContainsString('db:seed', $railway);
        $this->assertStringNotContainsString('seed-super-admin', $railway);
        $this->assertStringNotContainsString('bootstrap-production', $railway);
    }

    public function test_demo_seeder_refuses_to_run_outside_explicit_demo_mode(): void
    {
        config()->set('jawla.mode', 'production');

        $this->expectException(\LogicException::class);
        $this->seed(DemoSeeder::class);
    }

    public function test_demo_mode_has_a_permanent_bilingual_evaluation_banner(): void
    {
        config()->set('jawla.is_demo', true);

        $banner = Blade::render('<x-runtime-banners />');

        $this->assertStringContainsString('DEMO / EVALUATION', $banner);
        $this->assertStringContainsString('وضع تجريبي / للتقييم', $banner);
        $this->assertStringContainsString('SAMPLE, NOT A TAX INVOICE', $banner);
    }

    public function test_demo_credentials_are_generated_and_never_printed_as_defaults(): void
    {
        $source = file_get_contents(database_path('seeders/DemoSeeder.php'));
        $perfSource = file_get_contents(database_path('seeders/PerfUserSeeder.php'));
        $mixedWorkload = file_get_contents(base_path('tests/stress/k6-mixed-workload.js'));
        $loginStress = file_get_contents(base_path('tests/stress/k6-login-stress.js'));

        $this->assertIsString($source);
        $this->assertIsString($perfSource);
        $this->assertIsString($mixedWorkload);
        $this->assertIsString($loginStress);
        $this->assertStringContainsString('Str::password(', $source);
        $this->assertStringContainsString('demo-credentials.json', $source);
        $this->assertStringNotContainsString("Hash::make('password')", $source);
        $this->assertStringNotContainsString('/ password', $source);
        $this->assertStringNotContainsString("?: 'password'", $perfSource);
        $this->assertStringNotContainsString('password: "password"', $mixedWorkload);
        $this->assertStringNotContainsString('password: "password"', $loginStress);
    }

    public function test_demo_financial_documents_have_the_bilingual_sample_watermark(): void
    {
        config()->set('jawla.is_demo', true);
        $method = new \ReflectionMethod(PdfService::class, 'demoWatermark');

        $watermark = $method->invoke(app(PdfService::class));

        $this->assertStringContainsString('SAMPLE — NOT A TAX INVOICE', $watermark);
        $this->assertStringContainsString('عينة — ليست فاتورة ضريبية', $watermark);

        config()->set('jawla.is_demo', false);
        $this->assertSame('', $method->invoke(app(PdfService::class)));
    }

    public function test_default_database_seeder_does_not_create_demo_business_data_in_production_mode(): void
    {
        config()->set('jawla.mode', 'production');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Company::count());
        $this->assertSame(0, User::count());
    }

    public function test_one_time_production_bootstrap_uses_secret_environment_password_and_cannot_repeat(): void
    {
        config()->set('jawla.mode', 'production');
        $password = 'S3cure!'.bin2hex(random_bytes(12));
        putenv("JAWLA_BOOTSTRAP_ADMIN_PASSWORD={$password}");

        try {
            $exit = Artisan::call('app:bootstrap-production', [
                '--confirm' => 'BOOTSTRAP',
                '--company-name-en' => 'Jawla Test Company',
                '--company-name-ar' => 'شركة جولة التجريبية',
                '--tax-number' => 'TEST-BOOTSTRAP-001',
                '--admin-name' => 'Initial Administrator',
                '--admin-email' => 'initial-admin@example.test',
            ]);

            $this->assertSame(0, $exit, Artisan::output());
            $this->assertStringNotContainsString($password, Artisan::output());

            $company = Company::where('tax_number', 'TEST-BOOTSTRAP-001')->firstOrFail();
            $user = User::where('email', 'initial-admin@example.test')->firstOrFail();

            $this->assertSame($company->id, $user->company_id);
            $this->assertTrue(Hash::check($password, $user->password));
            $this->assertTrue($user->hasAllRoles(['sales_manager', 'hr_admin', 'warehouse_keeper']));

            $secondExit = Artisan::call('app:bootstrap-production', [
                '--confirm' => 'BOOTSTRAP',
                '--company-name-en' => 'Another Company',
                '--company-name-ar' => 'شركة أخرى',
                '--tax-number' => 'TEST-BOOTSTRAP-002',
                '--admin-name' => 'Another Administrator',
                '--admin-email' => 'another-admin@example.test',
            ]);

            $this->assertSame(1, $secondExit);
            $this->assertSame(1, Company::count());
            $this->assertSame(1, User::count());
        } finally {
            putenv('JAWLA_BOOTSTRAP_ADMIN_PASSWORD');
        }
    }
}
