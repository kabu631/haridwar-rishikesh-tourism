<?php

namespace App\Filament\Resources\Redirects\Schemas;

use App\Models\Redirect;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(3)->schema([
                    TextInput::make('from_path')
                        ->label('Old URL')
                        ->prefix('/')
                        ->required()
                        ->maxLength(191)
                        ->formatStateUsing(fn (?string $state): ?string => $state === null ? null : ltrim($state, '/'))
                        ->dehydrateStateUsing(fn (?string $state): string => Redirect::normalisePath((string) $state))
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, ?string $state) => $rule->where('from_path', Redirect::normalisePath((string) $state)))
                        ->helperText('The address that no longer exists, e.g. old-page.html')
                        ->columnSpan(2),
                    Select::make('status_code')
                        ->label('Type')
                        ->options([
                            301 => '301 – moved permanently (keeps rankings)',
                            302 => '302 – temporary',
                            410 => '410 – gone (no replacement)',
                        ])
                        ->default(301)
                        ->required()
                        ->live()
                        ->native(false),
                ]),
                TextInput::make('to_path')
                    ->label('New URL')
                    ->maxLength(255)
                    ->required(fn (Get $get): bool => (int) $get('status_code') !== 410)
                    ->hidden(fn (Get $get): bool => (int) $get('status_code') === 410)
                    ->helperText('A page on this site (e.g. /har-ki-pauri.html) or a full https:// address. Point to the most relevant page – not the homepage – to keep rankings.')
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if (blank($value)) {
                                return;
                            }

                            $from = Redirect::normalisePath((string) $get('from_path'));
                            $to = preg_match('#^https?://#i', $value) ? $value : Redirect::normalisePath($value);

                            if (strcasecmp($from, $to) === 0) {
                                $fail('A URL cannot redirect to itself.');

                                return;
                            }

                            $loop = Redirect::query()->where('from_path', $to)->where('to_path', $from)->exists();
                            if ($loop) {
                                $fail('This would create a redirect loop: '.$to.' already redirects back to '.$from.'.');
                            }
                        },
                    ]),
                TextInput::make('note')->maxLength(255),
            ]);
    }
}
