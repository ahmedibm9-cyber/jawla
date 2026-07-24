<?php

namespace App\Services;

use App\Exceptions\Domain\DomainException;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\WarehouseImportLog;
use App\Services\Contracts\StockService as StockServiceContract;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Warehouse stock CSV import (REQ-STK-1/2).
 *
 * Quantity semantics: absolute counted stock, applied as a reconciliation
 * delta through StockService (D-03 default until the client file says
 * otherwise). transit_quantity is accepted read-only and never applied
 * to sellable stock.
 */
class StockImportService
{
    private const REQUIRED_HEADINGS = ['sku', 'quantity'];

    private const OPTIONAL_HEADINGS = ['transit_quantity'];

    public function __construct(private readonly StockServiceContract $stock) {}

    /**
     * Validate the file without mutating anything.
     *
     * @return array{valid: array<int, array{sku: string, product_id: int, quantity: float, current: float, transit: float|null}>, errors: array<int, string>, checksum: string, headings_ok: bool}
     */
    public function preview(string $absolutePath, Warehouse $warehouse): array
    {
        $checksum = hash_file('sha256', $absolutePath);
        $locale = app()->getLocale();
        $l = fn (string $ar, string $en) => $locale === 'ar' ? $ar : $en;

        $reader = SimpleExcelReader::create($absolutePath, 'csv');
        $rows = $reader->getRows();

        $products = Product::withoutGlobalScopes()
            ->where('company_id', $warehouse->company_id)
            ->pluck('id', 'sku');

        $valid = [];
        $errors = [];
        $seen = [];
        $headingsChecked = false;
        $headingsOk = true;
        $line = 1;

        foreach ($rows as $row) {
            $line++;

            if (! $headingsChecked) {
                $headingsChecked = true;
                $missing = array_diff(self::REQUIRED_HEADINGS, array_keys($row));
                $unknown = array_diff(array_keys($row), [...self::REQUIRED_HEADINGS, ...self::OPTIONAL_HEADINGS]);
                if ($missing !== [] || $unknown !== []) {
                    $headingsOk = false;
                    $errors[] = $l(
                        'أعمدة غير صحيحة. المطلوب: sku, quantity (اختياري: transit_quantity)',
                        'Invalid headings. Required: sku, quantity (optional: transit_quantity)',
                    );

                    break;
                }
            }

            $sku = trim((string) ($row['sku'] ?? ''));
            $qtyRaw = $row['quantity'] ?? null;

            if ($sku === '') {
                $errors[] = $l("سطر {$line}: SKU فارغ", "Row {$line}: empty SKU");

                continue;
            }

            if (isset($seen[$sku])) {
                $errors[] = $l("سطر {$line}: SKU مكرر ({$sku})", "Row {$line}: duplicate SKU ({$sku})");

                continue;
            }
            $seen[$sku] = true;

            if (! is_numeric($qtyRaw) || (float) $qtyRaw < 0) {
                $errors[] = $l("سطر {$line}: كمية غير صالحة ({$sku})", "Row {$line}: invalid quantity ({$sku})");

                continue;
            }

            $transitRaw = $row['transit_quantity'] ?? null;
            if ($transitRaw !== null && $transitRaw !== '' && (! is_numeric($transitRaw) || (float) $transitRaw < 0)) {
                $errors[] = $l("سطر {$line}: كمية عبور غير صالحة ({$sku})", "Row {$line}: invalid transit quantity ({$sku})");

                continue;
            }

            $productId = $products[$sku] ?? null;
            if ($productId === null) {
                $errors[] = $l("سطر {$line}: منتج غير معروف ({$sku})", "Row {$line}: unknown SKU ({$sku})");

                continue;
            }

            $valid[] = [
                'sku' => $sku,
                'product_id' => (int) $productId,
                'quantity' => (float) $qtyRaw,
                'current' => $this->stock->balance($warehouse->id, (int) $productId),
                'transit' => is_numeric($transitRaw) ? (float) $transitRaw : null,
            ];
        }

        return [
            'valid' => $valid,
            'errors' => $errors,
            'checksum' => $checksum,
            'headings_ok' => $headingsOk,
        ];
    }

    /**
     * Apply a validated preview inside one transaction. Every delta goes
     * through StockService::reconcile, producing matching stock movements.
     *
     * @param  array{valid: array, errors: array, checksum: string}  $preview
     */
    public function apply(array $preview, Warehouse $warehouse, string $fileName, int $userId): WarehouseImportLog
    {
        if (WarehouseImportLog::where('checksum', $preview['checksum'])->exists()) {
            throw new DomainException(app()->getLocale() === 'ar'
                ? 'تم استيراد هذا الملف من قبل'
                : 'This exact file was already imported');
        }

        return DB::transaction(function () use ($preview, $warehouse, $fileName, $userId): WarehouseImportLog {
            foreach ($preview['valid'] as $row) {
                $this->stock->reconcile(
                    $warehouse->id,
                    $row['product_id'],
                    null,
                    $row['quantity'],
                    'warehouse_import',
                    $userId,
                );
            }

            return WarehouseImportLog::create([
                'warehouse_id' => $warehouse->id,
                'imported_by' => $userId,
                'file_name' => $fileName,
                'rows_imported' => count($preview['valid']),
                'rows_total' => count($preview['valid']) + count($preview['errors']),
                'rows_rejected' => count($preview['errors']),
                'errors' => $preview['errors'],
                'status' => 'completed',
                'checksum' => $preview['checksum'],
                'imported_at' => now(),
            ]);
        });
    }

    public function template(): string
    {
        return "sku,quantity,transit_quantity\nSKU-0001,120.5,30\nSKU-0002,0,\n";
    }
}
