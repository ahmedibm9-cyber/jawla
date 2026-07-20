<?php

namespace App\Livewire\App;

use App\Enums\VanTransferStatus;
use App\Models\VanTransfer;
use App\Services\Contracts\VanTransferService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class VanTransfers extends Component
{
    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public function receive(int $transferId): void
    {
        $this->reset(['errorMessage', 'successMessage']);

        // Guard: a rep may only receive a transfer addressed to them.
        $transfer = VanTransfer::where('to_user_id', auth()->id())->find($transferId);

        if (! $transfer) {
            $this->errorMessage = __('app.transfer_not_found');

            return;
        }

        try {
            app(VanTransferService::class)->receive($transferId, auth()->id());
            $this->successMessage = __('app.transfer_received');
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $userId = auth()->id();

        return view('livewire.app.van-transfers', [
            'incoming' => VanTransfer::where('to_user_id', $userId)
                ->with(['fromUser', 'items.product'])
                ->whereIn('status', [VanTransferStatus::Shipped, VanTransferStatus::Pending])
                ->latest()
                ->get(),
            'outgoing' => VanTransfer::where('from_user_id', $userId)
                ->with(['toUser', 'items.product'])
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
