<?php

declare(strict_types=1);

namespace App\Events;

/**
 * Carries ids, not models: queued listeners re-fetch fresh state and the
 * payload stays trivially serializable.
 */
final readonly class TaskAssigned
{
    public function __construct(
        public int $taskId,
        public ?int $assigneeId,
        public int $byUserId,
    ) {}
}
