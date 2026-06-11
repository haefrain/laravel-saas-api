<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Task $task */
        $task = $this->resource;

        return [
            'id' => $task->id,
            'team_id' => $task->team_id,
            'project_id' => $task->project_id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'priority' => $task->priority,
            'assignee' => UserResource::make($this->whenLoaded('assignee')),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'due_at' => $task->due_at?->toIso8601String(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'comments_count' => $this->whenCounted('comments'),
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
        ];
    }
}
