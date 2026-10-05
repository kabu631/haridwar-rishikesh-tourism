@props([
    'tour' => null,
    'pageId' => null,
    'type' => 'quick',
    'title' => 'Plan your trip with a local expert',
    'subtitle' => 'Share your dates and our Haridwar team will send a tailored quote.',
    'compact' => false,
    'dark' => false,
    'id' => 'enquiry',
])
<form action="{{ route('enquiry.store', [], false) }}" method="post" data-enquiry="{{ $type }}" novalidate
    {{ $attributes->merge(['class' => $dark ? 'rounded-3xl bg-ink-900/70 p-6 text-white ring-1 ring-white/15 backdrop-blur-md sm:p-7' : 'rounded-3xl bg-white p-6 ring-1 ring-ink-900/5 shadow-card sm:p-7']) }}>
    <p @class(['font-display text-xl font-semibold', 'text-white' => $dark, 'text-ink-900' => ! $dark])>{{ $title }}</p>
    @if ($subtitle)
        <p @class(['mt-1 text-sm', 'text-white/75' => $dark, 'text-ink-500' => ! $dark])>{{ $subtitle }}</p>
    @endif

    <input type="hidden" name="type" value="{{ $type }}">
    <input type="hidden" name="source" value="">
    @if ($tour)
        <input type="hidden" name="tour" value="{{ $tour }}">
    @endif
    @if ($pageId)
        <input type="hidden" name="page_id" value="{{ $pageId }}">
    @endif
    {{-- Honeypot: hidden from people, irresistible to bots --}}
    <div class="absolute -left-[9999px]" aria-hidden="true">
        <label for="{{ $id }}-website">Website</label>
        <input id="{{ $id }}-website" type="text" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="mt-5 grid gap-3 {{ $compact ? '' : 'sm:grid-cols-2' }}">
        <div class="{{ $compact ? '' : 'sm:col-span-2' }}">
            <label for="{{ $id }}-name" class="sr-only">Your name</label>
            <input id="{{ $id }}-name" name="name" type="text" required minlength="2" maxlength="120" autocomplete="name" placeholder="Your name" class="field">
        </div>
        <div>
            <label for="{{ $id }}-email" class="sr-only">Email address</label>
            <input id="{{ $id }}-email" name="email" type="email" required maxlength="190" autocomplete="email" placeholder="Email address" class="field">
        </div>
        <div>
            <label for="{{ $id }}-phone" class="sr-only">Phone / WhatsApp</label>
            <input id="{{ $id }}-phone" name="phone" type="tel" required minlength="7" maxlength="30" autocomplete="tel" inputmode="tel" placeholder="Phone / WhatsApp" class="field">
        </div>
        @unless ($compact)
            <div class="sm:col-span-2">
                <label for="{{ $id }}-date" @class(['field-label', '!text-white/80' => $dark])>Travel date (optional)</label>
                <input id="{{ $id }}-date" name="travel_date" type="date" min="{{ now()->toDateString() }}" class="field">
            </div>
            <div class="sm:col-span-2">
                <label for="{{ $id }}-message" class="sr-only">Message</label>
                <textarea id="{{ $id }}-message" name="message" rows="3" maxlength="3000" placeholder="Number of travellers, hotel category, special requests…" class="field resize-y"></textarea>
            </div>
        @endunless
    </div>

    <button type="submit" class="btn-primary mt-4 w-full">Send enquiry <x-glyph name="arrow-right" class="size-4" /></button>
    <p data-form-status hidden class="mt-3"></p>
    <p @class(['mt-3 flex items-center gap-1.5 text-xs', 'text-white/70' => $dark, 'text-ink-500' => ! $dark])>
        <x-glyph name="shield" class="size-4 shrink-0 opacity-70" /> Your details are only used to answer your enquiry.
    </p>
</form>
