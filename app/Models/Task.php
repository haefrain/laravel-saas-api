<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskStatus;
use App\Models\Concerns\BelongsToTeam;
use App\Models\Scopes\TeamScope;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $project_id
 * @property int|null $assignee_id
 * @property int|null $created_by
 * @property string $title
 * @property string|null $description
 * @property TaskStatus $status
 * @property int $priority
 * @property Carbon|null $due_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['project_id', 'assignee_id', 'created_by', 'title', 'description', 'status', 'priority', 'due_at'])]
#[ScopedBy(TeamScope::class)]
class Task extends Model
{
    use BelongsToTeam, SoftDeletes;

    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * completed_at is intentionally absent from the fillable list: it is
     * stamped/cleared exclusively by TransitionTaskStatusAction.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => 'integer',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
