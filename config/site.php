<?php

/*
|--------------------------------------------------------------------------
| Business Details
|--------------------------------------------------------------------------
|
| Default name / address / phone (NAP) data used in the header, footer,
| contact page and LocalBusiness structured data. Every value here can be
| overridden from Admin → Settings without a deploy. Keep the NAP identical
| to the Google Business Profile listing.
|
*/

return [

    'name' => 'Haridwar Rishikesh Tourism',
    'legal_name' => 'India Easy Trip Pvt Ltd',
    'tagline' => 'Haridwar & Rishikesh tour packages from a local, government approved travel agent since 1995',
    'founded' => '1995-03',

    'phone' => '+91-9759222888',
    'landline' => '+91-1334-229479',
    'whatsapp' => '919759222888',
    'email' => 'mail@haridwarrishikeshtourism.com',

    'address' => [
        'street' => 'Near Narayanishila Temple, Devpura, Opposite Fire Brigade',
        'locality' => 'Haridwar',
        'region' => 'Uttarakhand',
        'region_code' => 'IN-UT',
        'postal_code' => '249401',
        'country' => 'IN',
    ],

    /*
     | Office coordinates (used for geo meta tags and LocalBusiness schema).
     | Verify against the Google Business Profile pin before launch.
     */
    'geo' => [
        'latitude' => env('SITE_GEO_LAT', '29.9406'),
        'longitude' => env('SITE_GEO_LNG', '78.1513'),
    ],

    // schema.org openingHours, e.g. "Mo-Su 08:00-21:00". Set the real hours before launch.
    'opening_hours' => env('SITE_OPENING_HOURS'),

    /*
     | Short line in the maroon bar above the header.
     */
    'topbar_text' => 'Uttarakhand Tourism approved travel agent since 1995',

    /*
     | Trust badges under the homepage slider. Icons: calendar, shield, award,
     | tripadvisor, users, star, map-pin, phone, temple, mountain, route.
     */
    'trust' => [
        ['icon' => 'calendar', 'title' => 'Since March 1995', 'text' => 'Local Haridwar travel agency'],
        ['icon' => 'shield', 'title' => 'Govt. approved', 'text' => 'Uttarakhand Tourism TA-01/HDR/53/2016-17'],
        ['icon' => 'award', 'title' => 'IATO & ATOAI member', 'text' => 'Tour operator associations'],
        ['icon' => 'tripadvisor', 'title' => 'Reviewed on Tripadvisor', 'text' => 'Real guest feedback'],
    ],

    /*
     | "Talk to a local expert" band shown above the footer.
     */
    'cta' => [
        'eyebrow' => 'Talk to a local expert',
        'heading' => 'Planning Haridwar, Rishikesh or Char Dham? Our Haridwar team is a call away.',
    ],

    'footer_about' => 'Haridwar Rishikesh Tourism is an initiative of India Easy Trip Pvt Ltd, a Haridwar travel agency providing tours in Haridwar, Rishikesh and Uttarakhand since March 1995.',

    'credentials' => [
        ['name' => 'Corporate Identity Number', 'value' => 'U63000UR2012PTC000152'],
        ['name' => 'Uttarakhand Tourism approved travel agent', 'value' => 'TA-01/HDR/53/2016-17'],
        ['name' => 'Indian Association of Tour Operators (IATO)', 'value' => 'ALD151216'],
        ['name' => 'Adventure Tour Operators Association of India', 'value' => '515'],
    ],

    'social' => [
        'facebook' => 'https://www.facebook.com/RishikeshHaridwar',
        'youtube' => 'https://www.youtube.com/user/hrttourreviews',
        'twitter' => 'https://twitter.com/8888Saini',
        'tripadvisor' => 'https://www.tripadvisor.in/Attraction_Review-g580106-d5982072-Reviews-India_Easy_Trip-Rishikesh_Dehradun_District_Uttarakhand.html',
        'tripadvisor_haridwar' => 'https://www.tripadvisor.in/Attraction_Review-g616028-d4868270-Reviews-Haridwar_Rishikesh_Tourism_Private_Day_Tours-Haridwar_Uttarakhand.html',
        'blog' => 'https://haridwarrishikeshtourism.wordpress.com',
        'google_business' => env('SITE_GOOGLE_BUSINESS_URL'),
    ],

    /*
     | Sister websites of India Easy Trip Pvt Ltd linked from the footer.
     */
    'network' => [
        'IndiaEasyTrip.com' => 'https://www.indiaeasytrip.com',
        'ChardhamPackage.com' => 'https://www.chardhampackage.com',
        'NainitalCorbettTourism.com' => 'https://www.nainitalcorbetttourism.com',
        'HaridwarCarRentals.com' => 'https://www.haridwarcarrentals.com',
        'RishikeshTaxiService.com' => 'https://www.rishikeshtaxiservice.com',
    ],

    /*
     | Address that receives enquiry notifications.
     */
    'enquiry_recipient' => env('SITE_ENQUIRY_EMAIL', 'mail@haridwarrishikeshtourism.com'),

];
