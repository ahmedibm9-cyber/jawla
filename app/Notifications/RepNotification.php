<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Base class for rep-facing outcome notifications. Database channel only —
 * push is deferred to v1.1; the in-app bell covers AM4 in beta.
 */
abstract class RepNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title_ar: string, title_en: string, body_ar: string, body_en: string, severity: string, url: string}
     */
    abstract public function toDatabase(object $notifiable): array;
}
