<?php

namespace App\Enums;

/**
 * Something a signed-in user may do in the admin panel; granted through their UserRole.
 */
enum Permission: string
{
    case EditPages = 'edit_pages';
    case DeletePages = 'delete_pages';
    case ManageSiteContent = 'manage_site_content';
    case ManageRedirects = 'manage_redirects';
    case ManageEnquiries = 'manage_enquiries';
    case ManageSettings = 'manage_settings';
    case ManageUsers = 'manage_users';
}
