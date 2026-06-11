<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property TeamRole $membership_role
 */
class TeamUser extends Pivot
{
    public $incrementing = true;

    protected $table = 'team_user';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'membership_role' => TeamRole::class,
        ];
    }
}
