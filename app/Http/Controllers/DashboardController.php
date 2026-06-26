<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\View\View;

use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        return $this->userDashboard();
    }

    private function adminDashboard(): RedirectResponse
    {
        return redirect()->route('admin.tasks.index');
    }

    private function userDashboard(): View
    {
        $user = auth()->user();

        $stats = [
            'total' => $user->assignedTasks()->count(),
            'pending' => $user->assignedTasks()->where('status', 'pending')->count(),
            'in_progress' => $user->assignedTasks()->where('status', 'in_progress')->count(),
            'done' => $user->assignedTasks()->where('status', 'done')->count(),
            'overdue' => $user->assignedTasks()->overdue()->count(),
        ];

        $myTasks = $user->assignedTasks()
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderBy('due_date')
            ->get();

        return view('user.dashboard', compact('stats', 'myTasks'));
    }
}