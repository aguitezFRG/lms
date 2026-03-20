<?php

namespace Tests;

use App\Models\User;
use App\Models\RrMaterials;
use App\Models\RrMaterialParents;
use App\Observers\RepositoryChangeLogsObserver;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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