<x-filament-panels::page>
    {{-- Welcome Section --}}
    <x-filament::section>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Welcome to INSTAT Reading Room System
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            You are logged in as a <span class="font-medium {{ $roleColorClass }}">{{ $roleLabel }}</span>.
        </p>
        {!! $bannerHtml !!}
    </x-filament::section>

    {{-- Feature Cards Section --}}
    @if ($cardsHtml)
        <x-filament::section class="mt-6">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">What you can do</h2>
            {!! $cardsHtml !!}
        </x-filament::section>
    @endif
</x-filament-panels::page>
