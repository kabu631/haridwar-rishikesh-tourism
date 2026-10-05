<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageType;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Models\Page;
use App\Support\Legacy\PageClassifier;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Page')
                    ->persistTabInQueryString()
                    ->tabs([
                        self::contentTab(),
                        self::seoTab(),
                        self::tourTab(),
                        self::faqTab(),
                        self::listingsTab(),
                        self::eeatTab(),
                        self::homepageTab(),
                    ]),
            ]);
    }

    /**
     * Type chosen with "Add tour package", "Add company page"… (?type= in the URL).
     */
    private static function requestedType(mixed $livewire): ?PageType
    {
        return PageType::tryFrom((string) ($livewire instanceof CreatePage ? $livewire->type : request()->query('type')));
    }

    private static function contentTab(): Tab
    {
        return Tab::make('Content')
            ->icon('heroicon-o-document-text')
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('title')
                        ->label('Heading (H1)')
                        ->helperText('The single visible H1 of the page. Put the main keyword near the start.')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->columnSpan(2),
                    TextInput::make('nav_label')
                        ->label('Short label')
                        ->helperText('Used in breadcrumbs and menus (optional).')
                        ->maxLength(120),
                ]),
                Grid::make(3)->schema([
                    TextInput::make('path')
                        ->label('URL')
                        ->prefix(fn (): string => rtrim(config('seo.site_url'), '/').'/')
                        ->helperText(fn (?Page $record): string => $record?->exists
                            ? 'Changing an existing URL automatically creates a 301 redirect from the old address, so rankings and backlinks are kept.'
                            : 'Short, lowercase, words separated by hyphens, ending in .html – e.g. kedarnath-yatra-package.html')
                        ->required(fn (?Page $record): bool => ! $record?->isHome())
                        ->disabled(fn (?Page $record): bool => (bool) $record?->isHome())
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*\.(html|php)$/')
                        ->validationMessages(['regex' => 'Use lowercase letters, numbers and hyphens, ending in .html'])
                        ->unique(ignoreRecord: true)
                        ->maxLength(191)
                        ->columnSpan(2),
                    Select::make('type')
                        ->options(PageType::options())
                        ->required()
                        ->default(fn ($livewire): string => self::requestedType($livewire)?->value ?? PageType::Guide->value)
                        ->live()
                        ->native(false),
                ]),
                Grid::make(3)->schema([
                    Select::make('section')
                        ->options(PageClassifier::SECTION_LABELS)
                        ->default(fn ($livewire): ?string => match (self::requestedType($livewire)) {
                            PageType::Company => 'company',
                            PageType::Blog => 'blog',
                            default => null,
                        })
                        ->native(false)
                        ->helperText('Groups related pages (sidebar, related links).'),
                    Select::make('parent_id')
                        ->label('Parent page (breadcrumb)')
                        ->relationship('parent', 'title', fn ($query, ?Page $record) => $query->when($record, fn ($q) => $q->whereKeyNot($record->id))->orderBy('title'))
                        ->searchable()
                        ->preload()
                        ->default(fn ($livewire): ?int => self::requestedType($livewire) === PageType::Blog
                            ? Page::query()->where('path', 'blog.html')->value('id')
                            : null)
                        ->helperText('Keeps every page within 3 clicks of the homepage.'),
                    Toggle::make('is_published')
                        ->label('Published')
                        ->default(true)
                        ->helperText('Unpublishing returns 404 – prefer a redirect when retiring a ranking page.')
                        ->inline(false),
                ]),
                Textarea::make('excerpt')
                    ->label('Summary')
                    ->rows(3)
                    ->maxLength(600)
                    ->helperText('Shown on cards and search results; also read by AI assistants. 1–2 plain sentences that answer “what is this page about?”.'),

                Section::make('Body')
                    ->description('Main article content. Keep the primary keyword in the first 100 words and use H2/H3 headings in order.')
                    ->schema([
                        RichEditor::make('body')
                            ->hiddenLabel()
                            ->visible(fn (?Page $record): bool => self::editorMode($record) === 'visual')
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'link'],
                                ['h2', 'h3', 'h4'],
                                ['bulletList', 'orderedList', 'blockquote', 'horizontalRule'],
                                ['table', 'attachFiles'],
                                ['undo', 'redo'],
                            ])
                            ->fileAttachmentsDisk('site')
                            ->fileAttachmentsDirectory('uploads/content'),
                        CodeEditor::make('body_source')
                            ->hiddenLabel()
                            ->language(Language::Html)
                            ->visible(fn (?Page $record): bool => self::editorMode($record) === 'html'),
                        ToggleButtons::make('extra.editor')
                            ->label('Editor')
                            ->options(['visual' => 'Visual editor', 'html' => 'HTML source'])
                            ->default('visual')
                            ->inline()
                            ->helperText('Use HTML source for pages with maps or embedded videos. Save the page to switch editors.'),
                    ]),

                Section::make('Header image')
                    ->schema([
                        Grid::make(2)->schema([
                            FileUpload::make('hero_image')
                                ->label('Image')
                                ->image()
                                ->disk('site')
                                ->directory('uploads/pages')
                                ->visibility('public')
                                ->maxSize(4096)
                                ->imageEditor()
                                ->helperText('Wide photo (at least 1366 × 485). WebP/AVIF versions are generated automatically. Use a descriptive file name, e.g. har-ki-pauri-ganga-aarti.jpg'),
                            TextInput::make('hero_alt')
                                ->label('Alt text')
                                ->maxLength(255)
                                ->helperText('Describe the photo for visually impaired visitors and Google Images.'),
                        ]),
                    ])
                    ->collapsible(),
            ]);
    }

    private static function seoTab(): Tab
    {
        return Tab::make('SEO')
            ->icon('heroicon-o-magnifying-glass')
            ->schema([
                Html::make(fn (Get $get): HtmlString => self::serpPreview($get)),
                TextInput::make('meta_title')
                    ->label('Title tag')
                    ->maxLength(255)
                    ->live(debounce: 400)
                    ->helperText(fn (?string $state): string => self::lengthHint($state, 50, 60, 'characters – aim for 50–60. Existing titles that rank well are best left unchanged.')),
                Textarea::make('meta_description')
                    ->label('Meta description')
                    ->rows(3)
                    ->maxLength(500)
                    ->live(debounce: 400)
                    ->helperText(fn (?string $state): string => self::lengthHint($state, 150, 160, 'characters – aim for 150–160 and include a call to action.')),
                Grid::make(3)->schema([
                    Select::make('robots')
                        ->options([
                            'index,follow' => 'Index, follow (normal)',
                            'noindex,follow' => 'Noindex, follow',
                            'noindex,nofollow' => 'Noindex, nofollow',
                        ])
                        ->default('index,follow')
                        ->native(false)
                        ->helperText('Noindex removes the page from Google.'),
                    TextInput::make('canonical_url')
                        ->label('Canonical URL override')
                        ->url()
                        ->maxLength(255)
                        ->helperText('Leave empty – pages point to themselves automatically.')
                        ->columnSpan(2),
                ]),
                Grid::make(2)->schema([
                    Select::make('schema_type')
                        ->label('Schema type override')
                        ->options([
                            'HinduTemple' => 'Hindu temple',
                            'AboutPage' => 'About page',
                            'ContactPage' => 'Contact page',
                        ])
                        ->native(false)
                        ->helperText('Normally derived from the page type.'),
                    Textarea::make('meta_keywords')
                        ->label('Legacy meta keywords')
                        ->rows(2)
                        ->helperText('Kept from the old site for reference; not used by Google.'),
                ]),
            ]);
    }

    private static function tourTab(): Tab
    {
        return Tab::make('Tour details')
            ->icon('heroicon-o-map')
            ->visible(fn (Get $get): bool => in_array($get('type'), [PageType::Package->value, PageType::Package], true))
            ->schema([
                Grid::make(4)->schema([
                    TextInput::make('facts.duration_nights')->label('Nights')->numeric()->minValue(0)->maxValue(60),
                    TextInput::make('facts.duration_days')->label('Days')->numeric()->minValue(1)->maxValue(60),
                    TextInput::make('facts.start_city')->label('Starts from')->maxLength(80),
                    Toggle::make('is_featured')->label('Feature on homepage')->inline(false),
                ]),
                TagsInput::make('facts.destinations')->label('Places covered')->placeholder('Add a place'),
                Repeater::make('itinerary')
                    ->label('Day-wise itinerary (for TouristTrip structured data and the “Trip at a glance” box)')
                    ->schema([
                        Grid::make(6)->schema([
                            TextInput::make('day')->numeric()->required()->minValue(1)->columnSpan(1),
                            TextInput::make('title')->required()->maxLength(160)->columnSpan(5),
                        ]),
                        Textarea::make('description')->rows(3)->maxLength(1000),
                    ])
                    ->itemLabel(fn (array $state): string => 'Day '.($state['day'] ?? '?').': '.($state['title'] ?? ''))
                    ->collapsed()
                    ->reorderableWithButtons()
                    ->defaultItems(0),
            ]);
    }

    private static function faqTab(): Tab
    {
        return Tab::make('FAQs')
            ->icon('heroicon-o-question-mark-circle')
            ->badge(fn (Get $get): ?int => count($get('faqs') ?? []) ?: null)
            ->schema([
                Html::make(new HtmlString('<p class="text-sm text-gray-500">Questions and answers published as FAQPage structured data. Answer directly in the first sentence – AI assistants and Google quote these.</p>')),
                Repeater::make('faqs')
                    ->hiddenLabel()
                    ->schema([
                        TextInput::make('question')->required()->maxLength(255),
                        Textarea::make('answer')->required()->rows(3)->maxLength(2000),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                    ->collapsed()
                    ->reorderableWithButtons()
                    ->defaultItems(0),
            ]);
    }

    private static function listingsTab(): Tab
    {
        return Tab::make('Cards & gallery')
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                Repeater::make('cards')
                    ->label('Cards (hub pages)')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('title')->required()->maxLength(160),
                            TextInput::make('url')->required()->maxLength(255)->helperText('e.g. /har-ki-pauri.html'),
                        ]),
                        Grid::make(2)->schema([
                            self::imageUpload('image', 'cards'),
                            TextInput::make('alt')->label('Image alt text')->maxLength(255),
                        ]),
                        Textarea::make('text')->rows(2)->maxLength(500),
                        TextInput::make('badge')->maxLength(30)->helperText('Optional, e.g. 2N/3D'),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->collapsed()
                    ->reorderableWithButtons()
                    ->defaultItems(0),
                Repeater::make('gallery')
                    ->label('Photos: slider & gallery')
                    ->helperText('Photos switched to “Show in slider” play as a slideshow at the top of the page; the others appear in the photo gallery.')
                    ->schema([
                        Grid::make(2)->schema([
                            self::imageUpload('image', 'gallery')->label('Photo'),
                            TextInput::make('alt')->label('Alt text (describe the photo)')->maxLength(255),
                            TextInput::make('title')->label('Caption')->maxLength(255),
                            Toggle::make('slide')->label('Show in slider at the top of the page')->inline(false),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('full')->label('Full-size image path (optional)')->maxLength(255),
                            TextInput::make('youtube')->label('YouTube video ID (optional)')->maxLength(20),
                            TextInput::make('url')->label('Links to page (optional)')->maxLength(255),
                        ]),
                        Textarea::make('caption')->label('Description (optional)')->rows(2)->maxLength(500),
                    ])
                    ->itemLabel(fn (array $state): ?string => ($state['slide'] ?? false ? '▶ Slider: ' : '').($state['title'] ?? $state['alt'] ?? basename((string) ($state['image'] ?? 'Photo'))))
                    ->collapsed()
                    ->reorderableWithButtons()
                    ->defaultItems(0),
            ]);
    }

    private static function eeatTab(): Tab
    {
        return Tab::make('Author & review')
            ->icon('heroicon-o-check-badge')
            ->schema([
                Html::make(new HtmlString('<p class="text-sm text-gray-500">Experience, expertise, authoritativeness and trust (E-E-A-T): show who wrote the page, when it was last checked, and which sources back up facts such as timings, distances and fees.</p>')),
                Grid::make(3)->schema([
                    Select::make('author_id')->relationship('author', 'name')->preload()->native(false),
                    Select::make('reviewer_id')->label('Reviewed by')->relationship('reviewer', 'name')->preload()->native(false),
                    DateTimePicker::make('reviewed_at')->label('Last reviewed')->native(false)->maxDate(now()),
                ]),
                DateTimePicker::make('published_at')->label('First published')->native(false),
                Repeater::make('sources')
                    ->label('Sources / citations')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('title')->required()->maxLength(200),
                            TextInput::make('url')->url()->maxLength(255),
                        ]),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->defaultItems(0),
            ]);
    }

    private static function homepageTab(): Tab
    {
        $icon = fn (): Select => Select::make('icon')->options(Settings::ICONS)->native(false);

        /**
         * One editable list of homepage cards; every stored field has an input
         * so nothing is lost on save.
         *
         * @param  list<string>  $extras  'icon', 'image', 'label', 'html'
         */
        $list = fn (string $key, string $label, array $extras = []): Section => Section::make($label)
            ->schema([
                TextInput::make("extra.home.{$key}.heading")->label('Section heading')->maxLength(160),
                Repeater::make("extra.home.{$key}.items")
                    ->hiddenLabel()
                    ->schema(array_values(array_filter([
                        Grid::make(2)->schema([
                            TextInput::make('title')->required()->maxLength(160),
                            TextInput::make('url')->label('Link')->required()->maxLength(255)->helperText('/page.html or https://…'),
                        ]),
                        in_array('label', $extras, true) ? Grid::make(2)->schema([
                            TextInput::make('label')->label('Label on photo')->maxLength(120),
                            TextInput::make('subtitle')->label('Sub-label on photo')->maxLength(160),
                        ]) : null,
                        in_array('image', $extras, true) ? Grid::make(2)->schema([
                            self::imageUpload('image', 'home'),
                            TextInput::make('alt')->label('Image alt text')->maxLength(255),
                        ]) : null,
                        in_array('icon', $extras, true) ? $icon() : null,
                        Textarea::make('text')->rows(2)->maxLength(600),
                        in_array('html', $extras, true) ? Textarea::make('html')->label('Text with links (HTML, optional – overrides the text above)')->rows(2) : null,
                    ])))
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->collapsed()
                    ->reorderableWithButtons(),
            ])
            ->collapsible()
            ->collapsed();

        return Tab::make('Homepage')
            ->icon('heroicon-o-home')
            ->visible(fn (?Page $record): bool => (bool) $record?->isHome())
            ->schema([
                Section::make('Slider')
                    ->description('Slides of the large photo slider at the top of the homepage. Photos at least 1366 × 640 px.')
                    ->schema([
                        Repeater::make('extra.home.hero.slides')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(2)->schema([
                                    self::imageUpload('image', 'slider')->label('Slide photo')->required(),
                                    Grid::make(1)->schema([
                                        TextInput::make('image_alt')->label('Photo alt text')->maxLength(200),
                                        TextInput::make('eyebrow')->label('Small text above the title')->maxLength(160),
                                        TextInput::make('title')->label('Slide title')->required()->maxLength(160),
                                    ]),
                                ]),
                                Textarea::make('text')->label('Slide text')->rows(2)->maxLength(400),
                                Grid::make(2)->schema([
                                    TextInput::make('button_label')->label('Button text')->maxLength(60),
                                    TextInput::make('button_url')->label('Button link')->maxLength(255),
                                ]),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->collapsed()
                            ->reorderableWithButtons()
                            ->minItems(1),
                        TagsInput::make('extra.home.hero.highlights')->label('Highlight chips under the search box'),
                    ])
                    ->collapsible(),
                Section::make('Offers')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('extra.home.offer.url')->label('Offer badge link')->url()->maxLength(255),
                            self::imageUpload('extra.home.offer.image', 'home')->label('Offer badge image'),
                            TextInput::make('extra.home.offer.alt')->label('Offer badge text')->maxLength(160),
                        ]),
                        Repeater::make('extra.home.promos')
                            ->label('Promotion banners')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('url')->label('Link')->required()->url()->maxLength(255),
                                    TextInput::make('image')->label('Banner image URL or path')->required()->maxLength(255),
                                    TextInput::make('alt')->label('Banner text')->maxLength(160),
                                    Grid::make(2)->schema([
                                        TextInput::make('width')->numeric()->default(1200),
                                        TextInput::make('height')->numeric()->default(150),
                                    ]),
                                ]),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['alt'] ?? null)
                            ->collapsed(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                $list('latest', 'Latest updates', ['icon']),
                $list('other', 'Other packages and guide', ['icon']),
                $list('haridwar', 'Haridwar Tourism cards', ['label', 'image']),
                $list('rishikesh', 'Rishikesh Tourism cards', ['label', 'image']),
                Section::make('Guest reviews block')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('extra.home.reviews.heading')->label('Heading')->maxLength(120),
                            TextInput::make('extra.home.reviews.subheading')->label('Small heading')->maxLength(120),
                            TextInput::make('extra.home.reviews.url')->label('Review link (Tripadvisor)')->url()->maxLength(255),
                            TextInput::make('extra.home.reviews.cta')->label('Button text')->maxLength(60),
                        ]),
                        Html::make(new HtmlString('<p class="text-sm text-gray-500">The reviews themselves are managed under Content → Testimonials.</p>')),
                    ])
                    ->collapsible()
                    ->collapsed(),
                $list('packages', 'Tour packages', ['image']),
                $list('guide', 'Guide', ['icon', 'html']),
                Section::make('Notice')
                    ->schema([
                        Textarea::make('extra.home.note')->hiddenLabel()->rows(3)->helperText('HTML allowed (links).'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Section::make('About India Easy Trip')
                    ->schema([
                        TextInput::make('extra.home.about.heading')->label('Heading'),
                        Repeater::make('extra.home.about.paragraphs')
                            ->label('Paragraphs (HTML allowed)')
                            ->simple(Textarea::make('paragraph')->rows(4)),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Image picker that uploads into public/uploads/{folder} and also shows
     * existing images by their public path (e.g. /haridwar-images/haridwar.jpg).
     * WebP/AVIF versions are created automatically on save.
     */
    public static function imageUpload(string $name, string $folder): FileUpload
    {
        return FileUpload::make($name)
            ->label('Image')
            ->image()
            ->disk('site')
            ->directory('uploads/'.$folder)
            ->visibility('public')
            ->maxSize(5120)
            ->imageEditor()
            ->fetchFileInformation(false);
    }

    public static function editorMode(?Page $record): string
    {
        $mode = $record?->extra['editor'] ?? null;

        if ($mode !== null) {
            return $mode;
        }

        return str_contains((string) $record?->body, '<iframe') ? 'html' : 'visual';
    }

    private static function lengthHint(?string $value, int $min, int $max, string $suffix): string
    {
        $length = mb_strlen((string) $value);
        $status = match (true) {
            $length === 0 => '⚪',
            $length < $min - 10 || $length > $max + 10 => '🔴',
            $length < $min || $length > $max => '🟠',
            default => '🟢',
        };

        return "{$status} {$length} {$suffix}";
    }

    private static function serpPreview(Get $get): HtmlString
    {
        $title = $get('meta_title') ?: $get('title') ?: 'Page title';
        $description = $get('meta_description') ?: 'Add a meta description to control the snippet Google shows under your title.';
        $path = $get('path') ?? '';
        $host = parse_url(config('seo.site_url'), PHP_URL_HOST) ?: 'www.haridwarrishikeshtourism.com';
        $crumb = $host.($path !== '' ? ' › '.Str::beforeLast($path, '.') : '');

        return new HtmlString(
            '<div style="max-width:600px;border:1px solid rgb(0 0 0 / .08);border-radius:12px;padding:16px 18px;background:#fff;font-family:arial,sans-serif">'
            .'<div style="font-size:12px;color:#5f6368;margin-bottom:4px">Google preview</div>'
            .'<div style="font-size:14px;color:#202124">'.e($crumb).'</div>'
            .'<div style="font-size:20px;line-height:1.3;color:#1a0dab;margin:4px 0">'.e(Str::limit($title, 62)).'</div>'
            .'<div style="font-size:14px;line-height:1.58;color:#4d5156">'.e(Str::limit($description, 162)).'</div>'
            .'</div>'
        );
    }
}
