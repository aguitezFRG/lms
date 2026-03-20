<x-filament-panels::page>

    {{-- ── Profile Card ─────────────────────────────────────────────────── --}}
    <x-filament::section class="mb-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">

            {{-- Initials avatar --}}
            <div class="flex h-14 w-14 shrink-0 items-center justify-center
                        rounded-full bg-primary-600 text-xl font-bold text-white shadow">
                {{ $initials }}
            </div>

            {{-- Profile fields via Filament infolist --}}
            <div class="flex-1 min-w-0">
                {{ $this->profileInfolist }}
            </div>

        </div>
    </x-filament::section>

    {{-- ── Stats Row ────────────────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">

        <x-filament::section>
            <p class="text-xs font-semibold uppercase tracking-wider
                       text-warning-600 dark:text-warning-400">Pending</p>
            <p class="mt-1 text-3xl font-bold text-warning-700 dark:text-warning-300">
                {{ $pendingCount }}
            </p>
        </x-filament::section>

        <x-filament::section>
            <p class="text-xs font-semibold uppercase tracking-wider
                       text-success-700 dark:text-success-400">Active / Approved</p>
            <p class="mt-1 text-3xl font-bold text-success-800 dark:text-success-300">
                {{ $approvedCount }}
            </p>
        </x-filament::section>

        <x-filament::section>
            <p class="text-xs font-semibold uppercase tracking-wider
                       text-gray-500 dark:text-gray-400">Total Requests</p>
            <p class="mt-1 text-3xl font-bold text-gray-700 dark:text-gray-200">
                {{ $totalCount }}
            </p>
        </x-filament::section>

    </div>

    {{-- ── Tab Bar ──────────────────────────────────────────────────────── --}}
    <x-filament::tabs class="mb-6">

        <x-filament::tabs.item
            :active="$activeTab === 'pending'"
            icon="heroicon-o-clock"
            wire:click="setTab('pending')"
        >
            Pending
            @if ($pendingCount > 0)
                <x-filament::badge color="warning" class="ml-1.5">
                    {{ $pendingCount }}
                </x-filament::badge>
            @endif
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'approved'"
            icon="heroicon-o-check-circle"
            wire:click="setTab('approved')"
        >
            Approved
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'closed'"
            icon="heroicon-o-archive-box"
            wire:click="setTab('closed')"
        >
            Closed
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'notifications'"
            icon="heroicon-o-bell"
            wire:click="setTab('notifications')"
        >
            Notifications
            @if ($unreadCount > 0)
                <x-filament::badge color="danger" class="ml-1.5">
                    {{ $unreadCount }}
                </x-filament::badge>
            @endif
        </x-filament::tabs.item>

    </x-filament::tabs>

    {{-- ── Request Tabs (Table) ─────────────────────────────────────────── --}}
    @if (in_array($activeTab, ['pending', 'approved', 'closed']))
        {{ $this->table }}
    @endif

    {{-- ── Notifications Tab (Infolist) ────────────────────────────────── --}}
    @if ($activeTab === 'notifications')
        {{ $this->notificationsInfolist }}
    @endif

</x-filament-panels::page>