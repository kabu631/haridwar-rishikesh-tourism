<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\PageCategory;
use App\Enums\PageType;
use App\Filament\Resources\Pages\Concerns\HandlesPageFormData;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Author;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

class CreatePage extends CreateRecord
{
    use HandlesPageFormData;

    protected static string $resource = PageResource::class;

    /**
     * Page type chosen from the sidebar category ("Add tour package" …).
     */
    #[Url]
    public ?string $type = null;

    public function getTitle(): string|Htmlable
    {
        return 'Add '.(PageCategory::forType(PageType::tryFrom((string) $this->type))?->singularLabel() ?? 'page');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->preparePageData($data, null);
        $data['published_at'] ??= now();
        $data['author_id'] ??= Author::query()->value('id');

        return $data;
    }
}
