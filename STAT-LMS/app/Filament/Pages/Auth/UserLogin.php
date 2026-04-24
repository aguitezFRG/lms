<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
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

    public function authenticate(): ?LoginResponse
    {
        $email = $this->data['email'] ?? null;

        if ($email && User::withTrashed()->where('email', $email)->whereNotNull('deleted_at')->exists()) {
            throw ValidationException::withMessages([
                'data.email' => 'This account has been deactivated. Please contact the administrator.',
            ]);
        }

        $candidate = $email ? User::where('email', $email)->whereNull('deleted_at')->first() : null;
        if ($candidate?->is_banned) {
            throw ValidationException::withMessages([
                'data.email' => 'Your account is banned from accessing the system. Please contact the administrator.',
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
            'Staff or committee member? <a href="/admin/login" class="text-primary-600 hover:underline font-medium">Sign in here</a>'
        );
    }
}
