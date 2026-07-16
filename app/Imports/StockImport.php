<?php

namespace App\Imports;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StockImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow, WithValidation
{
    public function __construct(
        private int $warehouseId,
        private bool $updateExisting = true,
    ) {}

    public function collection(Collection $rows): void
    {
        $warehouseId = $this->warehouseId;

        foreach ($rows as $row) {
            $sku = trim($row['sku'] ?? '');
            $productName = trim($row['name_ar'] ?? $row['name_en'] ?? '');
            $quantity = (float) ($row['quantity'] ?? 0);
            $batchNumber = trim($row['batch_number'] ?? $row['batch'] ?? '');

            if (! $sku && ! $productName) {
                continue;
            }

            $product = Product::where('company_id', auth()->user()->company_id)
                ->where(function ($q) use ($sku, $productName) {
                    if ($sku) {
                        $q->where('sku', $sku);
                    }
                    if ($productName) {
                        $q->orWhere('name_ar', $productName)
                            ->orWhere('name_en', $productName);
                    }
                })
                ->first();

            if (! $product) {
                continue;
            }

            $batchId = null;
            if ($batchNumber) {
                $batch = Batch::firstOrCreate([
                    'product_id' => $product->id,
                    'batch_number' => $batchNumber,
                ]);
                $batchId = $batch->id;
            }

            $stock = Stock::firstOrNew([
                'warehouse_id' => $this->warehouseId,
                'product_id' => $product->id,
                'batch_id' => $batchId,
            ]);

            $newQuantity = $quantity;

            if ($this->updateExisting) {
                $stock->quantity = max(0, $stock->quantity + $quantity);
            } else {
                $stock->quantity = max(0, ($stock->quantity ?? 0) + $quantity);
            }

            if ($stock->quantity < 0) {
                $stock->quantity = 0;
            }

            $stock->save();
        }
    }

    public function rules(): array
    {
        return [
            'sku' => 'nullable|string',
            'name_ar' => 'nullable|string',
            'name_en' => 'nullable|string',
            'quantity' => 'nullable|numeric',
            'batch_number' => 'nullable|string',
            'batch' => 'nullable|string',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'quantity.numeric' => 'الكمية يجب أن تكون رقماً',
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getImportedCount(): int
    {
        return 0;
    }

    public function getUpdatedCount(): int
    {
        return 0;
    }

    public function getSkippedCount(): int
    {
        return 0;
    }
}
