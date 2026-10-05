<section class="container-x" aria-label="Talk to us">
    <div class="rounded-[2rem] bg-brand-50 px-6 py-12 ring-1 ring-brand-100 sm:px-12 lg:flex lg:items-center lg:justify-between lg:gap-10 lg:py-14" data-reveal>
        <div class="max-w-2xl">
            <p class="eyebrow">{{ $site->get('cta.eyebrow') }}</p>
            <p class="mt-3 font-display text-3xl leading-tight font-semibold text-ink-900 sm:text-4xl">{{ $site->get('cta.heading') }}</p>
        </div>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row lg:mt-0 lg:shrink-0">
            <a href="{{ $site->phoneHref() }}" class="btn-primary"><x-glyph name="phone" class="size-4" /> {{ $site->get('phone') }}</a>
            <a href="{{ $site->whatsappUrl('Hello! I would like to plan a trip.') }}" target="_blank" rel="noopener" class="btn-ghost"><x-glyph name="whatsapp" class="size-4" /> WhatsApp us</a>
        </div>
    </div>
</section>
