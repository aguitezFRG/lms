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
                if (! window.FilamentNotification) return;

                const toast = new window.FilamentNotification();
                const status = notification.status === 'error'
                    ? 'danger'
                    : (notification.status ?? 'success');

                if (notification.identifier) {
                    toast.id = notification.identifier;
                }

                toast.title(notification.title ?? 'Request completed');

                if (notification.body) toast.body(notification.body);
                if (notification.icon) toast.icon(notification.icon);
                if (notification.duration !== null && notification.duration !== undefined) {
                    toast.duration(notification.duration);
                }

                if (typeof toast[status] === 'function') toast[status]();
                else toast.status(status);

                toast.send();
            });
        };

        if (window.Livewire) register();
        else document.addEventListener('livewire:init', register, { once: true });
    })();
</script>
