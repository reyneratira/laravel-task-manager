@extends('layouts.app')
@section('title', 'Statistik ' . $user->name . ' — Admin')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke Kelola User</a>
        <h1 class="text-2xl font-semibold text-gray-800 mt-2">Statistik: {{ $user->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $user->email }} · {{ $user->isAdmin() ? 'Admin' : 'User' }}</p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Menunggu</p>
            <p class="text-2xl font-bold text-gray-500 mt-1">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Dikerjakan</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['in_progress'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Selesai</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['done'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Terlambat</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['overdue'] }}</p>
        </div>
    </div>

    {{-- Recent Tasks --}}
    <h2 class="text-lg font-semibold text-gray-800 mb-3">Tugas Terbaru</h2>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Tugas</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Prioritas</th>
                    <th class="text-left px-4 py-3">Deadline</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($recentTasks as $task)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.tasks.show', $task) }}"
                                class="font-medium text-gray-800 hover:text-blue-600">
                                {{ $task->title }}
                            </a>
                            @if($task->is_overdue)
                                <span class="ml-2 text-xs text-red-500 font-medium">TERLAMBAT</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                bg-{{ $task->status->color() }}-100 text-{{ $task->status->color() }}-700">
                                {{ $task->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                bg-{{ $task->priority->color() }}-100 text-{{ $task->priority->color() }}-700">
                                {{ $task->priority->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $task->due_date?->format('d M Y') ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                            Belum ada tugas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
