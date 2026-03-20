<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\AdminLogin;
use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use Illuminate\Support\HtmlString;

use Filament\Support\Icons\Heroicon;
use Filament\Navigation\NavigationGroup;

use App\Filament\Pages\Auth\AdminProfile;
use Filament\Navigation\MenuItem;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
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
            ->login(AdminLogin::class)
            ->colors([
                'primary' => Color::hex('#8D1436'), // UP Maroon
                'success' => Color::hex('#014421'), // UP Forest Green
                'warning' => Color::hex('#F3AA2C'), // UP Yellow/Gold
                'danger'  => Color::hex('#8D1436'), // UP Maroon
                'info'    => Color::hex('#014421'), // UP Green
                'gray'    => Color::Slate,
                'black'   => Color::hex('#000000'),
                'white'   => Color::hex('#FFFFFF'),
                'stat-blue'=> Color::hex('#00007d'),
                'stat-yellow'=> Color::hex('#fffd0d'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationGroups([
                'Repository',
                'Logs',
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('My Profile')
                    ->url(fn () => AdminProfile::getUrl())
                    ->icon(Heroicon::OutlinedUser),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            // ->widgets([
            //     AccountWidget::class,
            //     FilamentInfoWidget::class,
            // ])
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->strictAuthorization()
            ->globalSearch(false);
    }
}