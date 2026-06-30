<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Mail\TaskDeadlineReminder;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendTaskDeadlineReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-task-deadline-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders for tasks due tomorrow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Explicit timezone handling for "tomorrow"
        $tomorrow = Carbon::tomorrow(config('app.timezone'));

        $tasks = Task::query()->with('assignee')
            ->whereDate('due_date', $tomorrow)
            ->whereNull('reminder_sent_at')
            ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])
            ->whereNotNull('assigned_to')
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            if ($task->assignee) {
                Mail::to($task->assignee)->queue(new TaskDeadlineReminder($task));
                
                // Mark as sent to ensure idempotency. Use saveQuietly to skip observer and fillable checks.
                $task->reminder_sent_at = now();
                $task->saveQuietly();
                $count++;
            }
        }

        $this->info("Sent {$count} task deadline reminders.");
    }
}
