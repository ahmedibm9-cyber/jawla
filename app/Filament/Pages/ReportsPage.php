<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\PriceQuotationRequest;
use App\Models\ProformaInvoice;
use App\Models\VisitReport;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class ReportsPage extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.reports-page';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'التقارير' : 'Reports';
    }

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->can('reports.view') ?? false;
    }

    public string $tab = 'visit_reports';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public function getVisitsProperty()
    {
        $q = VisitReport::whereHas('visit.customer', function ($q) {
            $q->where('company_id', Auth::user()->activeCompanyId());
        })->with(['visit.customer', 'visit.user'])->latest();

        if ($this->fromDate) {
            $q->whereDate('submitted_at', '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $q->whereDate('submitted_at', '<=', $this->toDate);
        }

        return $q->paginate(100, pageName: 'visitPage');
    }

    public function getQuotationsProperty()
    {
        $q = PriceQuotationRequest::where('company_id', Auth::user()->activeCompanyId())
            ->with(['customer', 'product', 'quotation'])
            ->latest();

        if ($this->fromDate) {
            $q->whereDate('created_at', '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $q->whereDate('created_at', '<=', $this->toDate);
        }

        return $q->paginate(100, pageName: 'quotationPage');
    }

    public function getProformasProperty()
    {
        $q = ProformaInvoice::where('company_id', Auth::user()->activeCompanyId())
            ->with(['customer', 'items'])
            ->latest();

        if ($this->fromDate) {
            $q->whereDate('posting_date', '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $q->whereDate('posting_date', '<=', $this->toDate);
        }

        return $q->paginate(100, pageName: 'proformaPage');
    }

    public function getInvoicesProperty()
    {
        $q = Invoice::where('company_id', Auth::user()->activeCompanyId())
            ->with(['customer', 'items'])
            ->latest();

        if ($this->fromDate) {
            $q->whereDate('issued_at', '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $q->whereDate('issued_at', '<=', $this->toDate);
        }

        return $q->paginate(100, pageName: 'invoicePage');
    }
}
