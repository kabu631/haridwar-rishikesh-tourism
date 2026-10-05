<?php

namespace App\Filament\Widgets;

use App\Enums\PageType;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Enquiry;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

/**
 * The jobs done most often, one click from the dashboard.
 */
class QuickActions extends Widget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-actions';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $homepage = PageResource::homepageId();

        return [
            'name' => auth()->user()?->name,
            'actions' => array_values(array_filter([
                [
                    'label' => 'Enquiries',
                    'icon' => Heroicon::OutlinedInboxArrowDown,
                    'url' => EnquiryResource::getUrl('index'),
                    'badge' => Enquiry::query()->where('status', 'new')->count() ?: null,
                    'primary' => true,
                ],
                ['label' => 'Add tour package', 'icon' => Heroicon::OutlinedPlus, 'url' => PageResource::getUrl('create', ['type' => PageType::Package->value])],
                ['label' => 'Add page', 'icon' => Heroicon::OutlinedDocumentPlus, 'url' => PageResource::getUrl('create')],
                $homepage ? ['label' => 'Edit homepage', 'icon' => Heroicon::OutlinedHome, 'url' => PageResource::getUrl('edit', ['record' => $homepage])] : null,
                ['label' => 'Site settings', 'icon' => Heroicon::OutlinedCog6Tooth, 'url' => Settings::getUrl()],
                ['label' => 'View website', 'icon' => Heroicon::OutlinedArrowTopRightOnSquare, 'url' => url('/'), 'external' => true],
            ])),
        ];
    }
}
