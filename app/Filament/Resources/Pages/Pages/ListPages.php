<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\PageCategory;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->activeCategory()?->label() ?? 'All pages';
    }

    public function getSubheading(): ?string
    {
        return $this->activeCategory()?->description();
    }

    public function getBreadcrumb(): ?string
    {
        return $this->activeCategory()?->label() ?? 'All pages';
    }

    /**
     * One tab per page category, with the number of pages in each.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $counts = Page::query()->toBase()->selectRaw('type, COUNT(*) AS total')->groupBy('type')->pluck('total', 'type');

        $tabs = ['all' => Tab::make('All pages')->badge($counts->sum())];

        foreach (PageCategory::cases() as $category) {
            $types = array_map(fn ($type): string => $type->value, $category->types());

            $tabs[$category->value] = Tab::make($category->label())
                ->badge($counts->only($types)->sum())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('type', $types));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(fn (): string => 'Add '.($this->activeCategory()?->singularLabel() ?? 'page'))
                ->url(fn (): string => PageResource::getUrl('create', array_filter(['type' => $this->activeCategory()?->defaultType()->value]))),
        ];
    }

    private function activeCategory(): ?PageCategory
    {
        return PageCategory::tryFrom((string) $this->activeTab);
    }
}
