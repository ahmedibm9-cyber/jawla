<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Livewire\App\VisitFlow;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\Visit;
use App\Models\WorkSession;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitGeofenceTest extends TestCase
{
    use RefreshDatabase;

    private const CUSTOMER_LAT = 24.7136;

    private const CUSTOMER_LNG = 46.6753;

    private Company $company;

    private User $rep;

    private Customer $customer;

    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create(['geofence_radius_m' => 500]);
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->rep->assignRole('rep');

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'latitude' => self::CUSTOMER_LAT,
            'longitude' => self::CUSTOMER_LNG,
        ]);

        $this->visit = Visit::factory()->create([
            'work_session_id' => WorkSession::factory()->create(['user_id' => $this->rep->id])->id,
            'user_id' => $this->rep->id,
            'customer_id' => $this->customer->id,
            'status' => VisitStatus::Open,
            'arrival_confirmed' => false,
            'arrival_confirmed_at' => null,
            'checkin_at' => null,
            'checkout_at' => null,
        ]);
    }

    /** ~0.009° latitude ≈ 1000m north of the customer. */
    private function farLat(): float
    {
        return self::CUSTOMER_LAT + 0.009;
    }

    public function test_in_range_checkin_persists_flag_coordinates_and_distance(): void
    {
        Livewire::actingAs($this->rep)
            ->test(VisitFlow::class, ['visit' => $this->visit])
            ->set('userLat', self::CUSTOMER_LAT + 0.001) // ~111m
            ->set('userLng', self::CUSTOMER_LNG)
            ->set('userAccuracy', 12.4)
            ->call('checkGps')
            ->assertSet('step', 'report');

        $this->visit->refresh();

        $this->assertTrue((bool) $this->visit->arrival_confirmed);
        $this->assertSame('in_range', $this->visit->arrival_flag);
        $this->assertNotNull($this->visit->checkin_at);
        $this->assertEqualsWithDelta(self::CUSTOMER_LAT + 0.001, (float) $this->visit->checkin_latitude, 0.0001);
        $this->assertEqualsWithDelta(111, $this->visit->checkin_distance_m, 5);
        $this->assertSame(12, $this->visit->checkin_accuracy_m);
    }

    public function test_out_of_range_checkin_is_declined_and_logged(): void
    {
        Livewire::actingAs($this->rep)
            ->test(VisitFlow::class, ['visit' => $this->visit])
            ->set('userLat', $this->farLat())
            ->set('userLng', self::CUSTOMER_LNG)
            ->call('checkGps')
            ->assertSet('withinRange', false)
            ->assertSet('step', 'checkin');

        $this->visit->refresh();

        $this->assertFalse((bool) $this->visit->arrival_confirmed);
        $this->assertNull($this->visit->arrival_flag);

        $this->assertDatabaseHas('activities', [
            'type' => 'geofence_declined',
            'subject_type' => Visit::class,
            'subject_id' => $this->visit->id,
        ]);
    }

    public function test_forged_within_range_is_rejected_server_side(): void
    {
        Livewire::actingAs($this->rep)
            ->test(VisitFlow::class, ['visit' => $this->visit])
            ->set('userLat', $this->farLat())
            ->set('userLng', self::CUSTOMER_LNG)
            ->set('withinRange', true) // forged client state
            ->call('confirmArrival')
            ->assertSet('withinRange', false)
            ->assertSet('step', 'checkin');

        $this->assertFalse((bool) $this->visit->refresh()->arrival_confirmed);
    }

    public function test_missing_gps_blocks_checkin(): void
    {
        Livewire::actingAs($this->rep)
            ->test(VisitFlow::class, ['visit' => $this->visit])
            ->call('confirmArrival')
            ->assertSet('gpsDenied', true)
            ->assertSet('step', 'checkin');

        $this->assertFalse((bool) $this->visit->refresh()->arrival_confirmed);
    }

    public function test_repeated_confirmation_is_idempotent(): void
    {
        $component = Livewire::actingAs($this->rep)
            ->test(VisitFlow::class, ['visit' => $this->visit])
            ->set('userLat', self::CUSTOMER_LAT)
            ->set('userLng', self::CUSTOMER_LNG)
            ->call('checkGps');

        $firstConfirmedAt = $this->visit->refresh()->arrival_confirmed_at;
        $this->assertNotNull($firstConfirmedAt);

        $component->call('confirmArrival')->assertSet('step', 'report');

        $this->assertEquals($firstConfirmedAt, $this->visit->refresh()->arrival_confirmed_at);
    }

    public function test_radius_is_read_from_company_config(): void
    {
        $this->company->update(['geofence_radius_m' => 2000]);

        Livewire::actingAs($this->rep)
            ->test(VisitFlow::class, ['visit' => $this->visit])
            ->set('userLat', $this->farLat()) // ~1000m: outside 500, inside 2000
            ->set('userLng', self::CUSTOMER_LNG)
            ->call('checkGps')
            ->assertSet('step', 'report');

        $this->assertSame('in_range', $this->visit->refresh()->arrival_flag);
    }

    public function test_gps_denied_state_blocks_and_no_skip_path_exists(): void
    {
        $component = Livewire::actingAs($this->rep)
            ->test(VisitFlow::class, ['visit' => $this->visit])
            ->call('markGpsDenied')
            ->assertSet('gpsDenied', true);

        $this->assertFalse(method_exists(VisitFlow::class, 'skipGpsAndConfirm'));
        $this->assertFalse((bool) $this->visit->refresh()->arrival_confirmed);
    }
}
