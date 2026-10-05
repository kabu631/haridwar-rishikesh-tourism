<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\PageCache;
use App\Support\SiteSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use UnitEnum;

/**
 * Business details (NAP), social profiles, analytics and verification codes.
 *
 * @property-read Schema $form
 */
class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    /**
     * Icons available for trust badges and homepage cards.
     */
    public const ICONS = [
        'calendar' => 'Calendar', 'shield' => 'Shield (approved)', 'award' => 'Award', 'tripadvisor' => 'Tripadvisor',
        'star' => 'Star', 'users' => 'People', 'map-pin' => 'Map pin', 'phone' => 'Phone', 'temple' => 'Temple',
        'mountain' => 'Mountain', 'route' => 'Route', 'diya' => 'Diya (lamp)', 'yoga' => 'Yoga', 'dinner' => 'Dinner',
        'village' => 'Village', 'balloon' => 'Hot-air balloon', 'lake' => 'Lake', 'map' => 'Map', 'raft' => 'Rafting',
        'bridge' => 'Bridge', 'compass' => 'Compass', 'camera' => 'Camera', 'car' => 'Car', 'bed' => 'Hotel bed',
        'sparkles' => 'Sparkles', 'check' => 'Check mark',
    ];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(SiteSettings $settings): void
    {
        $values = [];

        foreach (array_keys(SiteSettings::EDITABLE) as $key) {
            $values[$key] = $settings->get($key);
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Business & contact')
                            ->icon(Heroicon::OutlinedBuildingStorefront)
                            ->schema([self::businessSection(), self::enquiriesSection()]),
                        Tab::make('Website texts')
                            ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                            ->schema([self::textsSection(), self::trustSection()]),
                        Tab::make('Registrations')
                            ->icon(Heroicon::OutlinedCheckBadge)
                            ->schema([self::credentialsSection()]),
                        Tab::make('Social & reviews')
                            ->icon(Heroicon::OutlinedShare)
                            ->schema([self::socialSection()]),
                        Tab::make('Analytics')
                            ->icon(Heroicon::OutlinedChartBar)
                            ->schema([self::analyticsSection()]),
                    ]),
            ]);
    }

    private static function businessSection(): Section
    {
        return Section::make('Business details (NAP)')
            ->description('Keep name, address and phone identical to your Google Business Profile – consistency helps local rankings.')
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('name')->label('Website name')->required()->maxLength(120),
                    TextInput::make('legal_name')->label('Company name')->required()->maxLength(120),
                    TextInput::make('email')->email()->required(),
                    TextInput::make('phone')->label('Mobile (header)')->tel()->required(),
                    TextInput::make('landline')->tel(),
                    TextInput::make('whatsapp')->label('WhatsApp number')->helperText('Digits with country code, e.g. 919759222888'),
                ]),
                Textarea::make('tagline')->rows(2)->maxLength(300),
                Grid::make(3)->schema([
                    TextInput::make('address.street')->label('Street')->columnSpan(2),
                    TextInput::make('address.locality')->label('City'),
                    TextInput::make('address.region')->label('State'),
                    TextInput::make('address.postal_code')->label('PIN code'),
                    TextInput::make('address.country')->label('Country code')->maxLength(2),
                    TextInput::make('geo.latitude')->label('Latitude')->numeric(),
                    TextInput::make('geo.longitude')->label('Longitude')->numeric(),
                    TextInput::make('opening_hours')->label('Opening hours')->placeholder('Mo-Su 09:00-20:00'),
                ]),
            ]);
    }

    private static function enquiriesSection(): Section
    {
        return Section::make('Enquiries')
            ->schema([
                TextInput::make('enquiry_recipient')->label('Send new enquiries to')->email()->required(),
                Textarea::make('announcement')->label('Site-wide notice (optional)')->rows(2)->maxLength(300),
            ]);
    }

    private static function textsSection(): Section
    {
        return Section::make('Texts on every page')
            ->schema([
                TextInput::make('topbar_text')->label('Top bar text')->maxLength(140),
                Grid::make(2)->schema([
                    TextInput::make('cta.eyebrow')->label('Contact band – small heading')->maxLength(80),
                    TextInput::make('cta.heading')->label('Contact band – heading')->maxLength(200),
                ]),
                Textarea::make('footer_about')->label('Footer – about text')->rows(3)->maxLength(500),
            ]);
    }

    private static function trustSection(): Section
    {
        return Section::make('Trust badges')
            ->description('Shown under the homepage slider.')
            ->schema([
                Repeater::make('trust')
                    ->hiddenLabel()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('icon')->options(self::ICONS)->native(false)->required(),
                            TextInput::make('title')->required()->maxLength(60),
                            TextInput::make('text')->maxLength(80),
                        ]),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->reorderableWithButtons()
                    ->maxItems(8),
            ]);
    }

    private static function credentialsSection(): Section
    {
        return Section::make('Registrations & memberships')
            ->description('Shown in the footer, About section and booking page, and published in structured data (E-E-A-T).')
            ->schema([
                Repeater::make('credentials')
                    ->hiddenLabel()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')->required()->maxLength(120),
                            TextInput::make('value')->label('Number / value')->required()->maxLength(80),
                        ]),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                    ->reorderableWithButtons(),
            ]);
    }

    private static function socialSection(): Section
    {
        return Section::make('Social profiles & reviews')
            ->description('Used for footer links and the organisation’s “sameAs” structured data.')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('social.facebook')->label('Facebook')->url(),
                    TextInput::make('social.youtube')->label('YouTube')->url(),
                    TextInput::make('social.twitter')->label('X / Twitter')->url(),
                    TextInput::make('social.google_business')->label('Google Business Profile')->url(),
                    TextInput::make('social.tripadvisor')->label('Tripadvisor')->url(),
                    TextInput::make('social.tripadvisor_haridwar')->label('Tripadvisor (Haridwar listing)')->url(),
                    TextInput::make('social.blog')->label('Blog')->url(),
                ]),
                KeyValue::make('network')->label('Our websites (footer)')->keyLabel('Name')->valueLabel('URL'),
            ]);
    }

    private static function analyticsSection(): Section
    {
        return Section::make('Analytics & verification')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('google_ads_id')->label('Google Ads ID')->placeholder('AW-…'),
                    TextInput::make('ga4_id')->label('Google Analytics 4 ID')->placeholder('G-…'),
                    TextInput::make('google_verification')->label('Google Search Console code'),
                    TextInput::make('bing_verification')->label('Bing Webmaster code'),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save settings')->submit('save')->keyBindings(['mod+s']),
                    ])->sticky(),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (array_keys(SiteSettings::EDITABLE) as $key) {
            if (array_key_exists($key, $data)) {
                Setting::put($key, $data[$key]);
            }
        }

        Notification::make()->title('Settings saved')->body('The website has been updated.')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearCache')
                ->label('Clear website cache')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Pages are cached for speed and refreshed automatically when you edit content. Use this only if something looks out of date.')
                ->action(function (): void {
                    PageCache::flush();
                    Artisan::call('view:clear');
                    Notification::make()->title('Website cache cleared')->success()->send();
                }),
        ];
    }
}
