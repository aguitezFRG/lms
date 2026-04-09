<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * When a user is banned, revoke all their approved/pending access events
     * so they immediately lose access to approved materials.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('is_banned') && $user->is_banned) {
            $user->materialAccessEvents()
                ->whereIn('status', ['approved', 'pending'])
                ->update(['status' => 'revoked']);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
