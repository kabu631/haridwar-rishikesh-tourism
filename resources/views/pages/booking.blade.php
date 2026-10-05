@extends('layouts.app')

@section('content')
    <section class="bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 text-white">
        <div class="container-x py-12 sm:py-16">
            <x-breadcrumbs :items="$seo->breadcrumbs" class="text-white/90" />
            <h1 class="mt-4 font-display text-3xl font-semibold sm:text-5xl">{{ $page?->title ?? 'Booking Form' }}</h1>
            <p class="mt-3 max-w-2xl text-lg text-white/80">Send us your travel plan for Haridwar, Rishikesh or anywhere in Uttarakhand. Our Haridwar office will reply with an itinerary and quote.</p>
        </div>
    </section>

    <div class="container-x grid gap-10 py-10 lg:grid-cols-[minmax(0,1fr)_340px] lg:py-14">
        <form action="{{ route('booking.store', [], false) }}" method="post" data-enquiry="booking" data-redirect class="rounded-3xl bg-white p-6 ring-1 ring-ink-900/5 shadow-card sm:p-8" novalidate>
            @csrf
            <input type="hidden" name="type" value="booking">
            <input type="hidden" name="source" value="{{ old('source') }}">
            @if ($page)
                <input type="hidden" name="page_id" value="{{ $page->id }}">
            @endif
            <div class="absolute -left-[9999px]" aria-hidden="true"><label for="booking-website">Website</label><input id="booking-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>

            @if ($errors->any())
                <div class="mb-6 rounded-2xl bg-brand-50 p-4 text-sm text-brand-800 ring-1 ring-brand-200" role="alert">
                    <p class="font-semibold">Please correct the following:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <fieldset>
                <legend class="font-display text-xl font-semibold text-ink-900">Your details</legend>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="booking-name" class="field-label">Full name <span class="text-brand-600">*</span></label>
                        <input id="booking-name" name="name" type="text" required minlength="2" maxlength="120" autocomplete="name" value="{{ old('name') }}" class="field" @error('name') aria-invalid="true" @enderror>
                    </div>
                    <div>
                        <label for="booking-email" class="field-label">Email address <span class="text-brand-600">*</span></label>
                        <input id="booking-email" name="email" type="email" required maxlength="190" autocomplete="email" value="{{ old('email') }}" class="field" @error('email') aria-invalid="true" @enderror>
                    </div>
                    <div>
                        <label for="booking-phone" class="field-label">Mobile / WhatsApp <span class="text-brand-600">*</span></label>
                        <input id="booking-phone" name="phone" type="tel" required minlength="7" maxlength="30" autocomplete="tel" inputmode="tel" value="{{ old('phone') }}" class="field" @error('phone') aria-invalid="true" @enderror>
                    </div>
                </div>
            </fieldset>

            <fieldset class="mt-8">
                <legend class="font-display text-xl font-semibold text-ink-900">Your trip</legend>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="booking-tour" class="field-label">Tour / destination</label>
                        <input id="booking-tour" name="tour" type="text" maxlength="190" list="tour-options" value="{{ old('tour', $tour) }}" placeholder="e.g. Haridwar Rishikesh Tour, Char Dham Yatra" class="field">
                        <datalist id="tour-options">
                            @foreach ($tours as $option)
                                <option value="{{ $option }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label for="booking-travel" class="field-label">Arrival date</label>
                        <input id="booking-travel" name="travel_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('travel_date') }}" class="field">
                    </div>
                    <div>
                        <label for="booking-return" class="field-label">Departure date</label>
                        <input id="booking-return" name="return_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('return_date') }}" class="field">
                    </div>
                    <div>
                        <label for="booking-adults" class="field-label">Adults</label>
                        <input id="booking-adults" name="adults" type="number" min="1" max="99" inputmode="numeric" value="{{ old('adults', 2) }}" class="field">
                    </div>
                    <div>
                        <label for="booking-children" class="field-label">Children</label>
                        <input id="booking-children" name="children" type="number" min="0" max="99" inputmode="numeric" value="{{ old('children', 0) }}" class="field">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="booking-message" class="field-label">Additional information</label>
                        <textarea id="booking-message" name="message" rows="5" maxlength="3000" class="field" placeholder="Hotel category, pick-up city, special requests…">{{ old('message') }}</textarea>
                    </div>
                </div>
            </fieldset>

            <button type="submit" class="btn-primary mt-8 w-full sm:w-auto sm:px-10">Send booking request <x-glyph name="arrow-right" class="size-4" /></button>
            <p data-form-status hidden class="mt-4"></p>
        </form>

        <aside class="space-y-6">
            <div class="rounded-3xl bg-brand-900 p-6 text-white">
                <p class="font-display text-lg font-semibold">Prefer to talk?</p>
                <div class="mt-4 grid gap-2">
                    <a href="{{ $site->phoneHref() }}" class="btn bg-white text-brand-800"><x-glyph name="phone" class="size-4" /> {{ $site->get('phone') }}</a>
                    <a href="{{ $site->whatsappUrl('Hello! I would like to book a tour.') }}" target="_blank" rel="noopener" class="btn-light"><x-glyph name="whatsapp" class="size-4" /> WhatsApp</a>
                    <a href="mailto:{{ $site->get('email') }}" class="btn-light"><x-glyph name="mail" class="size-4" /> Email us</a>
                </div>
            </div>
            <div class="rounded-3xl bg-white p-6 ring-1 ring-ink-900/5">
                <p class="font-display text-lg font-semibold text-ink-900">Book with confidence</p>
                <ul class="mt-4 space-y-3 text-sm text-ink-700">
                    @foreach ((array) $site->get('credentials') as $credential)
                        <li class="flex gap-2"><x-glyph name="check" class="mt-0.5 size-4 shrink-0 text-brand-600" /> <span>{{ $credential['name'] }}<br><span class="font-semibold text-ink-900">{{ $credential['value'] }}</span></span></li>
                    @endforeach
                </ul>
            </div>
        </aside>
    </div>
@endsection
