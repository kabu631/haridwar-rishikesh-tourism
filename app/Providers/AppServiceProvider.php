<?php

namespace App\Providers;

use App\Models\Author;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\Media\MediaLibrary;
use App\Support\PageCache;
use App\Support\SiteSettings;
use App\View\Composers\LayoutComposer;
use App\View\Composers\NotFoundComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(SiteSettings::class);
        $this->app->scoped(MediaLibrary::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('enquiries', fn (Request $request): array => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perDay(40)->by($request->ip()),
        ]);

        RateLimiter::for('search', fn (Request $request): Limit => Limit::perMinute(90)->by($request->ip()));

        // Root-relative asset URLs ("/build/…") so fully cached HTML works on any
        // host or port. A CDN can still be used by setting ASSET_URL.
        if (blank(config('app.asset_url'))) {
            Vite::createAssetPathsUsing(fn (string $path): string => '/'.ltrim($path, '/'));
        }

        View::share('site', $this->app->make(SiteSettings::class));
        View::composer('layouts.app', LayoutComposer::class);
        View::composer(['errors::404', 'errors.404'], NotFoundComposer::class);

        // Anything that appears in the cached HTML invalidates the page cache.
        foreach ([Author::class, MenuItem::class, Setting::class, Testimonial::class] as $model) {
            $model::saved(fn () => PageCache::flush());
            $model::deleted(fn () => PageCache::flush());
        }

        Setting::saved(fn () => $this->app->make(SiteSettings::class)->refresh());

        Media::saved(fn () => MediaLibrary::forget());
        Media::deleted(fn () => MediaLibrary::forget());
    }
}
