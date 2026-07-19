<?php

namespace App\Notifications;

use App\Models\Complaint;

class ComplaintResolved extends RepNotification
{
    public function __construct(
        public readonly Complaint $complaint,
        public readonly string $resolution,
    ) {}

    public function toDatabase(object $notifiable): array
    {
        $customer = $this->complaint->customer?->name_ar ?? "#{$this->complaint->id}";

        return [
            'title_ar' => 'تم حل الشكوى',
            'title_en' => 'Complaint resolved',
            'body_ar' => "شكوى العميل {$customer} تم حلها: {$this->resolution}",
            'body_en' => "Complaint for {$customer} resolved: {$this->resolution}",
            'severity' => 'info',
            'url' => '/app/notifications',
        ];
    }
}
