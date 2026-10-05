<?php

namespace App\Filament\Resources\Enquiries\Tables;

use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Received')->since()->sortable()->tooltip(fn (Enquiry $record): string => $record->created_at->format('j M Y, H:i')),
                TextColumn::make('name')->searchable()->weight('medium')->description(fn (Enquiry $record): string => $record->email),
                TextColumn::make('phone')->searchable()->url(fn (Enquiry $record): string => 'tel:'.preg_replace('/[^0-9+]/', '', $record->phone)),
                TextColumn::make('tour')->searchable()->limit(40)->placeholder('—')->wrap(),
                TextColumn::make('travel_date')->label('Travel')->date('j M Y')->placeholder('—')->sortable(),
                TextColumn::make('type')->badge()->color('gray')->formatStateUsing(fn (string $state): string => Enquiry::TYPES[$state] ?? $state),
                SelectColumn::make('status')->options(Enquiry::STATUSES)->selectablePlaceholder(false),
            ])
            ->filters([
                SelectFilter::make('status')->options(Enquiry::STATUSES),
                SelectFilter::make('type')->options(Enquiry::TYPES),
                Filter::make('hide_spam')->label('Hide spam')->default()->query(fn (Builder $query) => $query->where('status', '!=', 'spam')),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn (): StreamedResponse => self::exportCsv()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('whatsapp')
                    ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->iconButton()
                    ->color('success')
                    ->tooltip('Reply on WhatsApp')
                    ->url(fn (Enquiry $record): string => 'https://wa.me/'.preg_replace('/\D/', '', str_starts_with(trim($record->phone), '+') || strlen(preg_replace('/\D/', '', $record->phone)) > 10 ? $record->phone : '91'.$record->phone).'?text='.rawurlencode('Namaste '.$record->name.', thank you for your enquiry'.($record->tour ? ' about '.$record->tour : '').' – India Easy Trip, Haridwar.'))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markContacted')
                        ->label('Mark as contacted')
                        ->icon(Heroicon::OutlinedPhone)
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'contacted'])),
                    BulkAction::make('markSpam')
                        ->label('Mark as spam')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('gray')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'spam'])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function exportCsv(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Received', 'Name', 'Email', 'Phone', 'Tour', 'Travel date', 'Return date', 'Adults', 'Children', 'Type', 'Status', 'Message', 'Source']);

            Enquiry::query()->orderByDesc('created_at')->chunk(500, function ($enquiries) use ($out): void {
                foreach ($enquiries as $enquiry) {
                    fputcsv($out, [
                        $enquiry->created_at?->toDateTimeString(), $enquiry->name, $enquiry->email, $enquiry->phone, $enquiry->tour,
                        $enquiry->travel_date?->toDateString(), $enquiry->return_date?->toDateString(), $enquiry->adults, $enquiry->children,
                        $enquiry->type, $enquiry->status, $enquiry->message, $enquiry->source_url,
                    ]);
                }
            });

            fclose($out);
        }, 'enquiries-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
