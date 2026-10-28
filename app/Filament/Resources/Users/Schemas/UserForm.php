<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Filament\Resources\Authors\Schemas\AuthorForm;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Account')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')->required()->maxLength(120),
                            self::emailInput(),
                        ]),
                        self::passwordInput()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Leave blank to keep the current password.' : 'At least 8 characters. Share it with the user privately.'),
                    ]),
                Section::make('Role')
                    ->description('What this person can do in the admin panel.')
                    ->schema([self::roleInput()]),
                Section::make('Author profile')
                    ->description('The public byline and bio shown on pages this person writes. New pages they add are credited to this author.')
                    ->schema([
                        Select::make('author_id')
                            ->hiddenLabel()
                            ->relationship(
                                'author',
                                'name',
                                // Each author profile belongs to one login at most.
                                fn (Builder $query, ?User $record): Builder => $query->whereDoesntHave('user', fn (Builder $users): Builder => $users->when($record, fn (Builder $users): Builder => $users->whereKeyNot($record->id))),
                            )
                            ->createOptionForm(AuthorForm::profileComponents())
                            ->unique(ignoreRecord: true)
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Not an author')
                            ->helperText('Pick an existing team member or use + to create a new author profile.'),
                    ]),
            ]);
    }

    public static function emailInput(): TextInput
    {
        return TextInput::make('email')->label('Login email')->email()->required()->maxLength(191)->unique(ignoreRecord: true);
    }

    /**
     * Password field with a button that fills in a random password to share with the person.
     */
    public static function passwordInput(): TextInput
    {
        return TextInput::make('password')
            ->password()
            ->revealable()
            ->rule(Password::default())
            ->dehydrated(fn (?string $state): bool => filled($state))
            ->suffixAction(
                Action::make('generatePassword')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->tooltip('Generate a password')
                    ->action(fn (Set $set): mixed => $set('password', Str::password(12, symbols: false))),
            );
    }

    public static function roleInput(): Radio
    {
        return Radio::make('role')
            ->hiddenLabel()
            ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])->all())
            ->descriptions(collect(UserRole::cases())->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->description()])->all())
            ->default(UserRole::Staff->value)
            ->required()
            // An admin cannot change their own role, so the panel always keeps an admin.
            ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
            ->helperText(fn (?User $record): ?string => $record?->is(auth()->user()) ? 'You cannot change your own role.' : null);
    }
}
