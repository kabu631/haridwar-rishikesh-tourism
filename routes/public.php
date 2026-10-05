<?php

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LlmsTxtController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public, cacheable routes
|--------------------------------------------------------------------------
|
| Served without sessions or cookies (middleware group "public") so pages
| can be cached in full by the application and by a CDN. Every legacy URL
| keeps its exact address, including the ".html" suffix.
|
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:search')->name('search.suggest');

Route::get('/chatbot/packages', [ChatbotController::class, 'packages'])->name('chatbot.packages');

Route::get('/sitemap.xml', [SitemapController::class, 'xml'])->name('sitemap.xml');
Route::get('/sitemap.html', [SitemapController::class, 'html'])->name('sitemap.html');
Route::get('/urllist.txt', [SitemapController::class, 'urlList'])->name('sitemap.urllist');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/feed.xml', FeedController::class)->name('feed');
Route::get('/llms.txt', [LlmsTxtController::class, 'index'])->name('llms');
Route::get('/llms-full.txt', [LlmsTxtController::class, 'full'])->name('llms.full');
Route::get('/{key}.txt', [RobotsController::class, 'indexNowKey'])->where('key', '[A-Za-z0-9\-]{8,128}')->name('indexnow.key');

Route::get('/{path}', [PageController::class, 'show'])
    ->where('path', '[A-Za-z0-9_\-]+\.html')
    ->name('page');
