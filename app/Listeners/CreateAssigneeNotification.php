<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Notifications\CreateNotificationAction;
use App\Events\TaskAssigned;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateAssigneeNotification implements ShouldQueue
{
    /** Only run after the assigning transaction commits. */
    public bool $afterCommit = true;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        private readonly CreateNotificationAction $createNotification,
    ) {}

    public function handle(TaskAssigned $event): void
    {
        // Nothing to notify on unassignment or self-assignment.
        if ($event->assigneeId === null || $event->assigneeId === $event->byUserId) {
            return;
        }

        $task = Task::find($event->taskId);
        if ($task === null) {
            return; // deleted before the queue caught up
        }

        $this->createNotification->handle($task, $event->assigneeId, 'task_assigned', [
            'actor_id' => $event->byUserId,
        ]);
    }

    public function failed(TaskAssigned $event, Throwable $exception): void
    {
        Log::warning('assignee notification failed', [
            'task_id' => $event->taskId,
            'assignee_id' => $event->assigneeId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
