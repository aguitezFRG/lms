@props([
    'activeFilterCount' => 0,
    'typeFilter'        => '',
    'formatFilter'      => '',
    'pubDateFrom'       => '',
    'pubDateTo'         => '',
    'sdgFilter'         => [],
    'availableOnly'     => true,
])

{{-- ── 3. Active Filter Chips (applied state only) ─────────────────────── --}}
@if ($activeFilterCount > 0)
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="text-xs font-medium text-gray-400">Active filters:</span>

        @if ($typeFilter !== '')
            @php $typeChipLabel = match((int)$typeFilter) { 1=>'Book', 2=>'Thesis', 3=>'Journal', 4=>'Dissertation', 5=>'Others', default=>'Type' }; @endphp
            <x-filament::badge color="primary">
                {{ $typeChipLabel }}
                <x-filament::icon-button
                    wire:click="removeFilter('typeFilter')"
                    icon="heroicon-m-x-mark"
                    size="xs"
                    color="primary"
                    class="ml-0.5"
                />
            </x-filament::badge>
        @endif

        @if ($formatFilter !== '')
            <x-filament::badge color="success">
                {{ $formatFilter === 'digital' ? 'Digital' : 'Physical' }}
                <x-filament::icon-button
                    wire:click="removeFilter('formatFilter')"
                    icon="heroicon-m-x-mark"
                    size="xs"
                    color="success"
                    class="ml-0.5"
                />
            </x-filament::badge>
        @endif

        @if ($pubDateFrom !== '' || $pubDateTo !== '')
            <x-filament::badge color="warning">
                {{ $pubDateFrom ?: '…' }} – {{ $pubDateTo ?: '…' }}
                <x-filament::icon-button
                    wire:click="removeFilter('pubDate')"
                    icon="heroicon-m-x-mark"
                    size="xs"
                    color="warning"
                    class="ml-0.5"
                />
            </x-filament::badge>
        @endif

        @foreach ($sdgFilter as $sdg)
            <x-filament::badge color="warning">
                SDG: {{ $sdg }}
                <x-filament::icon-button
                    wire:click="removeFilter('sdg', '{{ $sdg }}')"
                    icon="heroicon-m-x-mark"
                    size="xs"
                    color="warning"
                    class="ml-0.5"
                />
            </x-filament::badge>
        @endforeach

        @if (!$availableOnly)
            <x-filament::badge color="success">
                Including unavailable
                <x-filament::icon-button
                    wire:click="removeFilter('availableOnly')"
                    icon="heroicon-m-x-mark"
                    size="xs"
                    color="success"
                    class="ml-0.5"
                />
            </x-filament::badge>
        @endif

        <x-filament::button
            wire:click="clearAllFilters"
            color="danger"
            size="xs"
            class="ml-1"
        >
            Clear all
        </x-filament::button>
    </div>
@endif
