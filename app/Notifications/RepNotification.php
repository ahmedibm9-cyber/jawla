<?php

namespace App\Notifications;

use App\Notifications\Channels\PushChannel;
use Illuminate\Notifications\Notification;

/**
 * Base class for rep-facing outcome notifications. Push is enabled when this
 * self-hosted installation has a compatible gateway configured.
 */
abstract class RepNotification extends Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return config('jawla.push.gateway_url')
            ? ['database', PushChannel::class]
            : ['database'];
    }

    /**
     * @return array{title_ar: string, title_en: string, body_ar: string, body_en: string, severity: string, url: string}
     */
    abstract public function toDatabase(object $notifiable): array;
}
