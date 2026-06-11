<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // team_id mirrors the parent project's team, as in CreateTaskAction.
            'project_id' => Project::factory(),
            'team_id' => fn (array $attributes) => Project::find($attributes['project_id'])?->team_id
                ?? Project::factory()->create()->team_id,
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => TaskStatus::Todo,
            'priority' => fake()->numberBetween(1, 4),
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state([
            'project_id' => $project->id,
            'team_id' => $project->team_id,
        ]);
    }

    public function status(TaskStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
