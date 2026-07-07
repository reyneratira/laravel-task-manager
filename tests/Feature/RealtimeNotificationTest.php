<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RealtimeNotificationTest extends TestCase
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

    // ─── 1. Notification is created when task status changes ───────────────

    public function test_notification_is_created_when_task_status_changes(): void
    {
        Notification::fake();
        Mail::fake();

        $task = Task::factory()
            ->assignedTo($this->user)
            ->createdBy($this->admin)
            ->pending()
            ->create();

        // Admin changes the status
        $this->actingAs($this->admin);
        $task->update(['status' => TaskStatus::InProgress]);

        // The assignee should be notified
        Notification::assertSentTo($this->user, TaskStatusChanged::class);
    }

    // ─── 2. Status change notification is NOT sent to self ─────────────────

    public function test_status_change_notification_is_not_sent_to_self(): void
    {
        Notification::fake();
        Mail::fake();

        $task = Task::factory()
            ->assignedTo($this->user)
            ->createdBy($this->admin)
            ->pending()
            ->create();

        // User changes their own task status
        $this->actingAs($this->user);
        $task->update(['status' => TaskStatus::InProgress]);

        // The user should NOT receive a status change notification (they made the change)
        Notification::assertNotSentTo($this->user, TaskStatusChanged::class);

        // But the admin/creator SHOULD be notified
        Notification::assertSentTo($this->admin, TaskStatusChanged::class);
    }

    // ─── 3. Admin is notified when user updates status ─────────────────────

    public function test_admin_is_notified_when_user_updates_status(): void
    {
        Notification::fake();
        Mail::fake();

        $task = Task::factory()
            ->assignedTo($this->user)
            ->createdBy($this->admin)
            ->pending()
            ->create();

        $this->actingAs($this->user);
        $task->update(['status' => TaskStatus::Done]);

        Notification::assertSentTo($this->admin, TaskStatusChanged::class, function ($notification) {
            return $notification->task->status === TaskStatus::Done
                && $notification->changedBy->id === $this->user->id;
        });
    }

    // ─── 4. Notification is created when task is assigned ──────────────────

    public function test_notification_is_created_when_task_is_assigned(): void
    {
        Notification::fake();
        Mail::fake();

        $this->actingAs($this->admin);

        Task::factory()
            ->assignedTo($this->user)
            ->createdBy($this->admin)
            ->create();

        Notification::assertSentTo($this->user, TaskAssignedNotification::class);
    }

    // ─── 5. Assignment notification is NOT sent for self-assignment ────────

    public function test_assignment_notification_is_not_sent_for_self_assignment(): void
    {
        Notification::fake();
        Mail::fake();

        $this->actingAs($this->admin);

        // Admin assigns a task to themselves
        Task::factory()
            ->assignedTo($this->admin)
            ->createdBy($this->admin)
            ->create();

        Notification::assertNotSentTo($this->admin, TaskAssignedNotification::class);
    }

    // ─── 6. User can fetch unread notifications via AJAX ───────────────────

    public function test_user_can_fetch_unread_notifications(): void
    {
        Mail::fake();

        // Create a notification for the user
        $task = Task::factory()->assignedTo($this->user)->createdBy($this->admin)->create();
        $this->user->notify(new TaskAssignedNotification($task, $this->admin));

        $response = $this->actingAs($this->user)->getJson(route('notifications.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'notifications' => [
                    '*' => ['id', 'type', 'data', 'created_at'],
                ],
                'unread_count',
            ])
            ->assertJsonPath('unread_count', 1);
    }

    // ─── 7. User can mark notification as read ─────────────────────────────

    public function test_user_can_mark_notification_as_read(): void
    {
        Mail::fake();

        $task = Task::factory()->assignedTo($this->user)->createdBy($this->admin)->create();
        $this->user->notify(new TaskAssignedNotification($task, $this->admin));

        $notification = $this->user->unreadNotifications()->first();

        $response = $this->actingAs($this->user)
            ->patchJson(route('notifications.read', $notification->id));

        $response->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    // ─── 8. User can mark all notifications as read ────────────────────────

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        Mail::fake();

        // Create multiple notifications
        for ($i = 0; $i < 3; $i++) {
            $task = Task::factory()->assignedTo($this->user)->createdBy($this->admin)->create();
            $this->user->notify(new TaskAssignedNotification($task, $this->admin));
        }

        $this->assertEquals(3, $this->user->unreadNotifications()->count());

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.read-all'));

        $response->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertEquals(0, $this->user->fresh()->unreadNotifications()->count());
    }
}
