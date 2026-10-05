<?php

namespace App\Filament\Resources\Enquiries\Schemas;

use App\Models\Enquiry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EnquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Follow-up')
                    ->schema([
                        Select::make('status')->options(Enquiry::STATUSES)->required()->native(false),
                        Textarea::make('notes')->label('Internal notes')->rows(4)->maxLength(5000),
                    ]),
                Section::make('Enquiry')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('name')->required()->maxLength(120),
                            TextInput::make('email')->email()->required()->maxLength(190),
                            TextInput::make('phone')->tel()->required()->maxLength(30),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('tour')->maxLength(190),
                            DatePicker::make('travel_date')->native(false),
                            DatePicker::make('return_date')->native(false),
                            TextInput::make('adults')->numeric(),
                            TextInput::make('children')->numeric(),
                            Select::make('type')->options(Enquiry::TYPES)->native(false),
                        ]),
                        Textarea::make('message')->rows(4)->maxLength(3000),
                    ])
                    ->collapsible(),
            ]);
    }
}
