<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Rules\SafeWebhookUrl;
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
        WebhookEndpoint::create([
            'company_id' => $company->id,
            'name' => 'ERP',
            'url' => 'https://erp.example.test/hooks/jawla',
            'secret' => 'shared-secret',
            'events' => ['sales_order.approved'],
            'is_active' => true,
        ]);
        Http::fake(['erp.example.test/*' => Http::response('accepted', 200)]);

        $count = app(WebhookService::class)->dispatch($company->id, 'sales_order.approved', ['sales_order_id' => 42]);

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
                && hash_equals('sha256='.hash_hmac('sha256', $request->body(), 'shared-secret'), $signature);
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
}
