<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tasks\AssignTaskAction;
use App\Actions\Tasks\CreateTaskAction;
use App\Actions\Tasks\TransitionTaskStatusAction;
use App\Actions\Tasks\UpdateTaskAction;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\AssignTaskRequest;
use App\Http\Requests\Tasks\IndexTaskRequest;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\TransitionTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function index(IndexTaskRequest $request, Team $team, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $filters = $request->validated();
        $sort = (string) ($filters['sort'] ?? '-created_at');
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $query = $project->tasks()->with(['assignee', 'creator'])->withCount('comments');

        $statuses = $request->statuses();
        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        if (isset($filters['assignee_id'])) {
            $assignee = (string) $filters['assignee_id'];
            match (true) {
                $assignee === 'me' => $query->where('assignee_id', $request->user()?->getKey()),
                $assignee === 'null' => $query->whereNull('assignee_id'),
                default => $query->where('assignee_id', (int) $assignee),
            };
        }

        if (isset($filters['q'])) {
            $like = '%'.addcslashes((string) $filters['q'], '\\%_').'%';
            $query->where('title', 'like', $like);
        }

        if (isset($filters['due_before'])) {
            $query->where('due_at', '<=', $filters['due_before']);
        }

        if (isset($filters['due_after'])) {
            $query->where('due_at', '>=', $filters['due_after']);
        }

        $tasks = $query
            ->orderBy($column, $direction)
            ->paginate((int) ($filters['per_page'] ?? 15));

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, Team $team, Project $project, CreateTaskAction $action): JsonResponse
    {
        $this->authorize('create', [Task::class, $project]);

        /** @var User $user */
        $user = $request->user();

        $task = $action->handle($project, $user, $request->validated());

        return TaskResource::make($task->load(['assignee', 'creator']))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Team $team, Project $project, Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return TaskResource::make($task->load(['assignee', 'creator'])->loadCount('comments'));
    }

    public function update(UpdateTaskRequest $request, Team $team, Project $project, Task $task, UpdateTaskAction $action): TaskResource
    {
        $this->authorize('update', $task);

        return TaskResource::make($action->handle($task, $request->validated())->load(['assignee', 'creator']));
    }

    public function destroy(Team $team, Project $project, Task $task): Response
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }

    public function assign(AssignTaskRequest $request, Team $team, Project $project, Task $task, AssignTaskAction $action): TaskResource
    {
        $this->authorize('assign', $task);

        /** @var User $user */
        $user = $request->user();
        $assigneeId = $request->validated('assignee_id');

        $task = $action->handle($task, $assigneeId === null ? null : (int) $assigneeId, $user);

        return TaskResource::make($task->load(['assignee', 'creator']));
    }

    public function transition(TransitionTaskRequest $request, Team $team, Project $project, Task $task, TransitionTaskStatusAction $action): TaskResource
    {
        $this->authorize('transition', $task);

        /** @var User $user */
        $user = $request->user();
        $status = TaskStatus::from((string) $request->validated('status'));

        $task = $action->handle($task, $status, $user);

        return TaskResource::make($task->load(['assignee', 'creator']));
    }
}
