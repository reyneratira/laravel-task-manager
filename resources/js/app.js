import './echo';
import Alpine from 'alpinejs';

/**
 * Alpine.js notification store.
 * Manages real-time notifications via Laravel Echo + Reverb WebSocket.
 *
 * Usage in Blade: x-data="notifications({{ auth()->id() }})"
 */
Alpine.data('notifications', (userId) => ({
    open: false,
    notifications: [],
    unreadCount: 0,
    toastMessage: null,
    toastVisible: false,
    toastTimeout: null,

    init() {
        // Fetch initial unread notifications
        this.fetchNotifications();

        // Listen for new broadcast notifications on user's private channel
        if (userId && window.Echo) {
            window.Echo.private(`App.Models.User.${userId}`)
                .notification((notification) => {
                    this.handleNewNotification(notification);
                });
        }
    },

    async fetchNotifications() {
        try {
            const response = await fetch('/notifications', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (response.ok) {
                const data = await response.json();
                this.notifications = data.notifications;
                this.unreadCount = data.unread_count;
            }
        } catch (e) {
            console.error('Failed to fetch notifications:', e);
        }
    },

    handleNewNotification(notification) {
        // Add to the top of the list
        this.notifications.unshift({
            id: notification.id,
            type: notification.type?.split('\\').pop() ?? 'Notification',
            data: notification,
            created_at: 'Baru saja',
            created_at_iso: new Date().toISOString(),
        });

        this.unreadCount++;

        // Show toast
        this.showToast(notification.message || 'Notifikasi baru');
    },

    showToast(message) {
        this.toastMessage = message;
        this.toastVisible = true;

        if (this.toastTimeout) clearTimeout(this.toastTimeout);
        this.toastTimeout = setTimeout(() => {
            this.toastVisible = false;
            this.toastMessage = null;
        }, 5000);
    },

    async markAsRead(id) {
        try {
            const response = await fetch(`/notifications/${id}/read`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (response.ok) {
                const data = await response.json();
                this.notifications = this.notifications.filter(n => n.id !== id);
                this.unreadCount = data.unread_count;
            }
        } catch (e) {
            console.error('Failed to mark notification as read:', e);
        }
    },

    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/read-all', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (response.ok) {
                this.notifications = [];
                this.unreadCount = 0;
            }
        } catch (e) {
            console.error('Failed to mark all as read:', e);
        }
    },

    getNotificationIcon(type) {
        switch (type) {
            case 'TaskStatusChanged': return '🔄';
            case 'TaskAssignedNotification': return '📋';
            default: return '🔔';
        }
    },
}));

window.Alpine = Alpine;
Alpine.start();
