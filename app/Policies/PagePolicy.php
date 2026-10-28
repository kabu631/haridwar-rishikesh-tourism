<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Page;
use App\Models\User;

/**
 * Staff, editors and admins add and edit pages; only editors and admins delete them.
 */
class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::EditPages);
    }

    public function view(User $user, Page $page): bool
    {
        return $user->hasPermission(Permission::EditPages);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::EditPages);
    }

    public function update(User $user, Page $page): bool
    {
        return $user->hasPermission(Permission::EditPages);
    }

    public function replicate(User $user, Page $page): bool
    {
        return $user->hasPermission(Permission::EditPages);
    }

    public function reorder(User $user): bool
    {
        return $user->hasPermission(Permission::EditPages);
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->hasPermission(Permission::DeletePages);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermission(Permission::DeletePages);
    }

    public function restore(User $user, Page $page): bool
    {
        return $user->hasPermission(Permission::DeletePages);
    }

    public function forceDelete(User $user, Page $page): bool
    {
        return $user->hasPermission(Permission::DeletePages);
    }
}
