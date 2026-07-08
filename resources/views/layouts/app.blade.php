<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Task Manager')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen font-sans antialiased">

    {{-- Navbar --}}
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">

                {{-- Left: Logo + Nav links --}}
                <div class="flex items-center gap-8">
                    {{-- Logo --}}
                    <a href="{{ route('dashboard') }}" class="font-bold text-lg text-gray-800 shrink-0">
                        📋 Task Manager
                    </a>

                    @auth
                        {{-- Nav links --}}
                        <div class="hidden sm:flex items-center gap-1">
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.tasks.index') }}"
                                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors
                                        {{ request()->routeIs('admin.tasks.*')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    Semua Tugas
                                </a>
                                <a href="{{ route('admin.users.index') }}"
                                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors
                                        {{ request()->routeIs('admin.users.*')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    Kelola User
                                </a>
                            @else
                                <a href="{{ route('dashboard') }}"
                                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors
                                        {{ request()->routeIs('dashboard')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    Dashboard
                                </a>
                                <a href="{{ route('user.tasks.index') }}"
                                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors
                                        {{ request()->routeIs('user.tasks.*')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    Tugas Saya
                                </a>
                            @endif
                        </div>
                    @endauth
                </div>

                {{-- Right: User info + logout --}}
                @auth
                    <div class="flex items-center gap-4">
                        {{-- Notification Bell --}}
                        <x-notification-bell />

                        {{-- User name + role badge --}}
                        <div class="hidden sm:flex items-center gap-2">
                            <span class="text-sm text-gray-700 font-medium">{{ auth()->user()->name }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                {{ auth()->user()->isAdmin()
                                    ? 'bg-purple-100 text-purple-700'
                                    : 'bg-blue-100 text-blue-700' }}">
                                {{ auth()->user()->isAdmin() ? 'Admin' : 'User' }}
                            </span>
                        </div>

                        {{-- Logout --}}
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="text-sm text-gray-500 hover:text-red-600 transition-colors font-medium">
                                Keluar
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>

        {{-- Mobile nav --}}
        @auth
            <div class="sm:hidden border-t border-gray-100 px-4 py-3 space-y-1">
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.tasks.index') }}"
                        class="block px-3 py-2 rounded-md text-sm font-medium
                            {{ request()->routeIs('admin.tasks.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600' }}">
                        Semua Tugas
                    </a>
                    <a href="{{ route('admin.users.index') }}"
                        class="block px-3 py-2 rounded-md text-sm font-medium
                            {{ request()->routeIs('admin.users.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600' }}">
                        Kelola User
                    </a>
                @else
                    <a href="{{ route('dashboard') }}"
                        class="block px-3 py-2 rounded-md text-sm font-medium
                            {{ request()->routeIs('dashboard') ? 'bg-gray-100 text-gray-900' : 'text-gray-600' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('user.tasks.index') }}"
                        class="block px-3 py-2 rounded-md text-sm font-medium
                            {{ request()->routeIs('user.tasks.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600' }}">
                        Tugas Saya
                    </a>
                @endif

                <div class="pt-2 border-t border-gray-100 flex items-center gap-2">
                    <span class="text-sm text-gray-700">{{ auth()->user()->name }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ auth()->user()->isAdmin() ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ auth()->user()->isAdmin() ? 'Admin' : 'User' }}
                    </span>
                </div>
            </div>
        @endauth
    </nav>

    {{-- Flash messages --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 mb-4 text-sm">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 mb-4 text-sm">
                ❌ {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- Main content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

</body>

</html>