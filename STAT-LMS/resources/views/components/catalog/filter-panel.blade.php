@props([
    'filterPanelOpen'    => false,
    'draftFilterCount'   => 0,
    'draftTypeFilter'    => '',
    'draftFormatFilter'  => '',
    'draftPubDateFrom'   => '',
    'draftPubDateTo'     => '',
    'draftSdgFilter'     => [],
    'draftAvailableOnly' => true,
])

    {{-- ── 4. Filter Panel ──────────────────────────────────────────────────── --}}
    {{-- Draft state: all bindings inside the panel target $draft* properties.
         Nothing is applied to the live query until "Apply Filters" is clicked. --}}
    @if ($filterPanelOpen)
    <div class="mb-4">
            <div
                wire:key="filter-panel"
                class="w-full rounded-2xl
                       bg-white shadow-sm ring-1 ring-black/5
                       dark:bg-gray-900 dark:ring-white/10"
            >
                {{-- Panel Header — badge reflects DRAFT filter count --}}
                <div class="flex items-center border-b border-gray-100 px-5 py-3
                            dark:border-white/10">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-adjustments-horizontal class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                            Filter Catalog
                        </h2>
                        @if ($draftFilterCount > 0)
                            <span class="flex h-5 w-5 items-center justify-center rounded-full
                                         bg-primary-600 text-xs font-bold text-white dark:bg-primary-400">
                                {{ $draftFilterCount }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Panel Body — all inputs bind to draft* state --}}
                <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2">

                    {{-- Material Type --}}
                    <div class="sm:col-span-2">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Material Type
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (['' => 'All', '1' => 'Book', '2' => 'Thesis', '3' => 'Journal', '4' => 'Dissertation', '5' => 'Others'] as $val => $label)
                                <button
                                    wire:click="$set('draftTypeFilter', '{{ $val }}')"
                                    @class([
                                        'rounded-full px-3 py-1 text-xs font-medium transition border',
                                        'border-primary-600 bg-primary-600 text-white dark:border-primary-400 dark:bg-primary-400' => $draftTypeFilter === (string) $val,
                                        'border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:bg-gray-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-300' => $draftTypeFilter !== (string) $val,
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
                                    wire:click="$set('draftFormatFilter', '{{ $val }}')"
                                    @class([
                                        'flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition border',
                                        'border-primary-600 bg-primary-600 text-white dark:border-primary-400 dark:bg-primary-400' => $draftFormatFilter === (string) $val,
                                        'border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:bg-gray-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-300' => $draftFormatFilter !== (string) $val,
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
                                <input type="checkbox" wire:model="draftAvailableOnly" class="sr-only peer" />
                                <div class="h-5 w-9 rounded-full bg-gray-200 transition peer-checked:bg-primary-600
                                            dark:bg-gray-700 dark:peer-checked:bg-primary-500
                                            after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4
                                            after:rounded-full after:bg-white after:transition
                                            peer-checked:after:translate-x-4"></div>
                            </div>
                            <span class="text-sm text-gray-600 dark:text-gray-300">Show only available copies</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            Materials with no available copies are always hidden.
                        </p>
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
                                    wire:model="draftPubDateFrom"
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
                                    wire:model="draftPubDateTo"
                                    type="date"
                                    max="{{ date('Y-m-d') }}"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm
                                           focus:border-primary-500 focus:outline-none
                                           dark:border-white/10 dark:bg-white/5 dark:text-white dark:[color-scheme:dark]"
                                />
                            </div>
                            @if ($draftPubDateFrom !== '' || $draftPubDateTo !== '')
                                <button
                                    @click="$wire.set('draftPubDateFrom', ''); $wire.set('draftPubDateTo', '')"
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
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
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
                                @php $active = in_array($sdg, $draftSdgFilter); @endphp
                                <button
                                    wire:click="toggleDraftSdg('{{ $sdg }}')"
                                    @class([
                                        'rounded-lg px-2.5 py-1.5 text-left text-xs font-medium transition border',
                                        'border-warning-500 bg-warning-500 text-white dark:border-warning-400' => $active,
                                        'border-gray-200 bg-gray-50 text-gray-600 hover:border-warning-300 hover:bg-warning-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300' => ! $active,
                                    ])
                                >{{ $sdg }}</button>
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- Panel Footer --}}
                <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 dark:border-white/10">
                    <button
                        wire:click="clearDraftFilters"
                        class="rounded-lg border border-danger-200 px-4 py-2 text-xs font-medium text-danger-600
                               transition hover:bg-danger-50 dark:border-danger-500/40 dark:text-danger-400
                               dark:hover:bg-danger-500/10"
                    >
                        Clear All Filters
                    </button>
                    <button
                        wire:click="applyFilters"
                        class="rounded-lg bg-primary-600 px-5 py-2 text-xs font-semibold text-white shadow-sm
                               transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
                    >
                        Apply Filters
                    </button>
                </div>
            </div>
    </div>
    @endif
