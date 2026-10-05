@if ($authors->isNotEmpty())
    <section class="mt-12" aria-labelledby="editorial-team">
        <h2 id="editorial-team" class="section-title text-2xl">Editorial team</h2>
        <p class="mt-2 text-ink-500">The people who write and review the travel guides and tour itineraries on this website.</p>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            @foreach ($authors as $author)
                <article id="{{ $author->slug }}" class="scroll-mt-36 rounded-3xl bg-white p-6 ring-1 ring-ink-900/5">
                    <div class="flex items-center gap-4">
                        <span class="grid size-14 place-items-center rounded-2xl bg-brand-600 font-display text-xl font-semibold text-white">{{ \Illuminate\Support\Str::of($author->name)->explode(' ')->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}</span>
                        <div>
                            <h3 class="font-display text-lg font-semibold text-ink-900">{{ $author->name }}</h3>
                            <p class="text-sm text-ink-500">{{ $author->job_title }}</p>
                        </div>
                    </div>
                    <p class="mt-4 text-[15px] leading-relaxed text-ink-700">{{ $author->bio }}</p>
                </article>
            @endforeach
        </div>
    </section>
@endif
