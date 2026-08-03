<?php

namespace App\Notifications;

use App\Models\Task;

class TaskOutcome extends RepNotification
{
    public function __construct(
        private readonly Task $task,
        private readonly string $outcome,
        private readonly ?string $reason = null,
    ) {}

    public function toDatabase(object $notifiable): array
    {
        $approved = $this->outcome === 'approved';

        return [
            'title_ar' => $approved ? 'تم اعتماد المهمة' : 'تحتاج المهمة إلى متابعة',
            'title_en' => $approved ? 'Task approved' : 'Task needs attention',
            'body_ar' => $this->reason ?: $this->task->title,
            'body_en' => $this->reason ?: $this->task->title,
            'severity' => $approved ? 'success' : 'warning',
            'url' => '/app/tasks',
        ];
    }
}
