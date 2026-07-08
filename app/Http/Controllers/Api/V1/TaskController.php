<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTaskRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Exports\TaskExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class TaskController extends Controller
{
    /**
     * Export tasks.
     * Admin sees all tasks (optionally filtered by user_id).
     * Regular user sees only their own tasks.
     */
    public function export(Request $request)
    {
        // Cek token ability
        if ($request->user()->tokenCant('tasks:read')) {
            abort(403, 'Token tidak memiliki izin untuk melihat tugas.');
        }

        $filters = $request->only(['status', 'priority', 'search']);

        // Jika bukan admin, paksa filter ke tugas miliknya sendiri
        if (! $request->user()->isAdmin()) {
            $filters['user_id'] = $request->user()->id;
        } else {
            // Admin bisa filter by user_id
            if ($request->has('user_id')) {
                $filters['user_id'] = $request->input('user_id');
            }
        }

        $format = $request->input('format', 'excel');

        if ($format === 'pdf') {
            $tasks = Task::with(['assignee', 'creator'])
                ->when(isset($filters['status']) && $filters['status'], fn($q) => $q->byStatus(TaskStatus::from($filters['status'])))
                ->when(isset($filters['priority']) && $filters['priority'], fn($q) => $q->byPriority(TaskPriority::from($filters['priority'])))
                ->when(isset($filters['user_id']) && $filters['user_id'], fn($q) => $q->forUser($filters['user_id']))
                ->when(isset($filters['search']) && $filters['search'], fn($q) => $q->where('title', 'like', "%{$filters['search']}%"))
                ->latest()
                ->get();

            $pdf = Pdf::loadView('admin.tasks.report-pdf', compact('tasks'));
            return $pdf->download('tugas.pdf');
        }

        return Excel::download(new TaskExport($filters), 'tugas.xlsx');
    }

    /**
     * Daftar tugas. Admin melihat semua, user hanya tugasnya sendiri.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Cek token ability
        if ($request->user()->tokenCant('tasks:read')) {
            abort(403, 'Token tidak memiliki izin untuk melihat tugas.');
        }

        $query = Task::with(['assignee', 'creator']);

        // User biasa hanya lihat tugas sendiri
        if (! $request->user()->isAdmin()) {
            $query->forUser($request->user()->id);
        }

        // Filter opsional
        $query->when($request->status, fn($q) => $q->byStatus(TaskStatus::from($request->status)))
            ->when($request->priority, fn($q) => $q->byPriority(TaskPriority::from($request->priority)))
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"));

        $perPage = $request->input('per_page', 15);
        $tasks = $query->latest()->paginate($perPage);

        return TaskResource::collection($tasks);
    }

    /**
     * Buat tugas baru (admin only).
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        if ($request->user()->tokenCant('tasks:create')) {
            abort(403, 'Token tidak memiliki izin untuk membuat tugas.');
        }

        $this->authorize('create', Task::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $task = Task::create($data);
        $task->load(['assignee', 'creator']);

        return response()->json([
            'message' => 'Tugas berhasil dibuat.',
            'data' => new TaskResource($task),
        ], 201);
    }

    /**
     * Detail satu tugas.
     */
    public function show(Request $request, Task $task): JsonResponse
    {
        if ($request->user()->tokenCant('tasks:read')) {
            abort(403, 'Token tidak memiliki izin untuk melihat tugas.');
        }

        $this->authorize('view', $task);

        $task->load(['assignee', 'creator']);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * Update tugas.
     * Admin: bisa update semua field.
     * User biasa: hanya bisa update status (dan tidak bisa set 'cancelled').
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $user = $request->user();

        // Cek token ability yang sesuai
        if ($user->isAdmin()) {
            if ($user->tokenCant('tasks:update')) {
                abort(403, 'Token tidak memiliki izin untuk mengedit tugas.');
            }
        } else {
            if ($user->tokenCant('tasks:update-status')) {
                abort(403, 'Token tidak memiliki izin untuk mengubah status tugas.');
            }
        }

        $this->authorize('update', $task);

        $task->update($request->validated());
        $task->load(['assignee', 'creator']);

        return response()->json([
            'message' => 'Tugas berhasil diperbarui.',
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * Hapus tugas (soft delete, admin only).
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        if ($request->user()->tokenCant('tasks:delete')) {
            abort(403, 'Token tidak memiliki izin untuk menghapus tugas.');
        }

        $this->authorize('delete', $task);

        $task->delete();

        return response()->json([
            'message' => 'Tugas berhasil dihapus.',
        ]);
    }
}
