<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\MaterialAccessEvents;
use App\Observers\MaterialAccessEventsObserver;

use Filament\Actions\Action;

use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        MaterialAccessEvents::observe(MaterialAccessEventsObserver::class);

        Action::configureUsing(function (Action $action) {
            // Log::info('Configuring action: ' . $action->getName());
            match ($action->getName()) {
                'save', 'save changes', 'create' => $action->color('success'),
                'cancel', 'delete', => $action->color('danger'),
                default => null,
            };
        });
    }
}
