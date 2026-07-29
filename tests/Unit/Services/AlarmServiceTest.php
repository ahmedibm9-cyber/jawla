<?php

namespace Tests\Unit\Services;

use App\Models\AlarmRead;
use App\Models\Company;
use App\Models\User;
use App\Services\AlarmService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlarmServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_raise_creates_alarm_with_correct_fields(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        $alarm = app(AlarmService::class)->raise(
            'out_of_stock_request',
            $rep,
            'Title',
            'Description',
            'critical',
        );

        $this->assertSame($company->id, $alarm->company_id);
        $this->assertSame('out_of_stock_request', $alarm->type);
        $this->assertSame(get_class($rep), $alarm->reference_type);
        $this->assertSame($rep->id, $alarm->reference_id);
        $this->assertSame('Title', $alarm->title);
        $this->assertSame('Description', $alarm->description);
        $this->assertSame('critical', $alarm->severity);
    }

    public function test_raise_throws_when_reference_has_no_company_id(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);

        $model = new AlarmRead;
        $model->fill(['alarm_id' => 0, 'user_id' => 0]);
        $model->setAttribute('company_id', null);

        $this->expectException(\InvalidArgumentException::class);
        app(AlarmService::class)->raise(
            'out_of_stock_request',
            $model,
            'Title',
            'Description',
            'critical',
        );
    }

    public function test_raise_creates_alarm_read_entries_for_recipients(): void
    {
        $company = Company::factory()->create();

        // Users who should receive 'out_of_stock_request' alarms
        $accounts = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $accounts->assignRole('accounts');

        $salesManager = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $salesManager->assignRole('sales_manager');

        // User outside the role map — should NOT receive
        $rep = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $rep->assignRole('sales_rep');

        $alarm = app(AlarmService::class)->raise(
            'out_of_stock_request',
            $rep,
            'Title',
            'Description',
            'critical',
        );

        $this->assertCount(2, AlarmRead::where('alarm_id', $alarm->id)->get());
        $this->assertTrue(AlarmRead::where('alarm_id', $alarm->id)->where('user_id', $accounts->id)->exists());
        $this->assertTrue(AlarmRead::where('alarm_id', $alarm->id)->where('user_id', $salesManager->id)->exists());
    }

    public function test_acknowledge_sets_read_fields(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        $alarm = app(AlarmService::class)->raise(
            'customer_complaint',
            $rep,
            'Title',
            'Description',
            'warning',
        );

        $result = app(AlarmService::class)->acknowledge($alarm, $rep->id);

        $this->assertTrue($result->is_read);
        $this->assertSame($rep->id, $result->read_by);
        $this->assertNotNull($result->read_at);
        $this->assertTrue(AlarmRead::where('alarm_id', $alarm->id)->where('user_id', $rep->id)->where('acknowledged', true)->exists());
    }

    public function test_resolve_sets_resolved_fields(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        $alarm = app(AlarmService::class)->raise(
            'customer_complaint',
            $rep,
            'Title',
            'Description',
            'warning',
        );

        $result = app(AlarmService::class)->resolve($alarm, $rep->id);

        $this->assertTrue($result->is_read);
        $this->assertSame($rep->id, $result->read_by);
        $this->assertNotNull($result->read_at);

        $read = AlarmRead::where('alarm_id', $alarm->id)->where('user_id', $rep->id)->first();
        $this->assertTrue($read->acknowledged);
        $this->assertTrue($read->resolved);
        $this->assertNotNull($read->resolved_at);
    }
}
