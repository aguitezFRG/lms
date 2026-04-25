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
                            <x-filament::button
                                wire:click="$set('draftTypeFilter', '{{ $val }}')"
                                :color="$draftTypeFilter === (string) $val ? 'primary' : 'gray'"
                                size="xs"
                            >{{ $label }}</x-filament::button>
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
                            <x-filament::button
                                wire:click="$set('draftFormatFilter', '{{ $val }}')"
                                :color="$draftFormatFilter === (string) $val ? 'primary' : 'gray'"
                                :icon="$cfg['icon']"
                                size="xs"
                            >{{ $cfg['label'] }}</x-filament::button>
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
                            <x-filament::input.checkbox wire:model="draftAvailableOnly" class="sr-only peer" />
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
                            <span class="w-8 shrink-0 text-xs text-gray-400">From</span>
                            <x-filament::input.wrapper class="flex-1">
                                <x-filament::input
                                    wire:model="draftPubDateFrom"
                                    type="date"
                                    max="{{ date('Y-m-d') }}"
                                />
                            </x-filament::input.wrapper>
                        </div>
                        <span class="hidden text-gray-300 sm:block">—</span>
                        <div class="flex flex-1 items-center gap-2">
                            <span class="w-8 shrink-0 text-xs text-gray-400">To</span>
                            <x-filament::input.wrapper class="flex-1">
                                <x-filament::input
                                    wire:model="draftPubDateTo"
                                    type="date"
                                    max="{{ date('Y-m-d') }}"
                                />
                            </x-filament::input.wrapper>
                        </div>
                        @if ($draftPubDateFrom !== '' || $draftPubDateTo !== '')
                            <x-filament::button
                                color="danger"
                                size="xs"
                                @click="$wire.set('draftPubDateFrom', ''); $wire.set('draftPubDateTo', '')"
                            >Clear</x-filament::button>
                        @endif
                    </div>
                    @error('draftPubDateTo')
                        <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @enderror
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
                            <x-filament::button
                                wire:click="toggleDraftSdg('{{ $sdg }}')"
                                :color="$active ? 'warning' : 'gray'"
                                size="xs"
                                class="text-left"
                            >{{ $sdg }}</x-filament::button>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- Panel Footer --}}
            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 dark:border-white/10">
                <x-filament::button
                    wire:click="clearDraftFilters"
                    color="danger"
                    outlined
                >
                    Clear All Filters
                </x-filament::button>
                <x-filament::button
                    wire:click="applyFilters"
                    color="primary"
                >
                    Apply Filters
                </x-filament::button>
            </div>
        </div>
</div>
@endif
