<?php

namespace App\Filament\Resources\Authors\Pages;

use App\Filament\Resources\Authors\AuthorResource;
use App\Models\Author;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAuthor extends EditRecord
{
    protected static string $resource = AuthorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription(fn (Author $record): string => $record->user
                    ? "This also removes {$record->name}’s admin login ({$record->user->email}). Their pages stay published without an author."
                    : 'Their pages stay published without an author.'),
        ];
    }
}
