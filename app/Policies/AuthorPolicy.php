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
        return $user->hasPermission(Permission::ManageSiteContent);
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
        return $user->hasPermission(Permission::ManageSiteContent);
    }
}
