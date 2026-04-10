<?php

namespace Tests;

use App\Models\User;
use App\Models\RrMaterials;
use App\Models\RrMaterialParents;
use App\Observers\RepositoryChangeLogsObserver;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();
    }

    /**
     * Re-register all model observers that AppServiceProvider sets up.
     * Call this in tests that need observers to fire after using the make* helpers.
     */
    protected function reRegisterObservers(): void
    {
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\User::observe(\App\Observers\RepositoryChangeLogsObserver::class);
        \App\Models\RrMaterialParents::observe(\App\Observers\RepositoryChangeLogsObserver::class);
        \App\Models\RrMaterials::observe(\App\Observers\RepositoryChangeLogsObserver::class);
        \App\Models\MaterialAccessEvents::observe(\App\Observers\MaterialAccessEventsObserver::class);
        \App\Models\MaterialAccessEvents::observe(\App\Observers\RepositoryChangeLogsObserver::class);
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