/**
 * Scroll-snap carousels with previous/next buttons. Content stays in the
 * HTML (crawlable); the track scrolls natively with touch and trackpads.
 */
export function initCarousels() {
    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-carousel-track]');
        const prev = carousel.querySelector('[data-carousel-prev]');
        const next = carousel.querySelector('[data-carousel-next]');

        if (!track) {
            return;
        }

        const step = () => {
            const item = track.firstElementChild;
            const gap = parseFloat(getComputedStyle(track).columnGap) || 20;
            return item ? item.getBoundingClientRect().width + gap : track.clientWidth * 0.8;
        };

        const update = () => {
            const max = track.scrollWidth - track.clientWidth - 4;
            if (prev) prev.disabled = track.scrollLeft <= 4;
            if (next) next.disabled = track.scrollLeft >= max;
        };

        prev?.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
        next?.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
        track.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });
        window.addEventListener('resize', update, { passive: true });
        update();

        if (carousel.hasAttribute('data-carousel-autoplay') && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            let paused = false;
            ['mouseenter', 'focusin', 'touchstart'].forEach((type) => carousel.addEventListener(type, () => (paused = true), { passive: true }));
            ['mouseleave', 'focusout'].forEach((type) => carousel.addEventListener(type, () => (paused = false)));

            setInterval(() => {
                if (paused || document.hidden) {
                    return;
                }
                const atEnd = track.scrollLeft >= track.scrollWidth - track.clientWidth - 4;
                track.scrollTo({ left: atEnd ? 0 : track.scrollLeft + step(), behavior: 'smooth' });
            }, 6000);
        }
    });
}
