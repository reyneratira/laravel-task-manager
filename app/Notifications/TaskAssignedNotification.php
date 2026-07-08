<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public User $assignedBy,
        public bool $isSelfAssignment = false,
    ) {}

    /**
     * Deliver via database (persistence) + broadcast (real-time).
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Data stored in the notifications table.
     */
    public function toArray(object $notifiable): array
    {
        $message = $this->isSelfAssignment
            ? "Anda membuat tugas '{$this->task->title}' untuk diri sendiri"
            : "{$this->assignedBy->name} menugaskan '{$this->task->title}' kepada Anda";

        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'assigned_by' => [
                'id' => $this->assignedBy->id,
                'name' => $this->assignedBy->name,
            ],
            'is_self_assignment' => $this->isSelfAssignment,
            'message' => $message,
        ];
    }

    /**
     * Data sent via WebSocket broadcast.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
