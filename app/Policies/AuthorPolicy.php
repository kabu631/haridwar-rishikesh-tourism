<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Author;
use App\Models\User;

class AuthorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function view(User $user, Author $author): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function update(User $user, Author $author): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function reorder(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function delete(User $user, Author $author): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent) && $this->canRemoveLoginOf($user, $author);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function restore(User $user, Author $author): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function forceDelete(User $user, Author $author): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent) && $this->canRemoveLoginOf($user, $author);
    }

    /**
     * Deleting a team member also deletes their admin login: only admins may
     * do that, and never to their own account.
     */
    private function canRemoveLoginOf(User $user, Author $author): bool
    {
        $login = $author->user;

        return $login === null || ($user->hasPermission(Permission::ManageUsers) && $user->isNot($login));
    }
}
