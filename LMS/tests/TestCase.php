<?php

namespace Tests;

use App\Models\MaterialAccessEvents;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;
use App\Models\User;
use App\Observers\MaterialAccessEventsObserver;
use App\Observers\RepositoryChangeLogsObserver;
use App\Observers\UserObserver;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Re-register all model observers that AppServiceProvider sets up.
     * Call this in tests that need observers to fire after using the make* helpers.
     */
    protected function reRegisterObservers(): void
    {
        User::observe(UserObserver::class);
        User::observe(RepositoryChangeLogsObserver::class);
        RrMaterialParents::observe(RepositoryChangeLogsObserver::class);
        RrMaterials::observe(RepositoryChangeLogsObserver::class);
        MaterialAccessEvents::observe(MaterialAccessEventsObserver::class);
        MaterialAccessEvents::observe(RepositoryChangeLogsObserver::class);
    }

    /**
     * Create a User without triggering the RepositoryChangeLogsObserver.
     */
    protected function makeUser(string $role, array $attributes = []): User
    {
        User::flushEventListeners();
        $user = User::factory()->create(array_merge(['role' => $role], $attributes));
        User::observe(RepositoryChangeLogsObserver::class);

        return $user;
    }

    /**
     * Create an RrMaterialParents record without triggering the observer.
     */
    protected function makeMaterialParent(array $attributes = []): RrMaterialParents
    {
        RrMaterialParents::flushEventListeners();
        $parent = RrMaterialParents::factory()->create($attributes);
        RrMaterialParents::observe(RepositoryChangeLogsObserver::class);

        return $parent;
    }

    /**
     * Create an RrMaterials record without triggering the observer.
     */
    protected function makeMaterialCopy(array $attributes = []): RrMaterials
    {
        // parent model must already exist; flush both to be safe
        RrMaterialParents::flushEventListeners();
        RrMaterials::flushEventListeners();

        $copy = RrMaterials::factory()->create($attributes);

        RrMaterialParents::observe(RepositoryChangeLogsObserver::class);
        RrMaterials::observe(RepositoryChangeLogsObserver::class);

        return $copy;
    }
}
