<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\MaterialAccessEvents;
use App\Observers\MaterialAccessEventsObserver;

use App\Observers\RepositoryChangeLogsObserver;
use App\Models\User;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;

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


        RrMaterials::observe(RepositoryChangeLogsObserver::class);
        RrMaterialParents::observe(RepositoryChangeLogsObserver::class);
        MaterialAccessEvents::observe(RepositoryChangeLogsObserver::class);
        User::observe(RepositoryChangeLogsObserver::class);

    }
}
