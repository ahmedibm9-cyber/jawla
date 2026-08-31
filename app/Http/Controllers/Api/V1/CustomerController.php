<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use App\Support\LikeEscape;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only customer listing for API integrations. Company-scoped exactly like
 * ProductController via the BelongsToCompany global scope.
 */
class CustomerController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $customers = Customer::query()
            ->when($request->filled('q'), fn ($query) => $query->where(function ($w) use ($request) {
                $term = LikeEscape::wrap($request->string('q'));
                $w->where('name_ar', 'like', $term)
                    ->orWhere('name_en', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            }))
            ->when($request->boolean('active_only'), fn ($query) => $query->where('is_active', true))
            ->orderBy('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return CustomerResource::collection($customers);
    }
}
