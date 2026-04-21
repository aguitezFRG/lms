<x-filament-panels::page>

    {{-- Page Header --}}
    <x-filament::section class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Welcome to INSTAT Reading Room
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            @if ($role?->value === 'faculty')
                You are logged in as a <span class="font-medium text-primary-600 dark:text-primary-400">Faculty Member</span>.
            @elseif ($role?->value === 'student')
                You are logged in as a <span class="font-medium text-blue-600 dark:text-blue-400">Student User</span>.
            @endif
        </p>
    </x-filament::section>

    {{-- Role Banner --}}
    @if ($role?->value === 'faculty')
        <x-filament::section class="mb-6">
            <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                As a faculty member, you can browse and access research materials from the INSTAT Reading Room —
                including faculty-level and student-level materials.
                Use the navigation on the left to explore the catalog, submit requests, and track their progress.
            </p>
        </x-filament::section>
    @elseif ($role?->value === 'student')
        <x-filament::section class="mb-6">
            <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                As a student, you can browse and access student-level research materials from the INSTAT Reading Room.
                Use the navigation on the left to explore the catalog, submit requests, and track their progress.
            </p>
            <div class="mt-3 flex items-start gap-2 rounded-lg bg-blue-50 p-3 dark:bg-blue-950">
                <x-heroicon-o-information-circle class="mt-0.5 h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" />
                <p class="text-xs text-blue-700 dark:text-blue-300">
                    Note: Some materials are restricted to faculty and committee members only.
                </p>
            </div>
        </x-filament::section>
    @endif

    {{-- Role-specific sections --}}
    @if ($role?->value === 'faculty')
        <x-onboarding::user.faculty-feature-cards class="mb-6" />
        <x-onboarding::user.faculty-quick-links />
    @elseif ($role?->value === 'student')
        <x-onboarding::user.student-feature-cards class="mb-6" />
        <x-onboarding::user.student-quick-links />
    @else
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-400">Use the navigation on the left to get started.</p>
        </x-filament::section>
    @endif

</x-filament-panels::page>
