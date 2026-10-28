<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Enquiry;
use App\Models\User;

class EnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageEnquiries);
    }

    public function view(User $user, Enquiry $enquiry): bool
    {
        return $user->hasPermission(Permission::ManageEnquiries);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageEnquiries);
    }

    public function update(User $user, Enquiry $enquiry): bool
    {
        return $user->hasPermission(Permission::ManageEnquiries);
    }

    public function reorder(User $user): bool
    {
        return $user->hasPermission(Permission::ManageEnquiries);
    }

    public function delete(User $user, Enquiry $enquiry): bool
    {
        return $user->hasPermission(Permission::DeleteEnquiries);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermission(Permission::DeleteEnquiries);
    }

    public function restore(User $user, Enquiry $enquiry): bool
    {
        return $user->hasPermission(Permission::DeleteEnquiries);
    }

    public function forceDelete(User $user, Enquiry $enquiry): bool
    {
        return $user->hasPermission(Permission::DeleteEnquiries);
    }
}
