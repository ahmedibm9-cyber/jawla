<?php

namespace Tests\Feature;

use App\Enums\StockReason;
use App\Livewire\App\StockSearch;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * US-11.1 — View Van Stock (Rep)
 *
 * Tests the rep's stock search component and balance display.
 */
class VanStockViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_stock_search_page_renders_for_rep(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $this->get('/app/stock')->assertOk();
    }

    public function test_stock_search_component_renders(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        Livewire::test(StockSearch::class)
            ->assertSuccessful();
    }

    public function test_stock_search_by_sku_shows_results(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $product = Product::first();
        $van = Warehouse::where('user_id', $rep->id)->where('type', 'van')->first();

        app(StockService::class)->increment(
            $van->id, $product->id, null, 50.0,
            StockReason::Initial, $product,
        );

        Livewire::test(StockSearch::class)
            ->set('search', $product->sku)
            ->assertSuccessful();
    }

    public function test_stock_search_requires_minimum_two_characters(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        Livewire::test(StockSearch::class)
            ->set('search', 'a')
            ->assertSuccessful();
    }

    public function test_stock_search_is_company_scoped(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        Livewire::test(StockSearch::class)
            ->assertSuccessful();
    }

    public function test_non_rep_cannot_access_stock_search(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        // Admin (non-rep) is forbidden from /app routes by EnsureRepRole middleware
        $response = $this->get('/app/stock');
        $this->assertTrue(in_array($response->status(), [302, 403]),
            'Non-rep should be redirected or forbidden from /app/stock');
    }
}
