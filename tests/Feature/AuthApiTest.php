<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── 1. User can register via API ──────────────────────────────────────

    public function test_user_can_register_via_api(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'role', 'is_admin'],
            ])
            ->assertJsonPath('user.role', 'user')
            ->assertJsonPath('user.is_admin', false);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'user',
        ]);
    }

    // ─── 2. User can login via API and receive token ───────────────────────

    public function test_user_can_login_via_api_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'login@example.com',
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'role'],
            ])
            ->assertJsonPath('message', 'Login berhasil.');
    }

    // ─── 3. Authenticated user can access /auth/me ─────────────────────────

    public function test_authenticated_user_can_access_me(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['tasks:read', 'tasks:update-status']);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    }

    // ─── 4. User can logout (token revoked) ────────────────────────────────

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['tasks:read'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Logout berhasil.');

        // Token harus sudah dihapus
        $this->assertCount(0, $user->fresh()->tokens);
    }

    // ─── 5. User can logout from all devices ───────────────────────────────

    public function test_user_can_logout_from_all_devices(): void
    {
        $user = User::factory()->create();

        // Buat beberapa token (simulasi multi-device)
        $user->createToken('device_1', ['tasks:read']);
        $user->createToken('device_2', ['tasks:read']);
        $token = $user->createToken('device_3', ['tasks:read'])->plainTextToken;

        $this->assertCount(3, $user->fresh()->tokens);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/auth/logout-all');

        $response->assertOk()
            ->assertJsonPath('message', 'Logout dari semua perangkat berhasil.');

        $this->assertCount(0, $user->fresh()->tokens);
    }

    // ─── 6. Admin token has full abilities (CRUD tasks) ────────────────────

    public function test_admin_token_has_full_abilities(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        Sanctum::actingAs($admin, [
            'tasks:read', 'tasks:create', 'tasks:update',
            'tasks:update-status', 'tasks:delete',
            'users:read', 'users:manage',
        ]);

        // Admin bisa buat tugas
        $response = $this->postJson('/api/v1/tasks', [
            'title' => 'Tugas dari admin via API',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::High->value,
            'assigned_to' => $user->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Tugas berhasil dibuat.')
            ->assertJsonPath('data.title', 'Tugas dari admin via API');

        $taskId = $response->json('data.id');

        // Admin bisa lihat detail
        $this->getJson("/api/v1/tasks/{$taskId}")
            ->assertOk()
            ->assertJsonPath('data.id', $taskId);

        // Admin bisa update
        $this->putJson("/api/v1/tasks/{$taskId}", [
            'title' => 'Judul diperbarui',
            'status' => TaskStatus::InProgress->value,
            'priority' => TaskPriority::Medium->value,
        ])->assertOk()
            ->assertJsonPath('data.title', 'Judul diperbarui');

        // Admin bisa hapus
        $this->deleteJson("/api/v1/tasks/{$taskId}")
            ->assertOk()
            ->assertJsonPath('message', 'Tugas berhasil dihapus.');

        $this->assertSoftDeleted('tasks', ['id' => $taskId]);
    }

    // ─── 7. User token can read tasks ──────────────────────────────────────

    public function test_user_token_can_read_tasks(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        Task::factory()->assignedTo($user)->createdBy($admin)->count(3)->create();

        // Tugas milik user lain (tidak boleh terlihat)
        $otherUser = User::factory()->create();
        Task::factory()->assignedTo($otherUser)->createdBy($admin)->count(2)->create();

        Sanctum::actingAs($user, ['tasks:read', 'tasks:update-status']);

        $response = $this->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // ─── 8. User token can update own task status ──────────────────────────

    public function test_user_token_can_update_own_task_status(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $task = Task::factory()
            ->assignedTo($user)
            ->createdBy($admin)
            ->pending()
            ->create();

        Sanctum::actingAs($user, ['tasks:read', 'tasks:update-status']);

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'status' => TaskStatus::InProgress->value,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status.value', 'in_progress');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::InProgress->value,
        ]);
    }

    // ─── 9. User token cannot create/delete tasks (403) ────────────────────

    public function test_user_token_cannot_create_or_delete_tasks(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $task = Task::factory()
            ->assignedTo($user)
            ->createdBy($admin)
            ->create();

        Sanctum::actingAs($user, ['tasks:read', 'tasks:update-status']);

        // User tidak bisa buat tugas
        $this->postJson('/api/v1/tasks', [
            'title' => 'Tugas ilegal',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::Low->value,
        ])->assertStatus(403);

        // User tidak bisa hapus tugas
        $this->deleteJson("/api/v1/tasks/{$task->id}")
            ->assertStatus(403);
    }
}
