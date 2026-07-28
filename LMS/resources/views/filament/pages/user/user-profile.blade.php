<x-filament-panels::page>

    {{-- ── Tab Navigation ───────────────────────────────────────────────────── --}}
    <x-filament::tabs class="mb-6">
        <x-filament::tabs.item
            :active="$activeProfileTab === 'details'"
            icon="heroicon-o-user"
            wire:click="setProfileTab('details')"
        >
            Personal Details
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeProfileTab === 'notifications'"
            icon="heroicon-o-bell"
            wire:click="setProfileTab('notifications')"
        >
            Notifications
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{-- ── Personal Details Tab ─────────────────────────────────────────────── --}}
    @if ($activeProfileTab === 'details')
        <x-filament::section class="mb-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">

                <div class="flex h-16 w-16 shrink-0 items-center justify-center
                            rounded-full bg-primary-800 shadow">
                    <x-heroicon-o-user class="h-8 w-8 text-white" />
                </div>

                <div class="flex-1 min-w-0">
                    {{ $this->profileInfolist }}
                </div>

            </div>
        </x-filament::section>
    @endif

    {{-- ── Notifications Tab ────────────────────────────────────────────────── --}}
    @if ($activeProfileTab === 'notifications')
        <x-filament::section>
            {{-- Filter bar --}}
            <div class="flex flex-wrap items-center gap-4 mb-5">

                {{-- Read/Unread filter --}}
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mr-1">Status:</span>
                    @foreach (['all' => 'All', 'unread' => 'Unread', 'read' => 'Read'] as $val => $label)
                        <button
                            type="button"
                            wire:click="setNotifReadFilter('{{ $val }}')"
                            @class([
                                'px-3 py-1 rounded-full text-xs font-medium transition-colors',
                                'bg-primary-600 text-white'     => $notifReadFilter === $val,
                                'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20' => $notifReadFilter !== $val,
                            ])
                        >{{ $label }}</button>
                    @endforeach
                </div>

                {{-- Type filter --}}
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mr-1">Type:</span>
                    @foreach ([
                        'all'                     => 'All',
                        'request_status_changed'  => 'Request',
                        'access_level_changed'    => 'Revocation',
                        'account_details_changed' => 'Account',
                        'borrow_due_soon'         => 'Borrow',
                    ] as $val => $label)
                        <button
                            type="button"
                            wire:click="setNotifTypeFilter('{{ $val }}')"
                            @class([
                                'px-3 py-1 rounded-full text-xs font-medium transition-colors',
                                'bg-primary-600 text-white'     => $notifTypeFilter === $val,
                                'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20' => $notifTypeFilter !== $val,
                            ])
                        >{{ $label }}</button>
                    @endforeach
                </div>

                {{-- Mark all read --}}
                @if ($this->allUnreadCount > 0)
                    <button
                        type="button"
                        wire:click="markAllProfileNotifRead"
                        class="ml-auto text-xs font-medium text-primary-600 hover:text-primary-700
                               dark:text-primary-400 dark:hover:text-primary-300"
                    >
                        Mark all as read
                    </button>
                @endif
            </div>

            {{-- Notification list --}}
            @include('filament.partials.notification-list', [
                'notifications' => $this->allNotifications,
                'markReadMethod' => 'markProfileNotifRead',
            ])
        </x-filament::section>
    @endif

</x-filament-panels::page>
