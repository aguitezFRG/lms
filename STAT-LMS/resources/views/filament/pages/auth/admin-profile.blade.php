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

    {{-- ── Tab Bar ──────────────────────────────────────────────────────── --}}
    <x-filament::tabs class="mb-6">

        <x-filament::tabs.item
            :active="$activeTab === 'history'"
            icon="heroicon-o-clock"
            wire:click="setTab('history')"
        >
            Access History
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

    {{-- ── History Tab (Table) ─────────────────────────────────────────── --}}
    @if ($activeTab === 'history')
        {{ $this->table }}
    @endif

    {{-- ── Notifications Tab (Infolist) ────────────────────────────────── --}}
    @if ($activeTab === 'notifications')
        {{ $this->notificationsInfolist }}
    @endif

</x-filament-panels::page>