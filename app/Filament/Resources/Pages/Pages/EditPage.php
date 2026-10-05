<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\Concerns\HandlesPageFormData;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Author;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPage extends EditRecord
{
    use HandlesPageFormData;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('View page')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (Page $record): string => url($record->url()))
                ->openUrlInNewTab(),
            Action::make('markReviewed')
                ->label('Mark as reviewed')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->modalDescription('Records that the facts on this page (timings, distances, prices) were checked today. Shown to visitors and in structured data.')
                ->schema([
                    Select::make('reviewer_id')
                        ->label('Reviewed by')
                        ->options(Author::query()->pluck('name', 'id'))
                        ->default(fn (Page $record) => $record->reviewer_id ?? $record->author_id)
                        ->required(),
                ])
                ->action(function (Page $record, array $data): void {
                    $record->update(['reviewer_id' => $data['reviewer_id'], 'reviewed_at' => now()]);
                    $this->refreshFormData(['reviewer_id', 'reviewed_at', 'updated_at']);
                    Notification::make()->title('Marked as reviewed')->success()->send();
                }),
            DeleteAction::make()
                ->hidden(fn (Page $record): bool => $record->isHome())
                ->modalDescription('Deleting creates a 301 redirect from this URL to its parent page so visitors and rankings are not lost. Consider unpublishing or redirecting instead.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->preparePageData($data, $this->record instanceof Page ? $this->record : null);
    }

    protected function afterSave(): void
    {
        // Re-open with the editor matching the (possibly changed) editor mode.
        if ($this->record->wasChanged('extra')) {
            $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record, 'tab' => request()->query('tab')]));
        }
    }
}
