{{-- Notification Bell Component --}}
{{-- Uses Alpine.js data from 'notifications' component registered in app.js --}}
<div class="relative" x-data="notifications({{ auth()->id() }})">
    {{-- Bell Button --}}
    <button
        @click="open = !open"
        class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
        title="Notifikasi"
    >
        {{-- Bell Icon --}}
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>

        {{-- Badge Counter --}}
        <span
            x-show="unreadCount > 0"
            x-text="unreadCount > 99 ? '99+' : unreadCount"
            x-transition
            class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-red-500 rounded-full leading-none"
        ></span>
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50 overflow-hidden"
        style="display: none;"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-800">Notifikasi</h3>
            <button
                x-show="unreadCount > 0"
                @click="markAllAsRead()"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors"
            >
                Tandai semua dibaca
            </button>
        </div>

        {{-- Notification List --}}
        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
            <template x-if="notifications.length === 0">
                <div class="px-4 py-8 text-center text-sm text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-2.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 007.586 13H4"></path>
                    </svg>
                    Tidak ada notifikasi baru
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <div class="px-4 py-3 hover:bg-blue-50 transition-colors cursor-pointer group" @click="markAsRead(notification.id)">
                    <div class="flex items-start gap-3">
                        {{-- Icon --}}
                        <span class="text-lg flex-shrink-0 mt-0.5" x-text="getNotificationIcon(notification.type)"></span>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800 leading-snug" x-text="notification.data.message"></p>
                            <p class="text-xs text-gray-400 mt-1" x-text="notification.created_at"></p>
                        </div>

                        {{-- Dismiss button --}}
                        <button
                            @click.stop="markAsRead(notification.id)"
                            class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-gray-600 transition-opacity"
                            title="Tandai dibaca"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div
        x-show="toastVisible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-5 right-5 z-[100] max-w-sm bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 p-4"
        style="display: none;"
    >
        <div class="flex items-start gap-3">
            <span class="text-lg">🔔</span>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-800" x-text="toastMessage"></p>
            </div>
            <button @click="toastVisible = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
