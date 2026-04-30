<div>
    @script
        <script>
            $wire.on('request-status-toast', ({ title, message, status }) => {
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

                if (status === 'danger') {
                    toast.danger().send();

                    return;
                }

                toast.success().send();
            });
        </script>
    @endscript

    <span wire:poll.5s="pollForNewNotifications" class="hidden"></span>
</div>
