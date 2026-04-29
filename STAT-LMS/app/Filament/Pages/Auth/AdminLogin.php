<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Pages\AdminOnboarding;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        // the interrupted URL (e.g. /app/user/requests) in url.intended, which then
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
     * After successful authentication, redirect to the onboarding page
     * unless a specific intended URL is already stored in the session.
     */
    protected function getAuthenticatedUrl(): string
    {
        return AdminOnboarding::getUrl();
    }

    public function authenticate(): ?LoginResponse
    {
        $email = $this->data['email'] ?? null;
        $genericMessage = 'Invalid credentials.';

        if ($email && User::withTrashed()->where('email', $email)->whereNotNull('deleted_at')->exists()) {
            Log::notice('Login denied due to account state.', [
                'panel' => 'admin',
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
                'panel' => 'admin',
                'reason' => 'banned',
                'user_id' => $candidate->id,
                'ip' => request()->ip(),
            ]);
            throw ValidationException::withMessages([
                'data.email' => $genericMessage,
            ]);
        }

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
