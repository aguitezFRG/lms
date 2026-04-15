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
            <button
                type="button"
                @click="open = !open"
                class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition
                       hover:border-gray-400 focus:outline-none
                       dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:border-white/25"
            >
                <span x-text="label"></span>
                <x-heroicon-m-chevron-down class="h-3.5 w-3.5 text-gray-400 transition-transform duration-150"
                    x-bind:class="open ? 'rotate-180' : ''" />
            </button>

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

        {{-- Filter toggle button --}}
        <button
            type="button"
            wire:click="toggleFilterPanel"
            @class([
                'relative flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm shadow-sm transition focus:outline-none',
                'border-primary-600 bg-primary-600 text-white hover:bg-primary-700 dark:border-primary-400 dark:bg-primary-400 dark:hover:bg-primary-500' => $filterPanelOpen,
                'border-gray-300 bg-white text-gray-700 hover:border-gray-400 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:border-white/25' => ! $filterPanelOpen,
            ])
        >
            <x-heroicon-o-funnel class="h-4 w-4" />
            <span>Filter</span>
            @if ($activeFilterCount > 0)
                <span @class([
                    'flex h-4 w-4 items-center justify-center rounded-full text-xs font-bold',
                    'bg-white text-primary-600' => $filterPanelOpen,
                    'bg-primary-600 text-white dark:bg-primary-400' => ! $filterPanelOpen,
                ])>{{ $activeFilterCount }}</span>
            @endif
        </button>

        {{-- Search input --}}
        <div class="relative flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
            </div>
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="{{ match($searchScope) {
                    'title'   => 'Search by title…',
                    'author'  => 'Search by author name…',
                    'keyword' => 'Search by keyword…',
                    default   => 'Search by title, author, or keyword…',
                } }}"
                class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-4 text-sm shadow-sm
                       focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
                       dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder-gray-500"
            />
        </div>
    </div>
