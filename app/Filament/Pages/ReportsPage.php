<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\PriceQuotationRequest;
use App\Models\ProformaInvoice;
use App\Models\VisitReport;
use Filament\Pages\Page;
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

    public function exportCsv(): StreamedResponse
    {
        abort_unless(Auth::user()?->can('reports.view'), 403);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");

            match ($this->tab) {
                'visit_reports' => $this->exportVisits($output),
                'quotations' => $this->exportQuotations($output),
                'proformas' => $this->exportProformas($output),
                default => $this->exportInvoices($output),
            };

            fclose($output);
        }, "jawla-{$this->tab}-".today()->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportVisits($output): void
    {
        fputcsv($output, ['Representative', 'Customer', 'Submitted at', 'Summary']);
        $query = VisitReport::whereHas('visit.customer', fn ($query) => $query->where('company_id', Auth::user()->activeCompanyId()))
            ->with(['visit.customer', 'visit.user'])->orderBy('id');
        $this->applyDateRange($query, 'submitted_at');
        foreach ($query->lazyById(500) as $report) {
            fputcsv($output, [$report->visit?->user?->name, $report->visit?->customer?->name_ar, $report->submitted_at?->toIso8601String(), $report->summary]);
        }
    }

    private function exportQuotations($output): void
    {
        fputcsv($output, ['Customer', 'Product', 'Quantity', 'Price', 'Status']);
        $query = PriceQuotationRequest::query()
            ->where('company_id', Auth::user()->activeCompanyId())
            ->with(['customer', 'product', 'quotation'])
            ->orderBy('id');
        $this->applyDateRange($query, 'created_at');
        foreach ($query->lazyById(500) as $request) {
            fputcsv($output, [$request->customer?->name_ar, $request->product?->name_ar, $request->quantity_requested, $request->quotation?->base_price, $request->status]);
        }
    }

    private function exportProformas($output): void
    {
        fputcsv($output, ['Number', 'Customer', 'Total', 'Posting date', 'Status']);
        $query = ProformaInvoice::query()
            ->where('company_id', Auth::user()->activeCompanyId())
            ->with('customer')
            ->orderBy('id');
        $this->applyDateRange($query, 'posting_date');
        foreach ($query->lazyById(500) as $proforma) {
            fputcsv($output, [$proforma->proforma_number, $proforma->customer?->name_ar, $proforma->total, $proforma->posting_date?->format('Y-m-d'), $proforma->status]);
        }
    }

    private function exportInvoices($output): void
    {
        fputcsv($output, ['Number', 'Customer', 'Total', 'Remaining', 'Issued at', 'Status']);
        $query = Invoice::query()
            ->where('company_id', Auth::user()->activeCompanyId())
            ->with('customer')
            ->orderBy('id');
        $this->applyDateRange($query, 'issued_at');
        foreach ($query->lazyById(500) as $invoice) {
            fputcsv($output, [$invoice->invoice_number, $invoice->customer?->name_ar, $invoice->total, $invoice->remaining_amount, $invoice->issued_at?->toIso8601String(), $invoice->status?->value ?? $invoice->status]);
        }
    }

    private function applyDateRange($query, string $column): void
    {
        if ($this->fromDate) {
            $query->whereDate($column, '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $query->whereDate($column, '<=', $this->toDate);
        }
    }
}
