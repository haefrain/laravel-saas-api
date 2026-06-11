<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskComment
 */
class TaskCommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TaskComment $comment */
        $comment = $this->resource;

        return [
            'id' => $comment->id,
            'task_id' => $comment->task_id,
            'body' => $comment->body,
            'author' => UserResource::make($this->whenLoaded('author')),
            'created_at' => $comment->created_at?->toIso8601String(),
        ];
    }
}
