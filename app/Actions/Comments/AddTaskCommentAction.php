<?php

declare(strict_types=1);

namespace App\Actions\Comments;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

class AddTaskCommentAction
{
    public function handle(Task $task, User $author, string $body): TaskComment
    {
        $comment = new TaskComment(['body' => $body]);

        // Tenant from the parent task, authorship from the authenticated
        // caller — neither is ever request input.
        $comment->task_id = $task->getKey();
        $comment->team_id = $task->team_id;
        $comment->user_id = $author->getKey();
        $comment->save();

        return $comment;
    }
}
