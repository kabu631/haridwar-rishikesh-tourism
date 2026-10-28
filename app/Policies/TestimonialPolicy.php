<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Testimonial;
use App\Models\User;

class TestimonialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function view(User $user, Testimonial $testimonial): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function update(User $user, Testimonial $testimonial): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function reorder(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function delete(User $user, Testimonial $testimonial): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function restore(User $user, Testimonial $testimonial): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }

    public function forceDelete(User $user, Testimonial $testimonial): bool
    {
        return $user->hasPermission(Permission::ManageSiteContent);
    }
}
