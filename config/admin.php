<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Path
    |--------------------------------------------------------------------------
    |
    | URL segment of the admin panel (https://example.com/{path}). Kept away
    | from the guessable "/admin" and out of robots.txt; set ADMIN_PATH on the
    | server to use a private value instead of this default.
    |
    */

    'path' => trim(env('ADMIN_PATH', 'hrtsiteadmin'), '/'),

];
