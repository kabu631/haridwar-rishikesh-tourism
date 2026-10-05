{{-- Under "Book your …" / "Enquiry form" headings, where the legacy page had a (dead) Book Now image --}}
<div class="my-6 flex flex-wrap items-center gap-3 rounded-2xl bg-white p-5 ring-1 ring-ink-900/5">
    <a href="#enquire" class="btn-primary"><x-glyph name="calendar" class="size-4" /> Send an enquiry</a>
    <a href="/book-now.php?{{ http_build_query(['tour' => $page->path]) }}" class="btn-ghost">Book now <x-glyph name="arrow-right" class="size-4" /></a>
    <a href="{{ $site->whatsappUrl('Hello! I would like to book: '.$page->title) }}" target="_blank" rel="noopener" class="btn-ghost"><x-glyph name="whatsapp" class="size-4 text-[#25d366]" /> WhatsApp</a>
    <a href="{{ $site->phoneHref() }}" class="inline-flex items-center gap-2 px-2 text-sm font-semibold text-ink-900 hover:text-brand-700"><x-glyph name="phone" class="size-4" /> {{ $site->get('phone') }}</a>
</div>
