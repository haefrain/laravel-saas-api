<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Projects\CreateProjectAction;
use App\Actions\Projects\DeleteProjectAction;
use App\Actions\Projects\UpdateProjectAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\IndexProjectRequest;
use App\Http\Requests\Projects\StoreProjectRequest;
use App\Http\Requests\Projects\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function index(IndexProjectRequest $request, Team $team): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Project::class, $team]);

        $filters = $request->validated();
        $sort = (string) ($filters['sort'] ?? '-created_at');
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $query = $team->projects();

        if (isset($filters['q'])) {
            $like = '%'.addcslashes((string) $filters['q'], '\\%_').'%';
            $query->where('name', 'like', $like);
        }

        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        $projects = $query
            ->withCount('tasks')
            ->orderBy($column, $direction)
            ->paginate((int) ($filters['per_page'] ?? 15));

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request, Team $team, CreateProjectAction $action): JsonResponse
    {
        $this->authorize('create', [Project::class, $team]);

        /** @var User $user */
        $user = $request->user();

        $project = $action->handle($team, $user, $request->validated());

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Team $team, Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return ProjectResource::make($project);
    }

    public function update(UpdateProjectRequest $request, Team $team, Project $project, UpdateProjectAction $action): ProjectResource
    {
        $this->authorize('update', $project);

        return ProjectResource::make($action->handle($project, $request->validated()));
    }

    public function destroy(Team $team, Project $project, DeleteProjectAction $action): Response
    {
        $this->authorize('delete', $project);

        $action->handle($project);

        return response()->noContent();
    }
}
