@props(['task'])

<div class="mt-6 bg-white rounded-xl border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
            Lampiran File ({{ $task->attachments->count() }})
        </h2>
    </div>

    {{-- Form Upload --}}
    @can('create', [App\Models\TaskAttachment::class, $task])
        <form action="{{ route('tasks.attachments.store', $task) }}" method="POST" enctype="multipart/form-data"
            class="mb-6 p-4 border border-dashed border-gray-300 rounded-lg bg-gray-50">
            @csrf
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <input type="file" name="file" required
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg whitespace-nowrap transition-colors flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Unggah
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-2">Format: PDF, JPG, PNG, DOCX, XLSX, ZIP (Maks. 10MB)</p>

            @error('file')
                <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>
            @enderror
        </form>
    @endcan

    {{-- List Lampiran --}}
    @if($task->attachments->isEmpty())
        <p class="text-sm text-gray-400 italic">Belum ada lampiran file pada tugas ini.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($task->attachments as $attachment)
                <div class="border border-gray-200 rounded-lg p-3 flex flex-col justify-between bg-white hover:shadow-sm transition-shadow">
                    <div>
                        {{-- Preview jika Gambar --}}
                        @if($attachment->is_image)
                            <div class="mb-3 rounded overflow-hidden bg-gray-100 h-32 flex items-center justify-center relative group">
                                <img src="{{ route('attachments.preview', $attachment) }}" alt="{{ $attachment->filename }}"
                                    class="object-cover w-full h-full" />
                                <a href="{{ route('attachments.preview', $attachment) }}" target="_blank"
                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-medium gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Preview
                                </a>
                            </div>
                        @else
                            {{-- Icon untuk non-gambar --}}
                            <div class="mb-3 rounded bg-blue-50 text-blue-600 h-24 flex items-center justify-center">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        @endif

                        <h4 class="text-xs font-semibold text-gray-800 truncate" title="{{ $attachment->filename }}">
                            {{ $attachment->filename }}
                        </h4>
                        <div class="flex items-center justify-between text-[11px] text-gray-400 mt-1">
                            <span>{{ $attachment->formatted_size }}</span>
                            <span>{{ $attachment->uploader?->name ?? 'Anonim' }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between mt-3 pt-2 border-t border-gray-100 text-xs">
                        <a href="{{ route('attachments.download', $attachment) }}"
                            class="text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Unduh
                        </a>

                        @can('delete', $attachment)
                            <form action="{{ route('attachments.destroy', $attachment) }}" method="POST"
                                onsubmit="return confirm('Hapus lampiran ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-medium">
                                    Hapus
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
