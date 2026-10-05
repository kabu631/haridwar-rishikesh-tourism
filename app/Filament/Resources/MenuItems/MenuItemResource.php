<?php

namespace App\Filament\Resources\MenuItems;

use App\Filament\Resources\MenuItems\Pages\CreateMenuItem;
use App\Filament\Resources\MenuItems\Pages\EditMenuItem;
use App\Filament\Resources\MenuItems\Pages\ListMenuItems;
use App\Filament\Resources\MenuItems\Schemas\MenuItemForm;
use App\Filament\Resources\MenuItems\Tables\MenuItemsTable;
use App\Models\MenuItem;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

use function Filament\Support\original_request;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Site content';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Menus';

    protected static ?string $modelLabel = 'menu link';

    /**
     * Sidebar: one entry per menu (header navigation, footer links).
     *
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        $menus = [
            'main' => ['label' => 'Header menu', 'icon' => Heroicon::OutlinedBars3],
            'footer' => ['label' => 'Footer links', 'icon' => Heroicon::OutlinedBars3BottomLeft],
        ];

        $items = [];

        foreach ($menus as $menu => $item) {
            $items[] = NavigationItem::make($item['label'])
                ->group(static::getNavigationGroup())
                ->icon($item['icon'])
                ->sort(count($items) + 1)
                ->url(fn (): string => static::getUrl('index', ['tab' => $menu]))
                ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.*')
                    && static::activeMenu() === $menu);
        }

        return $items;
    }

    /**
     * The menu being viewed, added to or edited (the header menu by default).
     */
    public static function activeMenu(): string
    {
        $request = original_request();
        $route = static::getRouteBaseName();

        $menu = match (true) {
            $request->routeIs("{$route}.edit") => MenuItem::query()->whereKey($request->route('record'))->value('menu'),
            $request->routeIs("{$route}.create") => $request->query('menu'),
            default => $request->query('tab'),
        };

        return array_key_exists((string) $menu, MenuItem::MENUS) ? (string) $menu : 'main';
    }

    public static function form(Schema $schema): Schema
    {
        return MenuItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenuItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenuItems::route('/'),
            'create' => CreateMenuItem::route('/create'),
            'edit' => EditMenuItem::route('/{record}/edit'),
        ];
    }
}
