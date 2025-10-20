/**
 * In-page Toast Notifications (bottom-right)
 * Realtime via SSE, no OS/browser push permissions required
 */

class BrowserNotification {
    constructor() {
        this.lastNotificationId = 0;
        this.source = null; // EventSource for SSE
        this.enabled = true; // user preference (localStorage)
        this.backoff = 0; // reconnection backoff for SSE
        this.reconnectTimer = null; // pending SSE reconnect timeout
        this.container = null; // toast container element
    }

    // No permission checks needed for in-page toasts

    /**
     * Load user preference from localStorage
     */
    loadPreference() {
        const val = localStorage.getItem('enableBrowserNotifications');
        // Mặc định TẮT để tránh spam request khi chưa bật
        if (val === null) return false; // default disabled
        return val === '1';
    }

    savePreference(enabled) {
        localStorage.setItem('enableBrowserNotifications', enabled ? '1' : '0');
    }

    /** Render a toast UI at bottom-right */
    showToast(title, message, url = null) {
        if (!this.container) this.createContainer();

        const toast = document.createElement('div');
        toast.className = 'gecko-toast shadow-lg rounded-lg bg-white border border-gray-200 p-4 mb-3 w-80 animate-slide-in';
        toast.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-3">🦎</div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900 line-clamp-2">${this.escapeHtml(title)}</div>
                    <div class="text-sm text-gray-600 mt-1 line-clamp-3">${this.escapeHtml(message)}</div>
                </div>
                <button class="ml-3 text-gray-400 hover:text-gray-600" aria-label="Close">✕</button>
            </div>
        `;

        const closeBtn = toast.querySelector('button');
        closeBtn.addEventListener('click', () => this.dismissToast(toast));

        if (url) {
            toast.style.cursor = 'pointer';
            toast.addEventListener('click', (e) => {
                if (e.target === closeBtn) return; // already handled
                window.location.href = url;
            });
        }

        this.container.appendChild(toast);
        setTimeout(() => this.dismissToast(toast), 8000);
    }

    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'gecko-toast-container';
        this.container.style.position = 'fixed';
        this.container.style.bottom = '16px';
        this.container.style.right = '16px';
        this.container.style.zIndex = '9999';
        document.body.appendChild(this.container);

        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes gecko-slide-in { from { transform: translateY(10px); opacity: 0 } to { transform: translateY(0); opacity: 1 } }
            .animate-slide-in { animation: gecko-slide-in .2s ease-out }
            .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
            .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        `;
        document.head.appendChild(style);
    }

    dismissToast(toast) {
        if (!toast) return;
        toast.style.opacity = '0';
        toast.style.transition = 'opacity .15s';
        setTimeout(() => toast.remove(), 150);
    }

    escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, (s) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[s]));
    }

    /** Start SSE stream if supported */
    startSSE() {
        if (!this.enabled) return;
        if (this.source) return; // already started
        if (!('EventSource' in window)) return; // not supported

        const url = new URL('/api/notifications-stream.php', window.location.origin);
        if (this.lastNotificationId) {
            url.searchParams.set('last_id', this.lastNotificationId);
        }

        const source = new EventSource(url.toString(), { withCredentials: true });
        this.source = source;

        source.addEventListener('ready', (e) => {
            try {
                const data = JSON.parse(e.data);
                if (data.last_id) this.lastNotificationId = data.last_id;
            } catch {}
            this.backoff = 0; // reset backoff
            console.log('SSE ready');
        });

        source.addEventListener('notification', (e) => {
            try {
                const n = JSON.parse(e.data);
                this.lastNotificationId = n.id;
                this.showNotificationFromData(n);
            } catch (err) {
                console.error('Parse SSE notification failed', err);
            }
        });

        source.addEventListener('error', () => {
            console.warn('SSE error, will reconnect');
            this.stopSSE();
            // Backoff reconnection up to 60s
            this.backoff = Math.min(this.backoff ? this.backoff * 2 : 2000, 60000);
            this.reconnectTimer = setTimeout(() => {
                if (this.enabled) this.startSSE();
            }, this.backoff);
        });

        source.addEventListener('close', () => {
            console.log('SSE closed by server');
            this.stopSSE();
            this.reconnectTimer = setTimeout(() => {
                if (this.enabled) this.startSSE();
            }, 1000);
        });
    }

    stopSSE() {
        if (this.source) {
            try { this.source.close(); } catch {}
            this.source = null;
        }
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }
        this.backoff = 0;
    }

    /**
     * Hiển thị notification từ data API
     */
    showNotificationFromData(notification) {
        const typeIcons = {
            'order': '🛍️',
            'contact': '💬',
            'general': '📢',
            'promotion': '🎉'
        };

        const icon = typeIcons[notification.type] || '📢';
        const title = `${icon} ${notification.title}`;
        
        let url = '/notifications.php';
        if (notification.type === 'order' && notification.related_id) {
            url = `/order-detail.php?id=${notification.related_id}`;
        }

        this.showToast(title, notification.message, url);

        // Update badge count in header without extra request
        const countElement = document.getElementById('notification-count');
        if (countElement) {
            const current = parseInt(countElement.textContent || '0', 10) || 0;
            countElement.textContent = current + 1;
            countElement.classList.remove('hidden');
        }
    }

    /**
     * Khởi tạo và yêu cầu quyền
     */
    async init() {
        this.enabled = this.loadPreference();
        if (!this.enabled) {
            console.log('Browser notifications disabled by user');
            return false;
        }

        // Start SSE if available
        if ('EventSource' in window) {
            this.startSSE();
            return true;
        }
        console.log('Trình duyệt không hỗ trợ SSE');
        return false;
    }
}

// Tạo instance global
window.browserNotification = new BrowserNotification();

// Auto init khi page load (nếu user đã đăng nhập)
document.addEventListener('DOMContentLoaded', function() {
    // Chỉ init nếu có notification button (user đã login)
    if (document.getElementById('notification-button')) {
        // Chỉ init nếu người dùng đã bật trong cài đặt
        if (localStorage.getItem('enableBrowserNotifications') === '1') {
            setTimeout(() => {
                window.browserNotification.init();
            }, 500);
        }

        // Tab visibility: keep SSE (browsers throttle hidden tabs automatically)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                if (window.browserNotification.enabled && !window.browserNotification.source) {
                    if ('EventSource' in window) {
                        window.browserNotification.startSSE();
                    }
                }
            }
        });
    }
});
