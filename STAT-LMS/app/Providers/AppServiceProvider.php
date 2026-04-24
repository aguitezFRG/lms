<?php

namespace App\Providers;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\SystemUsage;
use App\Listeners\SendDueSoonOnLogin;
use App\Models\MaterialAccessEvents;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;
use App\Models\User;
use App\Observers\MaterialAccessEventsObserver;
use App\Observers\RepositoryChangeLogsObserver;
use App\Observers\UserObserver;
use App\Policies\DashboardPolicy;
use App\Policies\SystemUsagePolicy;
use Filament\Actions\Action;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->singleton(
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::componentNamespace('App\\Filament\\Components', 'onboarding');

        // Only force HTTPS if X-Forwarded-Proto header is present (ngrok or reverse proxy)
        if (request()->hasHeader('X-Forwarded-Proto')) {
            URL::forceScheme('https');
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

        MaterialAccessEvents::observe(MaterialAccessEventsObserver::class);
        User::observe(UserObserver::class);

        Event::listen(Login::class, SendDueSoonOnLogin::class);

        Action::configureUsing(function (Action $action) {
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
