<?php

use App\Http\Middleware\CachePublicPages;
use App\Http\Middleware\EnforceCanonicalHost;
use App\Http\Middleware\RedirectLegacyUrls;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('public')->group(base_path('routes/public.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->prepend([
            EnforceCanonicalHost::class,
            RedirectLegacyUrls::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        $middleware->group('public', [
            SubstituteBindings::class,
            CachePublicPages::class,
        ]);

        // Enquiry forms are embedded in fully cached pages (no session/CSRF
        // token); they are protected by a honeypot and rate limiting instead.
        $middleware->validateCsrfTokens(except: ['enquiry']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
