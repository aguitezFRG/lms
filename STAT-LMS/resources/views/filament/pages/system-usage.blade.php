<x-filament-panels::page>

    {{-- Auto-refresh polling (120s) --}}
    <span wire:poll.120s class="hidden"></span>

    {{-- Stats Overview Widget --}}
    <div class="mb-6">
        @livewire(\App\Filament\Widgets\SystemUsage\SystemUsageStatsWidget::class)
    </div>

    {{-- Table Tabs --}}
    <x-filament::tabs class="mb-6">
        <x-filament::tabs.item
            :active="$activeTab === 'materials'"
            icon="heroicon-o-document-text"
            wire:click="setTab('materials')"
        >
            Top Materials
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'trend'"
            icon="heroicon-o-chart-bar"
            wire:click="setTab('trend')"
        >
            Monthly Trend
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'users'"
            icon="heroicon-o-users"
            wire:click="setTab('users')"
        >
            Most Active Users
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{-- Tab Content --}}
    <div class="w-full">
        @if ($activeTab === 'materials')
            @livewire(\App\Filament\Widgets\SystemUsage\TopMaterialsTableWidget::class, key('materials'))
        @endif

        @if ($activeTab === 'trend')
            @livewire(\App\Filament\Widgets\SystemUsage\MonthlyTrendTableWidget::class, key('trend'))
        @endif

        @if ($activeTab === 'users')
            @livewire(\App\Filament\Widgets\SystemUsage\TopUsersTableWidget::class, key('users'))
        @endif
    </div>

</x-filament-panels::page>
