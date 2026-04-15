@props([
    'material' => [],
])

                @php
                    $typeLabel = match ((int) $material['material_type']) {
                        1 => 'Book', 2 => 'Thesis', 3 => 'Journal',
                        4 => 'Dissertation', default => 'Other',
                    };
                    $typeBg = match ((int) $material['material_type']) {
                        1 => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                        2 => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                        3 => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                        4 => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                    };

                    $isAvailable = $material['has_digital'] || $material['has_physical'];
                    $borderColour = $isAvailable
                        ? 'border-l-green-500 dark:border-l-green-400'
                        : 'border-l-amber-400 dark:border-l-amber-500';

                    $kwAll   = array_values(array_filter((array) $material['keywords']));
                    $kwShown = array_slice($kwAll, 0, 3);
                    $kwExtra = count($kwAll) - count($kwShown);
                @endphp

                <a href="{{ $material['view_url'] }}"
                   class="group flex w-full rounded-xl border border-gray-200 border-l-4 {{ $borderColour }}
                          bg-white px-5 py-4 shadow-sm transition-all duration-150
                          hover:-translate-y-0.5 hover:shadow-md
                          dark:border-gray-700/60 dark:bg-white/5 dark:hover:bg-white/[0.08]">

                    <div class="flex-1 min-w-0">

                        <h3 class="text-base font-bold leading-snug text-gray-800
                                   transition-colors group-hover:text-primary-600
                                   dark:text-white dark:group-hover:text-primary-400">
                            {{ $material['title'] }}
                        </h3>

                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                            {{ $material['author'] }}
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">

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

                            @if ($material['has_digital'])
                                <span class="inline-flex items-center gap-1 rounded-full
                                             bg-sky-100 px-2.5 py-0.5
                                             text-xs font-medium text-sky-700
                                             dark:bg-sky-900/40 dark:text-sky-300">
                                    <x-heroicon-o-computer-desktop class="h-3 w-3" />
                                    Digital
                                </span>
                            @endif
                            @if ($material['has_physical'])
                                <span class="inline-flex items-center gap-1 rounded-full
                                             bg-indigo-100 px-2.5 py-0.5
                                             text-xs font-medium text-indigo-700
                                             dark:bg-indigo-900/40 dark:text-indigo-300">
                                    <x-heroicon-o-book-open class="h-3 w-3" />
                                    Physical
                                </span>
                            @endif

                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $typeBg }}">
                                {{ $typeLabel }}
                            </span>

                            @foreach ($kwShown as $kw)
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5
                                             text-xs text-gray-500
                                             dark:bg-white/10 dark:text-gray-400">
                                    {{ $kw }}
                                </span>
                            @endforeach
                            @if ($kwExtra > 0)
                                @php
                                    $kwHidden  = array_slice($kwAll, 3);
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
                                    <span tabindex="0"
                                        class="cursor-default rounded-full bg-gray-100 px-2.5 py-0.5
                                                text-xs text-gray-400 select-none
                                                dark:bg-white/10 dark:text-gray-500">
                                        +{{ $kwExtra }} keywords
                                    </span>

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
                                        <span class="absolute left-1/2 top-full -translate-x-1/2
                                                    border-4 border-transparent border-t-gray-900
                                                    dark:border-t-gray-700">
                                        </span>
                                    </span>
                                </span>
                            @endif

                        </div>

                        @if (!empty($material['abstract']))
                            <p class="mt-2.5 line-clamp-2 text-sm leading-relaxed
                                      text-gray-600 dark:text-gray-300">
                                {{ $material['abstract'] }}
                            </p>
                        @endif

                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5
                                    text-xs text-gray-400 dark:text-gray-500">
                            @if ($material['publication_date'])
                                <span>{{ $material['publication_date'] }}</span>
                            @endif
                        </div>

                    </div>
                </a>
