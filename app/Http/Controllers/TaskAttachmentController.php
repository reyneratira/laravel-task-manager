<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskAttachmentRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Upload dan simpan lampiran file.
     */
    public function store(StoreTaskAttachmentRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('create', [TaskAttachment::class, $task]);

        $file = $request->file('file');

        // Sanitasi nama asli file dari client
        $sanitizedFilename = StoreTaskAttachmentRequest::sanitizeFilename($file->getClientOriginalName());

        // Buat nama fisik file yang unik (UUID) untuk mencegah tabrakan/overwrite pada konkurensi
        $extension = $file->getClientOriginalExtension();
        $uniqueStorageName = Str::uuid()->toString() . ($extension ? '.' . strtolower($extension) : '');
        $storageDir = 'attachments/tasks/' . $task->id;

        // Simpan file ke disk private ('local' -> storage/app)
        $path = $file->storeAs($storageDir, $uniqueStorageName, 'local');

        $task->attachments()->create([
            'user_id' => $request->user()->id,
            'filename' => $sanitizedFilename,
            'path' => $path,
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('success', 'Lampiran berhasil diunggah.');
    }

    /**
     * Download lampiran file.
     */
    public function download(TaskAttachment $attachment): StreamedResponse
    {
        $this->authorize('download', $attachment);

        if (!Storage::disk('local')->exists($attachment->path)) {
            abort(404, 'Berkas tidak ditemukan.');
        }

        return Storage::disk('local')->download(
            $attachment->path,
            $attachment->filename,
            ['Content-Type' => $attachment->mime_type ?? 'application/octet-stream']
        );
    }

    /**
     * Stream inline preview (misal untuk gambar).
     */
    public function preview(TaskAttachment $attachment)
    {
        $this->authorize('download', $attachment);

        if (!Storage::disk('local')->exists($attachment->path)) {
            abort(404, 'Berkas tidak ditemukan.');
        }

        return Storage::disk('local')->response(
            $attachment->path,
            $attachment->filename,
            [
                'Content-Type' => $attachment->mime_type ?? 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . addslashes($attachment->filename) . '"',
            ]
        );
    }

    /**
     * Hapus lampiran file.
     */
    public function destroy(TaskAttachment $attachment): RedirectResponse
    {
        $this->authorize('delete', $attachment);

        if (Storage::disk('local')->exists($attachment->path)) {
            Storage::disk('local')->delete($attachment->path);
        }

        $attachment->delete();

        return back()->with('success', 'Lampiran berhasil dihapus.');
    }
}
