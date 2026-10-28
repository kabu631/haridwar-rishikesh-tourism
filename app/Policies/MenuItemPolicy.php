<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function view(User $user, MenuItem $menuItem): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function update(User $user, MenuItem $menuItem): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function reorder(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function delete(User $user, MenuItem $menuItem): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function restore(User $user, MenuItem $menuItem): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function forceDelete(User $user, MenuItem $menuItem): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }
}
