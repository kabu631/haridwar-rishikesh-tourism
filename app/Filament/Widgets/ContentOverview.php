<?php

namespace App\Filament\Widgets;

use App\Enums\PageCategory;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Widgets\Widget;

/**
 * Number of pages in each "Website pages" category, linking to each list.
 */
class ContentOverview extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 1];

    protected string $view = 'filament.widgets.content-overview';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $counts = Page::query()->toBase()
            ->selectRaw('type, COUNT(*) AS total, SUM(is_published) AS live')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        return [
            'rows' => array_map(function (PageCategory $category) use ($counts): array {
                $rows = $counts->only(array_map(fn ($type): string => $type->value, $category->types()));

                return [
                    'label' => $category->label(),
                    'icon' => PageResource::categoryIcon($category),
                    'live' => (int) $rows->sum('live'),
                    'drafts' => (int) $rows->sum('total') - (int) $rows->sum('live'),
                    'url' => PageResource::getUrl('index', ['tab' => $category->value]),
                ];
            }, PageCategory::cases()),
            'allUrl' => PageResource::getUrl('index'),
        ];
    }
}
