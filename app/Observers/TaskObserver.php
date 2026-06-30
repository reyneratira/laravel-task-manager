<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\User;
use App\Mail\TaskAssigned;
use App\Mail\TaskUnassigned;
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
        }
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
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
            }
        }
    }

    /**
     * Helper to notify the new assignee.
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
