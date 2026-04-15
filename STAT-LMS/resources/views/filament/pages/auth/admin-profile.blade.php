<x-filament-panels::page>

    {{-- Auto-refresh polling (60s) --}}
    <span wire:poll.60s class="hidden"></span>

    {{-- ── Profile Card ─────────────────────────────────────────────────── --}}
    <x-filament::section class="mb-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">

            {{-- Avatar icon circle --}}
            <div class="flex h-16 w-16 shrink-0 items-center justify-center
                        rounded-full bg-primary-800 shadow">
                <x-heroicon-o-user class="h-8 w-8 text-white" />
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
            wire:click="setTab('history')"
        >
            Access History
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'notifications'"
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

    {{-- ── Notifications Tab ────────────────────────────────────── --}}
    @if ($activeTab === 'notifications')
        @include('filament.partials.notification-list', ['notifications' => $notifications])
    @endif

</x-filament-panels::page>