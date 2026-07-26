<?php

namespace Tests\Feature\Finance;

use App\Exceptions\Domain\DomainException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ReturnService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceServiceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_viewer_cannot_mutate_invoice_payment_or_return_services(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $viewer = User::factory()->create(['company_id' => $company->id]);
        $viewer->assignRole('system_viewer');
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        Warehouse::factory()->create([
            'company_id' => $company->id,
            'type' => 'van',
            'user_id' => $viewer->id,
        ]);
        $this->actingAs($viewer);

        foreach ([
            fn () => app(InvoiceService::class)->create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
            ]),
            fn () => app(PaymentService::class)->collect(
                $company->id, $viewer->id, $customer->id, 10, 'cash',
            ),
            fn () => app(ReturnService::class)->create(
                $company->id, $viewer->id, $customer->id, [], 1,
            ),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Read-only system viewer executed a finance mutation.');
            } catch (DomainException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('returns', 0);
    }
}
