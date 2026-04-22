/**
 * AGF Live.js — Real-Time SSE Client
 * Connects to /notifications/stream and dispatches live events
 * to registered handlers. Auto-reconnects with exponential backoff.
 */

const AGFLive = (() => {
    let evtSource = null;
    let reconnectDelay = 3000;
    const handlers = {};

    function connect() {
        if (!document.body.dataset.userId) return; // Not logged in

        evtSource = new EventSource('/notifications/stream');

        evtSource.onopen = () => {
            reconnectDelay = 3000;
            console.debug('[AGF Live] Connected');
        };

        evtSource.onerror = () => {
            console.debug('[AGF Live] Connection lost. Reconnecting in', reconnectDelay, 'ms');
            evtSource.close();
            setTimeout(() => {
                reconnectDelay = Math.min(reconnectDelay * 1.5, 30000);
                connect();
            }, reconnectDelay);
        };

        // Generic message handler
        evtSource.onmessage = (e) => {
            try {
                const data = JSON.parse(e.data);
                dispatch('message', data);
            } catch {}
        };

        // Event-specific handlers
        const eventTypes = [
            'new_reply', 'mention', 'vote', 'badge', 'new_post',
            'thread_locked', 'thread_pinned', 'system'
        ];
        eventTypes.forEach(type => {
            evtSource.addEventListener(type, (e) => {
                try {
                    const data = JSON.parse(e.data);
                    dispatch(type, data);
                } catch {}
            });
        });

        // new_reply: update thread post count + show toast
        on('new_reply', (data) => {
            showToast(`💬 New reply in <strong>${escHtml(data.thread_title)}</strong>`, 'info');
            updateNotifBadge(+1);
        });

        // mention: toast + badge
        on('mention', (data) => {
            showToast(`📣 <strong>${escHtml(data.from_username)}</strong> mentioned you`, 'info');
            updateNotifBadge(+1);
        });

        // vote: ephemeral toast
        on('vote', (data) => {
            showToast(`⬆️ Your post received a vote (+${data.value})`, 'success');
        });

        // badge: animated badge unlock
        on('badge', (data) => {
            showToast(`🏅 Badge unlocked: <strong>${escHtml(data.badge_name)}</strong>`, 'success');
            updateNotifBadge(+1);
        });
    }

    function on(event, handler) {
        if (!handlers[event]) handlers[event] = [];
        handlers[event].push(handler);
    }

    function dispatch(event, data) {
        (handlers[event] || []).forEach(h => h(data));
    }

    function updateNotifBadge(delta) {
        const badge = document.getElementById('notif-badge');
        if (!badge) return;
        const current = parseInt(badge.textContent) || 0;
        const next = Math.max(0, current + delta);
        badge.textContent = next;
        badge.style.display = next > 0 ? 'flex' : 'none';
        // Spring-scale animation
        badge.animate([
            { transform: 'scale(0.5)', opacity: 0 },
            { transform: 'scale(1.3)' },
            { transform: 'scale(1)', opacity: 1 }
        ], { duration: 380, easing: 'cubic-bezier(0.34,1.56,0.64,1)', fill: 'forwards' });
    }

    // Toast notification system
    let toastContainer = null;
    function getToastContainer() {
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'agf-toast-container';
            toastContainer.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                pointer-events: none;
            `;
            document.body.appendChild(toastContainer);
        }
        return toastContainer;
    }

    function showToast(message, type = 'info', duration = 4000) {
        const container = getToastContainer();
        const colors = {
            info:    'rgba(6,182,212,0.15)',
            success: 'rgba(16,185,129,0.15)',
            warning: 'rgba(245,158,11,0.15)',
            error:   'rgba(239,68,68,0.15)',
        };
        const borders = {
            info:    '#06b6d4',
            success: '#10b981',
            warning: '#f59e0b',
            error:   '#ef4444',
        };

        const toast = document.createElement('div');
        toast.innerHTML = message;
        toast.style.cssText = `
            background: ${colors[type] || colors.info};
            border: 1px solid ${borders[type] || borders.info};
            border-radius: 12px;
            padding: 14px 20px;
            color: #f8f9fa;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            backdrop-filter: blur(20px);
            pointer-events: auto;
            cursor: pointer;
            max-width: 320px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        `;

        // Spring-in from right
        toast.animate([
            { transform: 'translateX(120%)', opacity: 0 },
            { transform: 'translateX(0)',    opacity: 1 }
        ], { duration: 380, easing: 'cubic-bezier(0.34,1.56,0.64,1)', fill: 'forwards' });

        toast.addEventListener('click', () => dismissToast(toast));
        container.appendChild(toast);

        setTimeout(() => dismissToast(toast), duration);
        return toast;
    }

    function dismissToast(toast) {
        if (!toast.parentNode) return;
        toast.animate([
            { transform: 'translateX(0)',    opacity: 1 },
            { transform: 'translateX(120%)', opacity: 0 }
        ], { duration: 300, easing: 'cubic-bezier(0.55,0,1,0.45)', fill: 'forwards' })
            .onfinish = () => toast.remove();
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // Public API
    return { connect, on, showToast };
})();

// Auto-connect on DOM ready
document.addEventListener('DOMContentLoaded', () => AGFLive.connect());

// Export for other scripts
window.AGFLive = AGFLive;
