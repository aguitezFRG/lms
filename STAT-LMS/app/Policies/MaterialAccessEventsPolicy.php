<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MaterialAccessEvents;
use App\Models\User;

class MaterialAccessEventsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            \App\Enums\UserRole::SUPER_ADMIN,
            \App\Enums\UserRole::COMMITTEE,
            \App\Enums\UserRole::IT,
            \App\Enums\UserRole::RR,
            \App\Enums\UserRole::FACULTY,
            \App\Enums\UserRole::STUDENT,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MaterialAccessEvents $materialAccessEvents): bool
    {
        // Users can view their own requests, as well as committee, IT, and RR staff can view all requests
        return $materialAccessEvents->user_id == $user->id ||
               in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT, UserRole::RR]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MaterialAccessEvents $materialAccessEvents): bool
    {
        $user_role = $user->role;

        $updated_by = $materialAccessEvents->approver_id;

        // Allow the user who last updated the event to update it again, as well as committee and IT
        // RR staff can only update if the material access event is of an open material copy
        // Users can update if they're the one who initiated the request, but only if it hasn't been approved yet (i.e. approver_id is null)
        return in_array($user_role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]) ||
               $updated_by == $user->id ||
               ($user_role === UserRole::RR && $materialAccessEvents->material?->parent?->access_level == 1 && $materialAccessEvents->material?->is_digital == false) ||
               ($materialAccessEvents->user_id == $user->id && $materialAccessEvents->approver_id == null);
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        // return in_array($user->role, [UserRole::COMMITTEE, UserRole::IT]);

        // TO CLARIFY: No one should be able to delete material access events
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MaterialAccessEvents $materialAccessEvents): bool
    {
        // return in_array($user->role, [UserRole::COMMITTEE, UserRole::IT]);

        // TO CLARIFY: No one should be able to delete material access events
        return false;
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MaterialAccessEvents $materialAccessEvents): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false; // No one can permanently delete material access events
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MaterialAccessEvents $materialAccessEvents): bool
    {
        return false; // No one can permanently delete material access events
    }
}
