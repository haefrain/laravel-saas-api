<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\IndexNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(IndexNotificationRequest $request, Team $team): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->validated();

        // Object-level ownership: hard-filtered to the caller's own rows —
        // the tenant scope alone would let teammates read each other's feed.
        $query = $team->notifications()->where('user_id', $user->getKey());

        if (filter_var($filters['unread'] ?? false, FILTER_VALIDATE_BOOL)) {
            $query->whereNull('read_at');
        }

        $notifications = $query
            ->orderByDesc('created_at')
            ->paginate((int) ($filters['per_page'] ?? 15));

        return NotificationResource::collection($notifications);
    }

    public function markRead(Team $team, Notification $notification): NotificationResource
    {
        $this->authorize('update', $notification);

        $notification->markAsRead();

        return NotificationResource::make($notification);
    }
}
