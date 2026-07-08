@extends('layouts.app')
@section('title', 'Semua Tugas — Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Semua Tugas</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.tasks.export', request()->all()) }}"
                class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Excel
            </a>
            <a href="{{ route('admin.tasks.report-pdf', request()->all()) }}"
                class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Laporan PDF
            </a>
            <a href="{{ route('admin.tasks.create') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
                + Buat Tugas
            </a>
        </div>
    </div>

    {{-- Filter bar --}}
    <form method="GET" class="flex gap-3 mb-6 flex-wrap">
        <input name="search" value="{{ request('search') }}" placeholder="Cari judul..."
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-48">

        <select name="status" class="border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-sm">
            <option value="">Semua Status</option>
            @foreach(App\Enums\TaskStatus::cases() as $s)
                <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>
                    {{ $s->label() }}
                </option>
            @endforeach
        </select>

        <select name="priority" class="border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-sm">
            <option value="">Semua Prioritas</option>
            @foreach(App\Enums\TaskPriority::cases() as $p)
                <option value="{{ $p->value }}" {{ request('priority') === $p->value ? 'selected' : '' }}>
                    {{ $p->label() }}
                </option>
            @endforeach
        </select>

        <select name="user_id" class="border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-sm">
            <option value="">Semua User</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                    {{ $u->name }}
                </option>
            @endforeach
        </select>

        <button class="bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
        <a href="{{ route('admin.tasks.index') }}" class="text-sm text-gray-500 px-2 py-2">Reset</a>
    </form>

    {{-- Task table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Tugas</th>
                    <th class="text-left px-4 py-3">Assignee</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Prioritas</th>
                    <th class="text-left px-4 py-3">Deadline</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tasks as $task)
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
                        <td class="px-4 py-3 text-gray-600">{{ $task->assignee?->name ?? '—' }}</td>
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
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('admin.tasks.edit', $task) }}"
                                class="text-blue-600 hover:underline text-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}" class="inline"
                                onsubmit="return confirm('Hapus tugas ini?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                            Belum ada tugas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tasks->links() }}</div>
@endsection