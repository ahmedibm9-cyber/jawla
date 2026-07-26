<?php

namespace App\Services;

use App\Exceptions\Domain\DomainException;
use App\Models\Product;
use App\Models\StockImportPreview;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseImportLog;
use App\Services\Contracts\StockService as StockServiceContract;
use App\Support\ActiveCompanyContext;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class StockImportService
{
    private const REQUIRED_HEADINGS = ['sku', 'quantity'];

    private const OPTIONAL_HEADINGS = ['transit_quantity'];

    public function __construct(private readonly StockServiceContract $stock) {}

    /**
     * @return array{valid: array<int, array{sku: string, product_id: int, quantity: float, current: float, transit: float|null}>, errors: array<int, string>, checksum: string, headings_ok: bool}
     */
    public function preview(string $absolutePath, Warehouse $warehouse): array
    {
        $checksum = hash_file('sha256', $absolutePath);
        $locale = app()->getLocale();
        $message = fn (string $ar, string $en) => $locale === 'ar' ? $ar : $en;
        $rows = SimpleExcelReader::create($absolutePath, 'csv')->getRows();
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
                    $errors[] = $message(
                        'أعمدة غير صحيحة. المطلوب: sku, quantity (اختياري: transit_quantity)',
                        'Invalid headings. Required: sku, quantity (optional: transit_quantity)',
                    );

                    break;
                }
            }

            $sku = trim((string) ($row['sku'] ?? ''));
            $qtyRaw = $row['quantity'] ?? null;
            if ($sku === '') {
                $errors[] = $message("سطر {$line}: SKU فارغ", "Row {$line}: empty SKU");

                continue;
            }
            if (isset($seen[$sku])) {
                $errors[] = $message("سطر {$line}: SKU مكرر ({$sku})", "Row {$line}: duplicate SKU ({$sku})");

                continue;
            }
            $seen[$sku] = true;
            if (! is_numeric($qtyRaw) || (float) $qtyRaw < 0) {
                $errors[] = $message("سطر {$line}: كمية غير صالحة ({$sku})", "Row {$line}: invalid quantity ({$sku})");

                continue;
            }

            $transitRaw = $row['transit_quantity'] ?? null;
            if ($transitRaw !== null && $transitRaw !== ''
                && (! is_numeric($transitRaw) || (float) $transitRaw < 0)) {
                $errors[] = $message("سطر {$line}: كمية عبور غير صالحة ({$sku})", "Row {$line}: invalid transit quantity ({$sku})");

                continue;
            }

            $productId = $products[$sku] ?? null;
            if ($productId === null) {
                $errors[] = $message("سطر {$line}: منتج غير معروف ({$sku})", "Row {$line}: unknown SKU ({$sku})");

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
     * @return array{token: string, preview: array, expires_at: string, requires_approval: bool}
     */
    public function stage(string $absolutePath, Warehouse $warehouse, User $user): array
    {
        $this->authorizeImporter($user, $warehouse);
        app(ActiveCompanyContext::class)->assertMatches((int) $warehouse->company_id);

        $preview = $this->preview($absolutePath, $warehouse);
        $threshold = (string) config('jawla.stock_import.large_variance_threshold', '1000.000');
        $requiresApproval = collect($preview['valid'])->contains(function (array $row) use ($threshold): bool {
            $current = number_format((float) $row['current'], 3, '.', '');
            $target = number_format((float) $row['quantity'], 3, '.', '');
            $variance = number_format(abs((float) $target - (float) $current), 3, '.', '');

            return (bccomp($current, '0.000', 3) === 0 && bccomp($target, '0.000', 3) > 0)
                || bccomp($variance, $threshold, 3) >= 0;
        });
        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addMinutes(max(1, (int) config('jawla.stock_import.preview_ttl_minutes', 15)));

        StockImportPreview::create([
            'company_id' => $warehouse->company_id,
            'warehouse_id' => $warehouse->id,
            'staged_by' => $user->id,
            'token_hash' => hash('sha256', $token),
            'file_path' => $absolutePath,
            'file_checksum' => $preview['checksum'],
            'parsed_rows' => $preview['valid'],
            'errors' => $preview['errors'],
            'requires_approval' => $requiresApproval,
            'status' => $preview['errors'] === [] && $preview['valid'] !== [] ? 'staged' : 'invalid',
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $token,
            'preview' => $preview,
            'expires_at' => $expiresAt->toIso8601String(),
            'requires_approval' => $requiresApproval,
        ];
    }

    public function approve(string $token, User $manager): StockImportPreview
    {
        return DB::transaction(function () use ($token, $manager): StockImportPreview {
            return $this->approveLocked($this->lockedPreview($token), $manager);
        });
    }

    public function approveById(int $previewId, User $manager): StockImportPreview
    {
        return DB::transaction(function () use ($previewId, $manager): StockImportPreview {
            $staged = StockImportPreview::withoutGlobalScopes()
                ->whereKey($previewId)->lockForUpdate()->firstOrFail();

            return $this->approveLocked($staged, $manager);
        });
    }

    public function confirm(string $token, User $user, string $fileName): WarehouseImportLog
    {
        return DB::transaction(function () use ($token, $user, $fileName): WarehouseImportLog {
            $staged = $this->lockedPreview($token);
            $warehouse = Warehouse::withoutGlobalScopes()
                ->whereKey($staged->warehouse_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorizeImporter($user, $warehouse);
            app(ActiveCompanyContext::class)->assertMatches((int) $staged->company_id);
            $this->assertUsable($staged);

            if ((int) $staged->staged_by !== (int) $user->id) {
                throw new DomainException('This stock-import token belongs to another user.');
            }
            if ($staged->requires_approval && $staged->approved_at === null) {
                throw new DomainException('This opening balance or large variance requires sales-manager approval.');
            }
            if (! is_file($staged->file_path)
                || ! hash_equals($staged->file_checksum, hash_file('sha256', $staged->file_path))) {
                throw new DomainException('The staged stock-import file changed after preview.');
            }
            if (WarehouseImportLog::where('checksum', $staged->file_checksum)->exists()) {
                throw new DomainException('This exact file was already imported.');
            }

            $reparsed = $this->preview($staged->file_path, $warehouse);
            if ($reparsed['errors'] !== [] || $reparsed['valid'] === []) {
                throw new DomainException('The stock import no longer passes server validation.');
            }
            if ($staged->parsed_rows != $reparsed['valid']) {
                throw new DomainException('The stock import differs from its server-owned preview.');
            }

            foreach ($reparsed['valid'] as $row) {
                $this->stock->reconcile(
                    $warehouse->id,
                    $row['product_id'],
                    null,
                    $row['quantity'],
                    'warehouse_import',
                    $user->id,
                    $row['current'],
                );
            }

            $log = WarehouseImportLog::create([
                'warehouse_id' => $warehouse->id,
                'imported_by' => $user->id,
                'file_name' => $fileName,
                'rows_imported' => count($reparsed['valid']),
                'rows_total' => count($reparsed['valid']),
                'rows_rejected' => 0,
                'errors' => [],
                'status' => 'completed',
                'checksum' => $staged->file_checksum,
                'imported_at' => now(),
            ]);
            $staged->update(['status' => 'consumed', 'consumed_at' => now()]);

            return $log;
        });
    }

    private function authorizeImporter(User $user, Warehouse $warehouse): void
    {
        if (! $user->hasCompanyAccess((int) $warehouse->company_id)
            || ! $user->hasRole('warehouse_keeper')) {
            throw new DomainException('Stock import execution requires an assigned warehouse keeper.');
        }
    }

    private function approveLocked(StockImportPreview $staged, User $manager): StockImportPreview
    {
        if (! $manager->hasRole('sales_manager')
            || ! $manager->hasCompanyAccess((int) $staged->company_id)) {
            throw new DomainException('Only a same-company sales manager may approve this stock adjustment.');
        }
        app(ActiveCompanyContext::class)->assertMatches((int) $staged->company_id);
        $this->assertUsable($staged);
        if (! $staged->requires_approval) {
            throw new DomainException('This stock import does not require manager approval.');
        }
        $staged->update([
            'approved_by' => $manager->id,
            'approved_at' => now(),
            'status' => 'approved',
        ]);

        return $staged->fresh();
    }

    private function lockedPreview(string $token): StockImportPreview
    {
        $preview = StockImportPreview::withoutGlobalScopes()
            ->where('token_hash', hash('sha256', $token))
            ->lockForUpdate()
            ->first();
        if (! $preview) {
            throw new DomainException('Unknown stock-import token.');
        }

        return $preview;
    }

    private function assertUsable(StockImportPreview $staged): void
    {
        if ($staged->expires_at->isPast()) {
            throw new DomainException('The stock-import preview expired. Preview the file again.');
        }
        if (in_array($staged->status, ['invalid', 'consumed'], true)) {
            throw new DomainException('The stock-import preview cannot be used.');
        }
    }

    public function template(): string
    {
        return "sku,quantity,transit_quantity\nSKU-0001,120.5,30\nSKU-0002,0,\n";
    }
}
