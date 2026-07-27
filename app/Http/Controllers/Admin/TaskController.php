<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskAttachmentRequest;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Exports\TaskExport;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class TaskController extends Controller
{
    public function __construct()
    {
        // Policy diterapkan di tiap method
        $this->authorizeResource(Task::class, 'task');
    }

    /** Daftar semua tugas (admin melihat semua) */
    public function index(Request $request): View
    {
        $tasks = Task::with(['assignee', 'creator'])
            ->when($request->status, fn($q) => $q->byStatus(TaskStatus::from($request->status)))
            ->when($request->priority, fn($q) => $q->byPriority(TaskPriority::from($request->priority)))
            ->when($request->user_id, fn($q) => $q->forUser($request->user_id))
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $users = User::regularUsers()->get(['id', 'name']);

        return view('admin.tasks.index', compact('tasks', 'users'));
    }

    /** Export tugas ke Excel */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $filters = $request->only(['status', 'priority', 'user_id', 'search']);

        return Excel::download(new TaskExport($filters), 'tugas-admin.xlsx');
    }

    /** Export tugas ke PDF */
    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::with(['assignee', 'creator'])
            ->when($request->status, fn($q) => $q->byStatus(TaskStatus::from($request->status)))
            ->when($request->priority, fn($q) => $q->byPriority(TaskPriority::from($request->priority)))
            ->when($request->user_id, fn($q) => $q->forUser($request->user_id))
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->latest()
            ->get();

        $pdf = Pdf::loadView('admin.tasks.report-pdf', compact('tasks'));
        return $pdf->download('laporan-tugas.pdf');
    }

    /** Form buat tugas baru */
    public function create(): View
    {
        $users = User::where('role', 'user')->get(['id', 'name']);
        $statuses = TaskStatus::cases();
        $priorities = TaskPriority::cases();

        return view('admin.tasks.create', compact('users', 'statuses', 'priorities'));
    }

    /** Simpan tugas baru ke database */
    public function store(TaskRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $task = Task::create($data);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $sanitizedFilename = StoreTaskAttachmentRequest::sanitizeFilename($file->getClientOriginalName());
                    $extension = $file->getClientOriginalExtension();
                    $uniqueStorageName = Str::uuid()->toString() . ($extension ? '.' . strtolower($extension) : '');
                    $storageDir = 'attachments/tasks/' . $task->id;

                    $path = $file->storeAs($storageDir, $uniqueStorageName, 'local');

                    $task->attachments()->create([
                        'user_id' => auth()->id(),
                        'filename' => $sanitizedFilename,
                        'path' => $path,
                        'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }
        }

        return redirect()
            ->route('admin.tasks.index')
            ->with('success', 'Tugas berhasil dibuat!');
    }


    /** Detail satu tugas */
    public function show(Task $task): View
    {
        $task->load(['assignee', 'creator']);
        return view('admin.tasks.show', compact('task'));
    }

    /** Form edit tugas */
    public function edit(Task $task): View
    {
        $users = User::where('role', 'user')->get(['id', 'name']);
        $statuses = TaskStatus::cases();
        $priorities = TaskPriority::cases();

        return view('admin.tasks.edit', compact('task', 'users', 'statuses', 'priorities'));
    }

    /** Update tugas */
    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return redirect()
            ->route('admin.tasks.show', $task)
            ->with('success', 'Tugas berhasil diperbarui!');
    }

    /** Soft delete tugas */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()
            ->route('admin.tasks.index')
            ->with('success', 'Tugas berhasil dihapus.');
    }

    /** Restore tugas yang dihapus */
    public function restore(int $id): RedirectResponse
    {
        $task = Task::withTrashed()->findOrFail($id);
        $this->authorize('restore', $task);

        $task->restore();

        return redirect()
            ->route('admin.tasks.index')
            ->with('success', 'Tugas berhasil dipulihkan.');
    }
}
