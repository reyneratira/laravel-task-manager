<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Ambil notifikasi terbaru untuk user yang login.
     * Digunakan oleh Alpine.js untuk fetch notifikasi via AJAX.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => class_basename($n->type),
                'data' => $n->data,
                'created_at' => $n->created_at->diffForHumans(),
                'created_at_iso' => $n->created_at->toIso8601String(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai dibaca.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notifikasi ditandai sebagai dibaca.',
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai semua notifikasi sebagai dibaca.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'Semua notifikasi ditandai sebagai dibaca.',
            'unread_count' => 0,
        ]);
    }
}
