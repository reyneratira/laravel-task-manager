<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\User;
use App\Mail\TaskAssigned;
use App\Mail\TaskUnassigned;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskStatusChanged;
use Illuminate\Support\Facades\Mail;

class TaskObserver
{
    /**
     * Note: Eloquent observers don't fire on mass updates (e.g. Task::where(...)->update(...)).
     * If bulk reassignment is added in the future, these notifications must be handled manually there.
     */

    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        if ($task->assigned_to) {
            $this->notifyAssignment($task);
            $this->sendAssignedNotification($task);
        }
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        // Handle assignment change (email + in-app notification)
        if ($task->wasChanged('assigned_to')) {
            $oldAssigneeId = $task->getOriginal('assigned_to');
            $newAssigneeId = $task->assigned_to;

            // Notify old assignee if they were removed/changed
            if ($oldAssigneeId) {
                $oldUser = User::find($oldAssigneeId);
                if ($oldUser) {
                    Mail::to($oldUser)->queue(new TaskUnassigned($task));
                }
            }

            // Notify new assignee if set
            if ($newAssigneeId) {
                $this->notifyAssignment($task);
                $this->sendAssignedNotification($task);
            }
        }

        // Handle status change (in-app real-time notification)
        if ($task->wasChanged('status')) {
            $this->sendStatusChangedNotification($task);
        }
    }

    /**
     * Send email notification to new assignee.
     */
    private function notifyAssignment(Task $task): void
    {
        $user = User::find($task->assigned_to);
        if ($user) {
            $isSelfAssignment = auth()->check() && auth()->id() === $user->id;
            Mail::to($user)->queue(new TaskAssigned($task, $isSelfAssignment));
        }
    }

    /**
     * Send in-app + broadcast notification for task assignment.
     */
    private function sendAssignedNotification(Task $task): void
    {
        $assignee = User::find($task->assigned_to);
        $assigner = auth()->check() ? auth()->user() : null;

        if (!$assignee || !$assigner) {
            return;
        }

        // Don't notify if assigning to yourself
        $isSelfAssignment = $assigner->id === $assignee->id;
        if ($isSelfAssignment) {
            return;
        }

        $assignee->notify(new TaskAssignedNotification($task, $assigner));
    }

    /**
     * Send in-app + broadcast notification for status change.
     * Notifies:
     * - The assignee (if someone else changed the status)
     * - The creator/admin (if a user changed the status)
     */
    private function sendStatusChangedNotification(Task $task): void
    {
        $changedBy = auth()->check() ? auth()->user() : null;
        if (!$changedBy) {
            return;
        }

        $oldStatus = $task->getOriginal('status');
        // Handle both string and enum values from getOriginal
        $oldStatusValue = $oldStatus instanceof \App\Enums\TaskStatus
            ? $oldStatus->value
            : (string) $oldStatus;
        $oldStatusLabel = $oldStatus instanceof \App\Enums\TaskStatus
            ? $oldStatus->label()
            : \App\Enums\TaskStatus::from($oldStatusValue)->label();

        $recipients = collect();

        // Notify assignee (if they didn't make the change)
        if ($task->assigned_to && $task->assigned_to !== $changedBy->id) {
            $assignee = User::find($task->assigned_to);
            if ($assignee) {
                $recipients->push($assignee);
            }
        }

        // Notify creator/admin (if they didn't make the change)
        if ($task->created_by && $task->created_by !== $changedBy->id) {
            $creator = User::find($task->created_by);
            if ($creator && !$recipients->contains('id', $creator->id)) {
                $recipients->push($creator);
            }
        }

        foreach ($recipients as $recipient) {
            $recipient->notify(new TaskStatusChanged(
                $task,
                $oldStatusValue,
                $oldStatusLabel,
                $changedBy,
            ));
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }
}
