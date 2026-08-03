<?php

namespace App\Notifications\Channels;

use App\Services\PushService;
use Illuminate\Notifications\Notification;

class PushChannel
{
    public function __construct(private readonly PushService $push) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toDatabase')) {
            return;
        }

        $this->push->send($notifiable, $notification->toDatabase($notifiable));
    }
}
