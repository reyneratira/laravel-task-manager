<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Semua route di file ini mendapat prefix /api secara otomatis.
| Prefix /v1 ditambahkan via group di bawah.
|
*/

Route::prefix('v1')->group(function () {

    // ─── Public (tanpa auth) ─────────────────────────────────────────────
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    // ─── Authenticated (Sanctum) ─────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);

        // Tasks CRUD
        Route::get('tasks/export', [TaskController::class, 'export']);
        Route::apiResource('tasks', TaskController::class);
    });
});
