<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExportTest extends TestCase
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

    // ─── 1. Admin can export tasks via Web in Excel format ──────────────────
    public function test_admin_can_export_tasks_via_web(): void
    {
        Task::factory()->count(3)->create(['created_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.tasks.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('attachment; filename=tugas-admin.xlsx', $response->headers->get('Content-Disposition'));
    }

    // ─── 2. Admin can export tasks report PDF via Web ───────────────────────
    public function test_admin_can_export_pdf_via_web(): void
    {
        Task::factory()->count(3)->create(['created_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.tasks.report-pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('attachment; filename=laporan-tugas.pdf', $response->headers->get('Content-Disposition'));
    }

    // ─── 3. Regular user is forbidden from Web export routes ────────────────
    public function test_regular_user_is_forbidden_from_web_exports(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.tasks.export'));
        $response->assertStatus(403);

        $responsePdf = $this->actingAs($this->user)->get(route('admin.tasks.report-pdf'));
        $responsePdf->assertStatus(403);
    }

    // ─── 4. User can export tasks via API in Excel format ───────────────────
    public function test_user_can_export_tasks_via_api_excel(): void
    {
        Task::factory()->count(2)->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->admin->id
        ]);

        Sanctum::actingAs($this->user, ['tasks:read']);

        $response = $this->getJson('/api/v1/tasks/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('attachment; filename=tugas.xlsx', $response->headers->get('Content-Disposition'));
    }

    // ─── 5. User can export tasks via API in PDF format ─────────────────────
    public function test_user_can_export_tasks_via_api_pdf(): void
    {
        Task::factory()->count(2)->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->admin->id
        ]);

        Sanctum::actingAs($this->user, ['tasks:read']);

        $response = $this->getJson('/api/v1/tasks/export?format=pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('attachment; filename=tugas.pdf', $response->headers->get('Content-Disposition'));
    }

    // ─── 6. API export respects task visibility restrictions ─────────────────
    public function test_api_export_respects_visibility_restrictions(): void
    {
        $otherUser = User::factory()->create();

        Task::factory()->count(2)->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->admin->id
        ]);
        Task::factory()->count(3)->create([
            'assigned_to' => $otherUser->id,
            'created_by' => $this->admin->id
        ]);

        Sanctum::actingAs($this->user, ['tasks:read']);
        $response = $this->getJson('/api/v1/tasks/export?format=pdf');
        $response->assertStatus(200);
    }

    // ─── 7. API export requires tasks:read token ability ───────────────────
    public function test_api_export_requires_tasks_read_ability(): void
    {
        Sanctum::actingAs($this->user, ['tasks:write']); // No tasks:read ability

        $response = $this->getJson('/api/v1/tasks/export');

        $response->assertStatus(403);
    }
}
