<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;

class PurchaseOfferOutcome extends RepNotification
{
    public function __construct(
        public readonly PurchaseRequest $request,
        public readonly string $outcome, // 'sales_approved' | 'rejected_by_sales' | 'purchasing_approved' | 'rejected_by_purchasing'
        public readonly ?string $reason = null,
        public readonly ?string $poNumber = null,
    ) {}

    public function toDatabase(object $notifiable): array
    {
        $product = $this->request->product->name_ar ?? "#{$this->request->id}";
        $reasonAr = $this->reason ? " — السبب: {$this->reason}" : '';
        $reasonEn = $this->reason ? " — Reason: {$this->reason}" : '';

        return match ($this->outcome) {
            'sales_approved' => [
                'title_ar' => 'وافق مدير المبيعات على عرض الشراء',
                'title_en' => 'Sales approved your purchase offer',
                'body_ar' => "عرض شراء {$product} انتقل إلى مراجعة المشتريات",
                'body_en' => "Purchase offer for {$product} moved to Purchasing review",
                'severity' => 'info',
                'url' => '/app/orders?type=offers',
            ],
            'purchasing_approved' => [
                'title_ar' => 'تمت الموافقة النهائية — تم إنشاء أمر شراء',
                'title_en' => 'Final approval — purchase order created',
                'body_ar' => "عرض شراء {$product} اعتُمد وتم إنشاء أمر الشراء {$this->poNumber}",
                'body_en' => "Purchase offer for {$product} approved — PO {$this->poNumber} created",
                'severity' => 'info',
                'url' => '/app/orders?type=offers',
            ],
            'rejected_by_sales' => [
                'title_ar' => 'رفض مدير المبيعات عرض الشراء',
                'title_en' => 'Sales rejected your purchase offer',
                'body_ar' => "عرض شراء {$product} مرفوض — يمكنك تعديله وإعادة تقديمه{$reasonAr}",
                'body_en' => "Purchase offer for {$product} was rejected — you can edit and resubmit{$reasonEn}",
                'severity' => 'critical',
                'url' => '/app/orders?type=offers',
            ],
            'rejected_by_purchasing' => [
                'title_ar' => 'رفضت المشتريات عرض الشراء',
                'title_en' => 'Purchasing rejected your purchase offer',
                'body_ar' => "عرض شراء {$product} مرفوض من المشتريات — يمكنك تعديله وإعادة تقديمه{$reasonAr}",
                'body_en' => "Purchase offer for {$product} was rejected by Purchasing — you can edit and resubmit{$reasonEn}",
                'severity' => 'critical',
                'url' => '/app/orders?type=offers',
            ],
            default => throw new \InvalidArgumentException("Unknown purchase offer outcome: {$this->outcome}"),
        };
    }
}
