<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@taskmanager.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Sample Users
        $users = collect([
            ['name' => 'Budi Santoso', 'email' => 'budi@taskmanager.test'],
            ['name' => 'Siti Rahayu', 'email' => 'siti@taskmanager.test'],
            ['name' => 'Ahmad Fauzi', 'email' => 'ahmad@taskmanager.test'],
        ])->map(fn($u) => User::create([
                ...$u,
                'password' => Hash::make('password'),
                'role' => 'user',
            ]));

        // Sample Tasks
        $sampleTasks = [
            [
                'title' => 'Setup repository Git untuk proyek baru',
                'description' => 'Buat repository di GitHub, setup branch convention, dan dokumentasikan di README.',
                'status' => TaskStatus::Done,
                'priority' => TaskPriority::High,
                'due_date' => now()->subDays(3),
                'assigned_to' => $users[0]->id,
            ],
            [
                'title' => 'Desain tampilan halaman login',
                'description' => 'Buat mockup di Figma lalu implementasikan dengan Tailwind CSS.',
                'status' => TaskStatus::InProgress,
                'priority' => TaskPriority::High,
                'due_date' => now()->addDays(2),
                'assigned_to' => $users[1]->id,
            ],
            [
                'title' => 'Tulis unit test untuk modul autentikasi',
                'description' => 'Coverage minimal 80% untuk fitur login, register, dan reset password.',
                'status' => TaskStatus::Pending,
                'priority' => TaskPriority::Medium,
                'due_date' => now()->addDays(7),
                'assigned_to' => $users[2]->id,
            ],
            [
                'title' => 'Optimasi query laporan bulanan',
                'description' => 'Query saat ini timeout untuk data > 10.000 baris. Tambahkan index dan eager loading.',
                'status' => TaskStatus::Pending,
                'priority' => TaskPriority::High,
                'due_date' => now()->addDays(1),
                'assigned_to' => $users[0]->id,
            ],
            [
                'title' => 'Update dokumentasi API',
                'description' => 'Perbarui Postman collection dan README sesuai endpoint terbaru.',
                'status' => TaskStatus::Pending,
                'priority' => TaskPriority::Low,
                'due_date' => now()->addDays(14),
                'assigned_to' => $users[1]->id,
            ],
        ];

        foreach ($sampleTasks as $taskData) {
            Task::create([
                ...$taskData,
                'created_by' => $admin->id,
            ]);
        }

        $this->command->info('✅ Seed selesai!');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@taskmanager.test', 'password'],
                ['User', 'budi@taskmanager.test', 'password'],
                ['User', 'siti@taskmanager.test', 'password'],
                ['User', 'ahmad@taskmanager.test', 'password'],
            ]
        );
    }
}
