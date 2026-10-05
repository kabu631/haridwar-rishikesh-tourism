<?php

namespace App\Filament\Resources\Enquiries\Schemas;

use App\Models\Enquiry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EnquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Contact')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('name')->weight('bold'),
                            TextEntry::make('phone')->url(fn (Enquiry $record): string => 'tel:'.preg_replace('/[^0-9+]/', '', $record->phone))->copyable(),
                            TextEntry::make('email')->url(fn (Enquiry $record): string => 'mailto:'.$record->email)->copyable(),
                            TextEntry::make('status')->badge()->formatStateUsing(fn (string $state): string => Enquiry::STATUSES[$state] ?? $state)->color(fn (string $state): string => match ($state) {
                                'new' => 'primary',
                                'booked' => 'success',
                                'spam', 'closed' => 'gray',
                                default => 'warning',
                            }),
                        ]),
                    ]),
                Section::make('Trip')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('tour')
                                ->placeholder('—')
                                ->columnSpan(2)
                                ->url(fn (Enquiry $record): ?string => $record->page?->absoluteUrl())
                                ->openUrlInNewTab()
                                ->helperText(fn (Enquiry $record): ?string => $record->page
                                    ? collect($record->page->tripFacts())->map(fn (string $value, string $label): string => "{$label}: {$value}")->implode(' · ') ?: null
                                    : null),
                            TextEntry::make('travel_date')->date('j M Y')->placeholder('—'),
                            TextEntry::make('return_date')->date('j M Y')->placeholder('—'),
                            TextEntry::make('adults')->placeholder('—'),
                            TextEntry::make('children')->placeholder('—'),
                            TextEntry::make('type')->formatStateUsing(fn (string $state): string => Enquiry::TYPES[$state] ?? $state),
                            TextEntry::make('created_at')->label('Received')->dateTime('j M Y, H:i'),
                        ]),
                        TextEntry::make('message')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('notes')->label('Internal notes')->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make('Source')
                    ->schema([
                        TextEntry::make('source_url')->label('Submitted from')->url(fn (Enquiry $record): ?string => $record->source_url)->openUrlInNewTab()->placeholder('—'),
                        TextEntry::make('ip_address')->label('IP address')->placeholder('—'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
