<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestEnquiries extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest enquiries')
            ->query(Enquiry::query()->where('status', '!=', 'spam')->latest()->limit(8))
            ->paginated(false)
            ->columns([
                TextColumn::make('created_at')->label('Received')->since(),
                TextColumn::make('name')->weight('medium'),
                TextColumn::make('phone'),
                TextColumn::make('tour')->limit(50)->placeholder('—'),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => Enquiry::STATUSES[$state] ?? $state)->color(fn (string $state): string => $state === 'new' ? 'primary' : 'gray'),
            ])
            ->recordActions([
                Action::make('open')->url(fn (Enquiry $record): string => EnquiryResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
