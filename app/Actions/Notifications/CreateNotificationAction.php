<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\Notification;
use App\Models\Task;

class CreateNotificationAction
{
    /**
     * Tenant and recipient are derived from the task and the caller —
     * notifications are never created from request input.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Task $task, int $recipientId, string $type, array $data): Notification
    {
        return Notification::create([
            'team_id' => $task->team_id,
            'user_id' => $recipientId,
            'task_id' => $task->getKey(),
            'type' => $type,
            'data' => $data,
        ]);
    }
}
