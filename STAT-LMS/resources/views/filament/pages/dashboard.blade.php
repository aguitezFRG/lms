<x-filament-panels::page>

    {{-- Auto-refresh polling (30s) --}}
    <span wire:poll.30s class="hidden"></span>

    {{-- Tab Bar --}}
    <x-filament::tabs class="mb-6">

        @if ($canViewGeneral)
            <x-filament::tabs.item
                :active="$activeTab === 'general'"
                wire:click="setTab('general')"
            >
                General
            </x-filament::tabs.item>
        @endif

        @if ($canViewBorrows)
            <x-filament::tabs.item
                :active="$activeTab === 'borrows'"
                wire:click="setTab('borrows')"
            >
                Borrow Requests
                @if ($pendingBorrowCount > 0)
                    <x-filament::badge color="warning" class="ml-1.5">
                        {{ $pendingBorrowCount }}
                    </x-filament::badge>
                @endif
            </x-filament::tabs.item>
        @endif

        @if ($canViewAccess)
            <x-filament::tabs.item
                :active="$activeTab === 'access'"
                wire:click="setTab('access')"
            >
                Access Requests
                @if ($pendingAccessCount > 0)
                    <x-filament::badge color="danger" class="ml-1.5">
                        {{ $pendingAccessCount }}
                    </x-filament::badge>
                @endif
            </x-filament::tabs.item>
        @endif

    </x-filament::tabs>

    @if ($activeTab === 'general' && $canViewGeneral)
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />
    @endif

    @if ($activeTab === 'borrows' && $canViewBorrows)
        @livewire(\App\Filament\Widgets\Dashboard\PendingBorrowsWidget::class, key('borrows'))
    @endif

    @if ($activeTab === 'access' && $canViewAccess)
        @livewire(\App\Filament\Widgets\Dashboard\PendingAccessesWidget::class, key('access'))
    @endif
</x-filament-panels::page>