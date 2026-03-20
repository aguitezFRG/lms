<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\UserLogin;
use App\Filament\Pages\User\UserProfile;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\HtmlString;

use Filament\Support\Icons\Heroicon;

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
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="' . asset('images/up-seal.png') . '" alt="UP Seal" style="height: 2.5rem; width: auto;" />
                    <span style="font-size: 1.25rem; white-space: nowrap;">
                        INSTAT-RR-SPRIS
                    </span>
                </div>
            '))
            ->brandName('INSTAT-RR-SPRIS')
            ->login(UserLogin::class)
            ->colors([
                'primary'     => Color::hex('#8D1436'), // UP Maroon
                'success'     => Color::hex('#014421'), // UP Forest Green
                'warning'     => Color::hex('#F3AA2C'), // UP Yellow/Gold
                'danger'      => Color::hex('#8D1436'), // UP Maroon
                'info'        => Color::hex('#014421'), // UP Green
                'gray'        => Color::Slate,
                'black'       => Color::hex('#000000'),
                'white'       => Color::hex('#FFFFFF'),
                'stat-blue'   => Color::hex('#00007d'),
                'stat-yellow' => Color::hex('#fffd0d'),
            ])
            ->discoverResources(
                in: app_path('Filament/Resources/User'),
                for: 'App\Filament\Resources\User'
            )
            ->discoverPages(
                in: app_path('Filament/Pages/User'),
                for: 'App\Filament\Pages\User'
            )
            ->userMenuItems([
                MenuItem::make()
                    ->label('My Profile & Requests')
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
            ->strictAuthorization()
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}