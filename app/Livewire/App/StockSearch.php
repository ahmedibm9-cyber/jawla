<?php

namespace App\Livewire\App;

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StockSearch extends Component
{
    public string $search = '';

    public function render()
    {
        $results = collect();

        if (strlen($this->search) >= 2) {
            $results = Product::query()
                ->where(function ($q) {
                    $q->where('sku', 'ilike', "%{$this->search}%")
                        ->orWhere('name_ar', 'ilike', "%{$this->search}%")
                        ->orWhere('name_en', 'ilike', "%{$this->search}%");
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
