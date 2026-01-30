<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ValidityStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class ValidityStatusPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_validity::status');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ValidityStatus $validityStatus): bool
    {
        return $user->can('view_validity::status');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_validity::status');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ValidityStatus $validityStatus): bool
    {
        return $user->can('update_validity::status');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ValidityStatus $validityStatus): bool
    {
        return $user->can('delete_validity::status');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_validity::status');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, ValidityStatus $validityStatus): bool
    {
        return $user->can('force_delete_validity::status');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_validity::status');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, ValidityStatus $validityStatus): bool
    {
        return $user->can('restore_validity::status');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_validity::status');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, ValidityStatus $validityStatus): bool
    {
        return $user->can('replicate_validity::status');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_validity::status');
    }
}
