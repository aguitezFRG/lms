<x-filament-panels::page>

    {{-- ── Profile Card ─────────────────────────────────────────────────── --}}
    @php $user = auth()->user(); @endphp

    <x-filament::section class="mb-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            {{-- Avatar --}}
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full
                        bg-primary-600 text-2xl font-bold text-white shadow">
                {{ strtoupper(substr($user->f_name ?? $user->name, 0, 1)) }}{{ strtoupper(substr($user->l_name ?? '', 0, 1)) }}
            </div>

            {{-- Details --}}
            <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
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
                    <x-filament::badge :color="$roleLabel ? \App\Enums\UserRole::from($user->role)->getColor() : 'gray'">
                        {{ $roleLabel }}
                    </x-filament::badge>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Email</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $user->email }}</p>
                </div>
                @if ($user->std_number)
                    <div>
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Student Number</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $user->std_number }}</p>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>

    {{-- ── Stats Row ────────────────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-filament::section>
            <p class="text-xs font-semibold uppercase tracking-wider text-warning-600 dark:text-warning-400">Pending</p>
            <p class="mt-1 text-3xl font-bold text-warning-700 dark:text-warning-300">{{ $pendingCount }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-xs font-semibold uppercase tracking-wider text-success-700 dark:text-success-400">Active / Approved</p>
            <p class="mt-1 text-3xl font-bold text-success-800 dark:text-success-300">{{ $approvedCount }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Requests</p>
            <p class="mt-1 text-3xl font-bold text-gray-700 dark:text-gray-200">{{ $totalCount }}</p>
        </x-filament::section>
    </div>

    {{-- ── Tab Navigation ───────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-wrap gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1
                dark:border-white/10 dark:bg-white/5">
        @foreach ([
            'pending'       => 'Pending',
            'approved'      => 'Approved',
            'closed'        => 'Closed',
            'notifications' => 'Notifications',
        ] as $tab => $label)
            <button wire:click="setTab('{{ $tab }}')"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition
                           {{ $activeTab === $tab
                               ? 'bg-white shadow text-gray-800 dark:bg-white/10 dark:text-white'
                               : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                @if ($tab === 'notifications')
                    <x-heroicon-o-bell class="h-4 w-4" />
                @endif
                {{ $label }}
                @if ($tab === 'notifications' && $unreadCount > 0)
                    <x-filament::badge color="danger">{{ $unreadCount }}</x-filament::badge>
                @endif
            </button>
        @endforeach
    </div>

    {{-- ── Request Tabs (Filament Table) ───────────────────────────────── --}}
    @if (in_array($activeTab, ['pending', 'approved', 'closed']))
        {{ $this->table }}
    @endif

    {{-- ── Notifications Tab ───────────────────────────────────────────── --}}
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

                    <button wire:click="markRead('{{ $notification->id }}')" class="w-full text-left">
                        <x-filament::section :class="$isRead ? 'opacity-60' : 'ring-1 ring-primary-500/30 dark:ring-primary-400/30'">
                            <div class="flex items-start gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full
                                            {{ $isRead ? 'bg-gray-100 dark:bg-gray-700' : 'bg-primary-50 dark:bg-primary-400/20' }}">
                                    <x-dynamic-component
                                        :component="$icon"
                                        class="h-4 w-4 {{ $isRead ? 'text-gray-400' : 'text-primary-600 dark:text-primary-400' }}"
                                    />
                                </div>
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
                    <p class="mt-1 text-xs text-gray-400">You will be notified about request updates and account changes here.</p>
                </div>
            </x-filament::section>
        @endif
    @endif

</x-filament-panels::page>