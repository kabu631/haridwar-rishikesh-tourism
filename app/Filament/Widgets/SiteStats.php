<?php

namespace App\Filament\Widgets;

use App\Enums\PageCategory;
use App\Enums\PageType;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Enquiry;
use App\Models\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $trend = collect(range(13, 0))
            ->map(fn (int $daysAgo): int => Enquiry::query()->where('status', '!=', 'spam')->whereDate('created_at', now()->subDays($daysAgo))->count())
            ->all();

        $needsReview = Page::query()->published()->where(fn ($query) => $query->whereNull('reviewed_at')->orWhere('reviewed_at', '<', now()->subYear()))->count();

        return [
            Stat::make('New enquiries', Enquiry::query()->where('status', 'new')->count())
                ->description(Enquiry::query()->where('status', '!=', 'spam')->where('created_at', '>=', now()->subDays(7))->count().' received in the last 7 days')
                ->descriptionIcon(Heroicon::OutlinedInboxArrowDown)
                ->chart($trend)
                ->color('primary')
                ->url(EnquiryResource::getUrl('index')),
            Stat::make('Tour packages', Page::query()->published()->where('type', PageType::Package)->count())
                ->description('Live on the website')
                ->descriptionIcon(Heroicon::OutlinedMap)
                ->url(PageResource::getUrl('index', ['tab' => PageCategory::Packages->value])),
            Stat::make('Published pages', Page::query()->published()->count())
                ->description(Page::query()->where('updated_at', '>=', now()->subDays(30))->count().' updated in the last 30 days')
                ->descriptionIcon(Heroicon::OutlinedDocumentDuplicate)
                ->url(PageResource::getUrl('index')),
            Stat::make('Pages to review', $needsReview)
                ->description('Facts not checked in 12 months')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($needsReview > 0 ? 'warning' : 'success')
                ->url(PageResource::getUrl('index', ['filters' => ['stale' => ['isActive' => true]]])),
        ];
    }
}
