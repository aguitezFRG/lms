@props([
    'paginator' => null,
])

<div class="mt-8 flex flex-wrap items-center justify-center gap-1">
    <button
        wire:click="previousPage"
        @disabled($paginator->onFirstPage())
        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 shadow-sm
                transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40
                dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
    >&larr; Prev</button>

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
            <button
                wire:click="goToPage({{ $p }})"
                class="rounded-lg border px-3 py-1.5 text-sm transition
                        {{ $current === $p
                            ? 'border-primary-600 bg-primary-600 text-white shadow-sm'
                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300' }}"
            >{{ $p }}</button>
        @endif
    @endforeach

    <button
        wire:click="nextPage"
        @disabled(! $paginator->hasMorePages())
        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 shadow-sm
                transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40
                dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
    >Next &rarr;</button>
</div>
