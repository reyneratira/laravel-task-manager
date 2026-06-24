<?php

namespace App\Http\Controllers\User;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    /** Daftar tugas yang diberikan ke user yang login */
    public function index(Request $request): View
    {
        $tasks = Task::forUser(auth()->id())
            ->when($request->status, fn($q) => $q->byStatus(TaskStatus::from($request->status)))
            ->when($request->priority, fn($q) => $q->byPriority($request->priority))
            ->latest('due_date')
            ->paginate(10)
            ->withQueryString();

        $statuses = TaskStatus::cases();

        return view('user.tasks.index', compact('tasks', 'statuses'));
    }

    /** Detail tugas (hanya tugasnya sendiri) */
    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        return view('user.tasks.show', compact('task'));
    }

    /** User hanya bisa update status tugasnya sendiri */
    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $request->validate([
            'status' => ['required', 'in:pending,in_progress,done,cancelled'],
        ]);

        // User tidak boleh ubah ke 'cancelled' — hanya admin
        if ($request->status === 'cancelled' && !auth()->user()->isAdmin()) {
            return back()->with('error', 'Hanya admin yang dapat membatalkan tugas.');
        }

        $task->update(['status' => $request->status]);

        return back()->with('success', 'Status tugas berhasil diperbarui.');
    }

}
