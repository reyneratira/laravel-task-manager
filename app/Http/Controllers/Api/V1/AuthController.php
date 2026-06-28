<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Daftar user baru. Role selalu 'user'.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        $abilities = $this->abilitiesForRole('user');
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'token' => $token,
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Login dan dapatkan token.
     * Token lama dengan device_name yang sama akan dihapus otomatis.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $deviceName = $request->input('device_name', 'auth_token');

        // Hapus token lama dengan device name yang sama
        $user->tokens()->where('name', $deviceName)->delete();

        $abilities = $this->abilitiesForRole($user->role);
        $token = $user->createToken($deviceName, $abilities)->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Data profil user yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * Hapus token yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Hapus semua token dari semua perangkat.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout dari semua perangkat berhasil.',
        ]);
    }

    /**
     * Token abilities berdasarkan role user.
     *
     * @return string[]
     */
    private function abilitiesForRole(string $role): array
    {
        if ($role === 'admin') {
            return [
                'tasks:read',
                'tasks:create',
                'tasks:update',
                'tasks:update-status',
                'tasks:delete',
                'users:read',
                'users:manage',
            ];
        }

        return [
            'tasks:read',
            'tasks:update-status',
        ];
    }
}
