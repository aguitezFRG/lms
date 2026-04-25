<x-filament-panels::page>

    {{-- ── Profile Card ─────────────────────────────────────────────────── --}}
    <x-filament::section class="mb-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">

            <div class="flex h-16 w-16 shrink-0 items-center justify-center
                        rounded-full bg-primary-800 shadow">
                <x-heroicon-o-user class="h-8 w-8 text-white" />
            </div>

            <div class="flex-1 min-w-0">
                {{ $this->profileInfolist }}
            </div>

        </div>
    </x-filament::section>

</x-filament-panels::page>
