/**
 * Instant search: an accessible combobox (WAI-ARIA 1.2 pattern) that shows
 * matching pages while typing. Without JavaScript the form still submits to
 * /search and renders full results on the server.
 */
const cache = new Map();

/** Typing rhythm for the animated placeholder, in milliseconds. */
const TYPE_DELAY = 80;
const ERASE_DELAY = 35;
const HOLD_DELAY = 2000;
const BLINK_DELAY = 400;
const NEXT_DELAY = 400;

export function initSearch() {
    document.querySelectorAll('[data-search]').forEach((form) => new SearchBox(form));
    initSearchOverlay();

    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('[data-typed-placeholder]').forEach((input) => new TypedPlaceholder(input));
    }

    // "/" focuses the header search, like most modern sites.
    document.addEventListener('keydown', (event) => {
        if (event.key === '/' && !event.target.closest('input, textarea, select, [contenteditable]')) {
            const input = document.querySelector('[data-search] input[type="search"]');
            if (input && input.offsetParent !== null) {
                event.preventDefault();
                input.focus();
            }
        }
    });
}

/**
 * Full-screen search on phones: the header search icon opens it instead of
 * following its /search link. Focus stays inside while open; Escape closes it.
 */
function initSearchOverlay() {
    const overlay = document.querySelector('[data-search-overlay]');
    const input = overlay?.querySelector('input[type="search"]');

    if (!overlay || !input) {
        return;
    }

    const openers = document.querySelectorAll('[data-search-open]');
    let lastFocus = null;
    let hideTimer = null;

    const open = (event) => {
        event.preventDefault();
        clearTimeout(hideTimer);
        lastFocus = document.activeElement;
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        openers.forEach((opener) => opener.setAttribute('aria-expanded', 'true'));
        // Focus inside the tap itself, otherwise mobile browsers keep the keyboard down.
        input.focus();
        requestAnimationFrame(() => overlay.classList.add('is-open'));
    };

    const close = () => {
        if (overlay.hidden) {
            return;
        }
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
        openers.forEach((opener) => opener.setAttribute('aria-expanded', 'false'));
        hideTimer = setTimeout(() => {
            overlay.hidden = true;
        }, 200);
        lastFocus?.focus();
    };

    openers.forEach((opener) => opener.addEventListener('click', open));
    overlay.querySelectorAll('[data-search-close]').forEach((button) => button.addEventListener('click', close));

    // A popular search fills the box and shows its instant suggestions.
    overlay.querySelectorAll('[data-search-term]').forEach((chip) => {
        chip.addEventListener('click', () => {
            input.value = chip.dataset.searchTerm;
            input.dispatchEvent(new Event('input'));
        });
    });

    overlay.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }

        if (event.key === 'Tab') {
            const focusable = [...overlay.querySelectorAll('a, button, input')].filter((el) => el.tabIndex >= 0 && el.offsetParent !== null);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    // The overlay is hidden from "lg" up, so release the scroll lock if the window grows.
    window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
        if (event.matches) {
            close();
        }
    });
}

class SearchBox {
    constructor(form) {
        this.form = form;
        this.input = form.querySelector('input[type="search"]');
        this.list = form.querySelector('[data-search-results]');
        this.endpoint = form.dataset.suggest;
        this.active = -1;
        this.items = [];
        this.controller = null;
        this.timer = null;

        if (!this.input || !this.list || !this.endpoint) {
            return;
        }

        this.input.addEventListener('input', () => this.schedule());
        this.input.addEventListener('keydown', (event) => this.onKey(event));
        this.input.addEventListener('focus', () => {
            if (this.items.length) {
                this.open();
            }
        });

        document.addEventListener('click', (event) => {
            if (!this.form.contains(event.target)) {
                this.close();
            }
        });
    }

    schedule() {
        clearTimeout(this.timer);
        const query = this.input.value.trim();

        if (query.length < 2) {
            this.render(query, []);
            this.close();
            return;
        }

        this.timer = setTimeout(() => this.fetch(query), 160);
    }

