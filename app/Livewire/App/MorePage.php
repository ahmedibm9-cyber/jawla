<?php

namespace App\Livewire\App;

use App\Models\CashBox;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MorePage extends Component
{
    public function render()
    {
        $user = auth()->user()->load(['company', 'vanWarehouse']);

        $cashBoxBalance = CashBox::where('user_id', $user->id)->first()?->balance ?? 0;
        $vanStockCount = $user->vanWarehouse?->stocks()->count() ?? 0;

        return view('livewire.app.more', [
            'user' => $user,
            'cashBoxBalance' => $cashBoxBalance,
            'vanStockCount' => $vanStockCount,
        ]);
    }
}
