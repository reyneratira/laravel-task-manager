<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;

class TaskPolicy
{
    /** Admin bisa melihat daftar semua tugas */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Admin bisa lihat semua tugas, user hanya tugasnya sendiri */
    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->assigned_to === $user->id;
    }

    /** Hanya admin yang bisa buat tugas baru */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Admin bisa edit semua, user hanya bisa update status tugasnya sendiri */
    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->assigned_to === $user->id;
    }

    /** Hanya admin yang bisa hapus tugas (soft delete)*/
    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin();
    }

    /** Hanya admin yang bisa restore tugas yang dihapus (restore soft delete) */
    public function restore(User $user, Task $task): bool
    {
        return $user->isAdmin();
    }

    /** Hanya admin yang bisa assign tugas ke user lain */
    public function assign(User $user): bool
    {
        return $user->isAdmin();
    }
}
