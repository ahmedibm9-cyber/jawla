<?php

namespace Tests\Feature\Policies;

use App\Models\Alarm;
use App\Models\Complaint;
use App\Models\DailyVisitAssignment;
use App\Models\Invoice;
use App\Models\PriceQuotationRequest;
use App\Models\ProformaInvoice;
use App\Models\PurchaseRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourcePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    public function test_invoice_view_allows_admin_sales_manager_accounts(): void
    {
        foreach (['admin', 'sales_manager', 'accounts'] as $role) {
            $this->assertTrue($this->makeUser($role)->can('viewAny', Invoice::class));
        }
    }

    public function test_invoice_view_denies_others(): void
    {
        foreach (['purchasing', 'warehouse_keeper', 'executive', 'rep'] as $role) {
            $this->assertFalse($this->makeUser($role)->can('viewAny', Invoice::class));
        }
    }

    public function test_invoice_create_allows_admin_sales_manager(): void
    {
        $this->assertTrue($this->makeUser('admin')->can('create', Invoice::class));
        $this->assertTrue($this->makeUser('sales_manager')->can('create', Invoice::class));
        $this->assertFalse($this->makeUser('accounts')->can('create', Invoice::class));
    }

    public function test_invoice_delete_admin_only(): void
    {
        $this->assertTrue($this->makeUser('admin')->can('delete', Invoice::class));
        foreach (['sales_manager', 'accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'] as $role) {
            $this->assertFalse($this->makeUser($role)->can('delete', Invoice::class));
        }
    }

    public function test_proforma_invoice_view_allows_admin_sales_manager_accounts(): void
    {
        foreach (['admin', 'sales_manager', 'accounts'] as $role) {
            $this->assertTrue($this->makeUser($role)->can('viewAny', ProformaInvoice::class));
        }
    }

    public function test_proforma_invoice_view_denies_others(): void
    {
        foreach (['purchasing', 'warehouse_keeper', 'executive', 'rep'] as $role) {
            $this->assertFalse($this->makeUser($role)->can('viewAny', ProformaInvoice::class));
        }
    }

    public function test_daily_visit_assignment_allows_admin_sales_manager(): void
    {
        foreach (['admin', 'sales_manager'] as $role) {
            $this->assertTrue($this->makeUser($role)->can('viewAny', DailyVisitAssignment::class));
            $this->assertTrue($this->makeUser($role)->can('create', DailyVisitAssignment::class));
        }
    }

    public function test_daily_visit_assignment_denies_others(): void
    {
        foreach (['accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'] as $role) {
            $this->assertFalse($this->makeUser($role)->can('viewAny', DailyVisitAssignment::class));
        }
    }

    public function test_purchase_request_view_allows_admin_sales_manager_purchasing(): void
    {
        foreach (['admin', 'sales_manager', 'purchasing'] as $role) {
            $this->assertTrue($this->makeUser($role)->can('viewAny', PurchaseRequest::class));
        }
    }

    public function test_purchase_request_create_allows_admin_sales_manager(): void
    {
        $this->assertTrue($this->makeUser('admin')->can('create', PurchaseRequest::class));
        $this->assertTrue($this->makeUser('sales_manager')->can('create', PurchaseRequest::class));
        $this->assertFalse($this->makeUser('purchasing')->can('create', PurchaseRequest::class));
    }

    public function test_complaint_allows_admin_sales_manager(): void
    {
        foreach (['admin', 'sales_manager'] as $role) {
            $this->assertTrue($this->makeUser($role)->can('viewAny', Complaint::class));
            $this->assertTrue($this->makeUser($role)->can('update', Complaint::class));
        }
    }

    public function test_complaint_denies_others(): void
    {
        foreach (['accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'] as $role) {
            $this->assertFalse($this->makeUser($role)->can('viewAny', Complaint::class));
        }
    }

    public function test_alarm_allows_admin_sales_manager_executive(): void
    {
        foreach (['admin', 'sales_manager', 'executive'] as $role) {
            $this->assertTrue($this->makeUser($role)->can('viewAny', Alarm::class));
        }
    }

    public function test_alarm_denies_others(): void
    {
        foreach (['accounts', 'purchasing', 'warehouse_keeper', 'rep'] as $role) {
            $this->assertFalse($this->makeUser($role)->can('viewAny', Alarm::class));
        }
    }

    public function test_price_quotation_request_allows_admin_sales_manager(): void
    {
        foreach (['admin', 'sales_manager'] as $role) {
            $this->assertTrue($this->makeUser($role)->can('viewAny', PriceQuotationRequest::class));
        }
    }

    public function test_price_quotation_request_denies_others(): void
    {
        foreach (['accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep'] as $role) {
            $this->assertFalse($this->makeUser($role)->can('viewAny', PriceQuotationRequest::class));
        }
    }
}
