<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Pages\AdminOnboarding;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class AdminLogin extends Login
{
    /**
     * Log out a cross-panel user before showing the login form.
     *
     * Without this, a user authenticated in the user panel navigating to
     * /admin/login gets redirected to /admin by Filament's mount(), but
     * canAccessPanel('admin') returns false → 403.
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
        // the interrupted URL (e.g. /app/requests) in url.intended, which then
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

    /**
     * After successful authentication, redirect to the onboarding page
     * unless a specific intended URL is already stored in the session.
     */
    protected function getAuthenticatedUrl(): string
    {
        return AdminOnboarding::getUrl();
    }

    public function authenticate(): ?LoginResponse
    {
        if (! session()->has('url.intended')) {
            session()->put('url.intended', $this->getAuthenticatedUrl());
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
        return 'Admin Sign In';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return new HtmlString(
            'Student or faculty? <a href="/app/login" class="text-primary-600 hover:underline font-medium">Sign in here</a>'
        );
    }
}
