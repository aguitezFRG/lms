<x-filament-panels::page>

    {{-- Page Header --}}
    <x-filament::section class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Welcome to INSTAT Reading Room System
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            @if ($role?->value === 'committee')
                You are logged in as a <span class="font-medium text-warning-600 dark:text-warning-400">Reading Room Committee</span> member.
            @elseif ($role?->value === 'it')
                You are logged in as an <span class="font-medium text-danger-600 dark:text-danger-400">IT Administrator</span>.
            @elseif ($role?->value === 'staff/custodian')
                You are logged in as <span class="font-medium text-success-600 dark:text-success-400">Reading Room Staff</span>.
            @elseif ($role?->value === 'super_admin')
                You are logged in as <span class="font-medium text-purple-600 dark:text-purple-400">Super Administrator</span>.
            @else
                You are logged in as <span class="font-medium text-gray-700 dark:text-gray-300">{{ $role?->getLabel() ?? 'an administrator' }}</span>.
            @endif
        </p>
    </x-filament::section>

    {{-- Role Banner --}}
    @if ($role?->value === 'committee')
        <x-filament::section class="mb-6">
            <p class="text-sm text-warning-800 dark:text-warning-200">
                As a Reading Room Committee member, you oversee institutional policy and material curation. You have full access to all system features.
            </p>
        </x-filament::section>
    @elseif ($role?->value === 'it')
        <x-filament::section class="mb-6">
            <p class="text-sm text-danger-800 dark:text-danger-200">
                As an IT Administrator, you support system integrity and user access. You share operational permissions with committee members.
            </p>
        </x-filament::section>
    @elseif ($role?->value === 'staff/custodian')
        <x-filament::section class="mb-6">
            <p class="text-sm text-success-800 dark:text-success-200">
                As Reading Room Staff, you handle day-to-day material access operations and borrow request processing.
            </p>
        </x-filament::section>
    @elseif ($role?->value === 'super_admin')
        <x-filament::section class="mb-6">
            <p class="text-sm text-purple-800 dark:text-purple-200">
                As a Super Administrator, you have unrestricted access to all system features, including full catalog control, user management at every privilege level, audit logs, analytics, and the ability to manage or override any record in the system.
            </p>
        </x-filament::section>
    @endif

    {{-- Role-specific sections --}}
    @if ($role?->value === 'committee' || $role?->value === 'it')
        <x-onboarding::admin.committee-feature-cards class="mb-6" />
        <x-onboarding::admin.committee-quick-links />
    @elseif ($role?->value === 'staff/custodian')
        <x-onboarding::admin.staff-feature-cards class="mb-6" />
        <x-onboarding::admin.staff-quick-links />
    @elseif ($role?->value === 'super_admin')
        <x-onboarding::admin.super-admin-feature-cards class="mb-6" />
        <x-onboarding::admin.super-admin-quick-links />
    @endif

</x-filament-panels::page>
