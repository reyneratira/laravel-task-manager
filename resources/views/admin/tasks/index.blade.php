@extends('layouts.app')
@section('title', 'Semua Tugas — Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Semua Tugas</h1>
        <a href="{{ route('admin.tasks.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
            + Buat Tugas
        </a>
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