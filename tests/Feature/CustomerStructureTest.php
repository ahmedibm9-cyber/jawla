<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerStructureService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerStructureTest extends TestCase
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

    public function test_customer_supports_outlets_contacts_locations_and_rep_assignments(): void
    {
        $company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $customer = Customer::factory()->for($company)->create(['route_id' => null]);
        $manager = User::factory()->for($company)->create()->assignRole('sales_manager');
        $rep = User::factory()->for($company)->create()->assignRole('rep');
        $service = app(CustomerStructureService::class);

        $outlet = $service->createOutlet($customer, [
            'code' => 'DOWNTOWN',
            'name_ar' => 'فرع وسط البلد',
            'name_en' => 'Downtown outlet',
        ]);
        $contact = $service->addContact($customer, [
            'customer_outlet_id' => $outlet->id,
            'name' => 'Mona',
            'phone' => '01000000000',
            'is_primary' => true,
        ]);
        $location = $service->addLocation($customer, [
            'customer_outlet_id' => $outlet->id,
            'type' => 'visit',
            'label' => 'Main entrance',
            'address' => 'Downtown Cairo',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'is_primary' => true,
        ]);
        $assignment = $service->assignRep($customer, $rep, $manager, [
            'customer_outlet_id' => $outlet->id,
            'assignment_type' => 'primary',
        ]);

        self::assertSame($outlet->id, $contact->customer_outlet_id);
        self::assertSame($outlet->id, $location->customer_outlet_id);
        self::assertSame($rep->id, $assignment->user_id);
        self::assertCount(1, $customer->outlets()->get());
        self::assertCount(1, $customer->contacts()->get());
        self::assertCount(1, $customer->locations()->get());
        self::assertCount(1, $customer->assignments()->get());
    }

    public function test_new_primary_location_replaces_previous_primary_for_same_outlet_and_type(): void
    {
        $company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $customer = Customer::factory()->for($company)->create(['route_id' => null]);
        $service = app(CustomerStructureService::class);

        $first = $service->addLocation($customer, [
            'type' => 'billing', 'label' => 'Old', 'address' => 'Old address', 'is_primary' => true,
        ]);
        $second = $service->addLocation($customer, [
            'type' => 'billing', 'label' => 'New', 'address' => 'New address', 'is_primary' => true,
        ]);

        self::assertFalse($first->fresh()->is_primary);
        self::assertTrue($second->is_primary);
    }

    public function test_outlet_from_another_customer_cannot_be_attached_to_a_contact(): void
    {
        $company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $customer = Customer::factory()->for($company)->create(['route_id' => null]);
        $otherCustomer = Customer::factory()->for($company)->create(['route_id' => null]);
        $otherOutlet = app(CustomerStructureService::class)->createOutlet($otherCustomer, [
            'code' => 'OTHER', 'name_ar' => 'آخر',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(CustomerStructureService::class)->addContact($customer, [
            'customer_outlet_id' => $otherOutlet->id,
            'name' => 'Invalid contact',
        ]);
    }

    public function test_cross_company_rep_assignment_is_rejected(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $customer = Customer::factory()->for($company)->create(['route_id' => null]);
        $manager = User::factory()->for($company)->create()->assignRole('sales_manager');
        $externalRep = User::factory()->for($otherCompany)->create()->assignRole('rep');

        $this->expectException(AuthorizationException::class);
        app(CustomerStructureService::class)->assignRep($customer, $externalRep, $manager);
    }
}
