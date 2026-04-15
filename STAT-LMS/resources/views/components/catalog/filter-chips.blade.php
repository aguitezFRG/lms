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
                <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 px-2.5 py-1
                             text-xs font-medium text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                    {{ $typeChipLabel }}
                    <button wire:click="removeFilter('typeFilter')" class="ml-0.5 rounded-full transition-colors hover:text-primary-900 hover:bg-primary-200 dark:hover:bg-primary-800/60">
                        <x-heroicon-m-x-mark class="h-3 w-3" />
                    </button>
                </span>
            @endif

            @if ($formatFilter !== '')
                <span class="inline-flex items-center gap-1 rounded-full bg-success-100 px-2.5 py-1
                             text-xs font-medium text-success-700 dark:bg-success-900/40 dark:text-success-300">
                    {{ $formatFilter === 'digital' ? 'Digital' : 'Physical' }}
                    <button wire:click="removeFilter('formatFilter')" class="ml-0.5 rounded-full transition-colors hover:text-success-900 hover:bg-success-200 dark:hover:bg-success-800/60">
                        <x-heroicon-m-x-mark class="h-3 w-3" />
                    </button>
                </span>
            @endif

            @if ($pubDateFrom !== '' || $pubDateTo !== '')
                <span class="inline-flex items-center gap-1 rounded-full bg-warning-100 px-2.5 py-1
                             text-xs font-medium text-warning-700 dark:bg-warning-900/40 dark:text-warning-300">
                    {{ $pubDateFrom ?: '…' }} – {{ $pubDateTo ?: '…' }}
                    <button wire:click="removeFilter('pubDate')" class="ml-0.5 rounded-full transition-colors hover:text-warning-900 hover:bg-warning-200 dark:hover:bg-warning-800/60">
                        <x-heroicon-m-x-mark class="h-3 w-3" />
                    </button>
                </span>
            @endif

            @foreach ($sdgFilter as $sdg)
                <span class="inline-flex items-center gap-1 rounded-full bg-warning-100 px-2.5 py-1
                             text-xs font-medium text-warning-700 dark:bg-warning-900/40 dark:text-warning-300">
                    SDG: {{ $sdg }}
                    <button wire:click="removeFilter('sdg', '{{ $sdg }}')" class="ml-0.5 rounded-full transition-colors hover:text-warning-900 hover:bg-warning-200 dark:hover:bg-warning-800/60">
                        <x-heroicon-m-x-mark class="h-3 w-3" />
                    </button>
                </span>
            @endforeach

            @if (!$availableOnly)
                <span class="inline-flex items-center gap-1 rounded-full bg-success-100 px-2.5 py-1
                             text-xs font-medium text-success-700 dark:bg-success-900/40 dark:text-success-300">
                    Including unavailable
                    <button wire:click="removeFilter('availableOnly')" class="ml-0.5 rounded-full transition-colors hover:text-success-900 hover:bg-success-200 dark:hover:bg-success-800/60">
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
