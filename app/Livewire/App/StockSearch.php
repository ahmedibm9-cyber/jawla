<?php

namespace App\Livewire\App;

use App\Models\Product;
use App\Services\Contracts\OutOfStockService;
use App\Support\LikeEscape;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StockSearch extends Component
{
    public string $search = '';

    public ?int $flagProductId = null;

    public string $flagQuantity = '';

    public string $flagNotes = '';

    public string $flagMessage = '';

    public string $flagMessageType = 'success';

    public function startFlag(int $productId): void
    {
        abort_unless(auth()->user()->can('alarms.flag_out_of_stock'), 403);

        $this->flagProductId = $productId;
        $this->flagQuantity = '';
        $this->flagNotes = '';
        $this->flagMessage = '';
        $this->resetErrorBag();
    }

    public function cancelFlag(): void
    {
        $this->flagProductId = null;
        $this->resetErrorBag();
    }

    public function submitFlag(OutOfStockService $service): void
    {
        abort_unless(auth()->user()->can('alarms.flag_out_of_stock'), 403);

        if ($this->flagProductId === null) {
            return;
        }

        $this->validate([
            'flagQuantity' => 'required|numeric|min:0.001|max:999999',
            'flagNotes' => 'nullable|string|max:500',
        ], [], [
            'flagQuantity' => __('app.flag_quantity'),
            'flagNotes' => __('app.flag_notes'),
        ]);

        $request = $service->raise(
            auth()->user(),
            $this->flagProductId,
            (float) $this->flagQuantity,
            null,
            $this->flagNotes !== '' ? $this->flagNotes : null,
        );

        if ($request->wasRecentlyCreated) {
            $this->flagMessage = __('app.flag_success');
            $this->flagMessageType = 'success';
        } else {
            $this->flagMessage = __('app.flag_duplicate');
            $this->flagMessageType = 'info';
        }

        $this->flagProductId = null;
        $this->flagQuantity = '';
        $this->flagNotes = '';
    }

    public function render(): View
    {
        $results = collect();

        if (strlen($this->search) >= 2) {
            $term = LikeEscape::wrap($this->search);
            $results = Product::query()
                ->where('company_id', auth()->user()->activeCompanyId())
                ->where(function ($q) use ($term) {
                    $q->where('sku', 'ilike', $term)
                        ->orWhere('name_ar', 'ilike', $term)
                        ->orWhere('name_en', 'ilike', $term);
                })
                ->where('is_active', true)
                ->with(['stocks' => fn ($q) => $q->where('quantity', '>', 0)->with('warehouse')])
                ->limit(20)
                ->get();
        }

        return view('livewire.app.stock-search', [
            'results' => $results,
        ]);
    }
}
