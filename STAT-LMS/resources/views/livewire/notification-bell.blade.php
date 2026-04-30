<div
    class="relative"
    x-data="{ open: @entangle('open') }"
    x-on:click.outside="open = false"
>
    @script
        <script>
            $wire.on('request-status-toast', ({ title, message, status }) => {
                if (! window.FilamentNotification) {
                    return;
                }

                const toast = new window.FilamentNotification()
                    .title(title)
                    .body(message)
                    .seconds(6);

                if (status === 'danger') {
                    toast.danger().send();

                    return;
                }

                toast.success().send();
            });
        </script>
    @endscript

    {{-- Bell button --}}
    <button
        type="button"
        x-on:click="open = !open"
        class="relative flex items-center justify-center w-9 h-9 rounded-lg
               text-gray-500 hover:text-gray-700 hover:bg-gray-100
               dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-white/5
               transition-colors"
        aria-label="Notifications"
    >
        <x-heroicon-o-bell class="w-5 h-5" />

        @if ($this->unreadCount > 0)
            <span class="absolute top-0.5 right-0.5 min-w-[1.1rem] h-[1.1rem] px-1
                         flex items-center justify-center rounded-full
                         bg-danger-600 text-white text-[10px] font-bold leading-none
                         ring-2 ring-white dark:ring-gray-900">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        x-bind:style="window.innerWidth < 640
            ? { position: 'fixed', left: '8px', right: '8px',
                top: (document.querySelector('header')?.getBoundingClientRect().bottom ?? 56) + 4 + 'px',
                width: 'auto' }
            : {}"
        class="absolute right-0 top-full mt-2 z-50
               w-[min(28rem,calc(100vw-1rem))] max-h-[32rem]
               flex flex-col overflow-hidden
               rounded-xl border border-gray-200 bg-white shadow-xl
               dark:border-white/10 dark:bg-gray-900"
    >
        {{-- Header row (sticky) --}}
        <div class="flex-shrink-0 flex items-center justify-between px-4 py-3
                    border-b border-gray-100 dark:border-white/10">
            <span class="text-sm font-semibold text-gray-800 dark:text-white">
                Notifications
            </span>
            @if ($this->unreadCount > 0)
                <button
                    wire:click="markAllAsRead"
                    type="button"
                    class="text-xs text-primary-600 hover:text-primary-700
                           dark:text-primary-400 dark:hover:text-primary-300 font-medium"
                >
                    Mark all as read
                </button>
            @endif
        </div>

        {{-- Notification list (scrollable) --}}
        <div class="flex-1 overflow-y-auto p-2">
            @include('filament.partials.notification-list', [
                'notifications' => $this->notifications,
            ])
        </div>

        {{-- Footer: view all link (user panel only) --}}
        @if (auth()->user()?->role?->value && in_array(auth()->user()->role->value, ['faculty', 'student']))
            <div class="flex-shrink-0 border-t border-gray-100 dark:border-white/10 px-4 py-2.5 text-center">
                <a
                    href="{{ \App\Filament\Pages\User\UserProfile::getUrl() }}?activeProfileTab=notifications"
                    class="text-xs font-medium text-primary-600 hover:text-primary-700
                           dark:text-primary-400 dark:hover:text-primary-300"
                >
                    View all notifications
                </a>
            </div>
        @endif
    </div>

    {{-- Wire poll for live updates (5s) --}}
    <span wire:poll.5s="pollForNewNotifications" class="hidden"></span>
</div>
