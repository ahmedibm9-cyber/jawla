<?php

namespace Tests\Feature\Livewire;

use App\Enums\VisitStatus;
use App\Livewire\App\VisitFlow;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function rep(Company $company): User
    {
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        return $rep;
    }

    private function openVisit(Company $company, User $rep): Visit
    {
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        $workSession = \App\Models\WorkSession::factory()->create([
            'user_id' => $rep->id,
            'company_id' => $company->id,
        ]);

        return Visit::factory()->create([
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'work_session_id' => $workSession->id,
            'status' => VisitStatus::Open,
            'arrival_confirmed' => false,
        ]);
    }

    public function test_mount_shows_checkin_step_for_new_visit(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $visit = $this->openVisit($company, $rep);
        $this->actingAs($rep);

        Livewire::test(VisitFlow::class, ['visit' => $visit])
            ->assertSet('step', 'checkin');
    }

    public function test_mount_skips_to_report_when_already_arrived(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $workSession = \App\Models\WorkSession::factory()->create([
            'user_id' => $rep->id,
            'company_id' => $company->id,
        ]);
        $visit = Visit::factory()->create([
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'work_session_id' => $workSession->id,
            'arrival_confirmed' => true,
            'status' => VisitStatus::Open,
        ]);
        $this->actingAs($rep);

        Livewire::test(VisitFlow::class, ['visit' => $visit])
            ->assertSet('step', 'report');
    }

    public function test_confirm_arrival_sets_gps_denied_without_coordinates(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $visit = $this->openVisit($company, $rep);
        $this->actingAs($rep);

        Livewire::test(VisitFlow::class, ['visit' => $visit])
            ->call('confirmArrival')
            ->assertSet('gpsDenied', true);
    }

    public function test_submit_report_validates_summary(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $workSession = \App\Models\WorkSession::factory()->create([
            'user_id' => $rep->id,
            'company_id' => $company->id,
        ]);
        $visit = Visit::factory()->create([
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'work_session_id' => $workSession->id,
            'status' => VisitStatus::Open,
            'arrival_confirmed' => true,
        ]);
        $this->actingAs($rep);

        Livewire::test(VisitFlow::class, ['visit' => $visit])
            ->call('submitReport')
            ->assertHasErrors(['summary']);
    }

    public function test_submit_report_completes_with_valid_data(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $workSession = \App\Models\WorkSession::factory()->create([
            'user_id' => $rep->id,
            'company_id' => $company->id,
        ]);
        $visit = Visit::factory()->create([
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'work_session_id' => $workSession->id,
            'status' => VisitStatus::Open,
            'arrival_confirmed' => true,
        ]);
        $this->actingAs($rep);

        Livewire::test(VisitFlow::class, ['visit' => $visit])
            ->set('summary', 'Met with customer, discussed new order')
            ->call('submitReport')
            ->assertSet('step', 'done');
    }

    public function test_queue_offline_shows_done_step(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $visit = $this->openVisit($company, $rep);
        $this->actingAs($rep);

        Livewire::test(VisitFlow::class, ['visit' => $visit])
            ->call('queueOffline')
            ->assertSet('step', 'done')
            ->assertSet('queuedOffline', true);
    }

    public function test_mount_aborts_for_other_users_visit(): void
    {
        $company = Company::factory()->create();
        $rep1 = $this->rep($company);
        $rep2 = $this->rep($company);
        $visit = $this->openVisit($company, $rep1);
        $this->actingAs($rep2);

        $component = Livewire::test(VisitFlow::class, ['visit' => $visit]);
        $this->assertNotEquals('checkin', $component->get('step'));
    }
}
