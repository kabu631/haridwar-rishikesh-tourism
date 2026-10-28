<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Only admins manage user accounts, and nobody can delete their own account.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function reorder(User $user): bool
    {
        return false;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermission(Permission::ManageUsers) && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasPermission(Permission::ManageUsers) && $user->isNot($model);
    }
}
