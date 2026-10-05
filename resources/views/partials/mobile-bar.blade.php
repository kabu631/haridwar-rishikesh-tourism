{{-- Persistent quick actions on phones (no pop-ups or interstitials). --}}
<nav aria-label="Quick contact" class="fixed inset-x-0 bottom-0 z-30 border-t border-ink-900/10 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden">
    <ul class="grid h-16 grid-cols-3 text-xs font-semibold">
        <li>
            <a href="{{ $site->phoneHref() }}" class="flex h-full flex-col items-center justify-center gap-1 text-ink-700 active:bg-sand-100">
                <x-glyph name="phone" class="size-5 text-brand-600" /> Call
            </a>
        </li>
        <li>
            <a href="{{ $site->whatsappUrl('Hello! I would like information about a Haridwar Rishikesh tour.') }}" target="_blank" rel="noopener" class="flex h-full flex-col items-center justify-center gap-1 text-ink-700 active:bg-sand-100">
                <x-glyph name="whatsapp" class="size-5 text-brand-600" /> WhatsApp
            </a>
        </li>
        <li>
            <a href="/book-now.php" class="flex h-full flex-col items-center justify-center gap-1 bg-brand-600 text-white active:bg-brand-700">
                <x-glyph name="calendar" class="size-5" /> Enquire
            </a>
        </li>
    </ul>
</nav>
