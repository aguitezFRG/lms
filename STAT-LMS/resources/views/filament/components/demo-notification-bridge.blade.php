<style>
    #demo-notification-stack {
        position: fixed;
        z-index: 100;
        top: 1rem;
        right: 1rem;
        display: grid;
        width: min(24rem, calc(100vw - 2rem));
        gap: .75rem;
        pointer-events: none;
    }

    .demo-notification {
        border: 1px solid #bbf7d0;
        border-radius: .75rem;
        background: #f0fdf4;
        box-shadow: 0 10px 25px rgb(15 23 42 / 15%);
        color: #14532d;
        padding: 1rem;
    }

    .demo-notification[data-status="warning"] {
        border-color: #fde68a;
        background: #fffbeb;
        color: #78350f;
    }

    .demo-notification[data-status="danger"],
    .demo-notification[data-status="error"] {
        border-color: #fecaca;
        background: #fef2f2;
        color: #7f1d1d;
    }

    .demo-notification-title { font-weight: 700; }
    .demo-notification-body { margin-top: .25rem; font-size: .875rem; }

    html.dark .demo-notification {
        border-color: #166534;
        background: #052e16;
        color: #dcfce7;
    }

    html.dark .demo-notification[data-status="warning"] {
        border-color: #92400e;
        background: #451a03;
        color: #fef3c7;
    }

    html.dark .demo-notification[data-status="danger"],
    html.dark .demo-notification[data-status="error"] {
        border-color: #991b1b;
        background: #450a0a;
        color: #fee2e2;
    }

    html.oled.dark .demo-notification { background: #000; }
</style>

<script>
    (() => {
        if (window.__instatDemoNotificationBridgeRegistered) return;
        window.__instatDemoNotificationBridgeRegistered = true;

        const syncActionModalWindow = (modalId = null) => window.Alpine.nextTick(() => {
            const modal = modalId
                ? document.querySelector(`[data-fi-modal-id="${CSS.escape(modalId)}"]`)
                : Array.from(document.querySelectorAll(
                    '[data-fi-modal-id*="-action-"].fi-modal-open',
                )).pop();

            if (! modal?.dataset.fiModalId.includes('-action-')) return;

            const state = window.Alpine.$data(modal);

            if (state?.isOpen && ! state.isWindowVisible) {
                state.isWindowVisible = true;
            }
        });

        const register = () => {
            window.Livewire.hook('morphed', () => syncActionModalWindow());
            document.addEventListener('x-modal-opened', ({ detail }) => {
                syncActionModalWindow(detail?.id);
            });

            window.Livewire.on('demo-notification', ({ notification }) => {
                let stack = document.getElementById('demo-notification-stack');

                if (! stack) {
                    stack = document.createElement('div');
                    stack.id = 'demo-notification-stack';
                    stack.setAttribute('aria-live', 'polite');
                    document.body.append(stack);
                }

                const toast = document.createElement('section');
                toast.className = 'demo-notification';
                toast.dataset.status = notification.status ?? 'success';
                toast.dataset.notificationId = notification.identifier ?? '';
                toast.setAttribute('role', ['danger', 'error'].includes(notification.status) ? 'alert' : 'status');

                const title = document.createElement('div');
                title.className = 'demo-notification-title';
                title.textContent = notification.title ?? 'Request completed';
                toast.append(title);

                if (notification.body) {
                    const body = document.createElement('div');
                    body.className = 'demo-notification-body';
                    body.textContent = notification.body;
                    toast.append(body);
                }

                stack.append(toast);
                window.setTimeout(() => toast.remove(), Number(notification.duration) || 6000);
            });
        };

        if (window.Livewire) register();
        else document.addEventListener('livewire:init', register, { once: true });
    })();
</script>
