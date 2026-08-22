<?php

namespace App\Http\Controllers\App;

use App\Models\Customer;
use App\Models\DailyVisitAssignment;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Stock;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Returns a compact JSON snapshot of the rep's essential read data for
 * offline-first caching. Single authenticated GET — no writes, no mutations.
 */
class OfflineSnapshotController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user->activeCompanyId();
        $today = now()->toDateString();

        $vanWarehouse = $user->vanWarehouse;

        return response()->json([
            'customers' => Customer::where('company_id', $companyId)
                ->where('is_active', true)
                ->where('status', 'approved')
                ->select(['id', 'name_ar', 'name_en', 'phone', 'code', 'address', 'latitude', 'longitude', 'balance', 'price_list_id', 'route_id'])
                ->limit(2000)
                ->get(),

            'products' => Product::where('company_id', $companyId)
                ->where('is_active', true)
                ->select(['id', 'sku', 'barcode', 'name_ar', 'name_en', 'unit', 'price', 'vat_applicable', 'category_id'])
                ->limit(3000)
                ->get(),

            'stock' => $vanWarehouse
                ? Stock::where('warehouse_id', $vanWarehouse->id)
                    ->where('quantity', '>', 0)
                    ->select(['id', 'product_id', 'quantity', 'batch_id'])
                    ->limit(5000)
                    ->get()
                : [],

            'assignments' => DailyVisitAssignment::where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->where('visit_date', $today)
                ->with('customer:id,name_ar,name_en,phone,code,latitude,longitude')
                ->select(['id', 'customer_id', 'status', 'sort_order'])
                ->get(),

            'pricing' => ProductPrice::where('is_active', true)
                ->whereIn('price_list_id', fn ($q) => $q->select('id')->from('price_lists')->where('company_id', $companyId))
                ->select(['id', 'product_id', 'price_list_id', 'price', 'uom', 'min_quantity', 'customer_id'])
                ->limit(5000)
                ->get(),

            'company' => [
                'id' => $companyId,
                'name_ar' => $user->company->name_ar,
                'name_en' => $user->company->name_en,
                'vat_percent' => $user->company->vat_percent,
                'tax_number' => $user->company->tax_number,
                'geofence_radius_m' => $user->company->geofence_radius_m,
                'currency' => $user->company->currency,
            ],

            'tasks' => Task::where('company_id', $companyId)
                ->where('assigned_to', $user->id)
                ->where('status', 'open')
                ->select(['id', 'title', 'note', 'due_date', 'customer_id'])
                ->limit(200)
                ->get(),

            'cashbox' => $user->cashBox
                ? ['balance' => $user->cashBox->balance]
                : ['balance' => '0.00'],

            'cachedAt' => now()->toIso8601String(),
        ]);
    }
}
