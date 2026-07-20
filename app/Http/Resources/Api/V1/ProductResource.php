<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'unit' => $this->unit,
            'price' => (float) $this->price,
            'vat_applicable' => (bool) $this->vat_applicable,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
