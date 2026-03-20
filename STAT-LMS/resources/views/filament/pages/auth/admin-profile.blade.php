<x-filament-panels::page>

    {{-- ── Profile Card (Filament Infolist) ───────────────────────────────── --}}
    @php $user = auth()->user(); @endphp

    <x-filament::section class="mb-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            {{-- Avatar --}}
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full
                        bg-primary-600 text-2xl font-bold text-white shadow">
                {{ strtoupper(substr($user->f_name ?? $user->name, 0, 1)) }}{{ strtoupper(substr($user->l_name ?? '', 0, 1)) }}
            </div>

            {{-- Details --}}
            <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Full Name</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">
                        {{ trim(implode(' ', array_filter([
                            $user->f_name,
                            $user->m_name ? mb_substr($user->m_name, 0, 1) . '.' : null,
                            $user->l_name,
                        ]))) ?: $user->name }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Role</p>
                    <x-filament::badge :color="\App\Enums\UserRole::from($user->role)->getColor()">
                        {{ \App\Enums\UserRole::from($user->role)->getLabel() }}
                    </x-filament::badge>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Email</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500">UUID</p>
                    <p class="font-mono text-xs text-gray-400 dark:text-gray-500 truncate">{{ $user->id }}</p>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- ── Tab Navigation ───────────────────────────────────────────────── --}}
    <div class="mb-6 flex gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1
                dark:border-white/10 dark:bg-white/5">

        <button wire:click="setTab('history')"
                class="flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition
                       {{ $activeTab === 'history'
                           ? 'bg-white shadow text-gray-800 dark:bg-white/10 dark:text-white'
                           : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
            <x-heroicon-o-clock class="h-4 w-4" />
            Access History
        </button>

        <button wire:click="setTab('notifications')"
                class="flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition
                       {{ $activeTab === 'notifications'
                           ? 'bg-white shadow text-gray-800 dark:bg-white/10 dark:text-white'
                           : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
            <x-heroicon-o-bell class="h-4 w-4" />
            Notifications
            @if ($unreadCount > 0)
                <x-filament::badge color="danger">{{ $unreadCount }}</x-filament::badge>
            @endif
        </button>
    </div>

    {{-- ── History Tab (Filament Table) ────────────────────────────────────── --}}
    @if ($activeTab === 'history')
        {{ $this->table }}
    @endif

    {{-- ── Notifications Tab ───────────────────────────────────────────────── --}}
    @if ($activeTab === 'notifications')
        @if (count($notifications))
            @if ($unreadCount > 0)
                <div class="mb-3 flex justify-end">
                    <x-filament::button wire:click="markAllRead" color="gray" size="sm">
                        Mark all as read
                    </x-filament::button>
                </div>
            @endif

            <div class="flex flex-col gap-2">
                @foreach ($notifications as $notification)
                    @php
                        $data   = $notification->data;
                        $isRead = ! is_null($notification->read_at);
                        $icon   = match ($data['type'] ?? '') {
                            'request_status_changed'  => 'heroicon-o-clipboard-document-check',
                            'access_level_changed'    => 'heroicon-o-lock-closed',
                            'account_details_changed' => 'heroicon-o-user',
                            'borrow_due_soon'         => 'heroicon-o-clock',
                            default                   => 'heroicon-o-bell',
                        };
                    @endphp

                    <button
                        wire:click="markRead('{{ $notification->id }}')"
                        class="w-full text-left"
                    >
                        <x-filament::section
                            :class="$isRead
                                ? 'opacity-60'
                                : 'ring-1 ring-primary-500/30 dark:ring-primary-400/30'"
                        >
                            <div class="flex items-start gap-3">
                                {{-- Icon --}}
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full
                                            {{ $isRead ? 'bg-gray-100 dark:bg-gray-700' : 'bg-primary-50 dark:bg-primary-400/20' }}">
                                    <x-dynamic-component
                                        :component="$icon"
                                        class="h-4 w-4 {{ $isRead ? 'text-gray-400' : 'text-primary-600 dark:text-primary-400' }}"
                                    />
                                </div>

                                {{-- Text --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                        {{ $data['title'] ?? 'Notification' }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $data['message'] ?? '' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </p>
                                </div>

                                {{-- Unread dot --}}
                                @if (! $isRead)
                                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-primary-500"></span>
                                @endif
                            </div>
                        </x-filament::section>
                    </button>
                @endforeach
            </div>
        @else
            <x-filament::section>
                <div class="py-8 text-center">
                    <x-heroicon-o-bell class="mx-auto mb-3 h-8 w-8 text-gray-300" />
                    <p class="text-sm font-medium text-gray-500">No notifications yet.</p>
                </div>
            </x-filament::section>
        @endif
    @endif

</x-filament-panels::page>