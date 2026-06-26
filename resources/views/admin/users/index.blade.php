@extends('layouts.app')
@section('title', 'Kelola User — Admin')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Kelola User</h1>
    </div>

    {{-- Filter bar --}}
    <form method="GET" class="flex gap-3 mb-6 flex-wrap">
        <input name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56">

        <select name="role" class="border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-sm">
            <option value="">Semua Role</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
        </select>

        <button class="bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 px-2 py-2">Reset</a>
    </form>

    {{-- User table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Nama</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Role</th>
                    <th class="text-center px-4 py-3">Tugas Ditugaskan</th>
                    <th class="text-center px-4 py-3">Tugas Dibuat</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $user->isAdmin() ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $user->isAdmin() ? 'Admin' : 'User' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $user->assigned_tasks_count }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $user->created_tasks_count }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('admin.users.stats', $user) }}"
                                class="text-blue-600 hover:underline text-xs">Statistik</a>

                            @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.toggle-role', $user) }}" class="inline"
                                    onsubmit="return confirm('Ubah role {{ $user->name }}?')">
                                    @csrf @method('PATCH')
                                    <button class="text-yellow-600 hover:underline text-xs">
                                        {{ $user->isAdmin() ? 'Jadikan User' : 'Jadikan Admin' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                            Belum ada user.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
