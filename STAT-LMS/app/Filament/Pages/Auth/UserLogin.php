<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

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
