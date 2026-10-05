<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Enums\PageType;
use App\Models\Page;
use App\Support\Legacy\PageClassifier;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Page')
                    ->searchable(['title', 'path', 'meta_title'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap()
                    ->description(fn (Page $record): string => $record->url()),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (?PageType $state): string => $state?->label() ?? '—')
                    ->color(fn (?PageType $state): string => match ($state) {
                        PageType::Package => 'success',
                        PageType::Hub, PageType::Home => 'warning',
                        PageType::Attraction, PageType::Destination => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('section')
                    ->formatStateUsing(fn (?string $state): string => PageClassifier::SECTION_LABELS[$state] ?? '—')
                    ->toggleable(),
                IconColumn::make('seo_ok')
                    ->label('SEO')
                    ->state(fn (Page $record): bool => self::seoIssues($record) === [])
                    ->boolean()
                    ->tooltip(fn (Page $record): string => implode(' · ', self::seoIssues($record)) ?: 'Title and description lengths look good'),
                ToggleColumn::make('is_published')->label('Live'),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->date('j M Y')
                    ->sortable(),
                TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->date('j M Y')
                    ->placeholder('Never')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(PageType::options()),
                SelectFilter::make('section')->options(PageClassifier::SECTION_LABELS),
                TernaryFilter::make('is_published')->label('Published'),
                Filter::make('seo_issues')
                    ->label('Title/description length issues')
                    ->query(fn (Builder $query) => $query->where(fn (Builder $query) => $query
                        ->whereRaw('CHAR_LENGTH(COALESCE(meta_title, title)) NOT BETWEEN 30 AND 65')
                        ->orWhereNull('meta_description')
                        ->orWhereRaw('CHAR_LENGTH(meta_description) NOT BETWEEN 70 AND 170'))),
                Filter::make('stale')
                    ->label('Not reviewed in 12 months')
                    ->query(fn (Builder $query) => $query->where(fn (Builder $query) => $query
                        ->whereNull('reviewed_at')->orWhere('reviewed_at', '<', now()->subYear()))),
            ])
            ->recordActions([
                Action::make('view')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->iconButton()
                    ->tooltip('View on website')
                    ->url(fn (Page $record): string => url($record->url()))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->requiresConfirmation()
                        ->modalDescription('Unpublished pages return 404 to visitors and search engines.')
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => false])),
                    BulkAction::make('publish')
                        ->label('Publish')
                        ->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true])),
                ]),
            ]);
    }

    /**
     * @return list<string>
     */
    public static function seoIssues(Page $record): array
    {
        $title = mb_strlen($record->seoTitle());
        $description = mb_strlen((string) $record->meta_description);

        return array_values(array_filter([
            $title < 30 ? "Title short ({$title})" : null,
            $title > 65 ? "Title long ({$title})" : null,
            $description === 0 ? 'No meta description' : null,
            $description > 0 && $description < 70 ? "Description short ({$description})" : null,
            $description > 170 ? "Description long ({$description})" : null,
            str_contains(strtolower($record->robots), 'noindex') ? 'Noindex' : null,
            Str::wordCount(strip_tags((string) $record->body)) < 150 && ! $record->isHome() && $record->type !== PageType::Gallery && $record->type !== PageType::Hub ? 'Thin content' : null,
        ]));
    }
}
