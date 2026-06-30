<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Mail\TaskAssigned;
use App\Mail\TaskDeadlineReminder;
use App\Mail\TaskUnassigned;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_assigned_mailable_is_queued_when_assigned()
    {
        Mail::fake();

        $user = User::factory()->create(['role' => 'user']);

        // Simulating the creation logic that hits the observer
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
        ]);

        Mail::assertQueued(TaskAssigned::class, function ($mail) use ($user, $task) {
            return $mail->hasTo($user->email) && $mail->task->id === $task->id;
        });
    }

    public function test_task_unassigned_mailable_is_queued_when_reassigned()
    {
        Mail::fake();

        $user1 = User::factory()->create(['role' => 'user']);
        $user2 = User::factory()->create(['role' => 'user']);

        $task = Task::factory()->create([
            'assigned_to' => $user1->id,
        ]);

        // Clear fake queue from creation
        Mail::fake();

        $task->update([
            'assigned_to' => $user2->id,
        ]);

        Mail::assertQueued(TaskUnassigned::class, function ($mail) use ($user1, $task) {
            return $mail->hasTo($user1->email) && $mail->task->id === $task->id;
        });

        Mail::assertQueued(TaskAssigned::class, function ($mail) use ($user2, $task) {
            return $mail->hasTo($user2->email) && $mail->task->id === $task->id;
        });
    }

    public function test_deadline_reminder_command_queues_emails_idempotently()
    {
        Mail::fake();

        $user = User::factory()->create(['role' => 'user']);
        
        $tomorrow = Carbon::tomorrow(config('app.timezone'));

        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'due_date' => $tomorrow,
            'status' => TaskStatus::Pending,
        ]);

        // Run command first time
        Artisan::call('app:send-task-deadline-reminders');

        Mail::assertQueued(TaskDeadlineReminder::class, 1);
        Mail::assertQueued(TaskDeadlineReminder::class, function ($mail) use ($user, $task) {
            return $mail->hasTo($user->email) && $mail->task->id === $task->id;
        });

        $this->assertNotNull($task->fresh()->reminder_sent_at);

        // Run command second time
        Artisan::call('app:send-task-deadline-reminders');

        // Should still only be 1 queued email because of idempotency
        Mail::assertQueued(TaskDeadlineReminder::class, 1);
    }
}
