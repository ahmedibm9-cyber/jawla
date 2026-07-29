<?php

namespace Tests\Feature\Livewire;

use App\Enums\VanTransferStatus;
use App\Livewire\App\VanTransfers;
use App\Models\Company;
use App\Models\User;
use App\Models\VanTransfer;
use App\Services\Contracts\VanTransferService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VanTransfersTest extends TestCase
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

    public function test_receive_with_valid_transfer(): void
    {
        $company = Company::factory()->create();
        $from = $this->rep($company);
        $to = $this->rep($company);

        $transfer = VanTransfer::create([
            'company_id' => $company->id,
            'from_user_id' => $from->id,
            'to_user_id' => $to->id,
            'status' => VanTransferStatus::Shipped,
        ]);

        $this->mock(VanTransferService::class, function ($mock) use ($transfer, $to) {
            $mock->shouldReceive('receive')
                ->once()
                ->with($transfer->id, $to->id)
                ->andReturn($transfer->fresh());
        });

        $this->actingAs($to);

        Livewire::test(VanTransfers::class)
            ->call('receive', $transfer->id)
            ->assertSet('successMessage', fn ($msg) => $msg !== null);
    }

    public function test_receive_with_invalid_transfer_sets_error(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(VanTransfers::class)
            ->call('receive', 999999)
            ->assertSet('errorMessage', fn ($msg) => $msg !== null);
    }
}
