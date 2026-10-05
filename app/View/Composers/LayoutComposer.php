<?php

namespace App\View\Composers;

use App\Models\MenuItem;
use App\Support\SiteSettings;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

/**
 * Shares navigation and business details with the site layout.
 */
class LayoutComposer
{
    /**
     * Menu URLs rendered as header buttons or utility-bar links instead of
     * navigation items.
     */
    private const HEADER_ACTIONS = ['/', '/book-now.php', '/contact-us.html', '/about-us.html', '/gallery.html'];

    public function __construct(private SiteSettings $settings) {}

    public function compose(View $view): void
    {
        $main = $this->menu('main');

        $view->with([
            'site' => $this->settings,
            'mainMenu' => $main,
            'navigation' => $main->reject(fn (MenuItem $item): bool => in_array($item->url, self::HEADER_ACTIONS, true))->values(),
            'footerMenu' => $this->menu('footer'),
        ]);
    }

    /**
     * @return Collection<int, MenuItem>
     */
    private function menu(string $name): Collection
    {
        try {
            return MenuItem::query()->inMenu($name)->with('children')->get();
        } catch (Throwable) {
            return collect();
        }
    }
}
