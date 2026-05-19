<div>
    @script
        <script>
            const sessionId = @js(session()->getId());
            const listenerRegistryKey = '__requestStatusToastPollerListeners';
            const persistentOverdueStorageKey = `persistentOverdueBorrowToasts:${sessionId}`;
            const dismissedOverdueStorageKey = `dismissedOverdueBorrowReminderKeys:${sessionId}`;
            const dismissedOverdueStorageLimit = 200;

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
            const readDismissedOverdueReminderKeys = () => readJsonArray(dismissedOverdueStorageKey);
            const writeDismissedOverdueReminderKeys = (keys) => writeJsonArray(dismissedOverdueStorageKey, keys);
            const getRenderedPersistentOverdueReminderKeys = () => window[listenerRegistryKey]?.renderedPersistentOverdueReminderKeys;

            const normalizeToastId = (toast) => {
                const id = toast?.toastId ?? toast?.id ?? null;

                return id === null ? null : String(id);
            };

            const normalizeReminderKey = (toast) => {
                const reminderKey = toast?.reminderKey ?? null;

                return reminderKey === null ? null : String(reminderKey);
            };

            const isPersistentOverdueBorrowToast = (toast) => {
                return toast?.persistent === true
                    && toast?.kind === 'borrow-reminder'
                    && toast?.status === 'danger'
                    && Boolean(normalizeReminderKey(toast));
            };

            const isDismissedOverdueReminderKey = (reminderKey) => {
                if (! reminderKey) {
                    return false;
                }

                return readDismissedOverdueReminderKeys().includes(String(reminderKey));
            };

            const rememberPersistentOverdueToast = (toast) => {
                if (! isPersistentOverdueBorrowToast(toast)) {
                    return;
                }

                const reminderKey = normalizeReminderKey(toast);
                const toastId = normalizeToastId(toast);

                if (isDismissedOverdueReminderKey(reminderKey)) {
                    return;
                }

                const storedToasts = readPersistentOverdueToasts();
                const nextStoredToasts = [
                    ...storedToasts.filter((storedToast) => normalizeReminderKey(storedToast) !== reminderKey),
                    { ...toast, toastId, reminderKey },
                ];

                writePersistentOverdueToasts(nextStoredToasts);
            };

            const forgetPersistentOverdueToast = (reminderKey) => {
                const storedToasts = readPersistentOverdueToasts();
                const nextStoredToasts = storedToasts.filter((storedToast) => normalizeReminderKey(storedToast) !== reminderKey);

                if (nextStoredToasts.length !== storedToasts.length) {
                    writePersistentOverdueToasts(nextStoredToasts);
                }
            };

            const prunePersistentOverdueToasts = (activeReminderKeys) => {
                if (! Array.isArray(activeReminderKeys)) {
                    return;
                }

                const activeKeys = new Set(activeReminderKeys.map((key) => String(key)));
                const storedToasts = readPersistentOverdueToasts();
                const nextStoredToasts = storedToasts.filter((storedToast) => activeKeys.has(normalizeReminderKey(storedToast)));
                const renderedReminderKeys = getRenderedPersistentOverdueReminderKeys();

                if (renderedReminderKeys instanceof Set) {
                    for (const renderedReminderKey of renderedReminderKeys) {
                        if (! activeKeys.has(renderedReminderKey)) {
                            renderedReminderKeys.delete(renderedReminderKey);
                        }
                    }
                }

                const nextDismissedKeys = readDismissedOverdueReminderKeys().filter((key) => activeKeys.has(String(key)));

                if (nextDismissedKeys.length !== readDismissedOverdueReminderKeys().length) {
                    writeDismissedOverdueReminderKeys(nextDismissedKeys);
                }

                if (nextStoredToasts.length !== storedToasts.length) {
                    writePersistentOverdueToasts(nextStoredToasts);
                }
            };

            const markPersistentOverdueToastDismissed = (reminderKey) => {
                if (! reminderKey) {
                    return;
                }

                const normalizedReminderKey = String(reminderKey);
                const boundedDismissedKeys = [
                    ...new Set([
                        ...readDismissedOverdueReminderKeys(),
                        normalizedReminderKey,
                    ]),
                ].slice(-dismissedOverdueStorageLimit);

                writeDismissedOverdueReminderKeys(boundedDismissedKeys);

                forgetPersistentOverdueToast(normalizedReminderKey);
                getRenderedPersistentOverdueReminderKeys()?.delete(normalizedReminderKey);
            };

            const renderToast = ({ toastId = null, title, message, status, persistent = false, kind = null, reminderKey = null }) => {
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

                if (toastId && reminderKey) {
                    window[listenerRegistryKey].reminderKeysByToastId.set(String(toastId), String(reminderKey));
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
            window[listenerRegistryKey].renderedPersistentOverdueReminderKeys ??= new Set();
            window[listenerRegistryKey].reminderKeysByToastId ??= new Map();

            const renderPersistentOverdueToast = (toast) => {
                const toastId = normalizeToastId(toast);
                const reminderKey = normalizeReminderKey(toast);

                if (! isPersistentOverdueBorrowToast(toast) || isDismissedOverdueReminderKey(reminderKey)) {
                    return;
                }

                if (window[listenerRegistryKey].renderedPersistentOverdueReminderKeys.has(reminderKey)) {
                    return;
                }

                window[listenerRegistryKey].renderedPersistentOverdueReminderKeys.add(reminderKey);
                renderToast({ ...toast, toastId, reminderKey });
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

            if (typeof window[listenerRegistryKey].borrowReminderActiveKeysOff === 'function') {
                window[listenerRegistryKey].borrowReminderActiveKeysOff();
            }

            window[listenerRegistryKey].borrowReminderActiveKeysOff = $wire.on('borrow-reminder-active-keys', ({ reminderKeys = [] }) => {
                prunePersistentOverdueToasts(reminderKeys);
            });

            window[listenerRegistryKey].requestStatusToastOff = $wire.on('request-status-toast', ({ toastId = null, title, message, status, persistent = false, kind = null, reminderKey = null }) => {
                const toast = { toastId, title, message, status, persistent, kind, reminderKey };

                rememberPersistentOverdueToast(toast);
                renderPersistentOverdueToast(toast);

                if (! isPersistentOverdueBorrowToast(toast)) {
                    renderToast(toast);
                }
            });

            const handleNotificationClosed = ({ detail }) => {
                const toastId = detail?.id === undefined || detail?.id === null ? null : String(detail.id);
                let reminderKey = toastId ? window[listenerRegistryKey].reminderKeysByToastId.get(toastId) : null;

                if (! reminderKey) {
                    const renderedReminderKeys = getRenderedPersistentOverdueReminderKeys();

                    if (renderedReminderKeys instanceof Set && renderedReminderKeys.size === 1) {
                        reminderKey = Array.from(renderedReminderKeys)[0];
                    }
                }

                markPersistentOverdueToastDismissed(reminderKey);
            };

            window.addEventListener('notificationClosed', handleNotificationClosed);
            window[listenerRegistryKey].notificationClosedOff = () => {
                window.removeEventListener('notificationClosed', handleNotificationClosed);
            };
        </script>
    @endscript

    <span wire:init="pollForNewNotifications" wire:poll.60s="pollForNewNotifications" class="hidden"></span>
</div>
