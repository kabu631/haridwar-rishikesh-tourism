<section class="mt-12 grid gap-6 md:grid-cols-2" aria-label="Contact details">
    <div class="space-y-4 rounded-3xl bg-white p-6 ring-1 ring-ink-900/5 sm:p-7">
        <p class="font-display text-xl font-semibold text-ink-900">{{ $site->get('legal_name') }}</p>
        <address class="space-y-4 text-[15px] not-italic text-ink-700">
            <p class="flex gap-3"><x-glyph name="map-pin" class="mt-0.5 size-5 text-saffron-500" /> {{ $site->addressLine() }}</p>
            <p class="flex gap-3"><x-glyph name="phone" class="mt-0.5 size-5 text-saffron-500" />
                <span><a href="{{ $site->phoneHref() }}" class="font-semibold text-ink-900 hover:text-brand-700">{{ $site->get('phone') }}</a> (mobile / WhatsApp)<br>
                <a href="{{ $site->phoneHref($site->get('landline')) }}" class="hover:text-brand-700">{{ $site->get('landline') }}</a> (office)</span>
            </p>
            <p class="flex gap-3"><x-glyph name="mail" class="mt-0.5 size-5 text-saffron-500" /> <a href="mailto:{{ $site->get('email') }}" class="break-all hover:text-brand-700">{{ $site->get('email') }}</a></p>
        </address>
        <a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($site->get('legal_name').', '.$site->addressLine()) }}" target="_blank" rel="noopener" class="btn-ghost w-full"><x-glyph name="map" class="size-4" /> Get directions</a>
    </div>
    <x-enquiry-form id="contact-form" type="contact" title="Send us a message" subtitle="We reply by email or WhatsApp." />
</section>
