<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\Seo\SeoBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * The legacy booking form URL (/book-now.php), kept for its rankings.
     */
    public function create(Request $request, SeoBuilder $seo): View
    {
        $page = Page::query()->published()->where('path', 'book-now.php')->with(['parent', 'author'])->first();

        $tour = trim((string) $request->query('tour', ''));

        return view('pages.booking', [
            'page' => $page,
            'tour' => mb_substr($tour, 0, 190),
            'tours' => Page::query()->published()->where('type', 'package')->orderBy('title')->pluck('title'),
            'seo' => $page
                ? $seo->forPage($page)
                : $seo->forView('Book Haridwar Rishikesh Tour Packages Online', 'Send a booking request for Haridwar, Rishikesh and Uttarakhand tour packages.', 'book-now.php', true),
        ]);
    }
}
