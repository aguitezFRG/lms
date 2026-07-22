<?php

namespace App\Providers;

use App\Enums\RepositoryChangeType;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\SystemUsage;
use App\Listeners\SendDueSoonOnLogin;
use App\Models\MaterialAccessEvents;
use App\Models\RepositoryChangeLogs;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;
use App\Models\User;
use App\Observers\MaterialAccessEventsObserver;
use App\Observers\RepositoryChangeLogsObserver;
use App\Observers\UserObserver;
use App\Policies\DashboardPolicy;
use App\Policies\SystemUsagePolicy;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsIconAlias;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! config('demo.enabled')) {
            return;
        }

        config()->set([
            'cache.default' => 'file',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => config('demo.database_path'),
            'database.connections.sqlite.busy_timeout' => 5000,
            'database.connections.sqlite.journal_mode' => 'MEMORY',
            'database.connections.sqlite.synchronous' => 'NORMAL',
            'filesystems.default' => 'local',
            'filesystems.disks.local.root' => config('demo.storage_path'),
            'mail.default' => 'log',
            'queue.default' => 'sync',
            'session.driver' => 'cookie',
            'session.expire_on_close' => true,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('demo.enabled')) {
            URL::forceRootUrl(config('app.url'));
        }

        Blade::componentNamespace('App\\Filament\\Components', 'onboarding');
        FilamentIcon::register([
            PanelsIconAlias::TOPBAR_OPEN_SIDEBAR_BUTTON => 'heroicon-o-bars-3',
            PanelsIconAlias::TOPBAR_CLOSE_SIDEBAR_BUTTON => 'heroicon-o-bars-3',
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => 'heroicon-o-bars-3',
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL => 'heroicon-o-bars-3',
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => 'heroicon-o-bars-3',
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL => 'heroicon-o-bars-3',
        ]);

        if ((bool) config('app.force_https', false)) {
            URL::forceScheme('https');
        }

        // When behind a reverse proxy (e.g. cloudflared tunnel), the APP_URL
        // forces all generated URLs to use localhost. Clear it so the URL
        // generator uses the real host/scheme from the forwarded headers instead.
        if (! app()->runningInConsole() && app()->bound('request')) {
            $req = app('request');
            if ($req->hasHeader('X-Forwarded-Proto')) {
                URL::forceRootUrl(null);
                URL::forceScheme($req->header('X-Forwarded-Proto', 'https'));
            }
        }

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return [
                Limit::perMinute(3)->by($email),
                Limit::perMinute(3)->by($email.$request->ip()),
                Limit::perMinute(10, 5)->by($request->ip()),
            ];
        });

        RateLimiter::for('google-sso', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('material-stream', function (Request $request) {
            $userId = optional($request->user())->id ?? $request->ip();

            return [
                Limit::perMinute(30)->by($userId),
                Limit::perMinute(60)->by($request->ip()),
            ];
        });

        MaterialAccessEvents::observe(MaterialAccessEventsObserver::class);
        User::observe(UserObserver::class);

        Event::listen(Login::class, SendDueSoonOnLogin::class);
        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            RepositoryChangeLogs::create([
                'editor_id' => $event->user->id,
                'target_user_id' => $event->user->id,
                'table_changed' => 'users',
                'change_type' => RepositoryChangeType::LOGIN->value,
                'change_made' => [
                    'guard' => ['old' => null, 'new' => $event->guard],
                    'remember' => ['old' => null, 'new' => $event->remember],
                ],
                'changed_at' => now(),
            ]);
        });

        Action::configureUsing(function (Action $action) {
            if (config('demo.enabled')) {
                // Filament's partial action-modal renderer can emit an empty
                // fragment in PHP-WASM. A full render keeps all action mounts
                // and mutations on the stable Livewire rendering path.
                $action
                    ->mountUsing(static function (?Schema $schema, Component $livewire): void {
                        $schema?->fill();

                        if (method_exists($livewire, 'forceRender')) {
                            $livewire->forceRender();
                        }
                    })
                    ->before(static function (Component $livewire): void {
                        if (method_exists($livewire, 'forceRender')) {
                            $livewire->forceRender();
                        }
                    });
            }

            // Log::info('Configuring action: ' . $action->getName());
            match ($action->getName()) {
                'save', 'save changes', 'create' => $action->color('success'),
                'cancel', 'delete' => $action->color('danger'),
                default => null,
            };
        });

        Gate::policy(Dashboard::class, DashboardPolicy::class);
        Gate::policy(SystemUsage::class, SystemUsagePolicy::class);

        RrMaterials::observe(RepositoryChangeLogsObserver::class);
        RrMaterialParents::observe(RepositoryChangeLogsObserver::class);
        MaterialAccessEvents::observe(RepositoryChangeLogsObserver::class);
        User::observe(RepositoryChangeLogsObserver::class);

    }
}
