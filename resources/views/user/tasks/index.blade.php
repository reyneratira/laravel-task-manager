@extends('layouts.app')
@section('title', 'Tugas Saya')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Tugas Saya</h1>
    </div>

    {{-- Filter bar --}}
    <form method="GET" class="flex gap-3 mb-6 flex-wrap">
        <select name="status" class="border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-sm">
            <option value="">Semua Status</option>
            @foreach($statuses as $s)
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

        <button class="bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
        <a href="{{ route('user.tasks.index') }}" class="text-sm text-gray-500 px-2 py-2">Reset</a>
    </form>

    {{-- Task table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Tugas</th>
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
                            <a href="{{ route('user.tasks.show', $task) }}"
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
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('user.tasks.show', $task) }}"
                                class="text-blue-600 hover:underline text-xs">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                            Belum ada tugas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tasks->links() }}</div>
@endsection
