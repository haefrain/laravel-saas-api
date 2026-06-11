<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Comments\AddTaskCommentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comments\StoreTaskCommentRequest;
use App\Http\Resources\TaskCommentResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaskCommentController extends Controller
{
    public function index(Request $request, Team $team, Project $project, Task $task): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [TaskComment::class, $task]);

        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $comments = $task->comments()
            ->with('author')
            ->orderBy('created_at')
            ->paginate((int) ($validated['per_page'] ?? 15));

        return TaskCommentResource::collection($comments);
    }

    public function store(StoreTaskCommentRequest $request, Team $team, Project $project, Task $task, AddTaskCommentAction $action): JsonResponse
    {
        $this->authorize('create', [TaskComment::class, $task]);

        /** @var User $user */
        $user = $request->user();

        $comment = $action->handle($task, $user, (string) $request->validated('body'));

        return TaskCommentResource::make($comment->load('author'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Team $team, Project $project, Task $task, TaskComment $comment): Response
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->noContent();
    }
}
