@props([
    'paginator' => null,
])

<div class="mt-8 flex flex-wrap items-center justify-center gap-1">
    <x-filament::button
        wire:click="previousPage"
        @disabled($paginator->onFirstPage())
        color="gray"
        outlined
        size="sm"
        icon="heroicon-o-arrow-left"
        icon-position="before"
    >
        Prev
    </x-filament::button>

    @php
        $current  = $paginator->currentPage();
        $last     = $paginator->lastPage();
        $window   = 2; // pages to show either side of current
        $pages    = [];
        $prevAdded = null;
        for ($p = 1; $p <= $last; $p++) {
            if ($p === 1 || $p === $last || abs($p - $current) <= $window) {
                if ($prevAdded !== null && $p - $prevAdded > 1) {
                    $pages[] = '...';
                }
                $pages[] = $p;
                $prevAdded = $p;
            }
        }
    @endphp
    @foreach ($pages as $p)
        @if ($p === '...')
            <span class="px-1 py-1.5 text-sm text-gray-400 select-none">…</span>
        @else
            <x-filament::button
                wire:click="goToPage({{ $p }})"
                :color="$current === $p ? 'primary' : 'gray'"
                :outlined="$current !== $p"
                size="sm"
            >{{ $p }}</x-filament::button>
        @endif
    @endforeach

    <x-filament::button
        wire:click="nextPage"
        @disabled(! $paginator->hasMorePages())
        color="gray"
        outlined
        size="sm"
        icon="heroicon-o-arrow-right"
        icon-position="after"
    >
        Next
    </x-filament::button>
</div>
