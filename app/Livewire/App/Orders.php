<?php

namespace App\Livewire\App;

use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Models\PurchaseRequest;
use App\Models\SalesOrder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Orders extends Component
{
    use WithPagination;

    #[Url]
    public string $type = 'invoices';

    public function setType(string $type): void
    {
        if (! in_array($type, ['sales_orders', 'invoices', 'proformas', 'offers'], true)) {
            return;
        }

        $this->type = $type;
        $this->resetPage();
    }

    public function render(): View
    {
        if ($this->type === 'sales_orders') {
            $documents = SalesOrder::query()->where('user_id', auth()->id())
                ->with('customer')->latest()->simplePaginate(15);
        } elseif ($this->type === 'offers') {
            $documents = PurchaseRequest::where('user_id', auth()->id())
                ->with(['product', 'supplier', 'purchaseOrder'])
                ->orderByDesc('created_at')
                ->simplePaginate(15);
        } else {
            $query = $this->type === 'proformas'
                ? ProformaInvoice::query()
                : Invoice::query();

            $documents = $query
                ->where('user_id', auth()->id())
                ->with('customer')
                ->orderByDesc('created_at')
                ->simplePaginate(15);
        }

        return view('livewire.app.orders', [
            'documents' => $documents,
        ]);
    }
}
