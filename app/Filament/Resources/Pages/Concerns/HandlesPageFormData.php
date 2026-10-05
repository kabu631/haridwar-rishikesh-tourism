<?php

namespace App\Filament\Resources\Pages\Concerns;

use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Models\Page;

/**
 * Maps between the stored page and the admin form: editor mode, hero image
 * paths (stored root relative, edited relative to the public disk) and the
 * partially-edited "extra" JSON column.
 */
trait HandlesPageFormData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['body_source'] = $data['body'] ?? '';

        if (filled($data['hero_image'] ?? null)) {
            $data['hero_image'] = ltrim(rawurldecode($data['hero_image']), '/');
        }

        $data['extra'] ??= [];
        $data['extra']['editor'] = PageForm::editorMode($this->record instanceof Page ? $this->record : null);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePageData(array $data, ?Page $record): array
    {
        if (array_key_exists('body_source', $data)) {
            $data['body'] = $data['body_source'];
        }
        unset($data['body_source']);

        if (array_key_exists('hero_image', $data)) {
            $data['hero_image'] = filled($data['hero_image']) ? '/'.ltrim($data['hero_image'], '/') : null;
        }

        foreach (['cards', 'gallery'] as $list) {
            if (is_array($data[$list] ?? null)) {
                $data[$list] = $this->rootImagePaths($data[$list]);
            }
        }

        if (array_key_exists('extra', $data)) {
            $data['extra'] = $this->mergeExtra($record?->extra ?? [], $this->rootImagePaths($data['extra'] ?? []));
        }

        return $data;
    }

    /**
     * Uploads come back relative to the public disk ("uploads/x.jpg"); the
     * site stores and renders root relative paths ("/uploads/x.jpg").
     *
     * @param  array<string|int, mixed>  $values
     * @return array<string|int, mixed>
     */
    private function rootImagePaths(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->rootImagePaths($value);
            } elseif (in_array($key, ['image', 'full'], true) && is_string($value) && $value !== '' && ! preg_match('#^(https?:)?//#i', $value)) {
                $values[$key] = '/'.ltrim($value, '/');
            }
        }

        return $values;
    }

    /**
     * Merge edited keys into the stored array. Lists (repeaters, tags) are
     * replaced so removed items disappear; keys the form does not show are kept.
     *
     * @param  array<string|int, mixed>  $stored
     * @param  array<string|int, mixed>  $edited
     * @return array<string|int, mixed>
     */
    private function mergeExtra(array $stored, array $edited): array
    {
        foreach ($edited as $key => $value) {
            if (is_array($value) && ! array_is_list($value) && is_array($stored[$key] ?? null) && ! array_is_list($stored[$key])) {
                $stored[$key] = $this->mergeExtra($stored[$key], $value);
            } else {
                $stored[$key] = is_array($value) && array_is_list($value) ? array_values($value) : $value;
            }
        }

        return $stored;
    }
}
