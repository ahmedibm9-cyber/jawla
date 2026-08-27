<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\PriceQuotationRequest;
use App\Models\ProformaInvoice;
use App\Models\VisitReport;
use App\Support\CsvCell;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $user = Auth::user();

        return $user !== null && $user->can('reports.view') && $user->canAny([
            'reports.visits',
            'reports.sales',
            'reports.financial',
        ]);
    }

    public string $tab = 'visit_reports';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public function mount(): void
    {
        if (! $this->canUseTab($this->tab)) {
            $this->tab = collect(['visit_reports', 'quotations', 'proformas', 'invoices'])
                ->first(fn (string $tab): bool => $this->canUseTab($tab))
                ?? abort(403);
        }
    }

    public function updatedTab(string $tab): void
    {
        abort_unless($this->canUseTab($tab), 403);
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, VisitReport> */
    public function getVisitsProperty()
    {
        $this->authorizeTab('visit_reports');
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

    /** @return LengthAwarePaginator<int, PriceQuotationRequest> */
    public function getQuotationsProperty()
    {
        $this->authorizeTab('quotations');
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

    /** @return LengthAwarePaginator<int, ProformaInvoice> */
    public function getProformasProperty()
    {
        $this->authorizeTab('proformas');
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

    /** @return LengthAwarePaginator<int, Invoice> */
    public function getInvoicesProperty()
    {
        $this->authorizeTab('invoices');
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

    public function exportCsv(): StreamedResponse
    {
        $this->authorizeTab($this->tab);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");

            match ($this->tab) {
                'visit_reports' => $this->exportVisits($output),
                'quotations' => $this->exportQuotations($output),
                'proformas' => $this->exportProformas($output),
                'invoices' => $this->exportInvoices($output),
                default => abort(404),
            };

            fclose($output);
        }, "jawla-{$this->tab}-".today()->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** @param resource $output */
    private function exportVisits($output): void
    {
        $this->writeCsv($output, ['Representative', 'Customer', 'Submitted at', 'Summary']);
        $query = VisitReport::whereHas('visit.customer', fn ($query) => $query->where('company_id', Auth::user()->activeCompanyId()))
            ->with(['visit.customer', 'visit.user'])->orderBy('id');
        $this->applyDateRange($query, 'submitted_at');
        foreach ($query->lazyById(500) as $report) {
            $this->writeCsv($output, [$report->visit?->user?->name, $report->visit?->customer?->name_ar, $report->submitted_at?->toIso8601String(), $report->summary]);
        }
    }

    /** @param resource $output */
    private function exportQuotations($output): void
    {
        $this->writeCsv($output, ['Customer', 'Product', 'Quantity', 'Price', 'Status']);
        $query = PriceQuotationRequest::query()
            ->where('company_id', Auth::user()->activeCompanyId())
            ->with(['customer', 'product', 'quotation'])
            ->orderBy('id');
        $this->applyDateRange($query, 'created_at');
        foreach ($query->lazyById(500) as $request) {
            $this->writeCsv($output, [$request->customer?->name_ar, $request->product?->name_ar, $request->quantity_requested, $request->quotation?->base_price, $request->status]);
        }
    }

    /** @param resource $output */
    private function exportProformas($output): void
    {
        $this->writeCsv($output, ['Number', 'Customer', 'Total', 'Posting date', 'Status']);
        $query = ProformaInvoice::query()
            ->where('company_id', Auth::user()->activeCompanyId())
            ->with('customer')
            ->orderBy('id');
        $this->applyDateRange($query, 'posting_date');
        foreach ($query->lazyById(500) as $proforma) {
            $this->writeCsv($output, [$proforma->proforma_number, $proforma->customer?->name_ar, $proforma->total, $proforma->posting_date?->format('Y-m-d'), $proforma->status]);
        }
    }

    /** @param resource $output */
    private function exportInvoices($output): void
    {
        $this->writeCsv($output, ['Number', 'Customer', 'Total', 'Remaining', 'Issued at', 'Status']);
        $query = Invoice::query()
            ->where('company_id', Auth::user()->activeCompanyId())
            ->with('customer')
            ->orderBy('id');
        $this->applyDateRange($query, 'issued_at');
        foreach ($query->lazyById(500) as $invoice) {
            $this->writeCsv($output, [$invoice->invoice_number, $invoice->customer?->name_ar, $invoice->total, $invoice->remaining_amount, $invoice->issued_at->toIso8601String(), $invoice->status->value]);
        }
    }

    /** @param object $query */
    private function applyDateRange($query, string $column): void
    {
        if ($this->fromDate) {
            $query->whereDate($column, '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $query->whereDate($column, '<=', $this->toDate);
        }
    }

    private function authorizeTab(string $tab): void
    {
        abort_unless(Auth::user()?->can('reports.view') && $this->canUseTab($tab), 403);
    }

    private function canUseTab(string $tab): bool
    {
        $permission = match ($tab) {
            'visit_reports' => 'reports.visits',
            'quotations', 'proformas' => 'reports.sales',
            'invoices' => 'reports.financial',
            default => null,
        };

        return $permission !== null && (Auth::user()?->can($permission) ?? false);
    }

    /**
     * @param  resource  $output
     * @param  list<int|string|null>  $cells
     */
    private function writeCsv($output, array $cells): void
    {
        fputcsv($output, array_map(CsvCell::neutralize(...), $cells));
    }
}
