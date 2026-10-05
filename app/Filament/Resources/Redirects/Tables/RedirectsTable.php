<?php

namespace App\Filament\Resources\Redirects\Tables;

use App\Models\Redirect;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RedirectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('from_path')->label('Old URL')->searchable()->copyable()->weight('medium'),
                TextColumn::make('to_path')->label('Redirects to')->searchable()->placeholder('— gone —')->url(fn (Redirect $record): ?string => $record->to_path)->openUrlInNewTab(),
                TextColumn::make('status_code')->label('Type')->badge()->color(fn (int $state): string => match ($state) {
                    301 => 'success',
                    302 => 'warning',
                    default => 'gray',
                }),
                TextColumn::make('hits')->numeric()->sortable(),
                TextColumn::make('last_hit_at')->label('Last used')->since()->placeholder('Never')->sortable(),
                TextColumn::make('note')->limit(40)->toggleable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status_code')->label('Type')->options([301 => '301', 302 => '302', 410 => '410']),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
