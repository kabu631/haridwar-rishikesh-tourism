<?php

namespace App\Filament\Resources\Pages;

use App\Enums\PageCategory;
use App\Enums\PageType;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Filament\Resources\Pages\Tables\PagesTable;
use App\Models\Page;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

use function Filament\Support\original_request;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Website pages';

    protected static ?string $navigationLabel = 'All pages';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    /**
     * Sidebar: the homepage, one entry per page category, then all pages.
     *
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        $group = static::getNavigationGroup();

        $items = [
            NavigationItem::make('Homepage')
                ->group($group)
                ->icon(Heroicon::OutlinedHome)
                ->sort(1)
                ->visible(fn (): bool => static::homepageId() !== null)
                ->url(fn (): ?string => static::homepageId() ? static::getUrl('edit', ['record' => static::homepageId()]) : null)
                ->isActiveWhen(fn (): bool => static::isEditingHomepage()),
        ];

        foreach (PageCategory::cases() as $index => $category) {
            $items[] = NavigationItem::make($category->label())
                ->group($group)
                ->icon(static::categoryIcon($category))
                ->sort($index + 2)
                ->url(fn (): string => static::getUrl('index', ['tab' => $category->value]))
                ->isActiveWhen(fn (): bool => static::activeCategory() === $category);
        }

        $items[] = NavigationItem::make(static::getNavigationLabel())
            ->group($group)
            ->icon(static::getNavigationIcon())
            ->sort(20)
            ->url(fn (): string => static::getUrl('index'))
            ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.*')
                && static::activeCategory() === null
                && ! static::isEditingHomepage());

        return $items;
    }

    public static function categoryIcon(PageCategory $category): Heroicon
    {
        return match ($category) {
            PageCategory::Packages => Heroicon::OutlinedMap,
            PageCategory::Places => Heroicon::OutlinedMapPin,
            PageCategory::Guides => Heroicon::OutlinedBookOpen,
            PageCategory::Blog => Heroicon::OutlinedNewspaper,
            PageCategory::Sections => Heroicon::OutlinedRectangleStack,
            PageCategory::Company => Heroicon::OutlinedBuildingOffice2,
        };
    }

    public static function homepageId(): ?int
    {
        return once(fn (): ?int => Page::query()->where('path', '')->value('id'));
    }

    private static function isEditingHomepage(): bool
    {
        return original_request()->routeIs(static::getRouteBaseName().'.edit')
            && (string) original_request()->route('record') === (string) static::homepageId();
    }

    /**
     * The category of the pages screen being viewed: the list tab, the type
     * being added, or the type of the page being edited.
     */
    private static function activeCategory(): ?PageCategory
    {
        $request = original_request();
        $route = static::getRouteBaseName();

        return match (true) {
            $request->routeIs("{$route}.index") => PageCategory::tryFrom((string) $request->query('tab')),
            $request->routeIs("{$route}.create") => PageCategory::forType(PageType::tryFrom((string) $request->query('type'))),
            $request->routeIs("{$route}.edit") => PageCategory::forType(Page::query()->whereKey($request->route('record'))->value('type')),
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'path', 'meta_title'];
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Page $record */
        return ['URL' => $record->url()];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