    async fetch(query) {
        if (cache.has(query)) {
            this.render(query, cache.get(query));
            return;
        }

        this.controller?.abort();
        this.controller = new AbortController();
        this.form.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(`${this.endpoint}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            const data = await response.json();
            cache.set(query, data.results || []);

            if (this.input.value.trim() === query) {
                this.render(query, data.results || []);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.close();
            }
        } finally {
            this.form.removeAttribute('aria-busy');
        }
    }

    render(query, results) {
        this.items = results;
        this.active = -1;
        this.list.innerHTML = '';

        if (!query) {
            return;
        }

        if (!results.length) {
            const empty = document.createElement('li');
            empty.className = 'px-4 py-6 text-center text-sm text-ink-500';
            empty.setAttribute('role', 'presentation');
            empty.textContent = `No pages match “${query}”. Press Enter to search everything.`;
            this.list.append(empty);
            this.open();
            return;
        }

        results.forEach((result, index) => {
            const option = document.createElement('li');
            option.id = `${this.list.id}-option-${index}`;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.className = 'cursor-pointer';

            const link = document.createElement('a');
            link.href = result.url;
            link.tabIndex = -1;
            link.className = 'flex items-center gap-3 px-3 py-2.5 transition hover:bg-sand-100';

            const thumb = document.createElement(result.image ? 'img' : 'span');
            thumb.className = 'size-12 shrink-0 rounded-lg bg-sand-200 object-cover';
            if (result.image) {
                thumb.src = result.image;
                thumb.alt = '';
                thumb.width = 48;
                thumb.height = 48;
                thumb.loading = 'lazy';
            }

            const text = document.createElement('span');
            text.className = 'min-w-0 flex-1';

            const title = document.createElement('span');
            title.className = 'block truncate text-sm font-semibold text-ink-900';
            highlight(title, result.title, query);

            const meta = document.createElement('span');
            meta.className = 'block truncate text-xs text-ink-500';
            meta.textContent = [result.type, result.section].filter(Boolean).join(' · ');

            text.append(title, meta);
            link.append(thumb, text);
            option.append(link);
            option.addEventListener('mousemove', () => this.highlight(index));
            this.list.append(option);
        });

        const all = document.createElement('li');
        all.setAttribute('role', 'presentation');
        all.innerHTML = `<button type="submit" class="flex w-full items-center justify-between gap-3 border-t border-ink-900/5 px-4 py-3 text-left text-sm font-semibold text-brand-700 hover:bg-sand-100"><span class="truncate">See all results for “<span data-query></span>”</span><span aria-hidden="true">→</span></button>`;
        all.querySelector('[data-query]').textContent = query;
        this.list.append(all);

        this.open();
    }

    onKey(event) {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            if (!this.items.length) {
                return;
            }
            event.preventDefault();
            const delta = event.key === 'ArrowDown' ? 1 : -1;
            this.highlight((this.active + delta + this.items.length) % this.items.length);
            this.open();
        } else if (event.key === 'Enter' && this.active >= 0) {
            event.preventDefault();
            window.location.href = this.items[this.active].url;
        } else if (event.key === 'Escape') {
            if (this.list.hidden) {
                this.input.value = '';
            }
            this.close();
        }
    }

    highlight(index) {
        this.active = index;
        [...this.list.querySelectorAll('[role="option"]')].forEach((option, i) => {
            option.setAttribute('aria-selected', String(i === index));
            if (i === index) {
                option.scrollIntoView({ block: 'nearest' });
                this.input.setAttribute('aria-activedescendant', option.id);
            }
        });
    }

    open() {
        this.list.hidden = false;
        this.input.setAttribute('aria-expanded', 'true');
    }

    close() {
        this.list.hidden = true;
        this.input.setAttribute('aria-expanded', 'false');
        this.input.removeAttribute('aria-activedescendant');
    }
}

/**
 * Types destination names into an empty search box's placeholder one letter
 * at a time, then erases them and moves on. The static placeholder returns
 * while the visitor is focused on or typing in the box.
 */
class TypedPlaceholder {
    constructor(input) {
        this.input = input;
        this.fallback = input.placeholder;
        this.phrases = JSON.parse(input.dataset.typedPlaceholder || '[]').filter(Boolean);
        this.index = 0;
        this.length = 0;
        this.erasing = false;
        this.timer = null;

        if (!this.phrases.length) {
            return;
        }

        input.addEventListener('focus', () => this.stop());
        input.addEventListener('blur', () => this.start());
        document.addEventListener('visibilitychange', () => (document.hidden ? this.stop() : this.start()));

        this.start();
    }

    start() {
        if (this.timer || this.input.value || document.hidden || document.activeElement === this.input) {
            return;
        }

        this.tick();
    }

    stop() {
        clearTimeout(this.timer);
        this.timer = null;
        this.length = 0;
        this.erasing = false;
        this.input.placeholder = this.fallback;
    }

    tick() {
        const phrase = this.phrases[this.index];
        this.length += this.erasing ? -1 : 1;
        this.input.placeholder = phrase.slice(0, this.length);

        let delay = this.erasing ? ERASE_DELAY : TYPE_DELAY;

        if (!this.erasing && this.length >= phrase.length) {
            this.erasing = true;

            // Blink the final dot of "Haridwar..." while the phrase is held.
            if (phrase.endsWith('.') || phrase.endsWith('…')) {
                this.blink(phrase, HOLD_DELAY / BLINK_DELAY);
                return;
            }

            delay = HOLD_DELAY;
        } else if (this.erasing && this.length <= 0) {
            this.erasing = false;
            this.index = (this.index + 1) % this.phrases.length;
            delay = NEXT_DELAY;
        }

        this.timer = setTimeout(() => this.tick(), delay);
    }

    blink(phrase, remaining) {
        const withoutLastDot = phrase.endsWith('…') ? `${phrase.slice(0, -1)}..` : phrase.slice(0, -1);
        this.input.placeholder = remaining % 2 ? withoutLastDot : phrase;

        this.timer = setTimeout(() => (remaining > 1 ? this.blink(phrase, remaining - 1) : this.tick()), BLINK_DELAY);
    }
}

function highlight(element, text, query) {
    const terms = query.toLowerCase().split(/\s+/).filter((term) => term.length > 1);
    const pattern = terms.length ? new RegExp(`(${terms.map((t) => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|')})`, 'ig') : null;

    if (!pattern) {
        element.textContent = text;
        return;
    }

    text.split(pattern).forEach((part) => {
        if (pattern.test(part)) {
            const mark = document.createElement('mark');
            mark.textContent = part;
            element.append(mark);
        } else {
            element.append(document.createTextNode(part));
        }
        pattern.lastIndex = 0;
    });
}
