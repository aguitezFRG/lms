<div class="p-2">

    {{-- Brand header --}}
    <div class="mb-6 flex flex-col items-center gap-1 text-center">
        <div class="mb-3 flex items-center gap-2">
            <img
                src="{{ asset('images/up-seal.png') }}"
                alt="UP Seal"
                class="h-8 w-auto"
            >
            <span class="text-sm font-semibold text-gray-950 dark:text-white">
                INSTAT-RR-SPRIS
            </span>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
            Complete Your Profile
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            A few details are needed before you can access the reading room.
        </p>
    </div>

    {{-- Step indicator --}}
    <div class="mb-6 flex items-center gap-3">
        <div class="flex items-center gap-1.5">
            <div
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-colors {{ $step >= 1 ? 'text-white' : 'bg-gray-200 text-gray-500 dark:bg-white/10 dark:text-gray-400' }}"
                @if($step >= 1) style="background-color: #8D1436;" @endif
            >
                @if ($step > 1)
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                @else
                    1
                @endif
            </div>
            <span
                class="text-xs font-medium {{ $step !== 1 ? 'text-gray-400 dark:text-gray-500' : '' }}"
                @if($step === 1) style="color: #8D1436;" @endif
            >Your Name</span>
        </div>

        <div
            class="h-px flex-1 transition-colors {{ $step < 2 ? 'bg-gray-200 dark:bg-white/10' : '' }}"
            @if($step >= 2) style="background-color: #8D1436;" @endif
        ></div>

        <div class="flex items-center gap-1.5">
            <div
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-colors {{ $step >= 2 ? 'text-white' : 'bg-gray-200 text-gray-500 dark:bg-white/10 dark:text-gray-400' }}"
                @if($step >= 2) style="background-color: #8D1436;" @endif
            >2</div>
            <span
                class="text-xs font-medium {{ $step !== 2 ? 'text-gray-400 dark:text-gray-500' : '' }}"
                @if($step === 2) style="color: #8D1436;" @endif
            >Student Number</span>
        </div>
    </div>

    {{-- Step 1: Name --}}
    @if ($step === 1)
        <form wire:submit="nextStep" class="space-y-4">
            {{ $this->nameForm }}

            <x-filament::button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full"
                color="primary"
            >
                <span wire:loading.remove wire:target="nextStep">Continue</span>
                <span wire:loading wire:target="nextStep">Saving…</span>
            </x-filament::button>
        </form>
    @endif

    {{-- Step 2: Student Number --}}
    @if ($step === 2)
        <div class="space-y-4">
            <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <p class="text-xs text-gray-500 dark:text-gray-400">Name on record</p>
                <p class="mt-0.5 text-sm font-medium text-gray-950 dark:text-white">
                    {{ trim(implode(' ', array_filter([$data['f_name'] ?? '', $data['m_name'] ?? '', $data['l_name'] ?? '']))) }}
                </p>
            </div>

            <form wire:submit="submit" class="space-y-4">
                {{ $this->studentForm }}

                <div class="flex gap-3">
                    <x-filament::button
                        wire:click="previousStep"
                        type="button"
                        color="gray"
                        class="flex-1"
                    >
                        Back
                    </x-filament::button>

                    <x-filament::button
                        type="submit"
                        wire:loading.attr="disabled"
                        color="primary"
                        class="flex-1"
                    >
                        <span wire:loading.remove wire:target="submit">Finish Setup</span>
                        <span wire:loading wire:target="submit">Saving…</span>
                    </x-filament::button>
                </div>
            </form>
        </div>
    @endif

    {{-- Sign-out footer --}}
    <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-white/5">
        <span class="text-xs text-gray-400 dark:text-gray-500">Signed in with Google</span>
        <button
            wire:click="logout"
            type="button"
            class="text-xs text-gray-400 underline transition-colors hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
        >
            Sign out
        </button>
    </div>

</div>
