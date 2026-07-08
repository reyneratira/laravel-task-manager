<?php

use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\User\TaskController as UserTaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ─── Guest ───────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));

// Autentikasi dihandle Laravel Breeze (php artisan breeze:install)
require __DIR__ . '/auth.php';

// ─── Authenticated ────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard (auto-redirect ke tampilan admin/user)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // ─── Notifikasi ───────────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');

    // ─── Admin only ───────────────────────────────────────────────────────
    Route::middleware('admin')          // App\Http\Middleware\EnsureAdmin
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            // Dashboard admin
            Route::get('/dashboard', [DashboardController::class, 'index'])
                ->name('dashboard');

            // Manajemen Tugas (full CRUD)
            Route::get('tasks/export', [AdminTaskController::class, 'export'])
                ->name('tasks.export');
            Route::get('tasks/report-pdf', [AdminTaskController::class, 'reportPdf'])
                ->name('tasks.report-pdf');
            Route::resource('tasks', AdminTaskController::class);
            Route::patch('tasks/{id}/restore', [AdminTaskController::class, 'restore'])
                ->name('tasks.restore');

            // Manajemen User
            Route::get('users', [AdminUserController::class, 'index'])
                ->name('users.index');
            Route::patch('users/{user}/toggle-role', [AdminUserController::class, 'toggleRole'])
                ->name('users.toggle-role');
            Route::get('users/{user}/stats', [AdminUserController::class, 'stats'])
                ->name('users.stats');
        });

    // ─── Regular user ─────────────────────────────────────────────────────
    Route::prefix('my')
        ->name('user.')
        ->group(function () {

            // Lihat daftar tugas sendiri
            Route::get('tasks', [UserTaskController::class, 'index'])
                ->name('tasks.index');

            // Detail tugas
            Route::get('tasks/{task}', [UserTaskController::class, 'show'])
                ->name('tasks.show');

            // Update status saja (user tidak bisa edit title/deskripsi)
            Route::patch('tasks/{task}/status', [UserTaskController::class, 'updateStatus'])
                ->name('tasks.update-status');
        });
});