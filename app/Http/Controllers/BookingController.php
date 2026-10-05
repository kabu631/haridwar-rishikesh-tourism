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
     * "Book this tour now" links pass the tour's page (?tour=haridwar-tour-package.html);
     * the form then books that tour and the visitor cannot change it.
     */
    public function create(Request $request, SeoBuilder $seo): View
    {
        $page = Page::query()->published()->where('path', 'book-now.php')->with(['parent', 'author'])->first();

        $tour = trim((string) $request->query('tour', ''));
        $tourPage = preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*\.html$/', $tour)
            ? Page::query()->published()->where('path', $tour)->first()
            : null;

        return view('pages.booking', [
            'page' => $page,
            'tour' => $tourPage?->title ?? mb_substr($tour, 0, 190),
            'tourPage' => $tourPage,
            'tours' => Page::query()->published()->where('type', 'package')->orderBy('title')->pluck('title'),
            'seo' => $page
                ? $seo->forPage($page)
                : $seo->forView('Book Haridwar Rishikesh Tour Packages Online', 'Send a booking request for Haridwar, Rishikesh and Uttarakhand tour packages.', 'book-now.php', true),
        ]);
    }
}
