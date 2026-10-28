<?php

namespace App\Enums;

/**
 * Admin panel role of a user; decides which permissions they hold.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Editor => 'Editor',
            self::Staff => 'Staff',
        };
    }

    /**
     * Short explanation shown when choosing a role.
     */
    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Full access, including deleting and exporting enquiries, site settings and user accounts.',
            self::Editor => 'Handles enquiries; adds, edits and deletes pages; manages menus, testimonials, authors and redirects.',
            self::Staff => 'Handles enquiries; adds and edits pages. Cannot delete anything or change other settings.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Editor => 'warning',
            self::Staff => 'gray',
        };
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Editor => [Permission::EditPages, Permission::DeletePages, Permission::ManageSiteContent, Permission::ManageRedirects, Permission::ManageEnquiries],
            self::Staff => [Permission::EditPages, Permission::ManageEnquiries],
        };
    }

    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
