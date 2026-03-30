<x-filament-panels::page>
<style>[x-cloak] { display: none !important; }</style>
<div x-data="{ filtersOpen: false }" @keydown.escape.window="filtersOpen = false">

    {{-- ── 1. Search Bar Row ────────────────────────────────────────────── --}}
    <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center">

        {{-- Search scope selector --}}
        <select
            wire:model.live="searchScope"
            class="shrink-0 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm
                   focus:border-primary-500 focus:outline-none
                   dark:border-white/10 dark:bg-white/5 dark:text-white"
        >
            <option value="all">All Fields</option>
            <option value="title">Title</option>
            <option value="author">Author</option>
            <option value="keyword">Keyword</option>
        </select>

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

        {{-- Filter toggle button (highlighted when filters are active) --}}
        <button
            @click="filtersOpen = true"
            @class([
                'flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium shadow-sm transition',
                'border-primary-500 bg-primary-50 text-primary-700 dark:border-primary-400 dark:bg-primary-900/30 dark:text-primary-300' => $activeFilterCount > 0,
                'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300' => $activeFilterCount === 0,
            ])
        >
            <x-heroicon-o-adjustments-horizontal class="h-4 w-4" />
            Filters
            @if ($activeFilterCount > 0)
                <span class="flex h-5 w-5 items-center justify-center rounded-full
                             bg-primary-600 text-xs font-bold text-white dark:bg-primary-400">
                    {{ $activeFilterCount }}
                </span>
            @endif
        </button>
    </div>

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
            <select
                wire:model.live="sortBy"
                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs shadow-sm
                       focus:border-primary-500 focus:outline-none
                       dark:border-white/10 dark:bg-white/5 dark:text-white"
            >
                <option value="created_at">Date Added</option>
                <option value="publication_date">Publication Date</option>
                <option value="title">Title</option>
                <option value="author">Author</option>
            </select>

            <button
                wire:click="toggleSortDir"
                title="{{ $sortDir === 'desc' ? 'Descending — click for Ascending' : 'Ascending — click for Descending' }}"
                class="flex items-center rounded-lg border border-gray-300 bg-white p-1.5 text-gray-500
                       shadow-sm transition hover:bg-gray-50
                       dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
            >
                @if ($sortDir === 'desc')
                    <x-heroicon-o-arrow-down class="h-4 w-4" />
                @else
                    <x-heroicon-o-arrow-up class="h-4 w-4" />
                @endif
            </button>
        </div>
    </div>

    {{-- ── 3. Active Filter Chips ───────────────────────────────────────────── --}}
    @if ($activeFilterCount > 0)
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-400">Active filters:</span>

            @if ($typeFilter !== '')
                @php $typeChipLabel = match((int)$typeFilter) { 1=>'Book', 2=>'Thesis', 3=>'Journal', 4=>'Dissertation', 5=>'Others', default=>'Type' }; @endphp
                <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1
                             text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                    {{ $typeChipLabel }}
                    <button wire:click="$set('typeFilter', '')" class="ml-0.5 hover:text-blue-900">
                        <x-heroicon-m-x-mark class="h-3 w-3" />
                    </button>
                </span>
            @endif

            @if ($formatFilter !== '')
                <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-1
                             text-xs font-medium text-teal-700 dark:bg-teal-900/40 dark:text-teal-300">
                    {{ $formatFilter === 'digital' ? 'Digital' : 'Physical' }}
                    <button wire:click="$set('formatFilter', '')" class="ml-0.5 hover:text-teal-900">
                        <x-heroicon-m-x-mark class="h-3 w-3" />
                    </button>
                </span>
            @endif

            @if ($pubDateFrom !== '' || $pubDateTo !== '')
                <span class="inline-flex items-center gap-1 rounded-full bg-violet-100 px-2.5 py-1
                             text-xs font-medium text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">
                    {{ $pubDateFrom ?: '…' }} – {{ $pubDateTo ?: '…' }}
                    <button wire:click="$set('pubDateFrom', ''); $set('pubDateTo', '')"
                            class="ml-0.5 hover:text-violet-900">
                        <x-heroicon-m-x-mark class="h-3 w-3" />
                    </button>
                </span>
            @endif

            @foreach ($sdgFilter as $sdg)
                <span class="inline-flex items-center gap-1 rounded-full bg-warning-100 px-2.5 py-1
                             text-xs font-medium text-warning-700 dark:bg-warning-900/40 dark:text-warning-300">
                    SDG: {{ $sdg }}
                    <button wire:click="toggleSdg('{{ $sdg }}')" class="ml-0.5 hover:text-warning-900">
                        <x-heroicon-m-x-mark class="h-3 w-3" />
                    </button>
                </span>
            @endforeach

            @if ($availableOnly)
                <span class="inline-flex items-center gap-1 rounded-full bg-success-100 px-2.5 py-1
                             text-xs font-medium text-success-700 dark:bg-success-900/40 dark:text-success-300">
                    Available only
                    <button wire:click="$set('availableOnly', false)" class="ml-0.5 hover:text-success-900">
                        <x-heroicon-m-x-mark class="h-3 w-3" />
                    </button>
                </span>
            @endif

            <button wire:click="clearAllFilters"
                    class="ml-1 text-xs font-medium text-gray-400 underline underline-offset-2
                           hover:text-danger-600 dark:hover:text-danger-400">
                Clear all
            </button>
        </div>
    @endif

    {{-- ── 4. Filter Modal ──────────────────────────────────────────────────── --}}
    {{-- wire:ignore.self: Livewire skips morphing this node's attributes/children
         so Alpine's filtersOpen state and the @class on the pill buttons inside
         are not reset on every Livewire round-trip. --}}
    <div
        x-show="filtersOpen"
        x-cloak
        wire:ignore.self
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                @click="filtersOpen = false"
            ></div>

            {{-- Modal panel — no x-show here; the outer overlay's x-show handles
                 visibility. Keeping this node always in DOM lets Livewire morph
                 the @class pill buttons correctly on every re-render. --}}
            <div
                wire:key="filter-modal-panel"
                class="relative z-10 w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl
                       bg-white shadow-2xl ring-1 ring-black/5
                       dark:bg-gray-900 dark:ring-white/10"
            >
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4
                            dark:border-white/10">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-adjustments-horizontal class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                            Filter Catalog
                        </h2>
                        @if ($activeFilterCount > 0)
                            <span class="flex h-5 w-5 items-center justify-center rounded-full
                                         bg-primary-600 text-xs font-bold text-white dark:bg-primary-400">
                                {{ $activeFilterCount }}
                            </span>
                        @endif
                    </div>
                    <button
                        @click="filtersOpen = false"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100
                               hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                    >
                        <x-heroicon-m-x-mark class="h-5 w-5" />
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">

                    {{-- Material Type --}}
                    <div class="sm:col-span-2">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Material Type
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (['' => 'All', '1' => 'Book', '2' => 'Thesis', '3' => 'Journal', '4' => 'Dissertation', '5' => 'Others'] as $val => $label)
                                <button
                                    wire:click="$set('typeFilter', '{{ $val }}')"
                                    @class([
                                        'rounded-full px-3 py-1 text-xs font-medium transition border',
                                        'border-primary-600 bg-primary-600 text-white dark:border-primary-400 dark:bg-primary-400' => $typeFilter === (string) $val,
                                        'border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:bg-gray-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-300' => $typeFilter !== (string) $val,
                                    ])
                                >{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Format --}}
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Format
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ([
                                ''         => ['label' => 'All Formats', 'icon' => 'heroicon-o-squares-2x2'],
                                'digital'  => ['label' => 'Digital',     'icon' => 'heroicon-o-computer-desktop'],
                                'physical' => ['label' => 'Physical',    'icon' => 'heroicon-o-book-open'],
                            ] as $val => $cfg)
                                <button
                                    wire:click="$set('formatFilter', '{{ $val }}')"
                                    @class([
                                        'flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition border',
                                        'border-primary-600 bg-primary-600 text-white dark:border-primary-400 dark:bg-primary-400' => $formatFilter === (string) $val,
                                        'border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:bg-gray-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-300' => $formatFilter !== (string) $val,
                                    ])
                                >
                                    <x-dynamic-component :component="$cfg['icon']" class="h-3 w-3" />
                                    {{ $cfg['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Availability --}}
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Availability
                        </p>
                        <label class="flex cursor-pointer items-center gap-2.5">
                            <div class="relative">
                                <input type="checkbox" wire:model.live="availableOnly" class="sr-only peer" />
                                <div class="h-5 w-9 rounded-full bg-gray-200 transition peer-checked:bg-primary-600
                                            dark:bg-gray-700 dark:peer-checked:bg-primary-500
                                            after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4
                                            after:rounded-full after:bg-white after:transition
                                            peer-checked:after:translate-x-4"></div>
                            </div>
                            <span class="text-sm text-gray-600 dark:text-gray-300">Show only available copies</span>
                        </label>
                    </div>

                    {{-- Publication Date Range --}}
                    <div class="sm:col-span-2">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Publication Date
                        </p>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div class="flex flex-1 items-center gap-2">
                                <label class="w-8 shrink-0 text-xs text-gray-400">From</label>
                                <input
                                    wire:model.live="pubDateFrom"
                                    type="date"
                                    max="{{ date('Y-m-d') }}"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm
                                           focus:border-primary-500 focus:outline-none
                                           dark:border-white/10 dark:bg-white/5 dark:text-white dark:[color-scheme:dark]"
                                />
                            </div>
                            <span class="hidden text-gray-300 sm:block">—</span>
                            <div class="flex flex-1 items-center gap-2">
                                <label class="w-8 shrink-0 text-xs text-gray-400">To</label>
                                <input
                                    wire:model.live="pubDateTo"
                                    type="date"
                                    max="{{ date('Y-m-d') }}"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm
                                           focus:border-primary-500 focus:outline-none
                                           dark:border-white/10 dark:bg-white/5 dark:text-white dark:[color-scheme:dark]"
                                />
                            </div>
                            @if ($pubDateFrom !== '' || $pubDateTo !== '')
                                <button
                                    wire:click="$set('pubDateFrom', ''); $set('pubDateTo', '')"
                                    class="shrink-0 text-xs text-gray-400 underline underline-offset-2 hover:text-danger-500"
                                >Clear</button>
                            @endif
                        </div>
                    </div>

                    {{-- SDG Filter --}}
                    <div class="sm:col-span-2">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Sustainable Development Goals (SDGs)
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            @php
                                $sdgs = [
                                    'No Poverty', 'Zero Hunger', 'Good Health and Well-being',
                                    'Quality Education', 'Gender Equality', 'Clean Water and Sanitation',
                                    'Affordable and Clean Energy', 'Decent Work and Economic Growth',
                                    'Industry, Innovation and Infrastructure', 'Reduced Inequality',
                                    'Sustainable Cities and Communities',
                                    'Responsible Consumption and Production', 'Climate Action',
                                    'Life Below Water', 'Life on Land',
                                    'Peace, Justice and Strong Institutions',
                                    'Partnerships for the Goals',
                                ];
                            @endphp
                            @foreach ($sdgs as $sdg)
                                @php $active = in_array($sdg, $sdgFilter); @endphp
                                <button
                                    wire:click="toggleSdg('{{ $sdg }}')"
                                    @class([
                                        'rounded-full px-2.5 py-1 text-xs font-medium transition border',
                                        'border-warning-500 bg-warning-500 text-white dark:border-warning-400' => $active,
                                        'border-gray-200 bg-gray-50 text-gray-600 hover:border-warning-300 hover:bg-warning-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300' => ! $active,
                                    ])
                                >{{ $sdg }}</button>
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-white/10">
                    <button
                        wire:click="clearAllFilters"
                        class="rounded-lg border border-danger-200 px-4 py-2 text-xs font-medium text-danger-600
                               transition hover:bg-danger-50 dark:border-danger-500/40 dark:text-danger-400
                               dark:hover:bg-danger-500/10"
                    >
                        Clear All Filters
                    </button>
                    <button
                        @click="filtersOpen = false"
                        class="rounded-lg bg-primary-600 px-5 py-2 text-xs font-semibold text-white shadow-sm
                               transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
                    >
                        Apply & Close
                    </button>
                </div>
            </div>
    </div>

    {{-- ── 5. Card List (full-width, availability-bordered) ─────────────────── --}}
    @if (count($materials))
        <div class="flex flex-col gap-6">
            @foreach ($materials as $m)
                @php
                    /* — type label & badge colour — */
                    $typeLabel = match ((int) $m['material_type']) {
                        1 => 'Book', 2 => 'Thesis', 3 => 'Journal',
                        4 => 'Dissertation', default => 'Other',
                    };
                    $typeBg = match ((int) $m['material_type']) {
                        1 => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                        2 => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                        3 => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                        4 => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                    };

                    /* — availability — drives left border colour — */
                    $isAvailable = $m['has_digital'] || $m['has_physical'];
                    $borderColour = $isAvailable
                        ? 'border-l-green-500 dark:border-l-green-400'
                        : 'border-l-amber-400 dark:border-l-amber-500';

                    /* — keyword slicing — */
                    $kwAll    = array_values(array_filter((array) $m['keywords']));
                    $kwShown  = array_slice($kwAll, 0, 3);
                    $kwExtra  = count($kwAll) - count($kwShown);
                @endphp

                <a href="{{ $m['view_url'] }}"
                   class="group flex w-full rounded-xl border border-gray-200 border-l-4 {{ $borderColour }}
                          bg-white px-5 py-4 shadow-sm transition-all duration-150
                          hover:-translate-y-0.5 hover:shadow-md
                          dark:border-gray-700/60 dark:bg-white/5">

                    <div class="flex-1 min-w-0">

                        {{-- Title --}}
                        <h3 class="text-base font-bold leading-snug text-gray-800
                                   transition-colors group-hover:text-primary-600
                                   dark:text-white dark:group-hover:text-primary-400">
                            {{ $m['title'] }}
                        </h3>

                        {{-- Author --}}
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                            {{ $m['author'] }}
                        </p>

                        {{-- Badge row —————————————————————————————————————— --}}
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">

                            {{-- Availability badge --}}
                            @if ($isAvailable)
                                <span class="inline-flex items-center gap-1 rounded-full
                                             bg-green-100 px-2.5 py-0.5
                                             text-xs font-semibold text-green-700
                                             dark:bg-green-900/40 dark:text-green-300">
                                    <x-heroicon-m-check-circle class="h-3 w-3" />
                                    Available
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full
                                             bg-amber-100 px-2.5 py-0.5
                                             text-xs font-semibold text-amber-700
                                             dark:bg-amber-900/40 dark:text-amber-300">
                                    <x-heroicon-m-clock class="h-3 w-3" />
                                    Unavailable
                                </span>
                            @endif

                            {{-- Format badges --}}
                            @if ($m['has_digital'])
                                <span class="inline-flex items-center gap-1 rounded-full
                                             bg-sky-100 px-2.5 py-0.5
                                             text-xs font-medium text-sky-700
                                             dark:bg-sky-900/40 dark:text-sky-300">
                                    <x-heroicon-o-computer-desktop class="h-3 w-3" />
                                    Digital
                                </span>
                            @endif
                            @if ($m['has_physical'])
                                <span class="inline-flex items-center gap-1 rounded-full
                                             bg-indigo-100 px-2.5 py-0.5
                                             text-xs font-medium text-indigo-700
                                             dark:bg-indigo-900/40 dark:text-indigo-300">
                                    <x-heroicon-o-book-open class="h-3 w-3" />
                                    Physical
                                </span>
                            @endif

                            {{-- Material type --}}
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $typeBg }}">
                                {{ $typeLabel }}
                            </span>

                            {{-- Keywords (first 3 + overflow count) --}}
                            @foreach ($kwShown as $kw)
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5
                                             text-xs text-gray-500
                                             dark:bg-white/10 dark:text-gray-400">
                                    {{ $kw }}
                                </span>
                            @endforeach
                            @if ($kwExtra > 0)
                            @php
                                /* Build the tooltip string from the keywords that were sliced off */
                                $kwHidden = array_slice($kwAll, 3);
                                $kwTooltip = implode(', ', $kwHidden);
                            @endphp

                            <span
                                x-data="{ open: false }"
                                class="relative"
                                @mouseenter="open = true"
                                @mouseleave="open = false"
                                @focusin="open = true"
                                @focusout="open = false"
                            >
                                {{-- Pill trigger --}}
                                <span tabindex="0"
                                    class="cursor-default rounded-full bg-gray-100 px-2.5 py-0.5
                                            text-xs text-gray-400 select-none
                                            dark:bg-white/10 dark:text-gray-500">
                                    +{{ $kwExtra }} keywords
                                </span>

                                {{-- Tooltip bubble --}}
                                <span
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-1"
                                    class="absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2
                                        w-max max-w-[14rem] rounded-lg bg-gray-900 px-3 py-2
                                        text-xs leading-relaxed text-white shadow-lg
                                        dark:bg-gray-700"
                                    role="tooltip"
                                >
                                    {{ $kwTooltip }}

                                    {{-- Arrow --}}
                                    <span class="absolute left-1/2 top-full -translate-x-1/2
                                                border-4 border-transparent border-t-gray-900
                                                dark:border-t-gray-700">
                                    </span>
                                </span>
                            </span>
                        @endif

                        </div>
                        {{-- /Badge row ————————————————————————————————————— --}}

                        {{-- Abstract excerpt --}}
                        @if (!empty($m['abstract']))
                            <p class="mt-2.5 line-clamp-2 text-sm leading-relaxed
                                      text-gray-600 dark:text-gray-300">
                                {{ $m['abstract'] }}
                            </p>
                        @endif

                        {{-- Footer: publication year · UUID --}}
                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5
                                    text-xs text-gray-400 dark:text-gray-500">
                            @if ($m['publication_date'])
                                <span>{{ $m['publication_date'] }}</span>
                                <!-- <span aria-hidden="true">•</span> -->
                            @endif
                            <!-- <span>ID:&nbsp;<span class="font-mono">{{ $m['id'] }}</span></span> -->
                        </div>

                    </div>{{-- /flex-1 --}}
                </a>

            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($paginator->hasPages())
            <div class="mt-8 flex flex-wrap items-center justify-center gap-1">
                <button
                    wire:click="previousPage"
                    @disabled($paginator->onFirstPage())
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 shadow-sm
                           transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40
                           dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                >&larr; Prev</button>

                @foreach (range(1, $paginator->lastPage()) as $p)
                    <button
                        wire:click="goToPage({{ $p }})"
                        class="rounded-lg border px-3 py-1.5 text-sm transition
                               {{ $paginator->currentPage() === $p
                                   ? 'border-primary-600 bg-primary-600 text-white shadow-sm'
                                   : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300' }}"
                    >{{ $p }}</button>
                @endforeach

                <button
                    wire:click="nextPage"
                    @disabled(! $paginator->hasMorePages())
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 shadow-sm
                           transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40
                           dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                >Next &rarr;</button>
            </div>
        @endif

    @else
        {{-- Empty state --}}
        <div class="rounded-xl border border-dashed border-gray-300 bg-white py-20 text-center
                    dark:border-white/10 dark:bg-white/5">
            <x-heroicon-o-document-magnifying-glass class="mx-auto mb-4 h-10 w-10 text-gray-300" />
            <p class="text-sm font-medium text-gray-500">No materials found</p>
            <p class="mt-1 text-xs text-gray-400">Try adjusting your search or clearing some filters.</p>
        </div>
    @endif

</div>{{-- /x-data --}}
</x-filament-panels::page>