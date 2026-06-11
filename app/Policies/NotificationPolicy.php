<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notification;
use App\Models\Team;
use App\Models\User;

/**
 * Notifications are object-level owned: there is deliberately no team-role
 * override here — owners and admins cannot read or mark a teammate's
 * notifications.
 */
class NotificationPolicy
{
    public function view(User $user, Notification $notification): bool
    {
        return $this->ownsNotification($user, $notification);
    }

    public function update(User $user, Notification $notification): bool
    {
        return $this->ownsNotification($user, $notification);
    }

    private function ownsNotification(User $user, Notification $notification): bool
    {
        $team = $notification->team;

        return $team instanceof Team
            && $user->belongsToTeam($team)
            && $notification->user_id === $user->getKey();
    }
}
