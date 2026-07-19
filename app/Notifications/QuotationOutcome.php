<?php

namespace App\Notifications;

use App\Models\PriceQuotationRequest;

class QuotationOutcome extends RepNotification
{
    public function __construct(
        public readonly PriceQuotationRequest $request,
        public readonly string $outcome, // 'priced' | 'cancelled'
    ) {}

    public function toDatabase(object $notifiable): array
    {
        $product = $this->request->product?->name_ar ?? "#{$this->request->id}";

        if ($this->outcome === 'cancelled') {
            return [
                'title_ar' => 'تم إلغاء طلب التسعير',
                'title_en' => 'Quotation request cancelled',
                'body_ar' => "طلب تسعير {$product} تم إلغاؤه",
                'body_en' => "Quotation request for {$product} was cancelled",
                'severity' => 'critical',
                'url' => '/app/quotations',
            ];
        }

        return [
            'title_ar' => 'تم تسعير طلبك',
            'title_en' => 'Your quotation was priced',
            'body_ar' => "طلب تسعير {$product} جاهز — افتح عروض الأسعار للتأكيد",
            'body_en' => "Quotation for {$product} is ready — open quotations to confirm",
            'severity' => 'info',
            'url' => '/app/quotations',
        ];
    }
}
