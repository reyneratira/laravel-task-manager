<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Medium,
            'due_date' => now()->addDays(rand(1, 30)),
            'assigned_to' => User::factory(),
            'created_by' => User::factory()->state(['role' => 'admin']),
        ];
    }

    /** Status: pending */
    public function pending(): static
    {
        return $this->state(fn() => ['status' => TaskStatus::Pending]);
    }

    /** Status: in_progress */
    public function inProgress(): static
    {
        return $this->state(fn() => ['status' => TaskStatus::InProgress]);
    }

    /** Status: done */
    public function done(): static
    {
        return $this->state(fn() => ['status' => TaskStatus::Done]);
    }

    /** Priority: high */
    public function highPriority(): static
    {
        return $this->state(fn() => ['priority' => TaskPriority::High]);
    }

    /** Tugas yang sudah lewat deadline */
    public function overdue(): static
    {
        return $this->state(fn() => [
            'due_date' => now()->subDays(rand(1, 7)),
            'status' => TaskStatus::Pending,
        ]);
    }

    /** Assign ke user tertentu */
    public function assignedTo(User $user): static
    {
        return $this->state(fn() => ['assigned_to' => $user->id]);
    }

    /** Dibuat oleh user tertentu */
    public function createdBy(User $user): static
    {
        return $this->state(fn() => ['created_by' => $user->id]);
    }
}
