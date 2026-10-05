<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legacy Website
    |--------------------------------------------------------------------------
    |
    | The live website whose content, URLs and SEO signals are migrated into
    | this application. Used by the legacy:fetch, legacy:import and
    | seo:parity commands only — never at request time.
    |
    */

    'origin' => env('LEGACY_ORIGIN', 'https://www.haridwarrishikeshtourism.com'),

    'user_agent' => 'Mozilla/5.0 (compatible; HRT-Migration/1.0; +https://www.haridwarrishikeshtourism.com)',

    'archive_path' => storage_path('app/legacy'),

    /*
     | CA bundle for TLS verification. Local PHP builds on Windows often ship
     | without one; point this at a cacert.pem (https://curl.se/ca/cacert.pem).
     */
    'ca_bundle' => env('LEGACY_CA_BUNDLE', file_exists(storage_path('app/legacy/cacert.pem')) ? storage_path('app/legacy/cacert.pem') : true),

    /*
     | Root-level files that are referenced by robots.txt or search engines but
     | are not part of the sitemap.
     */
    'extra_paths' => [
        'index.html',
        'sitemap.html',
        'urllist.txt',
        'ror.xml',
        'BingSiteAuth.xml',
        'favicon.ico',
        'favicon_16x16.ico',
    ],

];
