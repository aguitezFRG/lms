<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

use App\Models\MaterialAccessEvents;

use App\Observers\MaterialAccessEventsObserver;
use App\Observers\RepositoryChangeLogsObserver;
use App\Observers\UserObserver;

use App\Listeners\SendDueSoonOnLogin;

use App\Models\User;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;

use Filament\Actions\Action;

use Illuminate\Support\Facades\Log;

use App\Policies\DashboardPolicy;
use App\Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Gate;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

use Illuminate\Support\Facades\URL;

use Illuminate\Http\Request;

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
        // if (config('app.env') === 'production' || request()->hasHeader('X-Forwarded-Proto')) {
        //     URL::forceScheme('https');
        // }

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return [
                Limit::perMinute(3)->by($email),                  // 3 attempts per minute per email
                Limit::perMinute(3)->by($email . $request->ip()), // 3 attempts per minute per email+IP
                Limit::perMinute(10, 5)->by($request->ip()),      // 10 attempts every 5 minutes per IP
        ];
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

        RrMaterials::observe(RepositoryChangeLogsObserver::class);
        RrMaterialParents::observe(RepositoryChangeLogsObserver::class);
        MaterialAccessEvents::observe(RepositoryChangeLogsObserver::class);
        User::observe(RepositoryChangeLogsObserver::class);

    }
}
