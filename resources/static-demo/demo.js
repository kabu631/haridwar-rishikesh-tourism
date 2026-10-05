/**
 * Static preview helpers (copied into the export by `php artisan demo:export`).
 * The preview has no server, so this script stands in for it:
 *  - instant search and the /search page run in the browser from search-index.json;
 *  - enquiry and booking forms are switched off and say so;
 *  - a slim bar tells visitors this is a preview of the new website.
 */
(() => {
    const BASE = window.__DEMO_BASE__ || '';
    let index;

    const loadIndex = () => (index ??= window._demoFetch(`${BASE}/demo/search-index.json`).then((response) => response.json()));
    const normalise = (text) => (text || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

    function search(pages, query, limit) {
        const terms = normalise(query).split(/[^a-z0-9]+/).filter((term) => term.length > 1);

        if (!terms.length) {
            return [];
        }

        return pages
            .map((page) => {
                const title = normalise(page.title);
                const text = normalise(page.text);
                let score = title.startsWith(terms[0]) ? 1 : 0;

                for (const term of terms) {
                    if (title.includes(term)) {
                        score += 3;
                    } else if (text.includes(term)) {
                        score += Math.min(text.split(term).length - 1, 4) * 0.5;
                    } else {
                        return null;
                    }
                }

                return { page, score };
            })
            .filter(Boolean)
            .sort((a, b) => b.score - a.score || a.page.title.length - b.page.title.length)
            .slice(0, limit)
            .map((result) => result.page);
    }

    // Instant search suggestions (the app calls <base>/search/suggest?q=…).
    window._demoFetch = window.fetch.bind(window);
    window.fetch = async (input, init) => {
        const url = typeof input === 'string' ? input : input.url;

        if (url.includes('/search/suggest')) {
            const query = new URL(url, window.location.href).searchParams.get('q') || '';
            const results = search(await loadIndex(), query, 8).map(({ title, url, type, section, image }) => ({ title, url, type, section, image }));

            return new Response(JSON.stringify({ query, results }), { headers: { 'Content-Type': 'application/json' } });
        }

        return window._demoFetch(input, init);
    };

    // Forms that would send data to the server.
    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || (form.getAttribute('method') || 'get').toLowerCase() !== 'post') {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            let note = form.querySelector('.demo-form-note');
            if (!note) {
                note = document.createElement('p');
                note.className = 'demo-form-note';
                note.setAttribute('role', 'status');
                form.append(note);
            }
            note.textContent = 'This is a preview of the new website, so enquiries are switched off here. On the live site this form sends the enquiry straight to the office inbox.';
        },
        true,
    );

    function previewBar() {
        const bar = document.createElement('div');
        bar.className = 'demo-bar';
        bar.textContent = 'Preview of the new Haridwar Rishikesh Tourism website – enquiry forms are switched off in this preview.';
        document.body.prepend(bar);
    }

    // The /search page: render results for ?q= in the browser.
    async function searchPage() {
        const query = new URLSearchParams(window.location.search).get('q')?.trim();
        const column = document.querySelector('main .container-x.grid > div.min-w-0');

        if (!query || !column || !window.location.pathname.replace(/\/$/, '').endsWith('/search.html')) {
            return;
        }

        document.title = `Search results for “${query}” – Haridwar Rishikesh Tourism`;
        const heading = document.querySelector('main h1');
        if (heading) {
            heading.textContent = `Search results for “${query}”`;
        }
        document.querySelectorAll('main input[type="search"]').forEach((input) => (input.value = query));

        const results = search(await loadIndex(), query, 40);
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-10';

        if (!results.length) {
            const empty = document.createElement('div');
            empty.className = 'rounded-3xl bg-white p-8 text-center ring-1 ring-ink-900/5';
            empty.innerHTML = '<p class="font-display text-2xl font-semibold text-ink-900"></p><p class="mt-2 text-ink-500">Try a shorter phrase or check the spelling.</p>';
            empty.firstElementChild.textContent = `No pages matched “${query}”`;
            wrapper.append(empty);
        } else {
            const count = document.createElement('p');
            count.className = 'text-sm text-ink-500';
            count.setAttribute('role', 'status');
            count.textContent = `${results.length}${results.length === 40 ? '+' : ''} ${results.length === 1 ? 'page' : 'pages'} found`;

            const list = document.createElement('ol');
            list.className = 'mt-4 space-y-4';

            for (const page of results) {
                const item = document.createElement('li');
                item.innerHTML = `
                    <article class="group relative flex gap-4 rounded-2xl bg-white p-4 ring-1 ring-ink-900/5 transition hover:shadow-card sm:gap-6 sm:p-5">
                        <div class="hidden w-40 shrink-0 overflow-hidden rounded-xl bg-sand-200 sm:block"><img alt="" class="aspect-[4/3] size-full object-cover" loading="lazy"></div>
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 text-xs"><span class="rounded-full bg-saffron-50 px-2.5 py-0.5 font-semibold text-saffron-700 ring-1 ring-saffron-200" data-type></span><span class="text-ink-500" data-section></span></p>
                            <h2 class="mt-2 font-display text-lg font-semibold text-ink-900 group-hover:text-brand-700"><a class="after:absolute after:inset-0"></a></h2>
                            <p class="mt-1.5 line-clamp-2 text-[15px] leading-relaxed text-ink-500" data-teaser></p>
                        </div>
                    </article>`;
                const image = item.querySelector('img');
                page.image ? (image.src = page.image) : image.parentElement.remove();
                item.querySelector('[data-type]').textContent = page.type || '';
                item.querySelector('[data-section]').textContent = page.section || '';
                const link = item.querySelector('a');
                link.href = page.url;
                link.textContent = page.title;
                item.querySelector('[data-teaser]').textContent = page.teaser || '';
                list.append(item);
            }

            wrapper.append(count, list);
        }

        column.prepend(wrapper);
    }

    document.addEventListener('DOMContentLoaded', () => {
        previewBar();
        searchPage();
    });
})();
