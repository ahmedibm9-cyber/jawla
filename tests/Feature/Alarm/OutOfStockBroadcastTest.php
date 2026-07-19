<?php

namespace Tests\Feature\Alarm;

use App\Livewire\App\StockSearch;
use App\Models\Alarm;
use App\Models\AlarmRead;
use App\Models\Company;
use App\Models\OutOfStockRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\Contracts\OutOfStockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OutOfStockBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create();
        $this->rep = $this->makeUser('rep', $this->company);
        $this->product = Product::factory()->create(['company_id' => $this->company->id]);
    }

    private function makeUser(string $role, Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_raise_broadcasts_to_exactly_the_three_roles_in_same_company(): void
    {
        $finance = $this->makeUser('accounts', $this->company);
        $manager = $this->makeUser('sales_manager', $this->company);
        $executive = $this->makeUser('executive', $this->company);
        $otherRep = $this->makeUser('rep', $this->company);

        $request = app(OutOfStockService::class)->raise($this->rep, $this->product->id, 5.0);

        $alarm = Alarm::where('reference_type', OutOfStockRequest::class)
            ->where('reference_id', $request->id)
            ->firstOrFail();

        $this->assertSame('critical', $alarm->severity);
        $this->assertSame($this->company->id, $alarm->company_id);
        $this->assertStringContainsString($this->rep->name, $alarm->description);

        $recipientIds = AlarmRead::where('alarm_id', $alarm->id)->pluck('user_id')->sort()->values()->all();
        $expected = collect([$finance->id, $manager->id, $executive->id])->sort()->values()->all();

        $this->assertSame($expected, $recipientIds);
        $this->assertNotContains($otherRep->id, $recipientIds);
    }

    public function test_no_cross_company_broadcast(): void
    {
        $otherCompany = Company::factory()->create();
        $otherManager = $this->makeUser('sales_manager', $otherCompany);
        $sameManager = $this->makeUser('sales_manager', $this->company);

        $request = app(OutOfStockService::class)->raise($this->rep, $this->product->id, 2.0);

        $alarm = Alarm::where('reference_id', $request->id)->firstOrFail();
        $recipientIds = AlarmRead::where('alarm_id', $alarm->id)->pluck('user_id')->all();

        $this->assertContains($sameManager->id, $recipientIds);
        $this->assertNotContains($otherManager->id, $recipientIds);
    }

    public function test_duplicate_open_request_raises_no_second_alarm(): void
    {
        $service = app(OutOfStockService::class);

        $first = $service->raise($this->rep, $this->product->id, 5.0);
        $second = $service->raise($this->rep, $this->product->id, 9.0);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, OutOfStockRequest::withoutGlobalScopes()->count());
        $this->assertSame(1, Alarm::where('reference_type', OutOfStockRequest::class)->count());
    }

    public function test_resolve_marks_request_fulfilled_and_is_idempotent(): void
    {
        $service = app(OutOfStockService::class);
        $manager = $this->makeUser('sales_manager', $this->company);

        $request = $service->raise($this->rep, $this->product->id, 5.0);

        $service->resolve($request, $manager->id);
        $this->assertSame('fulfilled', $request->refresh()->status);

        // Idempotent: second resolve is a no-op
        $service->resolve($request, $manager->id);
        $this->assertSame('fulfilled', $request->refresh()->status);

        // After fulfilment the rep can flag again -> new request + new alarm
        $new = $service->raise($this->rep, $this->product->id, 3.0);
        $this->assertNotSame($request->id, $new->id);
    }

    public function test_rep_flags_from_stock_search_component(): void
    {
        Livewire::actingAs($this->rep)
            ->test(StockSearch::class)
            ->call('startFlag', $this->product->id)
            ->set('flagQuantity', '4.5')
            ->set('flagNotes', 'Customer waiting')
            ->call('submitFlag')
            ->assertSet('flagMessageType', 'success')
            ->assertSet('flagProductId', null);

        $this->assertDatabaseHas('out_of_stock_requests', [
            'user_id' => $this->rep->id,
            'product_id' => $this->product->id,
            'company_id' => $this->company->id,
            'status' => 'open',
        ]);
    }

    public function test_duplicate_flag_from_component_shows_bilingual_notice(): void
    {
        app(OutOfStockService::class)->raise($this->rep, $this->product->id, 1.0);

        Livewire::actingAs($this->rep)
            ->test(StockSearch::class)
            ->call('startFlag', $this->product->id)
            ->set('flagQuantity', '2')
            ->call('submitFlag')
            ->assertSet('flagMessageType', 'info');

        $this->assertSame(1, Alarm::where('reference_type', OutOfStockRequest::class)->count());
    }

    public function test_flag_requires_quantity(): void
    {
        Livewire::actingAs($this->rep)
            ->test(StockSearch::class)
            ->call('startFlag', $this->product->id)
            ->set('flagQuantity', '')
            ->call('submitFlag')
            ->assertHasErrors(['flagQuantity']);

        $this->assertSame(0, OutOfStockRequest::withoutGlobalScopes()->count());
    }

    public function test_non_rep_role_cannot_flag(): void
    {
        $manager = $this->makeUser('sales_manager', $this->company);

        Livewire::actingAs($manager)
            ->test(StockSearch::class)
            ->call('startFlag', $this->product->id)
            ->assertForbidden();
    }

    public function test_cannot_flag_product_of_another_company(): void
    {
        $otherProduct = Product::factory()->create([
            'company_id' => Company::factory()->create()->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(OutOfStockService::class)->raise($this->rep, $otherProduct->id, 1.0);
    }
}
