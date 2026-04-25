@php
    $markReadMethod ??= 'markRead';
    $meta = fn (string $type) => match ($type) {
        'request_status_changed' => [
            'icon'      => 'heroicon-o-bell',
            'icon_bg'   => 'bg-blue-100 dark:bg-blue-900/40',
            'icon_text' => 'text-blue-600 dark:text-blue-300',
            'label'     => 'Request',
        ],
        'access_level_changed' => [
            'icon'      => 'heroicon-o-exclamation-circle',
            'icon_bg'   => 'bg-red-100 dark:bg-red-900/40',
            'icon_text' => 'text-red-600 dark:text-red-300',
            'label'     => 'Revocation',
        ],
        'account_details_changed' => [
            'icon'      => 'heroicon-o-shield-check',
            'icon_bg'   => 'bg-purple-100 dark:bg-purple-900/40',
            'icon_text' => 'text-purple-600 dark:text-purple-300',
            'label'     => 'Account',
        ],
        'borrow_due_soon' => [
            'icon'      => 'heroicon-o-clock',
            'icon_bg'   => 'bg-amber-100 dark:bg-amber-900/40',
            'icon_text' => 'text-amber-600 dark:text-amber-300',
            'label'     => 'Borrow',
        ],
        default => [
            'icon'      => 'heroicon-o-bell',
            'icon_bg'   => 'bg-gray-100 dark:bg-gray-800',
            'icon_text' => 'text-gray-500 dark:text-gray-400',
            'label'     => 'Notification',
        ],
    };
@endphp

<div
    x-data="{
        showDetail: false,
        selected: null,
        timer: null,
        isLong: false,
        startPress(n) {
            this.isLong = false;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.isLong = true;
                this.selected = n;
                this.showDetail = true;
            }, 500);
        },
        endPress() {
            clearTimeout(this.timer);
        }
    }"
>
    @if (count($notifications))
        <div class="flex flex-col gap-2">
            @foreach ($notifications as $n)
                @php $m = $meta($n['type']); @endphp

                <div
                    x-on:touchstart.passive="startPress({{ Js::from($n) }})"
                    x-on:touchend="endPress()"
                    x-on:touchmove.passive="endPress()"
                    x-on:mousedown="startPress({{ Js::from($n) }})"
                    x-on:mouseup="endPress()"
                    x-on:mouseleave="endPress()"
                    x-on:click="if (!isLong) $wire.{{ $markReadMethod }}('{{ $n['id'] }}')"
                    @class([
                        'flex items-start gap-4 px-4 py-4 cursor-pointer transition-colors duration-150 rounded-xl select-none',
                        'bg-primary-50/60 dark:bg-primary-950/20 hover:bg-primary-50 dark:hover:bg-primary-950/30' => $n['is_unread'],
                        'bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/[0.08]'          => ! $n['is_unread'],
                    ])
                >
                    {{-- Icon badge --}}
                    <div @class([
                        'shrink-0 self-center w-9 h-9 rounded-full flex items-center justify-center',
                        $m['icon_bg'],
                    ])>
                        <x-dynamic-component
                            :component="$m['icon']"
                            @class(['w-4 h-4', $m['icon_text']])
                        />
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-[15px] font-semibold leading-snug text-gray-900 dark:text-white mb-1.5">
                            {{ $n['title'] }}
                        </p>
                        <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400 line-clamp-2 mb-1.5">
                            {{ $n['message'] }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $m['label'] }}&ensp;&bull;&ensp;{{ $n['since'] }}
                            @isset($n['date'])
                                &ensp;&bull;&ensp;{{ $n['date'] }}
                            @endisset
                        </p>
                    </div>

                    {{-- Unread dot --}}
                    <div class="shrink-0 self-center w-4 flex justify-center">
                        @if ($n['is_unread'])
                            <x-filament::badge color="info" size="xs"></x-filament::badge>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center gap-2 py-16 rounded-xl border border-dashed border-gray-200 dark:border-white/10 text-center">
            <x-heroicon-o-bell-slash class="w-8 h-8 text-gray-300 dark:text-gray-600" />
            <p class="text-sm font-medium text-gray-400 dark:text-gray-500">No notifications yet</p>
        </div>
    @endif

    {{-- Long-press detail modal --}}
    <template x-teleport="body">
        <div
            x-show="showDetail"
            x-on:click.self="showDetail = false; isLong = false"
            x-on:keydown.escape.window="showDetail = false; isLong = false"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            class="fixed inset-0 z-9999 flex items-center justify-center p-5 bg-black/40 backdrop-blur-sm"
        >
            <div
                x-show="showDetail"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-on:click.stop
                class="w-full max-w-sm rounded-2xl bg-white dark:bg-gray-900 shadow-2xl
                       border border-gray-200 dark:border-white/10 overflow-hidden"
            >
                <template x-if="selected">
                    <div>
                        <div class="px-5 pt-5 pb-4">
                            <p x-text="selected.title"
                               class="text-base font-semibold text-gray-900 dark:text-white mb-2 leading-snug">
                            </p>
                            <p x-text="selected.message"
                               class="text-sm leading-relaxed text-gray-600 dark:text-gray-300 mb-3">
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                <span x-text="selected.since"></span>
                            </p>
                        </div>
                        <div class="border-t border-gray-100 dark:border-white/10 px-5 py-3 flex justify-end">
                            <button
                                type="button"
                                x-on:click="showDetail = false; isLong = false"
                                class="text-sm font-medium text-primary-600 hover:text-primary-700
                                       dark:text-primary-400 dark:hover:text-primary-300"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>