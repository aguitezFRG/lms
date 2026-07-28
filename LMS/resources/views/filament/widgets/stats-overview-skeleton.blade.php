@php
    $columns = $columns ?? ['@xl' => 3, '!@lg' => 3];
    $maxColumns = max(1, ...array_map(fn ($value): int => max(1, (int) $value), array_values($columns)));
    $gridAttributes = (new \Illuminate\View\ComponentAttributeBag)->grid($columns);

    $rows = max(1, (int) ($rows ?? 0));
    $cards = (int) ($cards ?? 0);
    $cards = $cards > 0 ? $cards : max(1, $rows * $maxColumns);
@endphp

<div class="fi-wi-widget fi-wi-stats-overview">
    <div class="fi-section-content-ctn">
        <div class="fi-section-content">
            <div {{ $gridAttributes->class(['gap-4']) }}>
                @foreach (range(1, $cards) as $card)
                    <div class="fi-wi-stats-overview-stat min-h-36">
                        <div class="fi-wi-stats-overview-stat-content animate-pulse">
                            <div class="fi-wi-stats-overview-stat-label-ctn">
                                <div class="h-5 w-5 rounded-md bg-gray-200 dark:bg-white/10"></div>
                                <div class="h-3.5 w-16 max-w-full rounded-md bg-gray-200 dark:bg-white/10"></div>
                            </div>

                            <div class="fi-wi-stats-overview-stat-value">
                                <div class="h-9 w-12 max-w-full rounded-md bg-gray-200 dark:bg-white/10"></div>
                            </div>

                            <div class="fi-wi-stats-overview-stat-description">
                                <div class="flex flex-col gap-3">
                                    <div class="h-3.5 w-4/5 max-w-full rounded-md bg-gray-200 dark:bg-white/10"></div>
                                    <div class="h-3.5 w-2/5 max-w-full rounded-md bg-gray-200 dark:bg-white/10"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
