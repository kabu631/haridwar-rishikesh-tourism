<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile()
            ->brandName('Haridwar Rishikesh Tourism')
            ->brandLogo(asset('images/india-easy-trip-logo-h104.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon-32.png'))
            ->colors([
                'primary' => Color::hex('#bd261f'),
                'warning' => Color::hex('#e5761a'),
                'success' => Color::hex('#02923b'),
                'gray' => Color::Stone,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            // Dashboard and Enquiries sit above these groups.
            ->navigationGroups([
                NavigationGroup::make('Website pages'),
                NavigationGroup::make('Site content'),
                NavigationGroup::make('SEO'),
                NavigationGroup::make('Settings'),
            ])
            ->navigationItems([
                NavigationItem::make('SEO issues')
                    ->group('SEO')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->sort(2)
                    ->url(fn (): string => PageResource::getUrl('index', ['filters' => ['seo_issues' => ['isActive' => true]]])),
                NavigationItem::make('XML sitemap')
                    ->group('SEO')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->sort(3)
                    ->url(fn (): string => url('/sitemap.xml'), shouldOpenInNewTab: true),
            ])
            ->userMenuItems([
                Action::make('website')
                    ->label('View website')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (): string => url('/'))
                    ->openUrlInNewTab(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->databaseNotifications(false)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
