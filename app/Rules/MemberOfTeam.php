<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Team;
use App\Models\TeamUser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects user ids from outside the given team — the validation-layer guard
 * against cross-tenant assignment (re-asserted in AssignTaskAction).
 */
class MemberOfTeam implements ValidationRule
{
    public function __construct(private readonly Team $team) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return; // nullability is decided by the accompanying rules
        }

        $isMember = TeamUser::query()
            ->where('team_id', $this->team->getKey())
            ->where('user_id', $value)
            ->exists();

        if (! $isMember) {
            $fail('The selected :attribute must be a member of this team.');
        }
    }
}
