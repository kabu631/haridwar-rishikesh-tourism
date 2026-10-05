<?php

namespace App\Filament\Resources\Authors\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AuthorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(3)->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(120)->helperText('Anchor on the Our Team page.'),
                    TextInput::make('job_title')->maxLength(160),
                ]),
                Textarea::make('bio')
                    ->rows(4)
                    ->maxLength(1500)
                    ->helperText('Shown under every article by this author. Mention real, verifiable experience (years guiding, licences, regions covered).'),
                TagsInput::make('same_as')
                    ->label('Profile links')
                    ->placeholder('https://…')
                    ->helperText('LinkedIn, X/Twitter, Instagram… Used in Person structured data.'),
            ]);
    }
}
