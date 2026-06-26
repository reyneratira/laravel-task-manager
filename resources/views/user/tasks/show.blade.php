@extends('layouts.app')
@section('title', $task->title . ' — Tugas Saya')

@section('content')
    <div class="mb-6">
        <a href="{{ route('user.tasks.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke Tugas Saya</a>
        <h1 class="text-2xl font-semibold text-gray-800 mt-2">{{ $task->title }}</h1>
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

            {{-- Deadline --}}
            <div>
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Deadline</span>
                <span class="text-sm text-gray-800">{{ $task->due_date?->format('d M Y') ?? '— Tidak ada —' }}</span>
            </div>

            {{-- Dibuat --}}
            <div>
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Dibuat pada</span>
                <span class="text-sm text-gray-800">{{ $task->created_at->format('d M Y H:i') }}</span>
            </div>
        </div>

        {{-- Deskripsi --}}
        @if($task->description)
            <div class="mt-6 pt-6 border-t border-gray-100">
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-2">Deskripsi</span>
                <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $task->description }}</div>
            </div>
        @endif
    </div>

    {{-- Update Status Form --}}
    @if(!$task->status->isClosed())
        <div class="mt-6 bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-3">Perbarui Status</h2>
            <form method="POST" action="{{ route('user.tasks.update-status', $task) }}" class="flex items-center gap-3">
                @csrf @method('PATCH')
                <select name="status" class="border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-sm">
                    @foreach(App\Enums\TaskStatus::cases() as $s)
                        @if($s->value !== 'cancelled')
                            <option value="{{ $s->value }}" {{ $task->status->value === $s->value ? 'selected' : '' }}>
                                {{ $s->label() }}
                            </option>
                        @endif
                    @endforeach
                </select>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
                    Simpan
                </button>
            </form>
            @error('status')
                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
            @enderror
        </div>
    @endif
@endsection
