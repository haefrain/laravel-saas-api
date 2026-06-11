<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'task_id' => null,
            'type' => 'task_assigned',
            'data' => ['actor_id' => null],
        ];
    }
}
