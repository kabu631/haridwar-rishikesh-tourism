<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(3)->schema([
                    TextInput::make('name')->required()->maxLength(120),
                    TextInput::make('location')->maxLength(120),
                    Select::make('rating')->options([5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 2 => '★★', 1 => '★'])->native(false),
                ]),
                TextInput::make('title')->label('Review title')->maxLength(255),
                Textarea::make('body')->label('Review')->required()->rows(5)->maxLength(3000)->helperText('Publish genuine guest feedback only, in the guest’s own words.'),
                TextInput::make('url')->label('Review link')->url()->maxLength(255)->placeholder('https://www.tripadvisor.in/ShowUserReviews-…'),
                Grid::make(3)->schema([
                    TextInput::make('source')->maxLength(120)->placeholder('Tripadvisor, Google, email…')->helperText('Only 4–5 star Tripadvisor reviews show on the homepage.'),
                    TextInput::make('sort_order')->numeric()->default(0),
                    Toggle::make('is_published')->label('Show on website')->default(true)->inline(false),
                ]),
            ]);
    }
}
