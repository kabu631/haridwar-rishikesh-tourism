<?php

namespace App\Models;

use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['menu', 'parent_id', 'label', 'url', 'title', 'description', 'open_in_new_tab', 'is_active', 'sort_order'])]
class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    public const MENUS = [
        'main' => 'Main navigation',
        'footer' => 'Footer links',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<MenuItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Top-level, active items of one menu in display order.
     *
     * @param  Builder<MenuItem>  $query
     */
    #[Scope]
    protected function inMenu(Builder $query, string $menu): void
    {
        $query->where('menu', $menu)->where('is_active', true)->whereNull('parent_id')->orderBy('sort_order');
    }

    public function isExternal(): bool
    {
        return (bool) preg_match('#^https?://#i', $this->url);
    }
}
