/**
 * "Back to top" button: appears once the visitor has scrolled most of a
 * screen down; its ring fills up as they read further down the page.
 */
export function initBackToTop() {
    const button = document.querySelector('[data-back-to-top]');

    if (!button) {
        return;
    }

    const ring = button.querySelector('[data-scroll-progress]');
    let queued = false;

    const update = () => {
        queued = false;
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;
        const progress = scrollable > 0 ? Math.min(1, window.scrollY / scrollable) : 0;

        button.classList.toggle('is-visible', window.scrollY > window.innerHeight * 0.75);
        ring?.setAttribute('stroke-dashoffset', String(100 - progress * 100));
    };

    const schedule = () => {
        if (!queued) {
            queued = true;
            requestAnimationFrame(update);
        }
    };

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });
    update();

    button.addEventListener('click', () => {
        // Smooth unless the visitor prefers reduced motion (see html { scroll-behavior }).
        window.scrollTo({ top: 0 });
        document.getElementById('main')?.focus({ preventScroll: true });
    });
}
