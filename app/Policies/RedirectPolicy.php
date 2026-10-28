<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Redirect;
use App\Models\User;

class RedirectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageRedirects);
    }

    public function view(User $user, Redirect $redirect): bool
    {
        return $user->hasPermission(Permission::ManageRedirects);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageRedirects);
    }

    public function update(User $user, Redirect $redirect): bool
    {
        return $user->hasPermission(Permission::ManageRedirects);
    }

    public function reorder(User $user): bool
    {
        return $user->hasPermission(Permission::ManageRedirects);
    }

    public function delete(User $user, Redirect $redirect): bool
    {
        return $user->hasPermission(Permission::ManageRedirects);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageRedirects);
    }

    public function restore(User $user, Redirect $redirect): bool
    {
        return $user->hasPermission(Permission::ManageRedirects);
    }

    public function forceDelete(User $user, Redirect $redirect): bool
    {
        return $user->hasPermission(Permission::ManageRedirects);
    }
}
