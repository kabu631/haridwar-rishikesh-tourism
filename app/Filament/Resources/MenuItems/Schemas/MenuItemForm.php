<?php

namespace App\Filament\Resources\MenuItems\Schemas;

use App\Models\MenuItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(3)->schema([
                    Select::make('menu')
                        ->options(MenuItem::MENUS)
                        ->default(fn (): string => array_key_exists((string) request()->query('menu'), MenuItem::MENUS) ? (string) request()->query('menu') : 'main')
                        ->required()
                        ->live()
                        ->native(false),
                    Select::make('parent_id')
                        ->label('Parent item')
                        ->options(fn (Get $get, ?MenuItem $record) => MenuItem::query()
                            ->where('menu', $get('menu') ?? 'main')
                            ->whereNull('parent_id')
                            ->when($record, fn ($query) => $query->whereKeyNot($record->id))
                            ->orderBy('sort_order')
                            ->pluck('label', 'id'))
                        ->placeholder('Top level')
                        ->native(false),
                    TextInput::make('sort_order')->numeric()->default(0),
                ]),
                Grid::make(2)->schema([
                    TextInput::make('label')->required()->maxLength(120),
                    TextInput::make('url')->required()->maxLength(255)->helperText('/page.html for this site, or a full https:// address.'),
                ]),
                TextInput::make('title')->label('Link title (tooltip)')->maxLength(255),
                Grid::make(2)->schema([
                    Toggle::make('open_in_new_tab'),
                    Toggle::make('is_active')->label('Visible')->default(true),
                ]),
            ]);
    }
}
