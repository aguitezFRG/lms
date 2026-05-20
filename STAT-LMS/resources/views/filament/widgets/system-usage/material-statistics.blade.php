<x-filament-widgets::widget>
    <x-filament::section heading="Material Statistics">

        {{-- Month range input row --}}
        <div class="flex flex-wrap gap-3 items-end mb-3">
            <div class="flex flex-col gap-1">
                <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    Start Month
                </label>
                <x-filament::input.wrapper class="w-32">
                    <x-filament::input.select wire:model.live="inputStartMonth">
                        @foreach (['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $i => $abbr)
                            <option value="{{ $i + 1 }}">{{ $abbr }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <div class="flex flex-col gap-1">
                <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    End Month
                </label>
                <x-filament::input.wrapper class="w-32">
                    <x-filament::input.select wire:model.live="inputEndMonth">
                        @foreach (['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $i => $abbr)
                            <option value="{{ $i + 1 }}">{{ $abbr }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            @if (empty($frames))
                <div class="flex flex-col gap-1">
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
                        End Year
                    </label>
                    <x-filament::input
                        type="number"
                        wire:model="inputEndYear"
                        wire:keydown.enter="addTimeFrame"
                        min="1900"
                        max="{{ now()->year + 50 }}"
                        placeholder="{{ now()->year }}"
                        class="w-28"
                    />
                </div>
            @else
                @php
                    $prev = last($frames);
                    $inferred = $inputStartMonth > $prev['endMonth']
                        ? $prev['endYear']
                        : $prev['endYear'] + 1;
                @endphp
                <div class="flex flex-col gap-1">
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
                        End Year
                    </label>
                    <span class="inline-flex items-center h-9 px-3 text-sm text-gray-500 dark:text-gray-400">
                        {{ $inferred }} <span class="ml-1 text-xs text-gray-400">(auto)</span>
                    </span>
                </div>
            @endif

            <x-filament::button
                wire:click="addTimeFrame"
                icon="heroicon-o-plus"
                size="sm"
            >
                Add Time Frame
            </x-filament::button>
        </div>

        {{-- Helper note --}}
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">
            Note: Each time frame shows 5 yearly data points ending at the anchor year. Additional frames are placed chronologically after the previous one.
        </p>

        {{-- Active time frame badges --}}
        @if (count($frames) > 0)
            <div class="flex flex-wrap gap-2 mb-4">
                @php
                    $monthAbbrs = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                @endphp
                @foreach ($frames as $index => $frame)
                    @php
                        $startAbbr = $monthAbbrs[$frame['startMonth'] - 1];
                        $endAbbr   = $monthAbbrs[$frame['endMonth'] - 1];
                        $startYear = $frame['endYear'] - 4;
                    @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset
                        bg-gray-100 text-gray-700 ring-gray-200
                        dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600">
                        {{ $startAbbr }}–{{ $endAbbr }} {{ $startYear }}–{{ $frame['endYear'] }}
                        @if (count($frames) > 1)
                            <button
                                type="button"
                                wire:click="removeFrame({{ $index }})"
                                class="ml-0.5 rounded-full text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none"
                                aria-label="Remove {{ $startAbbr }}–{{ $endAbbr }} {{ $startYear }}–{{ $frame['endYear'] }}"
                            >
                                <svg class="size-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                </svg>
                            </button>
                        @endif
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Chart canvas — uses Filament's bundled Chart.js Alpine component --}}
        <div class="fi-wi-chart rr-material-chart">
        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
            wire:ignore
            data-chart-type="line"
            x-data="chart({
                cachedData: @js($this->getChartData()),
                options: @js($this->getChartOptions()),
                type: 'line',
            })"
            class="fi-wi-chart-canvas-ctn"
        >
            <canvas
                x-ref="canvas"
                style="width: 100%; max-height: 400px"
            ></canvas>

            <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
            <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
            <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
            <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
        </div>
        </div>

    </x-filament::section>
</x-filament-widgets::widget>
