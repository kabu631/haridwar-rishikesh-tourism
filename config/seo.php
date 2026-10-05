<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Canonical Site URL
    |--------------------------------------------------------------------------
    |
    | Absolute origin used for canonical tags, sitemaps, Open Graph and schema.
    | In production this MUST be https://www.haridwarrishikeshtourism.com so
    | every page keeps the exact URL Google has indexed.
    |
    */

    'site_url' => rtrim(env('SEO_SITE_URL', env('APP_URL', 'http://localhost')), '/'),

    /*
    |--------------------------------------------------------------------------
    | Indexing
    |--------------------------------------------------------------------------
    |
    | Production allows indexing by default. Any other environment (local,
    | staging) sends "noindex" and a disallow-all robots.txt so a copy of the
    | site can never compete with the live one in search results.
    |
    */

    'allow_indexing' => (bool) env('SEO_ALLOW_INDEXING', env('APP_ENV') === 'production'),

    /*
     | Redirect http → https and bare domain → www in a single 301 hop.
     | Enable in production (Apache/Nginx can do this too; doing it in one
     | place avoids redirect chains).
     */
    'force_canonical_host' => (bool) env('SEO_FORCE_CANONICAL_HOST', env('APP_ENV') === 'production'),

    /*
     | Google ignores the keywords meta tag; Bing treats stuffed keywords as a
     | spam signal. The legacy values are kept in the database but are not
     | printed unless this is enabled.
     */
    'render_meta_keywords' => (bool) env('SEO_RENDER_META_KEYWORDS', false),

    'default_robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',

    'default_image' => '/haridwar-images/haridwar-ganga-aarti.jpg',

    'locale' => 'en_IN',

    /*
     | hreflang: the site is English for a worldwide audience, so pages
     | declare "en" plus "x-default" (both self-referencing).
     */
    'hreflang' => ['en', 'x-default'],

    'twitter_handle' => '@8888Saini',

    /*
     | Search engine verification codes (Search Console, Bing, Yandex).
     */
    'verification' => [
        'google' => env('SEO_GOOGLE_VERIFICATION', 'jl-_CUy-olSP22yfYjf5whYo5aX3mrGnDJaY0uYl0U0'),
        'bing' => env('SEO_BING_VERIFICATION'),
        'yandex' => env('SEO_YANDEX_VERIFICATION'),
    ],

    'analytics' => [
        'google_ads_id' => env('GOOGLE_ADS_ID', 'AW-997863386'),
        'ga4_id' => env('GA4_MEASUREMENT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI / Generative Engine crawlers
    |--------------------------------------------------------------------------
    |
    | Allowing these crawlers lets ChatGPT, Claude, Perplexity, Gemini and
    | Copilot read and cite the site (Generative Engine Optimisation).
    |
    */

    'ai_crawlers' => [
        'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-User', 'Claude-SearchBot',
        'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'Bingbot', 'CCBot',
    ],

    /*
     | Paths kept out of search engines.
     */
    'disallow' => ['/backup/', '/admin', '/search', '/chatbot/', '/enquiry', '/thank-you'],

    /*
     | IndexNow (Bing, Yandex, Seznam, Naver…): when a page is saved in the
     | admin, its URL is submitted for re-crawling immediately. Set a random
     | 32-character key to enable; it is served at /{key}.txt for verification.
     */
    'indexnow_key' => env('INDEXNOW_KEY'),

    /*
     | Seconds a rendered public page is cached (server side). Content edits
     | in the admin panel clear the cache immediately.
     */
    'page_cache_ttl' => (int) env('PAGE_CACHE_TTL', 86400),

];
