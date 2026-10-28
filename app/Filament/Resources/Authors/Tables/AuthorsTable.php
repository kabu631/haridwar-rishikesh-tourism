<?php

namespace App\Filament\Resources\Authors\Tables;

use App\Enums\Permission;
use App\Enums\UserRole;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuthorsTable
{
    public static function configure(Table $table): Table
    {
        $canManageLogins = auth()->user()?->hasPermission(Permission::ManageUsers) ?? false;

        return $table
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('job_title'),
                TextColumn::make('user.email')->label('Admin login')->placeholder('No login')->visible($canManageLogins),
                TextColumn::make('user.role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => $state->color())
                    ->placeholder('—')
                    ->visible($canManageLogins),
                TextColumn::make('pages_count')->counts('pages')->label('Pages'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
