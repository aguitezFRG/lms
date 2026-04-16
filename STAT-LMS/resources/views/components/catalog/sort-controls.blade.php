@props([
    'totalResults' => 0,
    'sortDir'      => 'desc',
    'paginator'    => null,
])

{{-- ── 2. Sort + Result-count Row ──────────────────────────────────────── --}}
<div class="mb-3 flex flex-wrap items-center justify-between gap-2">

    <p class="text-xs text-gray-400">
        @if ($totalResults > 0)
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            of <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $totalResults }}</span> materials
        @else
            No materials found
        @endif
    </p>

    <div class="flex items-center gap-2">
        <span class="text-xs text-gray-400">Sort by</span>

        {{-- Sort by custom dropdown --}}
        <div
            class="relative"
            x-data="{
                open: false,
                value: $wire.entangle('sortBy').live,
                label: 'Publication Date',
                options: [
                    { value: 'publication_date', label: 'Publication Date' },
                    { value: 'created_at',       label: 'Date Added' },
                    { value: 'title',            label: 'Title' },
                    { value: 'author',           label: 'Author' },
                ],
                select(val) { this.value = val; this.open = false; },
                init() {
                    this.label = this.options.find(o => o.value === this.value)?.label ?? 'Publication Date';
                    this.$watch('value', () => {
                        this.label = this.options.find(o => o.value === this.value)?.label ?? 'Publication Date';
                    });
                }
            }"
            @click.outside="open = false"
            @keydown.escape="open = false"
        >
            <x-filament::button
                type="button"
                color="gray"
                outlined
                size="xs"
                @click="open = !open"
            >
                <span x-text="label"></span>
                <x-heroicon-m-chevron-down class="h-3 w-3 text-gray-400 transition-transform duration-150"
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
                class="absolute right-0 top-full z-50 mt-1 w-44 origin-top-right rounded-lg border border-gray-200 bg-white py-1 shadow-lg
                        dark:border-white/10 dark:bg-gray-800"
            >
                <template x-for="opt in options" :key="opt.value">
                    <button
                        type="button"
                        @click="select(opt.value)"
                        class="flex w-full items-center px-3 py-2 text-xs transition
                                hover:bg-gray-100 dark:hover:bg-white/10"
                        :class="value === opt.value
                            ? 'font-semibold text-primary-700 dark:text-primary-400'
                            : 'text-gray-700 dark:text-gray-200'"
                        x-text="opt.label"
                    ></button>
                </template>
            </div>
        </div>

        <x-filament::button
            wire:click="toggleSortDir"
            title="{{ $sortDir === 'desc' ? 'Descending — click for Ascending' : 'Ascending — click for Descending' }}"
            color="gray"
            outlined
            size="xs"
            :icon="$sortDir === 'desc' ? 'heroicon-o-arrow-down' : 'heroicon-o-arrow-up'"
            icon-position="before"
        >
            {{ $sortDir === 'desc' ? 'Desc' : 'Asc' }}
        </x-filament::button>
    </div>
</div>
