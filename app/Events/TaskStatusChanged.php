<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\TaskStatus;

final readonly class TaskStatusChanged
{
    public function __construct(
        public int $taskId,
        public TaskStatus $from,
        public TaskStatus $to,
        public int $byUserId,
    ) {}
}
