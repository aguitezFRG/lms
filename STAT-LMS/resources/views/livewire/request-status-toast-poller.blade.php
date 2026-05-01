<div>
    @script
        <script>
            const persistentToastsStorageKey = 'persistentRequestStatusToasts';
            const listenerRegistryKey = '__requestStatusToastPollerListeners';

            const readPersistentToasts = () => {
                try {
                    const raw = sessionStorage.getItem(persistentToastsStorageKey);
                    const parsed = raw ? JSON.parse(raw) : [];

                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            };

            const writePersistentToasts = (toasts) => {
                sessionStorage.setItem(persistentToastsStorageKey, JSON.stringify(toasts));
            };

            const renderToast = ({ title, message, status, persistent = false }) => {
                let toast = null;

                if (window.FilamentNotification?.make) {
                    toast = window.FilamentNotification
                        .make()
                        .title(title)
                        .body(message)
                        .seconds(6);
                } else if (window.FilamentNotification) {
                    toast = new window.FilamentNotification()
                        .title(title)
                        .body(message)
                        .seconds(6);
                }

                if (! toast) {
                    return;
                }

                if (persistent && typeof toast.persistent === 'function') {
                    toast.persistent();
                }

                if (status === 'danger') {
                    toast.danger().send();

                    return;
                }

                if (status === 'warning' && typeof toast.warning === 'function') {
                    toast.warning().send();

                    return;
                }

                if (status === 'info' && typeof toast.info === 'function') {
                    toast.info().send();

                    return;
                }

                toast.success().send();
            };

            const persistedToasts = readPersistentToasts();
            const renderedPersistentToastIds = new Set();

            for (const persisted of persistedToasts) {
                if (! persisted?.toastId) {
                    continue;
                }

                renderedPersistentToastIds.add(persisted.toastId);
                renderToast(persisted);
            }

            window[listenerRegistryKey] ??= {};

            if (typeof window[listenerRegistryKey].requestStatusToastOff === 'function') {
                window[listenerRegistryKey].requestStatusToastOff();
            }

            window[listenerRegistryKey].requestStatusToastOff = $wire.on('request-status-toast', ({ toastId = null, title, message, status, persistent = false }) => {
                if (persistent && toastId) {
                    if (! renderedPersistentToastIds.has(toastId)) {
                        const nextPersistedToasts = readPersistentToasts();
                        const exists = nextPersistedToasts.some((toast) => toast.toastId === toastId);

                        if (! exists) {
                            nextPersistedToasts.push({ toastId, title, message, status, persistent: true });
                            writePersistentToasts(nextPersistedToasts);
                        }
                    } else {
                        return;
                    }

                    renderedPersistentToastIds.add(toastId);
                }

                renderToast({ title, message, status, persistent });
            });
        </script>
    @endscript

    <span wire:poll.5s="pollForNewNotifications" class="hidden"></span>
</div>
