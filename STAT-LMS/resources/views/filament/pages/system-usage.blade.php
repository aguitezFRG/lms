<x-filament-panels::page>

    {{-- Auto-refresh polling (120s) --}}
    <span wire:poll.120s class="hidden"></span>

    {{-- Tab Bar --}}
    <x-filament::tabs class="mb-6">
        <x-filament::tabs.item
            :active="$activeTab === 'summary'"
            icon="heroicon-o-chart-bar"
            wire:click="setTab('summary')"
        >
            Summary Statistics
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'export'"
            icon="heroicon-o-arrow-down-tray"
            wire:click="setTab('export')"
        >
            Export Raw Data
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{-- Summary Statistics Tab --}}
    @if ($activeTab === 'summary')

        {{-- Overview Cards --}}
        <div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ([
                ['label' => 'Total',    'value' => $stats['total'],    'color' => 'text-gray-700 dark:text-gray-200'],
                ['label' => 'Pending',  'value' => $stats['pending'],  'color' => 'text-yellow-600'],
                ['label' => 'Approved', 'value' => $stats['approved'], 'color' => 'text-green-600'],
                ['label' => 'Rejected', 'value' => $stats['rejected'], 'color' => 'text-red-600'],
                ['label' => 'Revoked',  'value' => $stats['revoked'],  'color' => 'text-gray-500'],
                ['label' => 'Overdue',  'value' => $stats['overdue'],  'color' => 'text-orange-600'],
            ] as $card)
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-2xl font-bold {{ $card['color'] }}">{{ $card['value'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</div>
            </div>
            @endforeach
        </div>

        <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Overdue rate: <strong>{{ $stats['overdueRate'] }}%</strong> of total requests/borrows
        </div>

        {{-- Three columns: top materials, trend, top users --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Most Requested Materials --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="mb-3 font-semibold text-gray-700 dark:text-gray-200">Top 5 Most Requested Materials</h3>
                @if ($stats['topMaterials']->isEmpty())
                    <p class="text-sm text-gray-400">No data.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700 text-left text-gray-500">
                                <th class="pb-1">#</th>
                                <th class="pb-1">Title</th>
                                <th class="pb-1 text-right">Requests</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['topMaterials'] as $i => $row)
                            <tr class="border-b border-gray-50 dark:border-gray-800">
                                <td class="py-1 pr-2 text-gray-400">{{ $i + 1 }}</td>
                                <td class="py-1 truncate max-w-[150px]" title="{{ $row['title'] }}">{{ $row['title'] }}</td>
                                <td class="py-1 text-right font-medium">{{ $row['count'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Monthly Trend --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="mb-3 font-semibold text-gray-700 dark:text-gray-200">Borrowing Trend (Last 6 Months)</h3>
                @if ($stats['monthlyTrend']->isEmpty())
                    <p class="text-sm text-gray-400">No data.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700 text-left text-gray-500">
                                <th class="pb-1">Month</th>
                                <th class="pb-1 text-right">Requests</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['monthlyTrend'] as $row)
                            <tr class="border-b border-gray-50 dark:border-gray-800">
                                <td class="py-1">{{ $row['month'] }}</td>
                                <td class="py-1 text-right font-medium">{{ $row['count'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Most Active Users --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="mb-3 font-semibold text-gray-700 dark:text-gray-200">Top 5 Most Active Users</h3>
                @if ($stats['topUsers']->isEmpty())
                    <p class="text-sm text-gray-400">No data.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700 text-left text-gray-500">
                                <th class="pb-1">#</th>
                                <th class="pb-1">User</th>
                                <th class="pb-1 text-right">Requests</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['topUsers'] as $i => $row)
                            <tr class="border-b border-gray-50 dark:border-gray-800">
                                <td class="py-1 pr-2 text-gray-400">{{ $i + 1 }}</td>
                                <td class="py-1 truncate max-w-[150px]" title="{{ $row['name'] }}">{{ $row['name'] }}</td>
                                <td class="py-1 text-right font-medium">{{ $row['count'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>

    @endif

    {{-- Export Raw Data Tab --}}
    @if ($activeTab === 'export')

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="mb-4 font-semibold text-gray-700 dark:text-gray-200">Filter & Export</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Use the filters below to narrow down records, then click <strong>Download CSV</strong>
                in the page header to export.
            </p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
                    <select
                        wire:model.live="filterStatus"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="revoked">Revoked</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Event Type</label>
                    <select
                        wire:model.live="filterType"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        <option value="">All types</option>
                        <option value="request">Request (digital)</option>
                        <option value="borrow">Borrow (physical)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Date From</label>
                    <input
                        type="date"
                        wire:model.live="filterDateFrom"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Date To</label>
                    <input
                        type="date"
                        wire:model.live="filterDateTo"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    />
                </div>

            </div>

            <p class="mt-4 text-xs text-gray-400">
                The CSV export includes: ID, User, Material Title, Event Type, Status, Approver,
                Due Date, Returned At, Is Overdue, Approved At, Completed At, Rejection Reason, Created At.
            </p>
        </div>

    @endif

</x-filament-panels::page>
