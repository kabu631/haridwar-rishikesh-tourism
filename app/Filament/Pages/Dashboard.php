<?php

namespace App\Filament\Pages;

use App\Support\SiteSettings;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Admin home: quick actions, key numbers, latest enquiries, content by
 * category and SEO checks (see app/Filament/Widgets).
 */
class Dashboard extends BaseDashboard
{
    public function getSubheading(): ?string
    {
        return 'Everything for '.app(SiteSettings::class)->get('name').' in one place.';
    }

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 3];
    }
}
