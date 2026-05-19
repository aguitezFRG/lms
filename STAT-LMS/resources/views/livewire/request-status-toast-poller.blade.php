<div>
    @script
        <script>
            const sessionId = @js(session()->getId());
            const listenerRegistryKey = '__requestStatusToastPollerListeners';
            const persistentOverdueStorageKey = `persistentOverdueBorrowToasts:${sessionId}`;
            const dismissedOverdueStorageKey = `dismissedOverdueBorrowToastIds:${sessionId}`;

            const readJsonArray = (storageKey) => {
                try {
                    const raw = sessionStorage.getItem(storageKey);
                    const parsed = raw ? JSON.parse(raw) : [];

                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            };

            const writeJsonArray = (storageKey, value) => {
                sessionStorage.setItem(storageKey, JSON.stringify(value));
            };

            const readPersistentOverdueToasts = () => readJsonArray(persistentOverdueStorageKey);
            const writePersistentOverdueToasts = (toasts) => writeJsonArray(persistentOverdueStorageKey, toasts);
            const readDismissedOverdueToastIds = () => readJsonArray(dismissedOverdueStorageKey);
            const writeDismissedOverdueToastIds = (ids) => writeJsonArray(dismissedOverdueStorageKey, ids);

            const normalizeToastId = (toast) => toast?.toastId ?? toast?.id ?? null;

            const isPersistentOverdueBorrowToast = (toast) => {
                return toast?.persistent === true
                    && toast?.kind === 'borrow-reminder'
                    && toast?.status === 'danger'
                    && Boolean(normalizeToastId(toast));
            };

            const isDismissedOverdueToastId = (toastId) => readDismissedOverdueToastIds().includes(toastId);

            const rememberPersistentOverdueToast = (toast) => {
                if (! isPersistentOverdueBorrowToast(toast)) {
                    return;
                }

                const toastId = normalizeToastId(toast);

                if (isDismissedOverdueToastId(toastId)) {
                    return;
                }

                const storedToasts = readPersistentOverdueToasts();
                const nextStoredToasts = [
                    ...storedToasts.filter((storedToast) => normalizeToastId(storedToast) !== toastId),
                    { ...toast, toastId },
                ];

                writePersistentOverdueToasts(nextStoredToasts);
            };

            const forgetPersistentOverdueToast = (toastId) => {
                const storedToasts = readPersistentOverdueToasts();
                const nextStoredToasts = storedToasts.filter((storedToast) => normalizeToastId(storedToast) !== toastId);

                if (nextStoredToasts.length !== storedToasts.length) {
                    writePersistentOverdueToasts(nextStoredToasts);
                }
            };

            const markPersistentOverdueToastDismissed = (toastId) => {
                if (! toastId) {
                    return;
                }

                const storedToasts = readPersistentOverdueToasts();

                if (! storedToasts.some((storedToast) => normalizeToastId(storedToast) === toastId)) {
                    return;
                }

                writeDismissedOverdueToastIds([
                    ...new Set([
                        ...readDismissedOverdueToastIds(),
                        toastId,
                    ]),
                ]);

                forgetPersistentOverdueToast(toastId);
            };

            const renderToast = ({ toastId = null, title, message, status, persistent = false, kind = null }) => {
                let toast = null;

                if (window.FilamentNotification?.make) {
                    toast = window.FilamentNotification
                        .make()
                        .title(title)
                        .body(message);
                } else if (window.FilamentNotification) {
                    toast = new window.FilamentNotification()
                        .title(title)
                        .body(message);
                }

                if (! toast) {
                    return;
                }

                if (toastId && typeof toast.id === 'function') {
                    toast.id(toastId);
                }

                if (persistent && typeof toast.persistent === 'function') {
                    toast.persistent();
                } else if (typeof toast.seconds === 'function') {
                    const seconds = kind === 'borrow-reminder' ? 12 : 6;

                    toast.seconds(seconds);
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

            window[listenerRegistryKey] ??= {};
            window[listenerRegistryKey].renderedPersistentOverdueToastIds ??= new Set();

            const renderPersistentOverdueToast = (toast) => {
                const toastId = normalizeToastId(toast);

                if (! isPersistentOverdueBorrowToast(toast) || isDismissedOverdueToastId(toastId)) {
                    return;
                }

                if (window[listenerRegistryKey].renderedPersistentOverdueToastIds.has(toastId)) {
                    return;
                }

                window[listenerRegistryKey].renderedPersistentOverdueToastIds.add(toastId);
                renderToast({ ...toast, toastId });
            };

            for (const storedToast of readPersistentOverdueToasts()) {
                renderPersistentOverdueToast(storedToast);
            }

            if (typeof window[listenerRegistryKey].requestStatusToastOff === 'function') {
                window[listenerRegistryKey].requestStatusToastOff();
            }

            if (typeof window[listenerRegistryKey].notificationClosedOff === 'function') {
                window[listenerRegistryKey].notificationClosedOff();
            }

            window[listenerRegistryKey].requestStatusToastOff = $wire.on('request-status-toast', ({ toastId = null, title, message, status, persistent = false, kind = null }) => {
                const toast = { toastId, title, message, status, persistent, kind };

                rememberPersistentOverdueToast(toast);
                renderPersistentOverdueToast(toast);

                if (! isPersistentOverdueBorrowToast(toast)) {
                    renderToast(toast);
                }
            });

            const handleNotificationClosed = ({ detail }) => {
                markPersistentOverdueToastDismissed(detail?.id ?? null);
            };

            window.addEventListener('notificationClosed', handleNotificationClosed);
            window[listenerRegistryKey].notificationClosedOff = () => {
                window.removeEventListener('notificationClosed', handleNotificationClosed);
            };
        </script>
    @endscript

    <span wire:init="pollForNewNotifications" wire:poll.60s="pollForNewNotifications" class="hidden"></span>
</div>
