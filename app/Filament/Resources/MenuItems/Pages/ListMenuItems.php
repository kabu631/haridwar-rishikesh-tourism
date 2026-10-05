<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Models\MenuItem;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListMenuItems extends ListRecords
{
    protected static string $resource = MenuItemResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->activeTab === 'footer' ? 'Footer links' : 'Header menu';
    }

    public function getSubheading(): ?string
    {
        return $this->activeTab === 'footer'
            ? 'Links listed at the bottom of every page.'
            : 'The main navigation under the header. Drag to reorder; child links appear in the dropdown.';
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'main' => Tab::make('Header menu')
                ->badge(MenuItem::query()->where('menu', 'main')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('menu', 'main')),
            'footer' => Tab::make('Footer links')
                ->badge(MenuItem::query()->where('menu', 'footer')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('menu', 'footer')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(fn (): string => $this->activeTab === 'footer' ? 'Add footer link' : 'Add menu link')
                ->url(fn (): string => MenuItemResource::getUrl('create', ['menu' => $this->activeTab ?: 'main'])),
        ];
    }
}
