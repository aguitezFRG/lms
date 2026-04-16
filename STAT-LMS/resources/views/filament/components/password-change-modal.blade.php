<div
    x-data="passwordChangeForm()"
    x-init="init()"
    class="space-y-4"
>
    {{-- Current Password --}}
    <div class="space-y-1">
        <x-filament::input.wrapper label="Current Password" required>
            <div class="relative w-full">
                <x-filament::input
                    type="password" x-bind:type="showCurrent ? 'text' : 'password'"
                    x-model="currentPassword"
                    autocomplete="current-password"
                    placeholder="Enter your current password"
                    class="pr-10"
                />
                <button type="button" x-on:click="showCurrent = !showCurrent"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <x-heroicon-o-eye x-show="!showCurrent" class="h-4 w-4"/>
                    <x-heroicon-o-eye-slash x-show="showCurrent" class="h-4 w-4"/>
                </button>
            </div>
        </x-filament::input.wrapper>
        <p x-show="errors.currentPassword" x-text="errors.currentPassword" class="text-sm text-danger-600 dark:text-danger-400"></p>
    </div>

    {{-- New Password --}}
    <div class="space-y-1">
        <x-filament::input.wrapper label="New Password" required>
            <div class="relative w-full">
                <x-filament::input
                    type="password" x-bind:type="showNew ? 'text' : 'password'"
                    x-model="newPassword"
                    autocomplete="new-password"
                    placeholder="At least 8 chars, upper/lowercase, number, symbol"
                    class="pr-10"
                />
                <button type="button" x-on:click="showNew = !showNew"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <x-heroicon-o-eye x-show="!showNew" class="h-4 w-4"/>
                    <x-heroicon-o-eye-slash x-show="showNew" class="h-4 w-4"/>
                </button>
            </div>
        </x-filament::input.wrapper>
        <p x-show="errors.newPassword" x-text="errors.newPassword" class="text-sm text-danger-600 dark:text-danger-400"></p>
    </div>

    {{-- Confirm New Password --}}
    <div class="space-y-1">
        <x-filament::input.wrapper label="Confirm New Password" required>
            <div class="relative w-full">
                <x-filament::input
                    type="password" x-bind:type="showConfirm ? 'text' : 'password'"
                    x-model="confirmPassword"
                    autocomplete="new-password"
                    placeholder="Re-enter your new password"
                    class="pr-10"
                />
                <button type="button" x-on:click="showConfirm = !showConfirm"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <x-heroicon-o-eye x-show="!showConfirm" class="h-4 w-4"/>
                    <x-heroicon-o-eye-slash x-show="showConfirm" class="h-4 w-4"/>
                </button>
            </div>
        </x-filament::input.wrapper>
        <p x-show="errors.confirmPassword" x-text="errors.confirmPassword" class="text-sm text-danger-600 dark:text-danger-400"></p>
    </div>

    {{-- Encryption status hint --}}
    <p class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
        <x-heroicon-o-lock-closed class="h-3 w-3"/>
        <span x-show="publicKey">Passwords are encrypted before being sent.</span>
        <span x-show="!publicKey">Loading encryption key…</span>
    </p>

    {{-- Submit --}}
    <div class="flex justify-end gap-2 pt-2">
        <x-filament::button
            color="gray"
            x-on:click="$dispatch('close-modal', { id: 'changePassword' }); resetForm()"
        >
            Cancel
        </x-filament::button>

        <x-filament::button
            color="success"
            x-on:click="submit()"
            x-bind:disabled="!publicKey || submitting"
        >
            <span x-show="!submitting">Update Password</span>
            <span x-show="submitting" class="flex items-center gap-1">
                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Updating…
            </span>
        </x-filament::button>
    </div>
</div>

{{-- passwordChangeForm() is defined in password-encryption-script.blade.php (BODY_END hook)
     so it is available before Alpine initialises and survives Livewire DOM morphing. --}}
