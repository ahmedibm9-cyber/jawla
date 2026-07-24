<?php

namespace App\Filament\Pages;

use App\Models\Warehouse;
use App\Models\WarehouseImportLog;
use App\Services\StockImportService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockImport extends Page
{
    protected string $view = 'filament.pages.stock-import';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    public ?int $warehouse_id = null;

    /** @var array<int, TemporaryUploadedFile|string> */
    public array $file = [];

    public ?array $preview = null;

    public ?string $fileName = null;

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المخزون' : 'Inventory';
    }

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'استيراد المخزون' : 'Stock Import';
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('stock.import') ?? false;
    }

    public static function form(Schema $schema): Schema
    {

        return $schema->schema([
            Forms\Components\Select::make('warehouse_id')
                ->label(l('المستودع', 'Warehouse'))
                ->options(fn () => Warehouse::where('company_id', Auth::user()->company_id)
                    ->where('is_active', true)
                    ->orderBy('name_ar')
                    ->pluck('name_ar', 'id'))
                ->required(),
            Forms\Components\FileUpload::make('file')
                ->label(l('ملف CSV', 'CSV File'))
                ->disk('private')
                ->directory('stock-imports')
                ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                ->maxSize(2048)
                ->required()
                ->helperText(l(
                    'الأعمدة: sku, quantity (اختياري: transit_quantity). الكمية = العد الفعلي الكامل.',
                    'Columns: sku, quantity (optional: transit_quantity). Quantity = absolute counted stock.',
                )),
        ]);
    }

    public function runPreview(): void
    {
        $data = $this->form->getState();

        $warehouse = Warehouse::where('company_id', Auth::user()->company_id)
            ->findOrFail($data['warehouse_id']);

        $storedPath = is_array($data['file']) ? reset($data['file']) : $data['file'];
        $this->fileName = basename((string) $storedPath);

        $this->preview = app(StockImportService::class)->preview(
            Storage::disk('private')->path($storedPath),
            $warehouse,
        );

        if ($this->preview['valid'] === [] && $this->preview['errors'] !== []) {
            Notification::make()
                ->title(l('لا توجد صفوف صالحة', 'No valid rows'))
                ->danger()
                ->send();
        }
    }

    public function confirmImport(): void
    {

        if ($this->preview === null || $this->preview['valid'] === []) {
            return;
        }

        $warehouse = Warehouse::where('company_id', Auth::user()->company_id)
            ->findOrFail($this->warehouse_id);

        try {
            $log = app(StockImportService::class)->apply(
                $this->preview,
                $warehouse,
                $this->fileName ?? 'upload.csv',
                Auth::id(),
            );
        } catch (\DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()
            ->title(l("تم استيراد {$log->rows_imported} صف", "{$log->rows_imported} rows imported"))
            ->success()
            ->send();

        $this->reset(['preview', 'file', 'fileName']);
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print (app(StockImportService::class)->template()),
            'stock-import-template.csv',
            ['Content-Type' => 'text/csv'],
        );
    }

    public function getRecentImportsProperty()
    {
        return WarehouseImportLog::query()
            ->whereHas('warehouse', fn ($q) => $q->where('company_id', Auth::user()->company_id))
            ->with(['warehouse', 'importedBy'])
            ->latest('imported_at')
            ->limit(10)
            ->get();
    }
}
