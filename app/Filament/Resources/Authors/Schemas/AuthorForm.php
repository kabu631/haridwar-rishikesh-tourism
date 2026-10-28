<?php

namespace App\Filament\Resources\Authors\Schemas;

use App\Enums\Permission;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Models\Author;
use App\Models\User;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AuthorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                ...self::profileComponents(),
                self::loginSection(),
            ]);
    }

    /**
     * @return array<Component>
     */
    public static function profileComponents(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(120)->helperText('Anchor on the Our Team page.'),
                TextInput::make('job_title')->maxLength(160),
            ]),
            Textarea::make('bio')
                ->rows(4)
                ->maxLength(1500)
                ->helperText('Shown under every article by this author. Mention real, verifiable experience (years guiding, licences, regions covered).'),
            TagsInput::make('same_as')
                ->label('Profile links')
                ->placeholder('https://…')
                ->helperText('LinkedIn, X/Twitter, Instagram… Used in Person structured data.'),
        ];
    }

    /**
     * The team member's own admin panel login; only admins see and change it.
     */
    private static function loginSection(): Section
    {
        return Section::make('Admin login')
            ->description('Lets this team member sign in to the admin panel. Pages they add are credited to them.')
            ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::ManageUsers) ?? false)
            ->schema([
                Toggle::make('has_login')
                    ->label('Can sign in to the admin panel')
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(fn (Toggle $component, ?Author $record) => $component->state($record?->user !== null))
                    // Nobody can switch off their own login.
                    ->disabled(fn (?Author $record): bool => $record?->user?->is(auth()->user()) ?? false),
                Group::make()
                    // Switching the login off deletes the user account. The group itself stays
                    // visible (hidden groups are not saved); only its fields are hidden.
                    ->relationship('user', condition: fn (Get $get): bool => (bool) $get('has_login'))
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data, Get $get): array => [...$data, 'name' => $get('name')])
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data, Get $get): array => [...$data, 'name' => $get('name')])
                    ->schema([
                        Grid::make(2)
                            ->visible(fn (Get $get): bool => (bool) $get('../has_login'))
                            ->schema([
                                UserForm::emailInput(),
                                UserForm::passwordInput()
                                    ->required(fn (?User $record): bool => $record === null)
                                    ->helperText(fn (?User $record): string => $record ? 'Leave blank to keep the current password.' : 'At least 8 characters. Use the ✦ button to generate one, then share it privately.'),
                                UserForm::roleInput()->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
