<?php

namespace App\Notifications;

use App\Models\Customer;

class CustomerApprovalOutcome extends RepNotification
{
    public function __construct(
        public readonly Customer $customer,
        public readonly string $outcome, // 'approved' | 'rejected'
        public readonly ?string $reason = null,
    ) {}

    public function toDatabase(object $notifiable): array
    {
        $name = $this->customer->name_ar;

        if ($this->outcome === 'rejected') {
            return [
                'title_ar' => 'تم رفض العميل الجديد',
                'title_en' => 'New customer rejected',
                'body_ar' => "العميل {$name} مرفوض — السبب: {$this->reason}",
                'body_en' => "Customer {$name} was rejected — reason: {$this->reason}",
                'severity' => 'critical',
                'url' => '/app/customers',
            ];
        }

        return [
            'title_ar' => 'تم اعتماد العميل الجديد',
            'title_en' => 'New customer approved',
            'body_ar' => "العميل {$name} معتمد ويمكن البيع له الآن",
            'body_en' => "Customer {$name} is approved and ready for sales",
            'severity' => 'info',
            'url' => '/app/customers',
        ];
    }
}
