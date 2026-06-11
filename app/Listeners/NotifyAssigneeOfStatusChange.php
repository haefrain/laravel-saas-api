<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Notifications\CreateNotificationAction;
use App\Events\TaskStatusChanged;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyAssigneeOfStatusChange implements ShouldQueue
{
    /** Only run after the transitioning transaction commits. */
    public bool $afterCommit = true;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        private readonly CreateNotificationAction $createNotification,
    ) {}

    public function handle(TaskStatusChanged $event): void
    {
        $task = Task::find($event->taskId);

        // No assignee, or the assignee made the change themselves: skip.
        if ($task === null || $task->assignee_id === null || $task->assignee_id === $event->byUserId) {
            return;
        }

        $this->createNotification->handle($task, $task->assignee_id, 'task_status_changed', [
            'actor_id' => $event->byUserId,
            'from' => $event->from->value,
            'to' => $event->to->value,
        ]);
    }

    public function failed(TaskStatusChanged $event, Throwable $exception): void
    {
        Log::warning('status-change notification failed', [
            'task_id' => $event->taskId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
