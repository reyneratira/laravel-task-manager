<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TaskStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public string $oldStatus,
        public string $oldStatusLabel,
        public User $changedBy,
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
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'old_status' => [
                'value' => $this->oldStatus,
                'label' => $this->oldStatusLabel,
            ],
            'new_status' => [
                'value' => $this->task->status->value,
                'label' => $this->task->status->label(),
            ],
            'changed_by' => [
                'id' => $this->changedBy->id,
                'name' => $this->changedBy->name,
            ],
            'message' => "Status tugas '{$this->task->title}' diubah dari {$this->oldStatusLabel} menjadi {$this->task->status->label()}",
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
