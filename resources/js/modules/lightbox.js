/**
 * Gallery lightbox built on <dialog>: keyboard (← → Esc), swipe, captions.
 * Links point at the full-size image, so without JS they simply open it.
 */
export function initLightbox() {
    const groups = new Map();

    document.querySelectorAll('[data-lightbox]').forEach((link) => {
        const group = link.dataset.lightbox || 'default';
        if (!groups.has(group)) {
            groups.set(group, []);
        }
        groups.get(group).push(link);
    });

    if (!groups.size) {
        return;
    }

    const dialog = buildDialog();
    const image = dialog.querySelector('img');
    const caption = dialog.querySelector('figcaption');
    const counter = dialog.querySelector('[data-counter]');
    let items = [];
    let index = 0;

    const show = (i) => {
        index = (i + items.length) % items.length;
        const link = items[index];
        image.src = link.href;
        image.alt = link.dataset.caption || link.querySelector('img')?.alt || '';
        caption.textContent = link.dataset.caption || image.alt;
        counter.textContent = `${index + 1} / ${items.length}`;
    };

    groups.forEach((links) => {
        links.forEach((link, i) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                items = links;
                show(i);
                dialog.showModal();
            });
        });
    });

    dialog.querySelector('[data-prev]').addEventListener('click', () => show(index - 1));
    dialog.querySelector('[data-next]').addEventListener('click', () => show(index + 1));
    dialog.querySelector('[data-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') show(index - 1);
        if (event.key === 'ArrowRight') show(index + 1);
    });

    let startX = null;
    dialog.addEventListener('touchstart', (event) => (startX = event.touches[0].clientX), { passive: true });
    dialog.addEventListener('touchend', (event) => {
        if (startX === null) return;
        const delta = event.changedTouches[0].clientX - startX;
        if (Math.abs(delta) > 50) show(index + (delta < 0 ? 1 : -1));
        startX = null;
    });
}

function buildDialog() {
    const dialog = document.createElement('dialog');
    dialog.className = 'm-auto max-h-[92vh] max-w-[min(1100px,94vw)] overflow-visible bg-transparent p-0 backdrop:bg-ink-900/85 backdrop:backdrop-blur-sm';
    dialog.setAttribute('aria-label', 'Photo viewer');
    dialog.innerHTML = `
        <figure class="relative">
            <img class="max-h-[80vh] w-auto rounded-2xl object-contain shadow-2xl" alt="">
            <figcaption class="mt-3 flex items-center justify-between gap-4 text-sm text-white/90"></figcaption>
            <span data-counter class="absolute -top-9 left-0 text-sm text-white/70"></span>
        </figure>
        <button data-close type="button" class="absolute -top-11 right-0 grid size-10 place-items-center rounded-full bg-white/10 text-white hover:bg-white/25" aria-label="Close">✕</button>
        <button data-prev type="button" class="absolute top-1/2 -left-3 grid size-12 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-xl text-ink-900 shadow-lg hover:bg-white sm:-left-16" aria-label="Previous photo">‹</button>
        <button data-next type="button" class="absolute top-1/2 -right-3 grid size-12 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-xl text-ink-900 shadow-lg hover:bg-white sm:-right-16" aria-label="Next photo">›</button>`;
    document.body.append(dialog);
    return dialog;
}
