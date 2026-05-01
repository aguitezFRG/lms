<?php

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use App\Filament\Pages\AdminOnboarding;
use App\Filament\Pages\User\UserOnboarding;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class UserLogin extends Login
{
    /**
     * Log out a cross-panel user before showing the login form.
     *
     * Without this, a user authenticated in the admin panel navigating to
     * /app/login gets redirected to /app by Filament's mount(), but
     * canAccessPanel('user') returns false → 403.
     */
    public function mount(): void
    {
        if (auth()->check() && ! auth()->user()->canAccessPanel(Filament::getCurrentPanel())) {
            auth()->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        // Discard any intended URL that belongs to a different panel.
        // When Laravel's AuthenticateSession invalidates a session it stores
        // the interrupted URL (e.g. /admin/users) in url.intended, which then
        // poisons redirect()->intended() on the next login — sending the new
        // user to a panel they cannot access → 403.
        if (request()->hasSession()) {
            $intended = request()->session()->get('url.intended', '');
            $panelPrefix = '/'.Filament::getCurrentPanel()->getPath();

            if ($intended && ! str_starts_with($intended, $panelPrefix)) {
                request()->session()->forget('url.intended');
            }
        }

        parent::mount();

        if ($error = session('error')) {
            Notification::make()
                ->title($error)
                ->danger()
                ->send();
        }
    }

    /**
     * Route the authenticated user to the correct panel based on their role.
     */
    protected function getAuthenticatedUrl(): string
    {
        $user = auth()->user();

        if ($user === null) {
            return UserOnboarding::getUrl();
        }

        return $this->isAdminRole($user->role)
            ? AdminOnboarding::getUrl()
            : UserOnboarding::getUrl();
    }

    public function authenticate(): ?LoginResponse
    {
        $email = $this->data['email'] ?? null;
        $genericMessage = 'Invalid credentials.';

        if ($email && User::withTrashed()->where('email', $email)->whereNotNull('deleted_at')->exists()) {
            Log::notice('Login denied due to account state.', [
                'panel' => 'user',
                'reason' => 'soft_deleted',
                'email_hash' => hash('sha256', strtolower(trim((string) $email))),
                'ip' => request()->ip(),
            ]);
            throw ValidationException::withMessages([
                'data.email' => $genericMessage,
            ]);
        }

        $candidate = $email ? User::where('email', $email)->whereNull('deleted_at')->first() : null;
        if ($candidate?->is_banned) {
            Log::notice('Login denied due to account state.', [
                'panel' => 'user',
                'reason' => 'banned',
                'user_id' => $candidate->id,
                'ip' => request()->ip(),
            ]);
            throw ValidationException::withMessages([
                'data.email' => $genericMessage,
            ]);
        }

        return parent::authenticate();
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->color('success');
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'User Sign In';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return new HtmlString(
            '<span class="text-gray-600 dark:text-gray-400">Committee or Reading Room Staff?</span> <a href="/admin/login" class="text-primary-600 hover:underline font-medium">Sign in here</a>'
        );
    }

    private function isAdminRole(UserRole $role): bool
    {
        return in_array($role, [
            UserRole::SUPER_ADMIN,
            UserRole::COMMITTEE,
            UserRole::IT,
            UserRole::RR,
        ], true);
    }
}
