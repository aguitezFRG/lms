<x-filament-panels::page>
<style>[x-cloak] { display: none !important; }</style>

{{-- Auto-refresh polling (60s) --}}
<span wire:poll.60s class="hidden"></span>
<div>

    <x-catalog.search-bar
        :search-scope="$searchScope"
        :active-filter-count="$activeFilterCount"
        :filter-panel-open="$filterPanelOpen"
    />

    <x-catalog.sort-controls
        :total-results="$totalResults"
        :sort-dir="$sortDir"
        :paginator="$paginator"
    />

    <x-catalog.filter-chips
        :active-filter-count="$activeFilterCount"
        :type-filter="$typeFilter"
        :format-filter="$formatFilter"
        :pub-date-from="$pubDateFrom"
        :pub-date-to="$pubDateTo"
        :sdg-filter="$sdgFilter"
        :available-only="$availableOnly"
    />

    <x-catalog.filter-panel
        :filter-panel-open="$filterPanelOpen"
        :draft-filter-count="$draftFilterCount"
        :draft-type-filter="$draftTypeFilter"
        :draft-format-filter="$draftFormatFilter"
        :draft-pub-date-from="$draftPubDateFrom"
        :draft-pub-date-to="$draftPubDateTo"
        :draft-sdg-filter="$draftSdgFilter"
        :draft-available-only="$draftAvailableOnly"
    />

    {{-- ── Card List ─────────────────────────────────────────────────────── --}}
    {{-- Scoped to only data-loading actions; filter panel interactions won't trigger skeleton --}}
    <div
        wire:loading.remove
        wire:target="goToPage,nextPage,previousPage,updatedSearch,updatedSearchScope,updatedSortBy,updatedSortDir,applyFilters,removeFilter,clearAllFilters"
    >
        @if (count($materials))
            <div class="flex flex-col gap-6">
                @foreach ($materials as $m)
                    <x-catalog.material-card :material="$m" />
                @endforeach
            </div>

            @if ($paginator->hasPages())
                <x-catalog.pagination-controls :paginator="$paginator" />
            @endif
        @else
            <div class="rounded-xl border border-dashed border-gray-300 bg-white py-20 text-center
                        dark:border-white/10 dark:bg-white/5">
                <x-heroicon-o-document-magnifying-glass class="mx-auto mb-4 h-10 w-10 text-gray-300" />
                <p class="text-sm font-medium text-gray-500">No materials found</p>
                <p class="mt-1 text-xs text-gray-400">Try adjusting your search or clearing some filters.</p>
            </div>
        @endif
    </div>

    {{-- ── Skeleton rows (shown only during data-loading actions) ──────── --}}
    <div
        wire:loading.flex
        wire:target="goToPage,nextPage,previousPage,updatedSearch,updatedSearchScope,updatedSortBy,updatedSortDir,applyFilters,removeFilter,clearAllFilters"
        class="flex-col gap-6"
    >
        @foreach (range(1, 5) as $i)
            <x-catalog.skeleton-card />
        @endforeach
    </div>

</div>{{-- /x-data --}}
</x-filament-panels::page>
