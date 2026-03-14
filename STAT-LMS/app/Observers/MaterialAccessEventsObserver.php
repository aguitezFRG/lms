<?php

namespace App\Observers;

use App\Models\MaterialAccessEvents;

class MaterialAccessEventsObserver
{

    public function retrieved(MaterialAccessEvents $materialAccessEvents): void
    {
        if
        (
            $materialAccessEvents->due_at &&
            !$materialAccessEvents->returned_at &&
            !$materialAccessEvents->completed_at &&
            $materialAccessEvents->due_at->isPast() &&
            !$materialAccessEvents->is_overdue
        )
        {
            $materialAccessEvents->updateQuietly(['is_overdue' => true]);
        }

    }

    /**
     * Handle the MaterialAccessEvents "created" event.
     */
    public function created(MaterialAccessEvents $materialAccessEvents): void
    {
        //
    }

    /**
     * Handle the MaterialAccessEvents "updated" event.
     */
    public function updated(MaterialAccessEvents $materialAccessEvents): void
    {
        //
    }

    /**
     * Handle the MaterialAccessEvents "deleted" event.
     */
    public function deleted(MaterialAccessEvents $materialAccessEvents): void
    {
        //
    }

    /**
     * Handle the MaterialAccessEvents "restored" event.
     */
    public function restored(MaterialAccessEvents $materialAccessEvents): void
    {
        //
    }

    /**
     * Handle the MaterialAccessEvents "force deleted" event.
     */
    public function forceDeleted(MaterialAccessEvents $materialAccessEvents): void
    {
        //
    }
}
