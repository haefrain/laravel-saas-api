<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskComment>
 */
class TaskCommentFactory extends Factory
{
    protected $model = TaskComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'team_id' => fn (array $attributes) => Task::find($attributes['task_id'])?->team_id
                ?? Task::factory()->create()->team_id,
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }

    public function forTask(Task $task): static
    {
        return $this->state([
            'task_id' => $task->id,
            'team_id' => $task->team_id,
        ]);
    }
}
