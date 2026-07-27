<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskAttachmentRequest;
use App\Http\Resources\TaskAttachmentResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * List lampiran per task.
     */
    public function index(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', [TaskAttachment::class, new TaskAttachment(['task_id' => $task->id])]);

        if ($request->user()->tokenCant('tasks:read')) {
            return response()->json(['message' => 'Token tidak memiliki akses tasks:read'], 403);
        }

        $attachments = $task->attachments()->with('uploader:id,name')->latest()->get();

        return response()->json([
            'message' => 'Daftar lampiran tugas berhasil diambil.',
            'data' => TaskAttachmentResource::collection($attachments),
        ]);
    }

    /**
     * Upload lampiran via API.
     */
    public function store(StoreTaskAttachmentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('create', [TaskAttachment::class, $task]);

        if ($request->user()->tokenCant('tasks:update') && $request->user()->tokenCant('tasks:update-status')) {
            return response()->json(['message' => 'Token tidak memiliki izin untuk menambah lampiran.'], 403);
        }


        $file = $request->file('file');
        $sanitizedFilename = StoreTaskAttachmentRequest::sanitizeFilename($file->getClientOriginalName());

        $extension = $file->getClientOriginalExtension();
        $uniqueStorageName = Str::uuid()->toString() . ($extension ? '.' . strtolower($extension) : '');
        $storageDir = 'attachments/tasks/' . $task->id;

        $path = $file->storeAs($storageDir, $uniqueStorageName, 'local');

        $attachment = $task->attachments()->create([
            'user_id' => $request->user()->id,
            'filename' => $sanitizedFilename,
            'path' => $path,
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Lampiran berhasil diunggah.',
            'data' => new TaskAttachmentResource($attachment->load('uploader:id,name')),
        ], 201);
    }

    /**
     * Download lampiran via API.
     */
    public function download(Request $request, TaskAttachment $attachment): StreamedResponse|JsonResponse
    {
        $this->authorize('download', $attachment);

        if (!Storage::disk('local')->exists($attachment->path)) {
            return response()->json(['message' => 'Berkas tidak ditemukan.'], 404);
        }

        return Storage::disk('local')->download(
            $attachment->path,
            $attachment->filename,
            ['Content-Type' => $attachment->mime_type ?? 'application/octet-stream']
        );
    }

    /**
     * Hapus lampiran via API.
     */
    public function destroy(Request $request, TaskAttachment $attachment): JsonResponse
    {
        $this->authorize('delete', $attachment);

        if (Storage::disk('local')->exists($attachment->path)) {
            Storage::disk('local')->delete($attachment->path);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Lampiran berhasil dihapus.',
        ]);
    }
}
