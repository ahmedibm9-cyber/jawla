<?php

namespace App\Notifications;

use App\Models\OutOfStockRequest;

class OutOfStockResolved extends RepNotification
{
    public function __construct(
        public readonly OutOfStockRequest $request,
    ) {}

    public function toDatabase(object $notifiable): array
    {
        $product = $this->request->product?->name_ar ?? "#{$this->request->product_id}";

        return [
            'title_ar' => 'تم توفير المنتج',
            'title_en' => 'Out-of-stock request fulfilled',
            'body_ar' => "المنتج {$product} الذي أبلغت عن نفاده أصبح متاحاً",
            'body_en' => "{$product}, which you flagged out of stock, is now available",
            'severity' => 'info',
            'url' => '/app/stock',
        ];
    }
}
