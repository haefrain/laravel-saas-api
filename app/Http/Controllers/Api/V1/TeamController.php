<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Teams\CreateTeamAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\StoreTeamRequest;
use App\Http\Requests\Teams\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TeamController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $teams = $user->teams()->withCount(['members', 'projects'])->orderBy('name')->get();

        return TeamResource::collection($teams);
    }

    public function store(StoreTeamRequest $request, CreateTeamAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $name = (string) $request->validated('name');
        $slug = $request->validated('slug');

        $team = $action->handle($user, $name, is_string($slug) ? $slug : null);

        return TeamResource::make($team->loadCount('members'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Team $team): TeamResource
    {
        $this->authorize('view', $team);

        return TeamResource::make($team->loadCount('members'));
    }

    public function update(UpdateTeamRequest $request, Team $team): TeamResource
    {
        $this->authorize('update', $team);

        $team->update($request->validated());

        return TeamResource::make($team->refresh()->loadCount('members'));
    }

    public function destroy(Team $team): Response
    {
        $this->authorize('delete', $team);

        $team->delete();

        return response()->noContent();
    }
}
