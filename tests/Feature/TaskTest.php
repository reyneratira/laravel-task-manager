<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
    }

    // ─── 1. Admin can view all tasks list ──────────────────────────────────

    public function test_admin_can_view_all_tasks_list(): void
    {
        // Buat tugas milik user lain
        $otherUser = User::factory()->create();
        Task::factory()->assignedTo($this->user)->createdBy($this->admin)->count(2)->create();
        Task::factory()->assignedTo($otherUser)->createdBy($this->admin)->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.tasks.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.tasks.index');
        $response->assertViewHas('tasks');

        // Admin harus bisa lihat semua 5 tugas
        $tasks = $response->viewData('tasks');
        $this->assertEquals(5, $tasks->total());
    }

    // ─── 2. Admin can create a task ────────────────────────────────────────

    public function test_admin_can_create_a_task(): void
    {
        $taskData = [
            'title' => 'Tugas baru dari admin',
            'description' => 'Deskripsi tugas baru.',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::High->value,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'assigned_to' => $this->user->id,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.tasks.store'), $taskData);

        $response->assertRedirect(route('admin.tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title' => 'Tugas baru dari admin',
            'created_by' => $this->admin->id,
            'assigned_to' => $this->user->id,
        ]);
    }

    // ─── 3. Admin can update any task ──────────────────────────────────────

    public function test_admin_can_update_any_task(): void
    {
        $task = Task::factory()
            ->assignedTo($this->user)
            ->createdBy($this->admin)
            ->create(['title' => 'Judul lama']);

        $response = $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), [
            'title' => 'Judul baru',
            'status' => TaskStatus::InProgress->value,
            'priority' => TaskPriority::Medium->value,
        ]);

        $response->assertRedirect(route('admin.tasks.show', $task));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Judul baru',
            'status' => TaskStatus::InProgress->value,
        ]);
    }

    // ─── 4. Admin can soft-delete a task ───────────────────────────────────

    public function test_admin_can_soft_delete_a_task(): void
    {
        $task = Task::factory()
            ->assignedTo($this->user)
            ->createdBy($this->admin)
            ->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.tasks.destroy', $task));

        $response->assertRedirect(route('admin.tasks.index'));
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    // ─── 5. User can view own tasks list ───────────────────────────────────

    public function test_user_can_view_own_tasks_list(): void
    {
        $otherUser = User::factory()->create();
        Task::factory()->assignedTo($this->user)->createdBy($this->admin)->count(3)->create();
        Task::factory()->assignedTo($otherUser)->createdBy($this->admin)->count(2)->create();

        $response = $this->actingAs($this->user)->get(route('user.tasks.index'));

        $response->assertStatus(200);
        $response->assertViewIs('user.tasks.index');

        // User hanya lihat tugasnya sendiri (3 tugas)
        $tasks = $response->viewData('tasks');
        $this->assertEquals(3, $tasks->total());
    }

    // ─── 6. User can view own task detail ──────────────────────────────────

    public function test_user_can_view_own_task_detail(): void
    {
        $task = Task::factory()
            ->assignedTo($this->user)
            ->createdBy($this->admin)
            ->create();

        $response = $this->actingAs($this->user)->get(route('user.tasks.show', $task));

        $response->assertStatus(200);
        $response->assertViewIs('user.tasks.show');
        $response->assertViewHas('task');
    }

    // ─── 7. User can update own task status ────────────────────────────────

    public function test_user_can_update_own_task_status(): void
    {
        $task = Task::factory()
            ->assignedTo($this->user)
            ->createdBy($this->admin)
            ->pending()
            ->create();

        $response = $this->actingAs($this->user)->patch(
            route('user.tasks.update-status', $task),
            ['status' => 'in_progress']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::InProgress->value,
        ]);
    }

    // ─── 8. User cannot access admin task routes ───────────────────────────

    public function test_user_cannot_access_admin_task_routes(): void
    {
        $task = Task::factory()
            ->assignedTo($this->user)
            ->createdBy($this->admin)
            ->create();

        // User tidak bisa akses halaman admin list tugas
        $this->actingAs($this->user)->get(route('admin.tasks.index'))
            ->assertStatus(403);

        // User tidak bisa buat tugas baru via admin route
        $this->actingAs($this->user)->post(route('admin.tasks.store'), [
            'title' => 'Tugas ilegal',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::Low->value,
        ])->assertStatus(403);

        // User tidak bisa hapus tugas
        $this->actingAs($this->user)->delete(route('admin.tasks.destroy', $task))
            ->assertStatus(403);
    }
}
