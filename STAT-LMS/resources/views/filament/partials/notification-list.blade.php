@php
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

@if (count($notifications))
    <div class="divide-y divide-gray-100 dark:divide-white/5 rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden">
        @foreach ($notifications as $n)
            @php $m = $meta($n['type']); @endphp

            <div
                wire:click="markRead('{{ $n['id'] }}')"
                @class([
                    'flex items-start gap-4 px-4 py-4 cursor-pointer transition-colors duration-150',
                    'bg-blue-50/60 dark:bg-blue-950/20 hover:bg-blue-50 dark:hover:bg-blue-950/30' => $n['is_unread'],
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
                    </p>
                </div>

                {{-- Unread dot --}}
                <div class="shrink-0 self-center w-4 flex justify-center">
                    @if ($n['is_unread'])
                        <span class="block w-2.5 h-2.5 rounded-full bg-blue-500 dark:bg-blue-400"></span>
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