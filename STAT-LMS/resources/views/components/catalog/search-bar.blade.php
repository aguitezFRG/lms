@props([
    'searchScope'       => 'all',
    'activeFilterCount' => 0,
    'filterPanelOpen'   => false,
])

{{-- ── 1. Search Bar Row ────────────────────────────────────────────── --}}
<div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center">

    {{-- Search scope custom dropdown --}}
    <div
        class="relative shrink-0"
        x-data="{
            open: false,
            value: $wire.entangle('searchScope').live,
            label: 'All Fields',
            options: [
                { value: 'all',     label: 'All Fields' },
                { value: 'title',   label: 'Title' },
                { value: 'author',  label: 'Author' },
                { value: 'keyword', label: 'Keyword' },
            ],
            select(val) { this.value = val; this.open = false; },
            init() {
                this.label = this.options.find(o => o.value === this.value)?.label ?? 'All Fields';
                this.$watch('value', () => {
                    this.label = this.options.find(o => o.value === this.value)?.label ?? 'All Fields';
                });
            }
        }"
        @click.outside="open = false"
        @keydown.escape="open = false"
    >
        <x-filament::button
            type="button"
            color="gray"
            @click="open = !open"
            class="flex items-center gap-2"
        >
            <span x-text="label"></span>
            <x-heroicon-m-chevron-down class="h-3.5 w-3.5 text-gray-400 transition-transform duration-150"
                x-bind:class="open ? 'rotate-180' : ''" />
        </x-filament::button>

        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
            class="absolute left-0 top-full z-50 mt-1 w-40 origin-top-left rounded-lg border border-gray-200 bg-white py-1 shadow-lg
                    dark:border-white/10 dark:bg-gray-800"
        >
            <template x-for="opt in options" :key="opt.value">
                <button
                    type="button"
                    @click="select(opt.value)"
                    class="flex w-full items-center px-3 py-2 text-sm transition
                            hover:bg-gray-100 dark:hover:bg-white/10"
                    :class="value === opt.value
                        ? 'font-semibold text-primary-700 dark:text-primary-400'
                        : 'text-gray-700 dark:text-gray-200'"
                    x-text="opt.label"
                ></button>
            </template>
        </div>
    </div>

    {{-- Search input --}}
    <div class="relative flex-1">
        <x-filament::input.wrapper class="w-full">
            <x-slot name="prefix">
                <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
            </x-slot>
            <x-filament::input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="{{ match($searchScope) {
                    'title'   => 'Search by title…',
                    'author'  => 'Search by author name…',
                    'keyword' => 'Search by keyword…',
                    default   => 'Search by title, author, or keyword…',
                } }}"
            />
        </x-filament::input.wrapper>
    </div>

    {{-- Filter toggle button --}}
    <x-filament::button
        type="button"
        wire:click="toggleFilterPanel"
        :color="$filterPanelOpen ? 'primary' : 'gray'"
        icon="heroicon-o-funnel"
        class="relative shrink-0"
    >
        <span>Filter</span>
        @if ($activeFilterCount > 0)
            <span @class([
                'flex h-4 w-4 items-center justify-center rounded-full text-xs font-bold',
                'bg-white text-primary-600' => $filterPanelOpen,
                'bg-primary-600 text-white dark:bg-primary-400' => ! $filterPanelOpen,
            ])>{{ $activeFilterCount }}</span>
        @endif
    </x-filament::button>

</div>
