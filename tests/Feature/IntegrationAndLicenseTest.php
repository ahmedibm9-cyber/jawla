<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\InstallationLicense;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Rules\SafeWebhookUrl;
use App\Services\Contracts\DnsResolver;
use App\Services\LicenseService;
use App\Services\PushService;
use App\Services\WebhookService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegrationAndLicenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_configured_push_gateway_receives_browser_subscription_and_payload(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $subscription = PushSubscription::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/subscription/1',
            'p256dh' => 'public-key',
            'auth' => 'auth-secret',
        ]);
        config()->set('jawla.push.gateway_url', 'https://gateway.example.test/send');
        config()->set('jawla.push.gateway_token', 'gateway-token');
        Http::fake(['gateway.example.test/*' => Http::response([], 202)]);

        $delivered = app(PushService::class)->send($user, ['title_en' => 'Task approved', 'url' => '/app/tasks']);

        self::assertSame(1, $delivered);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://gateway.example.test/send'
            && $request->hasHeader('Authorization', 'Bearer gateway-token')
            && $request['subscription']['endpoint'] === $subscription->endpoint
            && $request['payload']['title_en'] === 'Task approved');
    }

    public function test_webhook_delivery_is_hmac_signed_and_logged(): void
    {
        $company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $this->app->instance(DnsResolver::class, new class implements DnsResolver
        {
            public function addresses(string $host): array
            {
                return ['93.184.216.34'];
            }
        });
        $secret = str_repeat('s', 32);
        WebhookEndpoint::create([
            'company_id' => $company->id,
            'name' => 'ERP',
            'url' => 'https://erp.example.test/hooks/jawla',
            'secret' => $secret,
            'events' => ['sales_order.approved'],
            'is_active' => true,
        ]);
        Http::fake(['erp.example.test/*' => Http::response('accepted', 200)]);

        $count = app(WebhookService::class)->dispatch($company->id, 'sales_order.approved', ['sales_order_id' => 42]);
        $delivery = WebhookDelivery::query()->firstOrFail();

        self::assertSame('pending', $delivery->status);
        app(WebhookService::class)->attempt($delivery);

        self::assertSame(1, $count);
        $this->assertDatabaseHas('webhook_deliveries', [
            'company_id' => $company->id,
            'event_type' => 'sales_order.approved',
            'status' => 'succeeded',
            'attempts' => 1,
            'http_status' => 200,
        ]);
        Http::assertSent(function (Request $request): bool {
            $signature = $request->header('X-Jawla-Signature')[0] ?? '';

            return str_starts_with($signature, 'sha256=')
                && hash_equals('sha256='.hash_hmac('sha256', $request->body(), str_repeat('s', 32)), $signature);
        });
    }

    public function test_webhook_url_rule_rejects_private_network_targets(): void
    {
        $validator = Validator::make(
            ['url' => 'https://127.0.0.1/internal'],
            ['url' => [new SafeWebhookUrl]],
        );

        self::assertTrue($validator->fails());
    }

    public function test_webhook_rejects_private_dns_at_delivery_time(): void
    {
        $company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $this->app->instance(DnsResolver::class, new class implements DnsResolver
        {
            public function addresses(string $host): array
            {
                return ['169.254.169.254'];
            }
        });
        WebhookEndpoint::create([
            'company_id' => $company->id,
            'name' => 'Rebinding target',
            'url' => 'https://rebind.example.test/hook',
            'secret' => str_repeat('r', 32),
            'events' => ['sales_order.approved'],
            'is_active' => true,
        ]);
        Http::fake();

        app(WebhookService::class)->dispatch($company->id, 'sales_order.approved', []);
        $delivery = WebhookDelivery::query()->firstOrFail();
        app(WebhookService::class)->attempt($delivery);

        self::assertSame('failed', $delivery->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_due_webhook_is_retried_with_a_stable_event_id(): void
    {
        $company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $this->app->instance(DnsResolver::class, new class implements DnsResolver
        {
            public function addresses(string $host): array
            {
                return ['93.184.216.34'];
            }
        });
        WebhookEndpoint::create([
            'company_id' => $company->id,
            'name' => 'Retry target',
            'url' => 'https://retry.example.test/hook',
            'secret' => str_repeat('r', 32),
            'events' => ['sales_order.approved'],
            'is_active' => true,
        ]);
        Http::fake(['retry.example.test/*' => Http::sequence()->push('down', 503)->push('ok', 200)]);

        app(WebhookService::class)->dispatch($company->id, 'sales_order.approved', []);
        $delivery = WebhookDelivery::query()->firstOrFail();
        $eventId = $delivery->event_id;
        app(WebhookService::class)->attempt($delivery);
        $delivery->refresh()->update(['next_retry_at' => now()->subSecond()]);

        self::assertSame(1, app(WebhookService::class)->deliverDue());
        $delivery->refresh();
        self::assertSame('succeeded', $delivery->status);
        self::assertSame(2, $delivery->attempts);
        self::assertSame($eventId, $delivery->event_id);
        Http::assertSentCount(2);
    }

    public function test_webhook_secret_requires_strength_and_rotation_is_audited(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create()->assignRole('admin');
        app(ActiveCompanyContext::class)->setCompanyId($company->id);

        try {
            WebhookEndpoint::create([
                'company_id' => $company->id,
                'name' => 'Weak',
                'url' => 'https://example.com/hook',
                'secret' => 'weak',
                'events' => ['sales_order.approved'],
                'is_active' => true,
            ]);
            $this->fail('A weak signing secret must be rejected.');
        } catch (\DomainException) {
            $this->assertDatabaseCount('webhook_endpoints', 0);
        }

        $endpoint = WebhookEndpoint::create([
            'company_id' => $company->id,
            'name' => 'Strong',
            'url' => 'https://example.com/hook',
            'secret' => str_repeat('a', 32),
            'events' => ['sales_order.approved'],
            'is_active' => true,
        ]);
        $newSecret = app(WebhookService::class)->rotateSecret($endpoint, $admin);

        self::assertGreaterThanOrEqual(43, strlen($newSecret));
        self::assertNotSame(str_repeat('a', 32), $endpoint->fresh()->secret);
        $this->assertDatabaseHas('activities', ['type' => 'webhook_secret_rotated']);
    }

    public function test_vendor_signed_license_is_verified_without_storing_a_plain_license_key(): void
    {
        $keys = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($keys === false) {
            $this->markTestSkipped('The local PHP OpenSSL build has no usable openssl.cnf for RSA key generation.');
        }
        openssl_pkey_export($keys, $privateKey);
        $publicKey = openssl_pkey_get_details($keys)['key'];
        config()->set('jawla.license.public_key', $publicKey);
        config()->set('jawla.license.installation_id', 'install-cairo-01');

        $actorCompany = Company::factory()->create();
        $admin = User::factory()->for($actorCompany)->create()->assignRole('admin');
        $document = json_encode([
            'license_id' => (string) Str::uuid(),
            'licensee' => 'Client Company',
            'installation_id' => 'install-cairo-01',
            'edition' => 'enterprise',
            'max_users' => 100,
            'features' => ['field_sales', 'webhooks'],
            'valid_from' => today()->subDay()->toDateString(),
            'expires_at' => today()->addYear()->toDateString(),
        ], JSON_THROW_ON_ERROR);
        openssl_sign($document, $rawSignature, $privateKey, OPENSSL_ALGO_SHA256);

        $license = app(LicenseService::class)->install($document, base64_encode($rawSignature), $admin);

        self::assertSame('active', $license->status);
        self::assertSame('Client Company', $license->licensee);
        self::assertSame(hash('sha256', $document), $license->document_hash);
        self::assertArrayNotHasKey('raw_document', $license->toArray());
        self::assertSame($license->id, app(LicenseService::class)->assertValid()->id);
    }

    public function test_runtime_route_fails_closed_when_license_is_missing(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->for($company)->create()->assignRole('rep');
        config()->set('jawla.is_demo', false);

        $this->actingAs($rep)->get('/app')->assertRedirect(route('license.recovery'));
    }

    public function test_signed_claims_overwrite_database_tampering(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create()->assignRole('admin');
        $license = $this->installLicense($admin, ['max_users' => 5, 'features' => ['field_sales']]);
        $license->update(['max_users' => 5000, 'features' => ['webhooks'], 'edition' => 'tampered']);

        $verified = app(LicenseService::class)->verify($license);

        self::assertSame(5, $verified->max_users);
        self::assertSame(['field_sales'], $verified->features);
        self::assertSame('enterprise', $verified->edition);
    }

    public function test_expired_not_yet_valid_and_disabled_feature_licenses_fail_closed(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create()->assignRole('admin');
        $expired = $this->installLicense($admin, [
            'valid_from' => today()->subYear()->toDateString(),
            'expires_at' => today()->subDay()->toDateString(),
        ]);
        config()->set('jawla.is_demo', false);

        try {
            app(LicenseService::class)->assertRuntimeValid();
            $this->fail('Expired licenses must fail closed.');
        } catch (\DomainException) {
            self::assertSame('expired', $expired->fresh()->status);
        }

        config()->set('jawla.is_demo', true);
        $future = $this->installLicense($admin, [
            'valid_from' => today()->addDay()->toDateString(),
            'expires_at' => today()->addYear()->toDateString(),
        ]);
        config()->set('jawla.is_demo', false);
        try {
            app(LicenseService::class)->assertRuntimeValid();
            $this->fail('Not-yet-valid licenses must fail closed.');
        } catch (\DomainException) {
            self::assertSame('not_yet_valid', $future->fresh()->status);
        }

        config()->set('jawla.is_demo', true);
        $this->installLicense($admin, ['features' => ['field_sales']]);
        config()->set('jawla.is_demo', false);
        $this->expectException(\DomainException::class);
        app(LicenseService::class)->assertRuntimeFeature('webhooks');
    }

    public function test_active_user_limit_is_enforced_on_creation(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create()->assignRole('admin');
        User::factory()->for($company)->create();
        $this->installLicense($admin, ['max_users' => 2]);
        config()->set('jawla.is_demo', false);

        $this->expectException(\DomainException::class);
        User::factory()->for($company)->create(['is_active' => true]);
    }

    private function installLicense(User $admin, array $overrides = []): InstallationLicense
    {
        $keys = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($keys === false) {
            $this->markTestSkipped('The local PHP OpenSSL build cannot generate RSA keys.');
        }
        openssl_pkey_export($keys, $privateKey);
        $publicKey = openssl_pkey_get_details($keys)['key'];
        config()->set('jawla.license.public_key', $publicKey);
        config()->set('jawla.license.installation_id', 'test-installation');
        $payload = array_replace([
            'license_id' => (string) Str::uuid(),
            'licensee' => 'Test Client',
            'installation_id' => 'test-installation',
            'edition' => 'enterprise',
            'max_users' => 100,
            'features' => ['field_sales', 'webhooks'],
            'valid_from' => today()->subDay()->toDateString(),
            'expires_at' => today()->addYear()->toDateString(),
        ], $overrides);
        $document = json_encode($payload, JSON_THROW_ON_ERROR);
        openssl_sign($document, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return app(LicenseService::class)->install($document, base64_encode($signature), $admin);
    }
}
