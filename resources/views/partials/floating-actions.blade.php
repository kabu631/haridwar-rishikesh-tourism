{{--
    Back-to-top button and the trip assistant: a guided chat that suggests tour
    packages for the option a visitor taps (config/chatbot.php). Neither opens
    on its own – no pop-ups or interstitials.
--}}
<div class="group/fab pointer-events-none fixed right-4 bottom-[calc(5rem+env(safe-area-inset-bottom))] z-40 flex flex-col items-end gap-3 lg:right-6 lg:bottom-6">
    <button type="button" data-back-to-top aria-label="Back to top"
        class="pointer-events-auto invisible relative grid size-12 translate-y-3 place-items-center rounded-full bg-white text-brand-700 opacity-0 shadow-lift ring-1 ring-ink-900/10 transition-all duration-300 hover:-translate-y-0.5 hover:text-brand-800 group-has-[.is-open]/fab:hidden [&.is-visible]:visible [&.is-visible]:translate-y-0 [&.is-visible]:opacity-100">
        <svg class="absolute inset-0 size-full -rotate-90" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
            <circle cx="24" cy="24" r="22.5" fill="none" stroke-width="2" class="stroke-sand-200" />
            <circle data-scroll-progress cx="24" cy="24" r="22.5" fill="none" stroke-width="2.5" stroke-linecap="round" pathLength="100" stroke-dasharray="100" stroke-dashoffset="100" class="stroke-saffron-500" />
        </svg>
        <x-glyph name="arrow-up" class="size-5" />
    </button>

    <div data-chatbot data-endpoint="{{ route('chatbot.packages', [], false) }}" data-whatsapp="{{ $site->whatsappUrl() }}" data-greeting="{{ config('chatbot.greeting') }}" class="pointer-events-auto relative">
        <section id="chatbot-panel" data-chatbot-panel hidden role="dialog" aria-labelledby="chatbot-title"
            class="absolute right-0 bottom-[calc(100%+0.75rem)] flex max-h-[calc(100dvh-10rem)] w-[min(24rem,calc(100vw-2rem))] origin-bottom-right translate-y-3 scale-95 flex-col overflow-hidden rounded-3xl bg-sand-50 opacity-0 shadow-2xl ring-1 ring-ink-900/10 transition duration-200 ease-out lg:max-h-[min(36rem,calc(100dvh-14.5rem))] [&.is-open]:translate-y-0 [&.is-open]:scale-100 [&.is-open]:opacity-100">
            <header class="relative flex items-center gap-3 bg-brand-900 px-5 py-4 text-white">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-white/10 ring-1 ring-white/20">
                    <x-glyph name="compass" class="size-6 text-saffron-300" />
                </span>
                <div class="min-w-0 flex-1">
                    <p id="chatbot-title" class="font-display text-lg leading-tight font-semibold">Trip Assistant</p>
                    <p class="mt-0.5 flex items-center gap-1.5 text-xs text-white/75">
                        Local Haridwar experts since 1995
                    </p>
                </div>
                <button type="button" data-chatbot-close class="grid size-10 shrink-0 place-items-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white" aria-label="Close chat">
                    <x-glyph name="x" class="size-5" />
                </button>
            </header>

            <div data-chatbot-log role="log" aria-live="polite" aria-label="Conversation" class="relative flex-1 space-y-3 overflow-y-auto overscroll-contain px-4 py-5"></div>

            <footer class="flex items-center justify-between gap-3 border-t border-ink-900/5 bg-white px-4 py-3 text-xs text-ink-500">
                <span>Rather talk to us?</span>
                <span class="flex gap-2">
                    <a href="{{ $site->phoneHref() }}" class="chip transition hover:text-brand-700"><x-glyph name="phone" class="size-3.5" /> Call</a>
                    <a href="{{ $site->whatsappUrl('Hello! Please suggest a tour package for my trip.') }}" target="_blank" rel="noopener" class="chip transition hover:text-brand-700"><x-glyph name="whatsapp" class="size-3.5 text-brand-600" /> WhatsApp</a>
                </span>
            </footer>
        </section>

        <button type="button" data-chatbot-toggle aria-expanded="false" aria-controls="chatbot-panel" title="Find my tour"
            class="group relative grid size-14 place-items-center rounded-full bg-brand-600 text-white shadow-lift transition hover:-translate-y-0.5 hover:bg-brand-700">
            <span class="relative grid size-6 place-items-center">
                <x-glyph name="chat" class="size-6 transition duration-200 group-aria-expanded:scale-50 group-aria-expanded:opacity-0" />
                <x-glyph name="x" class="absolute size-6 scale-50 opacity-0 transition duration-200 group-aria-expanded:scale-100 group-aria-expanded:opacity-100" />
            </span>
            <span class="sr-only">Find my tour</span>
        </button>

        {{-- Message templates used by resources/js/modules/chatbot.js --}}
        <template data-chatbot-template="bot">
            <div class="flex items-end gap-2">
                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-900 text-saffron-300"><x-glyph name="compass" class="size-4" /></span>
                <p data-content class="max-w-[85%] rounded-2xl rounded-bl-md bg-white px-4 py-2.5 text-[14.5px] leading-relaxed text-ink-700 shadow-sm ring-1 ring-ink-900/5"></p>
            </div>
        </template>

        <template data-chatbot-template="user">
            <div class="flex justify-end">
                <p data-content class="max-w-[85%] rounded-2xl rounded-br-md bg-brand-600 px-4 py-2.5 text-[14.5px] leading-relaxed text-white"></p>
            </div>
        </template>

        <template data-chatbot-template="typing">
            <div class="flex items-end gap-2" aria-label="Typing">
                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-900 text-saffron-300"><x-glyph name="compass" class="size-4" /></span>
                <span class="flex gap-1 rounded-2xl rounded-bl-md bg-white px-4 py-3.5 shadow-sm ring-1 ring-ink-900/5">
                    <span class="size-1.5 animate-bounce rounded-full bg-ink-400 [animation-delay:-0.3s]"></span>
                    <span class="size-1.5 animate-bounce rounded-full bg-ink-400 [animation-delay:-0.15s]"></span>
                    <span class="size-1.5 animate-bounce rounded-full bg-ink-400"></span>
                </span>
            </div>
        </template>

        <template data-chatbot-template="topics">
            <div data-chatbot-choices class="flex flex-wrap gap-2 pl-9">
                @foreach (config('chatbot.topics') as $topic)
                    <button type="button" data-choice="topic" data-topic="{{ $topic['key'] }}" data-reply="{{ $topic['reply'] }}" data-url="{{ $topic['url'] }}" class="chat-option">
                        <x-glyph :name="$topic['icon']" class="size-4 text-saffron-600" /> <span data-label>{{ $topic['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </template>

        <template data-chatbot-template="choices">
            <div data-chatbot-choices class="flex flex-wrap gap-2 pl-9"></div>
        </template>

        <template data-chatbot-template="icon-clock"><x-glyph name="clock" class="size-4 text-saffron-600" /></template>
        <template data-chatbot-template="icon-refresh"><x-glyph name="refresh" class="size-4 text-saffron-600" /></template>
        <template data-chatbot-template="icon-arrow-right"><x-glyph name="arrow-right" class="size-4 text-saffron-600" /></template>
        <template data-chatbot-template="icon-whatsapp"><x-glyph name="whatsapp" class="size-4 text-saffron-600" /></template>

        <template data-chatbot-template="packages">
            <ul class="space-y-2 pl-9"></ul>
        </template>

        <template data-chatbot-template="package">
            <li>
                <a data-package class="group/card flex items-center gap-3 rounded-2xl bg-white p-2 pr-3 shadow-sm ring-1 ring-ink-900/5 transition hover:shadow-card hover:ring-brand-200">
                    <span class="size-14 shrink-0 overflow-hidden rounded-xl bg-sand-200">
                        <img data-image width="56" height="56" alt="" loading="lazy" decoding="async" class="size-full object-cover transition duration-300 group-hover/card:scale-105">
                    </span>
                    <span class="min-w-0 flex-1">
                        <span data-title class="line-clamp-2 text-[13.5px] leading-snug font-semibold text-ink-900 group-hover/card:text-brand-700"></span>
                        <span class="mt-1 flex items-center gap-1 text-xs text-ink-500"><x-glyph name="clock" class="size-3.5 text-saffron-600" /> <span data-duration></span></span>
                    </span>
                    <x-glyph name="chevron-right" class="size-4 text-ink-400 transition group-hover/card:translate-x-0.5 group-hover/card:text-brand-600" />
                </a>
            </li>
        </template>
    </div>
</div>
