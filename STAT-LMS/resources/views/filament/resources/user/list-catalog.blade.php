<x-filament-panels::page>

    {{-- ── Search & Filters ─────────────────────────────────────────────── --}}
    {{-- ── Search & Filters ─────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">

        {{-- Search with OPAC tooltip --}}
        <div
            class="relative flex-1"
            x-data="{ tip: false }"
            @mouseenter="tip = true"
            @mouseleave="tip = false"
            @focusin="tip = true"
            @focusout="tip = false"
        >
            {{-- ── Tooltip popover ──────────────────────────────────────── --}}
            {{-- ── Tooltip popover ──────────────────────────────────────── --}}
            <div
                x-show="tip"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-1"
                class="absolute top-full left-0 z-50 mt-2 w-[520px] max-w-[90vw] rounded-xl
                       border border-gray-200 bg-white shadow-xl
                       dark:border-white/10 dark:bg-gray-800"
            >
                {{-- Header --}}
                <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-2.5
                            dark:border-white/10">
                    <x-heroicon-o-magnifying-glass class="h-3.5 w-3.5 text-primary-500 dark:text-primary-400" />
                    <span class="text-xs font-semibold uppercase tracking-wider
                                 text-primary-600 dark:text-primary-400">
                        OPAC Search Syntax
                    </span>
                </div>

                {{-- Grid of examples --}}
                <div class="grid grid-cols-2 gap-x-6 gap-y-3 px-4 py-3">

                    {{-- Col 1 --}}
                    <div class="space-y-3">
                        <div>
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs
                                         text-gray-800 dark:bg-white/10 dark:text-gray-100">
                                regression analysis
                            </code>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                AND — both words must appear
                            </p>
                        </div>
                        <div>
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs
                                         text-gray-800 dark:bg-white/10 dark:text-gray-100">
                                "time series"
                            </code>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Exact phrase match
                            </p>
                        </div>
                        <div>
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs
                                         text-gray-800 dark:bg-white/10 dark:text-gray-100">
                                ti:bayesian
                            </code>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Search <strong class="text-gray-700 dark:text-gray-200">title</strong> only
                            </p>
                        </div>
                        <div>
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs
                                         text-gray-800 dark:bg-white/10 dark:text-gray-100">
                                au:santos
                            </code>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Search <strong class="text-gray-700 dark:text-gray-200">author</strong> only
                            </p>
                        </div>
                    </div>

                    {{-- Col 2 --}}
                    <div class="space-y-3">
                        <div>
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs
                                         text-gray-800 dark:bg-white/10 dark:text-gray-100">
                                kw:ANOVA
                            </code>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Search <strong class="text-gray-700 dark:text-gray-200">keywords</strong> only
                            </p>
                        </div>
                        <div>
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs
                                         text-gray-800 dark:bg-white/10 dark:text-gray-100">
                                adv:reyes
                            </code>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Search <strong class="text-gray-700 dark:text-gray-200">adviser</strong> only
                            </p>
                        </div>
                        <div>
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs
                                         text-gray-800 dark:bg-white/10 dark:text-gray-100">
                                ab:multivariate
                            </code>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Search <strong class="text-gray-700 dark:text-gray-200">abstract</strong> only
                            </p>
                        </div>
                        <div>
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs
                                         text-gray-800 dark:bg-white/10 dark:text-gray-100">
                                ti:survival au:cruz
                            </code>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Combine multiple prefixes
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Footer hint --}}
                <div class="border-t border-gray-100 px-4 py-2 dark:border-white/10">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Results ranked by relevance: title › author › keywords › adviser › abstract
                    </p>
                </div>

                {{-- Arrow pointing up toward the input --}}
                <div class="absolute -top-1.5 left-6 h-3 w-3 rotate-45 rounded-sm
                            border-l border-t border-gray-200 bg-white
                            dark:border-white/10 dark:bg-gray-800"></div>
            </div>

            {{-- Search icon --}}
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
            </div>

            {{-- Input --}}
            <input
                wire:model.live.debounce.400ms="search"
                type="search"
                placeholder='Search… e.g.  "time series"  ti:bayesian  au:santos  kw:ANOVA'
                class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-9 text-sm shadow-sm
                       focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
                       dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder-gray-500"
            />

            {{-- Clear button --}}
            @if ($search)
                <button
                    wire:click="$set('search', '')"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400
                           hover:text-gray-600 dark:hover:text-gray-300"
                    title="Clear search"
                >
                    <x-heroicon-o-x-circle class="h-4 w-4" />
                </button>
            @endif
        </div>

        {{-- Type filter --}}
        <select
            wire:model.live="typeFilter"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm
                   focus:border-primary-500 focus:outline-none
                   dark:border-white/10 dark:bg-white/5 dark:text-white"
        >
            <option value="">All Types</option>
            <option value="1">Book</option>
            <option value="2">Thesis</option>
            <option value="3">Journal</option>
            <option value="4">Dissertation</option>
            <option value="5">Others</option>
        </select>

        {{-- Format filter --}}
        <select
            wire:model.live="formatFilter"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm
                   focus:border-primary-500 focus:outline-none
                   dark:border-white/10 dark:bg-white/5 dark:text-white"
        >
            <option value="">All Formats</option>
            <option value="digital">Digital Available</option>
            <option value="physical">Physical Available</option>
        </select>
    </div>

    {{-- ── Result count + active query hint ───────────────────────────────── --}}
    <div class="mb-4 flex items-center gap-2 text-xs text-gray-400">
        @if ($totalResults > 0)
            <span>
                Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
                of {{ $totalResults }} {{ Str::plural('result', $totalResults) }}
            </span>
        @else
            <span>No materials found</span>
        @endif

        @if ($search)
            <span class="text-gray-300 dark:text-gray-600">·</span>
            <span>
                Query: <em class="font-medium text-gray-500 dark:text-gray-300">{{ $search }}</em>
            </span>
        @endif
    </div>

    {{-- ── Card Grid ────────────────────────────────────────────────────── --}}
    @if (count($materials))
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($materials as $m)
                @php
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
                    $levelLabel = match ((int) $m['access_level']) {
                        1 => 'Public', 2 => 'Restricted', 3 => 'Confidential', default => 'Unknown',
                    };
                    $levelBg = match ((int) $m['access_level']) {
                        1 => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                        2 => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                        3 => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
                        default => 'bg-gray-100 text-gray-500',
                    };
                @endphp

                <a href="{{ $m['view_url'] }}"
                   class="group flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm
                          transition-all duration-150 hover:-translate-y-0.5 hover:shadow-md
                          dark:border-white/10 dark:bg-white/5">

                    {{-- Type + Access badges --}}
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $typeBg }}">
                            {{ $typeLabel }}
                        </span>
                        <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $levelBg }}">
                            {{ $levelLabel }}
                        </span>
                    </div>

                    {{-- Title --}}
                    <h3 class="line-clamp-2 flex-1 text-sm font-bold text-gray-800
                               transition-colors group-hover:text-primary-600
                               dark:text-white dark:group-hover:text-primary-400">
                        {{ $m['title'] }}
                    </h3>

                    {{-- Author & year --}}
                    <p class="mt-1.5 truncate text-xs text-gray-400">{{ $m['author'] }}</p>
                    @if ($m['publication_date'])
                        <p class="text-xs text-gray-400">{{ $m['publication_date'] }}</p>
                    @endif

                    {{-- Keywords preview --}}
                    @if ($m['keywords'])
                        <p class="mt-1 truncate text-xs italic text-gray-300 dark:text-gray-600">
                            {{ $m['keywords'] }}
                        </p>
                    @endif

                    {{-- Format chips --}}
                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        @if ($m['has_digital'])
                            <span class="flex items-center gap-1 rounded-full bg-success-50 px-2 py-0.5
                                         text-xs font-medium text-success-700
                                         dark:bg-success-400/10 dark:text-success-400">
                                <x-heroicon-o-computer-desktop class="h-3 w-3" />
                                Digital
                            </span>
                        @endif
                        @if ($m['has_physical'])
                            <span class="flex items-center gap-1 rounded-full bg-info-50 px-2 py-0.5
                                         text-xs font-medium text-info-700
                                         dark:bg-info-400/10 dark:text-info-400">
                                <x-heroicon-o-book-open class="h-3 w-3" />
                                Physical
                            </span>
                        @endif
                        @if (! $m['has_digital'] && ! $m['has_physical'])
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs
                                         font-medium text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                No copies available
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        {{-- ── Pagination ────────────────────────────────────────────────── --}}
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
        <div class="rounded-xl border border-dashed border-gray-300 bg-white py-20 text-center
                    dark:border-white/10 dark:bg-white/5">
            <x-heroicon-o-document-magnifying-glass class="mx-auto mb-4 h-10 w-10 text-gray-300" />
            <p class="text-sm font-medium text-gray-500">No materials found</p>
            <p class="mt-1 text-xs text-gray-400">
                @if ($search)
                    Try adjusting your query or removing field prefixes.
                @else
                    Try adjusting your filters.
                @endif
            </p>
        </div>
    @endif

</x-filament-panels::page>