<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Support\LikeEscape;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only product listing for API integrations. Results are automatically
 * scoped to the token owner's company by the BelongsToCompany global scope
 * (active-company context is set from the authenticated user by middleware).
 */
class ProductController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->when($request->filled('q'), fn ($query) => $query->where(function ($w) use ($request) {
                $term = LikeEscape::wrap($request->string('q'));
                $w->where('name_ar', 'like', $term)
                    ->orWhere('name_en', 'like', $term)
                    ->orWhere('sku', 'like', $term);
            }))
            ->when($request->boolean('active_only'), fn ($query) => $query->where('is_active', true))
            ->orderBy('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return ProductResource::collection($products);
    }
}
