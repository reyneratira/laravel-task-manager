@extends('layouts.app')
@section('title', $task->title . ' — Detail Tugas')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">{{ $task->title }}</h1>
            <p class="text-sm text-gray-500 mt-1">Dibuat oleh {{ $task->creator?->name ?? '—' }} pada {{ $task->created_at->format('d M Y H:i') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.tasks.edit', $task) }}"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
                Edit
            </a>
            <a href="{{ route('admin.tasks.index') }}" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">
                ← Kembali
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Status --}}
            <div>
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Status</span>
                <span class="px-2.5 py-1 rounded-full text-xs font-medium
                    bg-{{ $task->status->color() }}-100 text-{{ $task->status->color() }}-700">
                    {{ $task->status->label() }}
                </span>
                @if($task->is_overdue)
                    <span class="ml-2 text-xs text-red-500 font-medium">TERLAMBAT</span>
                @endif
            </div>

            {{-- Prioritas --}}
            <div>
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Prioritas</span>
                <span class="px-2.5 py-1 rounded-full text-xs font-medium
                    bg-{{ $task->priority->color() }}-100 text-{{ $task->priority->color() }}-700">
                    {{ $task->priority->label() }}
                </span>
            </div>

            {{-- Assignee --}}
            <div>
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Ditugaskan ke</span>
                <span class="text-sm text-gray-800">{{ $task->assignee?->name ?? '— Belum ditugaskan —' }}</span>
            </div>

            {{-- Deadline --}}
            <div>
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Deadline</span>
                <span class="text-sm text-gray-800">{{ $task->due_date?->format('d M Y') ?? '— Tidak ada —' }}</span>
            </div>
        </div>

        {{-- Deskripsi --}}
        @if($task->description)
            <div class="mt-6 pt-6 border-t border-gray-100">
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-2">Deskripsi</span>
                <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $task->description }}</div>
            </div>
        @endif

        {{-- Timestamps --}}
        <div class="mt-6 pt-6 border-t border-gray-100 flex gap-8 text-xs text-gray-400">
            <span>Dibuat: {{ $task->created_at->format('d M Y H:i') }}</span>
            <span>Diperbarui: {{ $task->updated_at->format('d M Y H:i') }}</span>
            @if($task->deleted_at)
                <span class="text-red-400">Dihapus: {{ $task->deleted_at->format('d M Y H:i') }}</span>
            @endif
        </div>
    </div>

    {{-- Hapus --}}
    <div class="mt-4">
        <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}"
            onsubmit="return confirm('Hapus tugas ini?')">
            @csrf @method('DELETE')
            <button class="text-red-500 hover:text-red-700 text-sm">Hapus Tugas</button>
        </form>
    </div>
@endsection
