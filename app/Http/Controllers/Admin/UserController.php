<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /** Daftar semua user */
    public function index(Request $request): View
    {
        $users = User::withCount(['assignedTasks', 'createdTasks'])
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('email', 'like', "%{$request->search}%"))
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /** Ubah role user (admin <-> user) */
    public function toggleRole(User $user): RedirectResponse
    {
        // Jangan ubah role diri sendiri
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat mengubah role diri sendiri.');
        }

        $user->update([
            'role' => $user->isAdmin() ? 'user' : 'admin',
        ]);

        return back()->with('success', "Role {$user->name} berhasil diubah.");
    }

    /** Statistik tugas per user */
    public function stats(User $user): View
    {
        $stats = [
            'total' => $user->assignedTasks()->count(),
            'pending' => $user->assignedTasks()->where('status', TaskStatus::Pending)->count(),
            'in_progress' => $user->assignedTasks()->where('status', TaskStatus::InProgress)->count(),
            'done' => $user->assignedTasks()->where('status', TaskStatus::Done)->count(),
            'overdue' => $user->assignedTasks()->overdue()->count(),
        ];

        $recentTasks = $user->assignedTasks()->latest()->take(5)->get();

        return view('admin.users.stats', compact('user', 'stats', 'recentTasks'));
    }
}
