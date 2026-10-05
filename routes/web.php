<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\EnquiryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Session routes
|--------------------------------------------------------------------------
|
| Forms that need sessions (validation errors, old input, CSRF). Public
| content routes live in routes/public.php.
|
*/

Route::get('/book-now.php', [BookingController::class, 'create'])->name('booking.create');
Route::post('/book-now.php', [EnquiryController::class, 'store'])->middleware('throttle:enquiries')->name('booking.store');

Route::post('/enquiry', [EnquiryController::class, 'store'])->middleware('throttle:enquiries')->name('enquiry.store');
Route::get('/thank-you', [EnquiryController::class, 'thankYou'])->name('enquiry.thanks');
