<?php

namespace Tests\Feature\Api;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskAttachmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);

        $this->task = Task::factory()->create([
            'created_by' => $this->admin->id,
            'assigned_to' => $this->user->id,
        ]);
    }

    /** Test API Upload attachment. */
    public function test_api_upload_attachment(): void
    {
        Sanctum::actingAs($this->admin, ['tasks:update']);

        $file = UploadedFile::fake()->create('laporan.pdf', 300, 'application/pdf');

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'task_id',
                    'filename',
                    'mime_type',
                    'size',
                    'formatted_size',
                    'is_image',
                    'download_url',
                ],
            ]);

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $this->task->id,
            'filename' => 'laporan.pdf',
        ]);
    }

    /** Test API List attachments. */
    public function test_api_list_attachments(): void
    {
        Sanctum::actingAs($this->user, ['tasks:read']);

        TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'filename' => 'foto.jpg',
            'path' => 'attachments/tasks/' . $this->task->id . '/test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $response = $this->getJson("/api/v1/tasks/{$this->task->id}/attachments");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** Test API Delete attachment. */
    public function test_api_delete_attachment(): void
    {
        Sanctum::actingAs($this->admin, ['tasks:delete']);

        $file = UploadedFile::fake()->create('hapus.pdf', 100, 'application/pdf');
        $path = $file->storeAs('attachments/tasks/' . $this->task->id, 'hapus.pdf', 'local');

        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->admin->id,
            'filename' => 'hapus.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);

        $response = $this->deleteJson("/api/v1/attachments/{$attachment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
    }
}
