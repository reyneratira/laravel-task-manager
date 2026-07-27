<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;

class TaskAttachmentPolicy
{
    /** Admin atau assignee task dapat melihat/list lampiran. */
    public function view(User $user, TaskAttachment $attachment): bool
    {
        return $user->isAdmin() || $attachment->task->assigned_to === $user->id;
    }

    /** Admin atau assignee task dapat mengunggah lampiran. */
    public function create(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->assigned_to === $user->id;
    }

    /** Admin atau assignee task dapat mendownload lampiran. */
    public function download(User $user, TaskAttachment $attachment): bool
    {
        return $user->isAdmin() || $attachment->task->assigned_to === $user->id;
    }

    /** Admin atau pengunggah lampiran dapat menghapus lampiran. */
    public function delete(User $user, TaskAttachment $attachment): bool
    {
        return $user->isAdmin() || ($attachment->user_id !== null && $attachment->user_id === $user->id);
    }
}
