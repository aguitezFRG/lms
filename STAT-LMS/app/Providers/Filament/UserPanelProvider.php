<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\UserLogin;
use App\Filament\Pages\Onboarding\CompleteProfile;
use App\Filament\Pages\User\UserOnboarding;
use App\Filament\Pages\User\UserProfile;
use App\Http\Middleware\RedirectIfBanned;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ->default()
            ->id('user')
            ->path('app')
            ->viteTheme('resources/css/filament/user/theme.css')
            ->brandLogoHeight('2.5rem')
            ->brandLogo(new HtmlString('
                <div style="display: flex; align-items: center; gap: 16px; padding: 4px 0;">
                    <img src="'.asset('images/up-seal.png').'" alt="UP Seal" style="height: 2.5rem; width: auto; flex-shrink: 0;" />
                    <span style="font-family: ui-sans-serif, system-ui, -apple-system, &quot;Segoe UI&quot;, Roboto, Ubuntu, Cantarell, &quot;Noto Sans&quot;, sans-serif, BlinkMacSystemFont, &quot;Helvetica Neue&quot;, Arial, &quot;Apple Color Emoji&quot;, &quot;Segoe UI Emoji&quot;, &quot;Segoe UI Symbol&quot;, &quot;Noto Color Emoji&quot;; font-size: 1.1rem; font-weight: 600; white-space: nowrap; letter-spacing: 0.01em;">
                        INSTAT-RR-SPRIS
                    </span>
                </div>
            '))
            ->brandName('INSTAT-RR-SPRIS')
            ->login(UserLogin::class)
            ->homeUrl(fn () => UserOnboarding::getUrl())
            ->colors([
                'primary' => Color::hex('#8D1436'), // UP Maroon (PANTONE 1955C)
                'success' => Color::hex('#014421'), // UP Forest Green (PANTONE 7484C)
                'warning' => Color::hex('#f55536'), // UP Gold (PANTONE 1235C)
                'danger' => Color::hex('#8D1436'), // UP Maroon
                'info' => Color::hex('#014421'), // UP Forest Green
                'stat-yellow' => Color::hex('#F3AA2C'),
                'stat-blue' => Color::hex('#1a3a8f'),
                'gray' => Color::Slate,
            ])
            ->discoverResources(
                in: app_path('Filament/Resources/User'),
                for: 'App\Filament\Resources\User'
            )
            ->discoverPages(
                in: app_path('Filament/Pages/User'),
                for: 'App\Filament\Pages\User'
            )
            ->pages([
                CompleteProfile::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('My Profile')
                    ->url(fn () => UserProfile::getUrl())
                    ->icon(Heroicon::OutlinedUser),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(false)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.components.password-encryption-script'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => auth()->check() ? view('filament.components.request-status-toast-poller-hook') : '',
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn () => view('filament.components.session-flash'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn () => view('filament.components.google-sso-button'),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => auth()->check() ? view('filament.components.notification-bell-hook') : '',
            )
            ->strictAuthorization()
            ->authMiddleware([
                RedirectIfBanned::class,
                Authenticate::class,
                \App\Http\Middleware\EnsureProfileComplete::class,
            ]);
    }
}
