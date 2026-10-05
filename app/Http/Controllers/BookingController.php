<?php

namespace App\Http\Controllers;

use App\Enums\PageType;
use App\Models\Page;
use App\Support\Seo\SeoBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * The legacy booking form URL (/book-now.php), kept for its rankings.
     * "Book this tour now" links on tour package pages pass the package (?tour=haridwar-tour-package.html);
     * the form then books that tour and the visitor cannot change it.
     */
    public function create(Request $request, SeoBuilder $seo): View
    {
        $page = Page::query()->published()->where('path', 'book-now.php')->with(['parent', 'author'])->first();

        $tour = trim((string) $request->query('tour', ''));
        $isPagePath = (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*\.html$/', $tour);
        $tourPage = $isPagePath
            ? Page::query()->published()->where('path', $tour)->where('type', PageType::Package)->first()
            : null;

        return view('pages.booking', [
            'page' => $page,
            // Any other page address (contact-us.html…) is not a tour: the field stays empty.
            'tour' => $tourPage?->title ?? ($isPagePath ? '' : mb_substr($tour, 0, 190)),
            'tourPage' => $tourPage,
            'tours' => Page::query()->published()->where('type', 'package')->orderBy('title')->pluck('title'),
            'seo' => $page
                ? $seo->forPage($page)
                : $seo->forView('Book Haridwar Rishikesh Tour Packages Online', 'Send a booking request for Haridwar, Rishikesh and Uttarakhand tour packages.', 'book-now.php', true),
        ]);
    }
}
