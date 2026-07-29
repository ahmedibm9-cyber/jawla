<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\StockSearch;
use App\Models\Company;
use App\Models\OutOfStockRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\Contracts\OutOfStockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function repWithPermission(Company $company): User
    {
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $rep->givePermissionTo('alarms.flag_out_of_stock');

        return $rep;
    }

    public function test_start_flag_sets_product_id(): void
    {
        $company = Company::factory()->create();
        $rep = $this->repWithPermission($company);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $this->actingAs($rep);

        Livewire::test(StockSearch::class)
            ->call('startFlag', $product->id)
            ->assertSet('flagProductId', $product->id)
            ->assertSet('flagQuantity', '')
            ->assertSet('flagNotes', '')
            ->assertSet('flagMessage', '');
    }

    public function test_cancel_flag_clears(): void
    {
        $company = Company::factory()->create();
        $rep = $this->repWithPermission($company);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $this->actingAs($rep);

        Livewire::test(StockSearch::class)
            ->call('startFlag', $product->id)
            ->assertSet('flagProductId', $product->id)
            ->call('cancelFlag')
            ->assertSet('flagProductId', null);
    }

    public function test_submit_flag_validates_quantity(): void
    {
        $company = Company::factory()->create();
        $rep = $this->repWithPermission($company);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $this->actingAs($rep);

        Livewire::test(StockSearch::class)
            ->call('startFlag', $product->id)
            ->set('flagQuantity', '')
            ->call('submitFlag')
            ->assertHasErrors(['flagQuantity']);
    }

    public function test_submit_flag_success_message(): void
    {
        $company = Company::factory()->create();
        $rep = $this->repWithPermission($company);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $outOfStockRequest = new OutOfStockRequest;
        $outOfStockRequest->wasRecentlyCreated = true;

        $this->mock(OutOfStockService::class, function ($mock) use ($outOfStockRequest) {
            $mock->shouldReceive('raise')
                ->once()
                ->andReturn($outOfStockRequest);
        });

        $this->actingAs($rep);

        Livewire::test(StockSearch::class)
            ->call('startFlag', $product->id)
            ->set('flagQuantity', '50')
            ->set('flagNotes', 'Out of stock')
            ->call('submitFlag')
            ->assertSet('flagMessage', fn ($msg) => $msg !== null)
            ->assertSet('flagMessageType', 'success')
            ->assertSet('flagProductId', null);
    }
}
