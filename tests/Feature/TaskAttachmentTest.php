<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected User $otherUser;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
        $this->otherUser = User::factory()->create(['role' => 'user']);

        $this->task = Task::factory()->create([
            'created_by' => $this->admin->id,
            'assigned_to' => $this->user->id,
        ]);
    }

    /** Test: Admin dapat membuat tugas sekaligus mengunggah lampiran file. */
    public function test_admin_can_create_task_with_attachments(): void
    {
        $file1 = UploadedFile::fake()->create('modul_a.pdf', 300, 'application/pdf');
        $file2 = UploadedFile::fake()->create('preview.png', 500, 'image/png');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Tugas Baru dengan Lampiran',
                'description' => 'Deskripsi tugas baru',
                'status' => 'pending',
                'priority' => 'high',
                'assigned_to' => $this->user->id,
                'attachments' => [$file1, $file2],
            ]);

        $response->assertRedirect(route('admin.tasks.index'));

        $this->assertDatabaseHas('tasks', ['title' => 'Tugas Baru dengan Lampiran']);
        $newTask = Task::where('title', 'Tugas Baru dengan Lampiran')->first();

        $this->assertEquals(2, $newTask->attachments()->count());
        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $newTask->id,
            'filename' => 'modul_a.pdf',
        ]);
    }

    /** Test 1: Admin dan Assignee berhasil upload lampiran valid. */

    public function test_admin_and_assignee_can_upload_valid_attachment(): void
    {
        $file = UploadedFile::fake()->create('dokumen.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->post(route('tasks.attachments.store', $this->task), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'filename' => 'dokumen.pdf',
        ]);

        $attachment = TaskAttachment::first();
        Storage::disk('local')->assertExists($attachment->path);
    }

    /** Test 2: Sanitasi Input pada nama file berbahaya (path traversal, XSS, script). */
    public function test_input_sanitization_for_malicious_filenames(): void
    {
        $maliciousFiles = [
            '../../../etc/passwd.png' => 'etc_passwd.png',
            '<script>alert("xss")</script>.pdf' => 'alert_xss_.pdf',
            'test_file\0null.docx' => 'test_file_0null.docx',
        ];

        foreach ($maliciousFiles as $rawName => $expectedPattern) {
            $file = UploadedFile::fake()->create($rawName, 100, 'image/png');

            $this->actingAs($this->admin)
                ->post(route('tasks.attachments.store', $this->task), [
                    'file' => $file,
                ]);

            $attachment = TaskAttachment::latest('id')->first();

            // Pastikan nama file di database tidak mengandung path traversal (../) atau HTML tags
            $this->assertStringNotContainsString('../', $attachment->filename);
            $this->assertStringNotContainsString('<script>', $attachment->filename);
            $this->assertStringNotContainsString("\0", $attachment->filename);

            // File di storage fisik menggunakan UUID aman
            $this->assertStringStartsWith('attachments/tasks/' . $this->task->id . '/', $attachment->path);
            Storage::disk('local')->assertExists($attachment->path);
        }
    }

    /** Test 3: Pengujian upload bersamaan (konkurensi) oleh 2 user dengan nama file yang sama persis. */
    public function test_concurrent_uploads_by_two_users_do_not_overwrite_files(): void
    {
        // User A dan User B mengunggah file dengan nama asli yang sama di tugas yang sama
        $fileA = UploadedFile::fake()->create('laporan.pdf', 200, 'application/pdf');
        $fileB = UploadedFile::fake()->create('laporan.pdf', 300, 'application/pdf');

        // User A upload
        $this->actingAs($this->admin)
            ->post(route('tasks.attachments.store', $this->task), ['file' => $fileA]);

        // User B (Assignee) upload di waktu bersamaan
        $this->actingAs($this->user)
            ->post(route('tasks.attachments.store', $this->task), ['file' => $fileB]);

        // Pastikan ada 2 record di database
        $this->assertEquals(2, TaskAttachment::where('task_id', $this->task->id)->count());

        $attachments = TaskAttachment::where('task_id', $this->task->id)->get();
        $attachmentA = $attachments->firstWhere('user_id', $this->admin->id);
        $attachmentB = $attachments->firstWhere('user_id', $this->user->id);

        // Path penyimpanan fisik HARUS berbeda (UUID unik)
        $this->assertNotEquals($attachmentA->path, $attachmentB->path);

        // Kedua file tersimpan dengan aman di disk tanpa timpa-menimpa
        Storage::disk('local')->assertExists($attachmentA->path);
        Storage::disk('local')->assertExists($attachmentB->path);
    }

    /** Test 4: Gagal upload jika file lebih besar dari 10MB atau format tidak diizinkan. */
    public function test_upload_fails_for_invalid_file_type_or_oversized_file(): void
    {
        // Oversized file (> 10MB)
        $largeFile = UploadedFile::fake()->create('besar.pdf', 10241, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->post(route('tasks.attachments.store', $this->task), ['file' => $largeFile]);

        $response->assertSessionHasErrors('file');

        // Format tidak terdaftar (.exe)
        $exeFile = UploadedFile::fake()->create('program.exe', 500, 'application/x-msdownload');

        $responseExe = $this->actingAs($this->user)
            ->post(route('tasks.attachments.store', $this->task), ['file' => $exeFile]);

        $responseExe->assertSessionHasErrors('file');
    }

    /** Test 5: Otorisasi download dan delete. */
    public function test_download_and_delete_authorization(): void
    {
        $file = UploadedFile::fake()->create('nota.png', 100, 'image/png');
        $this->actingAs($this->user)
            ->post(route('tasks.attachments.store', $this->task), ['file' => $file]);

        $attachment = TaskAttachment::first();

        // Assignee dan Admin bisa download
        $this->actingAs($this->user)
            ->get(route('attachments.download', $attachment))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('attachments.download', $attachment))
            ->assertOk();

        // User lain yang bukan assignee ditolak
        $this->actingAs($this->otherUser)
            ->get(route('attachments.download', $attachment))
            ->assertForbidden();

        // User lain ditolak menghapus
        $this->actingAs($this->otherUser)
            ->delete(route('attachments.destroy', $attachment))
            ->assertForbidden();

        // Assignee (pengunggah) bisa menghapus file miliknya
        $this->actingAs($this->user)
            ->delete(route('attachments.destroy', $attachment))
            ->assertRedirect();

        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->path);
    }
}
