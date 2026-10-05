/**
 * Fade-up elements as they enter the viewport. Skipped entirely when the
 * visitor prefers reduced motion (CSS keeps them visible).
 */
export function initReveal() {
    const elements = document.querySelectorAll('[data-reveal]');

    if (!elements.length) {
        return;
    }

    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        elements.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
    );

    elements.forEach((el) => {
        // Anything already on screen is shown immediately (no flash on load).
        if (el.getBoundingClientRect().top < window.innerHeight) {
            el.classList.add('is-visible');
        } else {
            observer.observe(el);
        }
    });
}
