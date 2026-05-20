@props([
    'activeFilterCount' => 0,
    'typeFilter'        => '',
    'formatFilter'      => '',
    'adviserFilter'     => '',
    'keywordFilter'     => '',
    'pubDateFrom'       => '',
    'pubDateTo'         => '',
    'sdgFilter'         => [],
    'availableOnly'     => true,
])

@php
$chip = fn(string $color) => match($color) {
    'primary' => 'bg-primary-50 text-primary-700 ring-primary-700/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30',
    'success'  => 'bg-success-50 text-success-700 ring-success-700/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
    'info'     => 'bg-info-50 text-info-700 ring-info-700/10 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30',
    'warning'  => 'bg-warning-50 text-warning-700 ring-warning-700/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30',
    default    => 'bg-gray-50 text-gray-700 ring-gray-700/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30',
};
$chipBase = 'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset cursor-pointer transition-opacity hover:opacity-70 focus:outline-none';
@endphp

{{-- ── 3. Active Filter Chips (applied state only) ─────────────────────── --}}
@if ($activeFilterCount > 0)
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="text-xs font-medium text-gray-400">Active filters:</span>

        @if ($typeFilter !== '')
            @php $typeChipLabel = match((int)$typeFilter) { 1=>'Book', 2=>'Thesis', 3=>'Journal', 4=>'Dissertation', 5=>'Others', default=>'Type' }; @endphp
            <button
                wire:click="removeFilter('typeFilter')"
                x-data x-tooltip="'Remove Filter'"
                class="{{ $chipBase }} {{ $chip('primary') }}"
            >{{ $typeChipLabel }}</button>
        @endif

        @if ($formatFilter !== '')
            <button
                wire:click="removeFilter('formatFilter')"
                x-data x-tooltip="'Remove Filter'"
                class="{{ $chipBase }} {{ $chip('success') }}"
            >{{ $formatFilter === 'digital' ? 'Digital' : 'Physical' }}</button>
        @endif

        @if ($adviserFilter !== '')
            <button
                wire:click="removeFilter('adviserFilter')"
                x-data x-tooltip="'Remove Filter'"
                class="{{ $chipBase }} {{ $chip('info') }}"
            >Adviser: {{ $adviserFilter }}</button>
        @endif

        @if ($keywordFilter !== '')
            <button
                wire:click="removeFilter('keywordFilter')"
                x-data x-tooltip="'Remove Filter'"
                class="{{ $chipBase }} {{ $chip('info') }}"
            >Keywords: {{ $keywordFilter }}</button>
        @endif

        @if ($pubDateFrom !== '' || $pubDateTo !== '')
            <button
                wire:click="removeFilter('pubDate')"
                x-data x-tooltip="'Remove Filter'"
                class="{{ $chipBase }} {{ $chip('warning') }}"
            >{{ $pubDateFrom ?: '…' }} – {{ $pubDateTo ?: '…' }}</button>
        @endif

        @foreach ($sdgFilter as $sdg)
            <button
                wire:click="removeFilter('sdg', '{{ $sdg }}')"
                x-data x-tooltip="'Remove Filter'"
                class="{{ $chipBase }} {{ $chip('warning') }}"
            >SDG: {{ $sdg }}</button>
        @endforeach

        @if (!$availableOnly)
            <button
                wire:click="removeFilter('availableOnly')"
                x-data x-tooltip="'Remove Filter'"
                class="{{ $chipBase }} {{ $chip('success') }}"
            >Including unavailable</button>
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
