<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\TeamRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int|null $current_team_id
 * @property string $password
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Per-request map of team_id => caller's role, loaded once. This is the
     * single source for read-path role resolution so no read ever mutates
     * spatie's process-global team id.
     *
     * @var array<int, TeamRole>|null
     */
    protected ?array $membershipCache = null;

    /**
     * @return BelongsToMany<Team, $this, TeamUser>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->using(TeamUser::class)
            ->withPivot('membership_role')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * @return array<int, TeamRole>
     */
    public function membershipMap(): array
    {
        if ($this->membershipCache !== null) {
            return $this->membershipCache;
        }

        $map = [];
        $memberships = TeamUser::query()->where('user_id', $this->getKey())->get();
        foreach ($memberships as $membership) {
            $map[$membership->team_id] = $membership->membership_role;
        }

        return $this->membershipCache = $map;
    }

    public function belongsToTeam(Team $team): bool
    {
        return array_key_exists($team->getKey(), $this->membershipMap());
    }

    public function roleForTeam(Team $team): ?TeamRole
    {
        return $this->membershipMap()[$team->getKey()] ?? null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
