<x-filament-panels::page>
    <div class="space-y-4">
        <x-filament::section>
            <x-slot name="heading">
                Export Preview
            </x-slot>
            <x-slot name="description">
                Filter and preview material access events before exporting to CSV format.
                The export will include all matching records with full details.
            </x-slot>

            {{ $this->table }}
        </x-filament::section>

        <div class="text-xs text-gray-500 dark:text-gray-400">
            <p>
                The CSV export will include all matching records with: ID, User, Material Title,
                Event Type, Status, Approver, Due Date, Returned At, Is Overdue, Approved At,
                Completed At, Rejection Reason, and Created At.
            </p>
        </div>
    </div>
</x-filament-panels::page>
